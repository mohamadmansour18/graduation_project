<?php

namespace App\Http\Requests\Profile;

use App\Enums\Gender;
use App\Enums\Governorate;
use App\Http\Requests\ApiFormRequest;
use App\Rules\ArabicOnly;
use Illuminate\Validation\Rule;

class UpdatePersonalInformationRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:255' ,  new ArabicOnly()],

            'governorate' => ['sometimes', 'nullable', 'string' , Rule::enum(Governorate::class)],

            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^09[0-9]{8}$/'],
            'birth_date' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'before:today'],

            'gender' => ['sometimes', 'string', Rule::enum(Gender::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.min' => 'الاسم يجب أن يحتوي على حرفين على الأقل',
            'name.max' => 'الاسم طويل جد',
            'name.arabic_only' => 'يجب أن يحتوي الاسم على أحرف عربية فقط',

            'governorate.Illuminate\Validation\Rules\Enum' => 'حقل المحافظة المدخل غير صالح',

            'phone.regex' => 'يجب أن يبدأ رقم الهاتف بـ 09 وأن يتكون من 10 أرقام.',

            'birth_date.before' => 'تاريخ الميلاد يجب أن يكون قبل تاريخ اليوم',
            'birth_date.date_format' => 'تنسيق حقل تاريخ الميلاد خاطئ',

            'gender.Illuminate\Validation\Rules\Enum' => 'قيمة حقل الجنس غير صحيحة',
        ];
    }
}
