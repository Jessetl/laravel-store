<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'symbol' => $this->currency->symbol,
            'price' => $this->price,
            'taxCost' => $this->tax_cost,
            'manufacturingCost' => $this->manufacturing_cost,
            'prices' => ProductPriceResource::collection($this->whenLoaded('prices')),
            'createdAt' => $this->created_at->format('d/m/Y H:i:s'),
            'updatedAt' => $this->updated_at->format('d/m/Y H:i:s'),
        ];
    }
}
