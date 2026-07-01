<?php

namespace App\Enums;

enum NotificationPlatform: string
{
    case Mobile = 'mobile';
    case Web = 'web';

    public static function values(): array
    {
        return array_map(
            fn (NotificationPlatform $platform) => $platform->value,
            self::cases()
        );
    }
}
