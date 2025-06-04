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

    public function test_process_order_applies_category_discount()
    {
        // Create customer
        $customer = new Customer();
        $customer->name = 'Sultan';
        $customer->email = 'sultan@example.com';
        $customer->save();

        // Create category
        $category = new Category();
        $category->name = 'Electronics';
        $category->save();

        // Create product
        $product = new Product();
        $product->name = 'Smartphone';
        $product->category_id = $category->id;
        $product->description = 'Test phone';
        $product->save();

        // Create vendor
        $vendor = new Vendor();
        $vendor->name = 'Vendor 1';
        $vendor->email = 'vendor@example.com';
        $vendor->phone = '123456789';
        $vendor->save();

        // Link product to vendor
        ProductVendor::create([
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'price' => 200,
            'stock' => 50,
        ]);

        // Create order and order item
        $order = new Order();
        $order->customer_id = $customer->id;
        $order->status = 'pending';
        $order->save();

        $orderItem = new OrderItem();
        $orderItem->order_id = $order->id;
        $orderItem->product_id = $product->id;
        $orderItem->quantity = 3;
        $orderItem->save();

        // Inject discount rules (Category rule only)
        app()->singleton('discount.rules', fn () => [
            new CategoryDiscountRule(),
        ]);

        // Process the order
        $service = new OrderProcessingService();
        $service->processOrder($order);

        // Assertions
        $subOrder = SubOrder::where('order_id', $order->id)->first();
        $this->assertNotNull($subOrder);

        $subOrderItem = SubOrderItem::where('sub_order_id', $subOrder->id)->first();
        $this->assertNotNull($subOrderItem);
        $this->assertGreaterThan(0, $subOrderItem->discount_amount);

        $this->assertDatabaseHas('applied_discounts', [
            'sub_order_item_id' => $subOrderItem->id,
        ]);

    }
}
