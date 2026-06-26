<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class ListDashboardUsersRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['mobile_users', 'supervisors', 'owners'])],
            'sort_by' => ['nullable', Rule::in([
                'created_at',
                'name',
                'governorate',
                'gender',
                'account_status',
            ])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cursor' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'نوع المستخدمين مطلوب',
            'type.in' => 'نوع المستخدمين غير صالح',
            'sort_by.in' => 'مفتاح الترتيب غير صالح',
            'per_page.integer' => 'عدد العناصر يجب أن يكون رقماً',
            'per_page.min' => 'عدد العناصر يجب ألا يقل عن عنصر واحد',
            'per_page.max' => 'عدد العناصر يجب ألا يزيد عن 50 عنصر',
        ];
    }
}
