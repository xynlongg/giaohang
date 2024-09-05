@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Chi tiết Đơn Hàng #{{ $order->tracking_number }}</h1>

    <div id="order-details" data-order-id="{{ $order->id }}">
        <div class="row">
            <div class="col-md-6">
                <!-- Thông tin người gửi -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h2>Thông tin người gửi</h2>
                    </div>
                    <div class="card-body">
                        <p><strong>Tên:</strong> {{ $order->sender_name }}</p>
                        <p><strong>Số điện thoại:</strong> {{ $order->sender_phone }}</p>
                        <p><strong>Địa chỉ:</strong> {{ $order->sender_address }}</p>
                    </div>
                </div>

                <!-- Thông tin người nhận -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h2>Thông tin người nhận</h2>
                    </div>
                    <div class="card-body">
                        <p><strong>Tên:</strong> {{ $order->receiver_name }}</p>
                        <p><strong>Số điện thoại:</strong> {{ $order->receiver_phone }}</p>
                        <p><strong>Địa chỉ:</strong> {{ $order->receiver_address }}</p>
                    </div>
                </div>

                <!-- Thông tin đơn hàng -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h2>Thông tin đơn hàng</h2>
                    </div>
                    <div class="card-body">
                        <p><strong>Mã đơn hàng:</strong> {{ $order->tracking_number }}</p>
                        <p><strong>Tổng khối lượng:</strong> {{ $order->total_weight }} kg</p>
                        <p><strong>Tổng tiền thu hộ:</strong> {{ number_format($order->total_cod) }} VND</p>
                        <p><strong>Tổng giá trị hàng:</strong> {{ number_format($order->total_value) }} VND</p>
                        <p><strong>Phí vận chuyển:</strong> {{ number_format($order->shipping_fee) }} VND</p>
                        <p><strong>Tổng cộng:</strong> {{ number_format($order->total_amount) }} VND</p>
                        <p><strong>Trạng thái:</strong> <span class="badge bg-{{ $order->status_class }}">{{ ucfirst($order->status) }}</span></p>
                        <p><strong>Ngày lấy hàng:</strong> {{ $order->pickup_date ? $order->pickup_date->format('d/m/Y H:i') : 'N/A' }}</p>
                        <p><strong>Ngày giao hàng dự kiến:</strong> {{ $order->delivery_date ? $order->delivery_date->format('d/m/Y H:i') : 'N/A' }}</p>
                        <p><strong>Lấy hàng tại bưu cục:</strong> {{ $order->is_pickup_at_post_office ? 'Có' : 'Không' }}</p>
                        @if($order->is_pickup_at_post_office && $order->pickupLocation)
                            <p><strong>Bưu cục lấy hàng:</strong> {{ $order->pickupLocation->name }} - {{ $order->pickupLocation->address }}</p>
                        @endif
                    </div>
                </div>
                <div class="card mb-3">
                <div class="card-header">
                    <h2>Lịch sử vị trí</h2>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Loại vị trí</th>
                                <th>Địa chỉ</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->locationHistory()->orderBy('timestamp', 'desc')->get() as $history)
                                <tr>
                                    <td>{{ $history->timestamp->format('d/m/Y H:i:s') }}</td>
                                    <td>
                                        @if($history->location_type == 'sender')
                                            Người gửi
                                        @elseif($history->location_type == 'post_office')
                                            Bưu cục
                                        @elseif($history->location_type == 'receiver')
                                            Người nhận
                                        @endif
                                    </td>
                                    <td>
                                        @if($history->location_type == 'post_office')
                                            {{ $history->postOffice->name }} - 
                                        @endif
                                        {{ $history->address }}
                                    </td>
                                    <td>{{ ucfirst($history->status) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
                <!-- Thông tin giao nhận -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h2>Thông tin giao nhận</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="start">Điểm đi:</label>
                            <input type="text" id="start" class="form-control" value="{{ $order->sender_address }}" readonly>
                        </div>
                        <div class="form-group">
                            <label for="end">Điểm đến:</label>
                            <input type="text" id="end" class="form-control" value="{{ $order->receiver_address }}" readonly>
                        </div>
                        <div id="route-info" class="mt-3">
                            <div><strong>Khoảng cách:</strong> <span id="distance"></span></div>
                            <div><strong>Thời gian:</strong> <span id="duration"></span></div>
                        </div>
                    </div>
                </div>

                <!-- Danh sách sản phẩm -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h2>Danh sách sản phẩm</h2>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tên sản phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Giá</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->products as $product)
                                    <tr>
                                        <td>{{ $product->name }}</td>
                                        <td>{{ $product->pivot->quantity }}</td>
                                        <td>{{ number_format($product->value) }} VND</td>
                                        <td>{{ number_format($product->value * $product->pivot->quantity) }} VND</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <!-- Bản đồ -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h2>Bản đồ</h2>
                    </div>
                    <div class="card-body">
                        <div id="map" style="width: 100%; height: 400px;"></div>
                    </div>
                </div>

                <!-- Vị trí hiện tại -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h2>Vị trí hiện tại</h2>
                    </div>
                    <div class="card-body">
                        <p>Vị trí hiện tại: <span id="current-location">{{ $order->current_status ?? 'Chưa có thông tin' }}</span></p>
                    </div>
                </div>

                <!-- QR Code -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h2>QR Code</h2>
                    </div>
                    <div class="card-body">
                        {!! $order->qr_code !!}
                        <p class="mt-2">Quét mã QR để theo dõi đơn hàng</p>
                        <p><strong>Mã theo dõi:</strong> {{ $order->tracking_number }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.css' rel='stylesheet' />
<link rel='stylesheet' href='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-directions/v4.1.0/mapbox-gl-directions.css' type='text/css' />
@endpush

@push('scripts')
<script src='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.js'></script>
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script src='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-directions/v4.1.0/mapbox-gl-directions.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo Mapbox với Access Token
    mapboxgl.accessToken = '{{ env('MAPBOX_ACCESS_TOKEN') }}';
    var map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: {{ json_encode($order->sender_coordinates) }},
        zoom: 12
    });

    var senderCoordinates = {{ json_encode($order->sender_coordinates) }};
    var receiverCoordinates = {{ json_encode($order->receiver_coordinates) }};

    // Khởi tạo Mapbox Directions để chỉ đường
    var directions = new MapboxDirections({
        accessToken: mapboxgl.accessToken,
        unit: 'metric',
        profile: 'mapbox/driving',
        alternatives: false,
        geometries: 'geojson',
        controls: { inputs: false, instructions: false },
        flyTo: false
    });

    map.addControl(directions, 'top-left');

    map.on('load', function() {
        directions.setOrigin(senderCoordinates);
        directions.setDestination(receiverCoordinates);

        // Thêm marker cho người gửi và người nhận
        new mapboxgl.Marker({ color: "#3887be" })
            .setLngLat(senderCoordinates)
            .setPopup(new mapboxgl.Popup().setHTML("<h3>Người gửi</h3><p>{{ $order->sender_name }}</p><p>{{ $order->sender_address }}</p>"))
            .addTo(map);

        new mapboxgl.Marker({ color: "#f30" })
            .setLngLat(receiverCoordinates)
            .setPopup(new mapboxgl.Popup().setHTML("<h3>Người nhận</h3><p>{{ $order->receiver_name }}</p><p>{{ $order->receiver_address }}</p>"))
            .addTo(map);
    });

    // Hiển thị thông tin đường đi
    directions.on('route', function(e) {
        document.getElementById('distance').textContent = e.route[0].distance.toFixed(2) + ' meters';
        document.getElementById('duration').textContent = (e.route[0].duration / 60).toFixed(2) + ' minutes';
    });
});
</script>
@endpush