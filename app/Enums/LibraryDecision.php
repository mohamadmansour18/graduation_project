<?php

namespace App\Enums;

enum LibraryDecision:string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Deleted = 'deleted';
}
