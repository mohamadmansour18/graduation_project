<?php

namespace App\Support;

class LibraryMaterialReportThresholdPolicy
{
    private const int MIN_BASE_LIKES = 20;

    private const int SAME_REASON_MIN_REPORTS = 5;
    private const float SAME_REASON_RATIO = 0.15;

    private const int TOTAL_MIN_DISTINCT_REPORTERS = 10;
    private const float TOTAL_REPORTS_RATIO = 0.20;

    public function shouldMarkAsReported(int $likesCount, int $sameReasonReportersCount, int $totalDistinctReportersCount): bool
    {
        $base = max($likesCount, self::MIN_BASE_LIKES);

        $sameReasonRatio = $sameReasonReportersCount / $base;
        $totalReportsRatio = $totalDistinctReportersCount / $base;

        return (
                $sameReasonReportersCount >= self::SAME_REASON_MIN_REPORTS
                && $sameReasonRatio >= self::SAME_REASON_RATIO
            ) || (
                $totalDistinctReportersCount >= self::TOTAL_MIN_DISTINCT_REPORTERS
                && $totalReportsRatio >= self::TOTAL_REPORTS_RATIO
            );
    }
}
