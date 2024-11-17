@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Danh sách danh mục sản phẩm</h1>
    <a href="{{ route('product-categories.create') }}" class="btn btn-primary mb-3">Tạo danh mục mới</a>
    
    <table class="table">
        <thead>
            <tr>
                <th>Tên</th>
                <th>Mô tả</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categories as $category)
            <tr>
                <td>{{ $category->name }}</td>
                <td>{{ $category->description }}</td>
                <td>
                    <a href="{{ route('product-categories.edit', $category) }}" class="btn btn-sm btn-primary">Sửa</a>
                    <form action="{{ route('product-categories.destroy', $category) }}" method="POST" class="d-inline">
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