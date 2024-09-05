<?php

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
        Schema::create('order_location_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('location_type'); // 'sender', 'post_office', 'receiver'
            $table->unsignedBigInteger('location_id')->nullable(); // Nullable for sender and receiver locations
            $table->string('address');
            $table->json('coordinates');
            $table->string('status');
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('post_offices');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_location_histories');
    }
};
