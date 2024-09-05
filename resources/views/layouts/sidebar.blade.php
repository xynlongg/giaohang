<aside id="sidebar" class="sidebar">
  <ul class="sidebar-nav" id="sidebar-nav">

    <!-- Dashboard -->
    <li class="nav-item">
      <a class="nav-link" href="{{ url('dashboard') }}">
        <i class="bi bi-grid"></i>
        <span>Dashboard</span>
      </a>
    </li><!-- End Dashboard Nav -->

    <!-- Chat -->
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#user-nav" data-bs-toggle="collapse" href="{{ url('user') }}">
        <i class="bi bi-menu-button-wide"></i><span>Chat</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="user-nav" class="nav-content collapse @if(Request::segment(2) == 'user') show @endif" data-bs-parent="#sidebar-nav">
        <li>
          <a href="{{ url('chat') }}">
            <i class="bi bi-circle"></i><span>Room chat</span>
          </a>
        </li>
      </ul>
    </li><!-- End Chat Nav -->

    <!-- Game -->
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#game-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-journal-text"></i><span>Game</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="game-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li>
          <a href="{{ url('game') }}">
            <i class="bi bi-circle"></i><span>Show game</span>
          </a>
        </li>
      </ul>
    </li><!-- End Game Nav -->

    <!-- Profile -->
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#profile-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-bar-chart"></i><span>Profile</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="profile-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li>
          <a href="{{ url('users/profile') }}">
            <i class="bi bi-circle"></i><span>My Profile</span>
          </a>
        </li>
      </ul>
    </li><!-- End Profile Nav -->

    <!-- Admin Only Sections -->
    @auth
      @if(auth()->user() && auth()->user()->roles()->where('name', 'admin')->exists())
        <!-- Users Management -->
        <li class="nav-item">
          <a class="nav-link collapsed" data-bs-target="#users-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-layout-text-window-reverse"></i><span>User</span><i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="users-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
            <li>
              <a href="{{ url('users') }}">
                <i class="bi bi-circle"></i><span>List</span>
              </a>
            </li>
          </ul>
        </li><!-- End Users Nav -->

        <!-- Roles Management -->
        <li class="nav-item">
          <a class="nav-link collapsed" data-bs-target="#roles-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-gem"></i><span>Role</span><i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="roles-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
            <li>
              <a href="{{ url('users/role') }}">
                <i class="bi bi-circle"></i><span>List Role</span>
              </a>
            </li>
            <li>
              <a href="{{ url('users/role/assign') }}">
                <i class="bi bi-circle"></i><span>Assign</span>
              </a>
            </li>
          </ul>
        </li><!-- End Roles Nav -->
      @endif
    @endauth

    <!-- Trackings -->
    <li class="nav-item">
      <a class="nav-link collapsed" href="{{ url('trackings') }}">
        <i class="bi bi-person"></i><span>Trackings</span>
      </a>
    </li><!-- End Trackings Nav -->

    <!-- Warehouse -->
    <li class="nav-item">
      <a class="nav-link collapsed" href="{{ url('warehouses') }}">
        <i class="bi bi-envelope"></i><span>Warehouse</span>
      </a>
    </li><!-- End Warehouse Nav -->

    <!-- Orders -->
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#orders-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-gem"></i><span>Orders</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="orders-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li>
          <a href="{{ url('orders') }}">
            <i class="bi bi-circle"></i><span>List Orders</span>
          </a>
        </li>
        <li>
          <a href="{{ url('orders/create') }}">
            <i class="bi bi-circle"></i><span>Create Order</span>
          </a>
        </li>
      </ul>
    </li><!-- End Orders Nav -->
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#shipper-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-gem"></i><span>Shipper Register</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="shipper-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li>
          <a href="{{ url('register-shipper') }}">
            <i class="bi bi-circle"></i><span>Register Shipper</span>
          </a>
        </li>
        <li>
          <a href="{{ url('shippers') }}">
            <i class="bi bi-circle"></i><span>Admin Shippers</span>
          </a>
        </li>
      </ul>
    </li>
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#postoffices-nav" data-bs-toggle="collapse" href="{{ url('post_offices') }}">
        <i class="bi bi-gem"></i><span>Post offices</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="postoffices-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li>
          <a href="{{ url('post_offices') }}">
            <i class="bi bi-circle"></i><span>Danh sách bưu cục</span>
          </a>
        </li>
        <li>
        <a href="{{ url('post_offices/create') }}">
        <i class="bi bi-circle"></i><span>Tạo mới bưu cục</span>
          </a>
        </li>
      </ul>
    </li>
    <!-- Products -->
    <li class="nav-item">
      <a class="nav-link collapsed" href="{{ url('products') }}">
        <i class="bi bi-file-earmark"></i><span>Product</span>
      </a>
    </li><!-- End Products Nav -->
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#search-nav" data-bs-toggle="collapse" href="{{ url('post_offices') }}">
        <i class="bi bi-gem"></i><span>Tra cứu đơn hàng</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="search-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li>
          <a href="{{ url('searchOrder') }}">
            <i class="bi bi-circle"></i><span>Tra cứu đơn hàng</span>
          </a>
        </li>
      </ul>
    </li>
  </ul>
</aside><!-- End Sidebar -->
