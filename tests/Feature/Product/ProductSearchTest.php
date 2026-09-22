<?php

namespace Tests\Feature\Product;

use App\DataTransferObjects\ProductSearchResult;
use App\Models\Product;
use App\Models\User;
use App\Services\Search\Contracts\ProductSearchServiceInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    public function test_guests_cannot_use_the_search_endpoint(): void
    {
        $this->getJson('/api/v1/products/search?q=mouse')->assertUnauthorized();
    }

    public function test_the_search_term_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')->getJson('/api/v1/products/search')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_it_returns_results_and_suggestions_from_the_search_service(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['nome' => 'Mouse Gamer']);

        // O endpoint só faz a ponte entre a request e o serviço de busca;
        // quem decide relevância/sugestões é o Elasticsearch, então aqui
        // testamos a integração com um double em vez de exigir um cluster
        // real no ar (o binding real fica em AppServiceProvider).
        $fake = new class($product) implements ProductSearchServiceInterface
        {
            public function __construct(private readonly Product $product) {}

            public function index(Product $product): void {}

            public function remove(int $productId): void {}

            public function search(string $query, int $limit = 15): ProductSearchResult
            {
                return new ProductSearchResult(
                    products: new Collection([$this->product]),
                    suggestions: ['mouse sem fio'],
                    total: 1,
                );
            }
        };

        $this->app->instance(ProductSearchServiceInterface::class, $fake);

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/products/search?q=mouse');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('suggestions.0', 'mouse sem fio');
    }
}
