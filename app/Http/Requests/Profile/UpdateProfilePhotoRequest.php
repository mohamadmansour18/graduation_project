<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\ApiFormRequest;

class UpdateProfilePhotoRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->query('type'),
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:avatar,cover'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'نوع الصورة مطلوب',
            'type.in' => 'نوع الصورة يجب أن يكون avatar أو cover',

            'photo.required' => 'الصورة مطلوبة',
            'photo.image' => 'الملف المرسل يجب أن يكون صورة',
            'photo.mimes' => 'صيغة الصورة يجب أن تكون jpg أو jpeg أو png أو webp',
            'photo.max' => 'حجم الصورة يجب ألا يتجاوز 4MB',
        ];
    }

}
