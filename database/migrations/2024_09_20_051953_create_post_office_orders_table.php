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
        Schema::create('post_office_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_office_id');
            $table->unsignedBigInteger('order_id');
            $table->timestamps();

            $table->foreign('post_office_id')->references('id')->on('post_offices')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            $table->unique(['post_office_id', 'order_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('post_office_orders');
    }
};
