<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->product->name,
            'price' => $this->price,
            'baseExchangeRate' => $this->product->currency?->exchange_rate,
            'exchangeRate' => $this->currency?->exchange_rate,
            'symbol' => $this->currency?->symbol,
            'convertedPrice' => $this->product->currency?->convert($this->product->price, $this->currency),
            'createdAt' => $this->created_at?->format('d/m/Y H:i:s'),
            'updatedAt' => $this->updated_at?->format('d/m/Y H:i:s'),
        ];
    }
}
