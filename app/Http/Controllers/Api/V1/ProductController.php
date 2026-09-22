<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ListProductsRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\Contracts\ProductServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductServiceInterface $productService,
    ) {}

    #[OA\Get(
        path: '/api/v1/products',
        tags: ['Products'],
        summary: 'Lista produtos com paginação, busca e filtros avançados',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Busca por nome do produto', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'categoria', in: 'query', description: 'Filtra por categoria exata', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'preco_min', in: 'query', description: 'Preço mínimo', schema: new OA\Schema(type: 'number', format: 'float')),
            new OA\Parameter(name: 'preco_max', in: 'query', description: 'Preço máximo', schema: new OA\Schema(type: 'number', format: 'float')),
            new OA\Parameter(name: 'em_estoque', in: 'query', description: 'true retorna apenas produtos com estoque disponível', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Itens por página (1-100)', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Página atual', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada de produtos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(property: 'links', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(ListProductsRequest $request): JsonResponse
    {
        $products = $this->productService->list($request->filters());

        return ProductResource::collection($products)->response();
    }

    #[OA\Post(
        path: '/api/v1/products',
        tags: ['Products'],
        summary: 'Cria um novo produto',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nome', 'preco', 'categoria', 'estoque'],
                properties: [
                    new OA\Property(property: 'nome', type: 'string', example: 'Teclado mecânico'),
                    new OA\Property(property: 'descricao', type: 'string', nullable: true, example: 'Switches azuis, layout ABNT2'),
                    new OA\Property(property: 'preco', type: 'number', format: 'float', example: 349.9),
                    new OA\Property(property: 'categoria', type: 'string', example: 'Periféricos'),
                    new OA\Property(property: 'estoque', type: 'integer', example: 42),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Produto criado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Product')])),
            new OA\Response(response: 401, description: 'Não autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Dados inválidos', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated(), $request->user()->id);

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/products/{id}',
        tags: ['Products'],
        summary: 'Exibe os dados completos de um produto',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Produto encontrado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Product')])),
            new OA\Response(response: 401, description: 'Não autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Produto não encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->find($id);

        return (new ProductResource($product))->response();
    }

    #[OA\Put(
        path: '/api/v1/products/{id}',
        tags: ['Products'],
        summary: 'Atualiza um produto existente',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nome', type: 'string', example: 'Teclado mecânico'),
                    new OA\Property(property: 'descricao', type: 'string', nullable: true, example: 'Switches azuis, layout ABNT2'),
                    new OA\Property(property: 'preco', type: 'number', format: 'float', example: 329.9),
                    new OA\Property(property: 'categoria', type: 'string', example: 'Periféricos'),
                    new OA\Property(property: 'estoque', type: 'integer', example: 30),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Produto atualizado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Product')])),
            new OA\Response(response: 401, description: 'Não autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Produto não encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Dados inválidos', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->update($id, $request->validated(), $request->user()->id);

        return (new ProductResource($product))->response();
    }

    #[OA\Delete(
        path: '/api/v1/products/{id}',
        tags: ['Products'],
        summary: 'Remove um produto',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Produto removido'),
            new OA\Response(response: 401, description: 'Não autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Produto não encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->productService->delete($id, $request->user()->id);

        return response()->json(null, 204);
    }
}
