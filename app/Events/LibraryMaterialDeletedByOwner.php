<?php

namespace App\Events;

use Carbon\CarbonInterface;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LibraryMaterialDeletedByOwner
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $materialId,
        public readonly int $deletedByUserId,
        public readonly CarbonInterface $materialCreatedAt,
        public readonly int $materialLikesCount,
    )
    {}

}
