<?php

namespace Database\Seeders;

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DiscountRulesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('discount_rules')->insert([
            [
                'type' => 'category',
                'target' => 'electronics',
                'min_quantity' => null,
                'discount_percent' => 10.00,
                'active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'type' => 'category',
                'target' => 'clothing',
                'min_quantity' => null,
                'discount_percent' => 5.00,
                'active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'type' => 'quantity',
                'target' => null,
                'min_quantity' => 3,
                'discount_percent' => 7.50,
                'active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'type' => 'loyalty',
                'target' => null,
                'min_quantity' => null,
                'discount_percent' => 5.00,
                'active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}


