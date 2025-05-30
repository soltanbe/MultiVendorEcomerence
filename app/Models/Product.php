<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'image' , 'description'];
    protected $table = 'products';
    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'product_vendor')
            ->withPivot('price', 'stock')
            ->withTimestamps();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function subOrderItems()
    {
        return $this->hasMany(SubOrderItem::class);
    }

    public static function getRandomData($count){
        return self::inRandomOrder()->take($count)->get();
    }
}
