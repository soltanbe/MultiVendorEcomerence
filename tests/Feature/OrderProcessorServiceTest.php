<?php

namespace Tests\Feature;

use App\Models\{Customer, Product, Vendor, ProductVendor, Order, SubOrder};
use App\Services\OrderProcessorService;
use App\Jobs\NotifyVendorJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class OrderProcessorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_creates_order_and_sub_orders(): void
    {
        Bus::fake();

        $vendor1 = Vendor::factory()->create();
        $vendor2 = Vendor::factory()->create();
        $customer = Customer::factory()->create();

        $product1 = Product::factory()->create(['category' => 'electronics']);
        $product2 = Product::factory()->create(['category' => 'books']);

        ProductVendor::create([
            'product_id' => $product1->id,
            'vendor_id' => $vendor1->id,
            'price' => 100,
        ]);

        ProductVendor::create([
            'product_id' => $product2->id,
            'vendor_id' => $vendor2->id,
            'price' => 50,
        ]);

        $cart = [
            ['product_id' => $product1->id, 'quantity' => 1],
            ['product_id' => $product2->id, 'quantity' => 2],
        ];

        $service = new OrderProcessorService();
        $order = $service->process($customer->id, $cart);

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertEquals(2, $order->subOrders()->count());
        $this->assertEquals(2, $order->items()->count());

        foreach ($order->subOrders as $subOrder) {
            $this->assertTrue(
                in_array($subOrder->vendor_id, [$vendor1->id, $vendor2->id])
            );
        }

        Bus::assertDispatched(NotifyVendorJob::class, 2);
    }

    public function test_selects_lowest_price_for_each_product(): void
    {
        $vendor1 = Vendor::factory()->create();
        $vendor2 = Vendor::factory()->create();
        $customer = Customer::factory()->create();

        $product = Product::factory()->create();

        ProductVendor::create([
            'product_id' => $product->id,
            'vendor_id' => $vendor1->id,
            'price' => 200,
        ]);

        ProductVendor::create([
            'product_id' => $product->id,
            'vendor_id' => $vendor2->id,
            'price' => 150,
        ]);

        $cart = [
            ['product_id' => $product->id, 'quantity' => 1],
        ];

        $service = new OrderProcessorService();
        $order = $service->process($customer->id, $cart);

        $subOrder = $order->subOrders()->first();
        $this->assertEquals($vendor2->id, $subOrder->vendor_id);
    }
}
