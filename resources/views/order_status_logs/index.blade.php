@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Lịch sử trạng thái đơn hàng #{{ $order->id }}</h1>
    <table class="table">
        <thead>
            <tr>
                <th>Thời gian</th>
                <th>Trạng thái</th>
                <th>Mô tả</th>
                <th>Cập nhật bởi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($statusLogs as $log)
            <tr>
                <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                <td>{{ $log->status }}</td>
                <td>{{ $log->description }}</td>
                <td>{{ $log->updatedBy->name }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary">Quay lại đơn hàng</a>
</div>
@endsection