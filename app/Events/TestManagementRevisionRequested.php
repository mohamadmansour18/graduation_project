<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestManagementRevisionRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $testId,
        public readonly int $reviewRoundId,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly string $changedDate,
        public readonly string $changedAt,
        public readonly bool $statusChanged,
        public readonly int $createdRevisionRequestsCount,
        public readonly int $totalRevisionRequestsCount
    )
    {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dashboard.test-management'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'test.revision.requested';
    }

    public function broadcastWith(): array
    {
        return [
            'test_id' => $this->testId,
            'review_round_id' => $this->reviewRoundId,
            'from_status' => $this->fromStatus,
            'to_status' => $this->toStatus,
            'changed_date' => $this->changedDate,
            'changed_at' => $this->changedAt,
            'status_changed' => $this->statusChanged,
            'created_revision_requests_count' => $this->createdRevisionRequestsCount,
            'total_revision_requests_count' => $this->totalRevisionRequestsCount,
        ];
    }
}
