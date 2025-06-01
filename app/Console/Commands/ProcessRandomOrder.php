<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVendor;
use App\Models\Vendor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class ProcessRandomOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:random {count=1 : Number of orders to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process one or more random customer orders with random products and lowest vendor prices';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');
        $rules = app('discount.rules');
        $this->info("\n Processing {$count} random order(s)...\n");
        Log::info("Starting processing of {$count} random order(s)");
        for ($i = 1; $i <= $count; $i++) {
            $this->log("Processing Order #{$i}");
            $customer = Customer::getRandomData();
            if (!$customer) {
                $msg = 'No customers found.';
                $this->error($msg);
                Log::error($msg);
                continue;
            }
            $this->log("Selected customer: {$customer->id} - {$customer->name} ({$customer->email})");
            $products = Product::getRandomData(rand(1, 5));
            if ($products->isEmpty()) {
                $this->log('No products found.');
                continue;
            }
            $itemsByVendor = [];
            foreach ($products as $product) {
                $this->log("Pulled product from DB: ID {$product->id}, Name: {$product->name}");
                try {
                    $order = Order::create([
                        'customer_id' => $customer->id,
                    ]);
                    $this->log("Created order ID: {$order->id}");

                    foreach ($products as $product) {
                        // no need this but just for debug
                        $vendorPrice = ProductVendor::getBestPriceFromVendors($product->id);
                        if (!$vendorPrice) {
                            $this->log(" No vendor found for product ID {$product->id}");
                            continue;
                        }
                        $quantity = rand(1, 3);
                        $price = $vendorPrice->price;
                        $orderItem = OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'quantity' => $quantity
                        ]);
                        $this->log("Saved OrderItem ID: {$orderItem->id} - Product #{$product->id} (Qty: {$quantity}, Price: ₪{$price}) via Vendor #{$vendorPrice->vendor_id}");
                        $itemsByVendor[$vendorPrice->vendor_id][] = [
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                        ];
                    }
                    DB::commit();
                }catch (Exception $exception){

                    DB::rollBack();
                }
            }
        }
    }
    private function log($msg){
        $this->line($msg);
        Log::info($msg);
    }
}
