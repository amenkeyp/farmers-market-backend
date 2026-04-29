<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RepaymentResource extends JsonResource
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
            'amount' => (float) $this->amount,
            'applied_amount' => (float) $this->applied_amount,
            'change_amount' => (float) $this->change_amount,
            'commodity_kg' => $this->commodity_kg !== null ? (float) $this->commodity_kg : null,
            'commodity_rate' => $this->commodity_rate !== null ? (float) $this->commodity_rate : null,
            'commodity_name' => $this->commodity_name,
            'method' => $this->method,
            'paid_at' => $this->paid_at,
            'notes' => $this->notes,
            'allocations' => $this->whenLoaded('debts', function () {
                return $this->debts->map(fn ($d) => [
                    'debt_id' => $d->id,
                    'transaction_id' => $d->transaction_id,
                    'amount_applied' => (float) $d->pivot->amount_applied,
                    'debt_remaining_before' => (float) $d->pivot->debt_remaining_before,
                    'debt_remaining_after' => (float) $d->pivot->debt_remaining_after,
                ]);
            }),
        ];
    }
}
