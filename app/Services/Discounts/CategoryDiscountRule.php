<?php

namespace App\Services\Discounts;

use App\Helpers\CustomHelper;
use App\Models\Product;
use App\Models\Customer;
use App\Models\DiscountRules;

class CategoryDiscountRule implements DiscountRuleInterface
{
    public function apply(Product $product, Customer $customer, int $quantity, int $vendorId, int $orderId): array
    {
        $applied = [];

        if (!$product->category) {
            return $applied; // []
        }

        $discounts = DiscountRules::where('type', 'category')
            ->where('active', true)
            ->where('target', $product->category->name)
            ->get();

        foreach ($discounts as $discount) {
            $amount = $discount->discount_percent / 100;

            $applied[] = [
                'rule_id' => $discount->id,
                'amount' => $amount,
            ];

            CustomHelper::log("📦 Category discount found", 'info', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'category' => $product->category->name,
                'discount_percent' => $discount->discount_percent,
                'rule_id' => $discount->id,
            ]);
        }

        return $applied;
    }
}
