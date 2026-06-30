<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\ApiFormRequest;

class SearchUsersRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'query' => [
                'required',
                'string',
                'min:1',
                'max:100',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:20',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'query.required' => 'عبارة البحث مطلوبة',
            'query.string' => 'عبارة البحث يجب أن تكون نصاً',
            'query.min' => 'عبارة البحث يجب ألا تكون فارغة',
            'query.max' => 'عبارة البحث يجب ألا تتجاوز 100 حرف',

            'per_page.integer' => 'عدد النتائج يجب أن يكون رقماً صحيحاً',
            'per_page.min' => 'عدد النتائج يجب ألا يقل عن 1',
            'per_page.max' => 'عدد النتائج يجب ألا يتجاوز 20',
        ];
    }

    public function searchQuery(): string
    {
        return trim($this->validated('query'));
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', 20);
    }
}
