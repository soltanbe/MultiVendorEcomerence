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
    public function notify(SubOrder $subOrder, int $customerId, int $vendorId): void
    {
        $subOrder = $subOrder->fresh(['items', 'order', 'vendor']);
        $vendorData = Vendor::find($vendorId);
        CustomHelper::log("Sending sub-orders for Customer #{$customerId} to Vendor #{$vendorId} name: {$vendorData->name} phone: {$vendorData->phone} email: {$vendorData->email}");
        $msg = "Sending sub-orders to Vendor #{$vendorId} for Customer #{$customerId}\n";

        foreach ($subOrder->items as $item) {
            $product = Product::find($item->product_id);
            $line = "SubOrder #{$item->sub_order_id} product_id #{$item->product_id} name {$product->name} quantity {$item->quantity} unit price {$item->unit_price}";
            CustomHelper::log($line);
            $msg .= $line . "\n";
        }

        $totalLine = "SubOrder #{$subOrder->id} (Order #{$subOrder->order_id}) - Total: ₪{$subOrder->total_amount}";
        CustomHelper::log($totalLine);
        $msg .= $totalLine;

        $subOrder->update(['status' => 'notified']);

        Notification::create([
            'sub_order_id' => $subOrder->id,
            'vendor_id' => $subOrder->vendor_id,
            'customer_id' => $subOrder->order->customer_id,
            'channel' => 'log',
            'message' => $msg,
            'notified_at' => now(),
        ]);
    }
}
