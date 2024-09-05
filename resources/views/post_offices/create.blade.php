@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Thêm Bưu cục mới</h1>
    
    <div class="row mb-3">
        <div class="col-md-12">
            <div id="geocoder" class="geocoder"></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <form action="{{ route('post_offices.store') }}" method="POST" id="postOfficeForm">
                @csrf
                <div class="form-group">
                    <label for="name">Tên bưu cục:</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="address">Địa chỉ:</label>
                    <input type="text" class="form-control" id="address" name="address" required>
                </div>
                <div class="form-group">
                    <label for="district">Quận/Huyện:</label>
                    <input type="text" class="form-control" id="district" name="district" required>
                </div>
                <div class="form-group">
                    <label for="province">Tỉnh/Thành phố:</label>
                    <input type="text" class="form-control" id="province" name="province" required>
                </div>
                <input type="hidden" id="coordinates" name="coordinates">

                <button type="submit" class="btn btn-primary">Thêm Bưu cục</button>
            </form>
        </div>
        <div class="col-md-6">
            <div id="map" style="width: 100%; height: 400px;"></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.css' rel='stylesheet' />
<link href='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.0/mapbox-gl-geocoder.css' rel='stylesheet' />
<style>
.geocoder {
    position: relative;
    z-index: 1;
    width: 100%;
    margin-bottom: 10px;
}
.mapboxgl-ctrl-geocoder {
    min-width: 100%;
}
</style>
@endpush

@push('scripts')
<script src='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.js'></script>
<script src='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.0/mapbox-gl-geocoder.min.js'></script>
<script>
mapboxgl.accessToken = '{{ env('MAPBOX_ACCESS_TOKEN') }}';
var map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/streets-v11',
    center: [105.85, 21.02], // Tọa độ mặc định (Hà Nội)
    zoom: 12
});

var marker = new mapboxgl.Marker({ draggable: true })
    .setLngLat([105.85, 21.02])
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

document.getElementById('geocoder').appendChild(geocoder.onAdd(map));

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