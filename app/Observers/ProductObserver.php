<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

final class ProductObserver
{
    public function created(Product $product): void
    {
        Log::info('Product created', ['id' => $product->id]);
    }

    public function updated(Product $product): void
    {
        Log::info('Product updated', ['id' => $product->id, 'changes' => $product->getChanges()]);
    }

    public function deleted(Product $product): void
    {
        Log::info('Product deleted', ['id' => $product->id]);
    }
}
