<?php

namespace App\Providers;

use App\OpenApi\GeneratorFactory;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\ProductRepository;
use App\Services\AuthService;
use App\Services\Contracts\AuthServiceInterface;
use App\Services\Contracts\ProductServiceInterface;
use App\Services\ProductService;
use App\Services\Search\Contracts\ProductSearchServiceInterface;
use App\Services\Search\ElasticsearchProductSearchService;
use App\Services\Search\NullProductSearchService;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use L5Swagger\GeneratorFactory as BaseGeneratorFactory;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(
            ProductSearchServiceInterface::class,
            $this->app->environment('testing') ? NullProductSearchService::class : ElasticsearchProductSearchService::class,
        );

        $this->app->singleton(Client::class, fn () => ClientBuilder::create()
            ->setHosts(config('elasticsearch.hosts'))
            ->build());

        // Restores docblock-style @OA\* annotation scanning for the Swagger
        // docs (see App\OpenApi\GeneratorFactory for why this can't just be
        // a config value).
        $this->app->bind(BaseGeneratorFactory::class, GeneratorFactory::class);
    }

    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
    }
}
