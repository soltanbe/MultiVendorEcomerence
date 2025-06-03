<?php

namespace App\Services\Discounts;

use App\Helpers\CustomHelper;
use App\Models\Product;
use App\Models\Customer;
use App\Models\DiscountRules;

class LoyaltyCustomerDiscountRule implements DiscountRuleInterface
{
    public function apply(Product $product, Customer $customer, int $quantity, int $vendorId, int $orderId): array
    {
        $orderCount = $customer->orders()->count();

        $discounts = DiscountRules::where('type', 'loyalty')
            ->where('active', true)
            ->get();

        $applied = [];

        foreach ($discounts as $discount) {
            if ($orderCount >= $discount->min_quantity) {
                $this->logDiscount($product, $customer, $discount, $orderCount);
                $applied[] = [
                    'rule_id' => $discount->id,
                    'amount' => $discount->discount_percent / 100,
                ];
            }
        }

        return $applied;
    }

    private function logDiscount(Product $product, Customer $customer, DiscountRules $discount, int $orderCount): void
    {
        CustomHelper::log("📦 Loyalty discount applied", 'info', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'customer' => $customer->name,
            'order_count' => $orderCount,
            'min_required' => $discount->min_quantity,
            'discount_percent' => $discount->discount_percent,
            'rule_id' => $discount->id,
        ]);
    }
}
