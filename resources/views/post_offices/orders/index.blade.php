@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Quản lý đơn hàng - {{ $postOffice->name }}</h1>
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <form action="{{ route('post_office.orders.index', $postOffice->id) }}" method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-3 mb-2">
                <input type="text" name="search" class="form-control" placeholder="Tìm kiếm..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3 mb-2">
                <select name="status" class="form-control">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Đang chờ xử lý</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                    <option value="picked_up" {{ request('status') == 'picked_up' ? 'selected' : '' }}>Đã lấy hàng</option>
                    <option value="in_transit" {{ request('status') == 'in_transit' ? 'selected' : '' }}>Đang vận chuyển</option>
                    <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Đã giao hàng</option>
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <input type="date" name="date" class="form-control" value="{{ request('date') }}">
            </div>
            <div class="col-md-3 mb-2">
                <button type="submit" class="btn btn-primary w-100">Tìm kiếm</button>
            </div>
        </div>
    </form>

    @if($orders->isEmpty())
        <div class="alert alert-info">Không có đơn hàng nào phù hợp với tiêu chí tìm kiếm.</div>
    @else
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Mã đơn hàng</th>
                        <th>Người gửi</th>
                        <th>Người nhận</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Shipper được gán</th>
                        <th>Gán shipper</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td>{{ $order->tracking_number }}</td>
                        <td>{{ $order->sender_name }}</td>
                        <td>{{ $order->receiver_name }}</td>
                        <td>{{ $order->status }}</td>
                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                             @if($order->distributions->isNotEmpty() && $order->distributions->first()->shipper)
                                {{ $order->distributions->first()->shipper->name }}
                            @else
                                Chưa gán
                            @endif
                        </td>
                        <td>
                        <form action="{{ route('post_office.orders.assign_shipper', $order->id) }}" method="POST">
                                @csrf
                                <select name="shipper_id" class="form-control form-control-sm" required>
                                    <option value="">Chọn shipper</option>
                                    @foreach($shippers as $shipper)
                                        <option value="{{ $shipper->id }}">
                                            {{ $shipper->name }} (Điểm: {{ $shipper->attendance_score + $shipper->vote_score }})
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm mt-1">Gán shipper</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $orders->links() }}
    @endif
</div>
@endsection