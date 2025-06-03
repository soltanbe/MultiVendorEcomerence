<?php
namespace App\Services\Discounts;

use App\Models\Product;
use App\Models\Customer;

interface DiscountRuleInterface
{
    public function apply(Product $product, Customer $customer, int $quantity, int $vendorId, int $orderId): array;
}
