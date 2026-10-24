<?php

namespace App\Services;

use App\Exceptions\DuplicateProductPrice;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;

use App\Models\Product;
use App\Models\ProductPrice;


final class ProductService
{
    public function list(int $perPage): LengthAwarePaginator
    {
        return Product::with('currency')->latest('id')->paginate($perPage);
    }

    public function create(array $data): Product
    {
        return Product::create($data)->load('currency');
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->load('currency');
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function addPrice(Product $product, array $data): ProductPrice
    {
        try {
            return $product->prices()->create($data)->load('currency');
        } catch (UniqueConstraintViolationException) {
            throw new DuplicateProductPrice;
        }
    }
}
