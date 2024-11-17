@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Danh sách Kho Tổng</h1>
    <a href="{{ route('provincial-warehouses.create') }}" class="btn btn-primary mb-3">Thêm Kho Tổng Mới</a>
    
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>Tên</th>
                <th>Địa chỉ</th>
                <th>Quận/Huyện</th>
                <th>Tỉnh/Thành phố</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach($warehouses as $warehouse)
            <tr>
                <td>{{ $warehouse->name }}</td>
                <td>{{ $warehouse->address }}</td>
                <td>{{ $warehouse->district }}</td>
                <td>{{ $warehouse->province }}</td>
                <td>
                    <a href="{{ route('provincial-warehouses.show', $warehouse) }}" class="btn btn-sm btn-info">Xem</a>
                    <a href="{{ route('provincial-warehouses.edit', $warehouse) }}" class="btn btn-sm btn-warning">Sửa</a>
                    <form action="{{ route('provincial-warehouses.destroy', $warehouse) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa kho tổng này?')">Xóa</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection