<?php

namespace App\Enums;

enum Decision:string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Deleted = 'deleted';
    case Needs_Revision = 'needs_revision';
}
