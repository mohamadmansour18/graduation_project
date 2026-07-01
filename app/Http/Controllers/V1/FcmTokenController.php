<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FCM\UpsertFcmTokenRequest;
use App\Services\Notifications\FcmTokenService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class FcmTokenController extends Controller
{
    use ApiResponse;
    public function upsertMobile(UpsertFcmTokenRequest $request, FcmTokenService $fcmTokenService,): JsonResponse
    {
        $fcmTokenService->upsertForUser(
            user: auth('api')->user(),
            token: $request->input('fcm_token'),
            platform: 'mobile',
            firebaseProject: 'mobile',
            deviceId: $request->input('device_id'),
            deviceName: $request->input('device_name'),
            userAgent: $request->userAgent(),
        );

        return $this->successResponse(
            message: 'تم تحديث رمز الإشعارات بنجاح'
        );
    }

    public function upsertWeb(UpsertFcmTokenRequest $request, FcmTokenService $fcmTokenService,): JsonResponse
    {
        $fcmTokenService->upsertForUser(
            user: auth('api')->user(),
            token: $request->input('fcm_token'),
            platform: 'web',
            firebaseProject: 'web',
            deviceId: $request->input('device_id'),
            deviceName: $request->input('device_name'),
            userAgent: $request->userAgent(),
        );

        return $this->successResponse(
            message: 'تم تحديث رمز الإشعارات بنجاح'
        );
    }
}
