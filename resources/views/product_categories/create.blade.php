@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ isset($productCategory) ? 'Sửa danh mục' : 'Tạo danh mục mới' }}</h1>
    
    <form action="{{ isset($productCategory) ? route('product-categories.update', $productCategory) : route('product-categories.store') }}" method="POST">
        @csrf
        @if(isset($productCategory))
            @method('PUT')
        @endif
        
        <div class="form-group">
            <label for="name">Tên danh mục</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $productCategory->name ?? old('name') }}" required>
        </div>
        
        <div class="form-group">
            <label for="description">Mô tả</label>
            <textarea class="form-control" id="description" name="description">{{ $productCategory->description ?? old('description') }}</textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">{{ isset($productCategory) ? 'Cập nhật' : 'Tạo mới' }}</button>
    </form>
</div>
@endsection