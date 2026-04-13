<?php

namespace App\Models;

use App\Models\TestFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestFolderBookmark extends Model
{

    protected $table = 'test_folder_bookmarks';

    protected $fillable = [
        'test_folder_id',
        'user_id',
    ];

    protected $casts = [
    ];

    public function testFolder(): BelongsTo
    {
        return $this->belongsTo(TestFolder::class, 'test_folder_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
