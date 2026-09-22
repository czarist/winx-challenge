<?php

namespace Tests\Feature\Product;

use App\Enums\ProductLogAction;
use App\Jobs\LogProductActivity;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProductAuditLogTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_creating_a_product_dispatches_an_async_log_job(): void
    {
        Queue::fake();

        $this->actingAs($this->user, 'api')->postJson('/api/v1/products', [
            'nome' => 'Produto novo',
            'preco' => 10,
            'categoria' => 'Teste',
            'estoque' => 1,
        ]);

        Queue::assertPushed(
            LogProductActivity::class,
            fn (LogProductActivity $job) => $job->action() === ProductLogAction::Created,
        );
    }

    public function test_updating_a_product_dispatches_an_async_log_job(): void
    {
        $product = Product::factory()->create();
        Queue::fake();

        $this->actingAs($this->user, 'api')->putJson("/api/v1/products/{$product->id}", ['estoque' => 99]);

        Queue::assertPushed(
            LogProductActivity::class,
            fn (LogProductActivity $job) => $job->action() === ProductLogAction::Updated,
        );
    }

    public function test_deleting_a_product_dispatches_an_async_log_job(): void
    {
        $product = Product::factory()->create();
        Queue::fake();

        $this->actingAs($this->user, 'api')->deleteJson("/api/v1/products/{$product->id}");

        Queue::assertPushed(
            LogProductActivity::class,
            fn (LogProductActivity $job) => $job->action() === ProductLogAction::Deleted,
        );
    }

    public function test_the_log_job_persists_an_audit_entry_when_it_runs(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user, 'api')->deleteJson("/api/v1/products/{$product->id}");

        $this->assertDatabaseHas('product_logs', [
            'product_id' => $product->id,
            'user_id' => $this->user->id,
            'action' => ProductLogAction::Deleted->value,
        ]);
    }
}
