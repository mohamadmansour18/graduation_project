<?php

namespace App\Repositories\Admin;

use App\Helpers\ImageProcessor;
use App\Models\Interest;
use App\Models\InterestCategory;
use Illuminate\Support\Facades\DB;

class AllocationDashboardRepository
{
    public function countScientificInterests(): int
    {
        return Interest::query()->count();
    }

    public function countScientificInterestCategories(): int
    {
        return InterestCategory::query()->count();
    }

    public function getScientificInterestsGrouped(): array
    {
         return InterestCategory::query()
             ->select(['id', 'title'])
             ->with([
                 'interests' => function ($query) {
                     $query->select([
                         'id',
                         'interest_category_id',
                         'name',
                         'storage_disk',
                         'icon_svg',
                         'color',
                     ]);
                 },
             ])
             ->get()
             ->map(function ($category) {
                  return [
                      'id' => $category->id,
                      'title' => $category->title,

                      'interests' => $category->interests->map(function ($interest) {
                          return [
                              'id' => $interest->id,
                              'name' => $interest->name,
                              'icon_svg' => ImageProcessor::url($interest->icon_svg , $interest->storage_disk),
                              'color' => $interest->color,
                          ];
                      })->values(),
                  ];
              })
              ->values()
              ->toArray();
    }

    public function getScientificInterestCategoriesWithCount(): array
    {
         return InterestCategory::query()
             ->select(['id', 'title'])
             ->withCount('interests')
             ->get()
             ->map(function ($category) {
                 return [
                     'id' => $category->id,
                     'title' => $category->title,
                     'interests_count' => (int) $category->interests_count,
                 ];
             })
             ->values()
             ->toArray();
    }

    public function createScientificInterest(array $data): Interest
    {
        return Interest::query()->create([
            'interest_category_id' => $data['interest_category_id'],
            'name' => $data['name'],
            'icon_svg' => $data['icon'] ?? null,

            ...array_key_exists('color', $data) && $data['color'] !== null
                ? ['color' => $data['color']]
                : [],
        ]);
    }

    public function updateScientificInterest(Interest $interest, array $data): bool
    {
        $payload = [];

        if (array_key_exists('interest_category_id', $data)) {
            $payload['interest_category_id'] = $data['interest_category_id'];
        }

        if (array_key_exists('name', $data)) {
            $payload['name'] = $data['name'];
        }

        if (array_key_exists('icon', $data)) {
            $payload['icon_svg'] = $data['icon'];
        }

        if (array_key_exists('color', $data)) {
            $payload['color'] = $data['color'];
        }

        return $interest->update($payload);
    }

    public function forceDeleteScientificInterest(Interest $interest): bool
    {
        return (bool) $interest->forceDelete();
    }

    public function findScientificInterestOrFail(int $interestId): Interest
    {
        return Interest::query()->findOrFail($interestId);
    }

    public function findBlockingSingleInterestUsage(int $interestId): ?string
    {
        if ($this->existsUserWithOnlyThisInterest($interestId)) {
            return 'user';
        }

        if ($this->existsTestWithOnlyThisInterest($interestId)) {
            return 'test';
        }

        if ($this->existsLibraryMaterialWithOnlyThisInterest($interestId)) {
            return 'library_material';
        }

        return null;
    }

    private function existsUserWithOnlyThisInterest(int $interestId): bool
    {
        return DB::table('user_interest_selections as selected')
            ->where('selected.interest_id', $interestId)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('user_interest_selections as other')
                    ->whereColumn('other.user_id', 'selected.user_id')
                    ->whereColumn('other.id', '!=', 'selected.id');
            })
            ->exists();
    }

    private function existsTestWithOnlyThisInterest(int $interestId): bool
    {
        return DB::table('test_interset_selections as selected')
            ->where('selected.interest_id', $interestId)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('test_interset_selections as other')
                    ->whereColumn('other.test_id', 'selected.test_id')
                    ->whereColumn('other.id', '!=', 'selected.id');
            })
            ->exists();
    }

    private function existsLibraryMaterialWithOnlyThisInterest(int $interestId): bool
    {
        return DB::table('library_material_interest_selections as selected')
            ->where('selected.interest_id', $interestId)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('library_material_interest_selections as other')
                    ->whereColumn('other.library_material_id', 'selected.library_material_id')
                    ->whereColumn('other.id', '!=', 'selected.id');
            })
            ->exists();
    }

    public function deleteInterestRelations(int $interestId): void
    {
        DB::table('user_interest_selections')
            ->where('interest_id', $interestId)
            ->delete();

        DB::table('test_interset_selections')
            ->where('interest_id', $interestId)
            ->delete();

        DB::table('library_material_interest_selections')
            ->where('interest_id', $interestId)
            ->delete();
    }

    public function createScientificInterestCategory(array $data): InterestCategory
    {
        return InterestCategory::query()->create([
            'title' => $data['title'],
        ]);
    }

    public function findScientificInterestCategoryOrFail(int $categoryId): InterestCategory
    {
        return InterestCategory::query()->findOrFail($categoryId);
    }

    public function updateScientificInterestCategory(InterestCategory $category, array $data): bool
    {
        return $category->update([
            'title' => $data['title'],
        ]);
    }

    public function countInterestsInsideCategory(int $categoryId): int
    {
        return DB::table('interests')
            ->where('interest_category_id', $categoryId)
            ->count();
    }

    public function forceDeleteScientificInterestCategory(InterestCategory $category): bool
    {
        return (bool) $category->forceDelete();
    }

    public function getInterestIconPathsByCategoryId(int $categoryId): array
    {
        return DB::table('interests')
            ->where('interest_category_id', $categoryId)
            ->whereNotNull('icon_svg')
            ->pluck('icon_svg')
            ->toArray();
    }
}
