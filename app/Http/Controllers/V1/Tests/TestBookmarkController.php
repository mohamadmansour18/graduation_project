<?php

namespace App\Http\Controllers\V1\Tests;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListTestBookmarkedUsersRequest;
use App\Http\Resources\Tests\TestBookmarkedUserResource;
use App\Services\Tests\TestBookmarkService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TestBookmarkController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TestBookmarkService $testBookmarkService
    ) {}

    public function bookmark(int $testId): JsonResponse
    {
        $result = $this->testBookmarkService->bookmark(
            testId: $testId,
            userId: Auth::id()
        );

        return $this->dataResponse(
            data: $result,
            title: '! تم حفظ الاختبار بنجاح'
        );
    }

    public function unbookmark(int $testId): JsonResponse
    {
        $result = $this->testBookmarkService->unbookmark(
            testId: $testId,
            userId: Auth::id()
        );

        return $this->dataResponse(
            data: $result,
            title: '! تم إزالة الاختبار من المحفوظات بنجاح'
        );
    }

    public function bookmarkedUsers(ListTestBookmarkedUsersRequest $request, int $testId): JsonResponse
    {
        $paginator = $this->testBookmarkService->listBookmarkedUsers(
            testId: $testId,
            viewerId: Auth::id(),
            search: $request->searchTerm(),
            perPage: $request->perPage()
        );

        return $this->cursorPaginatedResponse(
            paginator: $paginator,
            data: TestBookmarkedUserResource::collection($paginator->items()),
            title: '! تم جلب المستخدمين بنجاح'
        );
    }
}
