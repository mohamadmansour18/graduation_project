<?php

namespace App\Models;

use App\Models\TestQuestion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestQuestionOption extends Model
{

    protected $table = 'test_question_options';

    protected $fillable = [
        'test_question_id',
        'position',
        'option_text',
        'is_correct',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_correct' => 'boolean',
    ];

    public function testQuestion(): BelongsTo
    {
        return $this->belongsTo(TestQuestion::class, 'test_question_id');
    }
}
