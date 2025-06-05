<?php

namespace App\Console\Commands;

use App\Helpers\CustomHelper;
use App\Models\Order;
use App\Services\OrderProcessingService;
use Illuminate\Console\Command;

class ProcessPendingOrders extends Command
{
    protected $signature = 'orders:process-pending';

    protected $description = 'Process pending orders and create sub-orders grouped by vendor';

    public function handle(OrderProcessingService $orderProcessingService)
    {
        $byJob = true;
        $this->info("\n Looking for pending orders...");

        Order::where('status', 'pending')
            ->with('items')
            ->chunk(100, function ($pendingOrders) use ($orderProcessingService, $byJob) {
                foreach ($pendingOrders as $order) {
                    if($byJob){
                        $msg = "📤 Job queued ProcessOrder: for order #{$order->id}  for Customer #{$order->customer_id}";
                        \App\Jobs\ProcessOrder::dispatch($order)->onQueue('process-order');
                        CustomHelper::log($msg, 'info', [], $this);
                    }else{
                        $orderProcessingService->processOrder($order, $this);
                    }

                }
            });
        $this->info("\n🎉 All pending orders have been processed.");
    }

}
