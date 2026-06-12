<?php

namespace App\Http\Requests\Folders;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateTestFolderRequest extends ApiFormRequest
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
            'name' => ['sometimes', 'string', 'max:100'],
            'color_code' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            'visibility_type' => ['sometimes', Rule::in(['عام'])],

            'test_ids' => ['sometimes', 'array', 'min:1', 'max:10'],
            'test_ids.*' => ['required', 'integer', 'distinct', 'exists:test,id'],
        ];
    }
}
