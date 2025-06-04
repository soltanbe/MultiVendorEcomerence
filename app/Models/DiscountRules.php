<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountRules extends Model
{
    protected $table = 'discount_rules';

    protected $fillable = [
        'type',
        'target',
        'min_quantity',
        'discount_percent',
        'active',
    ];

    public function appliedDiscounts()
    {
        return $this->hasMany(AppliedDiscount::class);
    }
}
