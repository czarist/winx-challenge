<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use Tests\TestCase;

class ProductAuthorizationTest extends TestCase
{
    public function test_guests_cannot_list_products(): void
    {
        $this->getJson('/api/v1/products')->assertUnauthorized();
    }

    public function test_guests_cannot_view_a_product(): void
    {
        $product = Product::factory()->create();

        $this->getJson("/api/v1/products/{$product->id}")->assertUnauthorized();
    }

    public function test_guests_cannot_create_a_product(): void
    {
        $this->postJson('/api/v1/products', [])->assertUnauthorized();
    }

    public function test_guests_cannot_update_a_product(): void
    {
        $product = Product::factory()->create();

        $this->putJson("/api/v1/products/{$product->id}", [])->assertUnauthorized();
    }

    public function test_guests_cannot_delete_a_product(): void
    {
        $product = Product::factory()->create();

        $this->deleteJson("/api/v1/products/{$product->id}")->assertUnauthorized();
    }
}
