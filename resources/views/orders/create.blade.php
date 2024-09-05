@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Tạo Đơn Hàng Mới</h1>
    <form action="{{ route('orders.store') }}" method="POST" id="orderForm">
        @csrf
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Phương thức lấy hàng:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" id="pickup_type_post_office" name="pickup_type" value="post_office">
                        <label class="form-check-label" for="pickup_type_post_office">
                            Gửi tại bưu cục
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" id="pickup_type_home" name="pickup_type" value="home" checked>
                        <label class="form-check-label" for="pickup_type_home">
                            Lấy hàng tận nơi
                        </label>
                    </div>
                </div>

                <div id="post_office_section" style="display: none;">
                    <div class="form-group">
                        <label for="pickup_location_id">Chọn bưu cục gửi hàng:</label>
                        <select class="form-control" id="pickup_location_id" name="pickup_location_id">
                            @foreach($postOffices as $postOffice)
                                <option value="{{ $postOffice->id }}">{{ $postOffice->name }} - {{ $postOffice->address }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="home_pickup_section">
                    <div class="form-group">
                        <label for="pickup_date">Ngày lấy hàng:</label>
                        <input type="date" class="form-control" id="pickup_date" name="pickup_date" required>
                    </div>
                    <div class="form-group">
                        <label for="pickup_time">Thời gian lấy hàng:</label>
                        <select class="form-control" id="pickup_time" name="pickup_time" required>
                            <option value="08:00">8:00 AM</option>
                            <option value="09:00">9:00 AM</option>
                            <option value="10:00">10:00 AM</option>
                            <option value="11:00">11:00 AM</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="delivery_date">Ngày giao hàng dự kiến:</label>
                    <input type="date" class="form-control" id="delivery_date" name="delivery_date" readonly>
                </div>

                <h2>Thông tin người gửi</h2>
                <div class="form-group">
                    <label for="sender_name">Tên người gửi:</label>
                    <input type="text" class="form-control" id="sender_name" name="sender_name" required>
                </div>
                <div class="form-group">
                    <label for="sender_phone">Số điện thoại người gửi:</label>
                    <input type="text" class="form-control" id="sender_phone" name="sender_phone" required>
                </div>
                <div class="form-group">
                    <label for="sender_address">Địa chỉ người gửi:</label>
                    <input type="text" class="form-control" id="sender_address" name="sender_address" required>
                </div>
                <input type="hidden" id="sender_coordinates" name="sender_coordinates">

                <h2>Thông tin người nhận</h2>
                <div class="form-group">
                    <label for="receiver_name">Tên người nhận:</label>
                    <input type="text" class="form-control" id="receiver_name" name="receiver_name" required>
                </div>
                <div class="form-group">
                    <label for="receiver_phone">Số điện thoại người nhận:</label>
                    <input type="text" class="form-control" id="receiver_phone" name="receiver_phone" required>
                </div>
                <div class="form-group">
                    <label for="receiver_address">Địa chỉ người nhận:</label>
                    <input type="text" class="form-control" id="receiver_address" name="receiver_address" required>
                </div>
                <input type="hidden" id="receiver_coordinates" name="receiver_coordinates">
            </div>
            <div class="col-md-6">
                <div id="map" style="width: 100%; height: 400px;"></div>
            </div>
        </div>

        <h2>Sản phẩm</h2>
        <div id="products">
            <div class="product">
                <div class="form-group">
                    <label for="product">Chọn sản phẩm:</label>
                    <select class="form-control product-select" name="products[0][id]" required>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} - {{ number_format($product->value) }} VND</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="product_quantity">Số lượng:</label>
                    <input type="number" class="form-control" name="products[0][quantity]" required min="1">
                </div>
                <div class="form-group">
                    <label for="product_cod">Tiền thu hộ (VND):</label>
                    <input type="number" class="form-control" name="products[0][cod_amount]" required min="0">
                </div>
                <div class="form-group">
                    <label for="product_weight">Khối lượng (kg):</label>
                    <input type="number" step="0.01" class="form-control" name="products[0][weight]" required min="0">
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-secondary" id="add_product">Thêm sản phẩm</button>

        <button type="submit" class="btn btn-primary mt-3">Tạo đơn hàng</button>
    </form>

    <div id="orderCreatedInfo" style="display: none;">
        <h2>Thông tin đơn hàng đã tạo</h2>
        <p>Mã theo dõi: <span id="trackingNumber"></span></p>
        <div id="qrCodeContainer"></div>
    </div>
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
var map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/streets-v11',
    center: [105.85, 21.02], // Tọa độ mặc định (Hà Nội)
    zoom: 12
});

