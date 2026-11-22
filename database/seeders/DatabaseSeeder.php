<?php

namespace Database\Seeders;

use App\Models\Garage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@garasi.test'],
            [
                'nik'                 => '0000000000000001',
                'name'                => 'Admin Garasi',
                'phone'               => '081200000001',
                'password'            => Hash::make('password'),
                'role'                => 'admin',
                'verification_status' => 'approved',
            ]
        );

        $garages = [
            ['code' => 'S-001', 'type' => 'S', 'daily_rate' => 10000, 'kwh_rate' => 0],
            ['code' => 'C-001', 'type' => 'C', 'daily_rate' => 25000, 'kwh_rate' => 0],
            ['code' => 'E-001', 'type' => 'E', 'daily_rate' => 50000, 'kwh_rate' => 2500],
        ];

        foreach ($garages as $garage) {
            Garage::firstOrCreate(
                ['code' => $garage['code']],
                $garage
            );
        }
    }
}