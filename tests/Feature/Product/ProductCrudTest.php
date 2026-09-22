<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_can_create_a_product(): void
    {
        $payload = [
            'nome' => 'Teclado mecânico',
            'descricao' => 'Switches azuis',
            'preco' => 349.90,
            'categoria' => 'Periféricos',
            'estoque' => 20,
        ];

        $response = $this->actingAs($this->user, 'api')->postJson('/api/v1/products', $payload);

        $response->assertCreated()->assertJsonPath('data.nome', 'Teclado mecânico');
        $this->assertDatabaseHas('products', ['nome' => 'Teclado mecânico', 'categoria' => 'Periféricos']);
    }

    public function test_cannot_create_a_product_with_invalid_data(): void
    {
        $response = $this->actingAs($this->user, 'api')->postJson('/api/v1/products', [
            'preco' => -10,
            'estoque' => -1,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nome', 'preco', 'categoria', 'estoque']);
    }

    public function test_can_view_a_single_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user, 'api')->getJson("/api/v1/products/{$product->id}");

        $response->assertOk()->assertJsonPath('data.id', $product->id);
    }

    public function test_viewing_a_nonexistent_product_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'api')->getJson('/api/v1/products/999999');

        $response->assertNotFound();
    }

    public function test_can_update_a_product(): void
    {
        $product = Product::factory()->create(['preco' => 100, 'estoque' => 5]);

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/products/{$product->id}", ['preco' => 150.50, 'estoque' => 8]);

        $response->assertOk()->assertJsonPath('data.preco', 150.5);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'estoque' => 8]);
    }

    public function test_can_partially_update_a_product(): void
    {
        $product = Product::factory()->create(['nome' => 'Nome original']);

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/products/{$product->id}", ['estoque' => 42]);

        $response->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'nome' => 'Nome original', 'estoque' => 42]);
    }

    public function test_cannot_update_a_product_with_invalid_data(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/products/{$product->id}", ['preco' => -1]);

        $response->assertUnprocessable()->assertJsonValidationErrors('preco');
    }

    public function test_can_delete_a_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user, 'api')->deleteJson("/api/v1/products/{$product->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_deleting_a_nonexistent_product_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'api')->deleteJson('/api/v1/products/999999');

        $response->assertNotFound();
    }
}
