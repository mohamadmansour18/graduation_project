<?php

namespace App\Http\Resources;

use App\Enums\BanType;
use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBanHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'serial_no' => $this->serial_no,

            'reason' => $this->reason,

            'imposed_by' => $this->imposedByUser ? [
                'name' => $this->imposedByUser->name,

                'avatar' => ImageProcessor::urlOrDefault(
                    $this->imposedByUser?->userProfile?->avatar_path,
                    'defaults/User_Default.svg'
                ),

                'role' => $this->imposedByUser?->role?->name,
            ] : null,

            'starts_at' => DateProcessor::fromTimestamp($this->starts_at),

            'ends_at' => $this->ban_type->value === BanType::Permanent->value
                ? 'غير محدد'
                : DateProcessor::fromTimestamp($this->ends_at),

            'is_active_now' => $this->isActiveNow(),
        ];
    }

    private function isActiveNow(): bool
    {
        if ($this->lifted_at !== null) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ban_type->value === BanType::Permanent->value) {
            return true;
        }

        return $this->ends_at !== null && $this->ends_at->isFuture();
    }
}
