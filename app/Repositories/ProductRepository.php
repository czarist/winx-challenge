<?php
namespace App\Repositories;

use App\DataTransferObjects\ProductFilters;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductRepository implements ProductRepositoryInterface
{
    public function paginate(ProductFilters $filters): LengthAwarePaginator
    {
        return $this->applyFilters(Product::query(), $filters)
            ->latest()
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    public function findOrFail(int $id): Product
    {
        return Product::findOrFail($id);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    private function applyFilters(Builder $query, ProductFilters $filters): Builder
    {
        return $query
            ->when($filters->search !== null && $filters->search !== '', fn(Builder $q) => $q->whereRaw(
                'LOWER(nome) LIKE ?',
                ['%' . mb_strtolower($filters->search) . '%'],
            ))
            ->when($filters->categoria !== null && $filters->categoria !== '', fn(Builder $q) => $q->where('categoria', $filters->categoria))
            ->when($filters->precoMin !== null, fn(Builder $q) => $q->where('preco', '>=', $filters->precoMin))
            ->when($filters->precoMax !== null, fn(Builder $q) => $q->where('preco', '<=', $filters->precoMax))
            ->when($filters->emEstoque === true, fn(Builder $q) => $q->where('estoque', '>', 0))
            ->when($filters->emEstoque === false, fn(Builder $q) => $q->where('estoque', '<=', 0));
    }
}
