<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

class ProductListingTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_listing_is_paginated_with_a_default_page_size(): void
    {
        Product::factory()->count(20)->create();

        $response = $this->actingAs($this->user, 'api')->getJson('/api/v1/products');

        $response->assertOk()->assertJsonCount(15, 'data');
        $this->assertSame(15, $response->json('meta.per_page'));
    }

    public function test_per_page_query_param_is_respected(): void
    {
        Product::factory()->count(10)->create();

        $response = $this->actingAs($this->user, 'api')->getJson('/api/v1/products?per_page=5');

        $response->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_per_page_above_the_limit_is_rejected(): void
    {
        $response = $this->actingAs($this->user, 'api')->getJson('/api/v1/products?per_page=500');

        $response->assertUnprocessable()->assertJsonValidationErrors('per_page');
    }

    public function test_can_search_products_by_name(): void
    {
        Product::factory()->create(['nome' => 'Mouse Gamer RGB']);
        Product::factory()->create(['nome' => 'Cadeira de escritório']);

        $response = $this->actingAs($this->user, 'api')->getJson('/api/v1/products?search=mouse');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nome', 'Mouse Gamer RGB');
    }

    public function test_can_filter_by_categoria(): void
    {
        Product::factory()->create(['categoria' => 'Periféricos']);
        Product::factory()->create(['categoria' => 'Móveis']);

        $response = $this->actingAs($this->user, 'api')->getJson('/api/v1/products?categoria=Móveis');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.categoria', 'Móveis');
    }

    public function test_can_filter_by_price_range(): void
    {
        Product::factory()->create(['nome' => 'Barato', 'preco' => 10]);
        Product::factory()->create(['nome' => 'Medio', 'preco' => 50]);
        Product::factory()->create(['nome' => 'Caro', 'preco' => 500]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/products?preco_min=20&preco_max=100');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nome', 'Medio');
    }

    public function test_price_max_below_price_min_is_rejected(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/products?preco_min=100&preco_max=10');

        $response->assertUnprocessable()->assertJsonValidationErrors('preco_max');
    }

    public function test_can_filter_by_stock_availability(): void
    {
        Product::factory()->create(['nome' => 'Com estoque', 'estoque' => 5]);
        Product::factory()->create(['nome' => 'Sem estoque', 'estoque' => 0]);

        $inStock = $this->actingAs($this->user, 'api')->getJson('/api/v1/products?em_estoque=true');
        $outOfStock = $this->actingAs($this->user, 'api')->getJson('/api/v1/products?em_estoque=false');

        $inStock->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nome', 'Com estoque');
        $outOfStock->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nome', 'Sem estoque');
    }
}
