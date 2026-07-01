<?php

namespace App\Notifications;

use App\DTOs\Notifications\NotificationPayload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppDatabaseNotification extends Notification
{
    use Queueable;


    public function __construct(
        private readonly NotificationPayload $payload,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload->toDatabaseArray();
    }

    public function databaseType(object $notifiable): string
    {
        return $this->payload->metadata['type'] ?? 'general';
    }
}
