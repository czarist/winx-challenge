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

    public function __invoke(SearchProductsRequest $request): JsonResponse
    {
        $result = $this->searchService->search(
            $request->string('q')->toString(),
            (int) ($request->input('per_page') ?? 15),
        );

        return response()->json([
            'data'        => ProductResource::collection($result->products),
            'meta'        => ['total' => $result->total],
            'suggestions' => $result->suggestions,
        ]);
    }
}
