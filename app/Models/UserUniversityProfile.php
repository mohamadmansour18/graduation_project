<?php

namespace App\Models;

use App\Enums\UniversityDepartment;
use App\Enums\UniversityName;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserUniversityProfile extends Model
{

    protected $table = 'user_university_profiles';

    protected $fillable = [
        'user_id',
        'university_name',
        'university_year',
        'department'
    ];

    protected $casts = [
        'university_year' => 'integer',
        'university_name' => UniversityName::class,
        'department' => UniversityDepartment::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
