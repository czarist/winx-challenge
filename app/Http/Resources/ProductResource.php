<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Product
 *
 * @OA\Schema(
 *     schema="Product",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nome", type="string", example="Teclado mecânico"),
 *     @OA\Property(property="descricao", type="string", nullable=true, example="Switches azuis, layout ABNT2"),
 *     @OA\Property(property="preco", type="number", format="float", example=349.9),
 *     @OA\Property(property="categoria", type="string", example="Periféricos"),
 *     @OA\Property(property="estoque", type="integer", example=42),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'nome'       => $this->nome,
            'descricao'  => $this->descricao,
            'preco'      => (float) $this->preco,
            'categoria'  => $this->categoria,
            'estoque'    => $this->estoque,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
