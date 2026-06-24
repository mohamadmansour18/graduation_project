<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardLibraryMaterialReportsResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $reportsPaginator = $this->resource['reports_paginator'];
        $reasonCounters = $this->resource['reason_counters'];

        return [
            'current_approval_version' => (int) $this->resource['approval_version'],

            'statistics' => [
                'total_reports_count' => (int) $reasonCounters->sum('reporters_count'),

                'reports_by_reason' => $reasonCounters
                    ->map(fn ($counter) => [
                        'reason' => $counter->reason,
                        'reports_count' => (int) $counter->reporters_count,
                    ])
                    ->values()
                    ->toArray(),
            ],

            'reports' => collect($reportsPaginator->items())
                ->map(fn ($report) => [
                    'id' => $report->id,

                    'reporter' => [
                        'id' => $report->user?->id,
                        'name' => $report->user?->name,
                        'avatar_url' => ImageProcessor::urlOrDefault(
                            $report->user?->userProfile?->avatar_path,
                            'defaults/default-avatar.svg',
                            $report->user?->userProfile?->avatar_disk,
                        ),
                        'is_academically_verified' => (bool) $report->reporter?->is_academically_verified,
                    ],

                    'reason' => $report->reason,
                    'description' => $report->description ?? null,
                    'reported_at' => $report->reported_at
                        ? DateProcessor::fromTimestamp($report->reported_at)
                        : DateProcessor::fromTimestamp($report->created_at),
                ])
                ->values()
                ->toArray(),

            'meta' => [
                'per_page' => $reportsPaginator->perPage(),
                'next_cursor' => optional($reportsPaginator->nextCursor())->encode(),
                'previous_cursor' => optional($reportsPaginator->previousCursor())->encode(),
                'has_more_pages' => $reportsPaginator->hasMorePages(),
            ],
        ];
    }
}