var senderMarker = new mapboxgl.Marker({ color: "#3887be", draggable: true })
    .setLngLat([105.85, 21.02])
    .addTo(map);

var receiverMarker = new mapboxgl.Marker({ color: "#f30", draggable: true })
    .setLngLat([105.86, 21.03])
    .addTo(map);

function updateCoordinates(type, lngLat) {
    document.getElementById(type + '_coordinates').value = JSON.stringify([lngLat.lng, lngLat.lat]);
}

senderMarker.on('dragend', function() {
    updateCoordinates('sender', senderMarker.getLngLat());
    updateEstimatedDeliveryDate();
});

receiverMarker.on('dragend', function() {
    updateCoordinates('receiver', receiverMarker.getLngLat());
    updateEstimatedDeliveryDate();
});

// Add geocoder for sender address
var senderGeocoder = new MapboxGeocoder({
    accessToken: mapboxgl.accessToken,
    mapboxgl: mapboxgl,
    marker: false,
    placeholder: 'Nhập địa chỉ người gửi'
});

senderGeocoder.on('result', function(e) {
    document.getElementById('sender_address').value = e.result.place_name;
    senderMarker.setLngLat(e.result.center);
    updateCoordinates('sender', { lng: e.result.center[0], lat: e.result.center[1] });
    updateEstimatedDeliveryDate();
});

document.getElementById('sender_address').parentNode.insertBefore(senderGeocoder.onAdd(map), document.getElementById('sender_address'));

// Add geocoder for receiver address
var receiverGeocoder = new MapboxGeocoder({
    accessToken: mapboxgl.accessToken,
    mapboxgl: mapboxgl,
    marker: false,
    placeholder: 'Nhập địa chỉ người nhận'
});

receiverGeocoder.on('result', function(e) {
    document.getElementById('receiver_address').value = e.result.place_name;
    receiverMarker.setLngLat(e.result.center);
    updateCoordinates('receiver', { lng: e.result.center[0], lat: e.result.center[1] });
    updateEstimatedDeliveryDate();
});

document.getElementById('receiver_address').parentNode.insertBefore(receiverGeocoder.onAdd(map), document.getElementById('receiver_address'));

// Add product dynamically
document.getElementById('add_product').addEventListener('click', function() {
    var productDiv = document.createElement('div');
    productDiv.className = 'product';
    var productTemplate = document.querySelector('.product').cloneNode(true);
    
    var productCount = document.querySelectorAll('.product').length;
    productTemplate.querySelectorAll('select, input').forEach(function(element) {
        element.name = element.name.replace('[0]', '[' + productCount + ']');
        element.value = ''; // Reset giá trị
    });
    
    productDiv.innerHTML = productTemplate.innerHTML;
    document.getElementById('products').appendChild(productDiv);
});

// Toggle pickup type
document.querySelectorAll('input[name="pickup_type"]').forEach(function(elem) {
    elem.addEventListener("change", function(event) {
        var postOfficeSection = document.getElementById("post_office_section");
        var homePickupSection = document.getElementById("home_pickup_section");
        if (event.target.value === "post_office") {
            postOfficeSection.style.display = "block";
            homePickupSection.style.display = "none";
            document.getElementById('pickup_date').removeAttribute('required');
            document.getElementById('pickup_time').removeAttribute('required');
            document.getElementById('pickup_location_id').setAttribute('required', 'required');
        } else {
            postOfficeSection.style.display = "none";
            homePickupSection.style.display = "block";
            document.getElementById('pickup_date').setAttribute('required', 'required');
            document.getElementById('pickup_time').setAttribute('required', 'required');
            document.getElementById('pickup_location_id').removeAttribute('required');
        }
        updateEstimatedDeliveryDate();
    });
});

function updateEstimatedDeliveryDate() {
    var pickupType = document.querySelector('input[name="pickup_type"]:checked').value;
    var pickupDateInput = document.getElementById('pickup_date');
    var estimatedDeliveryDateInput = document.getElementById('delivery_date');
    var senderAddressInput = document.getElementById('sender_address');
    var receiverAddressInput = document.getElementById('receiver_address');

    var pickupDate;
    if (pickupType === "post_office") {
        pickupDate = new Date(); // Nếu gửi tại bưu cục, lấy ngày hiện tại
    } else {
        pickupDate = new Date(pickupDateInput.value);
    }

    var isSameProvince = checkSameProvince(senderAddressInput.value, receiverAddressInput.value);

    var deliveryDate = new Date(pickupDate);
    
    if (isSameProvince) {
        var deliveryDays = Math.floor(Math.random() * 2) + 1;
        deliveryDate.setDate(deliveryDate.getDate() + deliveryDays);
    } else {
        deliveryDate.setDate(deliveryDate.getDate() + 4);
    }
    
    while (deliveryDate.getDay() === 0 || deliveryDate.getDay() === 6) {
        deliveryDate.setDate(deliveryDate.getDate() + 1);
    }
    
    estimatedDeliveryDateInput.value = deliveryDate.toISOString().split('T')[0];
}

function checkSameProvince(address1, address2) {
    const provinces = [
        'An Giang', 'Bà Rịa - Vũng Tàu', 'Bắc Giang', 'Bắc Kạn', 'Bạc Liêu',
        'Bắc Ninh', 'Bến Tre', 'Bình Định', 'Bình Dương', 'Bình Phước',
        'Bình Thuận', 'Cà Mau', 'Cần Thơ', 'Cao Bằng', 'Đà Nẵng',
        'Đắk Lắk', 'Đắk Nông', 'Điện Biên', 'Đồng Nai', 'Đồng Tháp',
        'Gia Lai', 'Hà Giang', 'Hà Nam', 'Hà Nội', 'Hà Tĩnh',
        'Hải Dương', 'Hải Phòng', 'Hậu Giang', 'Hòa Bình', 'Hưng Yên',
        'Khánh Hòa', 'Kiên Giang', 'Kon Tum', 'Lai Châu', 'Lâm Đồng',
        'Lạng Sơn', 'Lào Cai', 'Long An', 'Nam Định', 'Nghệ An',
        'Ninh Bình', 'Ninh Thuận', 'Phú Thọ', 'Phú Yên', 'Quảng Bình',
        'Quảng Nam', 'Quảng Ngãi', 'Quảng Ninh', 'Quảng Trị', 'Sóc Trăng',
        'Sơn La', 'Tây Ninh', 'Thái Bình', 'Thái Nguyên', 'Thanh Hóa',
        'Thừa Thiên Huế', 'Tiền Giang', 'TP Hồ Chí Minh', 'Trà Vinh', 'Tuyên Quang',
        'Vĩnh Long', 'Vĩnh Phúc', 'Yên Bái'
    ];

    const getProvince = (address) => {
        const parts = address.split(',').map(part => part.trim());
        for (let i = parts.length - 1; i >= 0; i--) {
            const part = parts[i];
            const matchedProvince = provinces.find(province => 
                part.toLowerCase().includes(province.toLowerCase())
            );
            if (matchedProvince) {
                return matchedProvince;
            }
        }
        return null;
    };

    const province1 = getProvince(address1);
    const province2 = getProvince(address2);

    return province1 && province2 && province1 === province2;
}

// Form submit
document.getElementById('orderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!document.getElementById('sender_coordinates').value || !document.getElementById('receiver_coordinates').value) {
        alert('Vui lòng chọn vị trí người gửi và người nhận trên bản đồ');
        return;
    }

    var formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('trackingNumber').textContent = data.tracking_number;
            document.getElementById('qrCodeContainer').innerHTML = data.qr_code;
            document.getElementById('orderCreatedInfo').style.display = 'block';
            document.getElementById('orderForm').reset();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Đã xảy ra lỗi khi tạo đơn hàng.');
    });
});

// Đảm bảo rằng hàm được gọi khi có thay đổi trong các trường liên quan
document.getElementById('pickup_date').addEventListener('change', updateEstimatedDeliveryDate);
document.getElementById('sender_address').addEventListener('input', updateEstimatedDeliveryDate);
document.getElementById('receiver_address').addEventListener('input', updateEstimatedDeliveryDate);
document.querySelectorAll('input[name="pickup_type"]').forEach(function(elem) {
    elem.addEventListener('change', updateEstimatedDeliveryDate);
});

// Gọi hàm lần đầu để cập nhật ngày giao hàng dự kiến
document.addEventListener('DOMContentLoaded', function() {
    var pickupDateInput = document.getElementById('pickup_date');
    
    var tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    pickupDateInput.min = tomorrow.toISOString().split('T')[0];
    pickupDateInput.value = tomorrow.toISOString().split('T')[0];

    updateEstimatedDeliveryDate();
});
</script>
@endpush