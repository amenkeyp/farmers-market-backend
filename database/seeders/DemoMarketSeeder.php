<?php

namespace Database\Seeders;

use App\Models\Farmer;
use App\Models\Product;
use App\Models\User;
use App\Services\RepaymentService;
use App\Services\TransactionService;
use Illuminate\Database\Seeder;

class DemoMarketSeeder extends Seeder
{
    public function run(TransactionService $tx, RepaymentService $rp): void
    {
        $operator = User::where('email', 'operator@market.ci')->first();
        if (! $operator) {
            return;
        }

        $f1 = Farmer::where('identifier', 'CI-FARM-0001')->first();
        $f4 = Farmer::where('identifier', 'CI-FARM-0004')->first();

        $cacao = Product::where('sku', 'CACAO-001')->first();
        $cafe = Product::where('sku', 'CAFE-001')->first();
        $npk = Product::where('sku', 'NPK-50KG')->first();
        $riz = Product::where('sku', 'RIZ-25KG')->first();

        if (! $f1 || ! $cacao || ! $npk) {
            return;
        }

        // 1) Farmer 1: cash transaction (no debt)
        $tx->checkout([
            'farmer_id' => $f1->id,
            'type' => 'cash',
            'items' => [
                ['product_id' => $cacao->id, 'quantity' => 10],   // 10kg * 1500
                ['product_id' => $riz->id,   'quantity' => 1],    // 1 sac * 13500
            ],
            'notes' => 'Achat comptant démo',
        ], $operator);

        // 2) Farmer 4: credit transaction (creates debt #1)
        $tx->checkout([
            'farmer_id' => $f4->id,
            'type' => 'credit',
            'interest_rate' => 0.05,
            'items' => [
                ['product_id' => $npk->id,   'quantity' => 5],    // 5 * 22000 = 110000
                ['product_id' => $cafe->id,  'quantity' => 20],   // 20 * 1200 = 24000
            ],
            'notes' => 'Crédit intrants - démo',
        ], $operator);

        // 3) Farmer 4: second credit (debt #2, oldest=debt #1 first)
        $tx->checkout([
            'farmer_id' => $f4->id,
            'type' => 'credit',
            'interest_rate' => 0.05,
            'items' => [
                ['product_id' => $cacao->id, 'quantity' => 50],   // 50 * 1500 = 75000
            ],
            'notes' => 'Crédit cacao - démo',
        ], $operator);

        // 4) Farmer 4: partial repayment - should hit oldest debt first
        $rp->repay([
            'farmer_id' => $f4->id,
            'amount' => 50000,
            'method' => 'cash',
            'notes' => 'Acompte 1',
        ], $operator);
    }
}
