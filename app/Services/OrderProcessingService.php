<?php

namespace App\Services;

use App\Helpers\CustomHelper;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVendor;
use App\Models\SubOrder;
use App\Models\SubOrderItem;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class OrderProcessingService
{
    public function processOrder(Order $order, $output = null): void
    {
        DB::beginTransaction();
        try {
            $itemsByVendor = [];
            foreach ($order->items as $item) {
                $vendorPrice = ProductVendor::getBestPriceFromVendors($item->product_id);
                if (!$vendorPrice) {
                    if ($output) $output->warn("No vendor found for Product #{$item->product_id}");
                    CustomHelper::log("No vendor found for Product #{$item->product_id}", 'warn');
                    continue;
                }
                $itemsByVendor[$vendorPrice->vendor_id][] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $vendorPrice->price,
                ];
            }
            foreach ($itemsByVendor as $vendorId => $items) {
                $subTotal = collect($items)->sum(function ($item) {
                    return $item['price'] * $item['quantity'];
                });
                $subOrder = SubOrder::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendorId,
                    'total_amount' => $subTotal,
                ]);
                foreach ($items as $item) {
                    SubOrderItem::create([
                        'sub_order_id' => $subOrder->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                    ]);
                }
                CustomHelper::log("✅ Created SubOrder #{$subOrder->id} for Vendor #{$vendorId} (Order #{$order->id})", 'info', $output);
            }
            $order->update(['status' => 'processed']);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            CustomHelper::log("❌ Failed to process Order #{$order->id}: " . $exception->getMessage(), 'error', $output);
        }
    }

    public function notifyPendingSubOrders($pendingSubOrders, $output = null){
        $grouped = $pendingSubOrders->groupBy(function ($subOrder) {
            return $subOrder->order->customer_id;
        });
        foreach ($grouped as $customerId => $subOrdersForCustomer)
        {
            if($output){
                $output->info("\n👤 Customer #{$customerId}");
            }
            $vendorGrouped = $subOrdersForCustomer->groupBy('vendor_id');
            foreach ($vendorGrouped as $vendorId => $subOrders) {
                $vendorData = Vendor::find($vendorId);
                if($output){
                    $output->info("Sending sub-orders to Vendor #{$vendorId} name: {$vendorData->name} phone: {$vendorData->phone} email: {$vendorData->email}");
                }
                CustomHelper::log("Sending sub-orders to Vendor #{$vendorId} name: {$vendorData->name} phone: {$vendorData->phone} email: {$vendorData->email}", 'info');
                $msg = "Sending sub-orders to Vendor #{$vendorId} for Customer #{$customerId}\n";

                foreach ($subOrders as $subOrder) {
                    foreach ($subOrder->items as $item) {
                        $productData = Product::find($item->product_id);
                        $info = "SubOrder #{$item->sub_order_id} product_id #{$item->product_id} name {$productData->name} quantity {$item->quantity} unit price {$item->unit_price}";
                        CustomHelper::log($info, 'info');
                        $msg .= "$info\n";
                    }
                    CustomHelper::log("SubOrder #{$subOrder->id} (Order #{$subOrder->order_id}) - Total: ₪{$subOrder->total_amount}", 'info', $output);
                    $msg .= "SubOrder #{$subOrder->id} (Order #{$subOrder->order_id}) - Total: ₪{$subOrder->total_amount}\n";
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
                if($output){
                    $output->info("✅ Vendor #{$vendorId} notified with " . count($subOrders) . " sub-orders\n");
                }
            }
        }
    }
}
