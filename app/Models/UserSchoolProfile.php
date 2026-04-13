<?php

namespace App\Models;

use App\Enums\SchoolStage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserSchoolProfile extends Model
{

    protected $table = 'user_school_profiles';

    protected $fillable = [
        'user_id',
        'school_stage',
    ];

    protected $casts = [
        'school_stage' => SchoolStage::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
