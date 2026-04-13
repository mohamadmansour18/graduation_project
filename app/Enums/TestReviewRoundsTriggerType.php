<?php

namespace App\Enums;

enum TestReviewRoundsTriggerType:string
{
    case Initial_Submission = 'Initial Submission';
    case Owner_Resubmission = 'Owner Resubmission';
    case Auto_Reported = 'Auto Reported';
}
