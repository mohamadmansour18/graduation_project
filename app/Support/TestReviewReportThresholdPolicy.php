<?php

namespace App\Support;

class TestReviewReportThresholdPolicy
{
    public function deletionThreshold(int $yesCount, int $noCount): int
    {
        $totalFeedback = $yesCount + $noCount;

        if ($totalFeedback >= 10) {
            $yesRatio = $yesCount / $totalFeedback;
            $noRatio = $noCount / $totalFeedback;

            if ($yesRatio >= 0.70) {
                return 15;
            }

            if ($noRatio >= 0.70) {
                return 8;
            }
        }

        return 10;
    }

    public function shouldDeleteReview(int $reportsCount, int $yesCount, int $noCount): bool
    {
        return $reportsCount >= $this->deletionThreshold(
                yesCount: $yesCount,
                noCount: $noCount
            );
    }
}
