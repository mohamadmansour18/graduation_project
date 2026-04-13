<?php

namespace App\Enums;

enum StudyTaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Missed = 'missed';
}
