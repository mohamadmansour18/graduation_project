<?php

namespace App\Http\Resources\Tests;

use App\Helpers\DateProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyPrivateTestDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $test = $this->resource['test'];
        $context = $this->resource['viewer_context'];

        return [
            'id' => $test->id,

            'basic_info' => [
                'title' => $test->title,
                'description' => $test->description,
                'difficulty_level' => $test->difficulty_level,
            ],

            'extra_info' => [
                'question_count' => $test->question_count,
                'duration_seconds' => $test->duration_seconds ?? "غير محدد",
                'pass_mark_percentage' => $test->pass_mark_percentage ?? "غير محدد",
                'published_at' => DateProcessor::fromTimestamp($test->published_at) ?? "",
                'last_content_updated_at' => DateProcessor::fromTimestamp($test->last_content_updated_at) ?? "لم يتم تعديل المحتوى بعد",
                'target_level' => $test->target_level,
                'language' => $test->language,
                'interests' => $test->interests->map(fn ($interest) => [
                    'id' => $interest->id,
                    'name' => $interest->name,
                ])->values(),
            ],

            'viewer_context' => $context,
        ];
    }
}
