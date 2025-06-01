<?php
namespace App\Jobs;

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

class NotifyVendorSubOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $subOrder;
    protected $customerId;
    protected $vendorId;

    public function __construct(SubOrder $subOrder, $vendorId, $customerId)
    {
        $this->subOrder = $subOrder;
        $this->vendorId = $vendorId;
        $this->customerId = $customerId;
    }

    public function handle(OrderProcessingService $orderProcessingService)
    {
        $orderProcessingService->notify($this->subOrder, $this->vendorId, $this->customerId);
    }
}
