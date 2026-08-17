<?php

namespace App\Http\Requests\Library;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class SearchLibraryMaterialRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:2'],
            'mode' => ['nullable', Rule::in(['all_public', 'user_owned'])],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:20'],
            'cursor' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'query.required' => 'يجب إدخال كلمة البحث',
            'query.min' => 'كلمة البحث قصيرة جدًا',
            'mode.in' => 'نوع البحث غير صالح',
        ];
    }
}
