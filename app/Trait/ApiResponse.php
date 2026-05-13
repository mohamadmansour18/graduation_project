<?php

namespace App\Trait;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
trait ApiResponse
{
    protected function successResponse(string $title = '! تمت العملية بنجاح' , string $message = '' , int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'title' => $title,
            'message' => $message,
            'status_code' => $statusCode,
        ], $statusCode);
    }

    protected function dataResponse(mixed $data = null , string $title = '! تم جلب البيانات بنجاح', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'title' => $title,
            'data' => $data,
            'status_code' => $statusCode,
        ], $statusCode);
    }

    protected function paginatedResponse(LengthAwarePaginator $paginator , mixed $data = null , string $title = '! تم جلب البيانات بنجاح', int $statusCode = 200): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $title,
            'data' => $data ?? $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
            'status_code' => $statusCode,
        ], $statusCode);
    }

    protected function cursorPaginatedResponse(CursorPaginator $paginator, mixed $data = null , string $title = '! تم جلب البيانات بنجاح', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $title,
            'data' => $data ?? $paginator->items(),
            'meta' => [
                'per_page' => $paginator->perPage(),
                'next_cursor' => optional($paginator->nextCursor())->encode(),
                'prev_cursor' => optional($paginator->previousCursor())->encode(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
            'status_code' => $statusCode,
        ], $statusCode);
    }

    protected function downloadResponse(string $filePath , ?string $downloadName = null, array $headers = []): BinaryFileResponse {
        return response()
            ->download($filePath, $downloadName, $headers);
    }

    protected function downloadAndDeleteResponse(string $filePath , ?string $downloadName = null, array $headers = []): BinaryFileResponse {
        return response()
            ->download($filePath, $downloadName, $headers)
            ->deleteFileAfterSend(true);
    }
}
