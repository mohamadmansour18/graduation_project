<?php

namespace App\Models;

use App\Enums\ThemeMode;
use App\Enums\TimeFormat;
use App\Enums\WeekStartsOn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserSetting extends Model
{

    protected $table = 'user_settings';

    protected $fillable = [
        'user_id',
        'task_reminders_enabled',
        'week_starts_on',
        'time_format',
        'theme_mode',
    ];

    protected $casts = [
        'task_reminders_enabled' => 'boolean',
        'week_starts_on' => WeekStartsOn::class,
        'time_format' => TimeFormat::class,
        'theme_mode' => ThemeMode::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
