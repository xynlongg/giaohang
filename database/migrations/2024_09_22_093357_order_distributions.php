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
        Schema::create('order_distributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('shipper_id');
            $table->unsignedBigInteger('post_office_id');
            $table->unsignedBigInteger('distributed_by'); // ID của nhân viên bưu cục
            $table->timestamp('distributed_at');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('shipper_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('post_office_id')->references('id')->on('post_offices')->onDelete('cascade');
            $table->foreign('distributed_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_distributions');
    }

};
