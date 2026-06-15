<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialMonthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'month_no' => (int) $this->month_no,

            'sold_purchase_count' => (int) $this->sold_purchase_count,

            'gross_sales_amount' => $this->gross_sales_amount,

            'platform_net_profit_amount' => $this->platform_net_profit_amount,

            'users_profit_amount' => $this->users_profit_amount,
        ];
    }
}
