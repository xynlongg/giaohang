@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Cập nhật Đơn Hàng #{{ $order->tracking_number }}</h1>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h2>Thông tin đơn hàng</h2>
                </div>
                <div class="card-body">
                    <p><strong>Mã đơn hàng:</strong> {{ $order->tracking_number }}</p>
                    <p><strong>Người gửi:</strong> {{ $order->sender_name }}</p>
                    <p><strong>Người nhận:</strong> {{ $order->receiver_name }}</p>
                    <p><strong>Trạng thái hiện tại:</strong> <span class="badge bg-{{ $order->status_class }}">{{ ucfirst($order->status) }}</span></p>
                    <p><strong>Vị trí hiện tại:</strong> {{ $order->current_location ?? 'Chưa có thông tin' }}</p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h2>Cập nhật vị trí và trạng thái</h2>
                </div>
                <div class="card-body">
                <form id="update-order-form" action="{{ route('orders.update', $order->id) }}" method="POST">
                @csrf
                        <div class="form-group mb-3">
                            <label for="post_office_id">Chọn bưu cục:</label>
                            <select name="post_office_id" id="post_office_id" class="form-control">
                                @foreach($postOffices as $postOffice)
                                    <option value="{{ $postOffice->id }}" data-coordinates="{{ json_encode($postOffice->coordinates) }}">
                                        {{ $postOffice->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="status">Trạng thái đơn hàng:</label>
                            <select name="status" id="status" class="form-control">
                                <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Chưa xác nhận</option>
                                <option value="confirmed" {{ $order->status == 'confirmed' ? 'selected' : '' }}>Xác nhận</option>
                                <option value="picking_up" {{ $order->status == 'picking_up' ? 'selected' : '' }}>Shipper đang đi lấy hàng</option>
                                <option value="at_post_office" {{ $order->status == 'at_post_office' ? 'selected' : '' }}>Đơn hàng đã đến bưu cục</option>
                                <option value="delivering" {{ $order->status == 'delivering' ? 'selected' : '' }}>Đang giao hàng</option>
                                <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>Đã giao hàng</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h2>Bản đồ</h2>
                </div>
                <div class="card-body">
                    <div id="map" style="width: 100%; height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.css' rel='stylesheet' />
@endpush

@push('scripts')
<script src='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    mapboxgl.accessToken = '{{ env('MAPBOX_ACCESS_TOKEN') }}';
    var map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: {{ json_encode($order->current_coordinates ?? $order->sender_coordinates) }},
        zoom: 12
    });

    var currentMarker;

    function updateMap(coordinates) {
        if (currentMarker) {
            currentMarker.remove();
        }
        currentMarker = new mapboxgl.Marker({ color: "#00FF00" })
            .setLngLat(coordinates)
            .addTo(map);
        map.flyTo({ center: coordinates, zoom: 14 });
    }

    // Khởi tạo marker ban đầu
    updateMap({{ json_encode($order->current_coordinates ?? $order->sender_coordinates) }});

    document.getElementById('update-order-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);

        fetch('{{ route('orders.update', $order->id) }}', {
            method: 'POST', // Đảm bảo sử dụng phương thức POST
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json' // Yêu cầu phản hồi dạng JSON
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Cập nhật thành công!');
                document.querySelector('.card-body p:nth-child(4)').innerHTML = '<strong>Trạng thái hiện tại:</strong> <span class="badge bg-' + data.status_class + '">' + data.status + '</span>';
                document.querySelector('.card-body p:nth-child(5)').innerHTML = '<strong>Vị trí hiện tại:</strong> ' + data.location;

                var selectedOption = document.querySelector('#post_office_id option:checked');
                var coordinates = JSON.parse(selectedOption.dataset.coordinates);
                updateMap(coordinates);
            } else {
                alert('Có lỗi xảy ra khi cập nhật: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi cập nhật: ' + error.message);
        });
});
</script>
@endpush