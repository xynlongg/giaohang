@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Danh sách Bưu cục</h1>
    <a href="{{ route('post_offices.create') }}" class="btn btn-primary mb-3">Thêm Bưu cục mới</a>

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
            @foreach($postOffices as $postOffice)
            <tr>
                <td>{{ $postOffice->name }}</td>
                <td>{{ $postOffice->address }}</td>
                <td>{{ $postOffice->district }}</td>
                <td>{{ $postOffice->province }}</td>
                <td>
                    <a href="{{ route('post_offices.show', $postOffice) }}" class="btn btn-info btn-sm">Xem</a>
                    <a href="{{ route('post_offices.edit', $postOffice) }}" class="btn btn-primary btn-sm">Sửa</a>
                    <form action="{{ route('post_offices.destroy', $postOffice) }}" method="POST" style="display: inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa bưu cục này?')">Xóa</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection