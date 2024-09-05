@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Thêm Sản phẩm mới</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="create-product-form" action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="name">Tên sản phẩm:</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="description">Mô tả:</label>
            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label for="value">Giá trị (VND):</label>
            <input type="number" class="form-control" id="value" name="value" min="0" required>
        </div>
        <div class="form-group">
            <label for="image">Hình ảnh:</label>
            <input type="file" class="form-control-file" id="image" name="image">
        </div>
        <button type="submit" class="btn btn-primary">Thêm sản phẩm</button>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script>
    $(document).ready(function() {
        $('#create-product-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    alert('Sản phẩm đã được tạo thành công!');
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