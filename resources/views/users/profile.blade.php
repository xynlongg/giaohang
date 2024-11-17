@extends('layouts.app')
<style>
  .profile-card .img-avt {
    width: 450px; /* Tăng kích thước hình đại diện */
    height: 200px; /* Tăng kích thước hình đại diện */
    border-radius: 20px; /* Tạo bo góc nhẹ cho hình đại diện */
    object-fit: cover; /* Đảm bảo hình ảnh giữ nguyên tỷ lệ */
    transition: width 0.3s, height 0.3s; /* Tạo hiệu ứng chuyển đổi mượt mà */
  }

  @media (max-width: 768px) {
    .profile-card .img-avt {
      width: 300px; /* Điều chỉnh kích thước trên màn hình nhỏ hơn */
      height: 200px; /* Điều chỉnh kích thước trên màn hình nhỏ hơn */
    }
  }

  @media (max-width: 480px) {
    .profile-card .img-avt {
      width: 200px; /* Điều chỉnh kích thước trên màn hình rất nhỏ */
      height: 150px; /* Điều chỉnh kích thước trên màn hình rất nhỏ */
    }
  }
</style>
@section('content')
  <div class="pagetitle">
    <h1>Profile</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
        <li class="breadcrumb-item">Users</li>
        <li class="breadcrumb-item active">Profile</li>
      </ol>
    </nav>
  </div><!-- End Page Title -->

  <section class="section profile">
    <div class="row">
      <div class="col-xl-4">
        <div class="card">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        <div class="card-body profile-card pt-6 d-flex flex-column align-items-center">
            <img src="{{ asset('uploads/' . $user->avatar) }}" alt="Cập nhập avatar" class="img-avt">
            <h2>{{ $user->name }}</h2>
            <h3>Web</h3>
            <div class="social-links mt-2">
              <a href="{{ $user->twitter }}" class="twitter"><i class="bi bi-twitter"></i></a>
              <a href="{{ $user->facebook }}" class="facebook"><i class="bi bi-facebook"></i></a>
              <a href="{{ $user->instagram }}" class="instagram"><i class="bi bi-instagram"></i></a>
              <a href="{{ $user->linkedin }}" class="linkedin"><i class="bi bi-linkedin"></i></a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-8">
        <div class="card">
          <div class="card-body pt-3">
            <!-- Bordered Tabs -->
            <ul class="nav nav-tabs nav-tabs-bordered">
              <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-overview">Overview</button>
              </li>
              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-edit">Edit Profile</button>
              </li>
              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-settings">Settings</button>
              </li>
              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-change-password">Change Password</button>
              </li>
            </ul>
            <div class="tab-content pt-2">
              <div class="tab-pane fade show active profile-overview" id="profile-overview">
                <h5 class="card-title">Profile Details</h5>
                <div class="row">
                  <div class="col-lg-3 col-md-4 label">Full Name</div>
                  <div class="col-lg-9 col-md-8">{{ $user->name }}</div>
                </div>
                <div class="row">
                  <div class="col-lg-3 col-md-4 label">Created at</div>
                  <div class="col-lg-9 col-md-8">{{ $user->created_at }}</div>
                </div>
                <div class="row">
                  <div class="col-lg-3 col-md-4 label">Email</div>
                  <div class="col-lg-9 col-md-8">{{ $user->email }}</div>
                </div>
              </div>

            <!-- Profile Edit Form -->
              <div class="tab-pane fade profile-edit pt-3" id="profile-edit">
              <form action="{{ route('users.updateProfile') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row mb-3">
                    <label for="profileImage" class="col-md-4 col-lg-3 col-form-label">Profile Image</label>
                    <div class="col-md-8 col-lg-9">
                    @if ($user->avatar)
                        <img src="{{ asset('uploads/' . $user->avatar) }}" alt="Profile" class="img-fluid rounded-circle" style="width: 100px; height: auto;">
                    @else
                        <p>No profile image available</p>
                    @endif                    <div class="pt-2">
                                <input type="file" name="avatar" class="form-control">

                            @if(Auth::user()->image)
                                <a href="{{ route('users.removeAvatar') }}" class="btn btn-danger btn-sm" title="Remove my profile image"><i class="bi bi-trash"></i></a>
                            @endif
                        </div>
                    </div>
                    </div>
                    <div class="row mb-3">
                      <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Full Name</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="name" type="text" class="form-control" id="fullName" value="{{ $user->name }}">
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="Email" class="col-md-4 col-lg-3 col-form-label">Email</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="email" type="text" class="form-control" id="Email" value="{{ $user->email }}">
                      </div>
                    </div>
                    <!-- Add other fields similarly -->
                    <div class="text-center">
                      <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                  </form><!-- End Profile Edit Form -->
                </div>

              <div class="tab-pane fade pt-3" id="profile-settings">
                <!-- Settings Form -->
                <form>
                  <div class="row mb-3">
                    <label for="emailNotifications" class="col-md-4 col-lg-3 col-form-label">Email Notifications</label>
                    <div class="col-md-8 col-lg-9">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="emailNotifications1" checked>
                        <label class="form-check-label" for="emailNotifications1">
                          New products
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="emailNotifications2" checked>
                        <label class="form-check-label" for="emailNotifications2">
                          New blog posts
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="emailNotifications3" checked>
                        <label class="form-check-label" for="emailNotifications3">
                          Product updates
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="emailNotifications4">
                        <label class="form-check-label" for="emailNotifications4">
                          Daily news
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="emailNotifications5">
                        <label class="form-check-label" for="emailNotifications5">
                          Weekly news
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="emailNotifications6" checked>
                        <label class="form-check-label" for="emailNotifications6">
                          Monthly news
                        </label>
                      </div>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label for="smsNotifications" class="col-md-4 col-lg-3 col-form-label">SMS Notifications</label>
                    <div class="col-md-8 col-lg-9">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="smsNotifications1" checked>
                        <label class="form-check-label" for="smsNotifications1">
                          New products
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="smsNotifications2" checked>
                        <label class="form-check-label" for="smsNotifications2">
                          New blog posts
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="smsNotifications3" checked>
                        <label class="form-check-label" for="smsNotifications3">
                          Product updates
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="smsNotifications4">
                        <label class="form-check-label" for="smsNotifications4">
                          Daily news
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="smsNotifications5">
                        <label class="form-check-label" for="smsNotifications5">
                          Weekly news
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="smsNotifications6" checked>
                        <label class="form-check-label" for="smsNotifications6">
                          Monthly news
                        </label>
                      </div>
                    </div>
                  </div>
                  <div class="text-center">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                  </div>
                </form><!-- End Settings Form -->
              </div>

              <div class="tab-pane fade pt-3" id="profile-change-password">
                <!-- Change Password Form -->
                <form action="" method="POST">
                  @csrf
                  @method('PUT')
                  <div class="row mb-3">
                    <label for="currentPassword" class="col-md-4 col-lg-3 col-form-label">Current Password</label>
                    <div class="col-md-8 col-lg-9">
                      <input name="current_password" type="password" class="form-control" id="currentPassword">
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label for="newPassword" class="col-md-4 col-lg-3 col-form-label">New Password</label>
                    <div class="col-md-8 col-lg-9">
                      <input name="new_password" type="password" class="form-control" id="newPassword">
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label for="renewPassword" class="col-md-4 col-lg-3 col-form-label">Re-enter New Password</label>
                    <div class="col-md-8 col-lg-9">
                      <input name="new_password_confirmation" type="password" class="form-control" id="renewPassword">
                    </div>
                  </div>
                  <div class="text-center">
                    <button type="submit" class="btn btn-primary">Change Password</button>
                  </div>
                </form><!-- End Change Password Form -->
              </div>
            </div><!-- End Bordered Tabs -->
          </div>
        </div>
      </div>
    </div>
  </section>

@push('scripts')
<script type="module">
    const usersElement = document.getElementById('users');

    window.axios.get('/api/users')
        .then((response) => {
            const users = response.data;

            users.forEach((user, index) => {
                const element = document.createElement('li');
                element.setAttribute('id', user.id);
                element.innerText = user.name;

                usersElement.appendChild(element);
            });
        });
</script>
<script type="module">
    const usersElement = document.getElementById('users');

    Echo.channel('users')
        .listen('UserCreated', (e) => {
            const element = document.createElement('li');
            element.setAttribute('id', e.user.id);
            element.innerText = e.user.name;

            usersElement.appendChild(element);
        })
        .listen('UserUpdated', (e) => {
            const element = document.getElementById(e.user.id);
            element.innerText = e.user.name;
        })
        .listen('UserDeleted', (e) => {
            const element = document.getElementById(e.user.id);
            element.parentNode.removeChild(element);
        });
</script>
<script>
  const inputAvatar = document.querySelector('input[name="avatar"]');
  const imgPreview = document.querySelector('.img-fluid.rounded-circle');

  inputAvatar.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        imgPreview.src = e.target.result;
      }
      reader.readAsDataURL(file);
    } else {
      imgPreview.src = "{{ $user->image ? asset('uploads/' . $user->image) : 'assets/img/slides-3.jpg' }}";
    }
  });
</script>
@endpush
@endsection
