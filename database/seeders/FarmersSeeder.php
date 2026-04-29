<?php

namespace Database\Seeders;

use App\Models\Farmer;
use App\Models\User;
use Illuminate\Database\Seeder;

class FarmersSeeder extends Seeder
{
    public function run(): void
    {
        $supervisorId = User::where('email', 'supervisor@market.ci')->value('id');

        $farmers = [
            ['identifier' => 'CI-FARM-0001', 'first_name' => 'Konan',   'last_name' => 'Yao',     'phone' => '+2250707000001', 'village' => 'Daloa',     'region' => 'Haut-Sassandra', 'credit_limit' => 500000],
            ['identifier' => 'CI-FARM-0002', 'first_name' => 'Aminata', 'last_name' => 'Traoré',  'phone' => '+2250707000002', 'village' => 'Korhogo',   'region' => 'Poro',           'credit_limit' => 300000],
            ['identifier' => 'CI-FARM-0003', 'first_name' => 'Issa',    'last_name' => 'Diomandé','phone' => '+2250707000003', 'village' => 'Man',       'region' => 'Tonkpi',         'credit_limit' => 200000],
            ['identifier' => 'CI-FARM-0004', 'first_name' => 'Adjoua',  'last_name' => 'Kouamé',  'phone' => '+2250707000004', 'village' => 'Soubré',    'region' => 'Nawa',           'credit_limit' => 750000],
            ['identifier' => 'CI-FARM-0005', 'first_name' => 'Bakary',  'last_name' => 'Coulibaly','phone' => '+2250707000005','village' => 'Bouaké',    'region' => 'Gbêkê',          'credit_limit' => 150000],
            ['identifier' => 'CI-FARM-0006', 'first_name' => 'Mariam',  'last_name' => 'Ouattara','phone' => '+2250707000006', 'village' => 'Ferkessédougou','region' => 'Tchologo',    'credit_limit' => 400000],
            ['identifier' => 'CI-FARM-0007', 'first_name' => 'Sékou',   'last_name' => 'Bamba',   'phone' => '+2250707000007', 'village' => 'Abengourou','region' => 'Indénié-Djuablin','credit_limit' => 600000],
            ['identifier' => 'CI-FARM-0008', 'first_name' => 'Awa',     'last_name' => 'Touré',   'phone' => '+2250707000008', 'village' => 'Divo',      'region' => 'Lôh-Djiboua',    'credit_limit' => 250000],
        ];

        foreach ($farmers as $f) {
            Farmer::updateOrCreate(
                ['identifier' => $f['identifier']],
                array_merge($f, ['is_active' => true, 'current_debt' => 0, 'created_by' => $supervisorId])
            );
        }
    }
}
