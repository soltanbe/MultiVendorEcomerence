<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        \App\Models\Vendor::insert([
            [
                'name' => 'Vendor A',
                'email' => 'vendora@example.com',
                'phone' => '050-1111111',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Vendor B',
                'email' => 'vendorb@example.com',
                'phone' => '050-2222222',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Vendor C',
                'email' => 'vendorc@example.com',
                'phone' => '050-3333333',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Vendor D',
                'email' => 'vendord@example.com',
                'phone' => '050-4444444',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Vendor E',
                'email' => 'vendore@example.com',
                'phone' => '050-555555',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
