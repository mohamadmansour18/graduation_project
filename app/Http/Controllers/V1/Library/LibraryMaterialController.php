<?php

namespace App\Http\Controllers\V1\Library;

use App\Enums\VisibilityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Library\IndexLibraryMaterialRequest;
use App\Http\Requests\Library\SearchLibraryMaterialRequest;
use App\Http\Requests\Library\SimilarLibraryMaterialsRequest;
use App\Http\Requests\Library\StoreLibraryMaterialRequest;
use App\Http\Requests\Library\UpdateLibraryMaterialRequest;
use App\Http\Resources\LibraryMaterial\LibraryMaterialDetailsResource;
use App\Http\Resources\LibraryMaterial\MyLibraryMaterialDetailsResource;
use App\Services\AiQuestionGeneration\Validation\ImageContentHeuristicValidator;
use App\Services\AiQuestionGeneration\Validation\PdfStructureValidator;
use App\Services\LibraryMaterial\LibraryMaterialService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LibraryMaterialController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LibraryMaterialService $libraryMaterialService
    ) {}

    public function index(IndexLibraryMaterialRequest $request): JsonResponse
    {
        $result = $this->libraryMaterialService->getLibraryMaterials(
            userId: $request->user()->id,
            tab: $request->input('tab', 'trending'),
            perPage: (int) $request->input('per_page', 20),
            includeFeatured: !$request->filled('cursor'),
        );

        return $this->dataResponse(
            data: $result,
            title: '! تم جلب محتوى المكتبة بنجاح'
        );
    }

    public function search(SearchLibraryMaterialRequest $request): JsonResponse
    {
        $result = $this->libraryMaterialService->searchLibraryMaterials(
            userId: $request->user()->id,
            query: $request->input('query'),
            mode: $request->input('mode', 'all_public'),
            perPage: (int) $request->input('per_page', 20),
        );

        return $this->dataResponse(
            data: $result,
            title: '! تم البحث في محتوى المكتبة بنجاح'
        );
    }

    public function store(StoreLibraryMaterialRequest $request , PdfStructureValidator $pdfStructureValidator , ImageContentHeuristicValidator $imageContentHeuristicValidator): JsonResponse
    {
        $this->libraryMaterialService->create(
            userId: $request->user()->id,
            data: $request->validated(),
            files: $request->file('assets', []),
            pdfStructureValidator: $pdfStructureValidator,
            imageContentHeuristicValidator: $imageContentHeuristicValidator,
        );

        return $this->successResponse(
            title: '! تم إنشاء المحتوى بنجاح',
            message: $request->input('visibility_type') === VisibilityType::Public->value
                ? 'تم إرسال المحتوى للمراجعة وسيظهر بعد موافقة المشرف.'
                : 'تم حفظ المحتوى الخاص بنجاح.'
        );
    }

    public function showDetails(int $libraryMaterial): JsonResponse
    {
        $material = $this->libraryMaterialService->getPublicMaterialDetails(
            viewerUserId: Auth::id(),
            materialId: $libraryMaterial
        );

        return $this->dataResponse(
            data: new LibraryMaterialDetailsResource($material),
            title: '! تم جلب تفاصيل المحتوى بنجاح'
        );
    }

    public function showMyDetails(int $libraryMaterial): JsonResponse
    {
        $material = $this->libraryMaterialService->getMyMaterialDetails(
            userId: request()->user()->id,
            materialId: $libraryMaterial
        );

        return $this->dataResponse(
            data: new MyLibraryMaterialDetailsResource($material),
            title: '! تم جلب تفاصيل المحتوى بنجاح'
        );
    }

    public function destroy(int $materialId): JsonResponse
    {
        $this->libraryMaterialService->deleteOwnedMaterialPermanently(
            userId: Auth::id(),
            materialId: $materialId
        );

        return $this->successResponse(
            message: 'تم حذف المحتوى بنجاح'
        );
    }

    public function update(UpdateLibraryMaterialRequest $request, int $libraryMaterial): JsonResponse
    {
        $this->libraryMaterialService->updateMine(
            userId: Auth::id(),
            materialId: $libraryMaterial,
            data: $request->validated()
        );

        return $this->successResponse(
            title: '! تم تعديل المحتوى بنجاح',
            message: 'تم حفظ التعديلات بنجاح'
        );
    }

    public function similar(SimilarLibraryMaterialsRequest $request , int $materialId): JsonResponse
    {
        $result = $this->libraryMaterialService->getSimilarMaterials(
            userId: $request->user()->id,
            interestIds: $request->input('interest_ids'),
            excludeMaterialId: $materialId,
            perPage: (int) $request->input('per_page', 20)
        );

        return $this->dataResponse(
            data: $result,
            title: '! تم جلب المحتوى المشابه بنجاح'
        );
    }
}
