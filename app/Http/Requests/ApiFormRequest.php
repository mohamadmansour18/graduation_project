<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\ApiErrorResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
abstract class ApiFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiErrorResponse::make(
                title: '! خطأ تحقق',
                message: $validator->errors()->first(),
                status: 422
            )
        );
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            ApiErrorResponse::make(
                title: '! غير مصرح',
                message: 'عزيزي المستخدم انت غير مصرح لك بالقيام بهذا الفعل المحدد',
                status: 403
            )
        );
    }
}
