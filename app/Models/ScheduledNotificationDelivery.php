<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class ScheduledNotificationDelivery extends Model
{
    use MassPrunable;

    protected $fillable = [
        'user_id',
        'delivery_key',
        'notification_type',
        'deliver_at',
        'dispatched_at',
        'context',
    ];

    protected $casts = [
        'deliver_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'context' => 'array',
    ];

    public function prunable(): Builder
    {
        $dispatchedRetentionDays = (int) config(
            'prunable.prune.scheduled_deliveries_after_days',
            14
        );

        $staleRetentionDays = (int) config(
            'prunable.prune.stale_scheduled_deliveries_after_days',
            30
        );

        return static::query()
            ->where(function (Builder $query) use ($dispatchedRetentionDays) {
                $query
                    ->whereNotNull('dispatched_at')
                    ->where('dispatched_at', '<=', now()->subDays($dispatchedRetentionDays));
            })
            ->orWhere(function (Builder $query) use ($staleRetentionDays) {
                $query
                    ->whereNull('dispatched_at')
                    ->where('deliver_at', '<=', now()->subDays($staleRetentionDays));
            });
    }
}
