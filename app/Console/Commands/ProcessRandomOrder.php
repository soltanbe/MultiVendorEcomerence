<?php

namespace App\Console\Commands;

use App\Helpers\CustomHelper;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessRandomOrder extends Command
{
    protected $signature = 'orders:random {count=1 : Number of orders to process}';

    protected $description = 'Process one or more random customer orders with random products and lowest vendor prices';

    public function handle()
    {
        $count = (int) $this->argument('count');
        CustomHelper::log("\n[RandomOrder] Processing {$count} random order(s)...\n", 'info', [], $this);

        for ($i = 1; $i <= $count; $i++) {
            CustomHelper::log("[RandomOrder] Processing simulated Order #{$i}", 'info', [], $this);

            $customer = Customer::getRandomData();
            if (!$customer) {
                CustomHelper::log('[RandomOrder] ❌ No customers found.', 'error', [], $this);
                continue;
            }

            CustomHelper::log("[RandomOrder] Selected customer: #{$customer->id} - {$customer->name} <{$customer->email}>", 'info', [], $this);

            $products = Product::getRandomData(rand(1, 5));
            if ($products->isEmpty()) {
                CustomHelper::log('[RandomOrder] ⚠️ No products found. Skipping order.', 'warn', [], $this);
                continue;
            }

            try {
                DB::beginTransaction();
                $order = Order::create([
                    'customer_id' => $customer->id,
                ]);

                CustomHelper::log("[RandomOrder] ✅ Created Order #{$order->id} for Customer #{$customer->id}", 'info', [], $this);

                foreach ($products as $product) {
                    CustomHelper::log("[RandomOrder] 🔹 Pulled product: ID {$product->id}, Name: {$product->name}", 'info', [], $this);
                    $quantity = rand(1, 3);
                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity
                    ]);
                    CustomHelper::log("[RandomOrder] ✅ OrderItem ID: {$orderItem->id} | Product #{$product->id} ({$product->name}) | Qty: {$quantity} ", 'info', [], $this);
                }

                DB::commit();
            } catch (\Exception $exception) {
                DB::rollBack();
                CustomHelper::log("[RandomOrder] ❌ Failed to create order: " . $exception->getMessage(), 'error', [], $this);
            }
        }
    }
}
