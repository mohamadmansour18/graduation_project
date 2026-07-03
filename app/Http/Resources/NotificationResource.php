<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->notificationData();

        $title = (string) ($data['title'] ?? '');
        $body = (string) ($data['body'] ?? '');

        $metadata = $this->metadata($data);
        $presentation = $metadata['presentation'] ?? [];

        $mode = $presentation['mode'] ?? 'system';

        if (! in_array($mode, ['user', 'system'], true)) {
            $mode = 'system';
        }

        $actor = $metadata['actor'] ?? null;

        $displayTitle = $this->displayTitle(
            mode: $mode,
            defaultTitle: $title,
            actor: is_array($actor) ? $actor : null,
        );

        return [
            'id' => (string) $this->id,
            'mode' => $mode,
            'image' => $mode === 'user'
                ? data_get($metadata, 'actor.avatar_url')
                : null,

            'floor_color' => $mode === 'system'
                ? data_get($metadata, 'presentation.floor_color')
                : null,

            'icon' => $mode === 'system'
                ? data_get($metadata, 'presentation.icon')
                : null,

            'title' => $displayTitle,
            'body' => $body,

            'sent_at' => DateProcessor::fromTimestamp($this->created_at),
            'is_read' => ! is_null($this->read_at),

            'metadata' => $metadata,
        ];
    }

    private function notificationData(): array
    {
        $data = $this->data ?? [];

        if (is_array($data)) {
            return $data;
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function metadata(array $data): array
    {
        $metadata = $data['metadata'] ?? [];

        if (! is_array($metadata)) {
            return [];
        }

        return $metadata;
    }

    private function displayTitle(string $mode, string $defaultTitle, ?array $actor): string
    {
        if ($mode === 'user') {
            return (string) ($actor['name'] ?? $defaultTitle);
        }

        return $defaultTitle;
    }
}
