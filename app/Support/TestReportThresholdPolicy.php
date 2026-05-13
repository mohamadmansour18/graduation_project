<?php

namespace App\Support;

class TestReportThresholdPolicy
{
    private const MIN_BASE_PARTICIPANTS = 20;

    private const SAME_REASON_MIN_REPORTS = 5;
    private const SAME_REASON_RATIO = 0.15;

    private const TOTAL_MIN_DISTINCT_REPORTERS = 10;
    private const TOTAL_REPORTS_RATIO = 0.20;

    public function shouldMarkAsReported(int $participantsCount, int $sameReasonReportersCount, int $totalDistinctReportersCount): bool
    {
        //$participantsCount = 48 , $sameReasonReportersCount = 6
        $base = max($participantsCount, self::MIN_BASE_PARTICIPANTS);

        $sameReasonRatio = $sameReasonReportersCount / $base;
        $totalReportsRatio = $totalDistinctReportersCount / $base;

        return $this->sameReasonThresholdReached(
                sameReasonReportersCount: $sameReasonReportersCount,
                sameReasonRatio: $sameReasonRatio
            ) || $this->totalReportsThresholdReached(
                totalDistinctReportersCount: $totalDistinctReportersCount,
                totalReportsRatio: $totalReportsRatio
            );
    }

    private function sameReasonThresholdReached(int $sameReasonReportersCount, float $sameReasonRatio): bool
    {
        return $sameReasonReportersCount >= self::SAME_REASON_MIN_REPORTS
            && $sameReasonRatio >= self::SAME_REASON_RATIO;
    }

    private function totalReportsThresholdReached(int $totalDistinctReportersCount, float $totalReportsRatio): bool
    {
        return $totalDistinctReportersCount >= self::TOTAL_MIN_DISTINCT_REPORTERS
            && $totalReportsRatio >= self::TOTAL_REPORTS_RATIO;
    }
}
