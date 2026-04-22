<?php

namespace App\Support;



use Illuminate\Http\JsonResponse;

class ApiErrorResponse
{
    public static function make(
        string $title ,
        string $message,
        int $status,
        array $meta = [],
    ): JsonResponse
    {
        $request = request();
        return response()->json([
            'success' => false,
            'title' => $title,
            'message' => $message,
            'meta' => array_merge([
                'request_id' => $request?->attributes->get('request_id'),
                'timestamp' => now()->toISOString(),
            ], $meta),
            'status_code' => $status,
        ] , $status);
    }
}
