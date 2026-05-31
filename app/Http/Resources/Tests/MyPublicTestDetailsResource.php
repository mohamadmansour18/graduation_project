<?php

namespace App\Http\Resources\Tests;

use App\Helpers\DateProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyPublicTestDetailsResource extends JsonResource
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
                'price' => $test->price ?? 0,
                'likes_count' => $test->likes_count ?? 0,
                'reviews_count' => $test->reviews_count ?? 0,
                'bookmarks_count' => $test->bookmarks_count ?? 0,
            ],

            'extra_info' => [
                'question_count' => $test->question_count,
                'duration_seconds' => $test->duration_seconds ?? "غير محدد" ,
                'pass_mark_percentage' => $test->pass_mark_percentage ?? "غير محدد",
                'published_at' => DateProcessor::fromTimestamp($test->published_at) ?? "لم يتم نشر الاختبار للعامة بعد",
                'last_content_updated_at' => DateProcessor::fromTimestamp($test->last_content_updated_at) ?? "لم يتم تعديل المحتوى بعد",
                'target_level' => $test->target_level,
                'language' => $test->language,
                'participants_count' => $test->participants_count ?? 0,

                'interests' => $test->interests->map(fn ($interest) => [
                    'id' => $interest->id,
                    'name' => $interest->name,
                ])->values(),

                'preview_questions' => $test->previewQuestions->map(fn ($question) => [
                    'id' => $question->id,
                    'position' => $question->position,
                    'question_text' => $question->question_text,
                    'hint_text' => $question->hint_text ?? "لايوجد تلميح",
                    'options' => $question->testQuestionOptions->map(fn ($option) => [
                        'id' => $option->id,
                        'position' => $option->position,
                        'option_text' => $option->option_text,
                        'is_correct' => (bool) $option->is_correct,
                    ])->values(),
                ])->values(),
            ],

            'viewer_context' => $context,
        ];
    }
}
