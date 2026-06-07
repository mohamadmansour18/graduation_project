<?php

namespace App\Enums;

enum LibraryReportReason:string
{
    case OFFENSIVE_CONTENT = 'محتوى مسيء (أخلاقيا - دينيا - اجتماعيا)';
    case SCIENTIFIC_ERRORS = 'يوجد أخطاء علمية داخل المحتوى';
    case EMPTY_CONTENT = 'محتوى فارغ لايوجد به معلومات';
}
