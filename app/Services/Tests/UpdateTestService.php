<?php

namespace App\Services\Tests;

use App\DTOs\Notifications\NotificationPayload;
use App\Enums\RevisionType;
use App\Enums\TestReviewStatus;
use App\Exceptions\Api\TestException;
use App\Helpers\BuildActor;
use App\Models\Test;
use App\Models\TestQuestion;
use App\Models\TestQuestionOption;
use App\Repositories\Tests\TestRepository;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateTestService
{
    private const string TYPE_PRIVATE = 'خاص';
    private const string TYPE_PUBLIC = 'عام';

    public function __construct(
        private readonly TestRepository $testRepository,
        private readonly ScientificChangeDetector $changeDetector,
        private readonly NotificationCenter $notificationCenter,
    ) {}

    public function updateTest(int $testId, int $userId, array $payload): array
    {
        $notificationPayload = null;

        $result = DB::transaction(function () use ($testId, $userId, $payload, &$notificationPayload) {
            $test = $this->testRepository->findForUpdate($testId);

            if (! $test) {
                throw TestException::NotFound();
            }

            if ((int) $test->creator_user_id !== $userId) {
                throw TestException::notOwner('لايمكنك تعديل اختبار لا تملكه');
            }

            $this->ensureEditableStatus($test);

            $oldStatus = (string) $test->review_status->value;

            if ($this->isPublicToPrivateConversion($test, $payload)) {
                throw TestException::cannotConvertPublicToPrivate();
            }

            if ($this->isPrivateToPublicConversion($test, $payload)) {
                return $this->handlePrivateToPublicConversion($test, $payload, $userId, $notificationPayload);
            }

            $analysis = $this->analyzePayloadChanges($test, $payload);

            if ($test->review_status === TestReviewStatus::NeedsRevision) {
                return $this->handleNeedsRevisionUpdate($test, $payload, $analysis, $userId, $notificationPayload);
            }

            $this->applyBasicUpdates($test, $payload);
            $this->syncInterestsIfPresent($test, $payload);
            $this->applyQuestionsIfPresent($test, $payload);
            $this->applyPreviewQuestionIdsIfPresent($test, $payload);
            $this->validatePreviewCountIfPublic($test);

            $requiresReview = false;
            $statusChanged = false;

            if (
                $test->test_type === self::TYPE_PUBLIC
                && $oldStatus === TestReviewStatus::Approved->value
                && $analysis['has_significant_scientific_change']
            ) {
                $requiresReview = true;
                $statusChanged = true;

                $roundId = $this->testRepository->createReviewRoundForOwnerUpdate($test);

                $test->review_status = TestReviewStatus::UnderReview;
                $test->last_content_updated_at = now();
                $test->save();

                $this->createScientificChangeLogs(
                    roundId: $roundId,
                    test: $test,
                    changes: $analysis['scientific_changes'],
                    userId: $userId
                );

                $this->testRepository->createStatusHistory(
                    testId: (int) $test->id,
                    testReviewRoundId: $roundId,
                    fromStatus: $oldStatus,
                    toStatus: TestReviewStatus::UnderReview->value,
                    changedByUserId: $userId,
                    note: 'تم تعديل محتوى علمي جوهري وإرسال الاختبار للمراجعة'
                );

                $notificationPayload = [
                    'type' => 'test_owner_update_requires_review',
                    'title' => 'اختبار مُعدّل بانتظار المراجعة',
                    'body' => "قام صاحب الاختبار بتعديل محتوى علمي جوهري في اختبار: {$test->title}",
                    'test_id' => (int) $test->id,
                    'test_title' => $test->title,
                    'owner_user_id' => (int) $test->creator_user_id,
                    'review_round_id' => (int) $roundId,
                    'old_status' => $oldStatus,
                    'new_status' => TestReviewStatus::UnderReview->value,
                    'reason' => 'owner_significant_scientific_update',
                    'scientific_changes_count' => count($analysis['scientific_changes'] ?? []),
                ];

            } else {
                $test->last_content_updated_at = now();
                $test->save();
            }

            Log::channel('audit')->info('test_updated', [
                'test_id' => $test->id,
                'user_id' => $userId,
                'old_status' => $oldStatus,
                'new_status' => $test->review_status,
                'requires_review' => $requiresReview,
            ]);

            return $this->result(
                test: $test,
                requiresReview: $requiresReview,
                statusChanged: $statusChanged,
                message: $requiresReview
                    ? 'تم حفظ التعديلات وإرسال الاختبار للمراجعة'
                    : 'تم تعديل الاختبار بنجاح'
            );
        });

        if ($notificationPayload !== null) {
            $this->sendTestSubmittedForReviewAfterOwnerUpdateNotification($notificationPayload);
        }

        return $result;
    }

    private function ensureEditableStatus(Test $test): void
    {
        $editableStatuses = [
            TestReviewStatus::New,
            TestReviewStatus::Approved,
            TestReviewStatus::NeedsRevision,
        ];

        if (! in_array($test->review_status, $editableStatuses, true)) {
            throw TestException::testCannotBeEdited();
        }
    }

    private function isPrivateToPublicConversion(Test $test, array $payload): bool
    {
        return $test->test_type->value === self::TYPE_PRIVATE
            && ($payload['test_type'] ?? null) === self::TYPE_PUBLIC;
    }

    private function isPublicToPrivateConversion(Test $test, array $payload): bool
    {
        return $test->test_type->value === self::TYPE_PUBLIC
            && ($payload['test_type'] ?? null) === self::TYPE_PRIVATE;
    }

    private function handlePrivateToPublicConversion(Test $test, array $payload, int $userId, ?array &$notificationPayload = null): array
    {
        $this->applyBasicUpdates($test, $payload);
        $this->syncInterestsIfPresent($test, $payload);
        $this->applyQuestionsIfPresent($test, $payload);
        $this->applyPreviewQuestionIdsIfPresent($test, $payload);

        $test->refresh();

        $this->validatePreviewCountForPublicConversion($test);

        $test->test_type = self::TYPE_PUBLIC;
        $test->review_status = TestReviewStatus::New;
        $test->last_content_updated_at = now();
        $test->save();

        $this->testRepository->createStatusHistory(
            testId: (int) $test->id,
            testReviewRoundId: null,
            fromStatus: null,
            toStatus: TestReviewStatus::New->value,
            changedByUserId: $userId,
            note: 'تم تحويل الاختبار من خاص إلى عام'
        );

        $notificationPayload = [
            'type' => 'test_private_to_public_requires_review',
            'title' => 'اختبار جديد بانتظار المراجعة',
            'body' => "قام مستخدم بتحويل اختبار خاص إلى عام: {$test->title}",
            'test_id' => (int) $test->id,
            'test_title' => $test->title,
            'owner_user_id' => (int) $test->creator_user_id,
            'review_round_id' => null,
            'old_status' => null,
            'new_status' => TestReviewStatus::New->value,
            'reason' => 'private_to_public_conversion',
            'scientific_changes_count' => 0,
        ];

        Log::channel('audit')->info('test_converted_private_to_public', [
            'test_id' => $test->id,
            'user_id' => $userId,
        ]);

        return $this->result(
            test: $test,
            requiresReview: true,
            statusChanged: true,
            message: 'تم تحويل الاختبار إلى عام وإرساله للمراجعة'
        );
    }

    private function handleNeedsRevisionUpdate(Test $test, array $payload, array $analysis, int $userId, ?array &$notificationPayload = null): array
    {
        $roundId = $this->testRepository->getOpenRevisionRoundId((int) $test->id);

        if (! $roundId) {
            throw TestException::testCannotBeEdited();
        }

        $revisionRequests = $this->testRepository->getUnresolvedRevisionRequests(
            testId: (int) $test->id,
            roundId: $roundId
        );

        $matchedRevisionRequestIds = $this->matchRevisionRequestsWithPayload(
            revisionRequests: $revisionRequests,
            analysis: $analysis
        );

        if (count($matchedRevisionRequestIds) !== $revisionRequests->count()) {
            throw TestException::incompleteRevisionRequests();
        }

        if ($this->hasForbiddenScientificChangesInRevision($analysis, $revisionRequests)) {
            throw TestException::forbiddenScientificChangeInRevision();
        }

        $oldStatus = (string) $test->review_status->value;

        $this->applyBasicUpdates($test, $payload);
        $this->syncInterestsIfPresent($test, $payload);
        $this->applyQuestionsIfPresent($test, $payload);
        $this->applyPreviewQuestionIdsIfPresent($test, $payload);
        $this->validatePreviewCountIfPublic($test);

        $test->review_status = TestReviewStatus::UnderReview;
        $test->last_content_updated_at = now();
        $test->save();

        $this->testRepository->resolveRevisionRequests($matchedRevisionRequestIds);

        $this->createScientificChangeLogs(
            roundId: $roundId,
            test: $test,
            changes: $analysis['scientific_changes'],
            userId: $userId,
            revisionRequests: $revisionRequests
        );

        $this->testRepository->createStatusHistory(
            testId: (int) $test->id,
            testReviewRoundId: $roundId,
            fromStatus: $oldStatus,
            toStatus: TestReviewStatus::UnderReview->value,
            changedByUserId: $userId,
            note: 'تم تنفيذ التعديلات المطلوبة وإعادة إرسال الاختبار للمراجعة'
        );

        $notificationPayload = [
            'type' => 'test_revision_completed_requires_review',
            'title' => 'تم تنفيذ تعديلات مطلوبة على اختبار',
            'body' => "قام صاحب الاختبار بتنفيذ التعديلات المطلوبة على اختبار: {$test->title}",
            'test_id' => (int) $test->id,
            'test_title' => $test->title,
            'owner_user_id' => (int) $test->creator_user_id,
            'review_round_id' => (int) $roundId,
            'old_status' => $oldStatus,
            'new_status' => TestReviewStatus::UnderReview->value,
            'reason' => 'owner_completed_requested_revisions',
            'scientific_changes_count' => count($analysis['scientific_changes'] ?? []),
            'matched_revision_requests_count' => count($matchedRevisionRequestIds),
        ];

        return $this->result(
            test: $test,
            requiresReview: true,
            statusChanged: true,
            message: 'تم تنفيذ جميع التعديلات المطلوبة وإرسال الاختبار للمراجعة'
        );
    }

    private function applyBasicUpdates(Test $test, array $payload): void
    {
        $fields = [
            'title',
            'description',
            'difficulty_level',
            'duration_seconds',
            'pass_mark_percentage',
            'language',
            'price',
            'target_level',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $payload)) {
                $test->{$field} = $payload[$field];
            }
        }

        if (array_key_exists('price', $payload)) {
            $test->price = $payload['price'] === null ? null : (float) $payload['price'];
        }

        $test->save();
    }

    private function syncInterestsIfPresent(Test $test, array $payload): void
    {
        if (! array_key_exists('interest_ids', $payload)) {
            return;
        }

        $this->testRepository->syncTestInterests(
            testId: (int) $test->id,
            interestIds: $payload['interest_ids']
        );
    }

    private function applyQuestionsIfPresent(Test $test, array $payload): void
    {
        if (! array_key_exists('questions', $payload)) {
            return;
        }

        $incomingQuestions = collect($payload['questions']);

        $existingQuestionIds = $test->testQuestions
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $incomingExistingIds = $incomingQuestions
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $questionIdsToDelete = array_diff($existingQuestionIds, $incomingExistingIds);

        if (! empty($questionIdsToDelete)) {
            TestQuestion::query()
                ->where('test_id', $test->id)
                ->whereIn('id', $questionIdsToDelete)
                ->delete();
        }

        $this->testRepository->moveExistingQuestionsToTemporaryPositions((int) $test->id);

        foreach ($incomingQuestions as $questionPayload) {
            $this->upsertQuestion($test, $questionPayload);
        }

        $this->refreshQuestionCounters($test);
        $test->load('testQuestions.testQuestionOptions');
    }

    private function upsertQuestion(Test $test, array $questionPayload): void
    {
        $questionId = $questionPayload['id'] ?? null;

        if ($questionId) {
            $question = TestQuestion::query()
                ->where('test_id', $test->id)
                ->whereKey($questionId)
                ->first();

            if (! $question) {
                throw TestException::invalidQuestionPayload();
            }
        } else {
            $question = new TestQuestion();
            $question->test_id = $test->id;
        }

        $question->position = $questionPayload['position'];
        $question->question_text = $questionPayload['question_text'];
        $question->hint_text = $questionPayload['hint_text'] ?? null;
        $question->is_preview = (bool) ($questionPayload['is_preview'] ?? false);
        $question->options_count = count($questionPayload['options']);
        $question->save();

        $this->syncQuestionOptions($question, $questionPayload['options']);
    }

    private function syncQuestionOptions(TestQuestion $question, array $optionsPayload): void
    {
        $correctOptionsCount = collect($optionsPayload)
            ->where('is_correct', true)
            ->count();

        if ($correctOptionsCount !== 1) {
            throw TestException::invalidQuestionPayload();
        }

        $existingOptionIds = $question->testQuestionOptions()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $incomingExistingIds = collect($optionsPayload)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $optionIdsToDelete = array_diff($existingOptionIds, $incomingExistingIds);

        if (! empty($optionIdsToDelete)) {
            TestQuestionOption::query()
                ->where('test_question_id', $question->id)
                ->whereIn('id', $optionIdsToDelete)
                ->delete();
        }

        $this->testRepository->moveExistingOptionsToTemporaryPositions((int) $question->id);

        foreach ($optionsPayload as $optionPayload) {
            $optionId = $optionPayload['id'] ?? null;

            if ($optionId) {
                $option = TestQuestionOption::query()
                    ->where('test_question_id', $question->id)
                    ->whereKey($optionId)
                    ->first();

                if (! $option) {
                    throw TestException::invalidQuestionPayload();
                }
            } else {
                $option = new TestQuestionOption();
                $option->test_question_id = $question->id;
            }

            $option->position = $optionPayload['position'];
            $option->option_text = $optionPayload['option_text'];
            $option->is_correct = (bool) $optionPayload['is_correct'];
            $option->save();
        }
    }

    private function applyPreviewQuestionIdsIfPresent(Test $test, array $payload): void
    {
        if (! array_key_exists('preview_question_ids', $payload)) {
            return;
        }

        $previewQuestionIds = array_map('intval', $payload['preview_question_ids']);

        $validCount = TestQuestion::query()
            ->where('test_id', $test->id)
            ->whereIn('id', $previewQuestionIds)
            ->count();

        if ($validCount !== count($previewQuestionIds)) {
            throw TestException::invalidQuestionPayload();
        }

        TestQuestion::query()
            ->where('test_id', $test->id)
            ->update(['is_preview' => false]);

        if (! empty($previewQuestionIds)) {
            TestQuestion::query()
                ->where('test_id', $test->id)
                ->whereIn('id', $previewQuestionIds)
                ->update(['is_preview' => true]);
        }

        $this->refreshQuestionCounters($test);
        $test->load('testQuestions.testQuestionOptions');
    }

    private function refreshQuestionCounters(Test $test): void
    {
        $test->question_count = TestQuestion::query()
            ->where('test_id', $test->id)
            ->count();

        $test->preview_question_count = TestQuestion::query()
            ->where('test_id', $test->id)
            ->where('is_preview', true)
            ->count();

        $test->save();
    }

    private function validatePreviewCountForPublicConversion(Test $test): void
    {
        $requiredPreviewCount = $this->requiredPreviewQuestionCount($test);

        if ((int) $test->preview_question_count !== $requiredPreviewCount) {
            throw TestException::invalidPreviewQuestionsCount($requiredPreviewCount);
        }
    }

    private function validatePreviewCountIfPublic(Test $test): void
    {
        if ($test->test_type !== self::TYPE_PUBLIC) {
            return;
        }

        $requiredPreviewCount = $this->requiredPreviewQuestionCount($test);

        if ((int) $test->preview_question_count !== $requiredPreviewCount) {
            throw TestException::invalidPreviewQuestionsCount($requiredPreviewCount);
        }
    }

    private function requiredPreviewQuestionCount(Test $test): int
    {
        return (int) ceil(((int) $test->question_count) * 0.10);
    }

    private function analyzePayloadChanges(Test $test, array $payload): array
    {
        $changes = [];

        if (
            array_key_exists('title', $payload)
            && $this->changeDetector->isTitleSignificant($test->title, $payload['title'])
        ) {
            $changes[] = [
                'type' => RevisionType::TestTitle->value,
                'target_question_id' => null,
                'target_option_id' => null,
                'before' => $test->title,
                'after' => $payload['title'],
            ];
        }

        if (
            array_key_exists('description', $payload)
            && $this->changeDetector->isDescriptionSignificant($test->description, $payload['description'])
        ) {
            $changes[] = [
                'type' => RevisionType::TestDescription->value,
                'target_question_id' => null,
                'target_option_id' => null,
                'before' => $test->description,
                'after' => $payload['description'],
            ];
        }

        if (array_key_exists('questions', $payload)) {
            $changes = array_merge(
                $changes,
                $this->analyzeQuestionChanges($test, $payload['questions'])
            );
        }

        return [
            'has_significant_scientific_change' => ! empty($changes),
            'scientific_changes' => $changes,
        ];
    }

    private function analyzeQuestionChanges(Test $test, array $questionsPayload): array
    {
        $changes = [];

        $existingQuestions = $test->testQuestions->keyBy('id');

        $incomingExistingIds = collect($questionsPayload)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $deletedQuestionIds = $existingQuestions
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->diff($incomingExistingIds)
            ->all();

        foreach ($deletedQuestionIds as $deletedQuestionId) {
            $oldQuestion = $existingQuestions->get($deletedQuestionId);

            $changes[] = [
                'type' => RevisionType::QuestionText->value,
                'target_question_id' => (int) $deletedQuestionId,
                'target_option_id' => null,
                'before' => $oldQuestion?->question_text,
                'after' => null,
            ];
        }

        foreach ($questionsPayload as $questionPayload) {
            $questionId = $questionPayload['id'] ?? null;

            if (! $questionId) {
                $changes[] = [
                    'type' => RevisionType::QuestionText->value,
                    'target_question_id' => null,
                    'target_option_id' => null,
                    'before' => null,
                    'after' => $questionPayload['question_text'],
                ];

                continue;
            }

            $oldQuestion = $existingQuestions->get((int) $questionId);

            if (! $oldQuestion) {
                throw TestException::invalidQuestionPayload();
            }

            if (
                $this->changeDetector->isQuestionTextSignificant(
                    $oldQuestion->question_text,
                    $questionPayload['question_text']
                )
            ) {
                $changes[] = [
                    'type' => RevisionType::QuestionText->value,
                    'target_question_id' => (int) $oldQuestion->id,
                    'target_option_id' => null,
                    'before' => $oldQuestion->question_text,
                    'after' => $questionPayload['question_text'],
                ];
            }

            if (
                array_key_exists('hint_text', $questionPayload)
                && $this->changeDetector->isHintSignificant(
                    $oldQuestion->hint_text,
                    $questionPayload['hint_text']
                )
            ) {
                $changes[] = [
                    'type' => RevisionType::Hint->value,
                    'target_question_id' => (int) $oldQuestion->id,
                    'target_option_id' => null,
                    'before' => $oldQuestion->hint_text,
                    'after' => $questionPayload['hint_text'],
                ];
            }

            $changes = array_merge(
                $changes,
                $this->analyzeOptionChanges($oldQuestion, $questionPayload['options'])
            );
        }

        return $changes;
    }

    private function analyzeOptionChanges(TestQuestion $oldQuestion, array $optionsPayload): array
    {
        $changes = [];

        $oldOptions = $oldQuestion->testQuestionOptions->keyBy('id');

        $incomingExistingIds = collect($optionsPayload)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $deletedOptionIds = $oldOptions
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->diff($incomingExistingIds)
            ->all();

        foreach ($deletedOptionIds as $deletedOptionId) {
            $oldOption = $oldOptions->get($deletedOptionId);

            $changes[] = [
                'type' => RevisionType::AnswerText->value,
                'target_question_id' => (int) $oldQuestion->id,
                'target_option_id' => (int) $deletedOptionId,
                'before' => $oldOption?->option_text,
                'after' => null,
            ];
        }

        $oldCorrectOptionId = optional($oldOptions->firstWhere('is_correct', true))->id;

        $newCorrectOption = collect($optionsPayload)->firstWhere('is_correct', true);
        $newCorrectOptionId = $newCorrectOption['id'] ?? null;

        if (
            $oldCorrectOptionId
            && $newCorrectOptionId
            && (int) $oldCorrectOptionId !== (int) $newCorrectOptionId
        ) {
            $changes[] = [
                'type' => RevisionType::QuestionAnswer->value,
                'target_question_id' => (int) $oldQuestion->id,
                'target_option_id' => (int) $newCorrectOptionId,
                'before' => $oldCorrectOptionId,
                'after' => $newCorrectOptionId,
            ];
        }

        foreach ($optionsPayload as $optionPayload) {
            $optionId = $optionPayload['id'] ?? null;

            if (! $optionId) {
                $changes[] = [
                    'type' => RevisionType::AnswerText->value,
                    'target_question_id' => (int) $oldQuestion->id,
                    'target_option_id' => null,
                    'before' => null,
                    'after' => $optionPayload['option_text'],
                ];

                continue;
            }

            $oldOption = $oldOptions->get((int) $optionId);

            if (! $oldOption) {
                throw TestException::invalidQuestionPayload();
            }

            if (
                $this->changeDetector->isAnswerTextSignificant(
                    $oldOption->option_text,
                    $optionPayload['option_text']
                )
            ) {
                $changes[] = [
                    'type' => RevisionType::AnswerText->value,
                    'target_question_id' => (int) $oldQuestion->id,
                    'target_option_id' => (int) $oldOption->id,
                    'before' => $oldOption->option_text,
                    'after' => $optionPayload['option_text'],
                ];
            }
        }

        return $changes;
    }

    private function matchRevisionRequestsWithPayload($revisionRequests, array $analysis): array
    {
        $matchedIds = [];

        foreach ($revisionRequests as $request) {
            foreach ($analysis['scientific_changes'] as $change) {
                if (
                    $change['type'] === $request->revision_type
                    && (int) ($change['target_question_id'] ?? 0) === (int) ($request->target_question_id ?? 0)
                    && (int) ($change['target_option_id'] ?? 0) === (int) ($request->target_option_id ?? 0)
                ) {
                    $matchedIds[] = (int) $request->id;
                    break;
                }
            }
        }

        return array_values(array_unique($matchedIds));
    }

    private function hasForbiddenScientificChangesInRevision(array $analysis, $revisionRequests): bool
    {
        foreach ($analysis['scientific_changes'] as $change) {
            $allowed = $revisionRequests->contains(function ($request) use ($change) {
                return $change['type'] === $request->revision_type
                    && (int) ($change['target_question_id'] ?? 0) === (int) ($request->target_question_id ?? 0)
                    && (int) ($change['target_option_id'] ?? 0) === (int) ($request->target_option_id ?? 0);
            });

            if (! $allowed) {
                return true;
            }
        }

        return false;
    }

    private function createScientificChangeLogs(
        int $roundId,
        Test $test,
        array $changes,
        int $userId,
        $revisionRequests = null
    ): void {
        foreach ($changes as $change) {
            $revisionRequestId = null;

            if ($revisionRequests) {
                $matchedRequest = $revisionRequests->first(function ($request) use ($change) {
                    return $change['type'] === $request->revision_type
                        && (int) ($change['target_question_id'] ?? 0) === (int) ($request->target_question_id ?? 0)
                        && (int) ($change['target_option_id'] ?? 0) === (int) ($request->target_option_id ?? 0);
                });

                $revisionRequestId = $matchedRequest?->id;
            }

            $this->testRepository->createRevisionChangeLog(
                roundId: $roundId,
                testId: (int) $test->id,
                revisionRequestId: $revisionRequestId,
                revisionType: $change['type'],
                targetQuestionId: $change['target_question_id'],
                targetOptionId: $change['target_option_id'],
                beforeValue: $change['before'],
                afterValue: $change['after'],
                changedByUserId: $userId
            );
        }
    }

    private function result(Test $test, bool $requiresReview, bool $statusChanged, string $message): array
    {
        return [
            'test_id' => (int) $test->id,
            'review_status' => (string) $test->review_status->value,
            'requires_review' => $requiresReview,
            'status_changed' => $statusChanged,
            'message' => $message,
        ];
    }

    private function sendTestSubmittedForReviewAfterOwnerUpdateNotification(array $data): void
    {
        $reviewerIds = $this->testRepository->getDashboardReviewerUserIds();

        if (empty($reviewerIds)) {
            return;
        }

        $testTitle = $data['test_title'] ?? 'اختبار';

        $payload = NotificationPayload::make(
            title: $data['title'],
            body: $data['body'],
            metadata: [
                'type' => $data['type'],
                'category' => 'test_status',

                'presentation' => [
                    'mode' => 'user',
                    'floor_color' => null,
                    'icon' => null,
                ],

                'actor' => BuildActor::buildUserActor((int) (int) $data['owner_user_id']),

                'target' => [
                    'type' => 'test',
                    'id' => (int) $data['test_id'],
                    'title' => $testTitle,
                ],

                'navigation' => [
                    'screen' => 'test_details',
                    'action' => 'open',
                ],

                'params' => [
                    'test_id' => (int) $data['test_id'],
                ],
            ],
        );

        $this->notificationCenter->sendToWeb(
            userIds: $reviewerIds,
            payload: $payload,
        );
    }
}
