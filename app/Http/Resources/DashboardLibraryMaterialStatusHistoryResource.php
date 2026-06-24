<?php

namespace App\Http\Resources;

use App\Enums\LibraryMaterialReviewStatus;
use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardLibraryMaterialStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $material = $this->resource['material'];
        $histories = $this->resource['histories'];

        return [
            'current_approval_version' => (int) $material->current_approval_version,

            'status_history' => $histories
                ->map(fn ($history) => [
                    'id' => $history->id,
                    'status' => $history->to_status->value,
                    'entered_at' => DateProcessor::fromTimestamp($history->created_at),
                    'details' => $this->buildDetails($history),
                ])
                ->values()
                ->toArray(),
        ];
    }

    private function buildDetails($history): array
    {
        $status = $history->to_status->value;

        return match ($status) {
            LibraryMaterialReviewStatus::Deleted->value => [
                'actor' => $this->userPayload($history->changedByUser),
                'reason' => $history->note,
                'decision_at' => DateProcessor::fromTimestamp($history->created_at),
            ],

            LibraryMaterialReviewStatus::Reported->value => [
                'reason' => $history->note,
                'decision_at' => DateProcessor::fromTimestamp($history->created_at),
            ],

            LibraryMaterialReviewStatus::Approved->value => [
                'actor' => $this->userPayload($history->changedByUser, withRole: true),
                'decision_at' => DateProcessor::fromTimestamp($history->created_at),
            ],

            LibraryMaterialReviewStatus::New->value => [
                'note' => $history->note,
                'decision_at' => DateProcessor::fromTimestamp($history->created_at),
            ],

            default => [
                'note' => $history->note,
            ],
        };
    }

    private function userPayload($user, bool $withRole = false): ?array
    {
        if (! $user) {
            return null;
        }

        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => ImageProcessor::urlOrDefault(
                $user->userProfile?->avatar_path,
                'defaults/default-avatar.svg',
                $user->userProfile?->avatar_disk,
            ),
        ];

        if ($withRole) {
            $payload['role'] = $user->role?->name;
        }

        return $payload;
    }
}
