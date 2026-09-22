<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\SearchProductsRequest;
use App\Http\Resources\ProductResource;
use App\Services\Search\Contracts\ProductSearchServiceInterface;
use Illuminate\Http\JsonResponse;

class ProductSearchController extends Controller
{
    public function __construct(
        private readonly ProductSearchServiceInterface $searchService,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/products/search",
     *     tags={"Products"},
     *     summary="Busca full-text por produtos, com ranking de relevância e sugestões (Elasticsearch)",
     *     description="Diferencial do desafio: busca inteligente via Elasticsearch, com tolerância a erros de digitação, relevância por _score e sugestões de nomes. Complementa (não substitui) o filtro `search` de GET /products, que roda direto no Postgres.",
     *     security={{"bearerAuth"={}}},
     *
     *     @OA\Parameter(name="q", in="query", required=true, description="Termo de busca", @OA\Schema(type="string", minLength=2)),
     *     @OA\Parameter(name="per_page", in="query", description="Quantidade de resultados (1-50)", @OA\Schema(type="integer", default=15)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Resultado da busca",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Product")),
     *             @OA\Property(property="meta", type="object", @OA\Property(property="total", type="integer", example=3)),
     *             @OA\Property(property="suggestions", type="array", @OA\Items(type="string"), example={"mouse gamer", "mouse sem fio"})
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Não autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Dados inválidos", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=503, description="Serviço de busca indisponível", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function __invoke(SearchProductsRequest $request): JsonResponse
    {
        $result = $this->searchService->search(
            $request->string('q')->toString(),
            (int) ($request->input('per_page') ?? 15),
        );

        return response()->json([
            'data' => ProductResource::collection($result->products),
            'meta' => ['total' => $result->total],
            'suggestions' => $result->suggestions,
        ]);
    }
}
