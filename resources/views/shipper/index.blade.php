<!-- resources/views/shipper/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Danh sách Shipper đã đăng ký</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Loại công việc</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shippers as $shipper)
            <tr>
                <td>{{ $shipper->id }}</td>
                <td>{{ $shipper->name }}</td>
                <td>{{ $shipper->email }}</td>
                <td>{{ $shipper->phone }}</td>
                <td>{{ $shipper->job_type }}</td>
                <td>
                    <a href="{{ route('shippers.show', $shipper->id) }}" class="btn btn-info btn-sm">Chi tiết</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $shippers->links() }} <!-- Phân trang -->
</div>
@endsection