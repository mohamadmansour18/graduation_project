<?php

namespace App\Enums;

enum PurposeOTP : string
{
    case Email_Verification = 'Email_Verification';
    case Password_Reset = 'Password_Reset';
}
