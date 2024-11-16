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
        Schema::create('warehouse_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('provincial_warehouses')->onDelete('cascade');
            $table->string('staff_code')->unique(); // Mã nhân viên
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_manager')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Một user không thể làm việc ở nhiều kho cùng lúc
            $table->unique(['user_id', 'warehouse_id', 'start_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('warehouse_users');
    }
};
