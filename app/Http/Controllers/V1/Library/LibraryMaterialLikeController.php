<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Requests\Library\ListLibraryLikedUsersRequest;
use App\Http\Resources\LibraryMaterial\LibraryLikedUserResource;
use App\Services\LibraryMaterial\LibraryMaterialLikeService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LibraryMaterialLikeController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly LibraryMaterialLikeService $libraryMaterialService
    ){}
    public function like(int $libraryMaterial): JsonResponse
    {
        $this->libraryMaterialService->like(
            userId: Auth::id(),
            materialId: $libraryMaterial
        );

        return $this->successResponse(message: '! تم تسجيل الإعجاب بنجاح');
    }

    public function unlike(int $libraryMaterial): JsonResponse
    {
        $this->libraryMaterialService->unlike(
            userId:  Auth::id(),
            materialId: $libraryMaterial
        );

        return $this->successResponse(message: '! تم إزالة الإعجاب بنجاح');
    }

    public function likedUsers(ListLibraryLikedUsersRequest $request, int $materialId): JsonResponse
    {
        $paginator = $this->libraryMaterialService->listLikedUsers(
            materialId: $materialId,
            viewerId: Auth::id(),
            search: $request->searchTerm(),
            perPage: $request->perPage()
        );

        return $this->cursorPaginatedResponse(
            paginator: $paginator,
            data: LibraryLikedUserResource::collection($paginator->items()),
            title: '! تم جلب المستخدمين بنجاح'
        );
    }
}
