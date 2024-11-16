@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Thêm Kho Tổng mới</h1>
    
    <div class="row mb-3">
        <div class="col-md-12">
            <div id="geocoder" class="geocoder"></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <form action="{{ route('provincial-warehouses.store') }}" method="POST" id="provincialWarehouseForm">
                @csrf
                <div class="form-group">
                    <label for="name">Tên kho tổng:</label>
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

                <button type="submit" class="btn btn-primary">Thêm Kho Tổng</button>
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
    document.getElementById('coordinates').value = JSON.stringify([lngLat.lng, lngLat.lat]);
}

marker.on('dragend', function() {
    var lngLat = marker.getLngLat();
    updateCoordinates(lngLat);
});

var geocoder = new MapboxGeocoder({
    accessToken: mapboxgl.accessToken,
    mapboxgl: mapboxgl,
    marker: false,
    placeholder: 'Nhập địa chỉ kho tổng'
});

document.getElementById('geocoder').appendChild(geocoder.onAdd(map));

geocoder.on('result', function(e) {
    var lngLat = e.result.center;
    marker.setLngLat(lngLat);
    updateCoordinates({lng: lngLat[0], lat: lngLat[1]});
    
    document.getElementById('address').value = e.result.place_name;
    
    var context = e.result.context || [];
    context.forEach(function(item) {
        if (item.id.startsWith('district')) {
            document.getElementById('district').value = item.text;
        } else if (item.id.startsWith('region')) {
            document.getElementById('province').value = item.text;
        }
    });
});

document.getElementById('provincialWarehouseForm').addEventListener('submit', function(e) {
    if (!document.getElementById('coordinates').value) {
        e.preventDefault();
        alert('Vui lòng chọn vị trí kho tổng trên bản đồ');
    }
});

// Thêm event listeners cho các trường input district và province
document.getElementById('district').addEventListener('change', updateMapFromInputs);
document.getElementById('province').addEventListener('change', updateMapFromInputs);

function updateMapFromInputs() {
    var district = document.getElementById('district').value;
    var province = document.getElementById('province').value;
    
    if (district && province) {
        var query = district + ', ' + province + ', Vietnam';
        
        fetch(`https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?access_token=${mapboxgl.accessToken}`)
            .then(response => response.json())
            .then(data => {
                if (data.features && data.features.length > 0) {
                    var lngLat = data.features[0].center;
                    map.flyTo({center: lngLat, zoom: 12});
                    marker.setLngLat(lngLat);
                    updateCoordinates({lng: lngLat[0], lat: lngLat[1]});
                    document.getElementById('address').value = data.features[0].place_name;
                }
            })
            .catch(error => console.error('Error:', error));
    }
}
</script>
@endpush