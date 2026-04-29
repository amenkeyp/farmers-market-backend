<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'unit' => $this->unit,
            'unit_price' => (float) $this->unit_price,
            'rate_per_kg' => $this->rate_per_kg !== null ? (float) $this->rate_per_kg : null,
            'stock_quantity' => (float) $this->stock_quantity,
            'is_active' => (bool) $this->is_active,
            'description' => $this->description,
            'is_commodity' => $this->isCommodity(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
