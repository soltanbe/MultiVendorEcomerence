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
use App\Models\AppliedDiscount;

class OrderProcessingService
{
    public function processOrder(Order $order, $output = null): void
    {
        DB::beginTransaction();
        try {
            $itemsByVendor = [];
            $discountRules = app('discount.rules');
            $appliedRules = [];
            foreach ($order->items as $item) {
                $vendorPrice = ProductVendor::getBestPriceFromVendors($item->product_id);

                if (!$vendorPrice) {
                    if ($output) $output->warn("No vendor found for Product #{$item->product_id}");
                    CustomHelper::log("No vendor found for Product #{$item->product_id}", 'warn');
                    continue;
                }
                $product = Product::find($item->product_id);

                $customer = $order->customer;
                $totalDiscount = 0;

                foreach ($discountRules as $rule) {
                    $ruleApplications = $rule->apply($product, $customer, $item->quantity, $vendorPrice->vendor_id, $order->id);
                    foreach ($ruleApplications as $application) {
                        $totalDiscount += $application['amount'];
                        $appliedRules[] = $application;
                    }
                }


                $totalDiscount = min($totalDiscount, 0.5);
                $finalPrice = round($vendorPrice->price * (1 - $totalDiscount), 2);
                if ($output) {
                    $output->info("Product #{$product->id} base: ₪{$vendorPrice->price} discount: " . ($totalDiscount * 100) . "% → final: ₪{$finalPrice}");
                }
                CustomHelper::log("Product #{$product->id} base: ₪{$vendorPrice->price} discount: " . ($totalDiscount * 100) . "% → final: ₪{$finalPrice}", 'info');
                $itemsByVendor[$vendorPrice->vendor_id][] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $finalPrice,
                    'original_price' => $vendorPrice->price,
                ];
            }
            foreach ($itemsByVendor as $vendorId => $items) {
                $subTotalOriginal = collect($items)->sum(function ($item) {
                    return $item['original_price'] * $item['quantity'];
                });
                $subTotalDiscount = collect($items)->sum(function ($item) {
                    return $item['price'] * $item['quantity'];
                });

                $subOrder = SubOrder::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendorId,
                    'total_amount_original' => $subTotalOriginal,
                    'total_amount' => $subTotalDiscount,
                ]);
                CustomHelper::log("✅ Created SubOrder #{$subOrder->id} for Vendor #{$vendorId} (Order #{$order->id})", 'info', [], $output);

                foreach ($items as $item) {
                    $subOrderItem = SubOrderItem::create([
                        'sub_order_id' => $subOrder->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'unit_price_original' => $item['original_price'],
                        'discount_amount' => $item['original_price'] - $item['price'],
                    ]);
                    foreach ($appliedRules as $rule) {
                        AppliedDiscount::create([
                            'sub_order_item_id' => $subOrderItem->id,
                            'discount_rule_id' => $rule['rule_id'],
                            'amount' => $rule['amount'],
                        ]);
                    }
                    $product = Product::find($item['product_id']);
                    CustomHelper::log(
                        "🧾 SubOrderItem created: {$product->name} (ID #{$product->id}) | Qty: {$item['quantity']} | Original: ₪{$item['original_price']} | Discounted: ₪{$item['price']} | Discount: ₪" . ($item['original_price'] - $item['price']),
                        'info',
                        [],
                        $output
                    );                }
            }
            $order->update(['status' => 'processed']);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            CustomHelper::log("❌ Failed to process Order #{$order->id}: " . $exception->getMessage(), 'error', [] , $output);
        }
    }
    public function notify(SubOrder $subOrder, int $customerId, int $vendorId): void
    {
        $subOrder = $subOrder->fresh(['items', 'order', 'vendor']);
        $vendorData = Vendor::find($vendorId);
        CustomHelper::log("Sending sub-orders for Customer #{$customerId} to Vendor #{$vendorId} name: {$vendorData->name} phone: {$vendorData->phone} email: {$vendorData->email}",'info', []);
        $msg = "Sending sub-orders to Vendor #{$vendorId} for Customer #{$customerId}\n";

        foreach ($subOrder->items as $item) {
            $product = Product::find($item->product_id);
            $line = "SubOrder #{$item->sub_order_id} product_id #{$item->product_id} name {$product->name} quantity {$item->quantity} unit price original {$item->unit_price_original} unit price {$item->unit_price}";
            CustomHelper::log($line, 'info', []);
            $msg .= $line . "\n";
        }

        $totalLine = "SubOrder #{$subOrder->id} (Order #{$subOrder->order_id}) - Total original: ₪{$subOrder->total_amount_original }  Total: ₪{$subOrder->total_amount} ";
        CustomHelper::log($totalLine,'info', []);
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
