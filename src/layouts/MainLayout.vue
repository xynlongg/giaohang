<template>
  <div class="app-container" :class="[theme, { 'sidebar-open': isSidebarOpen }]">
    <Sidebar v-model:isOpen="isSidebarOpen" />
    <div class="main-content">
      <Header @toggle-sidebar="toggleSidebar" @toggle-theme="toggleTheme" />
      <main>
        <router-view></router-view>
      </main>
    </div>
  </div>
</template>

<script>
import { ref, provide } from 'vue'
import { useTheme } from '../composables/useTheme'
import Header from '@/components/Header.vue'
import Sidebar from '@/components/Sidebar.vue'

export default {
  components: {
    Header,
    Sidebar,
  },
  setup() {
    const { theme, toggleTheme } = useTheme()
    const isSidebarOpen = ref(false)

    const toggleSidebar = () => {
      isSidebarOpen.value = !isSidebarOpen.value
    }

    const closeSidebar = () => {
      isSidebarOpen.value = false
    }

    provide('theme', theme)
    provide('toggleTheme', toggleTheme)

    return { 
      theme, 
      toggleTheme, 
      isSidebarOpen, 
      toggleSidebar,
      closeSidebar
    }
  }
}
</script>

<style>
.app-container {
  display: flex;
  height: 100vh;
  overflow: hidden;
}

.main-content {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  overflow-x: hidden;
}

main {
  flex-grow: 1;
  padding: 20px;
  overflow-y: auto;
}

/* Light theme */
.light {
  --bg-primary: #ffffff;
  --bg-secondary: #f4f6f8;
  --text-primary: #333333;
  --text-secondary: #666666;
  --accent-color: #4CAF50;
}

/* Dark theme */
.dark {
  --bg-primary: #1e2a3a;
  --bg-secondary: #2c3e50;
  --text-primary: #ffffff;
  --text-secondary: #b0bec5;
  --accent-color: #66BB6A;
}

.app-container {
  background-color: var(--bg-secondary);
  color: var(--text-primary);
}

@media (max-width: 768px) {
  .app-container {
    position: relative;
  }

  .sidebar-open .main-content {
    transform: translateX(250px);
  }
}
</style>