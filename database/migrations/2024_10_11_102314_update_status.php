<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Trước tiên, chúng ta sẽ thêm một cột tạm thời
        Schema::table('orders', function (Blueprint $table) {
            $table->string('temp_status')->nullable();
        });

        // Sao chép dữ liệu từ cột 'status' sang cột 'temp_status'
        DB::table('orders')->update(['temp_status' => DB::raw('status')]);

        // Xóa cột 'status' cũ
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // Tạo cột 'status' mới với các giá trị enum mở rộng
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'confirmed',
                'ready_for_pickup',
                'pickup_assigned',
                'picked_up',
                'arrived_at_origin_post_office',
                'in_transit',
                'arrived_at_destination_post_office',
                'out_for_delivery',
                'delivered',
                'failed_pickup',
                'failed_delivery',
                'returned_to_sender',
                'cancelled'
            ])->default('pending');
        });

        // Cập nhật dữ liệu từ cột tạm thời sang cột mới
        DB::table('orders')->update([
            'status' => DB::raw("CASE 
                WHEN temp_status = 'pending' THEN 'pending'
                WHEN temp_status = 'picked_up' THEN 'picked_up'
                WHEN temp_status = 'in_transit' THEN 'in_transit'
                WHEN temp_status = 'delivered' THEN 'delivered'
                WHEN temp_status = 'failed_pickup' THEN 'failed_pickup'
                WHEN temp_status = 'failed_delivery' THEN 'failed_delivery'
                ELSE 'pending'
            END")
        ]);

        // Xóa cột tạm thời
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('temp_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->enum('status', ['pending', 'picked_up', 'in_transit', 'delivered', 'failed_pickup', 'failed_delivery'])
                ->default('pending');
        });
    }
};