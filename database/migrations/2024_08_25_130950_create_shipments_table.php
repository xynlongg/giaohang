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
    Schema::create('shipments', function (Blueprint $table) {
        $table->id();
        $table->point('start_point');
        $table->point('end_point');
        $table->point('current_location')->nullable();
        $table->enum('status', ['pending', 'in_transit', 'delivered']);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
