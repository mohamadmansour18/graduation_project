<?php

namespace App\Http\Resources\Tests;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestRevisionRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'revision_type' => $this->revision_type,
            'question_number' => $this->question_position ? "$this->question_position/$this->question_count" : '-',
            'option_number' => $this->question_option_position ?? '-',
            'problem_note' => $this->problem_note,
            'user_has_modified' => (bool) $this->user_has_modified,
        ];
    }
}
