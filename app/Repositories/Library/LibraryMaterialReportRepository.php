<?php

namespace App\Repositories\Library;

use App\Enums\LibraryDecision;
use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\LibraryTriggerType;
use App\Enums\VisibilityType;
use App\Exceptions\Api\LibraryMaterialException;
use App\Models\LibraryMaterial;
use App\Models\LibraryMaterialReport;
use App\Models\LibraryMaterialReviewRound;
use App\Models\LibraryMaterialStatusHistory;
use App\Models\LibraryReportReasonCounter;
use App\Support\LibraryMaterialReportThresholdPolicy;
use Illuminate\Support\Facades\DB;

class LibraryMaterialReportRepository
{
    public function createReportAndMaybeMarkAsReported(int $userId, int $materialId, string $reason, ?string $description, LibraryMaterialReportThresholdPolicy $thresholdPolicy): array
    {
        return DB::transaction(function () use ($userId, $materialId, $reason, $description, $thresholdPolicy)
        {
            $material = LibraryMaterial::query()
                ->whereKey($materialId)
                ->lockForUpdate()
                ->first();

            if (
                ! $material
                || $material->visibility_type !== VisibilityType::Public
                || $material->review_status !== LibraryMaterialReviewStatus::Approved
                || (int) $material->creator_user_id === $userId
            ) {
                throw LibraryMaterialException::materialNotAvailableForReport();
            }

            $approvalVersion = (int) $material->current_approval_version;

            $alreadyReported = LibraryMaterialReport::query()
                ->where('library_material_id', $material->id)
                ->where('user_id', $userId)
                ->where('approval_version', $approvalVersion)
                ->where('reason', $reason)
                ->exists();

            if ($alreadyReported) {
                throw LibraryMaterialException::alreadyReportedSameReasonForCurrentVersion();
            }

            LibraryMaterialReport::query()->create([
                'library_material_id' => $material->id,
                'user_id' => $userId,
                'approval_version' => $approvalVersion,
                'reason' => $reason,
                'description' => $description,
                'reported_at' => now(),
            ]);

            $this->incrementReasonCounter(
                materialId: $material->id,
                approvalVersion: $approvalVersion,
                reason: $reason
            );

            $sameReasonReportersCount = $this->countSameReasonDistinctReporters(
                materialId: $material->id,
                approvalVersion: $approvalVersion,
                reason: $reason
            );

            $totalDistinctReportersCount = $this->countTotalDistinctReporters(
                materialId: $material->id,
                approvalVersion: $approvalVersion
            );

            $shouldMarkReported = $thresholdPolicy->shouldMarkAsReported(
                likesCount: (int) $material->like_count,
                sameReasonReportersCount: $sameReasonReportersCount,
                totalDistinctReportersCount: $totalDistinctReportersCount
            );

            if (! $shouldMarkReported) {
                return [
                    'statusChangedToReported' => false
                ];
            }

            $this->markMaterialAsReported(
                material: $material,
                changedByUserId: $userId,
                approvalVersion: $approvalVersion,
                sameReasonReportersCount: $sameReasonReportersCount,
                totalDistinctReportersCount: $totalDistinctReportersCount,
                reason: $reason,
            );

            return [
                'statusChangedToReported' => true
            ];
        });
    }

    private function incrementReasonCounter(int $materialId, int $approvalVersion, string $reason): void
    {
        DB::table('library_report_reason_counters')->insertOrIgnore([
            'library_material_id' => $materialId,
            'approval_version' => $approvalVersion,
            'reason' => $reason,
            'reporters_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('library_report_reason_counters')
            ->where('library_material_id', $materialId)
            ->where('approval_version', $approvalVersion)
            ->where('reason', $reason)
            ->increment('reporters_count', 1, [
                'updated_at' => now(),
            ]);
    }

    private function countSameReasonDistinctReporters(int $materialId, int $approvalVersion, string $reason): int
    {
        return LibraryMaterialReport::query()
            ->where('library_material_id', $materialId)
            ->where('approval_version', $approvalVersion)
            ->where('reason', $reason)
            ->distinct('user_id')
            ->count('user_id');
    }

    private function countTotalDistinctReporters(int $materialId, int $approvalVersion): int
    {
        return LibraryMaterialReport::query()
            ->where('library_material_id', $materialId)
            ->where('approval_version', $approvalVersion)
            ->distinct('user_id')
            ->count('user_id');
    }

    private function markMaterialAsReported(LibraryMaterial $material, int $changedByUserId, int $approvalVersion , int $sameReasonReportersCount , int $totalDistinctReportersCount , string $reason): void
    {
        $material->forceFill([
            'review_status' => LibraryMaterialReviewStatus::Reported->value,
        ])->save();

        $roundNo = LibraryMaterialReviewRound::query()
            ->where('library_material_id', $material->id)
            ->max('round_no');

        LibraryMaterialReviewRound::query()->create([
            'library_material_id' => $material->id,
            'round_no' => ((int) $roundNo) + 1,
            'reviewer_user_id' => null,
            'trigger_type' => LibraryTriggerType::Auto_Report->value,
            'decision' => LibraryDecision::Pending->value,
            'based_on_approval_version' => $approvalVersion,
            'started_at' => now(),
            'decided_at' => null,
        ]);

        LibraryMaterialStatusHistory::query()->create([
            'library_material_id' => $material->id,
            'from_status' => LibraryMaterialReviewStatus::Approved->value,
            'to_status' => LibraryMaterialReviewStatus::Reported->value,
            'changed_by_user_id' => null,
            'note' => $this->buildAutoReportNote(
                reason: $reason,
                sameReasonReportersCount: $sameReasonReportersCount,
                totalDistinctReportersCount: $totalDistinctReportersCount,
                approvalVersion: $approvalVersion
            ),
        ]);
    }

    private function buildAutoReportNote(string $reason, int $sameReasonReportersCount, int $totalDistinctReportersCount, int $approvalVersion): string
    {
        return sprintf(
            'تم تحويل الاختبار تلقائياً إلى reported بسبب وصول البلاغات إلى العتبة. السبب: %s | عدد بلاغات نفس السبب: %d | إجمالي المبلغين: %d | نسخة الاعتماد: %d',
            $reason,
            $sameReasonReportersCount,
            $totalDistinctReportersCount,
            $approvalVersion
        );
    }
}
