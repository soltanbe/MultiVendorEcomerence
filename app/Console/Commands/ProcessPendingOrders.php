<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderProcessingService;
use Illuminate\Console\Command;

class ProcessPendingOrders extends Command
{
    protected $signature = 'orders:process-pending';

    protected $description = 'Process pending orders and create sub-orders grouped by vendor';

    public function handle(OrderProcessingService $orderProcessingService)
    {
        $this->info("\n Looking for pending orders...");

        Order::where('status', 'pending')
            ->with('items')
            ->chunk(100, function ($pendingOrders) use ($orderProcessingService) {
                foreach ($pendingOrders as $order) {
                    $orderProcessingService->processOrder($order, $this);
                }
            });

        $this->info("\n🎉 All pending orders have been processed.");
    }

}
