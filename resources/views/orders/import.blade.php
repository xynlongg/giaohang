@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Nhập Đơn Hàng từ Excel</h1>
    @if (session('error'))
        <div class="alert alert-danger">
            {!! session('error') !!}
        </div>
    @endif
    <form action="{{ route('orders.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <h2>Thông tin chung</h2>
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

        <div id="post_office_section" style="display: none;">
            <div class="form-group">
                <label for="pickup_location_id">Chọn bưu cục gửi hàng:</label>
                <select class="form-control" id="pickup_location_id" name="pickup_location_id">
                    <option value="">-- Chọn bưu cục --</option>
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
        <input type="hidden" id="sender_coordinates_lng" name="sender_coordinates[]">
        <input type="hidden" id="sender_coordinates_lat" name="sender_coordinates[]">

        <h2>File Excel</h2>
        <div class="form-group">
            <label for="excel_file">Chọn file Excel:</label>
            <a href="{{ route('orders.download-template') }}" class="btn btn-secondary">Tải xuống mẫu Excel</a>
            <input type="file" class="form-control-file" id="excel_file" name="excel_file" required>
        </div>
        <button type="submit" class="btn btn-primary">Nhập Đơn Hàng</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
   document.addEventListener('DOMContentLoaded', function() {
    var tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    var tomorrowFormatted = tomorrow.toISOString().split('T')[0];
    document.getElementById('pickup_date').value = tomorrowFormatted;
    document.getElementById('pickup_date').min = tomorrowFormatted;

    function updatePickupFields() {
        var isPickupAtPostOffice = document.querySelector('input[name="is_pickup_at_post_office"]:checked').value === "1";
        var postOfficeSection = document.getElementById("post_office_section");
        var homePickupSection = document.getElementById("home_pickup_section");
        var pickupDateInput = document.getElementById('pickup_date');
        var pickupTimeInput = document.getElementById('pickup_time');
        var pickupLocationInput = document.getElementById('pickup_location_id');

        if (isPickupAtPostOffice) {
            postOfficeSection.style.display = "block";
            homePickupSection.style.display = "none";
            pickupTimeInput.removeAttribute('required');
            pickupLocationInput.setAttribute('required', 'required');
            
            pickupDateInput.value = tomorrowFormatted;
            pickupDateInput.setAttribute('readonly', 'readonly');
        } else {
            postOfficeSection.style.display = "none";
            homePickupSection.style.display = "block";
            pickupDateInput.setAttribute('required', 'required');
            pickupTimeInput.setAttribute('required', 'required');
            pickupLocationInput.removeAttribute('required');
            pickupDateInput.removeAttribute('readonly');
        }
    }

    document.querySelectorAll('input[name="is_pickup_at_post_office"]').forEach(function(elem) {
        elem.addEventListener("change", updatePickupFields);
    });

    updatePickupFields();
});
document.querySelectorAll('input[name="is_pickup_at_post_office"]').forEach(function(elem) {
    elem.addEventListener("change", function(event) {
        var postOfficeSection = document.getElementById("post_office_section");
        var homePickupSection = document.getElementById("home_pickup_section");
        if (event.target.value === "1") {
            postOfficeSection.style.display = "block";
            homePickupSection.style.display = "none";
            document.getElementById('pickup_time').removeAttribute('required');
            document.getElementById('pickup_location_id').setAttribute('required', 'required');
        } else {
            postOfficeSection.style.display = "none";
            homePickupSection.style.display = "block";
            document.getElementById('pickup_time').setAttribute('required', 'required');
            document.getElementById('pickup_location_id').removeAttribute('required');
        }
    });
});

document.getElementById('sender_address_select').addEventListener('change', function() {
    var newAddressFields = document.getElementById('new_sender_address_fields');
    if (this.value === 'new') {
        newAddressFields.style.display = 'block';
        document.getElementById('sender_name').required = true;
        document.getElementById('sender_phone').required = true;
        document.getElementById('sender_address').required = true;
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
        document.getElementById('sender_coordinates_lng').value = coordinates[0];
        document.getElementById('sender_coordinates_lat').value = coordinates[1];
    } else {
        newAddressFields.style.display = 'none';
        document.getElementById('sender_coordinates_lng').value = '';
        document.getElementById('sender_coordinates_lat').value = '';
    }
});
</script>
@endpush