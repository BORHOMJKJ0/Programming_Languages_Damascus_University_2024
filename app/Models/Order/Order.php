<?php

namespace App\Models\Order;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(Order_items::class);
    }

    public function store()
    {
        $this->loadMissing('items.product.store');

        return $this->items->first()?->product?->store;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
