<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductPriceRequest;
use App\Http\Resources\ProductPriceResource;
use App\Models\Product;
use App\Services\ProductService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductPriceController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    /**
     * Obtener lista de precios de un producto
     *
     * Cada precio incluye `convertedPrice`: el precio base del producto convertido a esa divisa con `exchange_rate`.
     */
    public function index(Product $product): AnonymousResourceCollection
    {
        $product->load(['currency', 'prices.currency']);

        return ProductPriceResource::collection($product->prices);
    }

    /**
     * Crear un nuevo precio para un producto
     *
     * No se permite la divisa base del producto ni más de un precio por divisa.
     */
    #[Response(409, 'El producto ya tiene un precio en esa divisa.', type: 'array{message: string}')]
    public function store(StoreProductPriceRequest $request, Product $product): JsonResponse
    {
        $price = $this->products->addPrice($product, $request->validated());

        return ProductPriceResource::make($price)->response()->setStatusCode(201);
    }
}
