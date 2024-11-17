@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Danh sách Sản phẩm</h1>
    <a href="{{ route('products.create') }}" class="btn btn-primary mb-3">Thêm Sản phẩm mới</a>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <table class="table" id="products-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên</th>
                <th>Giá trị</th>
                <th>Hình ảnh</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr data-product-id="{{ $product->id }}">
                    <td>{{ $product->id }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ number_format($product->value) }} VND</td>
                    <td>
                    @if ($product->image)
                        <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" style="max-width: 50px;">
                    @else
                        Không có hình ảnh
                    @endif
                    </td>
                    <td>
                        <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-info">Xem</a>
                        <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-primary">Sửa</a>
                        <form action="{{ route('products.destroy', $product) }}" method="POST" style="display: inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script>
    // Enable pusher logging - don't include this in production
    Pusher.logToConsole = true;

    var pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
        cluster: '{{ env('PUSHER_APP_CLUSTER') }}'
    });

    var channel = pusher.subscribe('product-channel');
    
    channel.bind('product-created', function(data) {
        var product = data.product;
        var newRow = `
            <tr data-product-id="${product.id}">
                <td>${product.id}</td>
                <td>${product.name}</td>
                <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(product.value)}</td>
                <td>${product.image ? `<img src="${product.image_url}" alt="${product.name}" style="max-width: 50px;">` : 'Không có hình ảnh'}</td>
                <td>
                    <a href="/products/${product.id}" class="btn btn-sm btn-info">Xem</a>
                    <a href="/products/${product.id}/edit" class="btn btn-sm btn-primary">Sửa</a>
                    <form action="/products/${product.id}" method="POST" style="display: inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')">Xóa</button>
                    </form>
                </td>
            </tr>
        `;
        $('#products-table tbody').append(newRow);
    });

    channel.bind('product-updated', function(data) {
        var product = data.product;
        var row = $(`tr[data-product-id="${product.id}"]`);
        row.find('td:eq(1)').text(product.name);
        row.find('td:eq(2)').text(new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(product.value));
        if (product.image) {
            row.find('td:eq(3)').html(`<img src="${product.image_url}" alt="${product.name}" style="max-width: 50px;">`);
        } else {
            row.find('td:eq(3)').text('Không có hình ảnh');
        }
    });

    channel.bind('product-deleted', function(data) {
        $(`tr[data-product-id="${data.id}"]`).remove();
    });
</script>
@endpush