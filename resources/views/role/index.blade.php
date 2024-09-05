@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    {{ __('Roles') }}
                    <button id="addRole" class="btn btn-primary float-right">Add Role</button>
                </div>

                <div class="card-body">
                    <table id="rolesTable" class="display">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Actions</th>
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

<!-- Add/Update Role Modal -->
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
                    <input type="hidden" id="roleId" name="roleId">
                    <div class="form-group">
                        <label for="roleName">Name</label>
                        <input type="text" class="form-control" id="roleName" name="roleName" required>
                    </div>
                    <button type="submit" class="btn btn-primary" id="saveRole">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    $(document).ready(function() {
        const rolesTable = $('#rolesTable').DataTable();

        // Fetch and display roles
        window.axios.get('/api/roles')
            .then((response) => {
                const roles = response.data;

                roles.forEach((role) => {
                    rolesTable.row.add([
                        role.id,
                        role.name,
                        `<button class="btn btn-warning btn-sm updateRole" data-id="${role.id}" data-name="${role.name}">Update</button>`
                    ]).draw();
                });
            })
            .catch((error) => {
                console.error('There was an error fetching the roles:', error);
            });

        // Add Role button click
        $('#addRole').click(function() {
            $('#roleId').val('');
            $('#roleName').val('');
            $('#roleModalLabel').text('Add Role');
            $('#roleModal').modal('show');
        });

        // Update Role button click
        $('#rolesTable').on('click', '.updateRole', function() {
            const roleId = $(this).data('id');
            const roleName = $(this).data('name');
            $('#roleId').val(roleId);
            $('#roleName').val(roleName);
            $('#roleModalLabel').text('Update Role');
            $('#roleModal').modal('show');
        });

        // Handle form submission for adding/updating role
        $('#roleForm').submit(function(e) {
            e.preventDefault();
            const roleId = $('#roleId').val();
            const roleName = $('#roleName').val();
            const requestType = roleId ? 'put' : 'post';
            const url = roleId ? `/api/roles/${roleId}` : '/api/roles';

            window.axios[requestType](url, {
                name: roleName
            })
            .then((response) => {
                $('#roleModal').modal('hide');
                const role = response.data;
                if (roleId) {
                    // Update role row
                    const row = rolesTable.row($(`.updateRole[data-id="${roleId}"]`).parents('tr'));
                    row.data([
                        role.id,
                        role.name,
                        `<button class="btn btn-warning btn-sm updateRole" data-id="${role.id}" data-name="${role.name}">Update</button>`
                    ]).draw();
                } else {
                    // Add new role row
                    rolesTable.row.add([
                        role.id,
                        role.name,
                        `<button class="btn btn-warning btn-sm updateRole" data-id="${role.id}" data-name="${role.name}">Update</button>`
                    ]).draw();
                }
            })
            .catch((error) => {
                console.error('There was an error saving the role:', error);
            });
        });
    });
</script>
@endpush
