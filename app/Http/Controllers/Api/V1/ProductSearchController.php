<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\SearchProductsRequest;
use App\Http\Resources\ProductResource;
use App\Services\Search\Contracts\ProductSearchServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ProductSearchController extends Controller
{
    public function __construct(
        private readonly ProductSearchServiceInterface $searchService,
    ) {}

    #[OA\Get(
        path: '/api/v1/products/search',
        tags: ['Products'],
        summary: 'Busca full-text por produtos, com ranking de relevância e sugestões (Elasticsearch)',
        description: 'Diferencial do desafio: busca inteligente via Elasticsearch, com tolerância a erros de digitação, relevância por _score e sugestões de nomes. Complementa (não substitui) o filtro `search` de GET /products, que roda direto no Postgres.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, description: 'Termo de busca', schema: new OA\Schema(type: 'string', minLength: 2)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Quantidade de resultados (1-50)', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Resultado da busca',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
                        new OA\Property(property: 'meta', properties: [new OA\Property(property: 'total', type: 'integer', example: 3)], type: 'object'),
                        new OA\Property(property: 'suggestions', type: 'array', items: new OA\Items(type: 'string'), example: ['mouse gamer', 'mouse sem fio']),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Dados inválidos', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 503, description: 'Serviço de busca indisponível', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
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
