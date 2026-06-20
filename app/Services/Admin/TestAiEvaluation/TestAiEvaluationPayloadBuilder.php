<?php

namespace App\Services\Admin\TestAiEvaluation;

use App\Models\Test;

class TestAiEvaluationPayloadBuilder
{
    public function build(Test $test): array
    {
        return [
            'test_id' => $test->id,
            'questions_count' => $test->testQuestions->count(),
            'difficulty_level' => $this->enumValue($test->difficulty_level),
            'language' => $this->enumValue($test->language),
            'questions' => $test->testQuestions
                ->map(function ($question) {
                    return [
                        'question_id' => (int) $question->id,
                        'position' => (int) $question->position,
                        'question_text' => (string) $question->question_text,
                        'options' => $question->testQuestionOptions
                            ->map(function ($option) {
                                return [
                                    'option_id' => (int) $option->id,
                                    'position' => (int) $option->position,
                                    'option_text' => (string) $option->option_text,
                                    'is_correct' => (bool) $option->is_correct,
                                ];
                            })
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum
            ? (string) $value->value
            : (string) $value;
    }
}
