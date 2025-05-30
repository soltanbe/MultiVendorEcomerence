<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Product;
use App\Models\SubOrder;
use App\Models\Vendor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotifyVendors extends Command
{
    protected $signature = 'sub-orders:notify-vendors';

    protected $description = 'Notify vendors about their pending sub-orders';

    public function handle()
    {
        $this->info("🔍 Fetching pending sub-orders...");
        $pendingSubOrders = SubOrder::where('status', 'pending')->with(['order','items','vendor'])->get();
        $grouped = $pendingSubOrders->groupBy(function ($subOrder) {
            return $subOrder->order->customer_id;
        });
        foreach ($grouped as $customerId => $subOrdersForCustomer) {
            $this->info("\n👤 Customer #{$customerId}");
            $vendorGrouped = $subOrdersForCustomer->groupBy('vendor_id');
            foreach ($vendorGrouped as $vendorId => $subOrders) {
                $vendorData = Vendor::find($vendorId);
                $this->info("Sending sub-orders to Vendor #{$vendorId} name: {$vendorData->name} phone: {$vendorData->phone} email: {$vendorData->email}" );
                Log::info("Sending sub-orders to Vendor #{$vendorId} name: {$vendorData->name} phone: {$vendorData->phone} email: {$vendorData->email}");
                $msg = "Sending sub-orders to Vendor #{$vendorId} for Customer #{$customerId}\n";
                foreach ($subOrders as $subOrder) {
                    foreach ($subOrder->items as $item){
                        $productData = Product::find($item->product_id);
                        $this->line("SubOrder #{$item->sub_order_id} product_id #{$item->product_id} name {$productData->name} quantity {$item->quantity} unit price {$item->unit_price }");
                        Log::info("SubOrder #{$item->sub_order_id} product_id #{$item->product_id} name {$productData->name}quantity {$item->quantity} unit price {$item->unit_price }");
                        $msg.="SubOrder #{$item->sub_order_id} product_id #{$item->product_id} name {$productData->name} quantity {$item->quantity} unit price {$item->unit_price }\n";
                    }
                    $this->line("SubOrder #{$subOrder->id} (Order #{$subOrder->order_id}) - Total: ₪{$subOrder->total_amount}");
                    Log::info("SubOrder #{$subOrder->id} (Order #{$subOrder->order_id}) - Total: ₪{$subOrder->total_amount}");
                    $msg.="SubOrder #{$subOrder->id} (Order #{$subOrder->order_id}) - Total: ₪{$subOrder->total_amount}\n";
                    $subOrder->update(['status' => 'notified']);
                }
                Notification::create([
                    'sub_order_id' => $subOrder->id,
                    'vendor_id' => $vendorId,
                    'customer_id' => $customerId,
                    'channel' => 'log',
                    'message' => $msg,
                    'notified_at' => now(),
                ]);
                $msg = "";
                $this->info("✅ Vendor #{$vendorId} notified with " . count($subOrders) . " sub-orders\n");
            }
        }
    }
}
