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

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductServiceInterface $productService,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/products",
     *     tags={"Products"},
     *     summary="Lista produtos com paginação, busca e filtros avançados",
     *     security={{"bearerAuth"={}}},
     *     @OA\Parameter(name="search", in="query", description="Busca por nome do produto", @OA\Schema(type="string")),
     *     @OA\Parameter(name="categoria", in="query", description="Filtra por categoria exata", @OA\Schema(type="string")),
     *     @OA\Parameter(name="preco_min", in="query", description="Preço mínimo", @OA\Schema(type="number", format="float")),
     *     @OA\Parameter(name="preco_max", in="query", description="Preço máximo", @OA\Schema(type="number", format="float")),
     *     @OA\Parameter(name="em_estoque", in="query", description="true retorna apenas produtos com estoque disponível", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="per_page", in="query", description="Itens por página (1-100)", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="page", in="query", description="Página atual", @OA\Schema(type="integer", default=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de produtos",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Product")),
     *             @OA\Property(property="meta", type="object"),
     *             @OA\Property(property="links", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(ListProductsRequest $request): JsonResponse
    {
        $products = $this->productService->list($request->filters());

        return ProductResource::collection($products)->response();
    }

    /**
     * @OA\Post(
     *     path="/api/v1/products",
     *     tags={"Products"},
     *     summary="Cria um novo produto",
     *     security={{"bearerAuth"={}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nome", "preco", "categoria", "estoque"},
     *             @OA\Property(property="nome", type="string", example="Teclado mecânico"),
     *             @OA\Property(property="descricao", type="string", nullable=true, example="Switches azuis, layout ABNT2"),
     *             @OA\Property(property="preco", type="number", format="float", minimum=0, maximum=99999999.99, example=349.9),
     *             @OA\Property(property="categoria", type="string", example="Periféricos"),
     *             @OA\Property(property="estoque", type="integer", minimum=0, maximum=2147483647, example=42)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Produto criado", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Product"))),
     *     @OA\Response(response=401, description="Não autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Dados inválidos", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
     * )
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated(), $request->user()->id);

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{id}",
     *     tags={"Products"},
     *     summary="Exibe os dados completos de um produto",
     *     security={{"bearerAuth"={}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Produto encontrado", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Product"))),
     *     @OA\Response(response=401, description="Não autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Produto não encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->find($id);

        return (new ProductResource($product))->response();
    }

    /**
     * @OA\Put(
     *     path="/api/v1/products/{id}",
     *     tags={"Products"},
     *     summary="Atualiza um produto existente",
     *     security={{"bearerAuth"={}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nome", type="string", example="Teclado mecânico"),
     *             @OA\Property(property="descricao", type="string", nullable=true, example="Switches azuis, layout ABNT2"),
     *             @OA\Property(property="preco", type="number", format="float", minimum=0, maximum=99999999.99, example=329.9),
     *             @OA\Property(property="categoria", type="string", example="Periféricos"),
     *             @OA\Property(property="estoque", type="integer", minimum=0, maximum=2147483647, example=30)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Produto atualizado", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Product"))),
     *     @OA\Response(response=401, description="Não autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Produto não encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Dados inválidos", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
     * )
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->update($id, $request->validated(), $request->user()->id);

        return (new ProductResource($product))->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/products/{id}",
     *     tags={"Products"},
     *     summary="Remove um produto",
     *     security={{"bearerAuth"={}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Produto removido"),
     *     @OA\Response(response=401, description="Não autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Produto não encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->productService->delete($id, $request->user()->id);

        return response()->json(null, 204);
    }
}
