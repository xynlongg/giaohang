@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Chỉnh sửa Đơn Hàng #{{ $order->tracking_number }}</h1>

    <form action="{{ route('orders.update', $order) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-6">
                <h2>Thông tin người gửi</h2>
                <div class="form-group">
                    <label for="sender_name">Tên người gửi</label>
                    <input type="text" class="form-control" id="sender_name" name="sender_name" value="{{ old('sender_name', $order->sender_name) }}" required>
                </div>
                <div class="form-group">
                    <label for="sender_phone">Số điện thoại người gửi</label>
                    <input type="text" class="form-control" id="sender_phone" name="sender_phone" value="{{ old('sender_phone', $order->sender_phone) }}" required>
                </div>
                <div class="form-group">
                    <label for="sender_address">Địa chỉ người gửi</label>
                    <input type="text" class="form-control" id="sender_address" name="sender_address" value="{{ old('sender_address', $order->sender_address) }}" required>
                </div>

                <h2>Thông tin người nhận</h2>
                <div class="form-group">
                    <label for="receiver_name">Tên người nhận</label>
                    <input type="text" class="form-control" id="receiver_name" name="receiver_name" value="{{ old('receiver_name', $order->receiver_name) }}" required>
                </div>
                <div class="form-group">
                    <label for="receiver_phone">Số điện thoại người nhận</label>
                    <input type="text" class="form-control" id="receiver_phone" name="receiver_phone" value="{{ old('receiver_phone', $order->receiver_phone) }}" required>
                </div>
                <div class="form-group">
                    <label for="receiver_address">Địa chỉ người nhận</label>
                    <input type="text" class="form-control" id="receiver_address" name="receiver_address" value="{{ old('receiver_address', $order->receiver_address) }}" required>
                </div>
            </div>

            <div class="col-md-6">
                <h2>Thông tin đơn hàng</h2>
                <div class="form-group">
                    <label for="is_pickup_at_post_office">Lấy hàng tại bưu cục</label>
                    <select class="form-control" id="is_pickup_at_post_office" name="is_pickup_at_post_office">
                        <option value="0" {{ old('is_pickup_at_post_office', $order->is_pickup_at_post_office) == 0 ? 'selected' : '' }}>Không</option>
                        <option value="1" {{ old('is_pickup_at_post_office', $order->is_pickup_at_post_office) == 1 ? 'selected' : '' }}>Có</option>
                    </select>
                </div>

                <div class="form-group" id="pickup_location_group" style="{{ old('is_pickup_at_post_office', $order->is_pickup_at_post_office) == 1 ? '' : 'display: none;' }}">
                    <label for="pickup_location_id">Bưu cục lấy hàng</label>
                    <select class="form-control" id="pickup_location_id" name="pickup_location_id">
                        @foreach($postOffices as $postOffice)
                            <option value="{{ $postOffice->id }}" {{ old('pickup_location_id', $order->pickup_location_id) == $postOffice->id ? 'selected' : '' }}>
                                {{ $postOffice->name }} - {{ $postOffice->address }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="pickup_date">Ngày lấy hàng</label>
                    <input type="date" class="form-control" id="pickup_date" name="pickup_date" value="{{ old('pickup_date', $order->pickup_date ? $order->pickup_date->format('Y-m-d') : '') }}" required>
                </div>

                <div class="form-group">
                    <label for="category_id">Danh mục sản phẩm</label>
                    <select class="form-control" id="category_id" name="category_id" required>
                        @foreach($productCategories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $order->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="warranty_package_id">Gói bảo hành</label>
                    <select class="form-control" id="warranty_package_id" name="warranty_package_id" required>
                        @foreach($warrantyPackages as $package)
                            <option value="{{ $package->id }}" {{ old('warranty_package_id', $order->warranty_package_id) == $package->id ? 'selected' : '' }}>
                                {{ $package->name }} - {{ number_format($package->price) }} VND
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="total_weight">Tổng khối lượng (kg)</label>
                    <input type="number" step="0.01" class="form-control" id="total_weight" name="total_weight" value="{{ old('total_weight', $order->total_weight) }}" required>
                </div>

                <div class="form-group">
                    <label for="total_value">Tổng giá trị hàng hóa (VND)</label>
                    <input type="number" class="form-control" id="total_value" name="total_value" value="{{ old('total_value', $order->total_value) }}" required>
                </div>

                <div class="form-group">
                    <label for="total_cod">Tổng tiền thu hộ (VND)</label>
                    <input type="number" class="form-control" id="total_cod" name="total_cod" value="{{ old('total_cod', $order->total_cod) }}" required>
                </div>
            </div>
        </div>
         <div class="row mt-4">
            <div class="col-md-12">
                <h2>Sản phẩm</h2>
                <table class="table" id="products-table">
                    <thead>
                        <tr>
                            <th>Tên sản phẩm</th>
                            <th>Số lượng</th>
                            <th>Giá (VND)</th>
                            <th>Khối lượng (kg)</th>
                            <th>Tiền thu hộ (COD)</th>

                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->products as $index => $product)
                        <tr>
                            <td><input type="text" name="products[{{ $index }}][name]" class="form-control" value="{{ $product->name }}" required></td>
                            <td><input type="number" name="products[{{ $index }}][quantity]" class="form-control" value="{{ $product->pivot->quantity }}" required></td>
                            <td><input type="number" name="products[{{ $index }}][price]" class="form-control" value="{{ $product->value }}" required></td>
                            <td><input type="number" step="0.01" name="products[{{ $index }}][weight]" class="form-control" value="{{ $product->pivot->weight }}" required></td>
                            <td><input type="number" name="products[{{ $index }}][cod_amount]" class="form-control" value="{{ $product->pivot->cod_amount }}" required></td>
                            <td><button type="button" class="btn btn-danger remove-product">Xóa</button></td>
                        </tr>       
                        @endforeach
                    </tbody>
                </table>
                <button type="button" class="btn btn-success" id="add-product">Thêm sản phẩm</button>
            </div>
        </div>

        <div class="form-group mt-3">
            <button type="submit" class="btn btn-primary">Cập nhật đơn hàng</button>
            <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    
document.addEventListener('DOMContentLoaded', function() {
    const isPickupAtPostOffice = document.getElementById('is_pickup_at_post_office');
    const pickupLocationGroup = document.getElementById('pickup_location_group');

    isPickupAtPostOffice.addEventListener('change', function() {
        if (this.value === '1') {
            pickupLocationGroup.style.display = 'block';
        } else {
            pickupLocationGroup.style.display = 'none';
        }
    });
    const addProductButton = document.getElementById('add-product');
    const productsTable = document.getElementById('products-table').getElementsByTagName('tbody')[0];

    addProductButton.addEventListener('click', function() {
        const newRow = productsTable.insertRow();
        const rowCount = productsTable.rows.length - 1;
        newRow.innerHTML = `
            <td><input type="text" name="products[${rowCount}][name]" class="form-control" required></td>
            <td><input type="number" name="products[${rowCount}][quantity]" class="form-control" required></td>
            <td><input type="number" name="products[${rowCount}][price]" class="form-control" required></td>
            <td><input type="number" step="0.01" name="products[${rowCount}][weight]" class="form-control" required></td>
            <td><input type="number" name="products[${rowCount}][cod_amount]" class="form-control" required></td>
            <td><button type="button" class="btn btn-danger remove-product">Xóa</button></td>
        `;
    });

    productsTable.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-product')) {
            e.target.closest('tr').remove();
        }
    });
});
</script>
@endpush