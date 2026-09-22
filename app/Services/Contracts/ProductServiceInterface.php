<?php

namespace App\Services\Contracts;

use App\DataTransferObjects\ProductFilters;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductServiceInterface
{
    public function list(ProductFilters $filters): LengthAwarePaginator;

    public function find(int $id): Product;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $userId): Product;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, int $userId): Product;

    public function delete(int $id, int $userId): void;
}
