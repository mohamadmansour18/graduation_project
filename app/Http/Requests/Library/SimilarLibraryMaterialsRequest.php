<?php

namespace App\Http\Requests\Library;

use App\Http\Requests\ApiFormRequest;

class SimilarLibraryMaterialsRequest extends ApiFormRequest
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
            'interest_ids' => ['required', 'array', 'min:1', 'max:3'],
            'interest_ids.*' => ['required', 'integer', 'distinct', 'exists:interests,id'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'interest_ids.required' => 'يجب إرسال التصنيفات العلمية.',
            'interest_ids.min' => 'يجب إرسال تصنيف علمي واحد على الأقل.',
            'interest_ids.max' => 'لا يمكن إرسال أكثر من ثلاثة تصنيفات علمية.',
            'interest_ids.*.distinct' => 'لا يمكن تكرار نفس التصنيف العلمي.',
            'interest_ids.*.exists' => 'أحد التصنيفات العلمية غير صالح.',
            'per_page.max' => 'عدد النتائج لا يمكن أن يتجاوز 20.',
        ];
    }
}
