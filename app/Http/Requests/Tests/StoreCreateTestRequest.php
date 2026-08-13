<?php

namespace App\Http\Requests\Tests;

use App\Enums\DifficultyLevel;
use App\Enums\Language;
use App\Enums\TargetLevel;
use App\Enums\TestType;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreCreateTestRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:10', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:250'],

            'test_type' => [
                'required',
                'string',
                Rule::enum(TestType::class),
            ],

            'difficulty_level' => [
                'required',
                'string',
                Rule::enum(DifficultyLevel::class),
            ],

            'duration_seconds' => [
                'nullable',
                'integer',
                'min:600',
                'max:10800',
            ],

            'pass_mark_percentage' => [
                'nullable',
                'integer',
                'min:10',
                'max:80',
                Rule::in([20 , 30 , 40 , 50 , 60 , 70 , 80])
            ],

            'language' => [
                'required',
                'string',
                Rule::enum(Language::class),
            ],

            'price' => [
                'prohibited_unless:test_type,عام',
                'nullable',
                'numeric',
                'min:1',
                'max:' . (float) config('payments.max_test_price', 10000000),
            ],

            'target_level' => [
                'required',
                'string',
                Rule::enum(TargetLevel::class),
            ],

            'interest_ids' => [
                'required',
                'array',
                'min:1',
                'max:3',
            ],

            'interest_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:interests,id',
            ],

            'questions' => [
                'required',
                'array',
                'min:5',
                'max:100',
            ],

            'questions.*.question_text' => [
                'required',
                'string',
                'min:10',
                'max:500',
            ],

            'questions.*.hint_text' => [
                'nullable',
                'string',
                'max:250',
            ],

            'questions.*.is_preview' => [
                'sometimes',
                'boolean',
            ],

            'questions.*.options' => [
                'required',
                'array',
                'min:2',
                'max:5',
            ],

            'questions.*.options.*.option_text' => [
                'required',
                'string',
                'min:2',
                'max:500',
            ],

            'questions.*.options.*.is_correct' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان الاختبار مطلوب',
            'title.string' => 'عنوان الاختبار يجب أن يكون نصاً',
            'title.min' => 'عنوان الاختبار يجب أن يتكون من 10 أحرف على الأقل',
            'title.max' => 'عنوان الاختبار لا يجوز أن يتجاوز 150 حرفاً',

            'description.required' => 'وصف الاختبار مطلوب',
            'description.string' => 'وصف الاختبار يجب أن يكون نصاً',
            'description.min' => 'وصف الاختبار يجب أن يتكون من 10 أحرف على الأقل',
            'description.max' => 'وصف الاختبار لا يجوز أن يتجاوز 250 حرف',

            'test_type.required' => 'نوع الاختبار مطلوب',
            'test_type.string' => 'نوع الاختبار يجب أن يكون نصاً',
            'test_type.Illuminate\Validation\Rules\Enum' => 'نوع الاختبار غير صالح',

            'difficulty_level.required' => 'مستوى صعوبة الاختبار مطلوب',
            'difficulty_level.string' => 'مستوى صعوبة الاختبار يجب أن يكون نصاً',
            'difficulty_level.Illuminate\Validation\Rules\Enum' => 'مستوى صعوبة الاختبار غير صالح',

            'duration_seconds.integer' => 'مدة الاختبار يجب أن تكون رقماً صحيحاً',
            'duration_seconds.min' => 'مدة الاختبار يجب ألا تقل عن عشر دقائق',
            'duration_seconds.max' => 'مدة الاختبار لا يجوز أن تتجاوز 3 ساعات',

            'pass_mark_percentage.integer' => 'حد النجاح يجب أن يكون رقماً صحيحاً',
            'pass_mark_percentage.min' => 'حد النجاح يجب ألا يقل عن 10%',
            'pass_mark_percentage.max' => 'حد النجاح يجب ألا يتجاوز 80%',

            'language.required' => 'لغة الاختبار مطلوبة',
            'language.string' => 'لغة الاختبار يجب ان تكون نصا',
            'language.Illuminate\Validation\Rules\Enum' => 'لغة الاختبار غير صالحة',

            'price.prohibited_unless' => 'لا يمكن إدخال سعر إلا للاختبارات العامة فقط',
            'price.numeric' => 'سعر الاختبار يجب أن يكون رقماً',
            'price.min' => 'سعر الاختبار يجب أن يكون أكبر من صفر',
            'price.max' => 'سعر الاختبار كبير جداً',

            'target_level.required' => 'المستوى الدراسي مطلوب',
            'target_level.string' => 'المستوى الدراسي يجب أن يكون نصاً',
            'target_level.Illuminate\Validation\Rules\Enum' => 'المستوى الدراسي غير صالح',

            'interest_ids.required' => 'التصنيف العلمي مطلوب',
            'interest_ids.array' => 'صيغة التصنيفات العلمية غير صحيحة',
            'interest_ids.min' => 'يجب اختيار تصنيف علمي واحد على الأقل',
            'interest_ids.max' => 'لا يمكن اختيار أكثر من ثلاثة تصنيفات علمية',
            'interest_ids.*.exists' => 'أحد التصنيفات العلمية المحددة غير موجود',
            'interest_ids.*.distinct' => 'لا يمكن تكرار نفس التصنيف العلمي',

            'questions.required' => 'يجب إضافة أسئلة للاختبار',
            'questions.array' => 'صيغة الأسئلة غير صحيحة',
            'questions.min' => 'يجب أن يحتوي الاختبار على 5 أسئلة على الأقل',
            'questions.max' => 'لا يمكن أن يحتوي الاختبار على أكثر من 100 سؤال',

            'questions.*.question_text.required' => 'نص السؤال مطلوب',
            'questions.*.question_text.min' => 'نص السؤال قصير جداً',
            'questions.*.question_text.max' => 'نص السؤال لا يجوز أن يتجاوز 500 حرف',

            'questions.*.hint_text.max' => 'تلميح السؤال لا يجوز أن يتجاوز 250 حرف',

            'questions.*.options.required' => 'خيارات السؤال مطلوبة',
            'questions.*.options.array' => 'صيغة خيارات السؤال غير صحيحة',
            'questions.*.options.min' => 'يجب أن يحتوي كل سؤال على خيارين على الأقل',
            'questions.*.options.max' => 'لا يمكن أن يحتوي السؤال على أكثر من خمسة خيارات',

            'questions.*.options.*.option_text.required' => 'نص الخيار مطلوب',
            'questions.*.options.*.option_text.max' => 'نص الخيار لا يجوز أن يتجاوز 500 حرف',

            'questions.*.options.*.is_correct.required' => 'يجب تحديد هل الخيار صحيح أم لا',
            'questions.*.options.*.is_correct.boolean' => 'قيمة تحديد الإجابة الصحيحة غير صالحة',
        ];
    }
}
