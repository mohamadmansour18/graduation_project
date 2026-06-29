<?php

namespace App\Http\Requests\StudyPlans;

use App\Http\Requests\ApiFormRequest;

class StoreStudySubjectRequest extends ApiFormRequest
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
            'name' => ['required', 'string', 'min:4', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المادة مطلوب',
            'name.string' => 'اسم المادة يجب أن يكون نصًا',
            'name.min' => 'اسم المادة يجب ألا يقل عن 4 احرف',
            'name.max' => 'اسم المادة يجب ألا يتجاوز 100 حرف',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->input('name')),
            ]);
        }
    }
}
