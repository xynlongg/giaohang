@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Danh sách gói bảo hành</h1>
    <a href="{{ route('warranty-packages.create') }}" class="btn btn-primary mb-3">Tạo gói bảo hành mới</a>
    
    <table class="table">
        <thead>
            <tr>
                <th>Tên</th>
                <th>Mô tả</th>
                <th>Giá</th>
                <th>Danh mục áp dụng</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @foreach($warrantyPackages as $package)
            <tr>
                <td>{{ $package->name }}</td>
                <td>{{ $package->description }}</td>
                <td>{{ number_format($package->price) }} VND</td>
                <td>{{ $package->categories->pluck('name')->implode(', ') }}</td>
                <td>
                    <a href="{{ route('warranty-packages.edit', $package) }}" class="btn btn-sm btn-primary">Sửa</a>
                    <form action="{{ route('warranty-packages.destroy', $package) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">Xóa</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection