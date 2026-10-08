<?php

declare(strict_types=1);

namespace Mrj\Foundation\Http\Middleware;

use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;
use Mrj\Foundation\Models\IdempotencyKey;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Makes a write request safe to retry. A client sends an `Idempotency-Key`
 * header (a UUID or ULID it generates per logical operation); the first
 * response is stored and any retry with the same key gets that response back
 * instead of running the request again, marked `Idempotent-Replayed: true`.
 *
 * - The same key with a different method, path or body: 422.
 * - The same key while the first request is still running: 409.
 * - Server errors (5xx) and exceptions are not stored, so they can be retried.
 * - Keys are scoped to the signed-in user (or the IP address for guests) and
 *   expire after `foundation.idempotency.ttl_hours`.
 *
 * Alias `idempotent`; `idempotent:required` also rejects requests without a key.
 * Only POST, PUT, PATCH and DELETE are affected.
 *
 * @api
 */
final class EnsureIdempotency
{
    public const string HEADER = 'Idempotency-Key';

    public const string REPLAYED_HEADER = 'Idempotent-Replayed';

    private const array WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        if (! in_array($request->method(), self::WRITE_METHODS, true)) {
            return $next($request);
        }

        $key = $request->header(self::HEADER);

        if ($key === null || $key === '') {
            return $mode === 'required'
                ? JsonResponseFactory::error(__('foundation::foundation.idempotency.key_required'), null, 400)
                : $next($request);
        }

        if (! preg_match('/^[A-Za-z0-9_\-]{8,255}$/', $key)) {
            return JsonResponseFactory::error(__('foundation::foundation.idempotency.key_invalid'), null, 400);
        }

        $scope = $request->user() !== null
            ? 'user:'.$request->user()->getAuthIdentifier()
            : 'ip:'.$request->ip();
        $hash = hash('sha256', $request->method().'|'.$request->path().'|'.$request->getContent());

        $existing = IdempotencyKey::query()
            ->where('scope', $scope)
            ->where('key', $key)
            ->where('expires_at', '>', now())
            ->first();

        if ($existing !== null) {
            return $this->answerExisting($existing, $hash);
        }

        // Expired leftovers would block the unique index; clear this one key.
        IdempotencyKey::query()->where('scope', $scope)->where('key', $key)->delete();

        try {
            $record = IdempotencyKey::create([
                'scope' => $scope,
                'key' => $key,
                'method' => $request->method(),
                'path' => '/'.ltrim($request->path(), '/'),
                'request_hash' => $hash,
                'expires_at' => now()->addHours((int) config('foundation.idempotency.ttl_hours', 24)),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Another request with this key started between the lookup and the insert.
            return JsonResponseFactory::error(__('foundation::foundation.idempotency.in_progress'), null, 409);
        }

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            $record->delete();

            throw $e;
        }

        if ($response->getStatusCode() >= 500) {
            $record->delete();

            return $response;
        }

        $record->update([
            'status_code' => $response->getStatusCode(),
            'response_headers' => ['Content-Type' => [(string) $response->headers->get('Content-Type', 'application/json')]],
            'response_body' => (string) $response->getContent(),
        ]);

        return $response;
    }

    private function answerExisting(IdempotencyKey $existing, string $hash): Response
    {
        if (! hash_equals($existing->request_hash, $hash)) {
            return JsonResponseFactory::error(__('foundation::foundation.idempotency.key_reused'), null, 422);
        }

        if (! $existing->isComplete()) {
            return JsonResponseFactory::error(__('foundation::foundation.idempotency.in_progress'), null, 409);
        }

        $headers = $existing->response_headers ?? [];
        $headers[self::REPLAYED_HEADER] = ['true'];

        return new HttpResponse((string) $existing->response_body, (int) $existing->status_code, $headers);
    }
}
