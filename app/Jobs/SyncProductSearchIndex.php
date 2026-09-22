<?php
namespace App\Jobs;

use App\Enums\ProductLogAction;
use App\Models\Product;
use App\Services\Search\Contracts\ProductSearchServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncProductSearchIndex implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $productId,
        private readonly ProductLogAction $action,
    ) {}

    public function handle(ProductSearchServiceInterface $search): void
    {
        if ($this->action === ProductLogAction::Deleted) {
            $search->remove($this->productId);

            return;
        }

        $product = Product::find($this->productId);

        if ($product !== null) {
            $search->index($product);
        }
    }

    public function action(): ProductLogAction
    {
        return $this->action;
    }
}
