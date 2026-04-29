<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    public function run(): void
    {
        $bySlug = fn (string $slug) => Category::where('slug', $slug)->value('id');

        $rows = [
            // Commodity (kg) products - rate_per_kg drives pricing
            ['sku' => 'CACAO-001', 'name' => 'Cacao fève (kg)', 'category_id' => $bySlug('cacao'),
                'unit' => 'kg', 'unit_price' => 0, 'rate_per_kg' => 1500, 'stock_quantity' => 5000],
            ['sku' => 'CAFE-001', 'name' => 'Café Robusta (kg)', 'category_id' => $bySlug('cafe'),
                'unit' => 'kg', 'unit_price' => 0, 'rate_per_kg' => 1200, 'stock_quantity' => 3000],
            ['sku' => 'NOIX-CAJOU-001', 'name' => 'Noix de cajou brute (kg)', 'category_id' => $bySlug('noix-de-cajou'),
                'unit' => 'kg', 'unit_price' => 0, 'rate_per_kg' => 850, 'stock_quantity' => 4000],

            // Unit/sac priced
            ['sku' => 'IGN-50KG', 'name' => 'Igname (sac 50kg)', 'category_id' => $bySlug('igname'),
                'unit' => 'sac', 'unit_price' => 18000, 'stock_quantity' => 200],
            ['sku' => 'MAN-25KG', 'name' => 'Manioc (sac 25kg)', 'category_id' => $bySlug('manioc'),
                'unit' => 'sac', 'unit_price' => 7500, 'stock_quantity' => 150],
            ['sku' => 'RIZ-25KG', 'name' => 'Riz local (sac 25kg)', 'category_id' => $bySlug('riz'),
                'unit' => 'sac', 'unit_price' => 13500, 'stock_quantity' => 300],
            ['sku' => 'PLT-REG', 'name' => 'Banane plantain (régime)', 'category_id' => $bySlug('banane-plantain'),
                'unit' => 'unit', 'unit_price' => 3500, 'stock_quantity' => 500],

            // Intrants
            ['sku' => 'NPK-50KG', 'name' => 'Engrais NPK (sac 50kg)', 'category_id' => $bySlug('engrais'),
                'unit' => 'sac', 'unit_price' => 22000, 'stock_quantity' => 400],
            ['sku' => 'SEM-CACAO', 'name' => 'Semences cacao (sachet)', 'category_id' => $bySlug('semences'),
                'unit' => 'unit', 'unit_price' => 5000, 'stock_quantity' => 250],
        ];

        foreach ($rows as $row) {
            if (! $row['category_id']) {
                continue;
            }
            Product::updateOrCreate(
                ['sku' => $row['sku']],
                array_merge($row, ['is_active' => true])
            );
        }
    }
}
