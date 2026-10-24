<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

use App\Models\Product;
use App\Services\ProductService;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;


class ProductController extends Controller
{
    /**
     * Create a new ProductController instance.
     *
     * @param ProductService $products
     */
    public function __construct(private readonly ProductService $products) {}

    /**
     * Display a listing of the products.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
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
     * Store a newly created product in storage.
     *
     * @param StoreProductRequest $request
     * @return JsonResponse
     */
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
     * Display the specified product.
     *
     * @param Product $product
     * @return ProductResource
     */
    /**
     * Obtener un producto por ID
     */
    public function show(Product $product): ProductResource
    {
        return ProductResource::make($product->load(['currency', 'prices']));
    }

    /**
     * Update the specified product in storage.
     *
     * @param UpdateProductRequest $request
     * @param Product $product
     * @return ProductResource
     */
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
     * Remove the specified product from storage.
     *
     * @param Product $product
     * @return Response
     */
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
