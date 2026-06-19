<?php

namespace App\Http\Resources;

use App\Enums\TestReviewStatus;
use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestManagementStatusHistoryResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'test_id' => $this->id,

            'current_status' => $this->review_status->value,

            'histories' => $this->testStatusHistories
                ->map(fn ($history) => $this->formatHistory($history))
                ->values(),
        ];
    }

    private function formatHistory($history): array
    {
        return [
            'id' => $history->id,
            'title' => $history->to_status->value,
            'entered_at' => DateProcessor::fromTimestamp($history->created_at),
            'note' => $history->note ?? "لايوجد ملاحظات لهذه الحالة",
            'details' => $this->detailsForStatus($history),
        ];
    }

    private function detailsForStatus($history): array
    {
        $status = $history->to_status->value;

        return match ($status) {
            TestReviewStatus::Deleted->value        => $this->deletedDetails($history),
            TestReviewStatus::Reported->value       => $this->reportedDetails($history),
            TestReviewStatus::Approved->value       => $this->approvedDetails($history),
            TestReviewStatus::UnderReview->value    => $this->underReviewDetails($history),
            TestReviewStatus::NeedsRevision->value  => $this->needsRevisionDetails($history),
            TestReviewStatus::New->value            => $this->newDetails($history),
        };
    }

    private function deletedDetails($history): array
    {
        $actor = $history->changedByUser ?: $history->reviewRound?->reviewerUser;

        return [
            'actor' => $this->userSummary($actor),
            'reason' => $history->note,
            'decision_at' => DateProcessor::fromTimestamp($history?->reviewRound?->decided_at)
                          ?? DateProcessor::fromTimestamp($history->created_at),
        ];
    }

    private function reportedDetails($history): array
    {
        return [
            'reason' => $history->note,
            'decision_at' => DateProcessor::fromTimestamp($history->created_at),
            'review_round' => $this->roundSummary($history->reviewRound),
        ];
    }

    private function approvedDetails($history): array
    {
        $actor = $history->changedByUser ?: $history->reviewRound?->reviewerUser;

        return [
            'actor' => $this->userSummary($actor, withRole: true),
            'decision_at' => DateProcessor::fromTimestamp($history?->reviewRound?->decided_at)
                          ?? DateProcessor::fromTimestamp($history->created_at),
            'review_round' => $this->roundSummary($history?->reviewRound),
        ];
    }

    private function underReviewDetails($history): array
    {
        $changeLogs = $history->reviewRound?->testRevisionChangeLogs ?? collect();

        return [
            'decision_at' => DateProcessor::fromTimestamp($history->created_at),
            'changes_count' => $changeLogs->count(),

            'changes' => $changeLogs
                ->values()
                ->map(function ($changeLog, int $index) {
                    return [
                        'change_no' => $index + 1,
                        'revision_type' => $changeLog->revision_type->value,
                        'question_position' => $changeLog->targetQuestion?->position ?? '-',
                        'option_position' => $changeLog->targetOption?->position ?? '-',
                        'before_value' => $changeLog->before_value,
                        'after_value' => $changeLog->after_value,
                    ];
                })
                ->values(),

            'review_round' => $this->roundSummary($history->reviewRound),
        ];
    }

    private function needsRevisionDetails($history): array
    {
        $round = $history->reviewRound;
        $revisionRequests = $round?->testRevisionRequests ?? collect();

        $reviewer = $round?->reviewerUser ?: $history->changedByUser;

        return [
            'actor' => $this->userSummary($reviewer , withRole: true),

            'decision_at' => DateProcessor::fromTimestamp($round->decided_at)
                          ?? DateProcessor::fromTimestamp($history->created_at),

            'revision_requests_count' => $revisionRequests->count(),

            'revision_requests' => $revisionRequests
                ->values()
                ->map(function ($revisionRequest, int $index) {
                    return [
                        'revision_no' => $index + 1,
                        'revision_type' => $revisionRequest->revision_type->value,
                        'question_position' => $revisionRequest->targetQuestion?->position ?? '-',
                        'option_position' => $revisionRequest->targetOption?->position ?? '-',
                        'problem_note' => $revisionRequest->problem_note,
                    ];
                })
                ->values(),

            'review_round' => $this->roundSummary($round),
        ];
    }

    private function newDetails($history): array
    {
        return [
            'reason' => $history->note,
            'decision_at' => DateProcessor::fromTimestamp($history->created_at),
        ];
    }

    private function userSummary($user, bool $withRole = false): ?array
    {
        if (! $user) {
            return null;
        }

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => ImageProcessor::urlOrDefault($user->userProfile?->avatar_path, 'defaults/default-avatar.svg', $user->userProfile?->avatar_disk),
        ];

        if ($withRole) {
            $data['role'] = $user->role?->name;
        }

        return $data;
    }

    private function roundSummary($round): ?array
    {
        if (! $round) {
            return null;
        }

        return [
            'id' => $round->id,
            'round_no' => (int) $round->round_no,
            'trigger_type' => $round->trigger_type->value,
            'decision' => $round->decision->value,
            'based_on_approval_version' => (int) ($round->based_on_approval_version ?? 0),
            'started_at' => DateProcessor::fromTimestamp($round->started_at),
            'decided_at' => DateProcessor::fromTimestamp($round->decided_at),
        ];
    }
}
