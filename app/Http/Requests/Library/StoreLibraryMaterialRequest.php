<?php

namespace App\Http\Requests\Library;

use App\Enums\LibraryMaterialContentKind;
use App\Enums\TargetLevel;
use App\Enums\VisibilityType;
use App\Http\Requests\ApiFormRequest;
use App\Models\LibraryMaterial;
use Illuminate\Validation\Rule;

class StoreLibraryMaterialRequest extends ApiFormRequest
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
        $contentKind = $this->input('content_kind');

        $assetRules = ['required', 'file'];

        if ($contentKind === LibraryMaterialContentKind::File->value) {
            $assetRules[] = 'mimes:pdf';
            $assetRules[] = 'max:8192'; // 8 MB
        }

        if ($contentKind === LibraryMaterialContentKind::ImageGroup->value) {
            $assetRules[] = 'mimes:jpg,jpeg,png';
            $assetRules[] = 'mimetypes:image/jpeg,image/png';
            $assetRules[] = 'max:2048';
        }

        return [
            'title' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['required', 'string', 'min:3', 'max:250'],

            'content_kind' => [
                'required',
                Rule::enum(LibraryMaterialContentKind::class),
            ],

            'assets' => [
                'required',
                'array',
                $contentKind === LibraryMaterialContentKind::File->value ? 'size:1' : 'min:1',
                $contentKind === LibraryMaterialContentKind::ImageGroup->value ? 'max:3' : '',
            ],

            'assets.*' => $assetRules,

            'interest_ids' => ['required', 'array', 'min:1', 'max:3'],
            'interest_ids.*' => ['required', 'integer', 'distinct', 'exists:interests,id'],

            'target_level' => ['required', 'string', Rule::enum(TargetLevel::class)],

            'visibility_type' => [
                'required',
                Rule::enum(VisibilityType::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان المادة مطلوب.',
            'title.string' => 'عنوان المادة يجب أن يكون نصاً.',
            'title.max' => 'عنوان المادة يجب ألا يزيد عن 100 حرف.',

            'description.required' => 'وصف المادة مطلوب.',
            'description.string' => 'وصف المادة يجب أن يكون نصاً.',
            'description.max' => 'وصف المادة يجب ألا يزيد عن 250 حرف.',

            'content_kind.required' => 'نوع المحتوى مطلوب.',
            'content_kind.Illuminate\Validation\Rules\Enum' => 'نوع المحتوى المحدد غير صالح.',

            'assets.required' => 'حقل الملفات مطلوب.',
            'assets.array' => 'حقل الملفات يجب أن يكون على شكل قائمة.',
            'assets.size' => 'يجب أن يحتوي الحقل assets على ملف واحد فقط عند اختيار نوع المحتوى ملف.',
            'assets.min' => 'يجب أن يحتوي الحقل assets على عنصر واحد على الأقل عند اختيار نوع المحتوى صور.',
            'assets.max' => 'لا يمكن أن يحتوي الحقل assets على أكثر من 3 عناصر عند اختيار نوع المحتوى صور.',
            'assets.*.required' => 'كل ملف في الحقل assets مطلوب.',
            'assets.*.file' => 'كل ملف في الحقل assets يجب أن يكون ملفاً صالحاً.',

            'interests.required' => 'يجب تحديد اهتمام واحد على الأقل.',
            'interests.array' => 'حقل الاهتمامات يجب أن يكون على شكل قائمة.',
            'interests.min' => 'يجب اختيار اهتمام واحد على الأقل.',
            'interests.max' => 'لا يمكن اختيار أكثر من 3 اهتمامات.',
            'interests.*.integer' => 'معرف الاهتمام يجب أن يكون رقماً صحيحاً.',
            'interests.*.exists' => 'أحد الاهتمامات المحددة غير موجود.',

            'target_level.required' => 'المستوى المستهدف مطلوب.',
            'target_level.string' => 'المستوى المستهدف يجب أن يكون نصاً.',
            'target_level.Illuminate\Validation\Rules\Enum' => 'المستوى المستهدف المحدد غير صالح.',

            'visibility_type.required' => 'نوع الرؤية مطلوب.',
            'visibility_type.string' => 'نوع الرؤية يجب أن يكون نصاً.',
            'visibility_type.Illuminate\Validation\Rules\Enum' => 'نوع الرؤية المحدد غير صالح.',
        ];
    }
}
