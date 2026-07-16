<?php

namespace App\Listeners;

use App\Enums\DiscoverySource;
use App\Events\UserDiscoverySourceSaved;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateUserStatsByDiscoverySource implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 2;
    public bool $afterCommit = true;
    public array $backoff = [5, 10];
    public int $timeout = 60;
    public string $queue = 'light';

    public function handle(UserDiscoverySourceSaved $event): void
    {
        $sources = collect([
            $event->discoverySource,
            $event->oldDiscoverySource,
        ])
            ->filter()
            ->unique()
            ->values();

        foreach ($sources as $source) {
            $this->refreshStats(
                year: $event->year,
                discoverySource: $source,
            );
        }

        Log::channel('daily')->info('User stats by discovery source refreshed', [
            'user_id' => $event->userId,
            'year' => $event->year,
            'discovery_source' => $event->discoverySource,
            'old_discovery_source' => $event->oldDiscoverySource,
        ]);
    }

    private function refreshStats(int $year, string $discoverySource): void
    {
        [$startDate, $endDate] = $this->yearRange($year);

        $count = DB::table('user_onboarding_profiles')
            ->where('discovery_source', $discoverySource)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $now = now();

        DB::table('user_stats_by_discovery_source')->upsert([
            [
                'year' => $year,
                'discovery_source' => $discoverySource,
                'completed_mobile_users_count' => $count,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['year', 'discovery_source'], [
            'completed_mobile_users_count',
            'updated_at',
        ]);
    }

    private function yearRange(int $year): array
    {
        $start = CarbonImmutable::create($year, 1, 1, 0, 0, 0);
        $end = $start->endOfYear();

        return [
            $start->toDateTimeString(),
            $end->toDateTimeString(),
        ];
    }

}
