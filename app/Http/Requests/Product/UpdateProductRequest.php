<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $id = $this->route('product')?->id ?? $this->route('product');
        return [
            'sku' => ['sometimes', 'string', 'max:60', Rule::unique('products', 'sku')->ignore($id)],
            'name' => ['sometimes', 'string', 'max:160'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'unit' => ['sometimes', 'string', 'max:20'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'rate_per_kg' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}
