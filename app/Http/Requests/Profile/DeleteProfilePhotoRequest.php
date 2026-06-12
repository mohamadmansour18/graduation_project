<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\ApiFormRequest;

class DeleteProfilePhotoRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->query('type'),
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:avatar,cover'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'نوع الصورة مطلوب',
            'type.in' => 'نوع الصورة يجب أن يكون avatar أو cover',
        ];
    }
}
