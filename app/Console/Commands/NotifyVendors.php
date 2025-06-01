<?php

namespace App\Console\Commands;

use App\Models\SubOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotifyVendors extends Command
{
    protected $signature = 'orders:notify-vendors';

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
                foreach ($subOrders as $subOrder) {
                    $msg = "Job is queued for Sending sub-orders to Vendor #{$vendorId} for Customer #{$customerId} SubOrder #{$subOrder->id}\n";
                    \App\Jobs\NotifyVendorSubOrder::dispatch($subOrder, $vendorId, $customerId)->onQueue('notify-vendor-sub-order');
                    $this->line($msg);
                    Log::info($msg);
                }
                $this->info("✅ Vendor #{$vendorId} notified with " . count($subOrders) . " sub-orders\n");
            }
        }
    }
}
