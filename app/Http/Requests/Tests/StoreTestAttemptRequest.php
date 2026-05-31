<?php

namespace App\Http\Requests\Tests;

use App\Enums\TestAttemptsMode;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreTestAttemptRequest extends ApiFormRequest
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
            'mode' => ['required', Rule::enum(TestAttemptsMode::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'mode.required' => 'نمط اللعب مطلوب',
            'mode.Illuminate\Validation\Rules\Enum' => 'نمط اللعب غير صحيح',
        ];
    }
}
