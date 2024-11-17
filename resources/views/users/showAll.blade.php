@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    {{ __('Users') }}
                    <button id="addUser" class="btn btn-primary float-right">Add User</button>
                </div>

                <div class="card-body">
                    <table id="usersTable" class="display">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Update User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Add User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
<!-- Add Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1" role="dialog" aria-labelledby="roleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roleModalLabel">Add Role</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="roleForm">
                    <div class="form-group">
                        <label for="roleName">Role Name</label>
                        <input type="text" class="form-control" id="roleName" name="roleName" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Role</button>
                </form>
            </div>
        </div>
    </div>
</div>

            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="userId" name="userId">
                    <div class="form-group">
                        <label for="userName">Name</label>
                        <input type="text" class="form-control" id="userName" name="userName" required>
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <label for="role">Role</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="" disabled selected>Select a role</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" id="saveUser">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    $(document).ready(function() {
        const usersTable = $('#usersTable').DataTable();

        // Fetch and display users with roles
        window.axios.get('/api/users')
            .then((response) => {
                const users = response.data;
                usersTable.clear(); // Clear existing table data
                users.forEach((user) => {
                    const roles = user.roles.map(role => role.name).join(', ');
                    usersTable.row.add([
                        user.id,
                        user.name,
                        user.email,
                        roles,
                        `<button class="btn btn-warning btn-sm updateUser" data-id="${user.id}" data-name="${user.name}" data-email="${user.email}" data-roles="${roles}">Update</button>
                         <button class="btn btn-danger btn-sm deleteUser" data-id="${user.id}">Delete</button>`
                    ]).draw();
                });
            })
            .catch((error) => {
                console.error('Error fetching users:', error);
            });

        // Handle form submission for adding/updating user
        $('#userForm').submit(function(e) {
            e.preventDefault();
            const userId = $('#userId').val();
            const userName = $('#userName').val();
            const userEmail = $('#email').val();
            const userRole = $('#role').val(); // Ensure `role` is an array

            const requestType = userId ? 'put' : 'post';
            const url = userId ? `/api/users/${userId}` : '/api/users';

            window.axios[requestType](url, {
                name: userName,
                email: userEmail,
                role: Array.isArray(userRole) ? userRole : [userRole] // Ensure role is an array
            })
            .then((response) => {
                $('#userModal').modal('hide');
                const roles = response.data.roles.map(role => role.name).join(', ');
                if (userId) {
                    // Update existing user row
                    const row = usersTable.row($(`.updateUser[data-id="${userId}"]`).parents('tr'));
                    row.data([
                        userId,
                        userName,
                        userEmail,
                        roles,
                        `<button class="btn btn-warning btn-sm updateUser" data-id="${userId}" data-name="${userName}" data-email="${userEmail}" data-roles="${roles}">Update</button>
                         <button class="btn btn-danger btn-sm deleteUser" data-id="${userId}">Delete</button>`
                    ]).draw();
                } else {
                    // Add new user row
                    const newUser = response.data;
                    usersTable.row.add([
                        newUser.id,
                        newUser.name,
                        newUser.email,
                        newUser.roles.map(role => role.name).join(', '),
                        `<button class="btn btn-warning btn-sm updateUser" data-id="${newUser.id}" data-name="${newUser.name}" data-email="${newUser.email}" data-roles="${newUser.roles.map(role => role.name).join(', ')}">Update</button>
                         <button class="btn btn-danger btn-sm deleteUser" data-id="${newUser.id}">Delete</button>`
                    ]).draw();
                }
            })
            .catch((error) => {
                console.error('There was an error saving the user:', error);
            });
        });

        // Handle Delete button click
        $('#usersTable').on('click', '.deleteUser', function() {
            const userId = $(this).data('id');
            if (confirm('Are you sure you want to delete this user?')) {
                window.axios.delete(`/api/users/${userId}`)
                    .then(() => {
                        usersTable.row($(this).parents('tr')).remove().draw();
                    })
                    .catch((error) => {
                        console.error('There was an error deleting the user:', error);
                    });
            }
        });

        // Populate roles dropdown
        function populateRoles(selectedRoles = []) {
            window.axios.get('/api/roles')
                .then((response) => {
                    const roles = response.data;
                    const roleSelect = $('#role');
                    roleSelect.empty(); // Clear existing options
                    roles.forEach((role) => {
                        const isSelected = selectedRoles.includes(role.id) ? 'selected' : ''; // Use role.id for comparison
                        roleSelect.append(`<option value="${role.id}" ${isSelected}>${role.name}</option>`);
                    });
                })
                .catch((error) => {
                    console.error('Error fetching roles:', error);
                });
        }

        // Handle Update button click
        $('#usersTable').on('click', '.updateUser', function() {
            const userId = $(this).data('id');
            const userName = $(this).data('name');
            const userEmail = $(this).data('email');
            const userRoles = $(this).data('roles').split(', '); // Parse roles
            $('#userId').val(userId);
            $('#userName').val(userName);
            $('#email').val(userEmail);
            populateRoles(userRoles); // Populate with user roles
            $('#userModalLabel').text('Update User');
            $('#userModal').modal('show');
        });

        // Handle Add User button click
        $('#addUser').click(function() {
            $('#userId').val('');
            $('#userName').val('');
            $('#email').val('');
            populateRoles(); // Populate roles dropdown
            $('#userModalLabel').text('Add User');
            $('#userModal').modal('show');
        });
    });
</script>



@endpush
