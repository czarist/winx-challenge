<?php

namespace Tests\Feature\Product;

use App\Enums\ProductLogAction;
use App\Jobs\SyncProductSearchIndex;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProductSearchIndexTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_creating_a_product_dispatches_an_async_index_job(): void
    {
        Queue::fake();

        $this->actingAs($this->user, 'api')->postJson('/api/v1/products', [
            'nome' => 'Produto novo',
            'preco' => 10,
            'categoria' => 'Teste',
            'estoque' => 1,
        ]);

        Queue::assertPushed(SyncProductSearchIndex::class);
    }

    public function test_updating_a_product_dispatches_an_async_index_job(): void
    {
        $product = Product::factory()->create();
        Queue::fake();

        $this->actingAs($this->user, 'api')->putJson("/api/v1/products/{$product->id}", ['estoque' => 5]);

        Queue::assertPushed(SyncProductSearchIndex::class);
    }

    public function test_deleting_a_product_dispatches_an_async_removal_job(): void
    {
        $product = Product::factory()->create();
        Queue::fake();

        $this->actingAs($this->user, 'api')->deleteJson("/api/v1/products/{$product->id}");

        Queue::assertPushed(
            SyncProductSearchIndex::class,
            fn (SyncProductSearchIndex $job) => $job->action() === ProductLogAction::Deleted,
        );
    }
}
