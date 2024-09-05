@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Chỉnh sửa Bưu cục</h1>
    <form action="{{ route('post_offices.update', $postOffice) }}" method="POST" id="postOfficeForm">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Tên bưu cục:</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $postOffice->name }}" required>
        </div>
        <div class="form-group">
            <label for="address">Địa chỉ:</label>
            <input type="text" class="form-control" id="address" name="address" value="{{ $postOffice->address }}" required>
        </div>
        <div class="form-group">
            <label for="district">Quận/Huyện:</label>
            <input type="text" class="form-control" id="district" name="district" value="{{ $postOffice->district }}" required>
        </div>
        <div class="form-group">
            <label for="province">Tỉnh/Thành phố:</label>
            <input type="text" class="form-control" id="province" name="province" value="{{ $postOffice->province }}" required>
        </div>
        <input type="hidden" id="coordinates" name="coordinates" value="{{ json_encode($postOffice->coordinates) }}">

        <div id="map" style="width: 100%; height: 400px;" class="mt-3 mb-3"></div>

        <button type="submit" class="btn btn-primary">Cập nhật Bưu cục</button>
    </form>
</div>
@endsection

@push('styles')
<link href='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.css' rel='stylesheet' />
<link href='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.0/mapbox-gl-geocoder.css' rel='stylesheet' />
@endpush

@push('scripts')
<script src='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.js'></script>
<script src='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.0/mapbox-gl-geocoder.min.js'></script>
<script>
mapboxgl.accessToken = '{{ env('MAPBOX_ACCESS_TOKEN') }}';
var coordinates = @json($postOffice->coordinates);
var map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/streets-v11',
    center: coordinates,
    zoom: 12
});

var marker = new mapboxgl.Marker({ draggable: true })
    .setLngLat(coordinates)
    .addTo(map);

function updateCoordinates(lngLat) {
    document.getElementById('coordinates').value = JSON.stringify(lngLat);
}

marker.on('dragend', function() {
    updateCoordinates(marker.getLngLat());
});

var geocoder = new MapboxGeocoder({
    accessToken: mapboxgl.accessToken,
    mapboxgl: mapboxgl,
    marker: false,
    placeholder: 'Nhập địa chỉ bưu cục'
});

map.addControl(geocoder);

geocoder.on('result', function(e) {
    marker.setLngLat(e.result.center);
    updateCoordinates(e.result.center);
    
    document.getElementById('address').value = e.result.place_name;
    
    // Cập nhật district và province từ kết quả tìm kiếm
    var context = e.result.context || [];
    context.forEach(function(item) {
        if (item.id.startsWith('district')) {
            document.getElementById('district').value = item.text;
        } else if (item.id.startsWith('region')) {
            document.getElementById('province').value = item.text;
        }
    });
});

document.getElementById('postOfficeForm').addEventListener('submit', function(e) {
    if (!document.getElementById('coordinates').value) {
        e.preventDefault();
        alert('Vui lòng chọn vị trí bưu cục trên bản đồ');
    }
});
</script>
@endpush