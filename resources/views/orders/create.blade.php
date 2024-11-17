@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Tạo Đơn Hàng Mới</h1>
    <div id="errorMessages" class="alert alert-danger" style="display: none;"></div>
    <form action="{{ route('orders.store') }}" method="POST" id="orderForm">
        @csrf
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Phương thức lấy hàng:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" id="pickup_type_post_office" name="is_pickup_at_post_office" value="1">
                        <label class="form-check-label" for="pickup_type_post_office">
                            Gửi tại bưu cục
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" id="pickup_type_home" name="is_pickup_at_post_office" value="0" checked>
                        <label class="form-check-label" for="pickup_type_home">
                            Lấy hàng tận nơi
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="category_id">Danh mục sản phẩm:</label>
                    <select class="form-control" id="category_id" name="category_id" required>
                        <option value="">-- Chọn danh mục --</option>
                        @foreach($productCategories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="warranty_package_id">Gói bảo hành:</label>
                    <select class="form-control" id="warranty_package_id" name="warranty_package_id" required>
                        <option value="">-- Chọn gói bảo hành --</option>
                    </select>
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
                    <label for="sender_address_select">Chọn địa chỉ đã lưu:</label>
                    <select class="form-control" id="sender_address_select" name="sender_address_select">
                        <option value="">-- Chọn địa chỉ --</option>
                        @foreach($userAddresses as $address)
                        <option value="{{ $address->id }}"
                            data-name="{{ $address->name }}"
                            data-phone="{{ $address->phone }}"
                            data-address="{{ $address->address }}"
                            data-coordinates="{{ json_encode($address->coordinates) }}">
                            {{ $address->name }} - {{ $address->address }}
                        </option>
                        @endforeach
                        <option value="new">Thêm địa chỉ mới</option>
                    </select>
                </div>
                <div id="new_sender_address_fields" style="display: none;">
                    <div class="form-group">
                        <label for="sender_name">Tên người gửi:</label>
                        <input type="text" class="form-control" id="sender_name" name="sender_name">
                    </div>
                    <div class="form-group">
                        <label for="sender_phone">Số điện thoại người gửi:</label>
                        <input type="text" class="form-control" id="sender_phone" name="sender_phone">
                    </div>
                    <div class="form-group">
                        <label for="sender_address">Địa chỉ người gửi:</label>
                        <input type="text" class="form-control" id="sender_address" name="sender_address">
                    </div>
                    <div class="form-group">
                        <label for="sender_district">Quận/Huyện người gửi:</label>
                        <input type="text" class="form-control" id="sender_district" name="sender_district" required>
                    </div>
                    <div class="form-group">
                        <label for="sender_province">Tỉnh/Thành phố người gửi:</label>
                        <input type="text" class="form-control" id="sender_province" name="sender_province" required>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="save_sender_address" name="save_sender_address">
                        <label class="form-check-label" for="save_sender_address">Lưu địa chỉ này</label>
                    </div>
                </div>
                <input type="hidden" id="sender_coordinates" name="sender_coordinates">
                <div id="shipping-info" class="mt-3" style="display: none;">
                    <h3>Thông tin vận chuyển</h3>
                    <p>Khoảng cách: <span id="distance"></span> km</p>
                    <p>Phí vận chuyển dự kiến: <span id="shipping-fee"></span> VND</p>
                </div>
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
                <div class="form-group">
                    <label for="receiver_district">Quận/Huyện người nhận:</label>
                    <input type="text" class="form-control" id="receiver_district" name="receiver_district" required>
                </div>
                <div class="form-group">
                    <label for="receiver_province">Tỉnh/Thành phố người nhận:</label>
                    <input type="text" class="form-control" id="receiver_province" name="receiver_province" required>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.js'></script>
<script src='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.0/mapbox-gl-geocoder.min.js'></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        mapboxgl.accessToken = '{{ env('MAPBOX_ACCESS_TOKEN') }}';
            var map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v11',
            center: [105.85, 21.02],
            zoom: 12
        });

        var senderMarker = new mapboxgl.Marker({
                color: "#3887be",
                draggable: true
            })
            .setLngLat([105.85, 21.02])
            .addTo(map);

        var receiverMarker = new mapboxgl.Marker({
                color: "#f30",
                draggable: true
            })
            .setLngLat([105.86, 21.03])
            .addTo(map);

        function updateCoordinates(type, lngLat) {
            var coordinatesElement = document.getElementById(type + '_coordinates');
            if (coordinatesElement) {
                coordinatesElement.value = JSON.stringify([lngLat.lng, lngLat.lat]);
                updateShippingInfo();
            }
        }

        function updateAddressFromMarker(type, lngLat) {
            var geocoder = type === 'sender' ? senderGeocoder : receiverGeocoder;
            if (geocoder) {
                geocoder.query(lngLat.lng + ',' + lngLat.lat);
            }
        }

        senderMarker.on('dragend', function() {
            var lngLat = senderMarker.getLngLat();
            updateCoordinates('sender', lngLat);
            updateAddressFromMarker('sender', lngLat);
            updateEstimatedDeliveryDate();
            map.flyTo({
                center: lngLat
            });
        });

        receiverMarker.on('dragend', function() {
            var lngLat = receiverMarker.getLngLat();
            updateCoordinates('receiver', lngLat);
            updateAddressFromMarker('receiver', lngLat);
            updateEstimatedDeliveryDate();
        });

        function updateMapFromAddress(type) {
            var addressElement = document.getElementById(type + '_address');
            var districtElement = document.getElementById(type + '_district');
            var provinceElement = document.getElementById(type + '_province');

            if (addressElement && districtElement && provinceElement &&
                addressElement.value && districtElement.value && provinceElement.value) {
                var fullAddress = addressElement.value + ', ' + districtElement.value + ', ' + provinceElement.value;
                var geocoder = type === 'sender' ? senderGeocoder : receiverGeocoder;
                if (geocoder) {
                    geocoder.query(fullAddress);
                }
            }
        }

        function checkAndUpdateMap(type) {
            var addressElement = document.getElementById(type + '_address');
            var districtElement = document.getElementById(type + '_district');
            var provinceElement = document.getElementById(type + '_province');

            if (addressElement && districtElement && provinceElement &&
                addressElement.value && districtElement.value && provinceElement.value) {
                updateMapFromAddress(type);
            }
        }

        ['sender', 'receiver'].forEach(function(type) {
            var addressElement = document.getElementById(type + '_address');
            var districtElement = document.getElementById(type + '_district');
            var provinceElement = document.getElementById(type + '_province');

            if (addressElement) {
                addressElement.addEventListener('change', function() {
                    checkAndUpdateMap(type);
                });
            }
            if (districtElement) {
                districtElement.addEventListener('change', function() {
                    checkAndUpdateMap(type);
                });
            }
            if (provinceElement) {
                provinceElement.addEventListener('change', function() {
                    checkAndUpdateMap(type);
                });
            }
        });

        function handleGeocoderResult(type, e) {
            var result = e.result;
            var lngLat = result.center;
            var marker = type === 'sender' ? senderMarker : receiverMarker;

            marker.setLngLat(lngLat);
            updateCoordinates(type, {
                lng: lngLat[0],
                lat: lngLat[1]
            });

            var addressElement = document.getElementById(type + '_address');
            var districtElement = document.getElementById(type + '_district');
            var provinceElement = document.getElementById(type + '_province');

            if (addressElement) {
                addressElement.value = result.place_name;
            }

            var context = result.context || [];
            var district = context.find(c => c.id.startsWith('district'));
            var province = context.find(c => c.id.startsWith('region'));

            if (district && districtElement) {
                districtElement.value = district.text;
            }
            if (province && provinceElement) {
                provinceElement.value = province.text;
            }

            map.flyTo({
                center: lngLat,
                zoom: 14
            });
        }

        var senderGeocoder = new MapboxGeocoder({
            accessToken: mapboxgl.accessToken,
            mapboxgl: mapboxgl,
            marker: false,
            placeholder: 'Nhập địa chỉ người gửi'
        });

        senderGeocoder.on('result', function(e) {
            handleGeocoderResult('sender', e);
            updateEstimatedDeliveryDate();
        });

        var senderAddressElement = document.getElementById('sender_address');
        if (senderAddressElement && senderAddressElement.parentNode) {
            senderAddressElement.parentNode.insertBefore(senderGeocoder.onAdd(map), senderAddressElement);
        }

        var receiverGeocoder = new MapboxGeocoder({
            accessToken: mapboxgl.accessToken,
            mapboxgl: mapboxgl,
            marker: false,
            placeholder: 'Nhập địa chỉ người nhận'
        });

        receiverGeocoder.on('result', function(e) {
            handleGeocoderResult('receiver', e);
            updateEstimatedDeliveryDate();
        });

        var receiverAddressElement = document.getElementById('receiver_address');
        if (receiverAddressElement && receiverAddressElement.parentNode) {
            receiverAddressElement.parentNode.insertBefore(receiverGeocoder.onAdd(map), receiverAddressElement);
        }

        var addProductButton = document.getElementById('add_product');
        if (addProductButton) {
            addProductButton.addEventListener('click', function() {
                var productDiv = document.createElement('div');
                productDiv.className = 'product';
                var productTemplate = document.querySelector('.product');
                if (productTemplate) {
                    var productCount = document.querySelectorAll('.product').length;
                    var clonedTemplate = productTemplate.cloneNode(true);
                    clonedTemplate.querySelectorAll('select, input').forEach(function(element) {
                        element.name = element.name.replace('[0]', '[' + productCount + ']');
                        element.value = '';
                    });
                    productDiv.innerHTML = clonedTemplate.innerHTML;
                    var productsContainer = document.getElementById('products');
                    if (productsContainer) {
                        productsContainer.appendChild(productDiv);
                    }
                }
            });
        }

        var senderAddressSelect = document.getElementById('sender_address_select');
        if (senderAddressSelect) {
            senderAddressSelect.addEventListener('change', function() {
                var newAddressFields = document.getElementById('new_sender_address_fields');
                var senderNameElement = document.getElementById('sender_name');
                var senderPhoneElement = document.getElementById('sender_phone');
                var senderAddressElement = document.getElementById('sender_address');
                var senderDistrictElement = document.getElementById('sender_district');
                var senderProvinceElement = document.getElementById('sender_province');
                var senderCoordinatesElement = document.getElementById('sender_coordinates');

                if (this.value === 'new') {
                    if (newAddressFields) newAddressFields.style.display = 'block';
                    if (senderNameElement) senderNameElement.required = true;
                    if (senderPhoneElement) senderPhoneElement.required = true;
                    if (senderAddressElement) senderAddressElement.required = true;
                    if (senderDistrictElement) senderDistrictElement.required = true;
                    if (senderProvinceElement) senderProvinceElement.required = true;
                    if (senderCoordinatesElement) senderCoordinatesElement.value = '';
                    senderMarker.setLngLat([105.85, 21.02]);
                    map.flyTo({
                        center: [105.85, 21.02],
                        zoom: 12
                    });
                } else if (this.value !== '') {
                    if (newAddressFields) newAddressFields.style.display = 'none';
                    if (senderNameElement) senderNameElement.required = false;
                    if (senderPhoneElement) senderPhoneElement.required = false;
                    if (senderAddressElement) senderAddressElement.required = false;
                    if (senderDistrictElement) senderDistrictElement.required = false;
                    if (senderProvinceElement) senderProvinceElement.required = false;
                    var selectedOption = this.options[this.selectedIndex];
                    if (senderNameElement) senderNameElement.value = selectedOption.dataset.name;
                    if (senderPhoneElement) senderPhoneElement.value = selectedOption.dataset.phone;
                    if (senderAddressElement) senderAddressElement.value = selectedOption.dataset.address;
                    if (senderDistrictElement) senderDistrictElement.value = selectedOption.dataset.district;
                    if (senderProvinceElement) senderProvinceElement.value = selectedOption.dataset.province;
                    var coordinates = JSON.parse(selectedOption.dataset.coordinates);
                    if (senderCoordinatesElement) senderCoordinatesElement.value = JSON.stringify(coordinates);
                    senderMarker.setLngLat(coordinates);
                    map.flyTo({
                        center: coordinates,
                        zoom: 14
                    });
                    updateEstimatedDeliveryDate();
                } else {
                    if (newAddressFields) newAddressFields.style.display = 'none';
                    if (senderCoordinatesElement) senderCoordinatesElement.value = '';
                    senderMarker.setLngLat([105.85, 21.02]);
                    map.flyTo({
                        center: [105.85, 21.02],
                        zoom: 12
                    });
                }
            });
        }

        function updatePickupFields() {
            var isPickupAtPostOffice = document.querySelector('input[name="is_pickup_at_post_office"]:checked');
            if (!isPickupAtPostOffice) return;
            isPickupAtPostOffice = isPickupAtPostOffice.value === "1";

            var postOfficeSection = document.getElementById("post_office_section");
            var homePickupSection = document.getElementById("home_pickup_section");
            var pickupDateInput = document.getElementById('pickup_date');
            var pickupTimeInput = document.getElementById('pickup_time');
            var pickupLocationInput = document.getElementById('pickup_location_id');

            if (!postOfficeSection || !homePickupSection || !pickupDateInput || !pickupTimeInput || !pickupLocationInput) return;

            var tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            var tomorrowString = tomorrow.toISOString().split('T')[0];

            if (isPickupAtPostOffice) {
                postOfficeSection.style.display = "block";
                homePickupSection.style.display = "none";
                pickupTimeInput.removeAttribute('required');
                pickupLocationInput.setAttribute('required', 'required');

                pickupDateInput.value = tomorrowString;
                pickupDateInput.setAttribute('readonly', 'readonly');
            } else {
                postOfficeSection.style.display = "none";
                homePickupSection.style.display = "block";
                pickupDateInput.setAttribute('required', 'required');
                pickupTimeInput.setAttribute('required', 'required');
                pickupLocationInput.removeAttribute('required');
                pickupDateInput.removeAttribute('readonly');
            }

            pickupDateInput.min = tomorrowString;
            if (new Date(pickupDateInput.value) < tomorrow) {
                pickupDateInput.value = tomorrowString;
            }

            updateEstimatedDeliveryDate();
        }

        document.querySelectorAll('input[name="is_pickup_at_post_office"]').forEach(function(elem) {
            elem.addEventListener("change", updatePickupFields);
        });

        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = deg2rad(lat2 - lat1);
            const dLon = deg2rad(lon2 - lon1);
            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            const distance = R * c;
            return distance;
        }

        function deg2rad(deg) {
            return deg * (Math.PI / 180);
        }

        function calculateShippingFee(distance) {
            if (distance <= 5) return 10000;
            if (distance <= 10) return 15000;
            if (distance <= 30) return Math.ceil(distance) * 1500;
            if (distance <= 60) return Math.ceil(distance) * 1300;
            if (distance <= 100) return Math.ceil(distance) * 1000;
            if (distance <= 150) return Math.ceil(distance) * 700;
            if (distance <= 300) return Math.ceil(distance) * 600;
            return Math.ceil(distance) * 450;
        }

        function updateEstimatedDeliveryDate() {
            var isPickupAtPostOffice = document.querySelector('input[name="is_pickup_at_post_office"]:checked');
            if (!isPickupAtPostOffice) return;
            isPickupAtPostOffice = isPickupAtPostOffice.value === "1";

            var pickupDateInput = document.getElementById('pickup_date');
            var estimatedDeliveryDateInput = document.getElementById('delivery_date');
            var senderCoordinatesInput = document.getElementById('sender_coordinates');
            var receiverCoordinatesInput = document.getElementById('receiver_coordinates');

            if (!pickupDateInput || !estimatedDeliveryDateInput || !senderCoordinatesInput || !receiverCoordinatesInput) return;

            var senderCoordinates = JSON.parse(senderCoordinatesInput.value || '[0,0]');
            var receiverCoordinates = JSON.parse(receiverCoordinatesInput.value || '[0,0]');

            if (senderCoordinates[0] === 0 && senderCoordinates[1] === 0 || 
                receiverCoordinates[0] === 0 && receiverCoordinates[1] === 0) {
                hideShippingInfo();
                return;
            }

            var distance = calculateDistance(senderCoordinates[1], senderCoordinates[0], receiverCoordinates[1], receiverCoordinates[0]);
            var shippingFee = calculateShippingFee(distance);

            var pickupDate = new Date(pickupDateInput.value);
            var deliveryDate = new Date(pickupDate);

            // Logic mới để tính ngày giao hàng
            if (distance <= 50) {
                deliveryDate.setDate(deliveryDate.getDate() + 1); // Giao hàng trong vòng 1 ngày
            } else if (distance <= 300) {
                deliveryDate.setDate(deliveryDate.getDate() + 2); // Giao hàng trong vòng 2 ngày
            } else {
                deliveryDate.setDate(deliveryDate.getDate() + 3); // Giao hàng trong vòng 3 ngày
            }

            // Điều chỉnh ngày giao hàng nếu rơi vào cuối tuần
            while (deliveryDate.getDay() === 0 || deliveryDate.getDay() === 6) {
                deliveryDate.setDate(deliveryDate.getDate() + 1);
            }

            estimatedDeliveryDateInput.value = deliveryDate.toISOString().split('T')[0];

            updateShippingInfoDisplay(distance, shippingFee);
        }

        function hideShippingInfo() {
            var shippingInfoElement = document.getElementById('shipping-info');
            if (shippingInfoElement) {
                shippingInfoElement.style.display = 'none';
            }
        }

        function updateShippingInfoDisplay(distance, shippingFee) {
            var distanceElement = document.getElementById('distance');
            var shippingFeeElement = document.getElementById('shipping-fee');
            var warrantyFeeElement = document.getElementById('warranty-fee');
            var totalFeeElement = document.getElementById('total-fee');
            var shippingInfoElement = document.getElementById('shipping-info');

            if (!distanceElement || !shippingFeeElement || !warrantyFeeElement || !totalFeeElement || !shippingInfoElement) {
                console.error('One or more shipping info elements not found');
                return;
            }

            var warrantyPackage = document.getElementById('warranty_package_id');
            var warrantyFee = warrantyPackage && warrantyPackage.options[warrantyPackage.selectedIndex] 
                ? Number(warrantyPackage.options[warrantyPackage.selectedIndex].dataset.price) || 0 
                : 0;
            var totalFee = shippingFee + warrantyFee;

            distanceElement.textContent = distance.toFixed(2);
            shippingFeeElement.textContent = shippingFee.toLocaleString();
            warrantyFeeElement.textContent = warrantyFee.toLocaleString();
            totalFeeElement.textContent = totalFee.toLocaleString();
            shippingInfoElement.style.display = 'block';
        }

        var orderForm = document.getElementById('orderForm');
        if (orderForm) {
            orderForm.addEventListener('submit', function(e) {
                e.preventDefault();
                hideError();

                var senderCoordinatesInput = document.getElementById('sender_coordinates');
                var receiverCoordinatesInput = document.getElementById('receiver_coordinates');

                if (!senderCoordinatesInput || !receiverCoordinatesInput ||
                    !senderCoordinatesInput.value || !receiverCoordinatesInput.value) {
                    showError('Vui lòng chọn vị trí người gửi và người nhận trên bản đồ');
                    return;
                }

                var formData = new FormData(this);

                fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            return response.json();
                        } else {
                            throw new Error('Received non-JSON response from server');
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                var trackingNumberElement = document.getElementById('trackingNumber');
                                var orderCreatedInfoElement = document.getElementById('orderCreatedInfo');
                                if (trackingNumberElement && orderCreatedInfoElement) {
                                    trackingNumberElement.textContent = data.order.tracking_number;
                                    orderCreatedInfoElement.style.display = 'block';
                                }
                                this.reset();
                                senderMarker.setLngLat([105.85, 21.02]);
                                receiverMarker.setLngLat([105.86, 21.03]);
                                map.flyTo({
                                    center: [105.85, 21.02],
                                    zoom: 12
                                });
                            }
                        } else {
                            showError(data.message || 'Đã xảy ra lỗi khi tạo đơn hàng.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Đã xảy ra lỗi khi xử lý yêu cầu. Vui lòng thử lại sau.');
                    });
            });
        }

        function showError(message) {
            var errorElement = document.getElementById('errorMessages');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            } else {
                console.error('Error element not found. Error message:', message);
                alert(message);
            }
        }

        function hideError() {
            var errorElement = document.getElementById('errorMessages');
            if (errorElement) {
                errorElement.style.display = 'none';
            }
        }

        var productCategories = @json($productCategories);
        var categorySelect = document.getElementById('category_id');
        var warrantySelect = document.getElementById('warranty_package_id');

        function updateWarrantyOptions(categoryId) {
            if (!warrantySelect) return;

            warrantySelect.innerHTML = '<option value="">-- Chọn gói bảo hành --</option>';

            if (categoryId) {
                var category = productCategories.find(c => c.id == categoryId);
                if (category && category.warranty_packages) {
                    category.warranty_packages.forEach(function(package) {
                        var option = document.createElement('option');
                        option.value = package.id;
                        option.textContent = `${package.name} (+${package.price.toLocaleString()} VND)`;
                        option.dataset.price = package.price;
                        warrantySelect.appendChild(option);
                    });
                }
            }
        }

        function updateShippingInfo() {
            var senderCoordinatesInput = document.getElementById('sender_coordinates');
            var receiverCoordinatesInput = document.getElementById('receiver_coordinates');

            if (!senderCoordinatesInput || !receiverCoordinatesInput) return;

            var senderCoords = JSON.parse(senderCoordinatesInput.value || '[0,0]');
            var receiverCoords = JSON.parse(receiverCoordinatesInput.value || '[0,0]');

            if (senderCoords[0] === 0 && senderCoords[1] === 0 ||
                receiverCoords[0] === 0 && receiverCoords[1] === 0) {
                return;
            }

            var distance = calculateDistance(senderCoords[1], senderCoords[0], receiverCoords[1], receiverCoords[0]);
            var shippingFee = calculateShippingFee(distance);

            updateShippingInfoDisplay(distance, shippingFee);
        }

        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                var categoryId = this.value;
                updateWarrantyOptions(categoryId);
                updateEstimatedDeliveryDate();
            });
        }

        if (warrantySelect) {
            warrantySelect.addEventListener('change', updateEstimatedDeliveryDate);
        }

        var initialCategoryId = categorySelect ? categorySelect.value : null;
        if (initialCategoryId) {
            updateWarrantyOptions(initialCategoryId);
        }

        updatePickupFields();
        updateEstimatedDeliveryDate();
    });
</script>
@endpush