<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'farmer_id' => $this->farmer_id,
            'farmer' => new FarmerResource($this->whenLoaded('farmer')),
            'operator_id' => $this->operator_id,
            'operator' => new UserResource($this->whenLoaded('operator')),
            'type' => $this->type,
            'status' => $this->status,
            'subtotal' => (float) $this->subtotal,
            'interest_rate' => (float) $this->interest_rate,
            'interest_amount' => (float) $this->interest_amount,
            'total_amount' => (float) $this->total_amount,
            'paid_amount' => (float) $this->paid_amount,
            'notes' => $this->notes,
            'completed_at' => $this->completed_at,
            'items' => TransactionItemResource::collection($this->whenLoaded('items')),
            'debt' => new DebtResource($this->whenLoaded('debt')),
            'created_at' => $this->created_at,
        ];
    }
}
