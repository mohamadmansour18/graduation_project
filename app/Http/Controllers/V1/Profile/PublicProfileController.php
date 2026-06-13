<?php

namespace App\Http\Controllers\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\PublicProfileFoldersRequest;
use App\Http\Requests\Profile\PublicProfileFollowListRequest;
use App\Http\Requests\Profile\PublicProfileMaterialsRequest;
use App\Http\Requests\Profile\PublicProfileTestsRequest;
use App\Http\Resources\LibraryMaterial\LibraryMaterialListResource;
use App\Http\Resources\PublicProfileFolderHeaderResource;
use App\Http\Resources\PublicProfileFolderResource;
use App\Http\Resources\PublicProfileFollowUserResource;
use App\Http\Resources\PublicProfileOverviewResource;
use App\Http\Resources\PublicProfileTestResource;
use App\Models\User;
use App\Services\Profile\PublicProfileService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class PublicProfileController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PublicProfileService $service
    ) {}

    public function show(int $userId): JsonResponse
    {
        $overview = $this->service->getOverview(
            viewer: Auth::id(),
            profileOwner: $userId
        );

        return $this->dataResponse(
            data: new PublicProfileOverviewResource($overview),
            title: '! تم جلب نظرة عامة عن الملف الشخصي بنجاح'
        );
    }

    public function tests(PublicProfileTestsRequest $request, int $userId): JsonResponse
    {
        $paginator = $this->service->getUserPublishedTests(
            viewer: Auth::id(),
            profileOwner: $userId,
            tab: $request->tab(),
            perPage: $request->perPage()
        );

        return $this->cursorPaginatedResponse(
            paginator: $paginator,
            data: PublicProfileTestResource::collection($paginator->items()),
            title: '! تم جلب اختبارات المستخدم بنجاح'
        );
    }

    public function folders(PublicProfileFoldersRequest $request, int $userId): JsonResponse
    {
        $paginator = $this->service->getUserPublicFolders(
            viewer: Auth::id(),
            profileOwner: $userId,
            perPage: $request->perPage()
        );

        return $this->cursorPaginatedResponse(
            paginator: $paginator,
            data: PublicProfileFolderResource::collection($paginator->items()),
            title: '! تم جلب قوائم المستخدم بنجاح'
        );
    }

    public function materials(PublicProfileMaterialsRequest $request, int $userId): JsonResponse
    {
        $paginator = $this->service->getUserPublicMaterials(
            viewer: Auth::id(),
            profileOwner: $userId,
            tab: $request->tab(),
            perPage: $request->perPage()
        );

        return $this->cursorPaginatedResponse(
            paginator: $paginator,
            data: LibraryMaterialListResource::collection($paginator->items()),
            title: '! تم جلب محتوى المستخدم بنجاح'
        );
    }

    public function shareLink(int $userId): JsonResponse
    {
        $data = $this->service->getShareLink($userId);

        return $this->dataResponse(
            data: $data,
            title: '! تم جلب رابط مشاركة الملف الشخصي بنجاح'
        );
    }

    public function resolveShareSlug(string $slug): JsonResponse
    {
        $data = $this->service->resolveSlug(
            slug: $slug,
            viewerId: Auth::id()
        );

        return $this->dataResponse(
            data: $data,
            title: '! تم جلب بيانات رابط الملف الشخصي بنجاح'
        );
    }

    public function folderContent(int $folder): JsonResponse
    {
        $data = $this->service->getFolderContent(
            viewerId: Auth::id(),
            folderId: $folder,
        );

        return $this->dataResponse(
            data: [
                'folder' => new PublicProfileFolderHeaderResource($data['folder']),
                'items' => PublicProfileTestResource::collection($data['tests']),
            ],
            title: '! تم جلب محتوى القائمة بنجاح'
        );
    }

    public function academicCertificate(int $userId): \Illuminate\Http\Response
    {
        $certificate = $this->service->getAcademicCertificate($userId);

        $encryptedContent = Storage::disk($certificate->storage_disk)->get($certificate->storage_path);

        $decryptedContent = Crypt::decrypt($encryptedContent);

        return response($decryptedContent, 200, [
            'Content-Type' => $certificate->mime_type,
            'Content-Disposition' => 'inline; filename="' . basename($certificate->original_name) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }

    public function followers(PublicProfileFollowListRequest $request, int $userId): JsonResponse
    {
        $paginator = $this->service->getFollowers(
            viewer: Auth::id(),
            profileOwner: $userId,
            search: $request->search(),
            perPage: $request->perPage()
        );

        return $this->cursorPaginatedResponse(
            paginator: $paginator,
            data: PublicProfileFollowUserResource::collection($paginator->items()),
            title: '! تم جلب قائمة المتابعين بنجاح'
        );
    }

    public function following(PublicProfileFollowListRequest $request, int $userId): JsonResponse
    {
        $paginator = $this->service->getFollowing(
            viewer: $request->user()->id,
            profileOwner: $userId,
            search: $request->search(),
            perPage: $request->perPage()
        );

        return $this->cursorPaginatedResponse(
            paginator: $paginator,
            data: PublicProfileFollowUserResource::collection($paginator->items()),
            title: '! تم جلب قائمة من يتابع بنجاح'
        );
    }

    public function bookmarkFolder(int $folder): JsonResponse
    {
        $this->service->bookmarkFolder(
            userId: Auth::id(),
            folderId: $folder
        );

        return $this->successResponse(
            message: '! تم حفظ القائمة بنجاح'
        );
    }

    public function unbookmarkFolder(int $folder): JsonResponse
    {
        $this->service->unbookmarkFolder(
            userId: Auth::id(),
            folderId: $folder
        );

        return $this->successResponse(
            message: '! تم إزالة القائمة من المحفوظات بنجاح'
        );
    }
}
