<?php

namespace App\Enums;

enum TestReviewStatus: string
{
    case New = 'جديد';
    case NeedsRevision = 'يحتاج تعديل';
    case UnderReview = 'قيد المراجعة';
    case Approved = 'تم الموافقة عليه';
    case Deleted = 'تم حذفه';
    case Reported = 'مبلغ عنه';
}
