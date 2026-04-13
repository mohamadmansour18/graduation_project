<?php

namespace App\Models;

use App\Models\Test;
use App\Models\TestFolder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestFolderItem extends Model
{

    protected $table = 'test_folder_item';

    protected $fillable = [
        'test_folder_id',
        'test_id',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function testFolder(): BelongsTo
    {
        return $this->belongsTo(TestFolder::class, 'test_folder_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }
}
