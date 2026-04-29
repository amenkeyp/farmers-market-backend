<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@market.ci'],
            [
                'name' => 'Admin Principal',
                'phone' => '+2250700000001',
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'password' => Hash::make('password'),
            ]
        );

        $supervisor = User::updateOrCreate(
            ['email' => 'supervisor@market.ci'],
            [
                'name' => 'Kouadio Supervisor',
                'phone' => '+2250700000002',
                'role' => User::ROLE_SUPERVISOR,
                'is_active' => true,
                'created_by' => $admin->id,
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'operator@market.ci'],
            [
                'name' => 'Aminata Operator',
                'phone' => '+2250700000003',
                'role' => User::ROLE_OPERATOR,
                'is_active' => true,
                'created_by' => $supervisor->id,
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'operator2@market.ci'],
            [
                'name' => 'Yao Operator',
                'phone' => '+2250700000004',
                'role' => User::ROLE_OPERATOR,
                'is_active' => true,
                'created_by' => $supervisor->id,
                'password' => Hash::make('password'),
            ]
        );
    }
}
