<?php

namespace App\Enums;

enum DiscoverySource : string
{
    case LinkedIn = 'لينكد إن';
    case FaceBook = 'فيسبوك';
    case Instagram = 'إنستاغرام';
    case Friends = 'الإصدقاء';
    case Family = 'العائلة';
    case Other = 'غير ذلك';
}
