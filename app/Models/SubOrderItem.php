<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubOrderItem extends Model
{
    protected $fillable = ['sub_order_id', 'product_id', 'quantity', 'unit_price', 'discount_amount'];
    protected $table = 'sub_order_items';
    public function subOrder()
    {
        return $this->belongsTo(SubOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
