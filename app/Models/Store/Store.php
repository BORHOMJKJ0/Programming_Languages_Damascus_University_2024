<?php

namespace App\Models\Store;

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
        return $this->belongsToMany(Product::class, 'products_stores')
            ->withPivot('price', 'amount')
            ->withTimestamps();
    }
}
