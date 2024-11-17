@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Chỉnh sửa Kho Tổng</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('provincial-warehouses.update', $provincialWarehouse) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="name" class="form-label">Tên Kho Tổng</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $provincialWarehouse->name) }}" required>
        </div>

        <div class="mb-3">
            <label for="address" class="form-label">Địa chỉ</label>
            <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $provincialWarehouse->address) }}" required>
        </div>

        <div class="mb-3">
            <label for="district" class="form-label">Quận/Huyện</label>
            <input type="text" class="form-control" id="district" name="district" value="{{ old('district', $provincialWarehouse->district) }}" required>
        </div>

        <div class="mb-3">
            <label for="province" class="form-label">Tỉnh/Thành phố</label>
            <input type="text" class="form-control" id="province" name="province" value="{{ old('province', $provincialWarehouse->province) }}" required>
        </div>

        <div class="mb-3">
            <label for="latitude" class="form-label">Vĩ độ</label>
            <input type="number" step="any" class="form-control" id="latitude" name="latitude" value="{{ old('latitude', $provincialWarehouse->latitude) }}" required>
        </div>

        <div class="mb-3">
            <label for="longitude" class="form-label">Kinh độ</label>
            <input type="number" step="any" class="form-control" id="longitude" name="longitude" value="{{ old('longitude', $provincialWarehouse->longitude) }}" required>
        </div>

        <button type="submit" class="btn btn-primary">Cập nhật Kho Tổng</button>
        <a href="{{ route('provincial-warehouses.index') }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
@endsection