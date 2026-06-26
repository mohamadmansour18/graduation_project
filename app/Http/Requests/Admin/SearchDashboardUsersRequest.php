<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class SearchDashboardUsersRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['required', 'string', 'min:1', 'max:100'],
            'role' => ['required', Rule::in(['mobile_users', 'supervisors', 'owners'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cursor' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.required' => 'كلمة البحث مطلوبة',
            'search.min' => 'كلمة البحث يجب ألا تكون فارغة',
            'search.max' => 'كلمة البحث طويلة جداً',
            'role.required' => 'نوع المستخدمين مطلوب',
            'role.in' => 'نوع المستخدمين غير صالح',
        ];
    }
}
