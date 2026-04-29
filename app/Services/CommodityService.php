<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;

class CommodityService
{
    /**
     * Resolve the rate (FCFA per kg) for a product. Falls back to settings then config.
     */
    public function resolveRatePerKg(Product $product): float
    {
        if ($product->rate_per_kg !== null && (float) $product->rate_per_kg > 0) {
            return (float) $product->rate_per_kg;
        }

        $key = "commodity.rate_per_kg.{$product->sku}";
        $setting = Setting::get($key);
        if ($setting !== null) {
            return (float) $setting;
        }

        return (float) config('market.default_rate_per_kg', 0);
    }

    /**
     * Compute line total for a product/quantity. Commodity (kg) products use rate_per_kg.
     */
    public function lineTotal(Product $product, float $quantity): array
    {
        if ($product->isCommodity()) {
            $rate = $this->resolveRatePerKg($product);
            return [
                'unit_price' => 0.0,
                'rate_per_kg' => $rate,
                'line_total' => round($quantity * $rate, 2),
            ];
        }

        return [
            'unit_price' => (float) $product->unit_price,
            'rate_per_kg' => null,
            'line_total' => round($quantity * (float) $product->unit_price, 2),
        ];
    }
}
