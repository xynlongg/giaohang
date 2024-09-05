@extends('layouts.app')

@section('content')
<div class="container d-flex justify-content-center align-items-center"">
    <div class="col-md-6">
        <h2 class="text-center">Create User</h2>
        <form class="row g-3" id="editUserForm">
            <div class="col-12">
                <label for="inputName4" class="form-label">Your Name</label>
                <input type="text" class="form-control" id="inputName4" name="name">
            </div>
            <div class="col-12">
                <label for="inputEmail4" class="form-label">Email</label>
                <input type="email" class="form-control" id="inputEmail4" name="email">
            </div>
            <div class="col-12">
                <label for="inputPassword4" class="form-label">Password</label>
                <input type="password" class="form-control" id="inputPassword4" name="password">
            </div>
            <div class="text-center">
                <button type="button" class="btn btn-primary" onclick="createUser()">Create</button>
            </div>
        </form>
    </div>
</div>


<script type="module">
    function createUser() {
        const name = document.getElementById('inputName4').value;
        const email = document.getElementById('inputEmail4').value;
        const password = document.getElementById('inputPassword4').value;

        window.axios.post('/api/users', {
            name: name,
            email: email,
            password: password,
        })
        .then((response) => {
            console.log('User created:', response.data);
            window.location.href = '/users';
        })
        .catch((error) => {
            console.error('There was an error creating the user!', error);
        });
    }
</script>
@endsection
