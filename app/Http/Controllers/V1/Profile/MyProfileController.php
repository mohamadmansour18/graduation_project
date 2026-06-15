<?php

namespace App\Http\Controllers\V1\Profile;

use App\Enums\TestSearchScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Library\SearchLibraryMaterialRequest;
use App\Http\Requests\Profile\DeleteProfilePhotoRequest;
use App\Http\Requests\Profile\MyCreatedTestsRequest;
use App\Http\Requests\Profile\MyFolderRequest;
use App\Http\Requests\Profile\MyLibraryMaterialsRequest;
use App\Http\Requests\Profile\MyProfileBookmarksRequest;
use App\Http\Requests\Profile\UpdateAcademicInformationRequest;
use App\Http\Requests\Profile\UpdatePersonalInformationRequest;
use App\Http\Requests\Profile\UpdateProfilePhotoRequest;
use App\Http\Requests\Profile\UpdateScientificInterestsRequest;
use App\Http\Requests\Search\SearchTestsRequest;
use App\Http\Resources\LibraryMaterial\LibraryMaterialListResource;
use App\Http\Resources\Profile\MyBasicProfileResource;
use App\Http\Resources\Profile\MyFolderResource;
use App\Http\Resources\Profile\MyLibraryMaterialListResource;
use App\Http\Resources\Profile\MyProfileTestResource;
use App\Http\Resources\Profile\PublicProfileFolderResource;
use App\Http\Resources\Profile\PublicProfileTestResource;
use App\Services\Home\TestSearchService;
use App\Services\LibraryMaterial\LibraryMaterialService;
use App\Services\Profile\MyProfileService;
use App\Trait\ApiResponse;
use Auth;
use Illuminate\Http\JsonResponse;

