<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOrderStatusColumn extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Nếu status là ENUM
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'confirmed', 'ready_for_pickup', 'pickup_assigned', 'picked_up', 'arrived_at_origin_post_office', 'in_transit', 'arrived_at_destination_post_office', 'out_for_delivery', 'delivered', 'failed_pickup', 'failed_delivery', 'returned_to_sender', 'cancelled', 'ready_for_local_delivery', 'ready_for_direct_delivery', 'ready_for_main_warehouse', 'arrived_at_post_office', 'transferring_to_provincial_warehouse')");

           
        });
    }

    public function down()
    {
    }
}