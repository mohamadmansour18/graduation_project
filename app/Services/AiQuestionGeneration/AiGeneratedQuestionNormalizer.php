<?php

namespace App\Services\AiQuestionGeneration;

use App\Exceptions\Api\AiQuestionGenerationException;

class AiGeneratedQuestionNormalizer
{
    /*
        $payload = [
            'questions' => [
                [
                    'question_text' => 'ما عاصمة سوريا؟',
                    'hint_text' => 'مدينة تاريخية',
                    'options' => [
                        ['option_text' => 'دمشق', 'is_correct' => true],
                        ['option_text' => 'حلب', 'is_correct' => false],
                    ],
                ],
            ],
        ];
     * */

    public function normalize(array $payload, int $requestedQuestionCount): array
    {
        $contentType = $payload['content_type'] ?? 'Unclear';

        if ($contentType === 'NotEducational') {
            throw AiQuestionGenerationException::contentIsNotEducational();
        }

        $questions = $payload['questions'] ?? null;

        if (! is_array($questions)) {
            throw AiQuestionGenerationException::invalidGeneratedQuestions();
        }

        $minimumAcceptableQuestions = (int) ceil($requestedQuestionCount * 0.5);

        if (count($questions) < $minimumAcceptableQuestions) {
            throw AiQuestionGenerationException::notEnoughEducationalContent(
                minimumRequired: $minimumAcceptableQuestions,
                generatedCount: count($questions)
            );
        }

        $normalizedQuestions = [];

        foreach (array_slice($questions, 0, $requestedQuestionCount) as $question)
        {
            $normalizedQuestion = $this->normalizeQuestion($question);
            $normalizedQuestions[] = $normalizedQuestion;
        }

        return $normalizedQuestions;
    }

    private function normalizeQuestion(array $question): array
    {
        $questionText = trim((string) ($question['question_text'] ?? ''));
        $hintText = trim((string) ($question['hint_text'] ?? ''));
        $options = $question['options'] ?? null;

        if ($questionText === '' || ! is_array($options)) {
            throw AiQuestionGenerationException::invalidGeneratedQuestions();
        }

        if (count($options) < 2 || count($options) > 5) {
            throw AiQuestionGenerationException::invalidGeneratedQuestions();
        }

        $normalizedOptions = [];
        $correctOptionsCount = 0;

        foreach ($options as $option) {
            $optionText = trim((string) ($option['option_text'] ?? ''));
            $isCorrect = (bool) ($option['is_correct'] ?? false);

            if ($optionText === '') {
                throw AiQuestionGenerationException::invalidGeneratedQuestions();
            }

            if ($isCorrect) {
                $correctOptionsCount++;
            }

            $normalizedOptions[] = [
                'option_text' => mb_substr($optionText, 0, 500),
                'is_correct' => $isCorrect,
            ];
        }

        if ($correctOptionsCount !== 1) {
            throw AiQuestionGenerationException::invalidGeneratedQuestions();
        }

        return [
            'question_text' => mb_substr($questionText, 0, 500),
            'hint_text' => $hintText !== '' ? mb_substr(trim((string) $hintText), 0, 250) : null,
            'options' => $normalizedOptions,
        ];

    }
}
