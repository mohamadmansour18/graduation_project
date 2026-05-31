<?php

namespace App\Repositories\Tests;

use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Models\Test;
use Illuminate\Contracts\Pagination\CursorPaginator;

class TestFilterRepository
{
    public function filter(array $filters, int $userId): CursorPaginator
    {
        $query = Test::query()
            ->select([
                'id',
                'title',
                'description',
                'difficulty_level',
                'price',
                'average_rating',
                'published_at',
                'question_count',
                'creator_user_id',
                'test_type',
                'review_status',
                'duration_seconds',
                'pass_mark_percentage',
                'language',
            ])
            ->with('interests:id,name')
            ->orderByDesc('id');

        if ($filters['scope'] === 'my_tests') {
            $query->where('creator_user_id', $userId)
                  ->where('review_status' , '!=' , TestReviewStatus::Deleted->value);
        }

        if ($filters['scope'] === 'explore') {
            $query
                ->where('creator_user_id', '!=', $userId)
                ->where('test_type', TestType::Public->value)
                ->where('review_status', TestReviewStatus::Approved->value)
                ->whereNotNull('published_at');
        }

        $this->applyTypeFilter($query, $filters);
        $this->applyStatusFilter($query, $filters);
        $this->applyLanguageFilter($query, $filters);
        $this->applyTimerFilter($query, $filters);
        $this->applyQuestionCountFilter($query, $filters);
        $this->applyPassMarkFilter($query, $filters);
        $this->applyInterestFilter($query, $filters);

        return $query->cursorPaginate($filters['per_page'] ?? 15);
    }

    private function applyTypeFilter($query, array $filters): void
    {
        $type = $filters['type'] ?? null;

        if (! $type || $type === 'all') {
            return;
        }


        match ($type) {
            'public' => $query->where('test_type', TestType::Public->value),
            'private' => $query->where('test_type', TestType::Private->value),
            'paid' => $query
                ->where('test_type', TestType::Public->value)
                ->where('price', '>', 0),
        };
    }

    private function applyStatusFilter($query, array $filters): void
    {
        $status = $filters['status'] ?? null;

        if (! $status || $status === 'all') {
            return;
        }

        $map = [
            'new' => TestReviewStatus::New->value,
            'under_review' => TestReviewStatus::UnderReview->value,
            'needs_revision' => TestReviewStatus::NeedsRevision->value,
            'approved' => TestReviewStatus::Approved->value,
            'reported' => TestReviewStatus::Reported->value,
        ];

        $query->where('review_status', $map[$status]);
    }

    private function applyLanguageFilter($query, array $filters): void
    {
        $language = $filters['language'] ?? null;

        if (! $language || $language === 'all') {
            return;
        }

        $map = [
            'arabic' => 'العربية',
            'english' => 'الإنكليزية',
            'mixed' => 'مختلطة',
        ];

        $query->where('language', $map[$language]);
    }

    private function applyTimerFilter($query, array $filters): void
    {
        if (! array_key_exists('has_timer', $filters)) {
            return;
        }

        if ((bool) $filters['has_timer']) {
            $query->whereNotNull('duration_seconds');
        } else {
            $query->whereNull('duration_seconds');
        }
    }

    private function applyQuestionCountFilter($query, array $filters): void
    {
        if (! isset($filters['questions_count_lte'])) {
            return;
        }

        $query->where('question_count', '<=', (int) $filters['questions_count_lte']);
    }

    private function applyPassMarkFilter($query, array $filters): void
    {
        if (! isset($filters['pass_mark_lte'])) {
            return;
        }

        $query->where('pass_mark_percentage', '<=', (int) $filters['pass_mark_lte']);
    }

    private function applyInterestFilter($query, array $filters): void
    {
        if (! isset($filters['interest_id'])) {
            return;
        }

        $query->whereHas('interests', function ($q) use ($filters) {
            $q->where('interests.id', $filters['interest_id']);
        });
    }
}
