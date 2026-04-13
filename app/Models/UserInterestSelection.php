<?php

namespace App\Models;

use App\Models\Interest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserInterestSelection extends Model
{

    protected $table = 'user_interest_selections';

    protected $fillable = [
        'user_id',
        'interest_id',
        'slot_no',
    ];

    protected $casts = [
        'slot_no' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function interest(): BelongsTo
    {
        return $this->belongsTo(Interest::class, 'interest_id');
    }
}
