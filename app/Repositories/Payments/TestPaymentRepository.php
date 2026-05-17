<?php

namespace App\Repositories\Payments;

use Illuminate\Support\Facades\DB;

class TestPaymentRepository
{
    public function findTestForPurchase(int $testId): ?object
    {
        return DB::table('test')
            ->select([
                'id',
                'creator_user_id',
                'title',
                'price',
                'test_type',
                'review_status',
                'published_at',
            ])
            ->where('id', $testId)
            ->first();
    }
}
