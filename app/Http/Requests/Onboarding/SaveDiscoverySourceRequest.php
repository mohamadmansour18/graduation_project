<?php

namespace App\Http\Requests\Onboarding;

use App\Enums\DiscoverySource;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rules\Enum;

class SaveDiscoverySourceRequest extends ApiFormRequest
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
            'email' => ['required' , 'exists:users,email'],
            'discovery_source' => [
                'required',
                'string',
                new Enum(DiscoverySource::class),
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'حقل البريد الإلكتروني مطلوب',
            'email.exists' => 'البريد الالكتروني الذي تحاول الوصول اليه غير موجود',

            'discovery_source.required' => 'يرجى اختيار طريقة سماعك عن التطبيق',
            'discovery_source.string' => 'طريقة سماعك عن التطبيق يجب أن تكون نصا',
            'discovery_source.' . Enum::class => 'الخيار المحدد لطريقة معرفة التطبيق غير صالح',
        ];
    }
}
