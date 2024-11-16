<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ForceUpdateOrdersTableForPolymorphicLocation extends Migration
{
    public function up()
    {
        // Xóa khóa ngoại bằng câu lệnh SQL trực tiếp
        DB::statement('ALTER TABLE orders DROP FOREIGN KEY orders_current_location_id_foreign');

        Schema::table('orders', function (Blueprint $table) {
            // Thay đổi current_location_id thành unsignedBigInteger
            $table->unsignedBigInteger('current_location_id')->change();
            
            // Thêm cột current_location_type nếu chưa có
            if (!Schema::hasColumn('orders', 'current_location_type')) {
                $table->string('current_location_type')->nullable();
            }
        });

        // Cập nhật dữ liệu hiện có
        DB::statement("UPDATE orders SET current_location_type = 'App\\Models\\PostOffice' WHERE current_location_type IS NULL");
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Định nghĩa rollback nếu cần
            $table->dropColumn('current_location_type');
            $table->foreign('current_location_id')->references('id')->on('post_offices');
        });
    }
}