<?php

namespace App\Services;

use App\DataTransferObjects\ProductFilters;
use App\Enums\ProductLogAction;
use App\Jobs\LogProductActivity;
use App\Jobs\SyncProductSearchIndex;
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

        $this->afterWrite($product->id, $userId, ProductLogAction::Created, $product->toArray());

        return $product;
    }

    public function update(int $id, array $data, int $userId): Product
    {
        $product = $this->repository->findOrFail($id);
        $product = $this->repository->update($product, $data);

        $this->afterWrite($product->id, $userId, ProductLogAction::Updated, $product->toArray());

        return $product;
    }

    public function delete(int $id, int $userId): void
    {
        $product = $this->repository->findOrFail($id);
        $snapshot = $product->toArray();

        $this->repository->delete($product);

        $this->afterWrite($id, $userId, ProductLogAction::Deleted, $snapshot);
    }

    /**
     * Efeitos colaterais de toda escrita em produto: nenhum deles roda de
     * forma síncrona no request, ambos são jobs em fila independentes (o
     * índice de busca pode falhar/ficar indisponível sem afetar o log de
     * auditoria, e vice-versa).
     *
     * @param  array<string, mixed>  $payload
     */
    private function afterWrite(?int $productId, int $userId, ProductLogAction $action, array $payload): void
    {
        LogProductActivity::dispatch($productId, $userId, $action, $payload);

        if ($productId !== null) {
            SyncProductSearchIndex::dispatch($productId, $action);
        }
    }
}
