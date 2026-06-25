<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Requests\Admin\ListDashboardLibraryMaterialsRequest;
use App\Http\Requests\Admin\ListLibraryMaterialReportsRequest;
use App\Http\Requests\DeleteLibraryMaterialRequest;
use App\Http\Requests\Library\SearchLibraryMaterialRequest;
use App\Http\Resources\DashboardLibraryMaterialDetailsResource;
use App\Http\Resources\DashboardLibraryMaterialReportsResource;
use App\Http\Resources\DashboardLibraryMaterialStatusHistoryResource;
use App\Services\Admin\LibraryDashboardService;
use App\Services\Admin\TestDashboardService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class LibraryDashboardController
{
    use ApiResponse;

    public function libraryMaterials(ListDashboardLibraryMaterialsRequest $request, LibraryDashboardService $service): JsonResponse
    {
        $result = $service->listApprovedMaterials(
            filters: $request->validated()
        );

        return $this->dataResponse(
            data: $result,
            title: '! تم جلب محتوى المكتبة بنجاح',
        );
    }

    public function search(SearchLibraryMaterialRequest $request , LibraryDashboardService $service): JsonResponse
    {
        $result = $service->searchLibraryMaterials(
            userId: $request->user()->id,
            query: $request->input('query'),
            mode: $request->input('mode', 'all_public'),
            perPage: (int) $request->input('per_page', 50),
        );

        return $this->dataResponse(
            data: $result,
            title: '! تم البحث في محتوى المكتبة بنجاح'
        );
    }

    public function showLibraryMaterial(int $libraryMaterialId, LibraryDashboardService $service): JsonResponse
    {
        $material = $service->showMaterialDetails(
            libraryMaterialId: $libraryMaterialId
        );

        return $this->dataResponse(
            data: new DashboardLibraryMaterialDetailsResource($material),
            title: '! تم جلب تفاصيل المحتوى بنجاح'
        );
    }

    public function libraryMaterialReports(int $libraryMaterialId, ListLibraryMaterialReportsRequest $request, LibraryDashboardService $service): JsonResponse
    {
        $result = $service->getCurrentVersionReports(
            libraryMaterialId: $libraryMaterialId,
            filters: $request->validated()
        );

        return $this->dataResponse(
            data: new DashboardLibraryMaterialReportsResource($result),
            title: '! تم جلب سجل البلاغات بنجاح'
        );
    }

    public function libraryMaterialStatusHistory(int $libraryMaterialId, LibraryDashboardService $service): JsonResponse
    {
        $result = $service->getStatusHistory(
            libraryMaterialId: $libraryMaterialId
        );

        return $this->dataResponse(
            data: new DashboardLibraryMaterialStatusHistoryResource($result),
            title: '! تم جلب سجل الحالة بنجاح'
        );
    }

    public function approveLibraryMaterial(int $libraryMaterialId, LibraryDashboardService $service): JsonResponse
    {
        $service->approve(
            user: request()->user(),
            libraryMaterialId: $libraryMaterialId
        );

        return $this->successResponse(
            message: '! تمت الموافقة على نشر المحتوى بنجاح'
        );
    }

    public function deleteLibraryMaterial(DeleteLibraryMaterialRequest $request ,int $libraryMaterialId, LibraryDashboardService $service): JsonResponse
    {
        $service->delete(
            user: request()->user(),
            libraryMaterialId: $libraryMaterialId,
            deleteReason: $request->validated('delete_reason')
        );

        return $this->successResponse(
            message: '! تم حذف المحتوى بنجاح'
        );
    }
}
