<?php
namespace App\Services\Discounts;

use App\Helpers\CustomHelper;
use App\Models\Product;
use App\Models\Customer;
use App\Models\DiscountRules;

class CategoryDiscountRule implements DiscountRuleInterface
{
    public function apply(Product $product, Customer $customer, int $quantity): float
    {
        if (!$product->category) {
            return 0.0;
        }
        $discounts = DiscountRules::where('type', 'category')
            ->where('active', true)
            ->where('target', $product->category->name)
            ->get();

        $totalDiscount = 0;

        foreach ($discounts as $discount) {
            $totalDiscount += $discount->discount_percent / 100;
            CustomHelper::log("📦 Category discount found", 'info', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'category' => $product->category->name,
                'discount_percent' => $discount->discount_percent,
                'rule_id' => $discount->id,
            ]);
        }

        return min($totalDiscount, 0.5);
    }
}

