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
            CustomHelper::log("⚠️ Skipping category discount — Product #{$product->id} has no category.", 'warn', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'order_id' => $orderId,
            ]);
            return $applied;
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

            CustomHelper::log("🏷️ Category discount applied", 'info', [
                'rule_id' => $discount->id,
                'discount_type' => $discount->type,
                'discount_value' => "{$discount->discount_percent}%",
                'category_name' => $product->category->name,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'vendor_id' => $vendorId,
                'order_id' => $orderId,
            ]);
        }

        return $applied;
    }
}
