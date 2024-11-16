<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('distribution_handovers', function (Blueprint $table) {
            $table->id();
            
            // Order reference
            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->onDelete('cascade');
            
            // Staff references
            $table->foreignId('distribution_staff_id')
                  ->constrained('users')
                  ->comment('ID của nhân viên phân phối được giao đơn hàng');
                  
            // Post office references  
            $table->foreignId('origin_post_office_id')
                  ->constrained('post_offices')
                  ->comment('Bưu cục đơn hàng đang ở');
                  
            $table->foreignId('destination_post_office_id')
                  ->nullable()
                  ->constrained('post_offices')
                  ->comment('Bưu cục đích đến (cho nội thành < 20km)');
                  
            $table->foreignId('destination_warehouse_id')
                  ->nullable()  
                  ->constrained('provincial_warehouses')
                  ->comment('Kho tổng đích đến (cho nội thành > 20km và ngoại thành)');

            // Handover type and status
            $table->string('shipping_type')
                  ->comment('Loại vận chuyển: noi_thanh/ngoai_thanh');
            
            $table->enum('status', ['pending', 'in_transit', 'completed', 'failed'])
                  ->default('pending')
                  ->comment('Trạng thái phân phối');

            $table->decimal('distance', 10, 2)
                  ->nullable()
                  ->comment('Khoảng cách vận chuyển (km)');

            // Timestamps
            $table->timestamp('assigned_at')
                  ->comment('Thời điểm giao đơn cho nhân viên phân phối');
            
            $table->timestamp('completed_at')
                  ->nullable()
                  ->comment('Thời điểm hoàn thành phân phối');
                  
            $table->timestamps();

            // Indexes
            $table->index(['origin_post_office_id', 'status']);
            $table->index(['distribution_staff_id', 'status']);
            $table->index('shipping_type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('distribution_handovers');
    }
};