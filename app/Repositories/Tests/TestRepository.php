<?php

namespace App\Repositories\Tests;

use App\Enums\PaymentStatus;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Models\Test;
use App\Models\TestQuestion;
use App\Models\TestReview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TestRepository
{
    public function findDetailsById(int $testId , int $viewerId ): Model|Builder|null
    {

        return Test::query()
            ->select('test.*')
            ->selectRaw(
                'exists (
                select 1
                from user_follows
                where user_follows.followed_user_id = test.creator_user_id
                and user_follows.follower_user_id = ?
            ) as viewer_is_following_creator',
                [$viewerId]
            )
            ->with([
                'creatorUser:id,name,is_academically_verified',
                'creatorUser.userProfileStat:user_id,followers_count,following_count,published_tests_count',
                'interests:id,name',
                'creatorUser.userProfile:user_id,avatar_path'
            ])
            ->withExists([
                'testPurchases as viewer_has_purchased' => function (Builder $query) use ($viewerId) {
                    $query
                        ->where('buyer_user_id', $viewerId)
                        ->where('payment_status', PaymentStatus::Paid->value);
                },
                'testLikes as viewer_has_liked_it' => function (Builder $query) use ($viewerId , $testId) {
                    $query
                        ->where('user_id', $viewerId);
                },
                'testBookmarks as viewer_has_bookmarked_it' => function (Builder $query) use ($viewerId, $testId) {
                    $query
                        ->where('user_id', $viewerId);
                },
                'testAttempts as viewer_has_attempted_it' => function (Builder $query) use ($viewerId, $testId) {
                    $query
                        ->where('user_id', $viewerId);
                }
            ])
            ->where('id', $testId)
            ->first();
    }

    public function findOwnedDetailsById(int $testId, int $ownerId, string $testType, bool $withPreviewQuestions = false): Model|Builder|null
    {
        return Test::query()
            ->select([
                'id',
                'creator_user_id',
                'title',
                'description',
                'difficulty_level',
                'price',
                'likes_count',
                'reviews_count',
                'bookmarks_count',
                'question_count',
                'duration_seconds',
                'pass_mark_percentage',
                'published_at',
                'last_content_updated_at',
                'target_level',
                'language',
                'participants_count',
                'review_status',
                'test_type',
            ])
            ->with([
                'interests:id,name',
            ])
            ->when($withPreviewQuestions , function (Builder $query){
                $query->with([
                    'previewQuestions' => function ($questionQuery) {
                        $questionQuery->select('id', 'test_id', 'position', 'question_text' , 'hint_text')
                            ->orderBy('position')
                            ->with([
                                'testQuestionOptions' => function ($OptionQuery) {
                                    $OptionQuery->select('id', 'test_question_id', 'position', 'option_text' , 'is_correct')
                                        ->orderBy('position');
                                }
                            ]);
                    }
                ]);
            })
            ->where('id', $testId)
            ->where('creator_user_id', $ownerId)
            ->where('test_type', $testType)
            ->first();

    }

    public function findVisiblePublicTest(int $testId): Builder|Model|null
    {
        return Test::query()
            ->select([
                'id',
                'creator_user_id',
                'test_type',
                'review_status',
                'reviews_count',
                'average_rating'
            ])
            ->where('id', $testId)
            ->where('test_type', TestType::Public->value)
            ->where('review_status', TestReviewStatus::Approved->value)
            ->first();
    }

    public function getPreviewQuestionsByTestId(int $testId): array|Collection
    {
        return TestQuestion::query()
            ->select([
                'id',
                'position',
                'question_text',
                'hint_text',
            ])
            ->with([
                'testQuestionOptions' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'test_question_id',
                            'position',
                            'option_text',
                            'is_correct',
                        ])
                        ->orderBy('position');
                },
            ])
            ->where('test_id', $testId)
            ->where('is_preview', true)
            ->orderBy('position')
            ->get();
    }

    public function getRatingDistribution(int $testId): array
    {
        $rows = TestReview::query()
            ->select('rating', DB::raw('COUNT(*) as total'))
            ->where('test_id', $testId)
            ->groupBy('rating')
            ->pluck('total', 'rating')
            ->toArray();

        return [
            5 => (int) ($rows[5] ?? 0),
            4 => (int) ($rows[4] ?? 0),
            3 => (int) ($rows[3] ?? 0),
            2 => (int) ($rows[2] ?? 0),
            1 => (int) ($rows[1] ?? 0),
        ];
    }

    public function countTextComments(int $testId): int
    {
        return TestReview::query()
            ->where('test_id', $testId)
            ->whereNotNull('review_text')
            ->count();
    }

    public function paginateReviews(int $testId, int $viewerId, ?int $rating, int $perPage = 20 , $excludeViewerReview = false): LengthAwarePaginator
    {
        return TestReview::query()
            ->select([
                'id',
                'test_id',
                'user_id',
                'rating',
                'review_text',
                'helpful_yes_count',
                'created_at',
            ])
            ->with([
                'user:id,name,is_academically_verified',
                'user.userProfile:user_id,avatar_path',
                'testReviewFeedback' => function ($query) use ($viewerId) {
                    $query->select('id' , 'test_review_id' , 'user_id' , 'vote')
                        ->where('user_id', $viewerId);
                }
            ])
            ->where('test_id', $testId)
            ->when($rating !== null , function ($query) use ($rating) {
                $query->where('rating', $rating);
            })
            ->when($excludeViewerReview , function ($query) use ($viewerId) {
                $query->where('user_id', '!=', $viewerId);
            })
            ->orderByDesc('helpful_yes_count')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
    public function findMyReviewForTest(int $testId, int $viewerId): Builder|Model|null
    {
        return TestReview::query()
            ->select([
                'id',
                'test_id',
                'user_id',
                'rating',
                'review_text',
                'helpful_yes_count',
                'created_at',
            ])
            ->with([
                'user:id,name,is_academically_verified',
                'user.userProfile:user_id,avatar_path',
            ])
            ->where('test_id', $testId)
            ->where('user_id', $viewerId)
            ->first();
    }

    /////////////////////////////////////////////////////////////////

    public function findShareableTest(int $testId): ?object
    {
        return DB::table('test')
            ->select([
                'id',
                'creator_user_id',
                'title',
                'test_type',
                'review_status',
                'share_slug',
            ])
            ->where('id', $testId)
            ->where('test_type', TestType::Public->value)
            ->where('review_status', TestReviewStatus::Approved->value)
            ->first();
    }

    public function updateShareSlug(int $testId, string $slug): void
    {
        DB::table('test')
            ->where('id', $testId)
            ->whereNull('share_slug')
            ->update([
                'share_slug' => $slug,
                'updated_at' => now(),
            ]);
    }

    public function shareSlugExists(string $slug): bool
    {
        return DB::table('test')
            ->where('share_slug', $slug)
            ->exists();
    }

    public function findByShareSlug(string $slug): ?object
    {
        return DB::table('test')
            ->select(['id', 'share_slug', 'creator_user_id'])
            ->where('share_slug', $slug)
            ->where('test_type', TestType::Public->value)
            ->where('review_status', TestReviewStatus::Approved->value)
            ->first();
    }

    /////////////////////////////////////////////////////////////////

    public function findTestWithContent(int $testId): Model|Builder|null
    {
        return Test::query()
            ->select([
                'id',
                'creator_user_id',
                'title',
                'question_count',
                'duration_seconds',
                'pass_mark_percentage',
                'test_type',
                'review_status',
                'price',
            ])
            ->with([
                'testQuestions' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'test_id',
                            'position',
                            'question_text',
                            'hint_text',
                        ])
                        ->orderBy('position')
                        ->with([
                            'testQuestionOptions' => function ($optionQuery) {
                                $optionQuery
                                    ->select([
                                        'id',
                                        'test_question_id',
                                        'position',
                                        'option_text',
                                        'is_correct',
                                    ])
                                    ->orderBy('position');
                            },
                        ]);
                },
            ])
            ->where('id', $testId)
            ->first();
    }

    public function getViewerInfo(int $viewerId): ?object
    {
        return DB::table('users')
            ->leftJoin('user_profile', 'user_profile.user_id', '=', 'users.id')
            ->select([
                'users.id',
                'users.name',
                'user_profile.avatar_path',
            ])
            ->where('users.id', $viewerId)
            ->first();
    }

    public function hasUserPurchasedTest(int $testId, int $userId): bool
    {
        return DB::table('test_purchases')
            ->where('test_id', $testId)
            ->where('buyer_user_id', $userId)
            ->where('payment_status', PaymentStatus::Paid->value)
            ->exists();
    }

}
