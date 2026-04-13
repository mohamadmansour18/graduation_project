<?php

namespace App\Enums;

enum WeekStartsOn:string
{
    case Sunday = "الأحد";
    case Monday = "الإتنين";
    case Tuesday = "الثلاثاء";
    case Wednesday = "الأربعاء";
    case Thursday = "الخميس";
    case Friday = "الجمعة";
    case Saturday = "السبت";
}
