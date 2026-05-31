<?php

namespace App\Http\Resources\Tests;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UpdateTestResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'test_id' => $this['test_id'],
            'review_status' => $this['review_status'],
            'requires_review' => $this['requires_review'],
            'status_changed' => $this['status_changed'],
            'message' => $this['message'],
        ];
    }
}
