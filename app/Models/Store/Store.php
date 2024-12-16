<?php

namespace App\Models\Store;

use App\Models\Order\Order_items;
use App\Models\Product\Product;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function order_items()
    {
        return $this->hasManyThrough(
            Order_items::class,
            Product::class,
            'store_id',
            'product_id');
    }
}
