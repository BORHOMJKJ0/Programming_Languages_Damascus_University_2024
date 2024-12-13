<?php

namespace App\Models\Product;

use App\Models\Category\Category;
use App\Models\Image\Image;
use App\Models\Order\Order;
use App\Models\Store\Store;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(User::class, 'favorite_products', 'product_id', 'user_id')->withTimestamps();
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class,'order_items');
    }

    public function images()
    {
        return $this->hasMany(Image::class);
    }
}
