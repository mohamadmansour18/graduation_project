<?php

namespace App\Http\Controllers\V1\Profile;

use App\Http\Controllers\Controller;
use App\Services\Profile\FollowService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FollowController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FollowService $followService
    ) {}
    public function follow(int $userId): JsonResponse
    {
        $this->followService->follow(
            followerUserId: Auth::id(),
            followedUserId: $userId
        );

        return $this->successResponse(
            message: 'تمت المتابعة بنجاح'
        );
    }

    public function unfollow(int $userId): JsonResponse
    {
        $this->followService->unfollow(
            followerUserId: Auth::id(),
            followedUserId: $userId
        );

        return $this->successResponse(
            message: 'تم إلغاء المتابعة بنجاح'
        );
    }

}
