<?php

namespace App\Enums;

enum TestAttemptsMode:string
{
    case MCQ = 'mcq';
    case Flash_Card = 'flashCard';
    case Challenge = 'challenge';
}
