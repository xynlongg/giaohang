@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    {{ __('Vai trò') }}
                    <button id="addRole" class="btn btn-primary float-right">Thêm vai trò</button>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên</th>
                                    <th>Số người dùng</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="rolesBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm/Cập nhật Vai trò -->
<div class="modal fade" id="roleModal" tabindex="-1" role="dialog" aria-labelledby="roleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roleModalLabel">Thêm vai trò</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="roleForm">
                    @csrf
                    <input type="hidden" id="roleId" name="roleId">
                    <div class="form-group">
                        <label for="roleName">Tên</label>
                        <input type="text" class="form-control" id="roleName" name="name" required>
                    </div>
                    <button type="submit" class="btn btn-primary" id="saveRole">Lưu</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<!-- FontAwesome CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpush

@push('scripts')
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Hàm tải và hiển thị vai trò
    function fetchRoles() {
        $.ajax({
            url: '/api/roles',
            method: 'GET',
            success: function(response) {
                console.log('Dữ liệu vai trò:', response);
                const rolesBody = $('#rolesBody');
                rolesBody.empty();

                if (Array.isArray(response) && response.length > 0) {
                    response.forEach(function(role) {
                        rolesBody.append(`
                            <tr>
                                <td>${role.id}</td>
                                <td>${role.name}</td>
                                <td>${role.users_count || 0}</td>
                                <td>
                                    <button class="btn btn-warning btn-sm updateRole" data-id="${role.id}" data-name="${role.name}">
                                        <i class="fas fa-edit"></i> Sửa
                                    </button>
                                    ${role.users_count === 0 ? 
                                        `<button class="btn btn-danger btn-sm deleteRole" data-id="${role.id}">
                                            <i class="fas fa-trash"></i> Xóa
                                        </button>` : 
                                        ''
                                    }
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    rolesBody.append('<tr><td colspan="4" class="text-center">Không có dữ liệu</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Lỗi khi tải danh sách vai trò:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Không thể tải danh sách vai trò. Vui lòng thử lại sau.'
                });
            }
        });
    }

    // Gọi hàm tải vai trò khi trang đã sẵn sàng
    fetchRoles();

    // Xử lý thêm vai trò
    $('#addRole').click(function() {
        $('#roleId').val('');
        $('#roleName').val('');
        $('#roleModalLabel').text('Thêm vai trò');
        $('#roleModal').modal('show');
    });

    // Xử lý cập nhật vai trò
    $('#rolesBody').on('click', '.updateRole', function() {
        const roleId = $(this).data('id');
        const roleName = $(this).data('name');
        $('#roleId').val(roleId);
        $('#roleName').val(roleName);
        $('#roleModalLabel').text('Cập nhật vai trò');
        $('#roleModal').modal('show');
    });

    // Xử lý form thêm/cập nhật vai trò
    $('#roleForm').submit(function(e) {
        e.preventDefault();
        const roleId = $('#roleId').val();
        const roleName = $('#roleName').val();
        const url = roleId ? `/api/roles/${roleId}` : '/api/roles';
        const method = roleId ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: {
                name: roleName,
                _token: $('input[name="_token"]').val()
            },
            success: function(response) {
                $('#roleModal').modal('hide');
                fetchRoles();
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: roleId ? 'Vai trò đã được cập nhật' : 'Vai trò mới đã được thêm'
                });
            },
            error: function(xhr, status, error) {
                console.error('Lỗi khi lưu vai trò:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: xhr.responseJSON?.message || 'Có lỗi xảy ra khi lưu vai trò. Vui lòng thử lại.'
                });
            }
        });
    });

    // Xử lý xóa vai trò
    $('#rolesBody').on('click', '.deleteRole', function() {
        const roleId = $(this).data('id');
        
        Swal.fire({
            title: 'Bạn có chắc chắn?',
            text: "Bạn không thể hoàn tác hành động này!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Có, xóa nó!',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/api/roles/${roleId}`,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function() {
                        fetchRoles();
                        Swal.fire(
                            'Đã xóa!',
                            'Vai trò đã được xóa.',
                            'success'
                        );
                    },
                    error: function(xhr, status, error) {
                        console.error('Lỗi khi xóa vai trò:', error);
                        Swal.fire(
                            'Lỗi!',
                            xhr.responseJSON?.message || 'Không thể xóa vai trò này.',
                            'error'
                        );
                    }
                });
            }
        });
    });
});
</script>
@endpush