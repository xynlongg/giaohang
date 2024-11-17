<template>
  <header class="header">
    <div class="search-bar">
      <input type="text" placeholder="Tìm kiếm đơn hàng..." />
      <button><i class="fas fa-search"></i></button>
    </div>
    <div class="user-info">
      <button @click="toggleTheme" class="theme-toggle" :title="theme === 'light' ? 'Chuyển sang chế độ tối' : 'Chuyển sang chế độ sáng'">
        <i :class="['fas', theme === 'light' ? 'fa-moon' : 'fa-sun']"></i>
        <span class="toggle-text">{{ theme === 'light' ? 'Chế độ tối' : 'Chế độ sáng' }}</span>
      </button>
      <template v-if="isAuthenticated">
        <span class="user-name">{{ shipperName }}</span>
        <img :src="shipperAvatar" alt="User Avatar" class="avatar" />
      </template>
      <template v-if="isAuthenticated">
        <button @click="logout" class="logout-button">Đăng xuất</button>
      </template>
      <template v-else>
        <router-link to="/login" class="login-link">Đăng nhập</router-link>
      </template>
    </div>
  </header>
</template>
<script>
import { inject, computed } from 'vue'
import { useShipperStore } from '../stores/shipper'
import { useRouter } from 'vue-router'
import { useToast } from "vue-toastification";


export default {
  name: 'Header',
  setup() {
    const shipperStore = useShipperStore()
    const theme = inject('theme')
    const toggleTheme = inject('toggleTheme')
    const router = useRouter()
    const toast = useToast();


    const isAuthenticated = computed(() => shipperStore.isAuthenticated)
    const shipperName = computed(() => shipperStore.getShipperName)
    const shipperAvatar = computed(() => shipperStore.getShipperAvatar)

    const logout = async () => {
      try {
        await shipperStore.logout();
        router.push('/login');
        toast.success('Logged out successfully');
      } catch (error) {
        console.error('Logout error:', error);
        if (error.response && error.response.status === 401) {
          // If unauthorized, redirect to login anyway
          router.push('/login');
        } else {
          toast.error('An error occurred during logout. Please try again.');
        }
      }
    };


    return { 
      theme, 
      toggleTheme,
      isAuthenticated,
      shipperName,
      shipperAvatar,
      logout
    }
  }
}
</script>
<style scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  background-color: var(--bg-primary);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.logo img {
  height: 40px;
  width: auto;
  object-fit: contain;
}

.search-bar {
  display: flex;
  flex-grow: 1;
  max-width: 400px;
  margin: 0 20px;
}

.search-bar input {
  flex-grow: 1;
  padding: 10px;
  border: 1px solid var(--text-secondary);
  border-radius: 4px 0 0 4px;
  background-color: var(--bg-secondary);
  color: var(--text-primary);
}

.search-bar button {
  padding: 10px 15px;
  background-color: var(--accent-color);
  color: white;
  border: none;
  border-radius: 0 4px 4px 0;
  cursor: pointer;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 15px; /* Thêm khoảng cách đều giữa các phần tử */
}

.theme-toggle {
  display: flex;
  align-items: center;
  background-color: var(--bg-secondary);
  border: 1px solid var(--text-secondary);
  color: var(--text-primary);
  font-size: 1em;
  cursor: pointer;
  padding: 8px 12px;
  border-radius: 20px;
  transition: all 0.3s ease;
}

.theme-toggle:hover {
  background-color: var(--accent-color);
  color: white;
}

.theme-toggle i {
  margin-right: 8px;
}

.user-name {
  margin-right: 10px;
  white-space: nowrap;
}

/* Cải thiện style cho avatar */
.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover; /* Đảm bảo ảnh không bị méo */
  aspect-ratio: 1/1; /* Đảm bảo tỷ lệ khung hình vuông */
  border: 2px solid var(--accent-color); /* Thêm viền để avatar nổi bật hơn */
  background-color: var(--bg-secondary); /* Màu nền khi ảnh chưa tải xong */
}

.logout-button {
  padding: 8px 12px;
  background-color: var(--accent-color);
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.3s ease;
  white-space: nowrap;
}

.logout-button:hover {
  background-color: var(--accent-color-dark);
}

/* Responsive styles */
@media (max-width: 768px) {
  .header {
    flex-wrap: wrap;
    justify-content: center;
    padding: 10px;
  }

  .logo {
    order: 1;
    width: 100%;
    text-align: center;
    margin-bottom: 10px;
  }

  .search-bar {
    order: 3;
    width: 100%;
    max-width: none;
    margin: 10px 0;
  }

  .user-info {
    order: 2;
    width: auto; /* Thay đổi từ 100% thành auto */
    justify-content: center;
    flex-wrap: nowrap; /* Ngăn các phần tử bị xuống dòng */
  }

  .theme-toggle {
    padding: 8px;
  }

  .theme-toggle .toggle-text {
    display: none;
  }

  .user-name {
    display: none;
  }

  .avatar {
    width: 32px; /* Giảm kích thước avatar trên mobile */
    height: 32px;
  }
}
</style>