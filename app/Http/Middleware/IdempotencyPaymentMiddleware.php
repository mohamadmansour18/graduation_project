<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyPaymentMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        if (! $idempotencyKey) {
            return $next($request);
        }

        $userId = optional($request->user())->id ?? 'guest';

        $routeNameOrPath = optional($request->route())->getName() ?? $request->path();

        $routeParametersHash = sha1(json_encode(
            optional($request->route())->parameters() ?? [],
            JSON_UNESCAPED_UNICODE
        ));

        $requestBodyHash = sha1(json_encode(
            $request->except([]),
            JSON_UNESCAPED_UNICODE
        ));

        $fingerprint = sha1(
            $userId . '|' .
            $request->method() . '|' .
            $routeNameOrPath . '|' .
            $routeParametersHash . '|' .
            $requestBodyHash . '|' .
            $idempotencyKey
        );

        $responseCacheKey = 'idempotency:response:' . $fingerprint;
        $lockKey = 'idempotency:lock:' . $fingerprint;

        $cachedResponse = Cache::get($responseCacheKey);

        if ($cachedResponse) {
            return response(
                $cachedResponse['content'],
                $cachedResponse['status']
            )->withHeaders($cachedResponse['headers']);
        }

        $lock = Cache::lock($lockKey, 30);

        if (! $lock->get()) {
            $cachedResponse = Cache::get($responseCacheKey);

            if ($cachedResponse) {
                return response(
                    $cachedResponse['content'],
                    $cachedResponse['status']
                )->withHeaders($cachedResponse['headers']);
            }

            return response()->json([
                'success' => false,
                'title' => '! الطلب قيد المعالجة',
                'message' => 'تم استقبال نفس الطلب مسبقاً وما زال قيد المعالجة',
                'status_code' => 409,
            ], 409);
        }

        try {
            $response = $next($request);

            if (
                $response instanceof JsonResponse &&
                $response->getStatusCode() >= 200 &&
                $response->getStatusCode() < 300
            ) {
                Cache::put($responseCacheKey, [
                    'content' => $response->getContent(),
                    'status' => $response->getStatusCode(),
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ], now()->addMinutes(10));
            }

            return $response;
        } finally {
            optional($lock)->release();
        }
    }
}
