<?php

use App\Models\User\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->integer('store_id');
            $table->integer('total_amount')->default(0);
            $table->double('total_price')->default(0);
            $table->enum('order_status',
                ['Pending', 'Preparing', 'Shipped', 'Not Available',
                    'Rejected', 'Delivered', 'Cancelled', 'Processing', 'Completed'])
                ->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
