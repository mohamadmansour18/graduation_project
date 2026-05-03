<?php

namespace App\Enums;

enum TestSearchScope : string
{
    case ALL = 'all' ;
    case MINE = 'mine' ;
    case OTHERS = 'others' ;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
