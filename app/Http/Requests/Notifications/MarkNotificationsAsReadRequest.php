<?php

namespace App\Http\Requests\Notifications;

use App\Http\Requests\ApiFormRequest;

class MarkNotificationsAsReadRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mark_all' => ['nullable', 'boolean', 'required_without:notification_ids'],
            'notification_ids' => ['nullable', 'array', 'required_without:mark_all'],
            'notification_ids.*' => ['required', 'string', 'max:64', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'mark_all.boolean' => 'قيمة تعليم كل الإشعارات كمقروءة غير صالحة',
            'mark_all.required_without' => 'يجب إرسال معرفات الإشعارات أو تحديد تعليم الكل كمقروء',
            'notification_ids.array' => 'معرفات الإشعارات يجب أن تكون ضمن مصفوفة',
            'notification_ids.required_without' => 'يجب إرسال معرفات الإشعارات أو تحديد تعليم الكل كمقروء',
            'notification_ids.*.required' => 'معرف الإشعار مطلوب',
            'notification_ids.*.string' => 'معرف الإشعار غير صالح',
            'notification_ids.*.max' => 'معرف الإشعار غير صالح',
            'notification_ids.*.distinct' => 'لا يجوز تكرار نفس الإشعار أكثر من مرة',
        ];
    }

    public function markAll(): bool
    {
        return (bool) $this->boolean('mark_all', false);
    }

    public function notificationIds(): array
    {
        return $this->input('notification_ids', []);
    }
}
