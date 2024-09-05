<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('post_offices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('district');
            $table->string('province');
            $table->json('coordinates');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('post_offices');
    }
};