@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ isset($warrantyPackage) ? 'Sửa gói bảo hành' : 'Tạo gói bảo hành mới' }}</h1>
    
    <form action="{{ isset($warrantyPackage) ? route('warranty-packages.update', $warrantyPackage) : route('warranty-packages.store') }}" method="POST">
        @csrf
        @if(isset($warrantyPackage))
            @method('PUT')
        @endif
        
        <div class="form-group">
            <label for="name">Tên gói bảo hành</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $warrantyPackage->name ?? old('name') }}" required>
        </div>
        
        <div class="form-group">
            <label for="description">Mô tả</label>
            <textarea class="form-control" id="description" name="description" required>{{ $warrantyPackage->description ?? old('description') }}</textarea>
        </div>
        
        <div class="form-group">
            <label for="price">Giá (VND)</label>
            <input type="number" class="form-control" id="price" name="price" value="{{ $warrantyPackage->price ?? old('price') }}" required min="0">
        </div>
        
        <div class="form-group">
            <label>Danh mục áp dụng</label>
            @foreach($categories as $category)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="categories[]" value="{{ $category->id }}" id="category{{ $category->id }}"
                        {{ isset($warrantyPackage) && $warrantyPackage->categories->contains($category->id) ? 'checked' : '' }}>
                    <label class="form-check-label" for="category{{ $category->id }}">
                        {{ $category->name }}
                    </label>
                </div>
            @endforeach
        </div>
        
        <button type="submit" class="btn btn-primary">{{ isset($warrantyPackage) ? 'Cập nhật' : 'Tạo mới' }}</button>
    </form>
</div>
@endsection