class MyProfileController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MyProfileService $myProfileService
    ){}

    public function myBasicInfo(int $userId): JsonResponse
    {
        $profile = $this->myProfileService->getMyBasicInfo(
            userId: $userId,
            viewerId: Auth::id()
        );

        return $this->dataResponse(
            data: new MyBasicProfileResource($profile),
            title: '! تم جلب بيانات الملف الشخصي بنجاح'
        );
    }

    public function updatePersonalInformation(UpdatePersonalInformationRequest $request , int $userId): JsonResponse
    {
        $this->myProfileService->updatePersonalInformation(
            userId: $userId,
            data: $request->validated(),
            viewerId: Auth::id()
        );

        return $this->successResponse(
            message: '! تم تعديل المعلومات الشخصية بنجاح'
        );
    }

    public function updateAcademicInformation(UpdateAcademicInformationRequest $request , int $userId): JsonResponse
    {
        $this->myProfileService->updateAcademicInformation(
            userId: $userId,
            data: $request->validated(),
            viewerId: Auth::id(),
            certificateImage: $request->file('certificate_image'),
            identityImage: $request->file('identity_image')
        );

        return $this->successResponse(
            message: '! تم تعديل المعلومات الدراسية بنجاح'
        );
    }

    public function updateScientificInterests(UpdateScientificInterestsRequest $request , int $userId): JsonResponse
    {
        $this->myProfileService->updateScientificInterests(
            userId: $userId,
            interestIds: $request->validated('interest_ids'),
            viewerId: Auth::id()
        );

        return $this->successResponse(
            message: '! تم تعديل الاهتمامات العلمية بنجاح'
        );
    }

    public function updatePhoto(UpdateProfilePhotoRequest $request , int $userId): JsonResponse
    {
        $this->myProfileService->updatePhoto(
            userId: $userId,
            type: $request->validated('type'),
            photo: $request->file('photo'),
            viewerId: Auth::id()
        );

        return $this->successResponse(
            message: '! تم تحديث الصورة بنجاح'
        );
    }

    public function deletePhoto(DeleteProfilePhotoRequest $request , int $userId): JsonResponse
    {
        $defaultPhotoUrl = $this->myProfileService->deletePhoto(
            userId: $userId,
            type: $request->validated('type'),
            viewerId: Auth::id()
        );

        return $this->dataResponse(
            data: [
                'default_photo_url' => $defaultPhotoUrl,
            ],
            title: '! تم حذف الصورة بنجاح'
        );
    }

    public function searchTests(SearchTestsRequest $request , TestSearchService $service)
    {
        $filters = \App\DTOs\Search\TestSearchFilters::fromRequest(
            $request->validated(),
            $request->user()->id,
            TestSearchScope::MINE->value
        );

        $paginator = $service->search($filters);

        return $this->paginatedResponse(
            paginator: $paginator,
            title: 'تم البحث عن الاختبارات بنجاح'
        );
    }

    public function myCreatedTests(MyCreatedTestsRequest $request , int $userId): JsonResponse
    {
        $paginator = $this->myProfileService->getMyCreatedTests(
            userId: $userId,
            viewerId: Auth::id(),
            tab: $request->validated('tab') ?? 'public',
            perPage: (int) ($request->validated('per_page') ?? 10)
        );

        return $this->cursorPaginatedResponse(
            paginator: $paginator,
            data: MyProfileTestResource::collection($paginator->items()),
            title: '! تم جلب الاختبارات بنجاح'
        );
    }

    public function myLibraryMaterials(MyLibraryMaterialsRequest $request , int $userId): JsonResponse
    {
        $paginator = $this->myProfileService->getMyLibraryMaterials(
            userId: $userId,
            viewerId: Auth::id(),
            tab: $request->validated('tab') ?? 'latest',
            perPage: (int) ($request->validated('per_page') ?? 10)
        );

        return $this->cursorPaginatedResponse(
            paginator: $paginator,
            data: MyLibraryMaterialListResource::collection($paginator->items()),
            title: '! تم جلب المحتوى بنجاح'
        );
    }

    public function search(SearchLibraryMaterialRequest $request, LibraryMaterialService $libraryMaterialService): JsonResponse
    {
        $result = $libraryMaterialService->searchLibraryMaterials(
            userId: $request->user()->id,
            query: $request->input('query'),
            mode: $request->input('mode', 'user_owned'),
            perPage: (int) $request->input('per_page', 20),
        );

        return $this->dataResponse(
            data: $result,
            title: '! تم البحث في محتوى المكتبة بنجاح'
        );
    }

    public function folders(MyFolderRequest $request, int $userId): JsonResponse
    {
        $paginator = $this->myProfileService->getMyFolders(
            userId: $userId,
            viewerId: Auth::id(),
            tab: $request->validated('tab') ?? 'latest',
            perPage: (int) $request->input('per_page', 20)
        );

        return $this->cursorPaginatedResponse(
            paginator: $paginator,
            data: MyFolderResource::collection($paginator->items()),
            title: '! تم جلب قوائم المستخدم بنجاح'
        );
    }

    public function bookmarks(MyProfileBookmarksRequest $request): JsonResponse
    {
        $tab = $request->tab();

        $paginator = $this->myProfileService->getBookmarks(
            userId: Auth::id(),
            tab: $tab,
            perPage: $request->perPage()
        );

        $resourceClass = match ($tab) {
            'materials' => LibraryMaterialListResource::class,
            'folders' => PublicProfileFolderResource::class,
            default => PublicProfileTestResource::class,
        };

        return $this->dataResponse(
            data: [
                'tab' => $tab,
                'items' => $resourceClass::collection($paginator->items()),
                'meta' => [
                    'per_page' => $paginator->perPage(),
                    'next_cursor' => $paginator->nextCursor()?->encode(),
                    'previous_cursor' => $paginator->previousCursor()?->encode(),
                    'has_more_pages' => $paginator->hasMorePages(),
                ],
            ],
            title: '! تم جلب المحفوظات بنجاح'
        );
    }

}
