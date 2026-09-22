<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    private const CATEGORIAS = ['Eletrônicos', 'Periféricos', 'Informática', 'Móveis', 'Papelaria', 'Vestuário'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => ucfirst($this->faker->words(3, true)),
            'descricao' => $this->faker->sentence(12),
            'preco' => $this->faker->randomFloat(2, 9.9, 4999.9),
            'categoria' => $this->faker->randomElement(self::CATEGORIAS),
            'estoque' => $this->faker->numberBetween(0, 200),
        ];
    }

    public function semEstoque(): static
    {
        return $this->state(fn () => ['estoque' => 0]);
    }
}
