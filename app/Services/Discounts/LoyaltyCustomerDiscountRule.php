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
                $applied[] = [
                    'rule_id' => $discount->id,
                    'amount' => $discount->discount_percent / 100,
                ];

                CustomHelper::log("🎁 Loyalty discount applied", 'info', [
                    'rule_id'          => $discount->id,
                    'discount_type'    => $discount->type,
                    'discount_value'   => $discount->discount_percent . '%',
                    'min_required'     => $discount->min_quantity,
                    'customer_id'      => $customer->id,
                    'customer_name'    => $customer->name,
                    'order_count'      => $orderCount,
                    'product_id'       => $product->id,
                    'product_name'     => $product->name,
                    'vendor_id'        => $vendorId,
                    'order_id'         => $orderId,
                ]);
            }
        }

        return $applied;
    }
}
