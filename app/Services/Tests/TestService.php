<?php

namespace App\Services\Tests;

use App\Enums\TestDeletionStrategy;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Events\TestDeleted;
use App\Exceptions\Api\TestException;
use App\Helpers\CounterProcessor;
use App\Models\Test;
use App\Repositories\Tests\TestRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TestService
{
    public function __construct(
        private readonly TestRepository           $testRepository,
        private readonly TestViewerContextService $viewerContextService
    )
    {
    }

    public function getDetailsForUser(int $testId, int $viewerId): array
    {
        $test = $this->testRepository->findDetailsById($testId, $viewerId);

        if (!$test) {
            throw TestException::notFound();
        }

        $viewerContext = $this->viewerContextService->build($test, $viewerId);

        return [
            'test' => $test,
            'viewer_context' => $viewerContext,
        ];
    }

    public function getMyPrivateTestDetails(int $testId, int $ownerId): array
    {
        $test = $this->testRepository->findOwnedDetailsById(
            testId: $testId,
            ownerId: $ownerId,
            testType: TestType::Private->value,
            withPreviewQuestions: false
        );

        if (!$test) {
            throw TestException::notFound();
        }

        $viewerContext = $this->viewerContextService->buildForOwner($test);

        return [
            'test' => $test,
            'viewer_context' => $viewerContext,
        ];
    }

    public function getMyPublicTestDetails(int $testId, int $ownerId): array
    {
        $test = $this->testRepository->findOwnedDetailsById(
            testId: $testId,
            ownerId: $ownerId,
            testType: TestType::Public->value,
            withPreviewQuestions: true
        );

        if (!$test) {
            throw TestException::notFound();
        }

        $viewerContext = $this->viewerContextService->buildForOwner($test);

        return [
            'test' => $test,
            'viewer_context' => $viewerContext,
        ];
    }

    public function getPreviewQuestionsForViewer(int $testId, int $viewerId): Collection|array
    {
        $test = $this->testRepository->findVisiblePublicTest($testId , $viewerId);

        if (!$test) {
            throw TestException::notFound();
        }

        if ((int)$test->creator_user_id === $viewerId) {
            throw TestException::previewIsForOtherUsersOnly();
        }

        return $this->testRepository->getPreviewQuestionsByTestId($testId);
    }

    public function listRatingForTest(int $testId, int $viewerId, ?int $rating, string $context, bool $excludeViewerReview , bool $mustBeApproved): array
    {
        $test = $this->testRepository->findVisiblePublicTest($testId , $viewerId , $mustBeApproved);

        if (!$test) {
            throw TestException::notFound();
        }

        $distribution = $this->testRepository->getRatingDistribution($testId);

        $totalReviews = (int)$test->reviews_count;
        $commentsCount = $this->testRepository->countTextComments($testId);

        $reviews = $this->testRepository->paginateReviews(
            testId: $testId,
            viewerId: $viewerId,
            rating: $rating,
            perPage: 20,
            excludeViewerReview: $excludeViewerReview
        );

        $response = [
            'summary' => [
                'average_rating' => round((float)$test->average_rating, 1) ?? 0.0,
                'total_reviews_count' => CounterProcessor::compact($totalReviews),
                'comments_count' => CounterProcessor::compact($commentsCount),
                'rating_distribution' => [
                    '5' => $this->buildRatingDistributionItem($distribution[5], $totalReviews),
                    '4' => $this->buildRatingDistributionItem($distribution[4], $totalReviews),
                    '3' => $this->buildRatingDistributionItem($distribution[3], $totalReviews),
                    '2' => $this->buildRatingDistributionItem($distribution[2], $totalReviews),
                    '1' => $this->buildRatingDistributionItem($distribution[1], $totalReviews),
                ],
            ],
            'reviews' => $reviews,
        ];

        if ($context === 'other') {
            $myReview = $this->testRepository->findMyReviewForTest(
                testId: $testId,
                viewerId: $viewerId
            );

            $response['my_review'] = $myReview ?? [];
        }

        return $response;
    }

    private function buildRatingDistributionItem(int $count, int $totalReviews): array
    {
        $percentage = $totalReviews > 0
            ? round(($count / $totalReviews) * 100, 1)
            : 0;

        return [
            'count' => $count,
            'percentage' => $percentage,
        ];
    }

    ////////////////////////////////////////////////////////////////////////////

    public function getShareLink(int $testId ): array
    {
        return DB::transaction(function () use ($testId) {
            $test = $this->testRepository->findShareableTest($testId);

            if (!$test) {
                throw TestException::notAvailableToShare();
            }

            $slug = $test->share_slug;

            if (!$slug) {
                $slug = $this->generateUniqueSlug();

                $this->testRepository->updateShareSlug(
                    testId: $testId,
                    slug: $slug
                );
            }

            return [
                'share_slug' => $slug,
                'share_url' => url('/share/tests/' . $slug),
            ];
        });
    }

    private function generateUniqueSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(15));
        } while ($this->testRepository->shareSlugExists($slug));

        return $slug;
    }

    public function getTestDetailsBySlug(string $slug , int $userId): array
    {
        $test = $this->testRepository->findByShareSlug($slug);

        if (! $test) {
            throw TestException::notAvailable();
        }

        $isOwner = (int) $test->creator_user_id === (int) $userId;

        return [
            'test_id' => $test->id,
            'is_owner' => $isOwner,
        ];
    }

    ////////////////////////////////////////////////////////////////////////////

    public function getContent(int $testId, int $viewerId): array
    {
        $test = $this->testRepository->findTestWithContent($testId);

        if (! $test) {
            throw TestException::notFound();
        }

        $viewer = $this->testRepository->getViewerInfo($viewerId);

        if (! $viewer) {
            throw TestException::viewerNotFound();
        }

        $isOwner = (int) $test->creator_user_id === $viewerId;

        if (! $isOwner) {
            $this->ensureViewerCanAccessTestContent($test, $viewerId);
        }

        return [
            'test' => $test,
            'viewer' => $viewer,
        ];
    }

    private function ensureViewerCanAccessTestContent($test, int $viewerId): void
    {

        if ($test->test_type->value !== TestType::Public->value) {
            throw TestException::contentNotAvailable();
        }

        if ($test->review_status->value !== TestReviewStatus::Approved->value) {
            throw TestException::contentNotAvailable();
        }

        $isFree = is_null($test->price) || (float) $test->price <= 0;

        if ($isFree) {
            return;
        }

        $hasPurchased = $this->testRepository->hasUserPurchasedTest(
            testId: (int) $test->id,
            userId: $viewerId
        );

        if (! $hasPurchased) {
            throw TestException::purchaseRequiredForContent();
        }
    }

    ////////////////////////////////////////////////////////////////////////////

    public function getTestStatusHistoryForOwner(int $testId, int $ownerId): \Illuminate\Support\Collection
    {
        $test = $this->testRepository->findOwnedPublicTest(
            testId: $testId,
            ownerId: $ownerId
        );

        if (! $test) {
            throw TestException::statusHistoryNotAvailable();
        }

        return $this->testRepository->getStatusHistories($testId);
    }

    ////////////////////////////////////////////////////////////////////////////

    public function deleteTest(int $testId, int $userId): void
    {
        DB::transaction(function () use ($testId, $userId) {
            $test = $this->testRepository->findForDelete($testId);

            if (! $test) {
                throw TestException::notFound();
            }

            if ((int) $test->creator_user_id !== $userId) {
                throw TestException::notOwner('لا يمكنك حذف اختبار لا تملكه');
            }

            if ($test->trashed() || $test->review_status === TestReviewStatus::Deleted->value) {
                throw TestException::alreadyDeleted();
            }

            $fromStatus = (string) $test->review_status->value;
            $strategy = $this->determineDeletionStrategy($test);

            $wasPublished = $fromStatus === TestReviewStatus::Approved->value && $test->published_at !== null;

            $eventPayload = [
                'testId' => (int) $test->id,
                'creatorUserId' => (int) $test->creator_user_id,
                'deletedByUserId' => $userId,
                'publishedAt' => $test->published_at?->toDateTimeString(),
                'wasPublished' => $wasPublished,
                'likesCount' => (int) $test->likes_count,
                'bookmarksCount' => (int) $test->bookmarks_count,
                'reviewsCount' => (int) $test->reviews_count,
                'downloadsCount' => (int) $test->downloads_count,
                'averageRating' => (float) $test->average_rating,
                'deletionStrategy' => $strategy,
            ];

            $test->review_status = TestReviewStatus::Deleted->value;
            $test->save();

            $this->testRepository->createStatusHistory(
                testId: (int) $test->id,
                testReviewRoundId: null,
                fromStatus: $fromStatus,
                toStatus: TestReviewStatus::Deleted->value,
                changedByUserId: $userId,
                note: 'تم حذف هذا الاختبار من قبل صاحب الاختبار'
            );

            if ($strategy === TestDeletionStrategy::SoftDelete) {
                $test->delete();
            } else {
                $test->forceDelete();
            }

            TestDeleted::dispatch(...array_values($eventPayload));

            Log::channel('audit')->info('test_deleted', [
                'test_id' => $eventPayload['testId'],
                'creator_user_id' => $eventPayload['creatorUserId'],
                'deleted_by_user_id' => $eventPayload['deletedByUserId'],
                'deletion_strategy' => $strategy->value,
            ]);
        });
    }

    private function determineDeletionStrategy(Test $test): TestDeletionStrategy
    {
        if ($test->test_type === TestType::Private) {
            return TestDeletionStrategy::ForceDelete;
        }

//        $price = (float) ($test->price ?? 0);
//
//        if ($price <= 0) {
//            return TestDeletionStrategy::ForceDelete;
//        }

        return $this->testRepository->hasPaidPurchases((int) $test->id)
            ? TestDeletionStrategy::SoftDelete
            : TestDeletionStrategy::ForceDelete;
    }
}
