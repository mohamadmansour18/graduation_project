<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class MyProfileBookmarksRequest extends ApiFormRequest
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
            'tab' => ['nullable', Rule::in(['tests', 'materials', 'folders'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function tab(): string
    {
        return $this->validated('tab', 'tests');
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', 20);
    }
}
