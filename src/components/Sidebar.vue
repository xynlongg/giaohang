<template>
  <div>
    <div class="sidebar-toggle" @click="toggleSidebar" v-if="!isOpen">
      <i class="fas fa-bars"></i>
    </div>
    <aside class="sidebar" :class="{ 'open': isOpen }" @click.self="closeSidebar">
      <button @click="closeSidebar" class="close-btn">&times;</button>
      <div class="logo">
        <img src="/images/logo1.png" alt="Shipper Logo" />
      </div>
      <nav>
        <ul>
          <li><a href="#"><i class="fas fa-home"></i> Trang chủ</a></li>
          <li>
            <a @click="toggleSubMenu('orders')">
              <i class="fas fa-box"></i> Đơn hàng
              <i :class="['fas', {'fa-chevron-down': !subMenus.orders, 'fa-chevron-up': subMenus.orders}]"></i>
            </a>
            <ul v-if="subMenus.orders" class="submenu">
              <li><a :href="'/orders'"><i class="fas fa-list"></i> Danh sách đơn hàng</a></li>
              <li><a href="/orders/pickuporders"><i class="fas fa-plus"></i> Quản lý đơn hàng đã lấy </a></li>
              <li><a href="/orders/deliveryorders"><i class="fas fa-truck"></i> Đơn hàng đang giao</a></li>
            </ul>
          </li>
          <li>
            <a @click="toggleSubMenu('Profile')">
              <i class="fas fa-box"></i> Profile
              <i :class="['fas', {'fa-chevron-down': !subMenus.orders, 'fa-chevron-up': subMenus.orders}]"></i>
            </a>
            <ul v-if="subMenus.Profile" class="submenu">
              <li><a :href="'/profile'"><i class="fas fa-list"></i> Cài đặt</a></li>
            </ul>
          </li>           
        </ul>
      </nav>
    </aside>
  </div>
</template>

<script>
import { ref } from 'vue'

export default {
  name: 'Sidebar',
  props: ['isOpen'],
  emits: ['update:isOpen'],
  setup(props, { emit }) {
    const subMenus = ref({
      orders: false,
      locations: false,
      history: false,
      settings: false
    })

    const toggleSubMenu = (menu) => {
      subMenus.value[menu] = !subMenus.value[menu]
    }

    const toggleSidebar = () => {
      emit('update:isOpen', true)
    }

    const closeSidebar = () => {
      emit('update:isOpen', false)
    }

    return { subMenus, toggleSubMenu, toggleSidebar, closeSidebar }
  }
}
</script>

<style scoped>
/* Icon menu hình vuông với radius 15px */
.sidebar-toggle {
  display: none;
  position: absolute; /* Định vị tuyệt đối để nằm trên header */
  top: 15px; /* Khoảng cách từ phía trên của header */
  left: 20px; /* Đặt icon menu ở góc trái */
  z-index: 1001;
  cursor: pointer;
  background-color: var(--bg-primary);
  color: var(--text-primary);
  padding: 10px;
  border-radius: 15px; /* Làm cho nút icon menu bo tròn góc với radius 15px */
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: background-color 0.3s;
}

.sidebar-toggle:hover {
  background-color: var(--bg-secondary);
}

.sidebar-toggle i {
  font-size: 24px;
}

.sidebar {
  width: 280px; /* Tăng chiều rộng từ 250px lên 280px */
  background-color: var(--bg-primary);
  color: var(--text-primary);
  padding: 20px;
  height: 100vh;
  overflow-y: auto;
  transition: all 0.3s ease-in-out;
  box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
  position: relative;
  border-radius: 0 10px 10px 0;
}

.sidebar.open {
  left: 0;
  transition: left 0.3s ease-in-out;
}

.logo img {
  width: 100%;
  max-width: 150px;
  margin-bottom: 20px;
  transition: opacity 0.3s;
}

.sidebar nav ul {
  list-style-type: none;
  padding: 0;
}

.sidebar nav ul li {
  margin-bottom: 12px;
  transition: transform 0.3s, background-color 0.3s;
}

.sidebar nav ul li:hover {
  transform: scale(1.05);
}

.sidebar nav ul li a {
  color: var(--text-primary);
  text-decoration: none;
  display: flex;
  align-items: center;
  justify-content: flex-start; /* Đảm bảo icon và chữ luôn sát nhau */
  padding: 12px 10px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
}

.sidebar nav ul li a:hover {
  background-color: var(--bg-secondary);
}

.sidebar nav ul li a i:first-child {
  margin-right: 5px; /* Giảm khoảng cách giữa icon và chữ */
}

.submenu {
  margin-left: 20px;
  margin-top: 5px;
  transition: max-height 0.3s ease-in-out;
}

.submenu li a {
  padding: 8px 10px;
  display: flex;
  align-items: center;
}

.close-btn {
  display: none;
  position: absolute;
  top: 10px;
  right: 10px;
  background: none;
  border: none;
  font-size: 24px;
  color: var(--text-primary);
  cursor: pointer;
  transition: transform 0.3s ease;
}

.close-btn:hover {
  transform: rotate(90deg);
}

@media (max-width: 768px) {
  .sidebar-toggle {
    display: block; /* Hiển thị icon menu trên mobile */
    position: absolute;
    top: 10px; /* Đảm bảo icon menu nằm trên header */
    left: 20px; /* Đặt icon menu ở góc trái */
    z-index: 1001;
  }

  .sidebar {
    position: fixed;
    top: 0;
    left: -280px; /* Điều chỉnh vị trí khởi đầu với chiều rộng mới */
    bottom: 0;
    z-index: 1000;
  }

  .sidebar.open {
    left: 0;
  }

  .close-btn {
    display: block;
  }
}
</style>
