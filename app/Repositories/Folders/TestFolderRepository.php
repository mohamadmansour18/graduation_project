<?php

namespace App\Repositories\Folders;

use App\Enums\TestReviewStatus;
use App\Models\Test;
use App\Models\TestFolder;
use Illuminate\Database\Eloquent\Collection;
use LaravelIdea\Helper\App\Models\_IH_Test_C;

class TestFolderRepository
{
    public function getTestsForFolderValidation(array $testIds, int $userId): array|Collection|_IH_Test_C
    {
        return Test::query()
            ->select([
                'id',
                'creator_user_id',
                'test_type',
                'review_status',
            ])
            ->whereIn('id', $testIds)
            ->where('creator_user_id', $userId)
            ->get();
    }

    public function createFolder(array $data): TestFolder
    {
        return TestFolder::query()->create($data);
    }

    public function createFolderItems(TestFolder $folder, array $testIds): void
    {
        $items = collect($testIds)
            ->values()
            ->map(fn ($testId, $index) => [
                'test_folder_id' => $folder->id,
                'test_id' => $testId,
                'position' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->toArray();

        $folder->testFolderItems()->insert($items);
    }

    public function findOwnedFolder(int $folderId, int $userId): ?TestFolder
    {
        return TestFolder::query()
            ->where('id', $folderId)
            ->where('creator_user_id', $userId)
            ->first();
    }

    public function deleteFolder(TestFolder $folder): void
    {
        $folder->delete();
    }

    public function getApprovedFolderTests(int $folderId): Collection
    {
        return Test::query()
            ->select([
                'test.id',
                'test.title',
                'test.description',
                'test.test_type',
                'test.difficulty_level',
                'test.average_rating',
                'test.price',
                'test.created_at',
                'test.question_count',
                'test_folder_item.position',
            ])
            ->join('test_folder_item', 'test.id', '=', 'test_folder_item.test_id')
            ->where('test_folder_item.test_folder_id', $folderId)
            ->where('test.review_status', TestReviewStatus::Approved->value)
            ->with([
                'testIntersetSelections:id,test_id,interest_id',
                'testIntersetSelections.interest:id,name',
            ])
            ->orderBy('test_folder_item.position')
            ->orderBy('test.id')
            ->get();
    }

    public function getTestsForValidation(array $testIds, int $userId): Collection
    {
        return Test::query()
            ->select(['id', 'creator_user_id', 'test_type', 'review_status'])
            ->whereIn('id', $testIds)
            ->where('creator_user_id', $userId)
            ->get();
    }

    public function updateFolder(TestFolder $folder, array $data): void
    {
        $folder->update($data);
    }

    public function replaceFolderItems(TestFolder $folder, array $testIds): void
    {
        $folder->testFolderItems()->delete();

        $items = collect($testIds)
            ->values()
            ->map(fn ($testId, $index) => [
                'test_folder_id' => $folder->id,
                'test_id' => $testId,
                'position' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->toArray();

        $folder->testFolderItems()->insert($items);
    }
}
