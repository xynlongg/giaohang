<?php
namespace App\Http\Controllers;

use App\Models\Shipper;
use App\Models\Order;

class OrderAssignmentService
{
    public function assignOrder(Order $order)
    {
        $availableShippers = Shipper::where('status', 'active')
            ->whereJsonContains('operating_area', $order->delivery_district)
            ->get();

        $assignedShipper = $availableShippers->sortByDesc(function ($shipper) {
            return $shipper->attendance_score * 0.6 + $shipper->vote_score * 0.4;
        })->first();

        if ($assignedShipper) {
            $order->shipper_id = $assignedShipper->id;
            $order->save();
            return $assignedShipper;
        }

        return null;
    }
}