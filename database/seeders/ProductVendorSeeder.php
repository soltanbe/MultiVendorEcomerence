<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Vendor;

class ProductVendorSeeder extends Seeder
{
    public function run()
    {
        $vendorIds = Vendor::pluck('id')->toArray();
        $productIds = Product::pluck('id')->toArray();
        $vendorCount = count($vendorIds);

        foreach ($productIds as $productId) {
            $minVendors = min(2, $vendorCount);
            $maxVendors = max($vendorCount, floor($vendorCount / 2));

            $numVendorsToAssign = rand($minVendors, $maxVendors);
            $assignedVendors = collect($vendorIds)->random($numVendorsToAssign);

            foreach ($assignedVendors as $vendorId) {
                $exists = DB::table('product_vendor')
                    ->where('product_id', $productId)
                    ->where('vendor_id', $vendorId)
                    ->exists();

                if (!$exists) {
                    DB::table('product_vendor')->insert([
                        'product_id' => $productId,
                        'vendor_id' => $vendorId,
                        'price' => rand(50, 500),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
