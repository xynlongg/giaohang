@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Theo dõi Đơn Hàng #{{ $order->id }}</h1>

    <div class="card mb-3">
        <div class="card-header">
            <h2>Thông tin đơn hàng</h2>
        </div>
        <div class="card-body">
            <p><strong>Trạng thái:</strong> {{ ucfirst($order->status) }}</p>
            <p><strong>Vị trí hiện tại:</strong> {{ $order->currentLocation ? $order->currentLocation->name : 'Chưa có thông tin' }}</p>
            <p><strong>Người gửi:</strong> {{ $order->sender_name }}</p>
            <p><strong>Người nhận:</strong> {{ $order->receiver_name }}</p>
            <p><strong>Ngày tạo đơn:</strong> {{ $order->created_at->format('d/m/Y H:i:s') }}</p>
            <p><strong>Ngày giao hàng dự kiến:</strong> {{ $order->delivery_date ? $order->delivery_date->format('d/m/Y') : 'Chưa xác định' }}</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h2>Lịch sử trạng thái</h2>
        </div>
        <div class="card-body">
            <ul>
                @foreach($order->statusLogs as $log)
                    <li>
                        {{ $log->created_at->format('d/m/Y H:i:s') }} - {{ ucfirst($log->status) }}
                        @if($log->description)
                            : {{ $log->description }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection