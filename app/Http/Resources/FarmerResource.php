<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FarmerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'identifier' => $this->identifier,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'village' => $this->village,
            'region' => $this->region,
            'credit_limit' => (float) $this->credit_limit,
            'current_debt' => (float) $this->current_debt,
            'available_credit' => (float) $this->available_credit,
            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
