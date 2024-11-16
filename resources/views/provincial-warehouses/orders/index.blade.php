@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Danh sách đơn hàng của Kho tổng {{ $warehouse->name }}</h2>

    <form method="GET" action="{{ route('provincial-warehouse-orders.index') }}" class="mb-3">
        <input type="text" name="search" placeholder="Tìm kiếm theo tên người gửi, nhận hoặc mã đơn hàng" value="{{ request('search') }}">
        <select name="status">
            <option value="">Trạng thái</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Đang xử lý</option>
            <!-- Thêm các trạng thái khác nếu cần -->
        </select>
        <input type="date" name="date" value="{{ request('date') }}">
        <button type="submit">Lọc</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Mã đơn hàng</th>
                <th>Người gửi</th>
                <th>Người nhận</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->tracking_number }}</td>
                    <td>{{ $order->sender_name }}</td>
                    <td>{{ $order->receiver_name }}</td>
                    <td>{{ $order->status }}</td>
                    <td>{{ $order->created_at->format('d-m-Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $orders->links() }}
</div>
@endsection
