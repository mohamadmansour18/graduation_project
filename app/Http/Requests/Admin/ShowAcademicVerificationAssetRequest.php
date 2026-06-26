<?php

namespace App\Http\Requests\Admin;

use App\Enums\AcademicAssetType;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class ShowAcademicVerificationAssetRequest extends ApiFormRequest
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
            'document_type' => ['required', Rule::enum(AcademicAssetType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'document_type.required' => 'نوع الوثيقة مطلوب',
            'document_type.Illuminate\Validation\Rules\Enum' => 'نوع الوثيقة غير صالح',
        ];
    }
}
