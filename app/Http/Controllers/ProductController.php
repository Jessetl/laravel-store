<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    /**
     * Obtener lista de productos
     *
     * Paginada, ordenada del más reciente al más antiguo.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['per_page' => ['integer', 'between:1,100']]);

        return ProductResource::collection($this->products->list($request->integer('per_page', 10)));
    }

    /**
     * Crear un nuevo producto
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->products->create($request->validated());

        return ProductResource::make($product)
            ->additional(['message' => 'Product created successfully.'])
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('products.show', $product));
    }

    /**
     * Obtener un producto por ID
     *
     * Incluye sus precios en otras divisas.
     */
    public function show(Product $product): ProductResource
    {
        return ProductResource::make($product->load(['currency', 'prices.currency']));
    }

    /**
     * Actualizar un producto
     *
     * Actualización parcial: solo se modifican los campos enviados.
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        return ProductResource::make($this->products->update($product, $request->validated()));
    }

    /**
     * Eliminar un producto
     *
     * Soft delete: el producto deja de listarse y su historial de precios se conserva.
     */
    public function destroy(Product $product): Response
    {
        $this->products->delete($product);

        return response()->noContent();
    }
}
