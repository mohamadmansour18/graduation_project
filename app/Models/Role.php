<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Enums\SystemRole;

class Role extends Model
{

    protected $table = 'roles';

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'name' => SystemRole::class,
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
