<?php

namespace App\Services\Folders;

use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Exceptions\Api\FoldersException;
use App\Repositories\Folders\TestFolderRepository;
use Illuminate\Support\Facades\DB;

class TestFolderService
{
    public function __construct(
        private readonly TestFolderRepository $repository
    )
    {}

    public function createFolder(int $userId, array $data): void
    {
        $testIds = $data['test_ids'];

        $tests = $this->repository->getTestsForFolderValidation($testIds, $userId);

        if ($tests->count() !== count($testIds)) {
            throw FoldersException::folderTestsMustBeOwnedByUser();
        }

        $testTypes = $tests->pluck('test_type')->unique()->values();

        if ($testTypes->count() !== 1) {
            throw FoldersException::folderTestsMustHaveSameType();
        }

        $containedTestType = $testTypes->first();

        if ($containedTestType === TestType::Public) {
            $hasNotApprovedPublicTest = $tests->contains(fn ($test) => $test->review_status !== TestReviewStatus::Approved);

            if ($hasNotApprovedPublicTest) {
                throw FoldersException::publicFolderTestsMustBeApproved();
            }
        }

        DB::transaction(function () use ($userId, $data, $testIds, $containedTestType) {
            $folder = $this->repository->createFolder([
                'creator_user_id' => $userId,
                'name' => $data['name'],
                'color_code' => $data['color_code'],
                'visibility_type' => $data['visibility_type'],
                'contained_test_type' => $containedTestType,
                'tests_count' => count($testIds),
                'published_at' => now(),
            ]);

            $this->repository->createFolderItems($folder, $testIds);
        });
    }

    public function deleteFolder(int $userId, int $folderId): void
    {
        $folder = $this->repository->findOwnedFolder($folderId, $userId);

        if (! $folder) {
            throw FoldersException::folderNotFound();
        }

        $this->repository->deleteFolder($folder);
    }

    public function getFolderTests(int $userId, int $folderId): \Illuminate\Database\Eloquent\Collection
    {
        $folder = $this->repository->findOwnedFolder($folderId, $userId);

        if (! $folder) {
            throw FoldersException::folderNotFound();
        }

        return $this->repository->getApprovedFolderTests($folderId);
    }

    public function updateFolder(int $userId, int $folderId, array $data): void
    {
        DB::transaction(function () use ($userId, $folderId, $data) {
            $folder = $this->repository->findOwnedFolder($folderId, $userId);

            if (! $folder) {
                throw FoldersException::folderNotFound();
            }

            $updateData = [];

            if (array_key_exists('name', $data)) {
                $updateData['name'] = $data['name'];
            }

            if (array_key_exists('color_code', $data)) {
                $updateData['color_code'] = $data['color_code'];
            }

            if (array_key_exists('visibility_type', $data)) {
                if ($folder->visibility_type === 'عام') {
                    throw FoldersException::cannotChangePublicFolderToPrivate();
                }

                $updateData['visibility_type'] = 'عام';
            }

            if (array_key_exists('test_ids', $data)) {
                $tests = $this->repository->getTestsForValidation($data['test_ids'], $userId);

                if ($tests->count() !== count($data['test_ids'])) {
                    throw FoldersException::folderTestsMustBeOwnedByUser();
                }

                $testTypes = $tests->pluck('test_type')->unique()->values();

                if ($testTypes->count() !== 1) {
                    throw FoldersException::folderTestsMustHaveSameType();
                }

                $containedTestType = $testTypes->first();

                if ($containedTestType === TestType::Public->value) {
                    $hasNotApproved = $tests->contains(fn ($test) => $test->review_status !== TestReviewStatus::Approved->value);

                    if ($hasNotApproved) {
                        throw FoldersException::publicFolderTestsMustBeApproved();
                    }
                }

                $newVisibility = $updateData['visibility_type'] ?? $folder->visibility_type;

                if ($newVisibility === 'عام' && $containedTestType->value !== 'عام') {
                    throw FoldersException::publicFolderCannotContainPrivateTests();
                }

                $updateData['contained_test_type'] = $containedTestType;
                $updateData['tests_count'] = count($data['test_ids']);

                $this->repository->replaceFolderItems($folder, $data['test_ids']);
            } else {
                $newVisibility = $updateData['visibility_type'] ?? $folder->visibility_type;

                if ($newVisibility === 'عام' && $folder->contained_test_type !== 'عام') {
                    throw FoldersException::publicFolderCannotContainPrivateTests();
                }
            }

            if ($updateData) {
                $this->repository->updateFolder($folder, $updateData);
            }
        });
    }
}
