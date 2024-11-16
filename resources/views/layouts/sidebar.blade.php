<aside id="sidebar" class="sidebar">
  <ul class="sidebar-nav" id="sidebar-nav">
    <!-- Dashboard cho tất cả user -->
    <li class="nav-item">
      <a class="nav-link" href="{{ url('dashboard') }}">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
      </a>
    </li>

    <!-- PHẦN DÀNH CHO KHÁCH HÀNG -->
    @auth
      <li class="nav-heading">CHỨC NĂNG CHUNG</li>
      
      <!-- Profile -->
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#profile-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-person"></i>
          <span>Hồ sơ cá nhân</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="profile-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
          <li>
            <a href="{{ url('users/profile') }}">
              <i class="bi bi-circle"></i><span>Thông tin cá nhân</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- Đơn hàng của tôi -->
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#my-orders-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-box"></i>
          <span>Đơn hàng của tôi</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="my-orders-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
          <li>
            <a href="{{ url('my-orders') }}">
              <i class="bi bi-circle"></i><span>Danh sách đơn hàng</span>
            </a>
          </li>
          <li>
            <a href="{{ url('orders/create') }}">
              <i class="bi bi-circle"></i><span>Tạo đơn hàng</span>
            </a>
          </li>
          <li>
            <a href="{{ url('orders/import') }}">
              <i class="bi bi-circle"></i><span>Import đơn hàng</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- Đăng ký Shipper -->
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#shipper-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-truck"></i>
          <span>Đăng ký Shipper</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="shipper-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
          <li>
            <a href="{{ url('register-shipper') }}">
              <i class="bi bi-circle"></i><span>Đăng ký làm Shipper</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- Tra cứu đơn hàng -->
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#search-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-search"></i>
          <span>Tra cứu đơn hàng</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="search-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
          <li>
            <a href="{{ url('searchOrder') }}">
              <i class="bi bi-circle"></i><span>Tìm kiếm đơn hàng</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- PHẦN QUẢN TRỊ HỆ THỐNG - CHỈ ADMIN MỚI THẤY -->
      @if(auth()->user()->roles()->where('name', 'admin')->exists())
        <li class="nav-heading">QUẢN TRỊ HỆ THỐNG</li>
        
        <!-- Quản lý người dùng -->
        <li class="nav-item">
          <a class="nav-link collapsed" data-bs-target="#users-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-people"></i>
            <span>Quản lý người dùng</span>
            <i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="users-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
            <li>
              <a href="{{ url('users') }}">
                <i class="bi bi-circle"></i><span>Danh sách người dùng</span>
              </a>
            </li>
          </ul>
        </li>

        <!-- Quản lý vai trò -->
        <li class="nav-item">
          <a class="nav-link collapsed" data-bs-target="#roles-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-shield-lock"></i>
            <span>Quản lý vai trò</span>
            <i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="roles-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
            <li>
              <a href="{{ url('users/role') }}">
                <i class="bi bi-circle"></i><span>Danh sách vai trò</span>
              </a>
            </li>
            <li>
              <a href="{{ url('users/role/assign') }}">
                <i class="bi bi-circle"></i><span>Phân quyền</span>
              </a>
            </li>
          </ul>
        </li>

        <!-- Quản lý sản phẩm -->
        <li class="nav-item">
          <a class="nav-link collapsed" data-bs-target="#products-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-box-seam"></i>
            <span>Quản lý sản phẩm</span>
            <i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="products-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
            <li>
              <a href="{{ url('products') }}">
                <i class="bi bi-circle"></i><span>Danh sách sản phẩm</span>
              </a>
            </li>
            <li>
              <a href="{{ url('products/create') }}">
                <i class="bi bi-circle"></i><span>Thêm sản phẩm</span>
              </a>
            </li>
          </ul>
        </li>

        <!-- QUẢN LÝ BƯU CỤC -->
        <li class="nav-heading">QUẢN LÝ BƯU CỤC</li>

        <!-- Quản lý bưu cục -->
        <li class="nav-item">
          <a class="nav-link collapsed" data-bs-target="#post-office-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-building"></i>
            <span>Quản lý bưu cục</span>
            <i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="post-office-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
            <li>
              <a href="{{ url('post_offices') }}">
                <i class="bi bi-circle"></i><span>Danh sách bưu cục</span>
              </a>
            </li>
            <li>
              <a href="{{ url('post_offices/create') }}">
                <i class="bi bi-circle"></i><span>Thêm bưu cục mới</span>
              </a>
            </li>
          </ul>
        </li>

        <!-- Quản lý nhân viên bưu cục -->
        <li class="nav-item">
          <a class="nav-link collapsed" data-bs-target="#post-office-staff-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-people-fill"></i>
            <span>Quản lý nhân viên bưu cục</span>
            <i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="post-office-staff-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
            <li>
              <a href="{{ url('/post-office-staff') }}">
                <i class="bi bi-circle"></i><span>Danh sách nhân viên</span>
              </a>
            </li>
          </ul>
        </li>

        <!-- Quản lý đơn hàng bưu cục -->
        <li class="nav-item">
          <a class="nav-link collapsed" data-bs-target="#post-office-orders-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-box-seam"></i>
            <span>Quản lý đơn hàng bưu cục</span>
            <i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="post-office-orders-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
            <li>
              <a href="{{ url('post-office/orders/prepared') }}">
                <i class="bi bi-circle"></i><span>Đơn hàng đã đến bưu cục</span>
              </a>
            </li>
            <li>
              <a href="{{ url('post-office/orders') }}">
                <i class="bi bi-circle"></i><span>Quản lý đơn hàng</span>
              </a>
            </li>
          </ul>
        </li>

        <!-- QUẢN LÝ KHO TỔNG -->
        <li class="nav-heading">QUẢN LÝ KHO TỔNG</li>
        
        <!-- Quản lý kho -->
        <li class="nav-item">
          <a class="nav-link collapsed" data-bs-target="#warehouse-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-building-fill"></i>
            <span>Quản lý kho tổng</span>
            <i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="warehouse-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
            <li>
              <a href="{{ url('provincial-warehouses') }}">
                <i class="bi bi-circle"></i><span>Danh sách kho</span>
              </a>
            </li>
            <li>
              <a href="{{ url('provincial-warehouses/create') }}">
                <i class="bi bi-circle"></i><span>Thêm kho mới</span>
              </a>
            </li>
          </ul>
        </li>

        <!-- Quản lý nhân viên kho -->
        <li class="nav-item">
          <a class="nav-link collapsed" data-bs-target="#warehouse-staff-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-people"></i>
            <span>Quản lý nhân viên kho</span>
            <i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="warehouse-staff-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
            <li>
              <a href="{{ url('provincial-warehouses/staff') }}">
                <i class="bi bi-circle"></i><span>Phân công nhân viên</span>
              </a>
            </li>
          </ul>
        </li>

       

      @endif

      <!-- PHẦN DÀNH CHO NHÂN VIÊN BƯU CỤC -->
      @if(!auth()->user()->roles()->where('name', 'admin')->exists() && 
          auth()->user()->roles()->whereIn('name', ['post_office_staff', 'post_office_manager'])->exists())
        <li class="nav-heading">QUẢN LÝ BƯU CỤC</li>

        <!-- Quản lý đơn hàng bưu cục -->
        <li class="nav-item">
       <a class="nav-link collapsed" data-bs-target="#buucucdich-nav" data-bs-toggle="collapse" href="#">
         <i class="bi bi-x-circle"></i>
         <span>Đơn hàng giao & phân công shipper</span>
         <i class="bi bi-chevron-down ms-auto"></i>
       </a>
       <ul id="buucucdich-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
         <li>
           <a href="{{ url('post-office/receiving') }}">
             <i class="bi bi-circle"></i><span>Danh sách đơn hàng</span>
           </a>
         </li>
       </ul>
     </li>

        <li class="nav-item">
          <a class="nav-link collapsed" data-bs-target="#post-office-orders-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-box-seam"></i>
            <span>Quản lý đơn hàng</span>
            <i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="post-office-orders-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
            <li>
              <a href="{{ url('post-office/orders/prepared') }}">
                <i class="bi bi-circle"></i><span>Đơn hàng đã đến bưu cục</span>
              </a>
            </li>
            @if(auth()->user()->roles()->where('name', 'post_office_manager')->exists())
              <li>
                <a href="{{ url('post-office/orders') }}">
                  <i class="bi bi-circle"></i><span>Quản lý đơn hàng</span>
                </a>
              </li>
            @endif
          </ul>
        </li>
      @endif

      <!-- PHẦN DÀNH CHO NHÂN VIÊN KHO -->
      @if(!auth()->user()->roles()->where('name', 'admin')->exists() && 
          auth()->user()->roles()->whereIn('name', ['warehouse_staff', 'warehouse_manager'])->exists())
       <!-- Quản lý đơn hàng kho -->
       <li class="nav-item">
          <a class="nav-link collapsed" data-bs-target="#warehouse-orders-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-box-seam"></i>
            <span>Quản lý đơn hàng kho</span>
            <i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="warehouse-orders-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
            <li>
              <a href="{{ url('warehouse\orders') }}">
                <i class="bi bi-circle"></i><span>Đơn hàng trong kho</span>
              </a>
            </li>
            <li>
              <a href="{{ url('warehouse-orders/transit') }}">
                <i class="bi bi-circle"></i><span>Đơn hàng trung chuyển</span>
              </a>
            </li>
          </ul>
        </li>
          <li class="nav-item">
           <a class="nav-link collapsed" data-bs-target="#warehouse-nav" data-bs-toggle="collapse" href="#">
               <i class="bi bi-building-fill"></i>
               <span>Đơn hàng & gán nhân viên phân phối kho</span>
               <i class="bi bi-chevron-down ms-auto"></i>
           </a>
           <ul id="warehouse-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
               <li>
                   <a href="{{ url('/warehouse/orders') }}">
                       <i class="bi bi-circle"></i><span>Danh sách</span>
                   </a>
               </li>
               @if(auth()->user()->roles()->where('name', 'warehouse_manager')->exists())
                   <li>
                       <a href="{{ url('provincial-warehouses/create') }}">
                           <i class="bi bi-circle"></i><span>Thêm kho mới</span>
                       </a>
                   </li>
               @endif
           </ul>
       </li>

     @endif

     <!-- PHẦN CHỨC NĂNG CHUNG CHO TẤT CẢ -->
     <li class="nav-heading">HỆ THỐNG</li>

     <!-- Quản lý yêu cầu hủy đơn -->
     <li class="nav-item">
       <a class="nav-link collapsed" data-bs-target="#cancellation-nav" data-bs-toggle="collapse" href="#">
         <i class="bi bi-x-circle"></i>
         <span>Quản lý yêu cầu hủy</span>
         <i class="bi bi-chevron-down ms-auto"></i>
       </a>
       <ul id="cancellation-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
         <li>
           <a href="{{ url('cancellation-requests') }}">
             <i class="bi bi-circle"></i><span>Danh sách yêu cầu</span>
           </a>
         </li>
       </ul>
     </li>
     <!-- PHẦN CHỨC NĂNG CHUNG CHO TẤT CẢ -->
     

     <li class="nav-heading">NHÂN VIÊN PHÂN PHỐI </li>

    <!-- Quản lý yêu cầu hủy đơn -->
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#phanphoi-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-truck"></i>
        <span>Danh sách đơn hàng phân phối nội thành < 20</span>
        <i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="phanphoi-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li>
          <a href="{{ url('/distribution/orders') }}">
            <i class="bi bi-circle"></i><span>Danh sách đơn hàng</span>
          </a>
        </li>
      </ul>
    </li>

    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#phanphoi1-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-truck"></i>
        <span>Danh sách đơn hàng phân phối nội thành > 20, ngoại thành</span>
        <i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="phanphoi1-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li>
          <a href="{{ url('distributor/assigned-orders') }}">
            <i class="bi bi-circle"></i><span>Danh sách đơn hàng</span>
          </a>
        </li>
      </ul>
    </li>

     <!-- Quản lý Shipper -->
     @if(auth()->user()->roles()->whereIn('name', ['admin', 'post_office_manager'])->exists())
       <li class="nav-item">
         <a class="nav-link collapsed" data-bs-target="#manage-shipper-nav" data-bs-toggle="collapse" href="#">
           <i class="bi bi-bicycle"></i>
           <span>Quản lý Shipper</span>
           <i class="bi bi-chevron-down ms-auto"></i>
         </a>
         <ul id="manage-shipper-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
           <li>
             <a href="{{ url('shippers') }}">
               <i class="bi bi-circle"></i><span>Danh sách Shipper</span>
             </a>
           </li>
           <li>
             <a href="{{ url('register-shipper') }}">
               <i class="bi bi-circle"></i><span>Yêu cầu đăng ký</span>
             </a>
           </li>
         </ul>
       </li>
     @endif

     <!-- Thống kê báo cáo - Chỉ cho Admin và Manager -->
     @if(auth()->user()->roles()->whereIn('name', ['admin', 'post_office_manager', 'warehouse_manager'])->exists())
       <li class="nav-item">
         <a class="nav-link collapsed" data-bs-target="#reports-nav" data-bs-toggle="collapse" href="#">
           <i class="bi bi-graph-up"></i>
           <span>Thống kê báo cáo</span>
           <i class="bi bi-chevron-down ms-auto"></i>
         </a>
         <ul id="reports-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
           <li>
             <a href="{{ url('reports/orders') }}">
               <i class="bi bi-circle"></i><span>Báo cáo đơn hàng</span>
             </a>
           </li>
           <li>
             <a href="{{ url('reports/revenue') }}">
               <i class="bi bi-circle"></i><span>Báo cáo doanh thu</span>
             </a>
           </li>
           <li>
             <a href="{{ url('reports/performance') }}">
               <i class="bi bi-circle"></i><span>Hiệu suất vận chuyển</span>
             </a>
           </li>
         </ul>
       </li>
     @endif

     <!-- Đăng xuất -->
     <li class="nav-item mt-5">
       <form method="POST" action="{{ route('logout') }}" class="d-inline">
         @csrf
         <button type="submit" class="nav-link collapsed border-0 bg-transparent w-100 text-start">
           <i class="bi bi-box-arrow-right text-danger"></i>
           <span class="text-danger">Đăng xuất</span>
         </button>
       </form>
     </li>

   @endauth

 </ul>
</aside>