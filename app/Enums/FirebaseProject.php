<?php

namespace App\Enums;

enum FirebaseProject: string
{
    case Mobile = 'mobile';
    case Web = 'web';

    public static function values(): array
    {
        return array_map(
            fn (FirebaseProject $project) => $project->value,
            self::cases()
        );
    }
}
