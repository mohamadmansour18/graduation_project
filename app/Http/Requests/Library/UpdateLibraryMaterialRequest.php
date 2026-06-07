<?php

namespace App\Http\Requests\Library;

use App\Enums\TargetLevel;
use App\Enums\VisibilityType;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateLibraryMaterialRequest extends ApiFormRequest
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
            'title' => ['sometimes', 'string', 'min:3', 'max:100'],

            'description' => ['sometimes', 'string', 'min:3', 'max:250'],

            'interest_ids' => ['sometimes', 'array', 'min:1', 'max:3'],
            'interest_ids.*' => ['required', 'integer', 'distinct', 'exists:interests,id'],

            'target_level' => ['sometimes', 'string', Rule::enum(TargetLevel::class)],

            'visibility_type' => ['sometimes', 'string', Rule::enum(VisibilityType::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (empty($this->validated())) {
                $validator->errors()->add(
                    'data',
                    'يجب إرسال حقل واحد على الأقل لتعديله.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'title.string' => 'عنوان المحتوى يجب أن يكون نصًا.',
            'title.min' => 'عنوان المحتوى قصير جدًا.',
            'title.max' => 'عنوان المحتوى طويل جدًا.',

            'description.string' => 'وصف المحتوى يجب أن يكون نصًا.',
            'description.min' => 'وصف المحتوى قصير جدًا.',
            'description.max' => 'وصف المحتوى طويل جدًا.',

            'interest_ids.array' => 'التصنيفات العلمية يجب أن تكون مصفوفة.',
            'interest_ids.min' => 'يجب اختيار تصنيف علمي واحد على الأقل.',
            'interest_ids.max' => 'لا يمكن اختيار أكثر من ثلاثة تصنيفات علمية.',
            'interest_ids.*.distinct' => 'لا يمكن تكرار نفس التصنيف العلمي.',
            'interest_ids.*.exists' => 'أحد التصنيفات العلمية غير صالح.',

            'target_level.string' => 'المستوى الدراسي يجب أن يكون نصًا.',
            'target_level.Illuminate\Validation\Rules\Enum' => 'المستوى الدراسي غير صالح.',

            'visibility_type.Illuminate\Validation\Rules\Enum' => 'نوع المحتوى غير صالح.',
        ];
    }
}
