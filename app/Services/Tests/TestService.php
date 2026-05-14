<?php

namespace App\Services\Tests;

use App\Enums\TestType;
use App\Exceptions\Api\TestException;
use App\Helpers\CounterProcessor;
use App\Repositories\Tests\TestRepository;
use Illuminate\Database\Eloquent\Collection;
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
        $test = $this->testRepository->findVisiblePublicTest($testId);

        if (!$test) {
            throw TestException::notFound();
        }

        if ((int)$test->creator_user_id === $viewerId) {
            throw TestException::previewIsForOtherUsersOnly();
        }

        return $this->testRepository->getPreviewQuestionsByTestId($testId);
    }

    public function listRatingForTest(int $testId, int $viewerId, ?int $rating, string $context, bool $excludeViewerReview): array
    {
        $test = $this->testRepository->findVisiblePublicTest($testId);

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

    public function getShareLink(int $testId): array
    {
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
            'share_url' => url('/share/tests/' . $slug)  ,
        ];
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
}
