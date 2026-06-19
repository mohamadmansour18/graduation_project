<?php

namespace App\Http\Requests\Admin;

use App\Enums\RevisionType;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class RequestTestRevisionsRequest extends ApiFormRequest
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
            'revisions' => ['required', 'array', 'min:1', 'max:8'],

            'revisions.*.revision_type' => [
                'required',
                'string',
                Rule::enum(RevisionType::class),
            ],

            'revisions.*.question_position' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'revisions.*.option_position' => [
                'nullable',
                'integer',
                'min:1',
                'max:5',
            ],

            'revisions.*.problem_note' => [
                'required',
                'string',
                'min:10',
                'max:250',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('revisions', []) as $index => $revision) {
                $type = $revision['revision_type'] ?? null;

                $hasQuestionPosition = filled($revision['question_position'] ?? null);
                $hasOptionPosition = filled($revision['option_position'] ?? null);

                if (in_array($type, ['نص السؤال', 'التلميح', 'إجابة السؤال'], true) && ! $hasQuestionPosition) {
                    $validator->errors()->add(
                        "revisions.{$index}.question_position",
                        'رقم السؤال مطلوب لهذا النوع من التعديل.'
                    );
                }

                if ($type === 'نص الاجابة') {
                    if (! $hasQuestionPosition) {
                        $validator->errors()->add(
                            "revisions.{$index}.question_position",
                            'رقم السؤال مطلوب عند تعديل نص الإجابة.'
                        );
                    }

                    if (! $hasOptionPosition) {
                        $validator->errors()->add(
                            "revisions.{$index}.option_position",
                            'رقم الإجابة مطلوب عند تعديل نص الإجابة.'
                        );
                    }
                }

                if ($type === 'وصف الاختبار' && ($hasQuestionPosition || $hasOptionPosition)) {
                    $validator->errors()->add(
                        "revisions.{$index}.revision_type",
                        'تعديل وصف اختبار لا يحتاج ادخال رقم سؤال أو رقم إجابة.'
                    );
                }

                if ($type === 'عنوان الاختبار' && ($hasQuestionPosition || $hasOptionPosition)) {
                    $validator->errors()->add(
                        "revisions.{$index}.revision_type",
                        'تعديل عنوان الاختبار لا يحتاج ادخال رقم سؤال أو رقم إجابة.'
                    );
                }

                if (in_array($type, ['نص السؤال', 'التلميح', 'إجابة السؤال'], true) && $hasOptionPosition) {
                    $validator->errors()->add(
                        "revisions.{$index}.option_position",
                        'هذا النوع من التعديل لا يحتاج رقم إجابة.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'revisions.required' => 'قائمة التعديلات مطلوبة.',
            'revisions.array' => 'قائمة التعديلات يجب أن تكون مصفوفة.',
            'revisions.min' => 'يجب إدخال تعديل واحد على الأقل.',
            'revisions.max' => 'لا يمكن إرسال أكثر من 8 تعديلات دفعة واحدة.',

            'revisions.*.revision_type.required' => 'نوع التعديل مطلوب.',
            'revisions.*.revision_type.Illuminate\Validation\Rules\Enum' => 'نوع التعديل المحدد غير صحيح.',

            'revisions.*.question_position.integer' => 'رقم السؤال يجب أن يكون رقماً صحيحاً.',
            'revisions.*.question_position.min' => 'رقم السؤال يجب أن يكون أكبر من صفر.',
            'revisions.*.question_position.max'=>'رقم السؤال لايجب ان يكون اكبر من 100',

            'revisions.*.option_position.integer' => 'رقم الإجابة يجب أن يكون رقماً صحيحاً.',
            'revisions.*.option_position.min' => 'رقم الإجابة يجب أن يكون أكبر من صفر.',
            'revisions.*.option_position.max' => 'رقم الإجابة لا يمكن أن يكون أكبر من 5.',

            'revisions.*.problem_note.required' => 'وصف المشكلة مطلوب.',
            'revisions.*.problem_note.min' => 'وصف المشكلة يجب أن يحتوي على 10 أحرف على الأقل.',
            'revisions.*.problem_note.max' => 'وصف المشكلة طويل جداً.',
        ];
    }
}
