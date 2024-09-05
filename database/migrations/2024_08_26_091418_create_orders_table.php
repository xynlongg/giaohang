<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('sender_name');
            $table->string('sender_phone');
            $table->text('sender_address');
            $table->json('sender_coordinates');
            $table->string('receiver_name');
            $table->string('receiver_phone');
            $table->text('receiver_address');
            $table->json('receiver_coordinates');
            $table->decimal('total_weight', 8, 2);
            $table->decimal('total_cod', 10, 2);
            $table->decimal('total_value', 10, 2);
            $table->decimal('shipping_fee', 8, 2);
            $table->decimal('total_amount', 10, 2);
            $table->string('status')->default('pending');
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
