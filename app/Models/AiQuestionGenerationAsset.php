<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiQuestionGenerationAsset extends Model
{
    protected $fillable = [
        'ai_question_generation_id',
        'storage_disk',
        'storage_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'sha256_hash',
        'position',
        'deleted_from_storage_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'position' => 'integer',
    ];

    public function generationRequest(): BelongsTo
    {
        return $this->belongsTo(
            AiQuestionGenerationRequest::class,
            'ai_question_generation_id'
        );
    }

    public function isDeletedFromStorage(): bool
    {
        return $this->deleted_from_storage_at !== null;
    }
}
