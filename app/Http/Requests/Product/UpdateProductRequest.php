<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'descricao' => ['sometimes', 'nullable', 'string'],
            'preco' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999999.99'],
            'categoria' => ['sometimes', 'required', 'string', 'max:100'],
            'estoque' => ['sometimes', 'required', 'integer', 'min:0', 'max:2147483647'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do produto é obrigatório.',
            'nome.max' => 'O nome pode ter no máximo :max caracteres.',
            'preco.required' => 'O preço é obrigatório.',
            'preco.numeric' => 'O preço deve ser um valor numérico.',
            'preco.min' => 'O preço não pode ser negativo.',
            'preco.max' => 'O preço não pode ultrapassar :max.',
            'categoria.required' => 'A categoria é obrigatória.',
            'categoria.max' => 'A categoria pode ter no máximo :max caracteres.',
            'estoque.required' => 'A quantidade em estoque é obrigatória.',
            'estoque.integer' => 'A quantidade em estoque deve ser um número inteiro.',
            'estoque.min' => 'A quantidade em estoque não pode ser negativa.',
            'estoque.max' => 'A quantidade em estoque não pode ultrapassar :max.',
        ];
    }
}
