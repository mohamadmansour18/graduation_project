<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardUserTestsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'stats' => [
                'total_tests_count' => $this->resource['stats']['total_tests_count'] ?? 0,
                'free_tests_count' => $this->resource['stats']['free_tests_count'] ?? 0,
                'paid_tests_count' => $this->resource['stats']['paid_tests_count'] ?? 0,
            ],

            'tests' => $this->resource['tests']->map(function ($test) {
                return [
                    'id' => $test->id,
                    'title' => $test->title,
                    'description' => $test->description,
                    'difficulty_level' => $test->difficulty_level,
                    'price' => $test->price ?? 0,
                    'average_rating' => round((float) $test->average_rating, 1),
                    'question_count' => (int) $test->question_count,
                    'published_at' => DateProcessor::fromTimestamp($test->published_at) ??  DateProcessor::fromTimestamp($test->created_at),

                    'interests' => $test->testIntersetSelections->pluck('interest.name')->filter()->values()->toArray(),
                ];
            })->values(),
        ];
    }
}
