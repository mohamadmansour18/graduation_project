<?php

namespace App\Repositories\Settings;

use App\Models\UserSetting;

class SettingsRepository
{
    public function setTaskRemindersStatus(int $userId, bool $enabled): UserSetting
    {
        return UserSetting::query()->updateOrCreate(
            ['user_id' => $userId],
            ['task_reminders_enabled' => $enabled]
        );
    }

    public function updateDateTimeSettings(int $userId, string $weekStartsOn, string $timeFormat): UserSetting
    {
        return UserSetting::query()->updateOrCreate(
            ['user_id' => $userId],
            [
                'week_starts_on' => $weekStartsOn,
                'time_format' => $timeFormat,
            ]
        );
    }

    public function updateThemeMode(int $userId, string $themeMode): UserSetting
    {
        return UserSetting::query()->updateOrCreate(
            ['user_id' => $userId],
            ['theme_mode' => $themeMode]
        );
    }

    public function getSettingsForUser(int $userId): ?UserSetting
    {
        return UserSetting::query()
            ->where('user_id', $userId)
            ->first([
                'id',
                'user_id',
                'task_reminders_enabled',
                'week_starts_on',
                'time_format',
                'theme_mode',
            ]);
    }
}
