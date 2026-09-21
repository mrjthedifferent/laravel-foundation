<?php

namespace Modules\ActivityLog\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\User\Services\ImpersonationService;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogRequestResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $request_params = $this->sanitizePayload($request->all());

        if ($request->is('api/*') && $request->method() !== 'OPTIONS') {
            try {
                $user = $request->user();
                try {
                    $responseLog = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($responseLog)) {
                        $responseLog = $this->sanitizePayload($responseLog);
                    }
                } catch (Throwable $e) {
                    $responseLog = $response->getContent();
                }
                Log::channel('daily_api')->info($request->method().' ::: '.$request->fullUrl(), [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'user' => $user ? [
                        'id' => $user->id,
                        'name' => $user->name ?? null,
                        'email' => $user->email ?? null,
                    ] : null,
                    'impersonator_id' => app(ImpersonationService::class)->impersonatorId(),
                    'parameters' => $request_params,
                    'status_code' => $response->getStatusCode(),
                    'headers' => $this->filterHeaders($request->headers->all(), ['authorization', 'cookie', 'x-xsrf-token']),
                    'response' => $responseLog,
                ]);
            } catch (Throwable $e) {
                Log::channel('daily_api')->error('Could not log API request', [
                    'error' => $e,
                    'request' => $request_params,
                ]);
            }

            return $response;
        }

        if (str_contains($request->fullUrl(), 'get-unread-notification') || str_contains($request->fullUrl(), 'server-info')) {
            return $response;
        }
        try {
            $user = $request->user();
            Log::channel('daily_admin')->info($request->method().' ::: '.$request->fullUrl(), [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name ?? null,
                    'phone' => $user->phone ?? null,
                    'email' => $user->email ?? null,
                ] : null,
                'impersonator_id' => app(ImpersonationService::class)->impersonatorId(),
                'parameters' => $request_params,
                'headers' => $this->filterHeaders($request->headers->all(), ['authorization', 'cookie', 'x-xsrf-token']),
                'status_code' => $response->getStatusCode(),
                'response' => $this->sanitizeResponseForAdminLog($this->handleResponse($response, $request)),
            ]);
        } catch (Throwable $e) {
            Log::channel('daily_admin')->error('Could not log Admin request', [
                'error' => $e->getMessage(),
                'request' => $request_params,
            ]);
        }

        return $response;
    }

    private function filterHeaders(array $headers, array $exclude): array
    {
        return array_filter($headers, function ($key) use ($exclude) {
            return ! in_array(strtolower($key), $exclude, true);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Redact secrets and tokens from nested request/response arrays before writing logs.
     *
     * @param  array<mixed>  $payload
     * @return array<mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            $keyLower = strtolower((string) $key);
            if ($this->isSensitiveKey($keyLower)) {
                $payload[$key] = $this->maskScalar($value);

                continue;
            }
            if (is_array($value)) {
                $payload[$key] = $this->sanitizePayload($value);
            }
        }

        return $payload;
    }

    private function isSensitiveKey(string $keyLower): bool
    {
        $exact = [
            'code', 'otp', 'cvv', 'pin', 'password', 'password_confirmation', 'current_password',
            'access_token', 'refresh_token', 'credit_card', 'card_number', 'secret',
        ];
        if (in_array($keyLower, $exact, true)) {
            return true;
        }
        foreach (['password', 'token', 'secret', 'authorization'] as $frag) {
            if (str_contains($keyLower, $frag)) {
                return true;
            }
        }

        return false;
    }

    private function maskScalar(mixed $value): string
    {
        if ($value === null) {
            return '***';
        }
        if (is_string($value)) {
            $len = strlen($value);

            return $len === 0 ? '***' : str_repeat('*', min($len, 32));
        }

        return '***';
    }

    /**
     * @param  array<string, mixed>|string  $response
     * @return array<string, mixed>|string
     */
    private function sanitizeResponseForAdminLog(array|string $response): array|string
    {
        if (is_array($response)) {
            return $this->sanitizePayload($response);
        }

        return $response;
    }

    private function handleResponse(Response $response, Request $request): array|string
    {
        try {
            if (! $response->getContent()) {
                return [];
            }
            if ($request->isMethod('GET')) {
                return [];
            }
            if ($response->getStatusCode() === 204) {
                return [];
            }
            if ($response->getStatusCode() === 302) {
                return ['redirect' => $response->headers->get('Location')];
            }
            if ($response->getStatusCode() >= 400) {
                try {
                    return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
                } catch (Throwable $e) {
                    return ['error' => $response->getContent()];
                }
            }

            return $response->getContent();
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
