<?php

namespace App\Enums;

enum EducationLevel : string
{
    case School = 'مدرسة';
    case University = 'جامعة';
    case Master = 'ماجستير';
    case PhD = 'دكتوراه';
    case Graduate = 'خريج';
}
