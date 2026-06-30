<?php

namespace App\Repositories\Auth;

use App\Enums\PaymentStatus;
use App\Models\TestPurchase;

class UserSalesRepository
{
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
