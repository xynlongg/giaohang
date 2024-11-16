<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateOrdersStatusColumn extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
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
            'cancelled',
            'ready_for_local_delivery',
            'ready_for_direct_delivery',
            'ready_for_main_warehouse',
            'arrived_at_post_office',
            'ready_for_transfer'
        ) NOT NULL DEFAULT 'pending'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
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
            'cancelled',
            'ready_for_local_delivery',
            'ready_for_direct_delivery',
            'ready_for_main_warehouse',
            'arrived_at_post_office'
        ) NOT NULL DEFAULT 'pending'");
    }
}