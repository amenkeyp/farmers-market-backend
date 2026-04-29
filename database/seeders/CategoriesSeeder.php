<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            'Cultures de rente' => ['Cacao', 'Café', 'Noix de cajou', 'Hévéa'],
            'Vivriers' => ['Igname', 'Manioc', 'Banane plantain', 'Riz'],
            'Maraîchers' => ['Tomate', 'Piment', 'Aubergine'],
            'Intrants agricoles' => ['Engrais', 'Semences', 'Produits phytosanitaires'],
        ];

        foreach ($tree as $parentName => $children) {
            $parent = Category::updateOrCreate(
                ['slug' => Str::slug($parentName)],
                ['name' => $parentName, 'is_active' => true]
            );
            foreach ($children as $child) {
                Category::updateOrCreate(
                    ['slug' => Str::slug($child)],
                    ['name' => $child, 'parent_id' => $parent->id, 'is_active' => true]
                );
            }
        }
    }
}
