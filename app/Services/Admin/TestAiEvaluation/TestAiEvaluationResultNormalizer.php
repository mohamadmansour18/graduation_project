<?php

namespace App\Services\Admin\TestAiEvaluation;

use App\Exceptions\Api\TestAiEvaluationException;

class TestAiEvaluationResultNormalizer
{
    public function normalize(array $payload, int $questionsCount, string $provider): array
    {
        $score = $payload['score_percentage'] ?? null;
        $correctQuestions = $payload['correct_questions'] ?? null;
        $suspiciousQuestions = $payload['suspicious_questions'] ?? null;
        $issues = $payload['issues'] ?? [];

        if (! is_numeric($score) || (int) $score < 0 || (int) $score > 100) {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: $provider,
                operation: 'normalize',
                reason: 'score_percentage must be between 0 and 100.'
            );
        }

        if (! is_string($correctQuestions) || ! $this->isValidCountLabel($correctQuestions, $questionsCount)) {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: $provider,
                operation: 'normalize',
                reason: 'correct_questions label is invalid.'
            );
        }

        if (! is_string($suspiciousQuestions) || ! $this->isValidCountLabel($suspiciousQuestions, $questionsCount)) {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: $provider,
                operation: 'normalize',
                reason: 'suspicious_questions label is invalid.'
            );
        }

        if (! is_array($issues)) {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: $provider,
                operation: 'normalize',
                reason: 'issues must be an array.'
            );
        }

        return [
            'score_percentage' => (int) $score,
            'correct_questions' => $correctQuestions,
            'suspicious_questions' => $suspiciousQuestions,
            'issues' => $this->normalizeIssues($issues, $questionsCount),
        ];
    }

    private function isValidCountLabel(string $label, int $questionsCount): bool
    {
        if (! preg_match('/^\d+\/\d+$/', $label)) {
            return false;
        }

        [$count, $total] = array_map('intval', explode('/', $label));

        return $total === $questionsCount && $count >= 0 && $count <= $questionsCount;
    }

    private function normalizeIssues(array $issues, int $questionsCount): array
    {
        $normalized = [];

        foreach ($issues as $issue) {
            if (! is_array($issue)) {
                continue;
            }

            $questionPosition = $issue['question_position'] ?? null;
            $problem = trim((string) ($issue['problem'] ?? ''));

            if (! is_numeric($questionPosition) || (int) $questionPosition < 1 || (int) $questionPosition > $questionsCount || $problem === '') {
                continue;
            }

            $normalized[] = [
                'question_position' => (int) $questionPosition,
                'problem' => $this->limitWords($problem, 100),
            ];
        }

        return $normalized;
    }

    private function limitWords(string $text, int $limit): string
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];

        if (count($words) <= $limit) {
            return $text;
        }

        return implode(' ', array_slice($words, 0, $limit));
    }
}
