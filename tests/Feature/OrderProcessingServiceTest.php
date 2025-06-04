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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_processes_order_with_multiple_products_and_creates_sub_orders()
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'loyalty_score' => 5
        ]);

        $vendorA = Vendor::create([
            'name' => 'Vendor A',
            'email' => 'vendorA@example.com'
        ]);

        $vendorB = Vendor::create([
            'name' => 'Vendor B',
            'email' => 'vendorB@example.com'
        ]);

        $category = Category::create(['name' => 'Electronics']);

        $product1 = Product::create([
            'name' => 'Laptop',
            'category_id' => $category->id,
            'description' => 'High-end laptop'
        ]);

        $product2 = Product::create([
            'name' => 'Phone',
            'category_id' => $category->id,
            'description' => 'Smartphone with AMOLED display'
        ]);

        ProductVendor::create([
            'product_id' => $product1->id,
            'vendor_id' => $vendorA->id,
            'price' => 2000,
            'stock' => 5,
        ]);

        ProductVendor::create([
            'product_id' => $product2->id,
            'vendor_id' => $vendorB->id,
            'price' => 1000,
            'stock' => 5,
        ]);

        $order = Order::create(['customer_id' => $customer->id]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 3,
        ]);

        $service = new \App\Services\OrderProcessingService();
        $service->processOrder($order);

        $order->refresh();
        $this->assertEquals('processed', $order->status);

        $this->assertDatabaseCount('sub_orders', 2);
        $this->assertDatabaseCount('sub_order_items', 2);

        $subOrder1 = SubOrder::where('vendor_id', $vendorA->id)->first();
        $this->assertNotNull($subOrder1);
        $this->assertEquals($order->id, $subOrder1->order_id);
    }

    /** @test */
    public function it_fails_if_product_has_no_vendor()
    {

        $category = Category::create(['name' => 'Test Category']);
        $product = Product::create([
            'name' => 'Abandoned Product',
            'category_id' => $category->id,
            'description' => 'No vendor available'
        ]);

        $customer = Customer::create([
            'name' => 'Lonely Customer',
            'email' => 'lonely@example.com',
            'loyalty_score' => 0,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseMissing('product_vendor', [
            'product_id' => $product->id,
        ]);

        $service = new OrderProcessingService();
        $service->processOrder($order);

        $order->refresh();
        $this->assertEquals('failed', $order->status);

        $this->assertDatabaseCount('sub_orders', 0);
        $this->assertDatabaseCount('sub_order_items', 0);
    }
    /** @test */
    public function it_applies_discounts_and_saves_notification()
    {
        DB::table('discount_rules')->insert([
            [
                'type' => 'category',
                'target' => 'electronics',
                'min_quantity' => null,
                'discount_percent' => 10.00,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'quantity',
                'target' => null,
                'min_quantity' => 3,
                'discount_percent' => 7.50,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'loyalty',
                'target' => null,
                'min_quantity' => 3,
                'discount_percent' => 5.00,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $category = Category::create([
            'name' => 'electronics',
        ]);

        $customer = Customer::create([
            'name' => 'Loyal Customer',
            'email' => 'loyal@example.com',
            'loyalty_score' => 10,
        ]);

        // ספק
        $vendor = Vendor::create([
            'name' => 'Vendor X',
            'email' => 'vendorx@example.com',
        ]);

        $product = Product::create([
            'name' => 'Smart TV',
            'category_id' => $category->id,
            'description' => 'Big screen TV'
        ]);

        ProductVendor::create([
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'price' => 2000,
            'stock' => 10,
        ]);

        $order = Order::create(['customer_id' => $customer->id]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);
        $service = new OrderProcessingService();
        $service->processOrder($order);

        $order->refresh();

        $this->assertEquals('processed', $order->status);

        $this->assertDatabaseCount('sub_orders', 1);
        $this->assertDatabaseCount('sub_order_items', 1);

        $item = SubOrderItem::first();
        $expectedMinPrice = 2000 * 0.5;
        $this->assertGreaterThanOrEqual($expectedMinPrice, $item->unit_price);

        $this->assertGreaterThanOrEqual(2, DB::table('applied_discounts')->count());

        $subOrder = SubOrder::first();
        $service->notify($subOrder, $customer->id, $vendor->id);

        $this->assertDatabaseHas('notifications', [
            'sub_order_id' => $subOrder->id,
            'vendor_id' => $vendor->id,
            'customer_id' => $customer->id,
        ]);
    }

    /** @test */
    public function it_caps_total_discount_at_50_percent()
    {
        DB::table('discount_rules')->insert([
            [
                'type' => 'category',
                'target' => 'electronics',
                'min_quantity' => null,
                'discount_percent' => 30.00,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'quantity',
                'target' => null,
                'min_quantity' => 3,
                'discount_percent' => 25.00,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $category = Category::create([
            'name' => 'electronics',
        ]);

        $customer = Customer::create([
            'name' => 'Regular Customer',
            'email' => 'regular@example.com',
            'loyalty_score' => 0,
        ]);

        $vendor = Vendor::create([
            'name' => 'Vendor Y',
            'email' => 'vendor@example.com',
        ]);

        $product = Product::create([
            'name' => 'Gaming Laptop',
            'category_id' => $category->id,
            'description' => 'Powerful device',
        ]);

        ProductVendor::create([
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'price' => 3000,
            'stock' => 5,
        ]);

        $order = Order::create(['customer_id' => $customer->id]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $service = new \App\Services\OrderProcessingService();
        $service->processOrder($order);

        $order->refresh();

        $this->assertEquals('processed', $order->status);

        $item = SubOrderItem::first();

        $original = $item->unit_price_original;
        $final = $item->unit_price;
        $minAllowed = round($original * 0.5, 2);

        $this->assertGreaterThanOrEqual($minAllowed, $final);

        $this->assertEquals($minAllowed, $final);
    }
    /** @test */
    public function it_does_not_apply_quantity_discount_if_below_threshold()
    {
        DB::table('discount_rules')->insert([
            [
                'type' => 'quantity',
                'target' => null,
                'min_quantity' => 5,
                'discount_percent' => 15.00,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $category = Category::create(['name' => 'accessories']);

        $customer = Customer::create([
            'name' => 'Simple Buyer',
            'email' => 'buyer@example.com',
            'loyalty_score' => 0,
        ]);

        $vendor = Vendor::create([
            'name' => 'Vendor Small',
            'email' => 'vendor@small.com',
        ]);

        $product = Product::create([
            'name' => 'Mouse',
            'category_id' => $category->id,
            'description' => 'Wireless',
        ]);

        ProductVendor::create([
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'price' => 100,
            'stock' => 10,
        ]);

        $order = Order::create(['customer_id' => $customer->id]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $service = new \App\Services\OrderProcessingService();
        $service->processOrder($order);

        $this->assertEquals('processed', $order->refresh()->status);

        $this->assertDatabaseCount('applied_discounts', 0);

        $item = SubOrderItem::first();
        $this->assertEquals(100, $item->unit_price);
    }
    /** @test */
    public function it_does_not_apply_category_discount_if_product_has_no_category()
    {
        DB::table('discount_rules')->insert([
            [
                'type' => 'category',
                'target' => 'electronics',
                'min_quantity' => null,
                'discount_percent' => 20.00,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $customer = Customer::create([
            'name' => 'NoCategory Guy',
            'email' => 'nocat@example.com',
            'loyalty_score' => 0,
        ]);

        // ספק
        $vendor = Vendor::create([
            'name' => 'Generic Vendor',
            'email' => 'vendor@generic.com',
        ]);

        $product = Product::create([
            'name' => 'Mystery Item',
            'category_id' => null, // 💥 שורת המקרה
            'description' => 'Uncategorized item',
        ]);

        ProductVendor::create([
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'price' => 500,
            'stock' => 10,
        ]);

        $order = Order::create(['customer_id' => $customer->id]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $service = new \App\Services\OrderProcessingService();
        $service->processOrder($order);

        $order->refresh();
        $this->assertEquals('processed', $order->status);

        $this->assertDatabaseCount('applied_discounts', 0);

        $item = SubOrderItem::first();
        $this->assertEquals(500, $item->unit_price);
    }
    /** @test */




}
