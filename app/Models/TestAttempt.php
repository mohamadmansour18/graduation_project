<?php

namespace App\Models;

use App\Enums\TestAttemptsMode;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestAttempt extends Model
{

    protected $table = 'test_attempts';

    protected $fillable = [
        'test_id',
        'user_id',
        'mode',
    ];

    protected $casts = [
        'mode' => TestAttemptsMode::class,
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
