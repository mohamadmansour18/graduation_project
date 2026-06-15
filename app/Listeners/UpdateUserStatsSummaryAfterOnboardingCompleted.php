<?php

namespace App\Listeners;

use App\Enums\Gender;
use App\Enums\SystemRole;
use App\Events\UserOnboardingCompleted;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateUserStatsSummaryAfterOnboardingCompleted implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 2;
    public bool $afterCommit = true;


    public function handle(UserOnboardingCompleted $event): void
    {
        $this->refreshStatsSummary($event->year);

        Log::channel('daily')->info('User stats summary refreshed after onboarding completed', [
            'user_id' => $event->userId,
            'year' => $event->year,
            'gender' => $event->gender,
        ]);
    }

    private function refreshStatsSummary(int $year): void
    {
        [$startDate, $endDate] = $this->yearRange($year);

        $stats = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('roles.name', SystemRole::Mobile_User->value)
            ->whereNotNull('users.onboarding_completed_at')
            ->whereBetween('users.onboarding_completed_at', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as total_completed_mobile_users')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN users.gender = ? THEN 1 ELSE 0 END), 0) as male_completed_mobile_users',
                [Gender::Male->value]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN users.gender = ? THEN 1 ELSE 0 END), 0) as female_completed_mobile_users',
                [Gender::Female->value]
            )
            ->first();

        $now = now();

        DB::table('user_stats_summary')->upsert([
            [
                'year' => $year,
                'total_completed_mobile_users' => (int) $stats->total_completed_mobile_users,
                'male_completed_mobile_users' => (int) $stats->male_completed_mobile_users,
                'female_completed_mobile_users' => (int) $stats->female_completed_mobile_users,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['year'], [
            'total_completed_mobile_users',
            'male_completed_mobile_users',
            'female_completed_mobile_users',
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

    public function failed(UserOnboardingCompleted $event, Throwable $exception): void
    {
        Log::channel('errors')->error('Failed to update user stats summary after onboarding completed', [
            'user_id' => $event->userId,
            'year' => $event->year,
            'gender' => $event->gender,
            'message' => $exception->getMessage(),
        ]);
    }
}
