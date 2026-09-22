<?php

namespace Mrj\Foundation\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;
use Override;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/** @api */
class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<Throwable>, LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    #[Override]
    public function register(): void
    {
        $this->reportable(function (Throwable $e): void {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @throws Throwable
     */
    #[Override]
    public function render($request, Throwable $e): Response
    {
        if ($request->is('api/*')) {
            if ($e instanceof ValidationException) {
                $message = collect($e->errors())->flatten()->implode(' ');

                return JsonResponseFactory::validationError(__('foundation::foundation.api.validation_error', ['message' => $message]), $e->errors());
            }
            if ($e instanceof ModelNotFoundException) {
                return JsonResponseFactory::notFound(__('foundation::foundation.api.resource_not_found'));
            }
            if ($e instanceof AuthenticationException) {
                return JsonResponseFactory::unauthorized();
            }
            if ($e instanceof AuthorizationException) {
                return JsonResponseFactory::forbidden($e->getMessage() ?: __('foundation::foundation.api.unauthorized'));
            }
            if ($e instanceof AccessDeniedHttpException) {
                return JsonResponseFactory::forbidden(
                    $e->getMessage() ?: __('foundation::foundation.api.channel_forbidden')
                );
            }
            if ($e instanceof NotFoundHttpException) {
                return JsonResponseFactory::notFound($e->getMessage() ?: __('foundation::foundation.api.not_found'));
            }
            if ($e instanceof HttpException) {
                return JsonResponseFactory::error($e->getMessage() ?: __('foundation::foundation.api.error'), null, $e->getStatusCode());
            }
            if ($e instanceof BroadcastException) {
                return JsonResponseFactory::serverError(
                    config('app.debug') ? ($e->getMessage() ?: __('foundation::foundation.api.broadcasting_unavailable')) : __('foundation::foundation.api.broadcasting_unavailable')
                );
            }

            // Domain exceptions that define their own render() (e.g. approval
            // idempotency guards) are honored here too — parent::render() only
            // does this for non-API routes because of the early return above.
            if (method_exists($e, 'render') && ($rendered = $e->render($request)) instanceof Response) {
                return $rendered;
            }

            // Unhandled server errors: never leak internal exception messages
            // (DB/SQL details, stack info) to API clients in production. Detail
            // is only surfaced when APP_DEBUG is enabled; it is always logged.
            $this->logApiException($request, $e);

            return JsonResponseFactory::serverError($this->safeServerErrorMessage($e));
        }

        // Every admin controller used to wrap its Action call in a try/catch
        // that flashed a generic "Failed to X" message and redirected back,
        // repeated near-identically ~30 times — and swallowing the real
        // exception before it ever reached here, so ErrorReporter and the
        // logs never saw it. Controllers now let exceptions propagate; this
        // is the one place that renders the same friendly fallback, for a
        // web request whose error page a visitor would otherwise never want
        // to see (an unhandled 500 in production). Anything Laravel already
        // renders sensibly — validation, auth, 404s, explicit HTTP statuses
        // — is left to parent::render(); in non-production the real error
        // page still shows, so debugging is unaffected.
        if (app()->isProduction() && $this->isUnhandledServerError($e)) {
            return redirect()->back()
                ->withInput($request->except($this->dontFlash))
                ->with('error', __('foundation::foundation.errors.generic_failure'));
        }

        return parent::render($request, $e);
    }

    private function isUnhandledServerError(Throwable $e): bool
    {
        return ! $e instanceof HttpExceptionInterface
            && ! $e instanceof ValidationException
            && ! $e instanceof AuthenticationException
            && ! $e instanceof AuthorizationException
            && ! $e instanceof ModelNotFoundException;
    }

    /**
     * Build a client-safe message for unhandled API server errors.
     */
    protected function safeServerErrorMessage(Throwable $e): string
    {
        if (config('app.debug')) {
            return $e->getMessage() ?: __('foundation::foundation.api.error_occurred');
        }

        return __('foundation::foundation.api.unexpected_error');
    }

    /**
     * Log API exception details for debugging 500 responses.
     */
    protected function logApiException(Request $request, Throwable $e): void
    {
        Log::error('API exception', [
            'path' => $request->path(),
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
}
