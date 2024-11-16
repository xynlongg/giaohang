<style>
  /* Logo styling */
.logo img {
  max-height: 40px;
  width: auto;
  object-fit: contain;
  margin-right: 10px;
}

/* Profile image styling */
.nav-profile img {
  width: 36px;
  height: 36px;
  object-fit: cover;
  border-radius: 50%;
}

.rounded-circle {
  aspect-ratio: 1/1;
  object-fit: cover;
}

/* General image rules */
img {
  max-width: 100%;
  height: auto;
}

/* Rest of your existing styles */
.logout-button {
  margin-right: 40px;
  color: blue;
  font-style: italic;
  padding: 10px 20px;
  background-color: #f8f9fa;
  border-radius: 5px;
  transition: background-color 0.3s ease, color 0.3s ease;
}

.logout-button:hover {
  background-color: #dc3545;
  color: red;
}

.dropdown-menu {
  display: none;
  position: absolute;
  right: 0;
  background-color: #fff;
  border: 1px solid #ccc;
  border-radius: 5px;
  margin-top: 10px;
  min-width: 150px;
}

.nav-item.dropdown.open .dropdown-menu {
  display: block;
}

.search-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background-color: white;
  border: 1px solid #ddd;
  border-top: none;
  max-height: 300px;
  overflow-y: auto;
  display: none;
}

.search-result-item {
  padding: 10px;
  border-bottom: 1px solid #eee;
}

.search-result-item:last-child {
  border-bottom: none;
}
</style>


<header id="header" class="header fixed-top d-flex align-items-center">
  <div class="d-flex align-items-center justify-content-between">
    <a href="{{ url('dashboard') }}" class="logo d-flex align-items-center">
      <img src="{{ asset('assets/img/logo.png') }}" alt="">
      <span class="d-none d-lg-block">Việt Long</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div><!-- End Logo -->

  <div class="search-bar">
    <form class="search-form d-flex align-items-center" id="searchForm">
      <input type="text" name="query" id="searchInput" placeholder="Tìm kiếm đơn hàng" title="Nhập mã đơn hàng">
      <button type="submit" title="Search"><i class="bi bi-search"></i></button>
    </form>
    <div class="search-results" id="searchResults"></div>
  </div><!-- End Search Bar -->

  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">

      <!-- Search Icon (Visible only on small screens) -->
      <li class="nav-item d-block d-lg-none">
        <a class="nav-link nav-icon search-bar-toggle" href="#">
          <i class="bi bi-search"></i>
        </a>
      </li><!-- End Search Icon-->

      <!-- Notification Icon -->
      <li class="nav-item dropdown">
        <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
          <i class="bi bi-bell"></i>
          <span class="badge bg-primary badge-number">4</span>
        </a><!-- End Notification Icon -->

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
          <!-- Notification Items -->
          <li class="dropdown-header">
            You have 4 new notifications
            <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
          </li>
          <li><hr class="dropdown-divider"></li>

          <!-- Add your notification items here -->

          <li class="dropdown-footer">
            <a href="#">Show all notifications</a>
          </li>
        </ul><!-- End Notification Dropdown Items -->
      </li><!-- End Notification Nav -->

      <!-- Messages Icon -->
      <li class="nav-item dropdown">
        <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
          <i class="bi bi-chat-left-text"></i>
          <span class="badge bg-success badge-number">3</span>
        </a><!-- End Messages Icon -->

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow messages">
          <!-- Message Items -->
          <li class="dropdown-header">
            You have 3 new messages
            <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
          </li>
          <li><hr class="dropdown-divider"></li>

          <!-- Add your message items here -->

          <li class="dropdown-footer">
            <a href="#">Show all messages</a>
          </li>
        </ul><!-- End Messages Dropdown Items -->
      </li><!-- End Messages Nav -->

      <!-- Profile Nav -->
      @auth
      <li class="nav-item dropdown pe-3" style="margin-right: 30px;">
        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" id="profileDropdown">
          @if (Auth::user()->avatar)
            <img src="{{ asset('uploads/' . Auth::user()->avatar) }}" alt="Profile" class="rounded-circle">
          @else
            <img src="{{ asset('assets/img/profile-img.jpg') }}" alt="Profile" class="rounded-circle">
          @endif
          <span class="d-none d-md-block dropdown-toggle ps-2">{{ Auth::user()->name }}</span>
        </a>

        <ul class="dropdown-menu">
          <li>
            <a class="dropdown-item profile-button" href="{{ route('user.profile') }}">Profile</a>
          </li>
          <li>
            <a class="dropdown-item logout-button" href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
              {{ __('Logout') }}
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
              @csrf
            </form>
          </li>
        </ul>
      </li><!-- End Profile Nav -->
      @endauth
    </ul>
  </nav><!-- End Icons Navigation -->
</header><!-- End Header -->

<script>
  document.getElementById('profileDropdown').addEventListener('click', function(event) {
    event.preventDefault();
    this.parentElement.classList.toggle('open');
  });

  document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');

    searchForm.addEventListener('submit', function(e) {
      e.preventDefault();
      performSearch();
    });

    searchInput.addEventListener('input', function() {
      if (this.value.length >= 3) {
        performSearch();
      } else {
        searchResults.style.display = 'none';
      }
    });

    function performSearch() {
      const query = searchInput.value;
      fetch(`/orders/search?query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
          displaySearchResults(data);
        })
        .catch(error => {
          console.error('Error:', error);
        });
    }

    function displaySearchResults(results) {
      searchResults.innerHTML = '';
      if (results.length > 0) {
        results.forEach(order => {
          const resultItem = document.createElement('div');
          resultItem.classList.add('search-result-item');
          resultItem.innerHTML = `
            <strong>Mã đơn hàng:</strong> ${order.tracking_number}<br>
            <strong>Người gửi:</strong> ${order.sender_name}<br>
            <strong>Người nhận:</strong> ${order.receiver_name}<br>
            <a href="/orders/${order.id}" class="btn btn-sm btn-primary">Xem chi tiết</a>
          `;
          searchResults.appendChild(resultItem);
        });
        searchResults.style.display = 'block';
      } else {
        searchResults.innerHTML = '<div class="search-result-item">Không tìm thấy đơn hàng nào</div>';
        searchResults.style.display = 'block';
      }
    }

    // Ẩn kết quả tìm kiếm khi click bên ngoài
    document.addEventListener('click', function(e) {
      if (!searchResults.contains(e.target) && e.target !== searchInput) {
        searchResults.style.display = 'none';
      }
    });
  });
</script>
