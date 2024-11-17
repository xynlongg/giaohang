<template>
  <div class="container-fluid vh-100 d-flex align-items-center justify-content-center">
    <div class="row w-100 h-auto">
      <!-- Form đăng nhập (50%) -->
      <div class="col-md-6 d-flex align-items-center justify-content-end bg-light p-4">
        <div class="card border-0 shadow-sm p-4" style="width: 100%; max-width: 400px;">
          <div class="card-body">
            <h2 class="card-title text-center mb-4 text-primary">Đăng nhập Shipper</h2>
            <form @submit.prevent="login">
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" v-model="email" placeholder="Nhập email" required>
              </div>
              <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu</label>
                <input type="password" class="form-control" id="password" v-model="password" placeholder="Nhập mật khẩu" required>
              </div>
              <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" v-model="remember">
                <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
              </div>
              <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary btn-lg">Đăng nhập</button>
              </div>
            </form>
            <div class="text-center">
              <a href="#" class="text-decoration-none" @click.prevent="goToForgotPassword">Quên mật khẩu?</a>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Hình ảnh công ty (50%) -->
      <div class="col-md-6 d-flex align-items-center justify-content-start p-0">
        <div class="text-center" style="width: 100%; max-width: 400px;">
          <img src="/images/logo1.png"  alt="Logo Công ty" class="img-fluid mb-4 w-100" style="max-width: 250px; border-radius: 8px;">
          <h2 class="fw-bold text-dark">LongXyn Delivery</h2>
          <p class="lead text-muted">Long Giao Hàng Tiết kiệm</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useShipperStore } from '../stores/shipper'
import { useToast } from "vue-toastification";

const router = useRouter()
const shipperStore = useShipperStore()
const toast = useToast()

const email = ref('')
const password = ref('')
const remember = ref(false)

const login = async () => {
  try {
    const success = await shipperStore.login(email.value, password.value)
    if (success) {
      console.log('Login successful')
      router.push('/dashboard') // hoặc trang chính sau khi đăng nhập
    } else {
      console.log('Login failed')
      alert('Đăng nhập thất bại. Vui lòng kiểm tra lại thông tin.')
    }
  } catch (error) {
    console.error('Login error:', error)
    if (error.response) {
      toast.error(error.response.data.message || 'Đăng nhập thất bại')
    } else if (error.request) {
      toast.error('Không thể kết nối đến server. Vui lòng thử lại sau.')
    } else {
      toast.error('Đã xảy ra lỗi. Vui lòng thử lại.')
    }
  }
}

</script>