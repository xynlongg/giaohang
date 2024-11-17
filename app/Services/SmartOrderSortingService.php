<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PostOffice;
use App\Models\OrderTransfer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SmartOrderSortingService
{
    const MAX_INTERNAL_DELIVERY_DISTANCE = 30; // km

    public function sortOrdersAtPostOffice(PostOffice $currentPostOffice)
    {
        $orders = Order::whereIn('shipping_type', ['cung_quan', 'noi_thanh', 'ngoai_thanh'])
                       ->whereHas('postOffices', function($query) use ($currentPostOffice) {
                           $query->where('post_offices.id', $currentPostOffice->id);
                       })
                       ->get();

        $sortedOrders = [
            'cung_quan' => [],
            'noi_thanh' => [],
            'ngoai_thanh' => []
        ];

        foreach ($orders as $order) {
            switch ($order->shipping_type) {
                case 'cung_quan':
                    $sortedOrders['cung_quan'][] = $order;
                    break;
                case 'noi_thanh':
                    $this->processInternalCityOrder($order, $currentPostOffice, $sortedOrders);
                    break;
                case 'ngoai_thanh':
                    $sortedOrders['ngoai_thanh'][] = $order;
                    break;
            }
        }

        return $sortedOrders;
    }

    private function processInternalCityOrder(Order $order, PostOffice $currentPostOffice, &$sortedOrders)
    {
        $nearestPostOffice = $this->findNearestPostOffice($order->sender_coordinates, $currentPostOffice);

        if ($nearestPostOffice && $nearestPostOffice->id !== $currentPostOffice->id) {
            DB::transaction(function () use ($order, $currentPostOffice, $nearestPostOffice) {
                OrderTransfer::create([
                    'order_id' => $order->id,
                    'from_post_office_id' => $currentPostOffice->id,
                    'to_post_office_id' => $nearestPostOffice->id,
                    'status' => 'pending'
                ]);

                $order->update([
                    'status' => Order::STATUS_ARRIVED_AT_POST_OFFICE,
                ]);
            });

            $sortedOrders['noi_thanh'][] = $order;
            Log::info("Order {$order->id} marked for transfer to post office {$nearestPostOffice->id}");
        } else {
            $sortedOrders['noi_thanh'][] = $order;
            Log::info("Order {$order->id} will be processed at current post office {$currentPostOffice->id}");
        }
    }

    private function findNearestPostOffice($senderCoordinates, $currentPostOffice)
    {
        $nearestPostOffice = null;
        $minDistance = self::MAX_INTERNAL_DELIVERY_DISTANCE;

        $postOffices = PostOffice::where('id', '!=', $currentPostOffice->id)->get();

        foreach ($postOffices as $postOffice) {
            $distance = $this->calculateDistance(
                $senderCoordinates[1],
                $senderCoordinates[0],
                $postOffice->latitude,
                $postOffice->longitude
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestPostOffice = $postOffice;
            }
        }

        return $nearestPostOffice;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        return $distance;
    }
}