<?php

namespace App\Http\Requests\Notifications;

use App\Http\Requests\ApiFormRequest;

class ListNotificationsRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cursor' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => 'عدد الإشعارات في الصفحة يجب أن يكون رقمًا',
            'per_page.min' => 'عدد الإشعارات في الصفحة غير صالح',
            'per_page.max' => 'لا يمكن جلب أكثر من 50 إشعار في الطلب الواحد',
            'cursor.string' => 'قيمة المؤشر غير صالحة',
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 20);
    }

    public function cursorValue(): ?string
    {
        return $this->input('cursor');
    }
}
