<?php

namespace App\Http\Resources;

use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSoldTestsResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $purchases = $this->resource['purchases'];

        return [
            'stats' => [
                'total_sales_count' => $purchases->count(),
                'total_seller_net_amount_syp' => round($purchases->sum('seller_net_amount'), 2),
            ],

            'sales' => $purchases->map(function ($purchase) {
                return [
                    'purchase' => [
                        'buyer_name' => $purchase->buyerUser?->name,
                        'buyer_avatar_url' => ImageProcessor::urlOrDefault($purchase->buyerUser?->userProfile?->avatar_path , 'defaults/default-avatar.svg' , $purchase->buyerUser?->userProfile?->avatar_disk),

                        'buyer_is_academically_verified' => (bool) $purchase->buyerUser?->is_academically_verified,

                        'purchased_date' => optional($purchase->purchased_at)->toDateString(),
                        'purchased_time' => optional($purchase->purchased_at)->format('H:i'),

                        'gross_amount' => (float) $purchase->gross_amount,
                        'platform_fee_amount' => (float) $purchase->platform_fee_amount,
                        'seller_net_amount' => (float) $purchase->seller_net_amount,
//                      'currency' => $purchase->currency,
                    ],

                    'test' => [
                        'id' => $purchase->test?->id,
                        'title' => $purchase->test?->title,
                        'description' => $purchase->test?->description,
                        'target_level' => $purchase->test?->target_level,
                        'question_count' => (int) $purchase->test?->question_count,
                        'average_rating' => round((float) $purchase->test?->average_rating, 1),
                        'interests' => $purchase->test?->testIntersetSelections?->pluck('interest.name')->filter()->values()->toArray(),
                    ],
                ];
            })->values(),
        ];
    }
}
