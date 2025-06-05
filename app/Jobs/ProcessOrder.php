<?php
namespace App\Jobs;

use App\Models\Order;
use App\Models\Product;
use App\Models\Notification;
use App\Models\SubOrder;
use App\Models\Vendor;
use App\Services\OrderProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    protected $output;

    public function __construct(Order $order, $output = null)
    {
        $this->order = $order;
        $this->output = $output;
    }

    public function handle(OrderProcessingService $orderProcessingService)
    {
        $orderProcessingService->processOrder($this->order, $this->output);
    }
}
