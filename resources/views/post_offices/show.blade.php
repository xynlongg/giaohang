@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Chi tiết Bưu cục</h1>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ $postOffice->name }}</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>Địa chỉ:</th>
                            <td>{{ $postOffice->address }}</td>
                        </tr>
                        <tr>
                            <th>Quận/Huyện:</th>
                            <td>{{ $postOffice->district }}</td>
                        </tr>
                        <tr>
                            <th>Tỉnh/Thành phố:</th>
                            <td>{{ $postOffice->province }}</td>
                        </tr>
                        <tr>
                            <th>Tọa độ:</th>
                            <td id="coordinates">Đang tải...</td>
                        </tr>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="{{ route('post_offices.edit', $postOffice) }}" class="btn btn-primary">Chỉnh sửa</a>
                    <a href="{{ route('post_offices.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Bản đồ</h5>
                    <div id="map" style="width: 100%; height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.css' rel='stylesheet' />
<style>
    #map { border-radius: 4px; }
    .mapboxgl-popup-content { padding: 15px; }
</style>
@endpush

@push('scripts')
<script src='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var mapboxToken = '{{ env('MAPBOX_ACCESS_TOKEN') }}';

    if (!mapboxToken) {
        console.error('Mapbox token is missing. Please check your .env file.');
        document.getElementById('map').innerHTML = '<div class="alert alert-danger">Error: Mapbox token is missing.</div>';
        return;
    }

    mapboxgl.accessToken = mapboxToken;

    var coordinates = @json($postOffice->coordinates);
    
    // Kiểm tra nếu coordinates là chuỗi JSON, thì parse nó
    if (typeof coordinates === 'string') {
        try {
            coordinates = JSON.parse(coordinates);
        } catch (error) {
            console.error('Error parsing coordinates:', error);
            document.getElementById('map').innerHTML = '<div class="alert alert-danger">Error: Invalid coordinates format.</div>';
            return;
        }
    }

    // Kiểm tra tính hợp lệ của coordinates
    if (!Array.isArray(coordinates) || coordinates.length !== 2 || 
        typeof coordinates[0] !== 'number' || typeof coordinates[1] !== 'number') {
        console.error('Invalid coordinates:', coordinates);
        document.getElementById('map').innerHTML = '<div class="alert alert-danger">Error: Invalid coordinates data.</div>';
        return;
    }

    var longitude = coordinates[0];
    var latitude = coordinates[1];

    document.getElementById('coordinates').textContent = `Longitude: ${longitude.toFixed(6)}, Latitude: ${latitude.toFixed(6)}`;

    try {
        var map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v11',
            center: [longitude, latitude],
            zoom: 15
        });

        map.addControl(new mapboxgl.NavigationControl());

        var marker = new mapboxgl.Marker()
            .setLngLat([longitude, latitude])
            .addTo(map);

        var popup = new mapboxgl.Popup({ offset: 25 })
            .setHTML(
                '<h3>' + @json($postOffice->name) + '</h3>' +
                '<p>' + @json($postOffice->address) + '</p>'
            );

        marker.setPopup(popup);

        map.on('load', function() {
            marker.togglePopup(); // Open popup when map loads
        });

        console.log('Mapbox initialized successfully');
    } catch (error) {
        console.error('Error initializing Mapbox:', error);
        document.getElementById('map').innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
    }
});
</script>
@endpush