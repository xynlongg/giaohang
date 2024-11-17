@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Danh sách Đơn Hàng</h1>

    <div class="mb-3">
        <a href="{{ route('orders.create') }}" class="btn btn-primary">Tạo Đơn Hàng Mới</a>
        <a href="{{ route('orders.import') }}" class="btn btn-success">Import Đơn Hàng Bằng Excel</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="search-form" action="{{ route('orders.index') }}" method="GET">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <input type="text" name="search" class="form-control" placeholder="Tìm kiếm..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <select name="status" class="form-control">
                            <option value="">Tất cả trạng thái</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Đang xử lý</option>
                            <option value="shipping" {{ request('status') == 'shipping' ? 'selected' : '' }}>Đang giao hàng</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Đã hoàn thành</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        <a href="{{ route('orders.index') }}" class="btn btn-secondary">Đặt lại</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="new-order-notification" class="alert alert-success" style="display: none;">
        Có đơn hàng mới đã được thêm vào danh sách.
    </div>
    <div id="new-order-notification" class="alert alert-success" style="display: none;">
        Có đơn hàng mới đã được thêm vào danh sách.
    </div>
    <div id="update-order-notification" class="alert alert-info" style="display: none;">
        Một đơn hàng vừa được cập nhật.
    </div>
    <div id="delete-order-notification" class="alert alert-warning" style="display: none;">
        Một đơn hàng đã bị xóa.
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Tracking Number</th>
                    <th>Người gửi</th>
                    <th>Người nhận</th>
                    <th>Địa chỉ nhận hàng</th>
                    <th>Địa chỉ hiện tại</th>
                    <th>Số điện thoại</th>
                    <th>Tiền COD</th>
                    <th>Lấy tại bưu cục</th>
                    <th>Ngày gửi</th>
                    <th>Ngày nhận dự kiến</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody id="orders-table-body">
                @foreach($orders as $order)
                    @include('orders.partials.order_row', ['order' => $order])
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center">
        {{ $orders->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
@vite(['resources/js/app.js', 'resources/js/realtime-orders.js'])
@endpush

@push('styles')
<style>
@keyframes highlightRow {
    0% { background-color: #ffff99; }
    100% { background-color: transparent; }
}
</style>
@endpush