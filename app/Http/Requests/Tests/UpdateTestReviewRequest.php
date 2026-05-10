<?php

namespace App\Http\Requests\Tests;

use App\Http\Requests\ApiFormRequest;

class UpdateTestReviewRequest extends ApiFormRequest
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
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'review_text' => ['sometimes', 'string', 'min:2', 'max:200'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->has('rating') && ! $this->has('review_text')) {
                $validator->errors()->add(
                    'review',
                    'يجب تعديل عدد النجوم أو نص التقييم على الأقل'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'rating.integer' => 'عدد النجوم يجب أن يكون رقماً صحيحاً',
            'rating.min' => 'أقل تقييم مسموح هو نجمة واحدة',
            'rating.max' => 'أعلى تقييم مسموح هو خمس نجوم',
            'review_text.string' => 'نص التقييم يجب أن يكون نصاً',
            'review_text.min' => 'نص التقييم قصير جداً',
            'review_text.max' => 'نص التقييم طويل جداً',
        ];
    }

}
