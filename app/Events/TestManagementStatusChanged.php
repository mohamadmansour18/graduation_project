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
use Illuminate\Support\Facades\Log;

class TestManagementStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $testId,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly string $changedDate,
        public readonly string $changedAt,
        public readonly int $currentApprovalVersion
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dashboard.test-management'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'test.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'test_id' => $this->testId,
            'from_status' => $this->fromStatus,
            'to_status' => $this->toStatus,
            'changed_date' => $this->changedDate,
            'changed_at' => $this->changedAt,
            'current_approval_version' => $this->currentApprovalVersion,
        ];
    }
}
