<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;
    
    return new class extends Migration
    {
        public function up()
        {
            Schema::create('post_office_user', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('post_office_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
    
                $table->foreign('post_office_id')->references('id')->on('post_offices')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    
                $table->unique(['post_office_id', 'user_id']);
            });
        }
    
        public function down()
        {
            Schema::dropIfExists('post_office_user');
        }
};
