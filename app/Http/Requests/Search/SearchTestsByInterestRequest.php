<?php

namespace App\Http\Requests\Search;

use App\Enums\TestSearchScope;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SearchTestsByInterestRequest extends ApiFormRequest
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
            'interest_id' => ['required', 'integer', 'exists:interests,id'],

            'q' => ['required', 'string', 'min:1', 'max:100'],

            'per_page' => ['nullable', 'integer', 'min:1', 'max:20'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'interest_id.required' => 'معرف الاهتمام العلمي الذي تريد البحث ضمنه مطلوب',
            'interest_id.integer' => 'معرف الاهتمام العلمي يجب أن يكون رقمًا',
            'interest_id.exists' => 'معرف الاهتمام العلمي غير موجود',

            'q.required' => 'يرجى إدخال كلمة البحث',
            'q.string' => 'كلمة البحث يجب أن تكون نصًا',
            'q.min' => 'كلمة البحث قصيرة جدًا',
            'q.max' => 'كلمة البحث طويلة جدًا',

            'per_page.integer' => 'عدد النتائج في الصفحة يجب أن يكون رقمًا',
            'per_page.min' => 'عدد النتائج في الصفحة يجب أن يكون على الأقل 1',
            'per_page.max' => 'عدد النتائج في الصفحة يجب ألا يتجاوز 20',

            'page.integer' => 'رقم الصفحة يجب أن يكون رقمًا',
            'page.min' => 'رقم الصفحة يجب أن يكون على الأقل 1',
        ];
    }
}
