@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Danh sách Đơn Hàng</h1>

    <div class="mb-3">
        <a href="{{ route('orders.create') }}" class="btn btn-primary">Tạo Đơn Hàng Mới</a>
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

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Người gửi</th>
                    <th>Người nhận</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Ngày lấy hàng</th>
                    <th>Ngày giao hàng</th>
                    <th>Lấy tại bưu cục</th>
                    <th>Ngày tạo</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody id="orders-table-body">
                @foreach($orders as $order)
                <tr data-order-id="{{ $order->id }}">
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->sender_name }}</td>
                    <td>{{ $order->receiver_name }}</td>
                    <td>{{ number_format($order->total_amount) }} VND</td>
                    <td>
                        <span class="badge bg-{{ $order->status == 'pending' ? 'warning' : ($order->status == 'shipping' ? 'info' : ($order->status == 'completed' ? 'success' : 'danger')) }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td>{{ $order->pickup_date ? $order->pickup_date->format('d/m/Y H:i') : 'N/A' }}</td>
                    <td>{{ $order->delivery_date ? $order->delivery_date->format('d/m/Y H:i') : 'N/A' }}</td>
                    <td>{{ $order->is_pickup_at_post_office ? 'Có' : 'Không' }}</td>
                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-info">Xem</a>
                        <a href="{{ route('orders.edit', $order) }}" class="btn btn-sm btn-primary">Sửa</a>
                        <form action="{{ route('orders.destroy', $order) }}" method="POST" style="display: inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này?')">Xóa</button>
                        </form>
                    </td>
                </tr>
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
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script>
    // Pusher configuration
    var pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
        cluster: '{{ env('PUSHER_APP_CLUSTER') }}'
    });

    var channel = pusher.subscribe('order-channel');
    
    channel.bind('order-created', function(data) {
        var order = data.order;
        var newRow = `
            <tr data-order-id="${order.id}">
                <td>${order.id}</td>
                <td>${order.sender_name}</td>
                <td>${order.receiver_name}</td>
                <td>${new Intl.NumberFormat('vi-VN').format(order.total_amount)} VND</td>
                <td>
                    <span class="badge bg-${order.status == 'pending' ? 'warning' : (order.status == 'shipping' ? 'info' : (order.status == 'completed' ? 'success' : 'danger'))}">
                        ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                    </span>
                </td>
                <td>${order.pickup_date ? new Date(order.pickup_date).toLocaleString('vi-VN') : 'N/A'}</td>
                <td>${order.delivery_date ? new Date(order.delivery_date).toLocaleString('vi-VN') : 'N/A'}</td>
                <td>${order.is_pickup_at_post_office ? 'Có' : 'Không'}</td>
                <td>${new Date(order.created_at).toLocaleString('vi-VN')}</td>
                <td>
                    <a href="/orders/${order.id}" class="btn btn-sm btn-info">Xem</a>
                    <a href="/orders/${order.id}/edit" class="btn btn-sm btn-primary">Sửa</a>
                    <form action="/orders/${order.id}" method="POST" style="display: inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này?')">Xóa</button>
                    </form>
                </td>
            </tr>
        `;
        $('#orders-table-body').prepend(newRow);
    });

    channel.bind('order-updated', function(data) {
        var order = data.order;
        var row = $(`tr[data-order-id="${order.id}"]`);
        row.find('td:eq(1)').text(order.sender_name);
        row.find('td:eq(2)').text(order.receiver_name);
        row.find('td:eq(3)').text(new Intl.NumberFormat('vi-VN').format(order.total_amount) + ' VND');
        row.find('td:eq(4)').html(`
            <span class="badge bg-${order.status == 'pending' ? 'warning' : (order.status == 'shipping' ? 'info' : (order.status == 'completed' ? 'success' : 'danger'))}">
                ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
            </span>
        `);
        row.find('td:eq(5)').text(order.pickup_date ? new Date(order.pickup_date).toLocaleString('vi-VN') : 'N/A');
        row.find('td:eq(6)').text(order.delivery_date ? new Date(order.delivery_date).toLocaleString('vi-VN') : 'N/A');
        row.find('td:eq(7)').text(order.is_pickup_at_post_office ? 'Có' : 'Không');
    });

    channel.bind('order-deleted', function(data) {
        $(`tr[data-order-id="${data.id}"]`).remove();
    });

    // Handle form submission
    $('#search-form').on('submit', function(e) {
        e.preventDefault();
        var url = $(this).attr('action') + '?' + $(this).serialize();
        $.get(url, function(data) {
            $('#orders-table-body').html(data);
        });
    });
</script>
@endpush