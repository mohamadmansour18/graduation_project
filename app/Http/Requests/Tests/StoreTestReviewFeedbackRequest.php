<?php

namespace App\Http\Requests\Tests;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreTestReviewFeedbackRequest extends ApiFormRequest
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
            'vote' => ['required', Rule::in(['yes', 'no'])],
        ];
    }

    public function messages(): array
    {
        return [
            'vote.required' => 'نوع التصويت مطلوب',
            'vote.in' => 'نوع التصويت يجب أن يكون نعم أو لا',
        ];
    }
}
