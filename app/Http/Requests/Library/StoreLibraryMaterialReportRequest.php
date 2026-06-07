<?php

namespace App\Http\Requests\Library;

use App\Enums\LibraryReportReason;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreLibraryMaterialReportRequest extends ApiFormRequest
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
            'reason' => [
                'required',
                Rule::enum(LibraryReportReason::class),
            ],

            'description' => [
                'nullable',
                'string',
                'max:250',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'سبب البلاغ مطلوب.',
            'reason.Illuminate\Validation\Rules\Enum' => 'سبب البلاغ غير صالح.',
            'description.string' => 'وصف البلاغ يجب أن يكون نصًا.',
            'description.max' => 'وصف البلاغ طويل جدًا.',
        ];
    }
}
