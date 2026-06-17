<?php

namespace App\Events;

use App\Enums\TestDeletionStrategy;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestDeleted implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $testId,
        public readonly int $creatorUserId,
        public readonly int $deletedByUserId,
        public readonly ?string $publishedAt,
        public readonly bool $wasPublished,
        public readonly int $likesCount,
        public readonly int $bookmarksCount,
        public readonly int $reviewsCount,
        public readonly int $downloadsCount,
        public readonly float $averageRating,
        public readonly TestDeletionStrategy $deletionStrategy,
    ) {}

}
