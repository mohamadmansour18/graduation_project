<?php

namespace App\Http\Resources;

use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestDashboardContentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $test = $this->resource['test'];

        return [
            'questions' => $test->testQuestions->map(fn ($question) => [
                'question_id' => (int) $question->id,
                'position' => (int) $question->position,
                'question_text' => $question->question_text,
                'hint_text' => $question->hint_text ?? null,

                'options' => $question->testQuestionOptions->map(fn ($option) => [
                    'option_id' => (int) $option->id,
                    'position' => (int) $option->position,
                    'option_text' => $option->option_text,
                    'is_correct' => (bool) $option->is_correct,
                ])->values(),
            ])->values(),
        ];
    }
}
