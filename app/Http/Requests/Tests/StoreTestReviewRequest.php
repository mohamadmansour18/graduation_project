<?php

namespace App\Http\Requests\Tests;

use App\Http\Requests\ApiFormRequest;

class StoreTestReviewRequest extends ApiFormRequest
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
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review_text' => ['required', 'string', 'min:10', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'عدد نجوم التقييم مطلوب',
            'rating.integer' => 'عدد النجوم يجب أن يكون رقماً صحيحاً',
            'rating.min' => 'أقل تقييم مسموح هو نجمة واحدة',
            'rating.max' => 'أعلى تقييم مسموح هو خمس نجوم',
            'review_text.required' => 'نص التقييم مطلوب',
            'review_text.string' => 'نص التقييم يجب أن يكون نصاً',
            'review_text.min' => 'نص التقييم قصير جداً',
            'review_text.max' => 'نص التقييم طويل جداً',
        ];
    }
}
