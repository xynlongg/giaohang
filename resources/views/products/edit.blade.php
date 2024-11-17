@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Chỉnh sửa Sản phẩm</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="edit-product-form" action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Tên sản phẩm:</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $product->name }}" required>
        </div>
        <div class="form-group">
            <label for="description">Mô tả:</label>
            <textarea class="form-control" id="description" name="description" rows="3">{{ $product->description }}</textarea>
        </div>
        <div class="form-group">
            <label for="value">Giá trị (VND):</label>
            <input type="number" class="form-control" id="value" name="value" value="{{ $product->value }}" min="0" required>
        </div>
        <div class="form-group">
            <label for="image">Hình ảnh:</label>
            <input type="file" class="form-control-file" id="image" name="image">
            @if ($product->image)
                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" style="max-width: 200px; margin-top: 10px;">
            @endif
        </div>
        <button type="submit" class="btn btn-primary">Cập nhật sản phẩm</button>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script>
    $(document).ready(function() {
        $('#edit-product-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    alert('Sản phẩm đã được cập nhật thành công!');
                    window.location.href = '{{ route('products.index') }}';
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON.errors;
                    var errorString = '';
                    $.each(errors, function(key, value) {
                        errorString += value + '<br>';
                    });
                    $('.alert-danger').html(errorString).show();
                }
            });
        });
    });
</script>
@endpush