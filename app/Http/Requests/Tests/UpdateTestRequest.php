<?php

namespace App\Http\Requests\Tests;

use App\Enums\DifficultyLevel;
use App\Enums\Language;
use App\Enums\TargetLevel;
use App\Enums\TestType;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateTestRequest extends ApiFormRequest
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
            'test_type' => ['sometimes', 'string' , Rule::enum(TestType::class),],

            'title' => ['sometimes', 'string', 'min:10' , 'max:150'],
            'description' => ['sometimes', 'string' , 'min:10', 'max:250'],

            'difficulty_level' => ['sometimes', 'string' , Rule::enum(DifficultyLevel::class)],

            'duration_seconds' => ['sometimes', 'integer', 'min:600' , 'max:10800'],

            'pass_mark_percentage' => ['sometimes', 'integer', 'min:10', 'max:80' , Rule::in([20 , 30 , 40 , 50 , 60 , 70 , 80])],

            'language' => ['sometimes' , 'string' , Rule::enum(Language::class)],

            'price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:1000'],

            'target_level' => ['sometimes', 'string' , Rule::enum(TargetLevel::class)],

            'interest_ids' => ['sometimes', 'array' , 'min:1' , 'max:3'],
            'interest_ids.*' => ['integer', 'distinct' , 'exists:interests,id'],

            'questions' => ['sometimes', 'array' , 'min:3' , 'max:100'],
            'questions.*.id' => ['sometimes' , 'integer' , 'exists:test_question,id'],
            'questions.*.position' => ['required_with:questions', 'integer', 'min:1' , 'max:100'],
            'questions.*.question_text' => ['required_with:questions', 'string' , 'min:10' , 'max:500' ],
            'questions.*.hint_text' => ['sometimes', 'nullable', 'string' , 'max:250'],
            'questions.*.is_preview' => ['sometimes', 'boolean'],

            'questions.*.options' => ['required_with:questions', 'array', 'min:2', 'max:5'],
            'questions.*.options.*.id' => ['sometimes', 'nullable', 'integer' , 'exists:test_question_options,id'],
            'questions.*.options.*.position' => ['required_with:questions.*.options', 'integer', 'min:1' , 'max:5'],
            'questions.*.options.*.option_text' => ['required_with:questions.*.options', 'string' , 'min:2' , 'max:500'],
            'questions.*.options.*.is_correct' => ['required_with:questions.*.options', 'boolean'],


            'preview_question_ids' => ['sometimes', 'array'],
            'preview_question_ids.*' => ['integer' , 'distinct' , 'min:1' , 'exists:test_question,id'],
        ];
    }

    public function messages(): array
    {
        return [

            // test_type
            'test_type.string' => 'نوع الاختبار يجب أن يكون نصاً.',
            'test_type.Illuminate\Validation\Rules\Enum' => 'نوع الاختبار المحدد غير صالح.',

            // title
            'title.string' => 'عنوان الاختبار يجب أن يكون نصاً.',
            'title.min' => 'عنوان الاختبار يجب ألا يقل عن 10 أحرف.',
            'title.max' => 'عنوان الاختبار يجب ألا يزيد عن 150 حرفاً.',

            // description
            'description.string' => 'وصف الاختبار يجب أن يكون نصاً.',
            'description.min' => 'وصف الاختبار يجب ألا يقل عن 10 أحرف.',
            'description.max' => 'وصف الاختبار يجب ألا يزيد عن 250 حرفاً.',

            // difficulty_level
            'difficulty_level.string' => 'مستوى الصعوبة يجب أن يكون نصاً.',
            'difficulty_level.Illuminate\Validation\Rules\Enum' => 'مستوى الصعوبة المحدد غير صالح.',

            // duration_seconds
            'duration_seconds.integer' => 'مدة الاختبار يجب أن تكون رقماً صحيحاً.',
            'duration_seconds.min' => 'مدة الاختبار يجب ألا تقل عن 600 ثانية.',
            'duration_seconds.max' => 'مدة الاختبار يجب ألا تزيد عن 10800 ثانية.',

            // pass_mark_percentage
            'pass_mark_percentage.integer' => 'درجة النجاح يجب أن تكون رقماً صحيحاً.',
            'pass_mark_percentage.min' => 'درجة النجاح يجب ألا تقل عن 10.',
            'pass_mark_percentage.max' => 'درجة النجاح يجب ألا تزيد عن 80.',
            'pass_mark_percentage.in' => 'درجة النجاح يجب أن تكون إحدى القيم التالية: 20، 30، 40، 50، 60، 70، 80.',

            // language
            'language.string' => 'لغة الاختبار يجب أن تكون نصاً.',
            'language.Illuminate\Validation\Rules\Enum' => 'اللغة المحددة غير صالحة.',

            // price
            'price.numeric' => 'السعر يجب أن يكون رقماً.',
            'price.min' => 'السعر يجب ألا يكون أقل من 0.',
            'price.max' => 'السعر يجب ألا يزيد عن 1000.',

            // target_level
            'target_level.string' => 'المستوى المستهدف يجب أن يكون نصاً.',
            'target_level.Illuminate\Validation\Rules\Enum' => 'المستوى المستهدف المحدد غير صالح.',

            // interest_ids
            'interest_ids.array' => 'الاهتمامات يجب أن تكون على شكل قائمة.',
            'interest_ids.min' => 'يجب اختيار اهتمام واحد على الأقل.',
            'interest_ids.max' => 'لا يمكن اختيار أكثر من 3 اهتمامات',

            'interest_ids.*.integer' => 'معرف الاهتمام يجب أن يكون رقماً صحيحاً.',
            'interest_ids.*.distinct' => 'لا يمكن تكرار نفس الاهتمام.',
            'interest_ids.*.exists' => 'أحد الاهتمامات المحددة غير موجود.',

            //is preview
            'questions.*.is_preview.boolean' => 'قيمة السؤال التجريبي يجب أن تكون true أو false.',

            // preview_question_ids
            'preview_question_ids.array' => 'الأسئلة التجريبية يجب أن تكون على شكل قائمة.',

            'preview_question_ids.*.integer' => 'معرف السؤال التجريبي يجب أن يكون رقماً صحيحاً.',
            'preview_question_ids.*.distinct' => 'لا يمكن تكرار نفس السؤال التجريبي.',
            'preview_question_ids.*.exists' => 'أحد الأسئلة التجريبية غير موجود.',
            'preview_question_ids.*.min' => 'معرف السؤال التجريبي غير صالح.',

            // questions
            'questions.array' => 'الأسئلة يجب أن تكون على شكل قائمة.',
            'questions.min' => 'يجب إضافة 5 أسئلة على الأقل.',
            'questions.max' => 'لا يمكن إضافة أكثر من 100 سؤال.',

            // questions.*.id
            'questions.*.id.integer' => 'معرف السؤال يجب أن يكون رقماً صحيحاً.',
            'questions.*.id.exists' => 'أحد الأسئلة المحددة غير موجود.',

            // questions.*.position
            'questions.*.position.required_with' => 'ترتيب السؤال مطلوب.',
            'questions.*.position.integer' => 'ترتيب السؤال يجب أن يكون رقماً صحيحاً.',
            'questions.*.position.min' => 'ترتيب السؤال يجب أن يبدأ من 1.',
            'questions.*.position.max' => 'ترتيب السؤال غير صالح.',

            // questions.*.question_text
            'questions.*.question_text.required_with' => 'نص السؤال مطلوب.',
            'questions.*.question_text.string' => 'نص السؤال يجب أن يكون نصاً.',
            'questions.*.question_text.min' => 'نص السؤال يجب ألا يقل عن 10 أحرف.',
            'questions.*.question_text.max' => 'نص السؤال يجب ألا يزيد عن 500 حرف.',

            // questions.*.hint_text
            'questions.*.hint_text.string' => 'التلميح يجب أن يكون نصاً.',
            'questions.*.hint_text.max' => 'التلميح يجب ألا يزيد عن 250 حرفاً.',

            // questions.*.options
            'questions.*.options.required_with' => 'خيارات السؤال مطلوبة.',
            'questions.*.options.array' => 'خيارات السؤال يجب أن تكون على شكل قائمة.',
            'questions.*.options.min' => 'يجب إضافة خيارين على الأقل لكل سؤال.',
            'questions.*.options.max' => 'لا يمكن إضافة أكثر من 5 خيارات لكل سؤال.',

            // questions.*.options.*.id
            'questions.*.options.*.id.integer' => 'معرف الخيار يجب أن يكون رقماً صحيحاً.',
            'questions.*.options.*.id.exists' => 'أحد الخيارات المحددة غير موجود.',

            // questions.*.options.*.position
            'questions.*.options.*.position.required_with' => 'ترتيب الخيار مطلوب.',
            'questions.*.options.*.position.integer' => 'ترتيب الخيار يجب أن يكون رقماً صحيحاً.',
            'questions.*.options.*.position.min' => 'ترتيب الخيار يجب أن يبدأ من 1.',
            'questions.*.options.*.position.max' => 'ترتيب الخيار غير صالح.',

            // questions.*.options.*.option_text
            'questions.*.options.*.option_text.required_with' => 'نص الخيار مطلوب.',
            'questions.*.options.*.option_text.string' => 'نص الخيار يجب أن يكون نصاً.',
            'questions.*.options.*.option_text.min' => 'نص الخيار يجب ألا يقل عن حرفين.',
            'questions.*.options.*.option_text.max' => 'نص الخيار يجب ألا يزيد عن 500 حرف.',

            // questions.*.options.*.is_correct
            'questions.*.options.*.is_correct.required_with' => 'يجب تحديد فيما إذا كان الخيار صحيحاً.',
            'questions.*.options.*.is_correct.boolean' => 'قيمة الخيار الصحيح يجب أن تكون true أو false.',
        ];
    }
}
