<?php

namespace App\Http\Controllers\V1\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateDateTimeSettingsRequest;
use App\Http\Requests\Settings\UpdateThemeModeRequest;
use App\Models\StudyPlan;
use App\Services\Settings\SettingsService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SettingsService $settingsService
    )
    {}

    public function enableTaskReminders(): JsonResponse
    {
        $this->settingsService->enableTaskReminders(
            userId: \Auth::id()
        );

        return $this->successResponse(
            message: 'تم تفعيل تذكيرات المهام بنجاح'
        );
    }

    public function disableTaskReminders(): JsonResponse
    {
        $this->settingsService->disableTaskReminders(
            userId: \Auth::id()
        );

        return $this->successResponse(
            message: 'تم إيقاف تذكيرات المهام بنجاح'
        );
    }

    public function updateDateTimeSettings(UpdateDateTimeSettingsRequest $request,): JsonResponse
    {
        $this->settingsService->updateDateTimeSettings(
            userId: $request->user()->id,
            weekStartsOn: $request->validated('week_starts_on'),
            timeFormat: $request->validated('time_format')
        );

        return $this->successResponse(
            message: 'تم تحديث إعدادات التاريخ والوقت بنجاح'
        );
    }

    public function updateThemeMode(UpdateThemeModeRequest $request,): JsonResponse
    {
        $this->settingsService->updateThemeMode(
            userId: $request->user()->id,
            themeMode: $request->validated('theme_mode')
        );

        return $this->successResponse(
            message: 'تم تحديث الثيم بنجاح'
        );
    }

    public function show(): JsonResponse
    {
        $settings = $this->settingsService->getUserSettings(
            userId: \Auth::id()
        );

        return $this->dataResponse(
            data: $settings,
            title: '! تم جلب إعدادات المستخدم بنجاح'
        );
    }
}
