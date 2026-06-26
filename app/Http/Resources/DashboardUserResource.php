<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'avatar' => ImageProcessor::urlOrDefault(
                $this->avatar_path,
                'defaults/default-avatar.svg',
                $this->avatar_disk
            ),

            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?? '-',
            'governorate' => $this->governorate ?? '-',
            'gender' => $this->gender,

            'account_status' => $this->is_banned
                ? 'حساب محظور'
                : 'حساب فعال',

            'last_login_at' => DateProcessor::fromTimestamp($this->last_login_at) ?? "لم يسجل دخول حتى الان",
        ];
    }
}
