<?php

namespace App\Enums;

enum SystemRole : string
{
    case Owner = 'owner';
    case Supervisor = 'supervisor';
    case Mobile_User = 'mobile_user';
}
