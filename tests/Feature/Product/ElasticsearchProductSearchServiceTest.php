<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Services\Search\ElasticsearchProductSearchService;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ElasticsearchProductSearchServiceTest extends TestCase
{
    private array $requests = [];

    public function test_removing_a_missing_document_is_idempotent(): void
    {
        $this->searchService([$this->response(404)])->remove(12);

        $this->assertSame('DELETE', $this->requests[0]['request']->getMethod());
        $this->assertSame('/products-test/_doc/12', $this->requests[0]['request']->getUri()->getPath());
    }

    #[DataProvider('failedDeleteStatuses')]
    public function test_delete_failures_are_propagated_for_queue_retries(int $status, string $exception): void
    {
        $search = $this->searchService([$this->response($status)]);

        $this->expectException($exception);
        $this->expectExceptionCode($status);

        $search->remove(12);
    }

    public static function failedDeleteStatuses(): array
    {
        return [
            'forbidden' => [403, ClientResponseException::class],
            'rate limited' => [429, ClientResponseException::class],
            'unavailable' => [503, ServerResponseException::class],
        ];
    }

    public function test_reindex_updates_products_and_removes_orphans_across_all_scroll_pages(): void
    {
        $first = Product::factory()->create(['nome' => 'Nome atualizado']);
        $second = Product::factory()->create();

        $this->searchService([
            $this->response(), // Existing index.
            $this->response(),
            $this->response(),
            $this->response(), // Refresh before scanning.
            $this->page('page-1', [$first->id, 900]),
            $this->response(), // Remove orphan 900.
            $this->page('page-2', [$second->id, 901]),
            $this->response(), // Remove orphan 901.
            $this->page('page-3', []),
            $this->response(), // Clear scroll.
            $this->response(), // Refresh after deleting.
        ]);

        $this->artisan('products:reindex')->assertSuccessful();

        $this->assertSame(['/products-test/_doc/900', '/products-test/_doc/901', '/_search/scroll'], $this->pathsFor('DELETE'));
        $this->assertSame(['/products-test/_doc/'.$first->id, '/products-test/_doc/'.$second->id], $this->pathsFor('PUT'));
        $this->assertSame('Nome atualizado', $this->bodyAt(1)['nome']);
        $this->assertSame(['scroll_id' => ['page-3']], $this->bodyAt(9));
        $this->assertSame('/products-test/_refresh', $this->requests[10]['request']->getUri()->getPath());
    }

    public function test_reindex_removes_every_indexed_product_when_the_database_is_empty(): void
    {
        $this->searchService([
            $this->response(),
            $this->response(),
            $this->page('page-1', [900, 901]),
            $this->response(),
            $this->response(),
            $this->page('page-2', []),
            $this->response(),
            $this->response(),
        ]);

        $this->artisan('products:reindex')->assertSuccessful();

        $this->assertSame(['/products-test/_doc/900', '/products-test/_doc/901', '/_search/scroll'], $this->pathsFor('DELETE'));
        $this->assertSame([], $this->pathsFor('PUT'));
    }

    public function test_reindex_creates_a_searchable_empty_index_when_both_stores_are_empty(): void
    {
        $this->searchService([
            $this->response(404),
            $this->response(),
            $this->response(),
            $this->page('page-1', []),
            $this->response(),
            $this->response(),
        ]);

        $this->artisan('products:reindex')->assertSuccessful();

        $this->assertSame(['/products-test'], $this->pathsFor('PUT'));
        $this->assertSame('completion', $this->bodyAt(1)['mappings']['properties']['nome']['fields']['suggest']['type']);
    }

    public function test_reindex_does_not_remove_anything_when_product_indexing_fails(): void
    {
        Product::factory()->create();
        $search = $this->searchService([$this->response(), $this->response(429)]);

        try {
            $search->reindex();
            $this->fail('Reindex must fail when a product cannot be indexed.');
        } catch (ClientResponseException $e) {
            $this->assertSame(429, $e->getCode());
        }

        $this->assertSame([], $this->pathsFor('DELETE'));
    }

    public function test_reindex_propagates_orphan_delete_failure_and_releases_the_scroll(): void
    {
        $search = $this->searchService([
            $this->response(),
            $this->response(),
            $this->page('page-1', [900]),
            $this->response(403),
            $this->response(),
        ]);

        try {
            $search->reindex();
            $this->fail('Reindex must fail when an orphan cannot be removed.');
        } catch (ClientResponseException $e) {
            $this->assertSame(403, $e->getCode());
        }

        $this->assertSame(['scroll_id' => ['page-1']], $this->bodyAt(4));
    }

    public function test_reindex_rejects_partial_search_results_and_releases_the_scroll(): void
    {
        $search = $this->searchService([
            $this->response(),
            $this->response(),
            $this->response(200, ['_scroll_id' => 'page-1', 'timed_out' => true]),
            $this->response(),
        ]);

        try {
            $search->reindex();
            $this->fail('Reindex must fail when Elasticsearch times out.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('incompleta', $e->getMessage());
        }

        $this->assertSame(['/_search/scroll'], $this->pathsFor('DELETE'));
    }

    public function test_scroll_cleanup_failure_does_not_hide_the_original_delete_failure(): void
    {
        $search = $this->searchService([
            $this->response(),
            $this->response(),
            $this->page('page-1', [900]),
            $this->response(403),
            $this->response(503),
        ]);

        $this->expectException(ClientResponseException::class);
        $this->expectExceptionCode(403);

        $search->reindex();
    }

    private function searchService(array $responses): ElasticsearchProductSearchService
    {
        config(['elasticsearch.products_index' => 'products-test']);
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($this->requests));
        $client = ClientBuilder::create()
            ->setHosts(['http://elasticsearch.test:9200'])
            ->setHttpClient(new HttpClient(['handler' => $handler]))
            ->setRetries(0)
            ->build();
        $this->app->instance(Client::class, $client);

        return new ElasticsearchProductSearchService($client);
    }

    private function response(int $status = 200, array $body = []): Response
    {
        return new Response($status, ['Content-Type' => 'application/json', 'X-Elastic-Product' => 'Elasticsearch'], json_encode($body));
    }

    private function page(string $scrollId, array $ids): Response
    {
        return $this->response(200, [
            '_scroll_id' => $scrollId,
            'hits' => ['hits' => array_map(fn ($id) => ['_id' => (string) $id], $ids)],
        ]);
    }

    private function pathsFor(string $method): array
    {
        return array_values(array_map(
            fn (array $entry) => $entry['request']->getUri()->getPath(),
            array_filter($this->requests, fn (array $entry) => $entry['request']->getMethod() === $method),
        ));
    }

    private function bodyAt(int $offset): array
    {
        return json_decode((string) $this->requests[$offset]['request']->getBody(), true);
    }
}
