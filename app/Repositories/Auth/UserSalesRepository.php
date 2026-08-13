<?php

namespace App\Repositories\Auth;

use App\Enums\PaymentStatus;
use App\Models\Test;
use App\Models\TestPurchase;
use Illuminate\Database\Eloquent\Collection;

class UserSalesRepository
{
    public function getPurchasedTests(int $buyerUserId, string $tab): Collection
    {
        return Test::query()
            ->select([
                'test.id',
                'test.title',
                'test.description',
                'test.difficulty_level',
                'test.average_rating',
                'test.price',
                'test.published_at',
                'test.question_count',
                'test_purchases.purchased_at',
            ])
            ->join('test_purchases', 'test_purchases.test_id', '=', 'test.id')
            ->where('test_purchases.buyer_user_id', $buyerUserId)
            ->where('test_purchases.payment_status', PaymentStatus::Paid->value)
            ->whereNotNull('test_purchases.purchased_at')
            ->when($tab === 'today', function ($query) {
                $query->whereDate('test_purchases.purchased_at', today());
            })
            ->when($tab === 'month', function ($query) {
                $query->whereBetween('test_purchases.purchased_at', [now()->subMonth(), now()]);
            })
            ->with([
                'testIntersetSelections:id,test_id,interest_id,slot_no',
                'testIntersetSelections.interest:id,name',
            ])
            ->orderByDesc('test_purchases.purchased_at')
            ->orderByDesc('test_purchases.id')
            ->get();
    }

    public function getSoldTestPurchases(int $sellerUserId, string $tab): array|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Models\_IH_TestPurchase_C
    {
        return TestPurchase::query()
            ->where('seller_user_id', $sellerUserId)
            ->where('payment_status', PaymentStatus::Paid->value)
            ->when($tab === 'today', function ($query) {
                $query->whereDate('purchased_at', today());
            })
            ->when($tab === 'week', function ($query) {
                $query->where('purchased_at', '>=', now()->subWeek());
            })
            ->when($tab === 'month', function ($query) {
                $query->where('purchased_at', '>=', now()->subMonth());
            })
            ->with([
                'buyerUser:id,name,is_academically_verified',
                'buyerUser.userProfile:id,user_id,avatar_disk,avatar_path',
                'test:id,title,description,target_level,question_count,average_rating',
                'test.testIntersetSelections:id,test_id,interest_id,slot_no',
                'test.testIntersetSelections.interest:id,name',
            ])
            ->latest('purchased_at')
            ->get();
    }
}
