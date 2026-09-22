<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    public function test_repeated_seeding_preserves_existing_data(): void
    {
        $this->seed();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('products', 50);
        $this->assertSame(10, Product::where('estoque', 0)->count());
        $user = User::where('email', 'teste@productapi.com')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $user->password));
        $user->update(['password' => 'changed-password']);
        $product = Product::firstOrFail();
        $product->update(['nome' => 'Edited product']);

        $this->seed();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('products', 50);
        $this->assertTrue(Hash::check('changed-password', $user->fresh()->password));
        $this->assertSame('Edited product', $product->fresh()->nome);
    }

    public function test_empty_database_is_populated_again(): void
    {
        $this->seed();
        Product::query()->delete();
        User::query()->delete();

        $this->seed();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('products', 50);
        $this->assertDatabaseHas('users', ['email' => 'teste@productapi.com']);
    }

    public function test_existing_products_are_not_supplemented_or_replaced(): void
    {
        $product = Product::factory()->create(['nome' => 'Existing']);

        $this->seed();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'nome' => 'Existing']);
    }
}
