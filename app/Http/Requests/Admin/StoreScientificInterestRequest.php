<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;
use App\Rules\ArabicOnly;
use Illuminate\Validation\Rule;

class StoreScientificInterestRequest extends ApiFormRequest
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
            'interest_category_id' => [
                'required',
                'integer',
                Rule::exists('interest_categories', 'id'),
            ],

            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('interests', 'name'),
                new ArabicOnly(),
            ],

            'icon' => [
                'required',
                'file',
                'mimes:svg',
                'max:512',
            ],

            'color' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'interest_category_id.required' => 'عنوان التصنيف العلمي مطلوب',
            'interest_category_id.exists' => 'عنوان التصنيف العلمي غير موجود',

            'name.required' => 'اسم التصنيف العلمي مطلوب',
            'name.unique' => 'اسم التصنيف العلمي موجود بالفعل',
            'name.min' => 'اسم التصنيف العلمي يجب ألا يقل عن محرفين',
            'name.arabic_only' => 'يجب أن يحتوي الاسم على أحرف عربية فقط',

            'icon.required' => 'ايقونة التصنيف مطلوبة',
            'icon.mimes' => 'أيقونة التصنيف يجب أن تكون بصيغة SVG حصراً',
            'icon.max' => 'حجم الأيقونة كبير جداً',

            'color.required' => 'لون الايقونة مطلوب',
            'color.regex' => 'لون الأيقونة يجب أن يكون بصيغة HEX مثل #5583FF',
        ];
    }
}
