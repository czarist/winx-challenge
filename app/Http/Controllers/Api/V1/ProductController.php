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

    public function index(ListProductsRequest $request): JsonResponse
    {
        $products = $this->productService->list($request->filters());

        return ProductResource::collection($products)->response();
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated(), $request->user()->id);

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->find($id);

        return (new ProductResource($product))->response();
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->update($id, $request->validated(), $request->user()->id);

        return (new ProductResource($product))->response();
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->productService->delete($id, $request->user()->id);

        return response()->json(null, 204);
    }
}
