<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Small helper that keeps every API response in the same envelope:
 * { "success": bool, "data": mixed, "message": string|null, "errors": object|null }
 */
class ApiResponse
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public static function ok(mixed $data = null, ?string $message = null, int $status = 200, ?array $meta = null): JsonResponse
    {
        $payload = [
            'success' => true,
            'data' => $data,
            'message' => $message,
        ];

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * @param  array<string, array<int, string>>|null  $errors
     */
    public static function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
