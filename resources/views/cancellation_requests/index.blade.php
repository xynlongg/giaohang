@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Danh sách yêu cầu hủy đơn hàng</h1>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>Mã đơn hàng</th>
                <th>Người yêu cầu</th>
                <th>Bưu cục</th>
                <th>Lý do</th>
                <th>Trạng thái</th>
                <th>Ngày yêu cầu</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cancellationRequests as $request)
            <tr>
                <td>{{ $request->order->tracking_number }}</td>
                <td>{{ $request->user->name }}</td>
                <td>{{ $request->postOffice ? $request->postOffice->name : 'N/A' }}</td>
                <td>{{ $request->reason }}</td>
                <td>{{ ucfirst($request->status) }}</td>
                <td>{{ $request->created_at->format('d/m/Y H:i') }}</td>
                <td>
                    <a href="{{ route('cancellation-requests.show', $request) }}" class="btn btn-sm btn-info">Chi tiết</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $cancellationRequests->links() }}
</div>
@endsection