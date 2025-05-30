<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVendor extends Model
{
    protected $table = 'product_vendor';

    protected $fillable = [
        'product_id',
        'vendor_id',
        'price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public static function getBestPriceFromVendors($productId){
        return self::where('product_id', $productId)
            ->orderBy('price', 'asc')
            ->first();
    }
}
