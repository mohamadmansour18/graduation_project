<?php

namespace App\Http\Requests\Tests;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class ListTestReviewsRequest extends ApiFormRequest
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
            'rating' => ['nullable', Rule::in(['all', '5', '4', '3', '2', '1'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.in' => 'قيمة فلترة التقييم غير صحيحة',
            'page.integer' => 'رقم الصفحة يجب أن يكون رقماً صحيحاً',
            'page.min' => 'رقم الصفحة غير صالح',
        ];
    }

    public function ratingFilter(): ?int
    {
        $rating = $this->query('rating', 'all');

        return $rating === 'all' ? null : (int) $rating;
    }
}
