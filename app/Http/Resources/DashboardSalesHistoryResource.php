<?php

namespace App\Http\Resources;

use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardSalesHistoryResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $sales = $this->resource['sales'];

        return [
            'period' => $this->resource['period'],

            'stats' => [
                'distinct_sold_tests_count' => $this->resource['stats']['distinct_sold_tests_count'],
                'gross_sales_amount' => $this->resource['stats']['gross_sales_amount'],
                'users_profit_amount' => $this->resource['stats']['users_profit_amount'],
                'platform_net_profit_amount' => $this->resource['stats']['platform_net_profit_amount'],
            ],

            'sales' => collect($sales->items())->map(function ($purchase) {
                $grossAmount = (float) $purchase->gross_amount;
                $platformFeeAmount = (float) $purchase->platform_fee_amount;

                return [
                    'sale_id' => $purchase->id,

                    'buyer' => [
                        'name' => $purchase->buyerUser?->name,

                        'avatar' => ImageProcessor::urlOrDefault(
                            $purchase->buyerUser?->userProfile?->avatar_path,
                            'defaults/default-avatar.svg',
                            $purchase->buyerUser?->userProfile?->avatar_disk,
                        ),
                    ],

                    'gross_amount' => round($grossAmount, 2),
                    'platform_fee_amount' => round($platformFeeAmount, 2),

                    'platform_fee_percentage' => $grossAmount > 0
                        ? round(($platformFeeAmount / $grossAmount) * 100, 2)
                        : 0,

                    'purchase_date' => optional($purchase->purchased_at)?->format('Y-m-d'),
                    'purchase_time' => optional($purchase->purchased_at)?->format('H:i:s'),

                    'test_id' => $purchase->test_id,
                    'test_status' => $purchase->test_review_status,
                ];
            })->values(),

            'meta' => [
                'per_page' => $sales->perPage(),
                'next_cursor' => optional($sales->nextCursor())->encode(),
                'previous_cursor' => optional($sales->previousCursor())->encode(),
                'has_more_pages' => $sales->hasMorePages(),
            ],
        ];
    }
}
