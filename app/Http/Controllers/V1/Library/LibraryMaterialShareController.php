<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Services\LibraryMaterial\LibraryMaterialShareService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class LibraryMaterialShareController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LibraryMaterialShareService $service
    ) {}

    public function generate(int $materialId): JsonResponse
    {
        $result = $this->service->generateShareLink(
            materialId: $materialId
        );

        return $this->dataResponse(
            data:  $result,
            title: '! تم جلب رابط المشاركة بنجاح'
        );
    }

    public function resolve(string $slug): JsonResponse
    {
        $result = $this->service->resolveShareSlug(
            slug: $slug,
            userId: auth()->id()
        );

        return $this->dataResponse(
            data:  $result,
        );
    }
}
