<?php

namespace App\Services\Discounts;

use App\Helpers\CustomHelper;
use App\Models\Product;
use App\Models\Customer;
use App\Models\DiscountRules;

class QuantityDiscountRule implements DiscountRuleInterface
{
    public function apply(Product $product, Customer $customer, int $quantity, int $vendorId, int $orderId): array
    {
        $discounts = DiscountRules::where('type', 'quantity')
            ->where('active', true)
            ->where('min_quantity', '<=', $quantity)
            ->get();

        $applied = [];

        foreach ($discounts as $discount) {
            $applied[] = [
                'rule_id' => $discount->id,
                'amount' => $discount->discount_percent / 100,
            ];

            CustomHelper::log("📦 Quantity discount applied", 'info', [
                'rule_id'        => $discount->id,
                'discount_type'  => $discount->type,
                'discount_value' => $discount->discount_percent . '%',
                'product_id'     => $product->id,
                'product_name'   => $product->name,
                'category'       => optional($product->category)->name,
                'customer_id'    => $customer->id,
                'quantity'       => $quantity,
                'vendor_id'      => $vendorId,
                'order_id'       => $orderId,
            ]);
        }

        return $applied;
    }
}
