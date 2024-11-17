<template>
    <div class="order-detail-page" :class="theme">
      <div class="page-header">
        <h1>Chi tiết đơn giao hàng #{{ order.tracking_number }}</h1>
      </div>
  
      <div v-if="loading" class="text-center">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Đang tải...</span>
      </div>
    </div>

    <div v-else-if="error" class="alert alert-danger">
      {{ error }}
    </div>

      <div class="row">
        <!-- Cột thông tin bên trái -->
        <div class="col-md-6">
          <!-- Thông tin chung -->
          <div class="card mb-4">
            <div class="card-body">
              <h5 class="card-title">Thông tin chung</h5>
              <div class="info-item">
                <strong>Trạng thái:</strong> 
                <span :class="getStatusClass(order.status)">{{ getStatusLabel(order.status) }}</span>
              </div>
              <div class="info-item">
                <strong>Ngày tạo:</strong> {{ formatDate(order.created_at) }}
              </div>
            </div>
          </div>
  
          <!-- Thông tin bưu cục giao -->
          <div class="card mb-4">
            <div class="card-body">
              <h5 class="card-title">Thông tin bưu cục giao hàng</h5>
              <div class="info-item">
                <strong>Tên bưu cục:</strong> {{ postOffice?.name }}
              </div>
              <div class="info-item">
                <strong>Địa chỉ:</strong> {{ postOffice?.address }}
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
          <div v-if="hasImage" class="card mb-4">
            <div class="card-body">
              <h5 class="card-title">Hình ảnh đơn hàng</h5>
              <div class="order-image-container">
                <img 
                  v-if="latestImageUrl" 
                  :src="latestImageUrl" 
                  alt="Hình ảnh đơn hàng" 
                  class="rounded order-img" 
                  @error="handleImageError"
                >
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
              <h5 class="card-title">Bản đồ - đường đi đến người nhận</h5>
              <div id="map" style="width: 100%; height: 400px;"></div>
            </div>
          </div>
  
          <!-- Thông tin giao nhận -->
          <div class="card mb-4">
            <div class="card-body">
              <h5 class="card-title">Thông tin giao hàng</h5>
              <div class="info-item">
                <strong>Khoảng cách:</strong> <span>{{ distance }}</span>
              </div>
              <div class="info-item">
                <strong>Thời gian dự kiến:</strong> <span>{{ duration }}</span>
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
                  <option value="delivered">Đã giao hàng thành công</option>
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
                <input 
                  v-model="customReason" 
                  type="text" 
                  class="form-control" 
                  placeholder="Nhập lý do khác"
                >
              </div>
              <div v-if="showImageUpload" class="form-group mt-3">
                <label for="imageUpload" class="form-label">Tải lên hình ảnh xác nhận</label>
                <input 
                  id="imageUpload" 
                  type="file" 
                  @change="handleFileUpload" 
                  class="form-control" 
                  accept="image/*"
                >
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
  const theme = inject('theme')
  
  // Reactive refs
  const order = ref({})
  const postOffice = ref(null)
  const newStatus = ref('')
  const selectedReason = ref('')
  const customReason = ref('')
  const file = ref(null)
  const imageError = ref(null)
  const distance = ref('')
  const duration = ref('')
  
  // Map variables
  let map
  let directions
  
  // Reason options cho đơn giao hàng
  const reasonOptions = [
    { value: 'address_not_found', label: 'Không tìm thấy địa chỉ' },
    { value: 'customer_not_available', label: 'Khách hàng không có mặt' },
    { value: 'customer_refused', label: 'Khách hàng từ chối nhận hàng' },
    { value: 'wrong_address', label: 'Địa chỉ không chính xác' },
    { value: 'other', label: 'Lý do khác' }
  ]
  
  // Computed
  const showReason = computed(() => newStatus.value === 'failed_delivery')
  const showCustomReason = computed(() => selectedReason.value === 'other')
  const showImageUpload = computed(() => newStatus.value === 'delivered')

 
  
  // Helper functions
  const formatDate = (dateString) => {
    if (!dateString) return ''
    return new Date(dateString).toLocaleDateString('vi-VN', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
  
  const getStatusClass = (status) => {
    const classes = {
      'out_for_delivery': 'status-in-transit',
      'delivered': 'status-delivered',
      'failed_delivery': 'status-failed'
    }
    return classes[status] || ''
  }
  
  const getStatusLabel = (status) => {
    const labels = {
      'out_for_delivery': 'Đang giao hàng',
      'delivered': 'Đã giao hàng',
      'failed_delivery': 'Giao thất bại'
    }
    return labels[status] || status
  }
  
  const parseCoordinates = (coordinates) => {
    if (!coordinates) return null
    
    try {
      if (Array.isArray(coordinates)) {
        return coordinates.map(Number)
      }
      
      if (typeof coordinates === 'string') {
        const parsed = JSON.parse(coordinates)
        if (Array.isArray(parsed)) {
          return parsed.map(Number)
        }
        const parts = coordinates.split(',').map(part => parseFloat(part.trim()))
        if (parts.length === 2 && !isNaN(parts[0]) && !isNaN(parts[1])) {
          return parts
        }
      }
      
      if (typeof coordinates === 'object' && 'lat' in coordinates && 'lng' in coordinates) {
        return [Number(coordinates.lng), Number(coordinates.lat)]
      }
    } catch (error) {
      console.error('Error parsing coordinates:', error)
    }
    
    return null
  }
  
  const handleImageError = (event) => {
    console.error('Image loading error:', event)
    imageError.value = 'Không thể tải hình ảnh'
  }
  const loading = ref(true)
  const error = ref(null)
  
  // Mapbox functions
  const initializeMap = () => {
    const postOfficeCoords = parseCoordinates(postOffice.value.coordinates)
    const receiverCoords = parseCoordinates(order.value.receiver_coordinates)
  
    if (!postOfficeCoords || !receiverCoords) {
      toast.error('Không thể hiển thị bản đồ do thiếu thông tin tọa độ')
      return
    }
  
    mapboxgl.accessToken = import.meta.env.VITE_MAPBOX_ACCESS_TOKEN
    
    map = new mapboxgl.Map({
      container: 'map',
      style: 'mapbox://styles/mapbox/streets-v11',
      center: postOfficeCoords,
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
      // Tạo tuyến đường từ bưu cục đến người nhận
      directions.setOrigin(postOfficeCoords)
      directions.setDestination(receiverCoords)
  
      // Thêm markers
      new mapboxgl.Marker({ color: "#00FF00" })
        .setLngLat(postOfficeCoords)
        .setPopup(new mapboxgl.Popup().setHTML(
          `<h3>Bưu cục giao hàng</h3>
           <p>${postOffice.value.name}</p>
           <p>${postOffice.value.address}</p>`
        ))
        .addTo(map)
  
      new mapboxgl.Marker({ color: "#f30" })
        .setLngLat(receiverCoords)
        .setPopup(new mapboxgl.Popup().setHTML(
          `<h3>Địa chỉ giao hàng</h3>
           <p>${order.value.receiver_name}</p>
           <p>${order.value.receiver_address}</p>`
        ))
        .addTo(map)
  
      // Fit map để hiển thị cả 2 điểm
      const bounds = new mapboxgl.LngLatBounds()
      bounds.extend(postOfficeCoords)
      bounds.extend(receiverCoords)
      map.fitBounds(bounds, { padding: 50 })
    })
  
    // Lấy thông tin khoảng cách và thời gian
    directions.on('route', (e) => {
      if (e.route && e.route[0]) {
        distance.value = `${(e.route[0].distance / 1000).toFixed(2)} km`
        duration.value = `${Math.round(e.route[0].duration / 60)} phút`
      }
    })
  }
  
  // API calls
  const loadOrderDetail = async () => {
    loading.value = true;
    error.value = null;
    
    try {
        const response = await axios.get(`/api/shipper/deliveryorders/${route.params.id}`, {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        console.log('API Response:', response.data);
        
        if (response.data.success) {
            order.value = response.data.order;
            postOffice.value = response.data.post_office;

            if (postOffice.value?.coordinates && order.value?.receiver_coordinates) {
                initializeMap();
            } else {
                console.warn('Missing coordinates data:', {
                    postOffice: postOffice.value?.coordinates,
                    receiver: order.value?.receiver_coordinates
                });
            }
        } else {
            error.value = response.data.message || 'Không tìm thấy thông tin đơn hàng';
        }
    } catch (err) {
        console.error('Error loading order:', err);
        
        if (err.response?.status === 404) {
            error.value = 'Không tìm thấy đơn hàng hoặc đơn hàng không được gán cho bạn';
        } else if (err.response?.status === 401) {
            router.push('/login');
        } else {
            error.value = err.response?.data?.message || 'Không thể tải thông tin đơn hàng';
        }
    } finally {
        loading.value = false;
    }
};
// Trong các computed properties, thêm kiểm tra null
const hasImage = computed(() => 
  order.value?.status === 'delivered' && 
  order.value?.statusUpdates?.some(update => update.image)
)

const latestImageUrl = computed(() => {
  if (!order.value?.statusUpdates?.length) return null
  const updates = order.value.statusUpdates
    .filter(update => update.image)
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
  return updates[0]?.image || null
})
  
  // Event handlers
  const handleStatusChange = () => {
    selectedReason.value = ''
    customReason.value = ''
    file.value = null
  }
  
  // Tiếp tục từ handleReasonChange
const handleReasonChange = () => {
  if (selectedReason.value !== 'other') {
    customReason.value = ''
  }
}

const handleFileUpload = (event) => {
  file.value = event.target.files[0]
}

const updateStatus = async () => {
  try {
    if (!order.value || !order.value.id) {
      throw new Error('Không tìm thấy thông tin đơn hàng');
    }

    if (!newStatus.value) {
      throw new Error('Vui lòng chọn trạng thái mới');
    }

    if (newStatus.value === 'failed_delivery' && !selectedReason.value) {
      throw new Error('Vui lòng chọn lý do không giao được hàng');
    }

    if (newStatus.value === 'delivered' && !file.value) {
      throw new Error('Vui lòng tải lên hình ảnh xác nhận giao hàng');
    }

    loading.value = true;
    const formData = new FormData();
    formData.append('status', newStatus.value);
    
    if (selectedReason.value) {
      formData.append('reason', selectedReason.value);
    }
    
    if (customReason.value) {
      formData.append('custom_reason', customReason.value);
    }
    
    if (file.value) {
      formData.append('image', file.value);
    }

    // Lấy vị trí hiện tại
    try {
      const position = await getCurrentPosition();
      if (position) {
        formData.append('latitude', position.coords.latitude.toString());
        formData.append('longitude', position.coords.longitude.toString());
      }
    } catch (error) {
      console.error('Error getting position:', error);
      toast.warning('Không thể lấy vị trí hiện tại. Vẫn tiếp tục cập nhật trạng thái.');
    }

    console.log('Sending update request:', {
      url: `/api/shipper/deliveryorders/${order.value.id}/status`,
      formData: Object.fromEntries(formData.entries())
    });

    // Gọi API cập nhật trạng thái
    const token = localStorage.getItem('token');
    const updateResponse = await axios.post(
      `/api/shipper/deliveryorders/${order.value.id}/status`,
      formData,
      {
        headers: { 
          'Content-Type': 'multipart/form-data',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`
        }
      }
    );

    console.log('Update response:', updateResponse.data);

    if (updateResponse.data.success) {
      toast.success('Cập nhật trạng thái thành công');
      
      // Nếu cập nhật thành công và trạng thái là delivered hoặc failed_delivery
      // thì quay về trang danh sách
      if (['delivered', 'failed_delivery'].includes(newStatus.value)) {
        router.push('/orders/deliveryorders');
      } else {
        // Nếu không, reload lại thông tin đơn hàng
        await loadOrderDetail();
      }
    } else {
      throw new Error(updateResponse.data.message || 'Cập nhật trạng thái thất bại');
    }

  } catch (error) {
    console.error('Error updating status:', error);
    
    let errorMessage = error.message;
    if (error.response?.data?.errors) {
      errorMessage = Object.values(error.response.data.errors)
        .flat()
        .join('\n');
    } else if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    }
    
    toast.error(errorMessage || 'Không thể cập nhật trạng thái đơn hàng');
  } finally {
    loading.value = false;
  }
};

const getCurrentPosition = () => {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Trình duyệt không hỗ trợ định vị'))
      return
    }
    
    navigator.geolocation.getCurrentPosition(resolve, reject, {
      enableHighAccuracy: true,
      timeout: 5000,
      maximumAge: 0
    })
  })
}

// Lifecycle hooks
onMounted(() => {
  loadOrderDetail()
})

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