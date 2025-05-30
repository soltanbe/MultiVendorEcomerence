<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Customer::insert([
            [
                'name' => 'Sultan',
                'email' => 'sultan@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Noa',
                'email' => 'noa@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Amit',
                'email' => 'amit@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
