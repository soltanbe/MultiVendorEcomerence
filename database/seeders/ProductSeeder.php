<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Product::insert([
            ['name' => 'Laptop', 'category' => 'electronics'],
            ['name' => 'Phone', 'category' => 'electronics'],
            ['name' => 'T-Shirt', 'category' => 'clothing'],
            ['name' => 'Shoes', 'category' => 'clothing'],
            ['name' => 'Book', 'category' => 'books'],
            ['name' => 'Desk', 'category' => 'furniture'],
            ['name' => 'Chair', 'category' => 'furniture'],
            ['name' => 'Headphones', 'category' => 'electronics'],
            ['name' => 'Watch', 'category' => 'accessories'],
            ['name' => 'Backpack', 'category' => 'accessories'],
        ]);
    }
}
