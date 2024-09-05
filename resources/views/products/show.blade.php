@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Chi tiết Sản phẩm</h1>

    <div id="product-details" data-product-id="{{ $product->id }}">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title" id="product-name">{{ $product->name }}</h5>
                <p class="card-text"><strong>Mô tả:</strong> <span id="product-description">{{ $product->description ?: 'Không có mô tả' }}</span></p>
                <p class="card-text"><strong>Giá trị:</strong> <span id="product-value">{{ number_format($product->value) }}</span> VND</p>
                <div id="product-image">
                    @if ($product->image)
                        <img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" style="max-width: 300px;">
                    @else
                        <p>Không có hình ảnh</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('products.edit', $product) }}" class="btn btn-primary">Chỉnh sửa</a>
            <a href="{{ route('products.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
            <form action="{{ route('products.destroy', $product) }}" method="POST" style="display: inline-block;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')">Xóa</button>
            </form>
        </div>
    </div>
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
    var productId = $('#product-details').data('product-id');
    
    channel.bind('product-updated', function(data) {
        if (data.product.id == productId) {
            updateProductDetails(data.product);
        }
    });

    channel.bind('product-deleted', function(data) {
        if (data.id == productId) {
            alert('Sản phẩm này đã bị xóa.');
            window.location.href = '{{ route('products.index') }}';
        }
    });

    function updateProductDetails(product) {
        $('#product-name').text(product.name);
        $('#product-description').text(product.description || 'Không có mô tả');
        $('#product-value').text(new Intl.NumberFormat('vi-VN').format(product.value));
        
        if (product.image) {
            $('#product-image').html(`<img src="${'{{ asset('storage') }}/' + product.image}" alt="${product.name}" style="max-width: 300px;">`);
        } else {
            $('#product-image').html('<p>Không có hình ảnh</p>');
        }
    }

    // Xử lý xóa sản phẩm
    $('form').on('submit', function(e) {
        e.preventDefault();
        if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) {
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    alert('Sản phẩm đã được xóa thành công.');
                    window.location.href = '{{ route('products.index') }}';
                },
                error: function(xhr) {
                    alert('Đã xảy ra lỗi khi xóa sản phẩm.');
                }
            });
        }
    });
</script>
@endpush