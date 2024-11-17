@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Đơn Hàng Của Tôi</h1>
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if (session('info'))
        <div class="alert alert-info">
            {{ session('info') }}
        </div>
    @endif

    <div class="card mb-4">
    <div class="card-body">
        <form id="search-form" action="{{ route('customer.orders') }}" method="GET">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <input type="text" name="search" class="form-control" placeholder="Tìm kiếm..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <select name="status" class="form-control">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                        <option value="shipping" {{ request('status') == 'shipping' ? 'selected' : '' }}>Đang giao hàng</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Đã hoàn thành</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                        <option value="assigned_to_post_office" {{ request('status') == 'assigned_to_post_office' ? 'selected' : '' }}>Đã gán cho bưu cục</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                    <a href="{{ route('customer.orders') }}" class="btn btn-secondary">Đặt lại</a>
                </div>
            </div>
        </form>
    </div>
</div>
    <div class="table-responsive">
    @if($orders->isNotEmpty())
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Mã Đơn Hàng</th>
                    <th>Người Nhận</th>
                    <th>Địa Chỉ Nhận</th>
                    <th>Tổng Tiền</th>
                    <th>Ngày Tạo</th>
                    <th>Trạng Thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr>
                    <td>{{ $order->tracking_number }}</td>
                    <td>{{ $order->receiver_name }}</td>
                    <td>{{ $order->receiver_address }}</td>
                    <td>{{ number_format($order->total_amount) }} VND</td>
                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <span class="badge bg-{{ $order->getStatusColor() }} text-white" style="font-size: 0.9em; padding: 5px 10px;">
                            {{ $order->getVietnameseStatus() }}
                        </span>
                    </td>
                    <td>
                        @if($order->status == 'pending' || ($order->status == 'assigned_to_post_office' && (!$order->pickup_date || now()->lte($order->pickup_date->subDay()))))
                            <button type="button" class="btn btn-sm btn-danger" onclick="showCancelModal({{ $order->id }})">Hủy</button>
                        @else
                            <span class="text-muted">Không thể hủy</span>
                        @endif
                    <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-info">Xem</a>

                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $orders->links() }}
    @else
        <p>Không có đơn hàng nào.</p>
    @endif
    </div>

    <div class="d-flex justify-content-center">
        {{ $orders->links() }}
    </div>
</div>

<!-- Modal Hủy Đơn Hàng -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelOrderModalLabel">Hủy Đơn Hàng</h5>
                
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="cancelOrderForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn hủy đơn hàng này?</p>
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label">Lý do hủy đơn</label>
                        <select class="form-select" id="cancellation_reason" name="reason" required>
                            <option value="">Chọn lý do</option>
                            <option value="changed_mind">Đổi ý không muốn mua nữa</option>
                            <option value="found_better_deal">Tìm được sản phẩm tốt hơn</option>
                            <option value="financial_reasons">Lý do tài chính</option>
                            <option value="other">Lý do khác</option>
                        </select>
                    </div>
                    <div class="mb-3" id="other_reason_container" style="display: none;">
                        <label for="other_reason" class="form-label">Lý do khác</label>
                        <textarea class="form-control" id="other_reason" name="other_reason" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-danger">Xác nhận hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showCancelModal(orderId) {
    var modal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
    document.getElementById('cancelOrderForm').action = '/orders/' + orderId + '/cancel';
    modal.show();
}

document.getElementById('cancellation_reason').addEventListener('change', function() {
    var otherReasonContainer = document.getElementById('other_reason_container');
    if (this.value === 'other') {
        otherReasonContainer.style.display = 'block';
    } else {
        otherReasonContainer.style.display = 'none';
    }
});
</script>
@endpush