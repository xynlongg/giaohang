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
        Schema::create('order_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('from_post_office_id');
            $table->unsignedBigInteger('to_post_office_id');
            $table->string('status')->default('pending'); // pending, completed, cancelled
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('from_post_office_id')->references('id')->on('post_offices');
            $table->foreign('to_post_office_id')->references('id')->on('post_offices');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_transfers');
    }
};
