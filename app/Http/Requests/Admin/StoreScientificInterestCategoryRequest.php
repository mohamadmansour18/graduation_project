<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;
use App\Rules\ArabicOnly;
use Illuminate\Validation\Rule;

class StoreScientificInterestCategoryRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('interest_categories', 'title'),
                new ArabicOnly(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان التصنيف العلمي مطلوب',
            'title.unique' => 'عنوان التصنيف العلمي موجود بالفعل',
            'title.min' => 'عنوان التصنيف العلمي يجب ألا يقل عن محرفين',
            'title.max' => 'عنوان التصنيف العلمي طويل جداً',
            'title.arabic_only' => 'يجب أن يحتوي الاسم على أحرف عربية فقط',
        ];
    }
}
