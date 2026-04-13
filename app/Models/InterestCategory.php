<?php

namespace App\Models;

use App\Models\Interest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InterestCategory extends Model
{

    protected $table = 'interest_categories';

    protected $fillable = [
        'title',
    ];

    public function interests(): HasMany
    {
        return $this->hasMany(Interest::class, 'interest_category_id');
    }
}
