<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AllowedEmailDomain implements ValidationRule
{
    protected array $allowed = [
        'gmail.com' ,
        'hotmail.com' ,
        'yahoo.com' ,
        'outlook.com' ,
        'icloud.com' ,
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = substr(strrchr($value , '@') , 1);
        if(!in_array(strtolower($domain) , $this->allowed , true))
        {
            $fail('نطاق البريد الالكتروني المستخدم غير صحيح');
        }
    }
}
