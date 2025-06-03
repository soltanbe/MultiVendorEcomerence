<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\DiscountRules;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVendor;
use App\Models\SubOrder;
use App\Models\SubOrderItem;
use App\Models\Vendor;
use App\Services\Discounts\CategoryDiscountRule;
use App\Services\OrderProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_applies_category_discount_rule_from_database()
    {
        // 1. Create category
        $category = Category::create(['name' => 'Electronics']);

        // 2. Insert discount rule into DB
        DiscountRules::create([
            'type' => 'category',
            'target' => 'Electronics',
            'min_quantity' => 1,
            'discount_percent' => 30,
            'active' => true,
        ]);

        // 3. Create customer
        $customer = Customer::create([
            'name' => 'Sultan',
            'email' => 'sultan@example.com',
        ]);

        // 4. Create vendor
        $vendor = Vendor::create([
            'name' => 'BestVendor',
            'phone' => '123',
            'email' => 'vendor@example.com',
        ]);

        // 5. Create product in Electronics category
        $product = Product::create([
            'name' => 'Smartphone',
            'description' => 'Great phone',
            'category_id' => $category->id,
        ]);

        // 6. Link vendor to product
        ProductVendor::create([
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'price' => 1000,
            'stock' => 5,
        ]);

        // 7. Create order + item
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // 8. Register rule loader (if not already)
        app()->bind('discount.rules', function () {
            $rules = [];
            $active = DiscountRules::where('active', true)->get();
            foreach ($active as $rule) {
                switch ($rule->type) {
                    case 'category':
                        $rules[] = new CategoryDiscountRule($rule);
                        break;
                }
            }
            return $rules;
        });

        // 9. Process order
        $service = new OrderProcessingService();
        $service->processOrder($order);

        // 10. Assert discount applied correctly
        $item = SubOrderItem::first();
        $this->assertEquals(1000, $item->unit_price_original);
        $this->assertEquals(700, $item->unit_price); // 30% off
        $this->assertEquals(300, $item->discount_amount);

        $subOrder = SubOrder::first();
        $this->assertEquals(1000, $subOrder->total_amount_original);
        $this->assertEquals(700, $subOrder->total_amount);
        $this->assertEquals('processed', $order->fresh()->status);
    }
    /** @test */
    public function it_applies_loyalty_discount_based_on_order_count()
    {
        // 1. Create category (needed for product)
        $category = Category::create(['name' => 'General']);

        // 2. Insert loyalty discount rule
        DiscountRules::create([
            'type' => 'loyalty',
            'target' => null,
            'min_quantity' => 3, // customer needs at least 3 orders
            'discount_percent' => 20,
            'active' => true,
        ]);

        // 3. Create customer with 3 past orders
        $customer = Customer::create([
            'name' => 'Loyal Sultan',
            'email' => 'sultan@example.com',
        ]);

        for ($i = 0; $i < 3; $i++) {
            Order::create([
                'customer_id' => $customer->id,
                'status' => 'processed',
            ]);
        }

        // 4. Create vendor
        $vendor = Vendor::create([
            'name' => 'Vendor Loyal',
            'phone' => '123',
            'email' => 'vendor@example.com',
        ]);

        // 5. Create product
        $product = Product::create([
            'name' => 'Loyalty Product',
            'description' => 'Special item',
            'category_id' => $category->id,
        ]);

        // 6. Link vendor to product
        ProductVendor::create([
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'price' => 500,
            'stock' => 10,
        ]);

        // 7. Create new order
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // 8. Bind the rule loader (from DB)
        app()->bind('discount.rules', function () {
            $rules = [];
            $active = DiscountRules::where('active', true)->get();
            foreach ($active as $rule) {
                if ($rule->type === 'loyalty') {
                    $rules[] = new \App\Services\Discounts\LoyaltyCustomerDiscountRule();
                }
            }
            return $rules;
        });

        // 9. Process the order
        $service = new OrderProcessingService();
        $service->processOrder($order);

        // 10. Assertions
        $item = SubOrderItem::first();
        $this->assertEquals(500, $item->unit_price_original);
        $this->assertEquals(400, $item->unit_price); // 20% off
        $this->assertEquals(100, $item->discount_amount);

        $subOrder = SubOrder::first();
        $this->assertEquals(500, $subOrder->total_amount_original);
        $this->assertEquals(400, $subOrder->total_amount);
        $this->assertEquals('processed', $order->fresh()->status);
    }

    /** @test */
    public function it_applies_quantity_discount_based_on_item_quantity()
    {
        // 1. Create category
        $category = Category::create(['name' => 'Bulk Items']);

        // 2. Insert quantity-based discount rule (min 5 units = 25%)
        DiscountRules::create([
            'type' => 'quantity',
            'target' => null,
            'min_quantity' => 5,
            'discount_percent' => 25,
            'active' => true,
        ]);

        // 3. Create customer
        $customer = Customer::create([
            'name' => 'Bulk Buyer',
            'email' => 'bulk@example.com',
        ]);

        // 4. Create vendor
        $vendor = Vendor::create([
            'name' => 'BulkVendor',
            'phone' => '555',
            'email' => 'vendor@example.com',
        ]);

        // 5. Create product
        $product = Product::create([
            'name' => 'Paper Box',
            'description' => 'Big box of paper',
            'category_id' => $category->id,
        ]);

        // 6. Link vendor to product
        ProductVendor::create([
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'price' => 200,
            'stock' => 50,
        ]);

        // 7. Create new order with quantity 5 (should trigger discount)
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        // 8. Bind rule loader (from DB)
        app()->bind('discount.rules', function () {
            $rules = [];
            $active = DiscountRules::where('active', true)->get();
            foreach ($active as $rule) {
                if ($rule->type === 'quantity') {
                    $rules[] = new \App\Services\Discounts\QuantityDiscountRule();
                }
            }
            return $rules;
        });

        // 9. Process order
        $service = new OrderProcessingService();
        $service->processOrder($order);

        // 10. Assertions
        $item = SubOrderItem::first();
        $this->assertEquals(200, $item->unit_price_original);
        $this->assertEquals(150, $item->unit_price); // 25% off
        $this->assertEquals(50, $item->discount_amount);

        $subOrder = SubOrder::first();
        $this->assertEquals(1000, $subOrder->total_amount_original); // 200 * 5
        $this->assertEquals(750, $subOrder->total_amount);           // 150 * 5
        $this->assertEquals('processed', $order->fresh()->status);
    }
    /** @test */
    public function it_applies_combined_discounts_up_to_fifty_percent()
    {
        // 1. Create category
        $category = Category::create(['name' => 'Electronics']);

        // 2. Insert 3 discount rules
        DiscountRules::insert([
            [
                'type' => 'category',
                'target' => 'Electronics',
                'min_quantity' => 1,
                'discount_percent' => 20,
                'active' => true,
            ],
            [
                'type' => 'loyalty',
                'target' => null,
                'min_quantity' => 3,
                'discount_percent' => 20,
                'active' => true,
            ],
            [
                'type' => 'quantity',
                'target' => null,
                'min_quantity' => 5,
                'discount_percent' => 15,
                'active' => true,
            ],
        ]);

        // 3. Create customer with 3 previous orders
        $customer = Customer::create([
            'name' => 'Combo Buyer',
            'email' => 'combo@example.com',
        ]);

        for ($i = 0; $i < 3; $i++) {
            Order::create([
                'customer_id' => $customer->id,
                'status' => 'processed',
            ]);
        }

        // 4. Create vendor
        $vendor = Vendor::create([
            'name' => 'ComboVendor',
            'phone' => '123',
            'email' => 'vendor@example.com',
        ]);

        // 5. Create product in Electronics category
        $product = Product::create([
            'name' => 'Laptop Pro',
            'description' => 'Expensive laptop',
            'category_id' => $category->id,
        ]);

        ProductVendor::create([
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'price' => 1000,
            'stock' => 10,
        ]);

        // 6. Create order with quantity = 5
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        // 7. Bind all rules
        app()->bind('discount.rules', function () {
            return [
                new \App\Services\Discounts\CategoryDiscountRule(),
                new \App\Services\Discounts\LoyaltyCustomerDiscountRule(),
                new \App\Services\Discounts\QuantityDiscountRule(),
            ];
        });

        // 8. Process
        $service = new OrderProcessingService();
        $service->processOrder($order);

        // 9. Assert max 50% discount applied
        $item = SubOrderItem::first();
        $this->assertEquals(1000, $item->unit_price_original);
        $this->assertEquals(500, $item->unit_price); // Max 50% off
        $this->assertEquals(500, $item->discount_amount);

        $subOrder = SubOrder::first();
        $this->assertEquals(5000, $subOrder->total_amount_original); // 1000 * 5
        $this->assertEquals(2500, $subOrder->total_amount); // 500 * 5
    }
    /** @test */
    public function it_applies_discount_only_to_eligible_product_in_multi_product_order()
    {
        // 1. Categories
        $catDiscounted = Category::create(['name' => 'Electronics']);
        $catFullPrice = Category::create(['name' => 'Books']);

        // 2. Discount Rules
        DiscountRules::insert([
            [
                'type' => 'category',
                'target' => 'Electronics',
                'min_quantity' => 1,
                'discount_percent' => 20,
                'active' => true,
            ],
            [
                'type' => 'loyalty',
                'target' => null,
                'min_quantity' => 3,
                'discount_percent' => 20,
                'active' => true,
            ],
            [
                'type' => 'quantity',
                'target' => null,
                'min_quantity' => 5,
                'discount_percent' => 15,
                'active' => true,
            ],
        ]);

        // 3. Customer with 3 previous orders
        $customer = Customer::create(['name' => 'Mixed Buyer', 'email' => 'mixed@example.com']);
        for ($i = 0; $i < 3; $i++) {
            Order::create(['customer_id' => $customer->id, 'status' => 'processed']);
        }

        // 4. Vendor
        $vendor = Vendor::create(['name' => 'OneVendor', 'phone' => '999', 'email' => 'one@vendor.com']);

        // 5. Product A – Eligible
        $productA = Product::create([
            'name' => 'Smartphone',
            'description' => 'Eligible product',
            'category_id' => $catDiscounted->id,
        ]);

        ProductVendor::create([
            'product_id' => $productA->id,
            'vendor_id' => $vendor->id,
            'price' => 1000,
            'stock' => 10,
        ]);

        // 6. Product B – Not eligible (category not matching, quantity too low)
        $productB = Product::create([
            'name' => 'Paperback Book',
            'description' => 'Not eligible product',
            'category_id' => $catFullPrice->id,
        ]);

        ProductVendor::create([
            'product_id' => $productB->id,
            'vendor_id' => $vendor->id,
            'price' => 300,
            'stock' => 10,
        ]);

        // 7. Create order with 2 items
        $order = Order::create(['customer_id' => $customer->id, 'status' => 'pending']);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $productA->id,
            'quantity' => 5, // triggers quantity rule
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $productB->id,
            'quantity' => 1, // not eligible
        ]);

        // 8. Bind all rules
        app()->bind('discount.rules', function () {
            return [
                new \App\Services\Discounts\CategoryDiscountRule(),
                new \App\Services\Discounts\LoyaltyCustomerDiscountRule(),
                new \App\Services\Discounts\QuantityDiscountRule(),
            ];
        });

        // 9. Process
        $service = new OrderProcessingService();
        $service->processOrder($order);

        // 10. Check results
        $items = SubOrderItem::get();

        $itemA = $items->firstWhere('product_id', $productA->id);
        $itemB = $items->firstWhere('product_id', $productB->id);

        // A – eligible → full 50% discount
        $this->assertEquals(1000, $itemA->unit_price_original);
        $this->assertEquals(500, $itemA->unit_price);
        $this->assertEquals(500, $itemA->discount_amount);

        // B – not eligible
        $this->assertEquals(300, $itemB->unit_price_original);
        $this->assertEquals(300, $itemB->unit_price);
        $this->assertEquals(0, $itemB->discount_amount);

        // Total
        $subOrder = SubOrder::first();
        $this->assertEquals(5300, $subOrder->total_amount_original); // 1000*5 + 300
        $this->assertEquals(2800, $subOrder->total_amount);          // 500*5 + 300
    }
}
