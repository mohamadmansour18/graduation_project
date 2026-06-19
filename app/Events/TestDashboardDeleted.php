<?php

namespace App\Events;

use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestDashboardDeleted implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $testId,
        public readonly int $creatorUserId,
        public readonly CarbonImmutable $deletedAt,
        public readonly ?int $publishedYear,
        public readonly ?int $publishedMonth,
        public readonly bool $shouldDecreasePublishCounters
    )
    {}

}
