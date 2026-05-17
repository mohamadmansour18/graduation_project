<?php

namespace App\Enums\Payments;

enum PaymentStatus: string
{
    case Pending = 'معلقة';
    case Paid = 'مدفوعة';
    case Failed = 'فاشلة';
    case Cancelled = 'ملغاة';
    case Refunded = 'مردودة';
}
