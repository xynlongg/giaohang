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
        Schema::create('order_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('shipper_id');
            $table->unsignedBigInteger('post_office_id');
            $table->string('status'); // assigned, in_delivery, delivered, failed
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('shipper_id')->references('id')->on('shippers');
            $table->foreign('post_office_id')->references('id')->on('post_offices');
            $table->foreign('assigned_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_deliveries');
    }
};
