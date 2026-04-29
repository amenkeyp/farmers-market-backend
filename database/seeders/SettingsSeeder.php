<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('default_interest_rate', 0.05, 'float', 'finance');
        Setting::set('default_currency', 'XOF', 'string', 'finance');
        // Default per-kg rates (FCFA) for common commodities
        Setting::set('commodity.rate_per_kg.CACAO-001', 1500, 'float', 'commodity');
        Setting::set('commodity.rate_per_kg.CAFE-001', 1200, 'float', 'commodity');
        Setting::set('commodity.rate_per_kg.NOIX-CAJOU-001', 850, 'float', 'commodity');
    }
}
