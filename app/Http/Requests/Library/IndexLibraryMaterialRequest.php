<?php

namespace App\Http\Requests\Library;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class IndexLibraryMaterialRequest extends ApiFormRequest
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
            'tab' => ['nullable', Rule::in(['trending', 'newest', 'most_downloaded'])],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'tab.in' => 'نوع التبويب المحدد غير صالح.',
            'per_page.integer' => 'عدد العناصر يجب أن يكون رقمًا صحيحًا.',
            'per_page.min' => 'عدد العناصر يجب ألا يقل عن 5.',
            'per_page.max' => 'عدد العناصر يجب ألا يتجاوز 20.',
        ];
    }
}
