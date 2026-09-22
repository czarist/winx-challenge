<?php

namespace App\Services\Search\Contracts;

use App\DataTransferObjects\ProductSearchResult;
use App\Models\Product;

interface ProductSearchServiceInterface
{
    public function index(Product $product): void;

    public function remove(int $productId): void;

    public function search(string $query, int $limit = 15): ProductSearchResult;
}
