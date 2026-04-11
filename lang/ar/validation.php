<?php

return [

    /*
    |--------------------------------------------------------------------------
    | رسائل التحقق
    |--------------------------------------------------------------------------
    */

    'accepted' => 'يجب قبول :attribute',
    'active_url' => ':attribute ليس رابطاً صالحاً',
    'after' => 'يجب أن يكون :attribute تاريخاً بعد :date',
    'after_or_equal' => 'يجب أن يكون :attribute تاريخاً مساوياً أو بعد :date',
    'alpha' => 'يجب أن لا يحتوي :attribute إلا على حروف',
    'alpha_dash' => 'يجب أن لا يحتوي :attribute إلا على حروف، أرقام، شرطات وشرطات سفلية',
    'alpha_num' => 'يجب أن يحتوي :attribute على حروف وأرقام فقط',
    'array' => 'يجب أن يكون :attribute مصفوفة',
    'before' => 'يجب أن يكون :attribute تاريخاً قبل :date',
    'before_or_equal' => 'يجب أن يكون :attribute تاريخاً مساوياً أو قبل :date',
    'between' => [
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max',
        'file' => 'يجب أن يكون حجم الملف :attribute بين :min و :max كيلوبايت',
        'string' => 'يجب أن يكون طول النص :attribute بين :min و :max حروف',
        'array' => 'يجب أن يحتوي :attribute على عدد من العناصر بين :min و :max',
    ],
    'boolean' => 'يجب أن تكون قيمة :attribute صحيحة أو خاطئة',
    'confirmed' => 'تأكيد :attribute غير متطابق',
    'date' => ':attribute ليس تاريخاً صالحاً',
    'date_equals' => 'يجب أن يكون :attribute مساوياً للتاريخ :date',
    'date_format' => 'يجب أن يكون :attribute بتنسيق :format',
    'different' => 'يجب أن يكون :attribute و :other مختلفين',
    'digits' => 'يجب أن يحتوي :attribute على :digits أرقام',
    'digits_between' => 'يجب أن يحتوي :attribute على عدد أرقام بين :min و :max',
    'email' => 'يجب أن يكون :attribute بريداً إلكترونياً صالحا',
    'exists' => ':attribute المحدد غير صالح',
    'file' => 'يجب أن يكون :attribute ملفا',
    'filled' => 'يجب تعبئة :attribute',
    'gt' => [
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من :value',
        'file' => 'يجب أن يكون حجم الملف :attribute أكبر من :value كيلوبايت',
        'string' => 'يجب أن يكون طول النص :attribute أكبر من :value حروف',
        'array' => 'يجب أن يحتوي :attribute على أكثر من :value عنصر',
    ],
    'gte' => [
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر أو تساوي :value',
        'file' => 'يجب أن يكون حجم الملف :attribute أكبر أو يساوي :value كيلوبايت',
        'string' => 'يجب أن يكون طول النص :attribute أكبر أو يساوي :value حروف',
        'array' => 'يجب أن يحتوي :attribute على :value عنصر أو أكثر',
    ],
    'image' => 'يجب أن يكون :attribute صورة',
    'integer' => 'يجب أن يكون :attribute عدداً صحيحاً',
    'ip' => 'يجب أن يكون :attribute عنوان IP صالحاً',
    'ipv4' => 'يجب أن يكون :attribute عنوان IPv4 صالحاً',
    'ipv6' => 'يجب أن يكون :attribute عنوان IPv6 صالحاً',
    'json' => 'يجب أن يكون :attribute نص JSON صالح',
    'lt' => [
        'numeric' => 'يجب أن تكون قيمة :attribute أقل من :value',
        'file' => 'يجب أن يكون حجم الملف :attribute أقل من :value كيلوبايت',
        'string' => 'يجب أن يكون طول النص :attribute أقل من :value حروف',
        'array' => 'يجب أن يحتوي :attribute على أقل من :value عنصر',
    ],
    'lte' => [
        'numeric' => 'يجب أن تكون قيمة :attribute أقل أو تساوي :value',
        'file' => 'يجب أن يكون حجم الملف :attribute أقل أو يساوي :value كيلوبايت',
        'string' => 'يجب أن يكون طول النص :attribute أقل أو يساوي :value حروف',
        'array' => 'يجب أن لا يحتوي :attribute على أكثر من :value عنصر',
    ],
    'max' => [
        'numeric' => 'يجب أن لا تكون قيمة :attribute أكبر من :max',
        'file' => 'يجب أن لا يتجاوز حجم الملف :attribute :max كيلوبايت',
        'string' => 'يجب أن لا يتجاوز طول النص :attribute :max حروف',
        'array' => 'يجب أن لا يحتوي :attribute على أكثر من :max عنصر',
    ],
    'min' => [
        'numeric' => 'يجب أن تكون قيمة :attribute على الأقل :min',
        'file' => 'يجب أن يكون حجم الملف :attribute على الأقل :min كيلوبايت',
        'string' => 'يجب أن يكون طول النص :attribute على الأقل :min حروف',
        'array' => 'يجب أن يحتوي :attribute على الأقل :min عناصر',
    ],
    'not_in' => ':attribute المحدد غير صالح ',
    'numeric' => 'يجب أن يكون :attribute رقماً ',
    'present' => 'يجب تقديم :attribute ',
    'regex' => 'تنسيق :attribute غير صالح ',
    'required' => 'حقل :attribute مطلوب ',
    'required_if' => 'حقل :attribute مطلوب عندما يكون :other هو :value ',
    'required_unless' => 'حقل :attribute مطلوب إلا إذا كان :other في :values ',
    'required_with' => 'حقل :attribute مطلوب عندما يكون :values موجوداً ',
    'required_without' => 'حقل :attribute مطلوب عندما لا يكون :values موجوداً ',
    'same' => 'يجب أن يتطابق :attribute مع :other ',
    'size' => [
        'numeric' => 'يجب أن تكون قيمة :attribute :size ',
        'file' => 'يجب أن يكون حجم الملف :attribute :size كيلوبايت ',
        'string' => 'يجب أن يحتوي النص :attribute على :size حروف ',
        'array' => 'يجب أن يحتوي :attribute على :size عنصر ',
    ],
    'string' => 'يجب أن يكون :attribute نصاً ',
    'timezone' => 'يجب أن يكون :attribute نطاقاً زمنياً صالحاً ',
    'unique' => ':attribute مستخدم بالفعل ',
    'url' => 'تنسيق :attribute غير صالح ',
    'uuid' => 'يجب أن يكون :attribute UUID صالحاً ',

    'in' => 'القيمة المختارة في حقل :attribute غير صالحة ',
    'enum' => 'القيمة المختارة في حقل :attribute غير موجودة ',

    'password' => [
        'letters' => 'يجب أن تحتوي :attribute على حرف واحد على الأقل ',
        'mixed' => 'يجب أن تحتوي :attribute على حرف كبير وصغير ',
        'numbers' => 'يجب أن تحتوي :attribute على رقم واحد على الأقل ',
        'symbols' => 'يجب أن تحتوي :attribute على رمز واحد على الأقل ',
        'uncompromised' => 'تم تسريب :attribute في قاعدة بيانات عامة  الرجاء اختيار كلمة مرور مختلفة ',
    ],
    'mimetypes' => 'حقل :attribute يجب أن يكون ملف PDF حصراً ',
    'mimes'     => 'حقل :attribute يجب أن يكون ملف PDF حصراً ',

    /*
    |--------------------------------------------------------------------------
    | تخصيص أسماء الحقول
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'name' => 'الاسم',
        'otp_code' => 'رمز التحقق',
        'governorate' => 'مكان الإقامة',
        'token' => 'رمز المصادقة',
        'search' => 'البحث',
    ],
];
