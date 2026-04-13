<?php

namespace App\Enums;

enum LibraryMaterialReviewStatus: string
{
    case New = 'new';
    case Approved = 'approved';
    case Deleted = 'deleted';
    case Reported = 'reported';
}
