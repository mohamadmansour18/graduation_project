<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminFinancialDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'year' => $this->resource['year'],

            'summary' => $this->resource['summary'],

            'top_months_by_sold_purchases' => FinancialMonthResource::collection(
                $this->resource['top_months_by_sold_purchases']
            ),

            'top_months_by_platform_profit' => FinancialMonthResource::collection(
                $this->resource['top_months_by_platform_profit']
            ),

            'most_purchased_test' => $this->when(
                $this->resource['most_purchased_test'] !== null,
                fn () => new MostPurchasedTestFinancialResource($this->resource['most_purchased_test']),
                null
            ),
        ];
    }
}
