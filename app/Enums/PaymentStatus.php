<?php

namespace App\Enums;

enum PaymentStatus:string
{
    case Pending = 'معلقة';
    case Paid = 'مدفوعة';
    case Failed = 'فاشلة';
    case Refunder = 'مردودة';
}
