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
        Schema::create('post_office_shippers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_office_id');
            $table->unsignedBigInteger('shipper_id');
            $table->timestamps();

            $table->foreign('post_office_id')->references('id')->on('post_offices')->onDelete('cascade');
            $table->foreign('shipper_id')->references('id')->on('shippers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('post_office_shippers');
    }
};
