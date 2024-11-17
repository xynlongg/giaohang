<!-- resources/views/shipper/show.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Thông tin chi tiết Shipper</h2>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">{{ $shipper->name }}</h5>
            <p class="card-text"><strong>Email:</strong> {{ $shipper->email }}</p>
            <p class="card-text"><strong>Số điện thoại:</strong> {{ $shipper->phone }}</p>
            <p class="card-text"><strong>CCCD:</strong> {{ $shipper->cccd }}</p>
            <p class="card-text"><strong>Loại công việc:</strong> {{ $shipper->job_type }}</p>
            <p class="card-text"><strong>Thành phố:</strong> {{ $shipper->city }}</p>
            <p class="card-text"><strong>Quận/Huyện:</strong> {{ $shipper->district }}</p>
            <p class="card-text"><strong>Ngày đăng ký:</strong> {{ $shipper->created_at->format('d/m/Y H:i:s') }}</p>
            <p class="card-text"><strong>Trạng thái:</strong> {{ $shipper->status }}</p>
        </div>
    </div>
    
    @if($shipper->status === 'pending')
    <div class="mt-3">
        <form action="{{ route('shippers.approve', $shipper->id) }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-success">Duyệt</button>
        </form>
        <form action="{{ route('shippers.reject', $shipper->id) }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-danger">Từ chối</button>
        </form>
    </div>
    @endif
    
    <a href="{{ route('shippers.index') }}" class="btn btn-primary mt-3">Quay lại danh sách</a>
</div>
@endsection