<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubOrder extends Model
{
    protected $fillable = ['order_id', 'vendor_id', 'total_amount', 'status'];
    protected $table = 'sub_orders';
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(SubOrderItem::class);
    }

    public function notifications()
    {
        return $this->hasOne(Notification::class);
    }
}
