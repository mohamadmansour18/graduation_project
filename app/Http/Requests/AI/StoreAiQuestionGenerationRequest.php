<?php

namespace App\Http\Requests\AI;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAiQuestionGenerationRequest extends ApiFormRequest
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
            'source_type' => [
                'required',
                'string',
                Rule::in(['Images', 'Pdf']),
            ],

            'question_count' => [
                'required',
                'integer',
                'min:' . config('ai_question_generation.min_question_count'),
                'max:' . config('ai_question_generation.max_question_count'),
            ],

            'difficulty_level' => [
                'required',
                'string',
                Rule::in(['Easy', 'Medium', 'Hard']),
            ],

            'language' => [
                'required',
                'string',
                Rule::in(['English', 'Arabic']),
            ],

            'files' => [
                'required',
                'array',
            ],

            'files.*' => [
                'required',
                'file',
                'min:1',
                'max:' . config('ai_question_generation.max_pdf_size_kb'),
                'mimes:jpg,jpeg,pdf,png',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void
        {
            $sourceType = $this->input('source_type');
            $files = $this->file('files', []);

            if (! is_array($files)) {
                return;
            }

            if ($sourceType === 'Images') {
                $this->validateImages($validator, $files);
            }

            if ($sourceType === 'Pdf') {
                $this->validatePdf($validator, $files);
            }
        });
    }

    private function validateImages(Validator $validator, array $files): void
    {
        $min = config('ai_question_generation.min_images_count');
        $max = config('ai_question_generation.max_images_count');
        $maxImageSizeKb = config('ai_question_generation.max_image_size_kb');

        if (count($files) < $min || count($files) > $max) {
            $validator->errors()->add(
                'files',
                "يجب رفع عدد صور بين {$min} و {$max}"
            );

            return;
        }

        foreach ($files as $index => $file) {
            if (! str_starts_with((string) $file->getMimeType() , 'image/')) {
                $validator->errors()->add(
                    "files.{$index}",
                    'يجب أن تكون جميع الملفات المرفوعة صوراً'
                );
            }

            if (($file->getSize() / 1024) > $maxImageSizeKb) {
                $validator->errors()->add(
                    "files.{$index}",
                    'حجم الصورة أكبر من الحد المسموح'
                );
            }
        }
    }

    private function validatePdf(Validator $validator, array $files): void
    {
        if (count($files) !== 1) {
            $validator->errors()->add(
                'files',
                'يجب رفع ملف PDF واحد فقط'
            );

            return;
        }

        $file = $files[0];

        if ($file->getMimeType() !== 'application/pdf') {
            $validator->errors()->add(
                'files.0',
                'يجب أن يكون الملف المرفوع من نوع PDF'
            );
        }
    }

    public function messages(): array
    {
        return [
            'source_type.required' => 'نوع مصدر التوليد مطلوب',
            'source_type.in' => 'نوع مصدر التوليد غير صالح',

            'question_count.required' => 'عدد الأسئلة المطلوب مطلوب',
            'question_count.integer' => 'عدد الأسئلة يجب أن يكون رقماً صحيحاً',
            'question_count.min' => 'عدد الأسئلة المطلوب أقل من الحد المسموح',
            'question_count.max' => 'عدد الأسئلة المطلوب أكبر من الحد المسموح',

            'difficulty_level.required' => 'مستوى الصعوبة مطلوب',
            'difficulty_level.in' => 'مستوى الصعوبة غير صالح',

            'language.required' => 'لغة الأسئلة مطلوبة',
            'language.in' => 'لغة الأسئلة غير صالحة',

            'files.required' => 'يجب رفع ملف واحد على الأقل',
            'files.array' => 'صيغة الملفات غير صحيحة',

            'files.*.required' => 'الملف مطلوب',
            'files.*.file' => 'الملف المرفوع غير صالح',
            'files.*.min' => 'الملف المرفوع فارغ',
            'files.*.max' => 'حجم الملف أكبر من الحد المسموح',
            'files.*.mimes' => 'نوع الملف غير مدعوم',
        ];
    }
}
