<?php
namespace App\Services\Search;

use App\DataTransferObjects\ProductSearchResult;
use App\Models\Product;
use App\Services\Search\Contracts\ProductSearchServiceInterface;
use Illuminate\Support\Collection;

class NullProductSearchService implements ProductSearchServiceInterface
{
    public function index(Product $product): void
    {
        //
    }

    public function remove(int $productId): void
    {
        //
    }

    public function search(string $query, int $limit = 15): ProductSearchResult
    {
        return new ProductSearchResult(new Collection, [], 0);
    }
}
