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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('current_location_id')->nullable();
            $table->string('current_location_type')->nullable();
            $table->json('current_coordinates')->nullable();
            $table->string('current_location')->nullable();
            
            $table->foreign('current_location_id')->references('id')->on('post_offices');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['current_location_id']);
            $table->dropColumn(['current_location_id', 'current_location_type', 'current_coordinates', 'current_location']);
        });
    }
};
