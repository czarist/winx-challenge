<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Product',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nome', type: 'string', example: 'Teclado mecânico'),
        new OA\Property(property: 'descricao', type: 'string', nullable: true, example: 'Switches azuis, layout ABNT2'),
        new OA\Property(property: 'preco', type: 'number', format: 'float', example: 349.9),
        new OA\Property(property: 'categoria', type: 'string', example: 'Periféricos'),
        new OA\Property(property: 'estoque', type: 'integer', example: 42),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]
/** @mixin Product */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'preco' => (float) $this->preco,
            'categoria' => $this->categoria,
            'estoque' => $this->estoque,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
