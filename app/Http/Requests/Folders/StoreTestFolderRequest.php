<?php

namespace App\Http\Requests\Folders;

use App\Enums\VisibilityType;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreTestFolderRequest extends ApiFormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'color_code' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'visibility_type' => ['required', Rule::enum(VisibilityType::class)],

            'test_ids' => ['required', 'array', 'min:1', 'max:10'],
            'test_ids.*' => ['required', 'integer', 'distinct', 'exists:test,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'color_code.regex' => 'لون المجلد يجب أن يكون بصيغة HEX مثل #FFAA00',
            'visibility_type.Illuminate\Validation\Rules\Enum' => 'حقل نوع المجلد يجب أن يكون إما خاصا أو عاما',
            'test_ids.min' => 'يجب إضافة اختبار واحد على الأقل',
            'test_ids.max' => 'لا يمكن إضافة أكثر من 10 اختبارات داخل المجلد',
            'test_ids.*.distinct' => 'لا يمكن تكرار نفس الاختبار داخل المجلد',
        ];
    }

}
