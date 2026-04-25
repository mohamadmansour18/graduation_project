<?php

namespace App\Http\Requests\TestDiscovery;

use App\Http\Requests\ApiFormRequest;
use App\Services\TestDiscovery\Enums\DiscoveryTab;
use Illuminate\Validation\Rules\Enum;

class ListLabTestDiscoveryRequest extends ApiFormRequest
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
            'tab' => [
                'nullable',
                'string',
                new Enum(DiscoveryTab::class),
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'tab' => $this->input('tab', 'trending'),
            'page' => (int) $this->input('page', 1),
        ]);
    }

    public function messages(): array
    {
        return [
            'tab.' . Enum::class => 'قيمة التاب غير صحيحة القيم المسموحة هي: trending أو new أو most_participated',
            'page.integer' => 'رقم الصفحة يجب أن يكون رقمًا صحيحًا',
            'page.min' => 'رقم الصفحة يجب أن يبدأ من 1',
        ];
    }
}
