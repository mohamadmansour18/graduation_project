<?php

namespace App\Services\Tests;

use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Exceptions\Api\TestException;
use App\Models\User;
use App\Repositories\Tests\TestCreationRepository;
use Illuminate\Support\Facades\DB;
use Throwable;

class TestCreationService
{

    private const int MAX_PENDING_PUBLIC_TESTS_FOR_VERIFIED_USER = 3;
    private const int MAX_PENDING_PUBLIC_TESTS_FOR_UNVERIFIED_USER = 1;
    private const int MAX_PRIVATE_TESTS_PER_DAY = 5;

    public function __construct(
        private readonly TestCreationRepository $repository
    ) {}

    /**
     * @throws TestException
     * @throws Throwable
     */
    public function create(User $user, array $data): void
    {
        $isPublic = $data['test_type'] === TestType::Public->value;
        $isPrivate = $data['test_type'] === TestType::Private->value;

        $questions = $data['questions'];
        $questionsCount = count($questions);
        $previewQuestionsCount = $this->countPreviewQuestions($questions);

        $this->assertUserCanCreateTest(
            user: $user,
            isPublic: $isPublic,
            isPrivate: $isPrivate
        );

        $this->assertPriceRules(
            isPrivate: $isPrivate,
            price: $data['price'] ?? null
        );

        $this->assertPreviewRules(
            isPublic: $isPublic,
            isPrivate: $isPrivate,
            questionsCount: $questionsCount,
            previewQuestionsCount: $previewQuestionsCount
        );

        $this->assertQuestionsCorrectOptions($questions);

        DB::transaction(function () use (
            $user,
            $data,
            $questions,
            $questionsCount,
            $previewQuestionsCount,
            $isPublic
        ): void {
            $test = $this->repository->createTest(
                user: $user,
                data: [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'test_type' => $data['test_type'],
                    'difficulty_level' => $data['difficulty_level'],
                    'duration_seconds' => $data['duration_seconds'] ?? null,
                    'pass_mark_percentage' => $data['pass_mark_percentage'] ?? null,
                    'language' => $data['language'],
                    'price' => $isPublic ? $this->normalizePrice($data['price'] ?? null) : null,
                    'target_level' => $data['target_level'],
                    'review_status' => $isPublic
                        ? TestReviewStatus::New->value
                        : TestReviewStatus::Approved->value,

                    'current_approval_version' => 0,
                    'published_at' => null,
                    'last_content_updated_at' => null,

                    'question_count' => $questionsCount,
                    'preview_question_count' => $isPublic ? $previewQuestionsCount : 0,

                    'likes_count' => 0,
                    'bookmarks_count' => 0,
                    'downloads_count' => 0,
                    'reviews_count' => 0,
                    'participants_count' => 0,
                    'average_rating' => 0,
                ]
            );

            $this->repository->createInterestSelections(
                test: $test,
                interestIds: $data['interest_ids']
            );

            foreach (array_values($questions) as $index => $question) {
                $this->repository->createQuestionWithOptions(
                    test: $test,
                    questionData: $question,
                    position: $index + 1
                );
            }

            if ($isPublic) {
                $this->repository->createInitialReviewRound($test);

                $this->repository->createInitialStatusHistory(
                    test: $test,
                );
            }
        });
    }

    private function countPreviewQuestions(array $questions): int
    {
        return collect($questions)
            ->filter(fn (array $question): bool => (bool) ($question['is_preview'] ?? false))
            ->count();
    }

    private function assertUserCanCreateTest(User $user, bool $isPublic, bool $isPrivate): void
    {
        if ($isPublic) {
            $pendingPublicTestsCount = $this->repository->countPendingPublicTestsForUser($user->id);

            if ((bool) $user->is_academically_verified) {
                if ($pendingPublicTestsCount >= self::MAX_PENDING_PUBLIC_TESTS_FOR_VERIFIED_USER) {
                    throw TestException::tooManyPendingPublicTestsForVerifiedUser();
                }

                return;
            }

            if ($pendingPublicTestsCount >= self::MAX_PENDING_PUBLIC_TESTS_FOR_UNVERIFIED_USER) {
                throw TestException::tooManyPendingPublicTestsForUnverifiedUser();
            }

            return;
        }

        if ($isPrivate) {
            $privateTestsCreatedToday = $this->repository->countPrivateTestsCreatedToday($user->id);

            if ($privateTestsCreatedToday >= self::MAX_PRIVATE_TESTS_PER_DAY) {
                throw TestException::tooManyPrivateTestsToday();
            }
        }
    }

    private function assertPriceRules(bool $isPrivate, mixed $price): void
    {
        if ($isPrivate && $price !== null) {
            throw TestException::privateTestCannotHavePrice();
        }
    }

    private function assertPreviewRules(bool $isPublic, bool $isPrivate, int $questionsCount, int $previewQuestionsCount): void
    {
        if ($isPrivate && $previewQuestionsCount > 0) {
            throw TestException::privateTestCannotHavePreviewQuestions();
        }

        if (! $isPublic) {
            return;
        }

        $requiredPreviewQuestionsCount = (int) ceil($questionsCount * 0.10);

        if ($previewQuestionsCount !== $requiredPreviewQuestionsCount) {
            throw TestException::publicTestMustHaveExactPreviewQuestionsCount(
                requiredCount: $requiredPreviewQuestionsCount
            );
        }
    }

    private function assertQuestionsCorrectOptions(array $questions): void
    {
        foreach (array_values($questions) as $index => $question) {
            $correctOptionsCount = collect($question['options'])
                ->filter(fn (array $option): bool => (bool) $option['is_correct'])
                ->count();

            if ($correctOptionsCount !== 1) {
                throw TestException::questionMustHaveExactlyOneCorrectOption(
                    questionNumber: $index + 1
                );
            }
        }
    }

    private function normalizePrice(mixed $price): ?float
    {
        if ($price === null || (float) $price <= 0) {
            return null;
        }

        return round((float) $price, 2);
    }
}
