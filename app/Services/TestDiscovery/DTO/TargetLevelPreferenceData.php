<?php

namespace App\Services\TestDiscovery\DTO;

final class TargetLevelPreferenceData
{
    public function __construct(
        public readonly array $primaryLevels = [],          #اقرب مستويات للمستخدم
        public readonly array $secondaryLevels = [],        # مستويات قريبة لكن أقل دقة
        public readonly array $broadLevels = [],            # مستويات عامة جدا
        public readonly string $confidence = 'low',         #درجة الثقة في هذا التحليل
        public readonly string $reason = 'unknown',         # سبب القرار مفيد جدا في ال debug مستقبلا
    )
    {}

    /**
     * هذا التابع يجمع كل المستويات الممكنة في قائمة واحدة
     * مع حذف التكرار وإعادة ترتيب المفاتيح
     *
     *: سنحتاجه لاحقًا في
     * - candidate selection
     * - fallback
     * - ranking
     */
    public function allLevels(): array
    {
        return array_values(array_unique(array_merge(
            $this->primaryLevels,
            $this->secondaryLevels,
            $this->broadLevels,
        )));
    }

    public function isPrimary(string $targetLevel): bool
    {
        return in_array($targetLevel, $this->primaryLevels, true);
    }

    public function isSecondary(string $targetLevel): bool
    {
        return in_array($targetLevel, $this->secondaryLevels, true);
    }

    public function isBroad(string $targetLevel): bool
    {
        return in_array($targetLevel, $this->broadLevels, true);
    }
}
