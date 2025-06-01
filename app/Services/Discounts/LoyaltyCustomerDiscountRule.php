<?php
namespace App\Services\Discounts;

use App\Helpers\CustomHelper;
use App\Models\Product;
use App\Models\Customer;
use App\Models\DiscountRules;

class LoyaltyCustomerDiscountRule implements DiscountRuleInterface
{
    public function apply(Product $product, Customer $customer, int $quantity): float
    {
        if (!$customer->is_loyal) {
            return 0.0;
        }
        $discounts = DiscountRules::where('type', 'loyalty')
            ->where('active', true)
            ->get();

        $totalDiscount = 0;

        foreach ($discounts as $discount) {
            $totalDiscount += $discount->discount_percent / 100;
            CustomHelper::log("📦 Loyalty discount found", 'info', [
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

