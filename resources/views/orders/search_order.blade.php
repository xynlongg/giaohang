@extends('layouts.app')

@section('content')
<div class="container">
    @if(isset($message))
        <div class="alert alert-warning">
            {{ $message }}
        </div>
    @endif

    @if(isset($order))
        <h1 class="my-4">Thông tin đơn hàng #{{ $order->tracking_number }}</h1>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2>Thông tin người gửi</h2>
                    </div>
                    <div class="card-body">
                        <p><strong>Tên:</strong> {{ $order->sender_name }}</p>
                        <p><strong>Số điện thoại:</strong> {{ $order->sender_phone }}</p>
                        <p><strong>Địa chỉ:</strong> {{ $order->sender_address }}</p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h2>Thông tin người nhận</h2>
                    </div>
                    <div class="card-body">
                        <p><strong>Tên:</strong> {{ $order->receiver_name }}</p>
                        <p><strong>Số điện thoại:</strong> {{ $order->receiver_phone }}</p>
                        <p><strong>Địa chỉ:</strong> {{ $order->receiver_address }}</p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h2>Thông tin đơn hàng</h2>
                    </div>
                    <div class="card-body">
                        <p><strong>Tiền COD:</strong> {{ number_format($order->total_cod) }} VND</p>
                        <p><strong>Ngày dự kiến giao hàng:</strong> {{ $order->delivery_date ? $order->delivery_date->format('d/m/Y') : 'Chưa xác định' }}</p>
                        <p><strong>Trạng thái:</strong> {{ $order->status }}</p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h2>Thông tin vận chuyển</h2>
                    </div>
                    <div class="card-body">
                        <p><strong>Điểm đi:</strong> {{ $order->sender_address }}</p>
                        <p><strong>Điểm đến:</strong> {{ $order->receiver_address }}</p>
                        <p><strong>Vị trí hiện tại:</strong> 
                            @if($order->current_location)
                                {{ $order->current_location->name }}
                            @else
                                Chưa cập nhật
                            @endif
                        </p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h2>Danh sách sản phẩm</h2>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tên sản phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Giá trị</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->products as $product)
                                    <tr>
                                        <td>{{ $product->name }}</td>
                                        <td>{{ $product->pivot->quantity }}</td>
                                        <td>{{ number_format($product->value * $product->pivot->quantity) }} VND</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2>Bản đồ</h2>
                    </div>
                    <div class="card-body">
                        <div id="map" style="width: 100%; height: 400px;"></div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h2>Thông tin giao nhận</h2>
                    </div>
                    <div class="card-body">
                        <div id="route-info">
                            <div><strong>Khoảng cách:</strong> <span id="distance"></span></div>
                            <div><strong>Thời gian:</strong> <span id="duration"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <a href="#" class="btn btn-primary">Theo dõi đơn hàng</a>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">Quay lại trang chủ</a>
            </div>
        </div>
    @endif
</div>
@endsection

@push('styles')
<link href='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.css' rel='stylesheet' />
<link rel='stylesheet' href='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-directions/v4.1.0/mapbox-gl-directions.css' type='text/css' />
@endpush

@push('scripts')
<script src='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.js'></script>
<script src='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-directions/v4.1.0/mapbox-gl-directions.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    mapboxgl.accessToken = '{{ env('MAPBOX_ACCESS_TOKEN') }}';
    var map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: {{ json_encode($order->sender_coordinates) }},
        zoom: 12
    });

    var senderCoordinates = {{ json_encode($order->sender_coordinates) }};
    var receiverCoordinates = {{ json_encode($order->receiver_coordinates) }};

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

        new mapboxgl.Marker({ color: "#3887be" })
            .setLngLat(senderCoordinates)
            .setPopup(new mapboxgl.Popup().setHTML("<h3>Điểm đi</h3><p>{{ $order->sender_name }}</p><p>{{ $order->sender_address }}</p>"))
            .addTo(map);

        new mapboxgl.Marker({ color: "#f30" })
            .setLngLat(receiverCoordinates)
            .setPopup(new mapboxgl.Popup().setHTML("<h3>Điểm đến</h3><p>{{ $order->receiver_name }}</p><p>{{ $order->receiver_address }}</p>"))
            .addTo(map);

        @if($order->current_location)
            new mapboxgl.Marker({ color: "#00ff00" })
                .setLngLat({{ json_encode($order->current_location->coordinates) }})
                .setPopup(new mapboxgl.Popup().setHTML("<h3>Vị trí hiện tại</h3><p>{{ $order->current_location->name }}</p>"))
                .addTo(map);
        @endif
    });

    directions.on('route', function(e) {
        var route = e.route[0];
        document.getElementById('distance').textContent = (route.distance / 1000).toFixed(2) + ' km';
        document.getElementById('duration').textContent = Math.floor(route.duration / 60) + ' phút';
    });
});
</script>
@endpush