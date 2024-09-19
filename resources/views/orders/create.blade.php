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
   document.addEventListener('DOMContentLoaded', function() {
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

    function updateAddressFromMarker(type, lngLat) {
        var geocoder = type === 'sender' ? senderGeocoder : receiverGeocoder;
        geocoder.query(lngLat.lng + ',' + lngLat.lat);
    }

    senderMarker.on('dragend', function() {
        var lngLat = senderMarker.getLngLat();
        updateCoordinates('sender', lngLat);
        updateAddressFromMarker('sender', lngLat);
        updateEstimatedDeliveryDate();
        map.flyTo({center: lngLat});
    });

    receiverMarker.on('dragend', function() {
        var lngLat = receiverMarker.getLngLat();
        updateCoordinates('receiver', lngLat);
        updateAddressFromMarker('receiver', lngLat);
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
        var lngLat = e.result.center;
        senderMarker.setLngLat(lngLat);
        updateCoordinates('sender', { lng: lngLat[0], lat: lngLat[1] });
        updateEstimatedDeliveryDate();
        map.flyTo({center: lngLat});
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
        var lngLat = e.result.center;
        receiverMarker.setLngLat(lngLat);
        updateCoordinates('receiver', { lng: lngLat[0], lat: lngLat[1] });
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

    // Xử lý địa chỉ người gửi
    document.getElementById('sender_address_select').addEventListener('change', function() {
        var newAddressFields = document.getElementById('new_sender_address_fields');
        if (this.value === 'new') {
            newAddressFields.style.display = 'block';
            document.getElementById('sender_name').required = true;
            document.getElementById('sender_phone').required = true;
            document.getElementById('sender_address').required = true;
            document.getElementById('sender_coordinates').value = '';
            senderMarker.setLngLat([105.85, 21.02]); // Reset to default position
            map.flyTo({center: [105.85, 21.02], zoom: 12});
        } else if (this.value !== '') {
            newAddressFields.style.display = 'none';
            document.getElementById('sender_name').required = false;
            document.getElementById('sender_phone').required = false;
            document.getElementById('sender_address').required = false;
            var selectedOption = this.options[this.selectedIndex];
            document.getElementById('sender_name').value = selectedOption.dataset.name;
            document.getElementById('sender_phone').value = selectedOption.dataset.phone;
            document.getElementById('sender_address').value = selectedOption.dataset.address;
            var coordinates = JSON.parse(selectedOption.dataset.coordinates);
            document.getElementById('sender_coordinates').value = JSON.stringify(coordinates);
            senderMarker.setLngLat(coordinates);
            map.flyTo({center: coordinates, zoom: 14});
            updateEstimatedDeliveryDate();
        } else {
            newAddressFields.style.display = 'none';
            document.getElementById('sender_coordinates').value = '';
            senderMarker.setLngLat([105.85, 21.02]); // Reset to default position
            map.flyTo({center: [105.85, 21.02], zoom: 12});
        }
    });

    function updatePickupFields() {
        var isPickupAtPostOffice = document.querySelector('input[name="is_pickup_at_post_office"]:checked').value === "1";
        var postOfficeSection = document.getElementById("post_office_section");
        var homePickupSection = document.getElementById("home_pickup_section");
        var pickupDateInput = document.getElementById('pickup_date');
        var pickupTimeInput = document.getElementById('pickup_time');
        var pickupLocationInput = document.getElementById('pickup_location_id');

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

    // Toggle pickup type
    document.querySelectorAll('input[name="is_pickup_at_post_office"]').forEach(function(elem) {
        elem.addEventListener("change", updatePickupFields);
    });

    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Radius of the earth in km
        const dLat = deg2rad(lat2 - lat1);
        const dLon = deg2rad(lon2 - lon1);
        const a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
            Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        const distance = R * c; // Distance in km
        return distance;
    }

    function deg2rad(deg) {
        return deg * (Math.PI/180);
    }

    function calculateShippingFee(distance) {
        if (distance <= 5) {
            return 10000; // 10k VND cho dưới 5km
        } else if (distance <= 10) {
            return 15000; // 15k VND cho 5-10km
        } else if (distance <= 30) {
            return Math.ceil(distance) * 1500; // 1500 VND * số km cho 10-30km
        } else if (distance <= 60) {
            return Math.ceil(distance) * 1300; // 1300 VND * số km cho 31-60km
        } else if (distance <= 100) {
            return Math.ceil(distance) * 1000; // 1000 VND * số km cho 61-100km
        } else if (distance <= 150) {
            return Math.ceil(distance) * 700; // 700 VND * số km cho 100-150km
        } else if (distance <= 300) {
            return Math.ceil(distance) * 600; // 600 VND * số km cho 150-300km
        } else {
            return Math.ceil(distance) * 450; // 450 VND * số km cho trên 300km
        }
    }

    function updateEstimatedDeliveryDate() {
        var isPickupAtPostOffice = document.querySelector('input[name="is_pickup_at_post_office"]:checked').value === "1";
        var pickupDateInput = document.getElementById('pickup_date');
        var estimatedDeliveryDateInput = document.getElementById('delivery_date');
        var senderCoordinates = JSON.parse(document.getElementById('sender_coordinates').value || '[0,0]');
        var receiverCoordinates = JSON.parse(document.getElementById('receiver_coordinates').value || '[0,0]');

        if (senderCoordinates[0] === 0 && senderCoordinates[1] === 0 || 
            receiverCoordinates[0] === 0 && receiverCoordinates[1] === 0) {
            document.getElementById('shipping-info').style.display = 'none';
            return;
        }

        var distance = calculateDistance(senderCoordinates[1], senderCoordinates[0], receiverCoordinates[1], receiverCoordinates[0]);
        var shippingFee = calculateShippingFee(distance);

        var pickupDate = new Date(pickupDateInput.value);
        var deliveryDate = new Date(pickupDate);
        deliveryDate.setDate(deliveryDate.getDate() + 1); // Đảm bảo ngày giao hàng ít nhất là ngày sau ngày lấy hàng
        
        if (distance <= 50) {
            deliveryDate.setDate(deliveryDate.getDate() + 1);
        } else if (distance <= 300) {
            deliveryDate.setDate(deliveryDate.getDate() + 2);
        } else {
            deliveryDate.setDate(deliveryDate.getDate() + 3);
        }
        
        while (deliveryDate.getDay() === 0 || deliveryDate.getDay() === 6) {
            deliveryDate.setDate(deliveryDate.getDate() + 1);
        }
        
        estimatedDeliveryDateInput.value = deliveryDate.toISOString().split('T')[0];

        const warrantyPackage = document.getElementById('warranty_package_id');
        const warrantyFee = warrantyPackage.options[warrantyPackage.selectedIndex]?.dataset.price || 0;
        const totalFee = shippingFee + Number(warrantyFee);

        document.getElementById('distance').textContent = distance.toFixed(2);
        document.getElementById('shipping-fee').textContent = shippingFee.toLocaleString();
        document.getElementById('warranty-fee').textContent = Number(warrantyFee).toLocaleString();
        document.getElementById('total-fee').textContent = totalFee.toLocaleString();
        document.getElementById('shipping-info').style.display = 'block';
        const distanceElement = document.getElementById('distance');
        
        const shippingFeeElement = document.getElementById('shipping-fee');
        const warrantyFeeElement = document.getElementById('warranty-fee');
        const totalFeeElement = document.getElementById('total-fee');
        const shippingInfoElement = document.getElementById('shipping-info');

        if (distanceElement && shippingFeeElement && warrantyFeeElement && totalFeeElement && shippingInfoElement) {
            distanceElement.textContent = distance.toFixed(2);
            shippingFeeElement.textContent = shippingFee.toLocaleString();
            warrantyFeeElement.textContent = Number(warrantyFee).toLocaleString();
            totalFeeElement.textContent = totalFee.toLocaleString();
            shippingInfoElement.style.display = 'block';
        } else {
            console.error('One or more elements not found in the DOM');
    }
    }

    // Form submit
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!document.getElementById('sender_coordinates').value || !document.getElementById('receiver_coordinates').value) {
            showError('Vui lòng chọn vị trí người gửi và người nhận trên bản đồ');
            return;
        }

        var formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                document.getElementById('trackingNumber').textContent = data.tracking_number;
                document.getElementById('qrCodeContainer').innerHTML = data.qr_code;
                document.getElementById('orderCreatedInfo').style.display = 'block';
                document.getElementById('orderForm').reset();
                senderMarker.setLngLat([105.85, 21.02]);
                receiverMarker.setLngLat([105.86, 21.03]);
                map.flyTo({center: [105.85, 21.02], zoom: 12});
                hideError();
                window.location.href = '/orders/';
            } else {
                showError(data.message || 'Đã xảy ra lỗi khi tạo đơn hàng.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError(error.message || 'Đã xảy ra lỗi khi tạo đơn hàng.');
        });
    });

    function showError(message) {
        const errorElement = document.getElementById('errorMessages');
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }

    function hideError() {
        const errorElement = document.getElementById('errorMessages');
        errorElement.style.display = 'none';
    }

    // Xử lý danh mục sản phẩm và gói bảo hành
    const productCategories = @json($productCategories);
    const categorySelect = document.getElementById('category_id');
    const warrantySelect = document.getElementById('warranty_package_id');

    function updateWarrantyOptions(categoryId) {
        warrantySelect.innerHTML = '<option value="">-- Chọn gói bảo hành --</option>';

        if (categoryId) {
            const category = productCategories.find(c => c.id == categoryId);
            if (category && category.warranty_packages) {
                category.warranty_packages.forEach(function(package) {
                    const option = document.createElement('option');
                    option.value = package.id;
                    option.textContent = `${package.name} (+${package.price.toLocaleString()} VND)`;
                    option.dataset.price = package.price;
                    warrantySelect.appendChild(option);
                });
            }
        }
    }

    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        updateWarrantyOptions(categoryId);
        updateEstimatedDeliveryDate();
    });

    warrantySelect.addEventListener('change', updateEstimatedDeliveryDate);

    // Khởi tạo các tùy chọn gói bảo hành khi trang được tải
    const initialCategoryId = categorySelect.value;
    if (initialCategoryId) {
        updateWarrantyOptions(initialCategoryId);
    }

    // Thêm event listener cho việc thay đổi trường địa chỉ
    document.getElementById('sender_address').addEventListener('change', function() {
        if (document.getElementById('sender_address_select').value === 'new') {
            senderGeocoder.query(this.value);
        }
    });

    document.getElementById('receiver_address').addEventListener('change', function() {
        receiverGeocoder.query(this.value);
    });

    // Đảm bảo rằng hàm được gọi khi có thay đổi trong các trường liên quan
    document.getElementById('pickup_date').addEventListener('change', updateEstimatedDeliveryDate);
    document.getElementById('sender_address').addEventListener('input', updateEstimatedDeliveryDate);
    document.getElementById('receiver_address').addEventListener('input', updateEstimatedDeliveryDate);
    document.querySelectorAll('input[name="is_pickup_at_post_office"]').forEach(function(elem) {
        elem.addEventListener('change', updateEstimatedDeliveryDate);
    });

    // Thêm event listener cho việc thay đổi bưu cục
    document.getElementById('pickup_location_id').addEventListener('change', updateEstimatedDeliveryDate);

    // Khởi tạo các trường khi trang được tải
    updatePickupFields();
    updateEstimatedDeliveryDate();
});
    </script>
       
    @endpush