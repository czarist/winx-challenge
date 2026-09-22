<?php

namespace Tests\Feature\Product;

use App\Jobs\SyncProductSearchIndex;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class ProductTransactionTest extends TestCase
{
    public function test_failed_enqueue_rolls_back_product_writes_and_previously_queued_jobs(): void
    {
        config(['queue.default' => 'database']);
        $this->actingAs(User::factory()->create(), 'api');
        $product = Product::factory()->create(['nome' => 'Original', 'estoque' => 5]);

        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            if ($payload['displayName'] === SyncProductSearchIndex::class) {
                throw new RuntimeException('Simulated queue failure');
            }

            return [];
        });

        try {
            $this->postJson('/api/v1/products', [
                'nome' => 'New product', 'categoria' => 'Test', 'preco' => 10, 'estoque' => 1,
            ])->assertStatus(500);
            $this->assertDatabaseCount('products', 1);
            $this->assertDatabaseCount('jobs', 0);

            $this->putJson('/api/v1/products/'.$product->id, ['estoque' => 99])->assertStatus(500);
            $this->assertDatabaseHas('products', ['id' => $product->id, 'estoque' => 5]);
            $this->assertDatabaseCount('jobs', 0);

            $this->deleteJson('/api/v1/products/'.$product->id)->assertStatus(500);
            $this->assertDatabaseHas('products', ['id' => $product->id]);
            $this->assertDatabaseCount('jobs', 0);
        } finally {
            Queue::createPayloadUsing(null);
        }
    }

    public function test_successful_write_persists_product_and_both_database_jobs(): void
    {
        config(['queue.default' => 'database']);
        $this->actingAs(User::factory()->create(), 'api')->postJson('/api/v1/products', [
            'nome' => 'New product', 'categoria' => 'Test', 'preco' => 10, 'estoque' => 1,
        ])->assertCreated();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('jobs', 2);
        $this->assertDatabaseCount('product_logs', 0);
    }
}
