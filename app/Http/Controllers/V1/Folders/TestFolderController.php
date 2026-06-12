<?php

namespace App\Http\Controllers\V1\Folders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Folders\StoreTestFolderRequest;
use App\Http\Requests\Folders\UpdateTestFolderRequest;
use App\Http\Resources\MyProfileFolderTestResource;
use App\Services\Folders\TestFolderService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TestFolderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TestFolderService $service
    )
    {}

    public function storeFolder(StoreTestFolderRequest $request): JsonResponse
    {
        $this->service->createFolder(
            userId: Auth::id(),
            data: $request->validated()
        );

        return $this->successResponse(
            message: '! تم إنشاء القائمة بنجاح'
        );
    }

    public function deleteFolder(int $folder): JsonResponse
    {
        $this->service->deleteFolder(
            userId: request()->user()->id,
            folderId: $folder
        );

        return $this->successResponse(
            message: '! تم حذف القائمة بنجاح'
        );
    }

    public function folderTests(int $folder): JsonResponse
    {
        $tests = $this->service->getFolderTests(
            userId: Auth::id(),
            folderId: $folder
        );

        return $this->dataResponse(
            data: MyProfileFolderTestResource::collection($tests),
            title: '! تم جلب محتوى القائمة بنجاح'
        );
    }

    public function updateFolder(UpdateTestFolderRequest $request, int $folder): JsonResponse
    {
        $this->service->updateFolder(
            userId: Auth::id(),
            folderId: $folder,
            data: $request->validated()
        );

        return $this->successResponse(
            message: '! تم تعديل القائمة بنجاح'
        );
    }
}
