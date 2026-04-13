<?php

namespace App\Enums;

enum BanType: string
{
    case Temporary = 'حظر دائم';
    case Permanent = 'حظر مؤقت';
}
