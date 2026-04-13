<?php

namespace App\Models;

use App\Models\Interest;
use App\Models\Test;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestIntersetSelection extends Model
{

    protected $table = 'test_interset_selections';

    protected $fillable = [
        'test_id',
        'interest_id',
        'slot_no',
    ];

    protected $casts = [
        'slot_no' => 'integer',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function interest(): BelongsTo
    {
        return $this->belongsTo(Interest::class, 'interest_id');
    }
}
