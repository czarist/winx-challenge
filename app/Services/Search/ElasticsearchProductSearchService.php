<?php
namespace App\Services\Search;

use App\DataTransferObjects\ProductSearchResult;
use App\Models\Product;
use App\Services\Search\Contracts\ProductSearchServiceInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use RuntimeException;
use Throwable;

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

        $this->writeProduct($product);
    }

    private function writeProduct(Product $product): void
    {

        $this->client->index([
            'index' => $this->index,
            'id'    => (string) $product->id,
            'body'  => [
                'nome'      => $product->nome,
                'descricao' => $product->descricao,
                'categoria' => $product->categoria,
                'preco'     => (float) $product->preco,
                'estoque'   => $product->estoque,
            ],
        ]);
    }

    public function remove(int $productId): void
    {
        try {
            $this->client->delete(['index' => $this->index, 'id' => (string) $productId]);
        } catch (ClientResponseException $e) {
            if ($e->getResponse()->getStatusCode() !== 404) {
                throw $e;
            }
        }
    }

    public function reindex(): void
    {
        $this->ensureIndexExists();

        foreach (Product::query()->lazyById(200) as $product) {
            $this->writeProduct($product);
        }

        $this->refreshIndex();
        $this->removeOrphans();
        $this->refreshIndex();
    }

    private function removeOrphans(): void
    {
        $scrollId = null;
        $failed = false;

        try {
            $response = $this->client->search([
                'index' => $this->index,
                'scroll' => '1m',
                'allow_partial_search_results' => false,
                'body' => [
                    'size' => 200,
                    '_source' => false,
                    'sort' => ['_doc'],
                    'query' => ['match_all' => (object) []],
                ],
            ])->asArray();

            while (true) {
                $scrollId = $response['_scroll_id'] ?? $scrollId;

                if (($response['timed_out'] ?? false) || ($response['_shards']['failed'] ?? 0) > 0) {
                    throw new RuntimeException('A leitura do índice de produtos ficou incompleta.');
                }

                $hits = $response['hits']['hits'] ?? [];

                if ($hits === []) {
                    break;
                }

                $indexedIds = array_column($hits, '_id');
                $existingIds = Product::query()->whereIn('id', $indexedIds)->pluck('id')->all();

                foreach (array_diff($indexedIds, $existingIds) as $productId) {
                    $this->remove((int) $productId);
                }

                $response = $this->client->scroll([
                    'body' => ['scroll_id' => $scrollId, 'scroll' => '1m'],
                ])->asArray();
            }
        } catch (Throwable $e) {
            $failed = true;

            throw $e;
        } finally {
            if ($scrollId !== null) {
                try {
                    $this->client->clearScroll(['body' => ['scroll_id' => [$scrollId]]]);
                } catch (Throwable $e) {
                    if (! $failed) {
                        throw $e;
                    }
                }
            }
        }
    }

    private function refreshIndex(): void
    {
        $response = $this->client->indices()->refresh(['index' => $this->index])->asArray();

        if (($response['_shards']['failed'] ?? 0) > 0) {
            throw new RuntimeException('Não foi possível atualizar todos os shards do índice de produtos.');
        }
    }

    public function search(string $query, int $limit = 15): ProductSearchResult
    {
        $this->ensureIndexExists();

        $response = $this->client->search([
            'index' => $this->index,
            'body'  => [
                'size'    => $limit,
                'query'   => [
                    'multi_match' => [
                        'query'     => $query,
                        'fields'    => ['nome^3', 'categoria^2', 'descricao'],
                        'fuzziness' => 'AUTO',
                    ],
                ],
                'suggest' => [
                    'nome_suggest' => [
                        'prefix'     => $query,
                        'completion' => [
                            'field'           => 'nome.suggest',
                            'size'            => 5,
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
        $hits       = $response['hits']['hits'] ?? [];
        $orderedIds = array_map(fn(array $hit) => (int) $hit['_id'], $hits);

        $products = Product::whereIn('id', $orderedIds)
            ->get()
            ->sortBy(fn(Product $product) => array_search($product->id, $orderedIds, true))
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
            'body'  => [
                'mappings' => [
                    'properties' => [
                        'nome'      => [
                            'type'   => 'text',
                            'fields' => [
                                'suggest' => ['type' => 'completion'],
                            ],
                        ],
                        'descricao' => ['type' => 'text'],
                        'categoria' => ['type' => 'text'],
                        'preco'     => ['type' => 'float'],
                        'estoque'   => ['type' => 'integer'],
                    ],
                ],
            ],
        ]);
    }
}
