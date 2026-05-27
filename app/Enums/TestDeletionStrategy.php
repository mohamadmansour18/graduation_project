<?php

namespace App\Enums;

enum TestDeletionStrategy:string
{
    case ForceDelete = 'force_delete';
    case SoftDelete = 'soft_delete';
}
