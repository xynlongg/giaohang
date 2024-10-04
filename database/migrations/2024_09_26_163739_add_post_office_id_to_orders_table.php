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
        Schema::table('order_cancellation_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('post_office_id')->nullable()->after('order_id');
            $table->foreign('post_office_id')->references('id')->on('post_offices')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('order_cancellation_requests', function (Blueprint $table) {
            $table->dropForeign(['post_office_id']);
            $table->dropColumn('post_office_id');
        });
    }
};
