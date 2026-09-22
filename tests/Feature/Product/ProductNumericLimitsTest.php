<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

class ProductNumericLimitsTest extends TestCase
{
    public function test_create_and_update_reject_values_that_exceed_database_limits(): void
    {
        $this->actingAs(User::factory()->create(), 'api');
        $product = Product::factory()->create(['preco' => 10, 'estoque' => 1]);
        $payload = ['nome' => 'Limits', 'categoria' => 'Test', 'preco' => 10, 'estoque' => 1];

        foreach ([['preco', 100000000], ['preco', '99999999.995'], ['estoque', 2147483648]] as [$field, $value]) {
            $this->postJson('/api/v1/products', array_replace($payload, [$field => $value]))
                ->assertUnprocessable()->assertJsonValidationErrors($field);
            $this->patchJson('/api/v1/products/'.$product->id, [$field => $value])
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'preco' => 10, 'estoque' => 1]);
    }

    public function test_create_and_update_accept_the_database_limits(): void
    {
        $this->actingAs(User::factory()->create(), 'api');
        $response = $this->postJson('/api/v1/products', [
            'nome' => 'Limits', 'categoria' => 'Test', 'preco' => '99999999.99', 'estoque' => 2147483647,
        ])->assertCreated()->assertJsonPath('data.preco', 99999999.99)->assertJsonPath('data.estoque', 2147483647);

        $this->patchJson('/api/v1/products/'.$response->json('data.id'), [
            'preco' => '99999999.99', 'estoque' => 2147483647,
        ])->assertOk()->assertJsonPath('data.preco', 99999999.99)->assertJsonPath('data.estoque', 2147483647);
    }
}
