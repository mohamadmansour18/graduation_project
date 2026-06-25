<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteLibraryMaterialRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delete_reason' => ['required', 'string', 'min:10', 'max:250'],
        ];
    }

    public function messages(): array
    {
        return [
            'delete_reason.required' => 'سبب الحذف مطلوب',
            'delete_reason.string' => 'سبب الحذف يجب أن يكون نصاً',
            'delete_reason.min' => 'سبب الحذف يجب أن يحتوي على 10 أحرف على الأقل',
            'delete_reason.max' => 'سبب الحذف يجب ألا يتجاوز 250 حرف',
        ];
    }
}
