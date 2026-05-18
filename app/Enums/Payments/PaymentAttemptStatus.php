<?php

namespace App\Enums\Payments;

enum PaymentAttemptStatus:string
{
    //أنشأنا محاولة دفع، ولم نعرف نتيجتها بعد
    case Pending = 'معلقة';

    //هذه المحاولة نجحت.
    case Succeeded = 'ناجحة';

    //حصل فشل واضح في الدفع
    case Failed = 'فاشلة';

    //جلسة Stripe انتهت صلاحيتها
    case Expired = 'منتهية';

    //المستخدم أو النظام ألغى المحاولة قد نستخدمها لاحقا اذا بنينا زر الغاء الدفع
    case Cancelled = 'ملغاة';
}
