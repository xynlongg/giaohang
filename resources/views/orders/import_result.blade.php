@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Kết Quả Nhập Đơn Hàng</h1>
    <p>Số đơn hàng đã nhập thành công: {{ $successCount }}</p>
    
    @if (count($failedRows) > 0)
        <h2>Danh sách đơn hàng nhập không thành công:</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Người gửi</th>
                    <th>Người nhận</th>
                    <th>Lỗi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($failedRows as $row)
                    <tr>
                        <td>{{ $row['sender_name'] }}</td>
                        <td>{{ $row['receiver_name'] }}</td>
                        <td>{{ $row['error'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection