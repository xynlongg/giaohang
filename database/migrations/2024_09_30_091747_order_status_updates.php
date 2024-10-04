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
        Schema::create('order_status_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('shipper_id')->constrained('shippers')->onDelete('cascade');
            $table->foreignId('post_office_id')->constrained('post_offices')->onDelete('cascade');
            $table->string('status');
            $table->string('reason')->nullable();
            $table->text('custom_reason')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_status_updates');
    }
};
