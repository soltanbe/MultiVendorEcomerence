<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password'];
    protected $table = 'customers';
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
    public static function getRandomData(){
        return  self::inRandomOrder()->first();
    }
}
