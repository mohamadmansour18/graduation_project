<?php

namespace App\Http\Requests\Tests;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class FilterTestsRequest extends ApiFormRequest
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
            'scope' => ['required', Rule::in(['my_tests', 'explore'])],

            'type' => ['sometimes', 'nullable', Rule::in(['all', 'public', 'private', 'paid'])],
            'status' => ['sometimes', 'nullable', Rule::in([
                'all',
                'new',
                'under_review',
                'needs_revision',
                'approved',
                'reported',
            ])],

            'language' => ['sometimes', 'nullable', Rule::in(['all', 'arabic', 'english', 'mixed'])],

            'has_timer' => ['sometimes', 'boolean'],

            'questions_count_lte' => ['sometimes', 'integer', Rule::in([10,20,30,40,50,60,70,80,90,100])],

            'pass_mark_lte' => ['sometimes', 'integer', Rule::in([20,30,40,50,60,70,80])],

            'interest_id' => ['sometimes', 'integer', 'exists:interests,id'],

            'per_page' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ];
    }
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('scope') === 'explore') {
                if ($this->filled('status')) {
                    $validator->errors()->add(
                        'status',
                        'لا يمكن استخدام فلتر الحالة في واجهة استكشاف الاختبارات'
                    );
                }

                if ($this->input('type') === 'private') {
                    $validator->errors()->add(
                        'type',
                        'لا يمكن فلترة الاختبارات الخاصة في واجهة الاستكشاف'
                    );
                }
            }
        });
    }

}
