<template>
  <div class="order-detail-page" :class="theme">
    <div class="page-header">
      <h1>Chi tiết đơn hàng #{{ order.tracking_number }}</h1>
    </div>

    <div class="row">
      <!-- Cột thông tin bên trái -->
      <div class="col-md-6">
        <!-- Thông tin chung -->
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title">Thông tin chung</h5>
            <div class="info-item">
              <strong>Trạng thái:</strong> <span :class="getStatusClass(order.status)">{{ getStatusLabel(order.status) }}</span>
            </div>
            <div class="info-item">
              <strong>Ngày tạo:</strong> {{ formatDate(order.created_at) }}
            </div>
          </div>
        </div>

        <!-- Thông tin người gửi -->
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title">Thông tin người gửi</h5>
            <div class="info-item">
              <strong>Tên:</strong> {{ order.sender_name }}
            </div>
            <div class="info-item">
              <strong>Địa chỉ:</strong> {{ order.sender_address }}
            </div>
            <div class="info-item">
              <strong>Số điện thoại:</strong> {{ order.sender_phone }}
            </div>
          </div>
        </div>

        <!-- Thông tin người nhận -->
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title">Thông tin người nhận</h5>
            <div class="info-item">
              <strong>Tên:</strong> {{ order.receiver_name }}
            </div>
            <div class="info-item">
              <strong>Địa chỉ:</strong> {{ order.receiver_address }}
            </div>
            <div class="info-item">
              <strong>Số điện thoại:</strong> {{ order.receiver_phone }}
            </div>
          </div>
        </div>

        <!-- Hình ảnh đơn hàng -->
        <div v-if="showOrderImage" class="card mb-4">
          <div class="card-body">
            <h5 class="card-title">Hình ảnh đơn hàng</h5>
            <div class="order-image-container">
              <img v-if="latestImageUrl" :src="latestImageUrl" alt="Hình ảnh đơn hàng" class="rounded order-img" @error="handleImageError">
            </div>
            <p v-if="imageError" class="text-danger mt-2">{{ imageError }}</p>
          </div>
        </div>
      </div>

      <!-- Cột thông tin bên phải -->
      <div class="col-md-6">
        <!-- Bản đồ -->
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title">Bản đồ - địa chỉ lấy hàng</h5>
            <div id="map" style="width: 100%; height: 400px;"></div>
          </div>
        </div>

        <!-- Thông tin giao nhận -->
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title">Thông tin lấy hàng</h5>
            <div class="info-item">
              <strong>Khoảng cách:</strong> <span id="distance">{{ distance }}</span>
            </div>
            <div class="info-item">
              <strong>Thời gian:</strong> <span id="duration">{{ duration }}</span>
            </div>
          </div>
        </div>

        <!-- Cập nhật trạng thái -->
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title">Cập nhật trạng thái</h5>
            <div class="form-group">
              <select v-model="newStatus" class="form-select" @change="handleStatusChange">
                <option value="">Chọn trạng thái</option>
                <option value="picked_up">Đã lấy hàng</option>
                <option value="in_transit">Đang vận chuyển</option>
                <option value="delivered">Đã giao hàng thành công</option>
                <option value="failed_pickup">Không lấy được hàng</option>
                <option value="failed_delivery">Không giao được hàng</option>
              </select>
            </div>
            <div v-if="showReason" class="form-group mt-3">
              <select v-model="selectedReason" class="form-select" @change="handleReasonChange">
                <option value="">Chọn lý do</option>
                <option v-for="reason in reasonOptions" :key="reason.value" :value="reason.value">
                  {{ reason.label }}
                </option>
              </select>
            </div>
            <div v-if="showCustomReason" class="form-group mt-3">
              <input v-model="customReason" type="text" class="form-control" placeholder="Nhập lý do khác">
            </div>
            <div v-if="showImageUpload" class="form-group mt-3">
              <label for="imageUpload" class="form-label">Tải lên hình ảnh đơn hàng</label>
              <input id="imageUpload" type="file" @change="handleFileUpload" class="form-control" accept="image/*">
            </div>
            <button @click="updateStatus" class="btn btn-primary btn-block mt-3">Cập nhật</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, inject } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import mapboxgl from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'
import MapboxDirections from '@mapbox/mapbox-gl-directions/dist/mapbox-gl-directions'
import '@mapbox/mapbox-gl-directions/dist/mapbox-gl-directions.css'

// Router và Toast
const route = useRoute()
const router = useRouter()
const toast = useToast()

// Reactive refs
const order = ref({})
const postOffice = ref(null)
const newStatus = ref('')
const selectedReason = ref('')
const customReason = ref('')
const file = ref(null)
const theme = inject('theme')
const imageError = ref(null)
const distance = ref('')
const duration = ref('')

