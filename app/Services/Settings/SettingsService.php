<?php

namespace App\Services\Settings;

use App\Http\Resources\UserSettingsResource;
use App\Models\UserSetting;
use App\Repositories\Settings\SettingsRepository;

class SettingsService
{
    public function __construct(
        private readonly SettingsRepository $settingsRepository
    )
    {}

    public function enableTaskReminders(int $userId): void
    {
        $this->settingsRepository->setTaskRemindersStatus(
            userId: $userId,
            enabled: true
        );
    }

    public function disableTaskReminders(int $userId): void
    {
        $this->settingsRepository->setTaskRemindersStatus(
            userId: $userId,
            enabled: false
        );
    }

    public function updateDateTimeSettings(int $userId, string $weekStartsOn, string $timeFormat): void
    {
        $this->settingsRepository->updateDateTimeSettings(
            userId: $userId,
            weekStartsOn: $weekStartsOn,
            timeFormat: $timeFormat
        );
    }

    public function updateThemeMode(int $userId, string $themeMode): void
    {
        $this->settingsRepository->updateThemeMode(
            userId: $userId,
            themeMode: $themeMode
        );
    }

    public function getUserSettings(int $userId): UserSettingsResource
    {
        $settings = $this->settingsRepository->getSettingsForUser($userId);

        if (! $settings) {
            $settings = new UserSetting([
                'task_reminders_enabled' => false,
                'week_starts_on' => 'السبت',
                'time_format' => '24 ساعة',
                'theme_mode' => 'نهاري',
            ]);
        }

        return new UserSettingsResource($settings);
    }
}
