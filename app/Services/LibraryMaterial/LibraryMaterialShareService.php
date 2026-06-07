<?php

namespace App\Services\LibraryMaterial;

use App\Exceptions\Api\LibraryMaterialException;
use App\Models\LibraryMaterial;
use App\Repositories\Library\LibraryMaterialShareRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LibraryMaterialShareService
{
    public function __construct(
        private readonly LibraryMaterialShareRepository $repository
    ) {}

    public function generateShareLink(int $materialId): array
    {
        return DB::transaction(function () use ($materialId) {

            $material = $this->repository->findShareDataById($materialId);

            if (! $material) {
                throw LibraryMaterialException::materialNotFound();
            }


            if (! $material->share_slug) {
                $slug = $this->generateUniqueSlug();

                $material->forceFill([
                    'share_slug' => $slug,
                ])->save();

            } else {
                $slug = $material->share_slug;
            }

            return [
                'share_slug' => $slug,
                'share_url' => url('/share/library/' . $slug),
            ];
        });
    }

    public function resolveShareSlug(string $slug, int $userId): array
    {
        $material = $this->repository->findByShareSlug($slug);

        if (! $material) {
            throw LibraryMaterialException::materialNotFound();
        }

        return [
            'material_id' => $material->id,
            'is_owner' => (int) $material->creator_user_id === $userId,
        ];
    }

    private function generateUniqueSlug(): string
    {
        do {
            $slug = Str::random(24);
        } while ($this->repository->slugExists($slug));

        return $slug;
    }
}
