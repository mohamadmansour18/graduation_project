<?php

namespace App\Enums;

enum LibraryMaterialReviewStatus: string
{
    case New = 'مسودة';
    case Approved = 'تم الموافقة عليه';
    case Deleted = 'تم حذفه';
    case Reported = 'مبلغ عنه';
}
