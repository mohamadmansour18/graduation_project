<?php

namespace App\Services\Admin;

use App\Exceptions\Api\DashboardUserException;
use App\Models\Interest;
use App\Repositories\Admin\AllocationDashboardRepository;
use App\Services\Cache\CacheKeys;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AllocationDashboardService
{
    public function __construct(
        private readonly AllocationDashboardRepository $allocationDashboardRepository
    )
    {}

    public function getOwnerStatistics(): array
    {
        return [
            'scientific_interests_count' => $this->allocationDashboardRepository
                ->countScientificInterests(),

            'scientific_interest_categories_count' => $this->allocationDashboardRepository
                ->countScientificInterestCategories(),

            'current_platform_fee_percent' => (float) config('payments.platform_fee_percent', 20),
        ];
    }

    public function getScientificInterestsGrouped(): array
    {
        return $this->allocationDashboardRepository->getScientificInterestsGrouped();
    }

    public function getScientificInterestCategories(): array
    {
        return $this->allocationDashboardRepository->getScientificInterestCategoriesWithCount();
    }

    public function createScientificInterest(int $ownerId, array $data, ?UploadedFile $icon = null): void
    {

        DB::transaction(function () use ($ownerId, $data, $icon) {
            if ($icon) {
                $data['icon'] = $this->storeInterestIcon($icon);
            }

            $interest = $this->allocationDashboardRepository->createScientificInterest($data);

            DB::afterCommit(function () use ($ownerId, $interest) {
                CacheKeys::clearScientificInterests();

                Log::channel('audit')->info('Scientific interest created', [
                    'action' => 'dashboard.scientific_interests.create',
                    'owner_id' => $ownerId,
                    'interest_id' => $interest->id,
                ]);
            });
        });
    }

    public function updateScientificInterest(int $ownerId, int $interestId, array $data, ?UploadedFile $icon = null): void
    {

        DB::transaction(function () use ($ownerId, $interestId, $data, $icon) {

            $interest = $this->allocationDashboardRepository->findScientificInterestOrFail($interestId);

            $oldIconPath = $interest->icon;

            if ($icon) {
                $data['icon'] = $this->storeInterestIcon($icon);
            }

            $this->allocationDashboardRepository->updateScientificInterest(
                interest: $interest,
                data: $data,
            );

            if ($icon && $oldIconPath) {
                Storage::disk('public')->delete($oldIconPath);
            }

            DB::afterCommit(function () use ($ownerId, $interest, $data) {
                CacheKeys::clearScientificInterests();
                CacheKeys::clearScientificInterests();

                Log::channel('audit')->info('Scientific interest updated', [
                    'action' => 'dashboard.scientific_interests.update',
                    'owner_id' => $ownerId,
                    'interest_id' => $interest->id,
                    'updated_fields' => array_keys($data),
                ]);

            });
        });
    }

    public function deleteScientificInterest(int $ownerId, int $interestId): void
    {
        DB::transaction(function () use ($ownerId, $interestId) {
            $interest = $this->allocationDashboardRepository->findScientificInterestOrFail($interestId);

            $blockingUsage = $this->allocationDashboardRepository
                ->findBlockingSingleInterestUsage($interest->id);

            if ($blockingUsage !== null) {
                throw DashboardUserException::cannotDeleteLastScientificInterest($blockingUsage);
            }

            $iconPath = $interest->icon;

            $this->allocationDashboardRepository->deleteInterestRelations($interest->id);

            $this->allocationDashboardRepository->forceDeleteScientificInterest($interest);

            if ($iconPath) {
                Storage::disk('public')->delete($iconPath);
            }

            DB::afterCommit(function () use ($ownerId, $interestId) {
                CacheKeys::clearScientificInterests();
                CacheKeys::clearTestsByInterest();

                Log::channel('audit')->info('Scientific interest force deleted', [
                    'action' => 'dashboard.scientific_interests.delete',
                    'owner_id' => $ownerId,
                    'interest_id' => $interestId,
                ]);
            });

        });
    }

    private function storeInterestIcon(UploadedFile $icon): string
    {
        return $icon->store('interest-icons', 'public');
    }

    public function createScientificInterestCategory(int $ownerId, array $data): void
    {

        DB::transaction(function () use ($ownerId, $data) {
            $category = $this->allocationDashboardRepository->createScientificInterestCategory($data);

            DB::afterCommit(function () use ($ownerId, $category) {

                CacheKeys::clearScientificInterests();

                Log::channel('audit')->info('Scientific interest category created', [
                    'action' => 'dashboard.scientific_interest_categories.create',
                    'owner_id' => $ownerId,
                    'category_id' => $category->id,
                ]);
            });

        });
    }

    public function updateScientificInterestCategory(int $ownerId, int $categoryId, array $data): void
    {
        DB::transaction(function () use ($ownerId, $categoryId, $data) {
            $category = $this->allocationDashboardRepository
                ->findScientificInterestCategoryOrFail($categoryId);

            $this->allocationDashboardRepository->updateScientificInterestCategory(
                category: $category,
                data: $data,
            );

            DB::afterCommit(function () use ($ownerId, $categoryId) {
                CacheKeys::clearScientificInterests();

                Log::channel('audit')->info('Scientific interest category updated', [
                    'action' => 'dashboard.scientific_interest_categories.update',
                    'owner_id' => $ownerId,
                    'category_id' => $categoryId,
                ]);
            });

        });
    }

    public function deleteScientificInterestCategory(int $ownerId, int $categoryId): void
    {
        DB::transaction(function () use ($ownerId, $categoryId) {
            $category = $this->allocationDashboardRepository
                ->findScientificInterestCategoryOrFail($categoryId);

            $interestsCount = $this->allocationDashboardRepository
                ->countInterestsInsideCategory($categoryId);

            if ($interestsCount === 1) {
                throw DashboardUserException::cannotDeleteCategoryWithOnlyOneInterest();
            }

            $iconPaths = $this->allocationDashboardRepository
                ->getInterestIconPathsByCategoryId($categoryId);

            $this->allocationDashboardRepository->forceDeleteScientificInterestCategory($category);

            DB::afterCommit(function () use ($iconPaths , $ownerId , $categoryId , $interestsCount) {
                collect($iconPaths)
                    ->filter()
                    ->each(fn (string $path) => Storage::disk('public')->delete($path));

                CacheKeys::clearScientificInterests();

                Log::channel('audit')->info('Scientific interest category force deleted', [
                    'action' => 'dashboard.scientific_interest_categories.delete',
                    'owner_id' => $ownerId,
                    'category_id' => $categoryId,
                    'interests_count_before_delete' => $interestsCount,
                ]);
            });

        });
    }
}
