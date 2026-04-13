<?php

namespace App\Enums;

enum TaskStatus:string
{
    case TODO = 'للقيام';
    case In_Progress = 'قيد المعالجة';
    case Completed = 'تم انجازها';
    case Missed = 'فائتة';
}
