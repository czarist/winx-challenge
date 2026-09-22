<?php

namespace App\Models;

use App\Enums\ProductLogAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'user_id', 'action', 'payload'])]
class ProductLog extends Model
{
    protected function casts(): array
    {
        return [
            'action' => ProductLogAction::class,
            'payload' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
