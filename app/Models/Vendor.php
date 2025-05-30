<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = ['name', 'email', 'phone'];
    protected $table = 'vendors';
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_vendor')
            ->withPivot('price', 'stock')
            ->withTimestamps();
    }

    public function subOrders()
    {
        return $this->hasMany(SubOrder::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }


}
