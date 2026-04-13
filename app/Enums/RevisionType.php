<?php

namespace App\Enums;

enum RevisionType:string
{
    case Question = 'لسؤال';
    case Description = 'الوصف';
    case Answer = 'الجواب';
}
