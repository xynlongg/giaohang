<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Kiểm tra nếu cột không tồn tại thì mới thêm
            if (!Schema::hasColumn('orders', 'warranty_fee')) {
                $table->decimal('warranty_fee', 10, 2)->nullable()->after('id');
            }

            if (!Schema::hasColumn('orders', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('warranty_fee');
                $table->foreign('category_id')->references('id')->on('product_categories')->onDelete('set null');
            }

            if (!Schema::hasColumn('orders', 'warranty_package_id')) {
                $table->unsignedBigInteger('warranty_package_id')->nullable()->after('category_id');
                $table->foreign('warranty_package_id')->references('id')->on('warranty_packages')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Xóa các cột nếu tồn tại
            if (Schema::hasColumn('orders', 'warranty_fee')) {
                $table->dropColumn('warranty_fee');
            }

            if (Schema::hasColumn('orders', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }

            if (Schema::hasColumn('orders', 'warranty_package_id')) {
                $table->dropForeign(['warranty_package_id']);
                $table->dropColumn('warranty_package_id');
            }
        });
    }
};
