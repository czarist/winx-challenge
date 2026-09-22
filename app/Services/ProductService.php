<?php

namespace App\Services;

use App\DataTransferObjects\ProductFilters;
use App\Enums\ProductLogAction;
use App\Jobs\LogProductActivity;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Contracts\ProductServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService implements ProductServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function list(ProductFilters $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    public function find(int $id): Product
    {
        return $this->repository->findOrFail($id);
    }

    public function create(array $data, int $userId): Product
    {
        $product = $this->repository->create($data);

        $this->logActivity($product->id, $userId, ProductLogAction::Created, $product->toArray());

        return $product;
    }

    public function update(int $id, array $data, int $userId): Product
    {
        $product = $this->repository->findOrFail($id);
        $product = $this->repository->update($product, $data);

        $this->logActivity($product->id, $userId, ProductLogAction::Updated, $product->toArray());

        return $product;
    }

    public function delete(int $id, int $userId): void
    {
        $product = $this->repository->findOrFail($id);
        $snapshot = $product->toArray();

        $this->repository->delete($product);

        $this->logActivity($id, $userId, ProductLogAction::Deleted, $snapshot);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function logActivity(?int $productId, int $userId, ProductLogAction $action, array $payload): void
    {
        LogProductActivity::dispatch($productId, $userId, $action, $payload);
    }
}
