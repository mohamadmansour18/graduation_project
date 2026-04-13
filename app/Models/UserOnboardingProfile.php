<?php

namespace App\Models;

use App\Enums\DiscoverySource;
use App\Enums\EducationLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserOnboardingProfile extends Model
{
    protected $table = 'user_onboarding_profiles';

    protected $fillable = [
        'user_id',
        'discovery_source',
        'education_level',
        'last_completed_step',
    ];

    protected $casts = [
        'last_completed_step' => 'integer',
        'discovery_source' => DiscoverySource::class,
        'education_level' =>  EducationLevel::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
