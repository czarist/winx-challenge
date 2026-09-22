<?php

namespace App\Http\Requests\Product;

use App\DataTransferObjects\ProductFilters;
use Illuminate\Foundation\Http\FormRequest;

class ListProductsRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'preco_min' => ['nullable', 'numeric', 'min:0'],
            'preco_max' => ['nullable', 'numeric', 'min:0', ...($this->filled('preco_min') ? ['gte:preco_min'] : [])],
            'em_estoque' => ['nullable', 'in:true,false,0,1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preco_min.numeric' => 'O preço mínimo deve ser um valor numérico.',
            'preco_max.numeric' => 'O preço máximo deve ser um valor numérico.',
            'preco_max.gte' => 'O preço máximo deve ser maior ou igual ao preço mínimo.',
            'em_estoque.in' => 'O filtro de disponibilidade em estoque deve ser verdadeiro ou falso.',
            'per_page.integer' => 'A quantidade por página deve ser um número inteiro.',
            'per_page.min' => 'A quantidade por página deve ser de no mínimo :min.',
            'per_page.max' => 'A quantidade por página deve ser de no máximo :max.',
        ];
    }

    public function filters(): ProductFilters
    {
        return ProductFilters::fromArray($this->validated());
    }
}
