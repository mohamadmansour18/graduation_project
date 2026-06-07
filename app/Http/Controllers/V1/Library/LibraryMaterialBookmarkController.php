<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Requests\Library\ListLibraryBookmarkedUsersRequest;
use App\Http\Resources\LibraryBookmarkedUserResource;
use App\Services\LibraryMaterial\LibraryMaterialBookmarkService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LibraryMaterialBookmarkController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly LibraryMaterialBookmarkService $libraryMaterialService
    ){}

    public function bookmark(int $libraryMaterial): JsonResponse
    {
        $this->libraryMaterialService->bookmark(
            userId: Auth::id(),
            materialId: $libraryMaterial
        );

        return $this->successResponse(message: '! تم حفظ المحتوى بنجاح' , );
    }

    public function unbookmark(int $libraryMaterial): JsonResponse
    {
        $this->libraryMaterialService->unbookmark(
            userId: Auth::id(),
            materialId: $libraryMaterial
        );

        return $this->successResponse(message: '! تم إزالة المحتوى من المحفوظات بنجاح');
    }

    public function bookmarkedUsers(ListLibraryBookmarkedUsersRequest $request, int $materialId): JsonResponse
    {
        $paginator = $this->libraryMaterialService->listBookmarkedUsers(
            materialId: $materialId,
            viewerId: Auth::id(),
            search: $request->searchTerm(),
            perPage: $request->perPage()
        );

        return $this->cursorPaginatedResponse(
            paginator: $paginator,
            data: LibraryBookmarkedUserResource::collection($paginator->items()),
            title: '! تم جلب المستخدمين بنجاح'
        );
    }
}
