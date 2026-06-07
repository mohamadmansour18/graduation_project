<?php

namespace App\Http\Requests\Library;

use App\Http\Requests\ApiFormRequest;

class ListLibraryBookmarkedUsersRequest extends ApiFormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cursor' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'كلمة البحث يجب أن تكون نصاً',
            'search.max' => 'كلمة البحث طويلة جداً',

            'per_page.integer' => 'عدد العناصر في الصفحة يجب أن يكون رقماً صحيحاً',
            'per_page.min' => 'عدد العناصر في الصفحة غير صالح',
            'per_page.max' => 'لا يمكن جلب أكثر من 50 عنصر في الطلب الواحد',

            'cursor.string' => 'قيمة المؤشر غير صحيحة',
        ];
    }

    public function searchTerm(): ?string
    {
        $search = trim((string) $this->query('search', ''));

        return $search === '' ? null : $search;
    }

    public function perPage(): int
    {
        return (int) $this->query('per_page', 20);
    }
}