// Map variables
let map, directions, postOfficeMarker, senderMarker

// Reason options
const reasonOptions = [
  { value: 'address_not_found', label: 'Không tìm thấy địa chỉ' },
  { value: 'customer_not_available', label: 'Khách hàng không có mặt' },
  { value: 'customer_refused', label: 'Khách hàng từ chối nhận hàng' },
  { value: 'other', label: 'Lý do khác' },
]

// Computed properties
const storageBaseUrl = import.meta.env.VITE_STORAGE_URL || '/storage/order_status_images/'

const showReason = computed(() => ['failed_pickup', 'failed_delivery'].includes(newStatus.value))
const showCustomReason = computed(() => selectedReason.value === 'other')
const showImageUpload = computed(() => ['picked_up', 'delivered'].includes(newStatus.value))
const showOrderImage = computed(() => 
  order.value.status === 'picked_up' || order.value.status === 'delivered'
)
const latestImageUrl = computed(() => {
  if (order.value?.statusUpdates?.length > 0) {
    const updatesWithImages = order.value.statusUpdates.filter(update => update.image)
    const latestUpdate = updatesWithImages.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0]
    return latestUpdate ? getFullImageUrl(latestUpdate.image) : null
  }
  return null
})

// Helper functions
const formatDate = (dateString) => new Date(dateString).toLocaleDateString('vi-VN')

const getStatusClass = (status) => {
  const statusClasses = {
    'picked_up': 'status-picked-up',
    'in_transit': 'status-in-transit',
    'delivered': 'status-delivered',
    'failed_pickup': 'status-failed',
    'failed_delivery': 'status-failed'
  }
  return statusClasses[status] || ''
}

const getStatusLabel = (status) => {
  const statusLabels = {
    'picked_up': 'Đã lấy hàng',
    'in_transit': 'Đang vận chuyển',
    'delivered': 'Đã giao hàng',
    'failed_pickup': 'Không lấy được hàng',
    'failed_delivery': 'Không giao được hàng'
  }
  return statusLabels[status] || status
}

const getFullImageUrl = (imagePath) => {
  if (!imagePath) return null
  if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
    return imagePath
  }
  return `${storageBaseUrl}${imagePath}`
}

const handleImageError = (event) => {
  console.error('Image loading error:', event)
  console.error('Failed image src:', event.target.src)
  imageError.value = `Không thể tải hình ảnh. URL: ${event.target.src}`
}

const parseCoordinates = (coordinates) => {
  if (Array.isArray(coordinates) && coordinates.length === 2) {
    return coordinates.map(Number);
  }
  
  if (typeof coordinates === 'string') {
    try {
      const parsed = JSON.parse(coordinates);
      if (Array.isArray(parsed) && parsed.length === 2) {
        return parsed.map(Number);
      }
    } catch (error) {
      console.error('Error parsing coordinates string:', error);
    }
    
    const parts = coordinates.split(',').map(part => parseFloat(part.trim()));
    if (parts.length === 2 && !isNaN(parts[0]) && !isNaN(parts[1])) {
      return parts;
    }
  }
  
  if (typeof coordinates === 'object' && 'lat' in coordinates && 'lng' in coordinates) {
    return [Number(coordinates.lng), Number(coordinates.lat)];
  }
  
  console.error('Invalid coordinates format:', coordinates);
  return null;
}

// Load order detail
const loadOrderDetail = async () => {
  try {
    const response = await axios.get(`/api/shipper/orders/${route.params.id}`)
    order.value = response.data
    
    console.log('Order data:', order.value)
    console.log('Status updates in order data:', order.value.statusUpdates)

    if (order.value.statusUpdates) {
      console.log('Status updates with images:', order.value.statusUpdates.filter(update => update.image))
    }

    await loadPostOfficeInfo(route.params.id)

    newStatus.value = order.value.status

    console.log('Status updates:', order.value.statusUpdates)
    console.log('Latest image URL:', latestImageUrl.value)

    if (postOffice.value && postOffice.value.coordinates) {
      initializeMap()
    } else {
      console.error('Post office data is missing or incomplete', postOffice.value)
      toast.error('Không thể tải thông tin bưu cục. Bản đồ sẽ không được hiển thị.')
    }
  } catch (error) {
    console.error('Error loading order detail:', error)
    if (error.response?.status === 401) {
      router.push('/login')
    } else {
      toast.error(error.response?.data?.message || 'Không thể tải thông tin đơn hàng')
    }
  }
}

