<?php

namespace App\DataTransferObjects;

use App\Models\Product;
use Illuminate\Support\Collection;

final readonly class ProductSearchResult
{
    /**
     * @param  Collection<int, Product>  $products  Ordenados por relevância (_score)
     * @param  array<int, string>  $suggestions
     */
    public function __construct(
        public Collection $products,
        public array $suggestions,
        public int $total,
    ) {}
}
