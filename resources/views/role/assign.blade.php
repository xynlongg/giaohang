@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    {{ __('Assign/Remove Role') }}
                </div>

                <div class="card-body">
                    <form id="assignRoleForm">
                        <div class="form-group">
                            <label for="user">User</label>
                            <select class="form-control" id="user" name="user" required>
                                <option value="">Vui lòng chọn user cần phân quyền hoặc xóa quyền</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="">Vui lòng chọn Role cần phân quyền hoặc xóa quyền</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" id="assignRole">Assign Role</button>
                        <button type="button" class="btn btn-danger" id="removeRole">Remove Role</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    $(document).ready(function() {
        // Function to populate roles
        function populateRoles() {
            window.axios.get('/api/roles')
                .then((response) => {
                    const roles = response.data;
                    const roleSelect = $('#role');
                    roleSelect.empty();
                    roleSelect.append('<option value="">Vui lòng chọn Role cần phân quyền hoặc xóa quyền</option>');
                    roles.forEach((role) => {
                        roleSelect.append(`<option value="${role.id}">${role.name}</option>`);
                    });
                })
                .catch((error) => {
                    console.error('Có lỗi khi lấy danh sách vai trò:', error);
                });
        }

        // Function to populate users
        function populateUsers() {
            window.axios.get('/api/users')
                .then((response) => {
                    const users = response.data;
                    const userSelect = $('#user');
                    userSelect.empty();
                    userSelect.append('<option value="">Vui lòng chọn user cần phân quyền hoặc xóa quyền</option>');
                    users.forEach((user) => {
                        userSelect.append(`<option value="${user.id}">${user.name}</option>`);
                    });
                })
                .catch((error) => {
                    console.error('Có lỗi khi lấy danh sách người dùng:', error);
                });
        }

        // Populate roles and users on page load
        populateRoles();
        populateUsers();

        // Handle role assignment
        $('#assignRoleForm').submit(function(e) {
            e.preventDefault();
            const userId = $('#user').val();
            const roleId = $('#role').val();

            // Check the current roles of the user
            window.axios.get(`/api/user/${userId}/roles`)
            .then((response) => {
                const currentRoles = response.data;

                if (currentRoles.length >= 3) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Oops...',
                        text: 'Người dùng này đã có 3 vai trò. Không thể thêm vai trò mới.',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                if (currentRoles.some(role => role.id == roleId)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Oops...',
                        text: 'Người dùng này đã có vai trò này. Vui lòng chọn vai trò khác.',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                // Nếu các kiểm tra đều hợp lệ, tiến hành phân vai trò
                window.axios.post(`/api/user/${userId}/assign-role`, {
                    role_id: roleId
                })
                .then((response) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: 'Vai trò đã được phân thành công',
                        confirmButtonText: 'OK'
                    });
                    window.location.href = '/users'; 
                })
                .catch((error) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Có lỗi khi phân vai trò. Vui lòng thử lại sau.',
                        confirmButtonText: 'OK'
                    });
                    console.error('Có lỗi khi phân vai trò:', error);
                });
            })
            .catch((error) => {
                console.error('Có lỗi khi lấy danh sách vai trò hiện tại của người dùng:', error);
            });

        });

        // Handle role removal
        $('#removeRole').click(function() {
            const userId = $('#user').val();
            const roleId = $('#role').val();

            window.axios.delete(`/api/user/${userId}/remove-role`, {
                data: { role_id: roleId }
            })
            .then((response) => {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Vai trò đã được xóa thành công',
                    confirmButtonText: 'OK'
                });
                window.location.href = '/users'; 
            })
            .catch((error) => {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Có lỗi khi xóa vai trò. Vui lòng thử lại sau.',
                    confirmButtonText: 'OK'
                });
                console.error('There was an error removing the role:', error);
            });
        });
    });
</script>
@endpush
 