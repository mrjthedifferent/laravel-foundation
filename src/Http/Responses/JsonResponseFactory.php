<?php

namespace Mrj\Foundation\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * JSON Response Factory
 *
 * Provides consistent JSON response structure across the API.
 * Replaces the global apiResponse() helper with a more robust solution.
 */
final class JsonResponseFactory
{
    /**
     * Return a success response
     */
    public static function success(
        string $message,
        mixed $data = null,
        int $code = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ];

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error response
     */
    public static function error(
        string $message,
        mixed $errors = null,
        int $code = 400,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => now()->toIso8601String(),
        ];

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a paginated response
     */
    public static function paginated(
        string $message,
        LengthAwarePaginator $paginator,
        ?callable $transformer = null,
        array $meta = []
    ): JsonResponse {
        $items = $transformer
            ? $paginator->through($transformer)->items()
            : $paginator->items();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'meta' => array_merge($meta, [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ]),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Return a created response (201)
     */
    public static function created(
        string $message,
        mixed $data = null,
        ?string $location = null
    ): JsonResponse {
        $response = self::success($message, $data, 201);

        if ($location) {
            $response->header('Location', $location);
        }

        return $response;
    }

    /**
     * Return a no content response (204)
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return an unauthorized response (401)
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, null, 401);
    }

    /**
     * Return a forbidden response (403)
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, null, 403);
    }

    /**
     * Return a not found response (404)
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, null, 404);
    }

    /**
     * Return a validation error response (422)
     */
    public static function validationError(string $message, array $errors): JsonResponse
    {
        return self::error($message, $errors, 422);
    }

    /**
     * Return a server error response (500)
     */
    public static function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return self::error($message, null, 500);
    }
}
