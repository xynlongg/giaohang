import { createApp } from 'vue'
import { createPinia } from 'pinia'
import axios from 'axios'
import App from './App.vue'
import router from './router'
import './index.css'
import MainLayout from './layouts/MainLayout.vue'
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap'
import Toast from "vue-toastification"
import "vue-toastification/dist/index.css"
import { useShipperStore } from '@/stores/shipper'
import Echo from 'laravel-echo'
import io from 'socket.io-client'

axios.defaults.baseURL = 'http://localhost:8000';
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Lấy CSRF token từ meta tag
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

const bearerToken = localStorage.getItem('token');
if (bearerToken) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${bearerToken}`;
}

// Cấu hình interceptors cho axios
axios.interceptors.request.use(config => {
  console.log('Request:', config.method.toUpperCase(), config.url)
  return config
}, error => {
  console.error('Request error:', error)
  return Promise.reject(error)
})

axios.interceptors.response.use(
  response => {
    console.log('Response:', response.status, response.config.url)
    return response
  },
  error => {
    console.error('Response error:', error.response ? error.response.status : error)
    if (error.response && error.response.status === 401) {
      const shipperStore = useShipperStore()
      shipperStore.clearShipperData()
      router.push('/login')
    }
    return Promise.reject(error)
  }
)

// Khởi tạo Echo
window.io = io
window.Echo = new Echo({
  broadcaster: 'socket.io',
  host: window.location.hostname + ':6001',
  transports: ['websocket', 'polling'],
  reconnectionAttempts: 5,
  reconnectionDelay: 3000,
});

// Lắng nghe sự kiện 'connect'
window.Echo.connector.socket.on('connect', () => {
  console.log('Đã kết nối lại thành công với Laravel Echo Server');
  const shipperStore = useShipperStore();
  shipperStore.initializeRealtime();
});
window.Echo.connector.socket.on('disconnect', (reason) => {
  console.log('Mất kết nối với Laravel Echo Server. Lý do:', reason);
});

window.Echo.connector.socket.on('reconnecting', (attemptNumber) => {
  console.log(`Đang cố gắng kết nối lại lần thứ ${attemptNumber}`);
});

window.Echo.connector.socket.on('reconnect_failed', () => {
  console.error('Không thể kết nối lại sau nhiều lần thử');
});

window.Echo.connector.socket.on('connect_error', (error) => {
  console.error('Lỗi khi cố gắng kết nối:', error);
});

window.Echo.connector.socket.on('error', (error) => {
  console.error('Lỗi Socket.IO:', error);
});

const app = createApp(App)
const pinia = createPinia()

app.component('MainLayout', MainLayout)
app.use(Toast)
app.use(createPinia())
app.use(router)
app.config.globalProperties.$axios = axios

app.mount('#app')

// Khởi tạo realtime sau khi ứng dụng đã được mount
const shipperStore = useShipperStore(pinia)
shipperStore.initializeRealtime()