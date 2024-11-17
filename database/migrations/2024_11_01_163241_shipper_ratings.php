<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shipper_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('shipper_id');
            $table->unsignedBigInteger('user_id'); // người đánh giá
            $table->integer('rating')->comment('Số sao đánh giá (1-5)');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('shipper_id')->references('id')->on('shippers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Đảm bảo mỗi order chỉ có 1 đánh giá
            $table->unique('order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipper_ratings');
    }
};
