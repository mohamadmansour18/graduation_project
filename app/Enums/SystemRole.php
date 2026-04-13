<?php

namespace App\Enums;

enum SystemRole : string
{
    case Owner = 'مالك التطبيق';
    case Supervisor = 'مشرف';
    case User = 'مستخدم';
}
