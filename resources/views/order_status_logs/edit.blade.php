@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Chi tiết log trạng thái</h1>
    <table class="table">
        <tr>
            <th>ID:</th>
            <td>{{ $statusLog->id }}</td>
        </tr>
        <tr>
            <th>Đơn hàng:</th>
            <td><a href="{{ route('orders.show', $statusLog->order) }}">#{{ $statusLog->order->id }}</a></td>
        </tr>
        <tr>
            <th>Trạng thái:</th>
            <td>{{ $statusLog->status }}</td>
        </tr>
        <tr>
            <th>Mô tả:</th>
            <td>{{ $statusLog->description }}</td>
        </tr>
        <tr>
            <th>Cập nhật bởi:</th>
            <td>{{ $statusLog->updatedBy->name }}</td>
        </tr>
        <tr>
            <th>Thời gian cập nhật:</th>
            <td>{{ $statusLog->created_at->format('d/m/Y H:i:s') }}</td>
        </tr>
    </table>
    <a href="{{ route('orders.status_logs.index', $statusLog->order) }}" class="btn btn-secondary">Quay lại lịch sử</a>
</div>
@endsection