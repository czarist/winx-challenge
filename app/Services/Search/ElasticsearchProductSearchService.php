<?php

namespace App\Services\Search;

use App\DataTransferObjects\ProductSearchResult;
use App\Models\Product;
use App\Services\Search\Contracts\ProductSearchServiceInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;

class ElasticsearchProductSearchService implements ProductSearchServiceInterface
{
    private readonly string $index;

    public function __construct(private readonly Client $client)
    {
        $this->index = config('elasticsearch.products_index');
    }

    public function index(Product $product): void
    {
        $this->ensureIndexExists();

        $this->client->index([
            'index' => $this->index,
            'id' => (string) $product->id,
            'body' => [
                'nome' => $product->nome,
                'descricao' => $product->descricao,
                'categoria' => $product->categoria,
                'preco' => (float) $product->preco,
                'estoque' => $product->estoque,
            ],
        ]);
    }

    public function remove(int $productId): void
    {
        try {
            $this->client->delete(['index' => $this->index, 'id' => (string) $productId]);
        } catch (ClientResponseException) {
            // Já não estava no índice (404) — nada a fazer.
        }
    }

    public function search(string $query, int $limit = 15): ProductSearchResult
    {
        $this->ensureIndexExists();

        $response = $this->client->search([
            'index' => $this->index,
            'body' => [
                'size' => $limit,
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => ['nome^3', 'categoria^2', 'descricao'],
                        'fuzziness' => 'AUTO',
                    ],
                ],
                'suggest' => [
                    'nome_suggest' => [
                        'prefix' => $query,
                        'completion' => [
                            'field' => 'nome.suggest',
                            'size' => 5,
                            'skip_duplicates' => true,
                        ],
                    ],
                ],
            ],
        ])->asArray();

        return $this->toResult($response);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function toResult(array $response): ProductSearchResult
    {
        $hits = $response['hits']['hits'] ?? [];
        $orderedIds = array_map(fn (array $hit) => (int) $hit['_id'], $hits);

        // Os ids/score vêm do Elasticsearch, mas os dados retornados são
        // buscados de novo no Postgres — fonte da verdade — para nunca
        // devolver um produto desatualizado em relação ao índice.
        $products = Product::whereIn('id', $orderedIds)
            ->get()
            ->sortBy(fn (Product $product) => array_search($product->id, $orderedIds, true))
            ->values();

        $suggestions = collect($response['suggest']['nome_suggest'][0]['options'] ?? [])
            ->pluck('text')
            ->unique()
            ->values()
            ->all();

        return new ProductSearchResult(
            products: $products,
            suggestions: $suggestions,
            total: (int) ($response['hits']['total']['value'] ?? count($hits)),
        );
    }

    private function ensureIndexExists(): void
    {
        if ($this->client->indices()->exists(['index' => $this->index])->asBool()) {
            return;
        }

        $this->client->indices()->create([
            'index' => $this->index,
            'body' => [
                'mappings' => [
                    'properties' => [
                        'nome' => [
                            'type' => 'text',
                            'fields' => [
                                // Multi-field: mesmo valor de "nome", indexado
                                // também como completion suggester para as
                                // sugestões de busca.
                                'suggest' => ['type' => 'completion'],
                            ],
                        ],
                        'descricao' => ['type' => 'text'],
                        'categoria' => ['type' => 'text'],
                        'preco' => ['type' => 'float'],
                        'estoque' => ['type' => 'integer'],
                    ],
                ],
            ],
        ]);
    }
}
