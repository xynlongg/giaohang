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
        Schema::table('shippers', function (Blueprint $table) {
            $table->float('attendance_score')->default(0);
            $table->float('vote_score')->default(0);
            $table->json('operating_area')->nullable();
            $table->string('password');
            $table->rememberToken();
        });
    }
    
    public function down()
    {
        Schema::table('shippers', function (Blueprint $table) {
            $table->dropColumn(['attendance_score', 'vote_score', 'operating_area', 'password', 'remember_token']);
        });
    }
};