// Load post office information
const loadPostOfficeInfo = async (orderId) => {
  try {
    const token = localStorage.getItem('token')
    const response = await axios.get(`/api/shipper/orders/${orderId}/post-office`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })

    console.log('Post office response:', response.data)

    if (response.data && typeof response.data === 'object' && !response.data.error) {
      postOffice.value = response.data
      console.log('Post office data set:', postOffice.value)
    } else {
      throw new Error('Invalid post office data received')
    }
  } catch (error) {
    console.error('Error loading post office info:', error)
    if (error.response) {
      console.error('Error response:', error.response.data)
      console.error('Error status:', error.response.status)
      console.error('Error headers:', error.response.headers)
    }
    toast.error('Không thể tải thông tin bưu cục')
    throw error
  }
}

// Initialize map
const initializeMap = () => {
  if (!postOffice.value || !postOffice.value.coordinates) {
    console.error('Post office data or coordinates are missing', postOffice.value)
    toast.error('Thông tin bưu cục không đầy đủ. Bản đồ sẽ không được hiển thị.')
    return
  }

  const postOfficeCoordinates = parseCoordinates(postOffice.value.coordinates)
  if (!postOfficeCoordinates) {
    console.error('Invalid post office coordinates:', postOffice.value.coordinates)
    toast.error('Tọa độ bưu cục không hợp lệ. Bản đồ sẽ không được hiển thị.')
    return
  }

  mapboxgl.accessToken = import.meta.env.VITE_MAPBOX_ACCESS_TOKEN
  map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/streets-v11',
    center: postOfficeCoordinates,
    zoom: 12
  })

  directions = new MapboxDirections({
    accessToken: mapboxgl.accessToken,
    unit: 'metric',
    profile: 'mapbox/driving',
    alternatives: false,
    geometries: 'geojson',
    controls: { inputs: false, instructions: false },
    flyTo: false
  })

  map.addControl(directions, 'top-left')

  map.on('load', () => {
    updateRoute()
    addMarkers()
    fitMapToMarkers()
  })

  directions.on('route', (e) => {
    if (e.route && e.route[0]) {
      distance.value = `${(e.route[0].distance / 1000).toFixed(2)} km`
      duration.value = `${Math.round(e.route[0].duration / 60)} phút`
    }
  })
}

const updateRoute = () => {
  const postOfficeCoordinates = parseCoordinates(postOffice.value.coordinates)
  const senderCoordinates = parseCoordinates(order.value.sender_coordinates)
  
  if (!postOfficeCoordinates || !senderCoordinates) {
    console.error('Missing coordinates for route update', {
      postOffice: postOffice.value.coordinates,
      sender: order.value.sender_coordinates
    })
    return
  }
  
  directions.setOrigin(postOfficeCoordinates)
  directions.setDestination(senderCoordinates)
}

const addMarkers = () => {
  const postOfficeCoordinates = parseCoordinates(postOffice.value.coordinates)
  const senderCoordinates = parseCoordinates(order.value.sender_coordinates)
  
  if (!postOfficeCoordinates || !senderCoordinates) {
    console.error('Missing coordinates for adding markers', {
      postOffice: postOffice.value.coordinates,
      sender: order.value.sender_coordinates
    })
    return
  }
  
  postOfficeMarker = new mapboxgl.Marker({ color: "#00FF00" })
    .setLngLat(postOfficeCoordinates)
    .setPopup(new mapboxgl.Popup().setHTML(`<h3>Bưu cục</h3><p>${postOffice.value.name}</p><p>${postOffice.value.address}</p>`))
    .addTo(map)

  senderMarker = new mapboxgl.Marker({ color: "#f30" })
    .setLngLat(senderCoordinates)
    .setPopup(new mapboxgl.Popup().setHTML(`<h3>Người gửi</h3><p>${order.value.sender_name}</p><p>${order.value.sender_address}</p>`))
    .addTo(map)
}

const fitMapToMarkers = () => {
  const postOfficeCoordinates = parseCoordinates(postOffice.value.coordinates)
  const senderCoordinates = parseCoordinates(order.value.sender_coordinates)
  
  if (!postOfficeCoordinates || !senderCoordinates) {
    console.error('Missing coordinates for fitting map to markers', {
      postOffice: postOffice.value.coordinates,
      sender: order.value.sender_coordinates
    })
    return
  }
  
  const bounds = new mapboxgl.LngLatBounds()
  bounds.extend(postOfficeCoordinates)
  bounds.extend(senderCoordinates)
  map.fitBounds(bounds, { padding: 50 })
}

// Event handlers
const handleStatusChange = () => {
  selectedReason.value = ''
  customReason.value = ''
  if (newStatus.value !== 'delivered' && newStatus.value !== 'picked_up') {
    file.value = null
  }
  console.log('New status selected:', newStatus.value)
}

