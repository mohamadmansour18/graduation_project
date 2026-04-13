<?php

namespace App\Enums;

enum RepeatPattern:string
{
    case None = 'بدون تكرار';
    case Weekly_1 = 'كل أسبوع';
    case Weekly_2 = 'كل أسبوعين';
    case Weekly_3 = 'كل 3 أسابيع';
    case Weekly_4 = 'كل 4 أسابيع';

}
