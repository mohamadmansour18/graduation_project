<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestManagementReportsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reportsPaginator = $this->resource['reports'];

        return [
            'approval_version' => $this->resource['approval_version'],
            'statistics' => [
                'total_reports_count' => (int) $this->resource['statistics']['total_reports_count'],

                'reasons' => $this->resource['statistics']['reasons']
                    ->map(function ($counter) {
                        return [
                            'reason' => $counter->reason->value,
                            'reports_count' => (int) $counter->reporters_count ?? 0,
                        ];
                    })
                    ->values(),
            ],

            'reports' => [
                'items' => $reportsPaginator
                    ->getCollection()
                    ->map(function ($report) {
                        return [
                            'id' => $report->id,

                            'reporter' => [
                                'id' => $report->user?->id,
                                'name' => $report->user?->name,
                                'avatar' => ImageProcessor::urlOrDefault(
                                    $report->user?->userProfile?->avatar_path,
                                    'defaults/default-avatar.svg',
                                    $report->user?->userProfile?->avatar_disk,
                                ),
                                'is_academically_verified' => (bool) ($report->user?->is_academically_verified ?? false),
                            ],

                            'reason' => $report->reason->value,
                            'description' => $report->description ?? null,
                            'reported_at' => DateProcessor::fromTimestamp($report->reported_at)
                                          ?? DateProcessor::fromTimestamp($report->created_at),
                        ];
                    })
                    ->values(),

                'meta' => [
                    'per_page' => $reportsPaginator->perPage(),
                    'next_cursor' => optional($reportsPaginator->nextCursor())->encode(),
                    'previous_cursor' => optional($reportsPaginator->previousCursor())->encode(),
                    'has_more_pages' => $reportsPaginator->hasMorePages(),
                ],
            ],
        ];
    }
}
