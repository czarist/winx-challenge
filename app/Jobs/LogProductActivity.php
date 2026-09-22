<?php

namespace App\Jobs;

use App\Enums\ProductLogAction;
use App\Models\ProductLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class LogProductActivity implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly ?int $productId,
        private readonly int $userId,
        private readonly ProductLogAction $action,
        private readonly array $payload,
    ) {}

    public function handle(): void
    {
        ProductLog::create([
            'product_id' => $this->productId,
            'user_id' => $this->userId,
            'action' => $this->action,
            'payload' => $this->payload,
        ]);
    }
}
