@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Chi tiết yêu cầu hủy đơn hàng</h1>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Thông tin đơn hàng</h5>
            <p><strong>Mã đơn hàng:</strong> {{ $cancellationRequest->order->tracking_number }}</p>
            <p><strong>Trạng thái đơn hàng:</strong> {{ ucfirst($cancellationRequest->order->status) }}</p>
            <p><strong>Bưu cục:</strong> {{ $cancellationRequest->postOffice ? $cancellationRequest->postOffice->name : 'N/A' }}</p>
            <p><strong>Người yêu cầu:</strong> {{ $cancellationRequest->user->name }}</p>
            <p><strong>Lý do hủy:</strong> {{ $cancellationRequest->reason }}</p>
            <p><strong>Trạng thái yêu cầu:</strong> {{ ucfirst($cancellationRequest->status) }}</p>
            <p><strong>Ngày yêu cầu:</strong> {{ $cancellationRequest->created_at->format('d/m/Y H:i') }}</p>

            @if($cancellationRequest->status === 'pending')
                <form action="{{ route('cancellation-requests.process', $cancellationRequest) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="status">Xử lý yêu cầu:</label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="approved">Chấp nhận</option>
                            <option value="rejected">Từ chối</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="comment">Ghi chú:</label>
                        <textarea name="comment" id="comment" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Xác nhận</button>
                </form>
            @else
                <p><strong>Kết quả xử lý:</strong> {{ ucfirst($cancellationRequest->status) }}</p>
                @if($cancellationRequest->admin_comment)
                    <p><strong>Ghi chú:</strong> {{ $cancellationRequest->admin_comment }}</p>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection