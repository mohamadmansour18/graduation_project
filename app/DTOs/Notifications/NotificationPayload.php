<?php

namespace App\DTOs\Notifications;
use App\Helpers\ArabicTextHelper;
use Illuminate\Support\Str;
use InvalidArgumentException;
class NotificationPayload
{
    public readonly string $title;
    public readonly string $body;
    public readonly array $metadata;
    public readonly string $notificationKey;

    public function __construct(
        string $title,
        string $body,
        array $metadata = [],
        ?string $notificationKey = null,
    ) {
        if (trim($title) === '') {
            throw new InvalidArgumentException('Notification title cannot be empty.');
        }

        if (trim($body) === '') {
            throw new InvalidArgumentException('Notification body cannot be empty.');
        }

        $this->title = $title;
        $this->body = $body;
        $this->metadata = $metadata;
        $this->notificationKey = $notificationKey ?: (string) Str::uuid();
    }

    public static function make(string $title, string $body, array $metadata = []): self
    {
        return new self(
            title: ArabicTextHelper::fixBidi($title),
            body: ArabicTextHelper::fixBidi($body),
            metadata: $metadata,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            body: $data['body'],
            metadata: $data['metadata'] ?? [],
            notificationKey: $data['notification_key'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'metadata' => $this->metadata,
            'notification_key' => $this->notificationKey,
        ];
    }

    public function toDatabaseArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'metadata' => $this->metadataForStorage(),
            'notification_key' => $this->notificationKey,
        ];
    }

    public function toFcmDataArray(): array
    {
        $metadata = $this->metadataForStorage();

        return [
            'title' => $this->title,
            'body' => $this->body,
            'notification_key' => $this->notificationKey,
            'metadata' => json_encode(
                $metadata,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
        ];
    }

    private function metadataForStorage(): array
    {
        return array_merge($this->metadata, [
            'notification_key' => $this->notificationKey,
        ]);
    }
}
