@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Chi tiết Bưu cục</h1>
    <div class="row">
        <div class="col-md-6">
            <table class="table">
                <tr>
                    <th>Tên bưu cục:</th>
                    <td>{{ $postOffice->name }}</td>
                </tr>
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
            <a href="{{ route('post_offices.edit', $postOffice) }}" class="btn btn-primary">Chỉnh sửa</a>
            <a href="{{ route('post_offices.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
        </div>
        <div class="col-md-6">
            <div id="map" style="width: 100%; height: 400px;"></div>
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
    var mapboxToken = '{{ env('MAPBOX_ACCESS_TOKEN') }}';

    if (!mapboxToken) {
        console.error('Mapbox token is missing. Please check your .env file.');
        document.getElementById('map').innerHTML = '<p class="text-danger">Error: Mapbox token is missing.</p>';
        return;
    }

    mapboxgl.accessToken = mapboxToken;

    // Parse the coordinates from JSON string to JavaScript array
    var coordinates = JSON.parse(@json($postOffice->coordinates));

    // Ensure coordinates is an array with two elements
    if (!Array.isArray(coordinates) || coordinates.length !== 2) {
        console.error('Invalid coordinates:', coordinates);
        document.getElementById('map').innerHTML = '<p class="text-danger">Error: Invalid coordinates.</p>';
        return;
    }

    var longitude = coordinates[0];
    var latitude = coordinates[1];

    // Update the coordinates in the table
    document.getElementById('coordinates').textContent = `Longitude: ${longitude}, Latitude: ${latitude}`;

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
        marker.togglePopup();

        console.log('Mapbox initialized successfully');
    } catch (error) {
        console.error('Error initializing Mapbox:', error);
        document.getElementById('map').innerHTML = '<p class="text-danger">Error: ' + error.message + '</p>';
    }
});
</script>
@endpush