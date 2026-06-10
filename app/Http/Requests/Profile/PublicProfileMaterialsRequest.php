<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class PublicProfileMaterialsRequest extends ApiFormRequest
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
            'tab' => ['nullable', Rule::in(['latest', 'popular', 'files', 'images'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'tab.in' => 'قيمة التبويب غير صحيحة',
            'per_page.integer' => 'عدد العناصر يجب أن يكون رقماً صحيحاً',
            'per_page.min' => 'عدد العناصر يجب ألا يقل عن عنصر واحد',
            'per_page.max' => 'عدد العناصر يجب ألا يزيد عن 20 عنصراً',
        ];
    }

    public function tab(): string
    {
        return $this->validated('tab', 'latest');
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', 20);
    }
}
