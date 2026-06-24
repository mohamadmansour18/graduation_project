<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class ListDashboardLibraryMaterialsRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort_by' => ['nullable', Rule::in(['latest', 'id', 'type', 'most_liked'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cursor' => ['nullable', 'string'],
        ];
    }
}
