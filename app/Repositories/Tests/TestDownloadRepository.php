<?php

namespace App\Repositories\Tests;

use App\Enums\PaymentStatus;
use App\Models\Test;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TestDownloadRepository
{
    public function findDownloadableTest(int $testId): Builder|Test|null
    {
        return Test::query()
            ->select([
                'id',
                'creator_user_id',
                'title',
                'description',
                'difficulty_level',
                'duration_seconds',
                'pass_mark_percentage',
                'target_level',
                'price',
                'language',
                'question_count',
                'downloads_count',
                'test_type',
                'current_approval_version',
                'last_content_updated_at',
                'updated_at'
            ])->with([
                'testQuestions' => function ($query) {
                    $query->select([
                        'id',
                        'test_id',
                        'position',
                        'question_text',
                        'hint_text',
                    ])->orderBy('position')
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
                }
            ])
            ->where('id', $testId)
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

    public function createDownloadLogIfMissing(int $testId, int $userId): bool
    {
        $inserted = DB::table('test_download_logs')->insertOrIgnore([
            'test_id' => $testId,
            'user_id' => $userId,

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $inserted === 1;
    }

    public function incrementTestDownloadsCount(int $testId): void
    {
        DB::table('test')
            ->where('id', $testId)
            ->increment('downloads_count', 1, [
                'updated_at' => now(),
            ]);
    }
}
