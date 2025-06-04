<?php

namespace App\Console\Commands;

use App\Helpers\CustomHelper;
use App\Models\SubOrder;
use Illuminate\Console\Command;

class NotifyVendors extends Command
{
    protected $signature = 'orders:notify-vendors';

    protected $description = 'Notify vendors about their pending sub-orders';

    public function handle()
    {
        CustomHelper::log("🔍 Fetching pending sub-orders...", 'info', [], $this);

        $pendingSubOrders = SubOrder::where('status', 'pending')->with(['order','items','vendor'])->get();
        $grouped = $pendingSubOrders->groupBy(function ($subOrder) {
            return $subOrder->order->customer_id;
        });

        foreach ($grouped as $customerId => $subOrdersForCustomer) {
            CustomHelper::log("\n👤 Customer #{$customerId}", 'info', [], $this);

            $vendorGrouped = $subOrdersForCustomer->groupBy('vendor_id');
            foreach ($vendorGrouped as $vendorId => $subOrders) {
                foreach ($subOrders as $subOrder) {
                    $msg = "📤 Job queued: SubOrder #{$subOrder->id} to Vendor #{$vendorId} for Customer #{$customerId}";
                    \App\Jobs\NotifyVendorSubOrder::dispatch($subOrder, $vendorId, $customerId)->onQueue('notify-vendor-sub-order');
                    CustomHelper::log($msg, 'info', [], $this);
                }
                CustomHelper::log("✅ Vendor #{$vendorId} notified with " . count($subOrders) . " sub-orders", 'info', [], $this);
            }
        }
    }
}
