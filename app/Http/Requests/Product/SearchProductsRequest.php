<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class SearchProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.required' => 'Informe o termo de busca.',
            'q.min' => 'O termo de busca deve ter no mínimo :min caracteres.',
            'per_page.integer' => 'A quantidade de resultados deve ser um número inteiro.',
            'per_page.max' => 'A quantidade de resultados deve ser de no máximo :max.',
        ];
    }
}
