<?php

namespace App\Http\Requests\Repayment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'farmer_id' => ['required', 'integer', 'exists:farmers,id'],
            // amount is auto-computed when commodity_kg + commodity_rate are given
            'amount' => ['required_without:commodity_kg', 'nullable', 'numeric', 'gt:0'],
            'commodity_kg' => ['nullable', 'numeric', 'gt:0'],
            'commodity_rate' => ['required_with:commodity_kg', 'nullable', 'numeric', 'gt:0'],
            'commodity_name' => ['nullable', 'string', 'max:120'],
            'method' => ['nullable', Rule::in(['cash', 'commodity', 'mobile_money', 'bank'])],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
