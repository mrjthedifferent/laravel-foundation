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
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

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
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e): Response
    {
        if ($request->is('api/*')) {
            if ($e instanceof ValidationException) {
                $message = collect($e->errors())->flatten()->implode(' ');

                return JsonResponseFactory::validationError('Validation Error: '.$message, $e->errors());
            }
            if ($e instanceof ModelNotFoundException) {
                return JsonResponseFactory::notFound('Resource not found');
            }
            if ($e instanceof AuthenticationException) {
                return JsonResponseFactory::unauthorized();
            }
            if ($e instanceof AuthorizationException) {
                return JsonResponseFactory::forbidden($e->getMessage() ?: 'Unauthorized');
            }
            if ($e instanceof AccessDeniedHttpException) {
                return JsonResponseFactory::forbidden(
                    $e->getMessage() ?: 'You are not authorized to subscribe to this channel.'
                );
            }
            if ($e instanceof NotFoundHttpException) {
                return JsonResponseFactory::notFound($e->getMessage() ?: 'Not found.');
            }
            if ($e instanceof HttpException) {
                return JsonResponseFactory::error($e->getMessage() ?: 'Error.', null, $e->getStatusCode());
            }
            if ($e instanceof BroadcastException) {
                return JsonResponseFactory::serverError(
                    config('app.debug') ? ($e->getMessage() ?: 'Broadcasting service unavailable.') : 'Broadcasting service unavailable.'
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

        return parent::render($request, $e);
    }

    /**
     * Build a client-safe message for unhandled API server errors.
     */
    protected function safeServerErrorMessage(Throwable $e): string
    {
        if (config('app.debug')) {
            return $e->getMessage() ?: 'An error occurred.';
        }

        return 'An unexpected error occurred. Please try again later.';
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
