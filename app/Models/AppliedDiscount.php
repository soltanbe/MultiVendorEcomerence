<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppliedDiscount extends Model
{
    protected $fillable = [
        'sub_order_item_id',
        'discount_rule_id',
        'amount',
    ];

    public function subOrderItem()
    {
        return $this->belongsTo(SubOrderItem::class);
    }

    public function discountRule()
    {
        return $this->belongsTo(DiscountRules::class);
    }
}
