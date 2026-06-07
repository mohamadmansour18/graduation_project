<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Requests\Library\StoreLibraryMaterialReportRequest;
use App\Services\LibraryMaterial\LibraryMaterialReportService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LibraryMaterialReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LibraryMaterialReportService $service
    ) {}

    public function store(StoreLibraryMaterialReportRequest $request, int $libraryMaterial): JsonResponse
    {
        $result = $this->service->report(
            userId: Auth::id(),
            materialId: $libraryMaterial,
            reason: $request->input('reason'),
            description: $request->input('description')
        );

        return $this->dataResponse(
            data: $result,
            title: '! تم إرسال البلاغ بنجاح'
        );
    }
}
