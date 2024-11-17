@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Quản lý nhân viên bưu cục</h1>

    <div id="realtime-notifications"></div>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('post_offices.staff.index') }}" method="GET">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Tìm kiếm nhân viên..." value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">Tìm kiếm</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(request('search'))
    <div class="card mb-4">
        <div class="card-header">Kết quả tìm kiếm</div>
        <div class="card-body">
            <ul class="list-group">
                @foreach($allUsers as $user)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $user->name }} ({{ $user->email }})
                        <form action="{{ route('post_offices.staff.assign_role', $user) }}" method="POST" class="d-inline">
                            @csrf
                            <select name="role" class="form-control form-control-sm" onchange="this.form.submit()">
                                <option value="">Chọn quyền</option>
                                <option value="post_office_staff" {{ $user->hasRole('post_office_staff') ? 'selected' : '' }}>
                                    Nhân viên bưu cục
                                </option>
                                <option value="post_office_manager" {{ $user->hasRole('post_office_manager') ? 'selected' : '' }}>
                                    Quản lý bưu cục
                                </option>
                                <option value="general_distribution_staff" {{ $user->hasRole('general_distribution_staff') ? 'selected' : '' }}>
                                    Nhân viên phân phối tổng
                                </option>
                                <option value="local_distribution_staff" {{ $user->hasRole('local_distribution_staff') ? 'selected' : '' }}>
                                    Nhân viên phân phối địa phương
                                </option>
                            </select>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header">Nhân viên bưu cục hiện tại</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="staff-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Tên</th>
                            <th>Email</th>
                            <th>Quyền</th>
                            <th>Bưu cục</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($filteredUsers as $user)
                        <tr data-user-id="{{ $user->id }}">
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <form action="{{ route('post_offices.staff.assign_role', $user) }}" method="POST">
                                    @csrf
                                    <select name="role" class="form-control form-control-sm" onchange="this.form.submit()">
                                        <option value="post_office_staff" {{ $user->hasRole('post_office_staff') ? 'selected' : '' }}>Nhân viên bưu cục</option>
                                        <option value="post_office_manager" {{ $user->hasRole('post_office_manager') ? 'selected' : '' }}>Quản lý bưu cục</option>
                                        <option value="local_distribution_staff" {{ $user->hasRole('local_distribution_staff') ? 'selected' : '' }}>Nhân viên phân phối nội cục</option>
                                         <option value="general_distribution_staff" {{ $user->hasRole('general_distribution_staff') ? 'selected' : '' }}>Nhân viên phân phối lên kho</option>
                            
                                    </select>
                                </form>
                            </td>
                            <td>
                                <form action="{{ route('post_offices.staff.assign_post_office', $user) }}" method="POST">
                                    @csrf
                                    <select name="post_office_id" class="form-control form-control-sm" onchange="this.form.submit()">
                                        <option value="">Chọn bưu cục</option>
                                        @foreach($postOffices as $postOffice)
                                            <option value="{{ $postOffice->id }}" {{ $user->postOffices->contains($postOffice->id) ? 'selected' : '' }}>
                                                {{ $postOffice->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td>
                                @if($user->postOffices->isNotEmpty())
                                    <form action="{{ route('post_offices.staff.remove_from_post_office', $user) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa nhân viên này khỏi bưu cục?')">
                                            <i class="fas fa-trash-alt"></i> Xóa khỏi bưu cục
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/realtime-updates.js') }}" defer></script>
<script src="{{ asset('js/app.js') }}"></script>
<script>
    Echo.channel('staff-updates')
        .listen('StaffUpdated', (e) => {
            console.log(e);
            let notificationDiv = document.getElementById('realtime-notifications');
            let notification = document.createElement('div');
            notification.className = 'alert alert-info alert-dismissible fade show';
            notification.innerHTML = `
                ${e.message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            `;
            notificationDiv.appendChild(notification);
            
            // Tự động ẩn thông báo sau 5 giây
            setTimeout(() => {
                notification.remove();
            }, 5000);

            // Cập nhật bảng nhân viên
            let userRow = document.querySelector(`tr[data-user-id="${e.userId}"]`);
            if (userRow) {
                
            }
        });
</script>
@endpush