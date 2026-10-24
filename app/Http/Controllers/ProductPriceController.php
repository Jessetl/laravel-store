<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

use App\Models\Product;
use Dedoc\Scramble\Attributes\Response;
use App\Services\ProductService;
use App\Http\Requests\StoreProductPriceRequest;
use App\Http\Resources\ProductPriceResource;

class ProductPriceController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    /**
     * Display a listing of the product prices for a specific product.
     *
     * Each price includes `converted_price`: the base price of the product converted to that currency using `exchange_rate`.
     * @param Product $product
     * @return AnonymousResourceCollection
     */
    public function index(Product $product): AnonymousResourceCollection
    {
        $product->load(['currency', 'prices.currency']);
        return ProductPriceResource::collection($product->prices);
    }

    /**
     * Store a newly created product price for a specific product.
     *
     * Creates a new price for the product. The base currency of the product is not allowed, and only one price per currency is permitted.
     * @param StoreProductPriceRequest $request
     * @param Product $product
     * @return JsonResponse
     */
    #[Response(409, 'The product already has a price in that currency.', type: 'array{message: string}')]
    public function store(StoreProductPriceRequest $request, Product $product): JsonResponse
    {
        $price = $this->products->addPrice($product, $request->validated());

        return ProductPriceResource::make($price)->response()->setStatusCode(201);
    }
}
