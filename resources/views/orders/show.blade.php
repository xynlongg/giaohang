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
                        <p><strong>Trạng Thái:</strong> <span class="badge bg-{{ $order->status_class }}">{{ $order->status }}</span></p>
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
                                            @else
                                                {{ $history->location_type }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($history->location_type == 'post_office' && $history->postOffice)
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
                            <label for="start">Vị trí hiện tại:</label>
                            <input type="text" id="start" class="form-control" value="{{ $order->current_location }}" readonly>
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
                        <p>Vị trí hiện tại: <span id="current-location">{{ $order->current_location ?? 'Chưa có thông tin' }}</span></p>
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

                <!-- Hủy đơn hàng -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h2>Hành động</h2>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            @php
                                $isCancellable = $order->status == 'pending' || 
                                    ($order->status == 'assigned_to_post_office' && 
                                    (!$order->pickup_date || now()->lte($order->pickup_date->subDay())));
                            @endphp

                            @if($isCancellable)
                                <button type="button" class="list-group-item list-group-item-action list-group-item-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                                    Yêu cầu hủy đơn hàng
                                </button>
                            @else
                                <button type="button" class="list-group-item list-group-item-action list-group-item-danger" disabled>
                                    Không thể hủy đơn hàng
                                </button>
                                <small class="text-muted">Đơn hàng đã quá hạn hủy hoặc đang được xử lý</small>
                            @endif
                           
                        </div>
                    </div>
    </div>
</div>

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelOrderModalLabel">Yêu cầu hủy đơn hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('orders.cancel', $order->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Bạn chắc chắn muốn hủy đơn hàng này?</p>
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label">Lý do hủy đơn</label>
                        <select class="form-select" id="cancellation_reason" name="reason" required>
                            <option value="">Chọn lý do</option>
                            <option value="changed_mind">Đổi ý không muốn mua nữa</option>
                            <option value="found_better_deal">Tìm được sản phẩm tốt hơn</option>
                            <option value="financial_reasons">Lý do tài chính</option>
                            <option value="other">Lý do khác</option>
                        </select>
                    </div>
                    <div class="mb-3" id="other_reason_container" style="display: none;">
                        <label for="other_reason" class="form-label">Lý do khác</label>
                        <textarea class="form-control" id="other_reason" name="other_reason" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-danger">Gửi yêu cầu hủy đơn</button>
                </div>
            </form>
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
<script src='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-directions/v4.1.0/mapbox-gl-directions.js'></script>
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');

    // Hủy đơn
    const reasonSelect = document.getElementById('cancellation_reason');
    const otherReasonContainer = document.getElementById('other_reason_container');

    if (reasonSelect) {
        reasonSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                otherReasonContainer.style.display = 'block';
            } else {
                otherReasonContainer.style.display = 'none';
            }
        });
    }

    var orderId = {{ $order->id }};
    var map, directions, currentMarker, receiverMarker;
    var currentCoordinates = {{ json_encode($order->current_coordinates) }};
    var receiverCoordinates = {{ json_encode($order->receiver_coordinates) }};

    console.log('Initial coordinates:', { current: currentCoordinates, receiver: receiverCoordinates });

    // Khởi tạo Mapbox
    mapboxgl.accessToken = '{{ env('MAPBOX_ACCESS_TOKEN') }}';
    map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: currentCoordinates,
        zoom: 12
    });

    console.log('Mapbox initialized');

    // Khởi tạo Mapbox Directions
    directions = new MapboxDirections({
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
        console.log('Map loaded');
        updateRoute();

        // Thêm marker cho vị trí hiện tại
        currentMarker = new mapboxgl.Marker({ color: "#00FF00" })
            .setLngLat(currentCoordinates)
            .setPopup(new mapboxgl.Popup().setHTML("<h3>Vị trí hiện tại</h3><p>" + document.getElementById('current-location').innerText + "</p>"))
            .addTo(map);

        // Thêm marker cho người nhận
        receiverMarker = new mapboxgl.Marker({ color: "#f30" })
            .setLngLat(receiverCoordinates)
            .setPopup(new mapboxgl.Popup().setHTML("<h3>Người nhận</h3><p>{{ $order->receiver_name }}</p><p>{{ $order->receiver_address }}</p>"))
            .addTo(map);

        fitMapToMarkers();
    });

    // Cập nhật route
    function updateRoute() {
        console.log('Updating route');
        directions.setOrigin(currentCoordinates);
        directions.setDestination(receiverCoordinates);
    }

    // Fit map để hiển thị cả hai marker
    function fitMapToMarkers() {
        console.log('Fitting map to markers');
        var bounds = new mapboxgl.LngLatBounds();
        bounds.extend(currentCoordinates);
        bounds.extend(receiverCoordinates);
        map.fitBounds(bounds, { padding: 50 });
    }

    // Hiển thị thông tin đường đi
    directions.on('route', function(e) {
        console.log('Route calculated', e.route[0]);
        document.getElementById('distance').textContent = (e.route[0].distance / 1000).toFixed(2) + ' km';
        document.getElementById('duration').textContent = (e.route[0].duration / 60).toFixed(0) + ' phút';
    });

    // Khởi tạo Pusher
    var pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
        cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
        encrypted: true
    });

    console.log('Pusher initialized');

    // Đăng ký kênh cho đơn hàng cụ thể
    var channel = pusher.subscribe('order.' + orderId);

    console.log('Subscribed to channel: order.' + orderId);

    // Lắng nghe sự kiện cập nhật đơn hàng
    channel.bind('order.updated', function(data) {
        console.log('Received order update', data);

        // Cập nhật thông tin đơn hàng
        document.getElementById('current-location').textContent = data.current_location;
        var statusBadge = document.querySelector('.badge');
        if (statusBadge) {
            statusBadge.textContent = data.status;
            statusBadge.className = 'badge bg-' + data.status_class;
        }

        // Cập nhật vị trí hiện tại trên bản đồ
        currentCoordinates = data.current_coordinates;
        if (currentMarker) {
            currentMarker.setLngLat(currentCoordinates);
            currentMarker.getPopup().setHTML("<h3>Vị trí hiện tại</h3><p>" + data.current_location + "</p>");
        }

        // Cập nhật route
        updateRoute();

        // Fit map lại để hiển thị cả hai marker
        fitMapToMarkers();

        // Cập nhật lịch sử vị trí
        var historyTable = document.querySelector('.table tbody');
        if (historyTable) {
            var newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>${data.timestamp}</td>
                <td>${data.location_type}</td>
                <td>${data.address}</td>
                <td>${data.status}</td>
            `;
            historyTable.insertBefore(newRow, historyTable.firstChild);
        }

        console.log('UI updated');
    });
});
</script>
@endpush