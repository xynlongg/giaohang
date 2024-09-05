@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="my-4">Tìm kiếm đơn hàng</h1>

    <form action="{{ route('searchOrder.results') }}" method="GET">
        <div class="form-group">
            <input type="text" name="query" class="form-control" placeholder="Nhập mã đơn hàng hoặc tên người gửi/nhận" required>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Tìm kiếm</button>
    </form>

    @if(isset($message))
        <div class="alert alert-warning mt-4">
            {{ $message }}
        </div>
    @endif
</div>
@endsection
