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
            $missingVendors = [];

            foreach ($order->items as $item) {
                $vendorPrice = ProductVendor::getBestPriceFromVendors($item->product_id);

                if (!$vendorPrice) {
                    $msg = "[OrderProcessing] Order #{$order->id} | ❌ No vendor found for Product #{$item->product_id}";
                    $missingVendors[] = $item->product_id;
                    if ($output) $output->warn($msg);
                    CustomHelper::log($msg, 'warn');
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

                $msg = "[OrderProcessing] Order #{$order->id} | Product #{$product->id} - \"{$product->name}\" | Vendor #{$vendorPrice->vendor_id} | Base: ₪{$vendorPrice->price} | Discount: " . ($totalDiscount * 100) . "% → Final: ₪{$finalPrice}";
                if ($output) $output->info($msg);
                CustomHelper::log($msg, 'info');

                $itemsByVendor[$vendorPrice->vendor_id][] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $finalPrice,
                    'original_price' => $vendorPrice->price,
                ];
            }

            if (empty($itemsByVendor)) {
                $order->update(['status' => 'failed']);
                $productList = implode(', ', $missingVendors);
                $msg = "[OrderProcessing] Order #{$order->id} | ❌ Failed — no vendors available for product(s): {$productList}";
                if ($output) $output->error($msg);
                CustomHelper::log($msg, 'error');
                return;
            }else{
                foreach ($itemsByVendor as $vendorId => $items) {
                    $subTotalOriginal = collect($items)->sum(fn($item) => $item['original_price'] * $item['quantity']);
                    $subTotalDiscount = collect($items)->sum(fn($item) => $item['price'] * $item['quantity']);

                    $subOrder = SubOrder::create([
                        'order_id' => $order->id,
                        'vendor_id' => $vendorId,
                        'total_amount_original' => $subTotalOriginal,
                        'total_amount' => $subTotalDiscount,
                    ]);

                    CustomHelper::log("[SubOrder] ✅ Created SubOrder #{$subOrder->id} for Vendor #{$vendorId} | Order #{$order->id}", 'info', [], $output);

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
                            $alreadyApplied = AppliedDiscount::where('sub_order_item_id', $subOrderItem->id)
                                ->where('discount_rule_id', $rule['rule_id'])
                                ->exists();

                            if (!$alreadyApplied) {
                                AppliedDiscount::create([
                                    'sub_order_item_id' => $subOrderItem->id,
                                    'discount_rule_id' => $rule['rule_id'],
                                    'amount' => $rule['amount'],
                                ]);
                            }
                        }

                        $product = Product::find($item['product_id']);
                        $discount = round($item['original_price'] - $item['price'], 2);
                        $msg = "[SubOrderItem] SubOrder #{$subOrder->id} | Product: \"{$product->name}\" (ID #{$product->id}) | Qty: {$item['quantity']} | Price: ₪{$item['price']} | Original: ₪{$item['original_price']} | Discount: ₪{$discount}";
                        CustomHelper::log($msg, 'info', [], $output);
                    }
                }
                $order->update(['status' => 'processed']);
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            CustomHelper::log("[OrderProcessing] ❌ Failed to process Order #{$order->id}: " . $exception->getMessage(), 'error', [], $output);
        }
    }

    public function notify(SubOrder $subOrder, int $customerId, int $vendorId): void
    {
        $subOrder = $subOrder->fresh(['items', 'order', 'vendor']);
        $vendorData = Vendor::find($vendorId);

        CustomHelper::log("[NotifyVendorJob] 🚚 Sending SubOrder #{$subOrder->id} (Order #{$subOrder->order_id}) to Vendor #{$vendorId} - {$vendorData->name} | Customer #{$customerId}", 'info');

        $msg = "";
        foreach ($subOrder->items as $item) {
            $product = Product::find($item->product_id);
            $line = "[NotifyVendorJob] SubOrder #{$item->sub_order_id} | Product: {$product->name} (ID #{$item->product_id}) | Qty: {$item->quantity} | Price: ₪{$item->unit_price} | Original: ₪{$item->unit_price_original}";
            CustomHelper::log($line, 'info');
            $msg .= $line . "\n";
        }

        $totalLine = "[NotifyVendorJob] SubOrder #{$subOrder->id} Summary | Order #{$subOrder->order_id} | Total: ₪{$subOrder->total_amount} | Original: ₪{$subOrder->total_amount_original}";
        CustomHelper::log($totalLine, 'info');
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
