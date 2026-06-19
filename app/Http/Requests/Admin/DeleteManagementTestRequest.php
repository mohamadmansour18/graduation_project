<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;

class DeleteManagementTestRequest extends ApiFormRequest
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
            'deletion_reason' => ['required', 'string', 'min:10', 'max:250'],
        ];
    }

    public function messages(): array
    {
        return [
            'deletion_reason.required' => 'سبب حذف الاختبار مطلوب',
            'deletion_reason.string' => 'سبب حذف الاختبار يجب أن يكون نصاً',
            'deletion_reason.min' => 'سبب حذف الاختبار يجب أن يحتوي على 10 أحرف على الأقل',
            'deletion_reason.max' => 'سبب حذف الاختبار طويل جداً',
        ];
    }
}
