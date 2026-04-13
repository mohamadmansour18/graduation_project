<?php

namespace App\Models;

use App\Enums\TestType;
use App\Enums\VisibilityType;
use App\Models\TestFolderBookmark;
use App\Models\TestFolderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestFolder extends Model
{

    protected $table = 'test_folder';

    protected $fillable = [
        'creator_id',
        'name',
        'color_code',
        'visibility_type',
        'contained_test_type',
        'tests_count',
        'published_at',
    ];

    protected $casts = [
        'visibility_type' => VisibilityType::class,
        'contained_test_type' => TestType::class,
        'tests_count' => 'integer',
        'published_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function testFolderBookmarks(): HasMany
    {
        return $this->hasMany(TestFolderBookmark::class, 'test_folder_id');
    }

    public function testFolderItems(): HasMany
    {
        return $this->hasMany(TestFolderItem::class, 'test_folder_id');
    }
}
