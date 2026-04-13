<?php

namespace App\Enums;

enum Status:string
{
    case PENDING = 'معلقة';
    case APPROVED = 'تم الموافقة عليها';
    case REJECTED = 'تم رفضها';
}
