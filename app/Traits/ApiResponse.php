<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * API Response Trait
 * 
 * Provides standardized JSON response methods for API controllers.
 * Ensures consistent response format across all API endpoints.
 * 
 * Response Format:
 * {
 *     "success": true|false,
 *     "message": "Human readable message",
 *     "data": {...}|[...],
 *     "meta": {...},      // Optional: pagination, timestamps, etc.
 *     "errors": {...}     // Only on error responses
 * }
 */
trait ApiResponse
{
    /**
     * Return a success response
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            // Handle paginated data
            if ($data instanceof LengthAwarePaginator) {
                $response['data'] = $data->items();
                $response['meta'] = array_merge([
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                ], $meta);
            } else {
                $response['data'] = $data;
                if (!empty($meta)) {
                    $response['meta'] = $meta;
                }
            }
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a created response (201)
     */
    protected function created(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return $this->success($data, $message, 201);
    }

    /**
     * Return an accepted response (202) - for async operations
     */
    protected function accepted(
        string $message = 'Request accepted for processing',
        array $meta = []
    ): JsonResponse {
        return $this->success(null, $message, 202, $meta);
    }

    /**
     * Return a no content response (204)
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return an error response
     */
    protected function error(
        string $message = 'An error occurred',
        int $statusCode = 400,
        array $errors = [],
        string $errorCode = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response (422)
     */
    protected function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->error($message, 422, $errors, 'VALIDATION_ERROR');
    }

    /**
     * Return an unauthorized response (401)
     */
    protected function unauthorized(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return $this->error($message, 401, [], 'UNAUTHORIZED');
    }

    /**
     * Return a forbidden response (403)
     */
    protected function forbidden(
        string $message = 'Access denied'
    ): JsonResponse {
        return $this->error($message, 403, [], 'FORBIDDEN');
    }

    /**
     * Return a not found response (404)
     */
    protected function notFound(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->error($message, 404, [], 'NOT_FOUND');
    }

    /**
     * Return a conflict response (409)
     */
    protected function conflict(
        string $message = 'Resource conflict'
    ): JsonResponse {
        return $this->error($message, 409, [], 'CONFLICT');
    }

    /**
     * Return a too many requests response (429)
     */
    protected function tooManyRequests(
        string $message = 'Too many requests',
        int $retryAfter = 60
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter,
        ], 429)->header('Retry-After', $retryAfter);
    }

    /**
     * Return a server error response (500)
     */
    protected function serverError(
        string $message = 'Internal server error'
    ): JsonResponse {
        return $this->error($message, 500, [], 'SERVER_ERROR');
    }

    /**
     * Return a service unavailable response (503)
     */
    protected function serviceUnavailable(
        string $message = 'Service temporarily unavailable',
        int $retryAfter = 300
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'SERVICE_UNAVAILABLE',
            'retry_after' => $retryAfter,
        ], 503)->header('Retry-After', $retryAfter);
    }

    /**
     * Return a paginated success response with additional helpers
     */
    protected function paginated(
        LengthAwarePaginator $paginator,
        string $message = 'Success'
    ): JsonResponse {
        return $this->success($paginator, $message);
    }

    /**
     * Return a response with a download header
     */
    protected function download(
        string $content,
        string $filename,
        string $mimeType = 'application/octet-stream'
    ): \Illuminate\Http\Response {
        return response($content)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
