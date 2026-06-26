<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class ListBannedUsersRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tab' => ['nullable', Rule::in(['all', 'permanent', 'temporary'])],
        ];
    }

    public function messages(): array
    {
        return [
            'tab.in' => 'نوع قائمة الحظر غير صالح',
        ];
    }
}
