<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

class PrunableDatabaseNotification extends DatabaseNotification
{
    use MassPrunable;

    protected $table = 'notifications';

    public $incrementing = false;

    protected $keyType = 'string';

    public function prunable(): Builder
    {
        $retentionDays = (int) config(
            'prunable.prune.notifications_after_days',
            30
        );

        return static::query()
            ->whereNotNull('read_at')
            ->where('created_at', '<=', now()->subDays($retentionDays));
    }
}