const handleReasonChange = () => {
  if (selectedReason.value !== 'other') {
    customReason.value = ''
  }
  console.log('Reason selected:', selectedReason.value)
}

const handleFileUpload = (event) => {
  file.value = event.target.files[0]
  console.log('File selected:', file.value)
}

// Update order status
const updateStatus = async () => {
  try {
    if (!newStatus.value) {
      throw new Error('Vui lòng chọn trạng thái mới')
    }

    const formData = new FormData()
    formData.append('status', newStatus.value)
    formData.append('reason', selectedReason.value)
    formData.append('custom_reason', customReason.value)

    if (file.value && showImageUpload.value) {
      formData.append('image', file.value)
      console.log('Image appended to form data')
    }

    console.log('Sending update request with data:', {
      status: newStatus.value,
      reason: selectedReason.value,
      custom_reason: customReason.value,
      has_image: !!file.value
    })

    const orderId = order.value.id
    const response = await axios.post(`/api/shipper/orders/${orderId}/status`, formData, {
      headers: { 
        'Content-Type': 'multipart/form-data',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
    
    console.log('Update response:', response.data)

    // Cập nhật thông tin đơn hàng
    const updatedOrderResponse = await axios.get(`/api/shipper/orders/${orderId}`)
    order.value = updatedOrderResponse.data
    console.log('Updated order status updates:', order.value.statusUpdates)
    toast.success('Cập nhật trạng thái thành công')

    // Reload post office info after status update
    try {
      await loadPostOfficeInfo(orderId)
    } catch (error) {
      console.error('Error loading post office info after status update:', error)
      toast.error('Không thể tải thông tin bưu cục sau khi cập nhật trạng thái')
    }

    // Reset form
    newStatus.value = order.value.status
    selectedReason.value = ''
    customReason.value = ''
    file.value = null

    // Refresh map only if post office info is available
    if (postOffice.value && postOffice.value.coordinates) {
      updateRoute()
      addMarkers()
      fitMapToMarkers()
    }

  } catch (error) {
    console.error('Error updating order status:', error)
    if (error.response) {
      console.error('Response data:', error.response.data)
      console.error('Response status:', error.response.status)
      console.error('Response headers:', error.response.headers)
      
      if (error.response.data.errors) {
        Object.values(error.response.data.errors).forEach(errors => {
          errors.forEach(errorMsg => toast.error(errorMsg))
        })
      } else {
        toast.error(error.response.data.message || 'Không thể cập nhật trạng thái đơn hàng')
      }
    } else {
      toast.error(error.message || 'Không thể cập nhật trạng thái đơn hàng')
    }
  }
}

// Thiết lập interceptor cho Axios
axios.interceptors.request.use(
  config => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`
    }
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      toast.error('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.')
      router.push('/login')
    }
    return Promise.reject(error)
  }
)

// Lifecycle hooks
onMounted(() => {
  loadOrderDetail()
})

// Cleanup function
onUnmounted(() => {
  if (map) {
    map.remove()
  }
})
</script>

<style scoped>
.order-image-container {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  margin-bottom: 1rem;
}

.order-img {
  width: 200px;
  height: 200px;
  object-fit: cover;
  border: 4px solid var(--bg-secondary);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.order-detail-page {
  padding: 30px;
  background-color: var(--bg-primary);
  color: var(--text-primary);
  min-height: calc(100vh - 60px);
}

.page-header {
  margin-bottom: 30px;
}

h1 {
  color: var(--text-primary);
  font-size: 28px;
}

.card {
  background-color: var(--bg-secondary);
  color: var(--text-primary);
  border-color: var(--border-color);
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
}

.card-title {
  color: var(--text-primary);
  font-size: 20px;
  margin-bottom: 15px;
}

.info-item {
  margin-bottom: 10px;
}

.form-select, .form-control {
  background-color: var(--bg-secondary);
  color: var(--text-primary);
  border-color: var(--border-color);
  border-radius: 4px;
}

.btn-primary {
  background-color: var(--accent-color);
  border-color: var(--accent-color);
  color: var(--text-dark);
  border-radius: 4px;
  transition: background-color 0.3s;
}

.btn-primary:hover {
  background-color: darken(var(--accent-color), 10%);
  border-color: darken(var(--accent-color), 10%);
}

.status-picked-up {
  color: var(--warning-color);
}

.status-in-transit {
  color: var(--info-color);
}

.status-delivered {
  color: var(--success-color);
}

.status-failed {
  color: var(--danger-color);
}

.img-fluid {
  max-width: 100%;
  height: auto;
  border-radius: 4px;
}

.list-group-item {
  background-color: var(--bg-secondary);
  color: var(--text-primary);
  border-color: var(--border-color);
}
</style>