<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $fillable = [
        'sub_order_id',
        'vendor_id',
        'customer_id',
        'channel',
        'message',
        'notified_at',
    ];

    public function subOrder()
    {
        return $this->belongsTo(SubOrder::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
