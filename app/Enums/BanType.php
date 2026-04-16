<?php

namespace App\Enums;

enum BanType: string
{
    case Temporary = 'حظر مؤقت';
    case Permanent = 'حظر دائم';
}
