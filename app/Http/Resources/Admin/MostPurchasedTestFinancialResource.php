<?php

namespace App\Http\Resources\Admin;

use App\Helpers\DateProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MostPurchasedTestFinancialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $test = $this->test;

        return [
            'purchase_count' => (int) $this->purchase_count,

            'gross_sales_amount' => (float) $this->gross_sales_amount,

            'platform_net_profit_amount' => (float) $this->platform_net_profit_amount,

            'users_profit_amount' => (float) $this->users_profit_amount,

            'test' => $test ? [
                'id' => (int) $test->id,
                'title' => $test->title,
                'description' => $test->description,
                'price' => $test->price ?? 0,
                'scientific_interests' => $test->testIntersetSelections->pluck('interest.name')->filter()->values()->toArray(),
                'average_rating' => round((float) $this->average_rating, 1),
                'difficulty_level' => $test->difficulty_level,
                'question_count' => (int) $test->question_count,
                'published_at' => DateProcessor::fromTimestamp($test->published_at),
            ] : null,
        ];
    }
}
