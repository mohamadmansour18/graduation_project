<?php

namespace App\Models;

use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestLike extends Model
{

    protected $table = 'test_likes';

    protected $fillable = [
        'test_id',
        'user_id',
    ];

    protected $casts = [
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
