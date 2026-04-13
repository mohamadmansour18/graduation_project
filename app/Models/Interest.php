<?php

namespace App\Models;

use App\Models\InterestCategory;
use App\Models\LibraryMaterialInterestSelection;
use App\Models\TestIntersetSelection;
use App\Models\UserInterestSelection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Interest extends Model
{

    protected $table = 'interests';

    protected $fillable = [
        'interest_category_id',
        'name',
    ];


    public function interestCategory(): BelongsTo
    {
        return $this->belongsTo(InterestCategory::class, 'interest_category_id');
    }

    public function userInterestSelections(): HasMany
    {
        return $this->hasMany(UserInterestSelection::class, 'interest_id');
    }

    public function testIntersetSelections(): HasMany
    {
        return $this->hasMany(TestIntersetSelection::class, 'interest_id');
    }

    public function libraryMaterialInterestSelections(): HasMany
    {
        return $this->hasMany(LibraryMaterialInterestSelection::class, 'interest_id');
    }
}
