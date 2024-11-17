<template>
    <div class="orders-page" :class="theme">
      <h1 class="page-title">Quản lý đơn giao hàng</h1>
      <div class="filters mb-3">
        <select v-model="statusFilter" @change="loadOrders" class="form-select me-2">
          <option value="">Tất cả trạng thái</option>
          <option value="assigned">Chờ lấy hàng</option>
          <option value="in_transit">Đang giao hàng</option>
          <option value="delivered">Đã giao hàng</option>
          <option value="failed_delivery">Giao thất bại</option>
        </select>
        <input type="date" v-model="dateFilter" @change="loadOrders" class="form-control">
      </div>
  
      <div v-if="loading" class="text-center">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Đang tải...</span>
        </div>
      </div>
  
      <div v-else-if="error" class="alert alert-danger" role="alert">
        {{ error }}
      </div>
  
      <div v-else class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Mã đơn hàng</th>
              <th>Bưu cục giao</th>
              <th>Người nhận</th>
              <th>Địa chỉ giao</th>
              <th>Trạng thái</th>
              <th>Thời gian gán</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in deliveryOrders" :key="order.id">
              <td>{{ order.tracking_number }}</td>
              <td>{{ order.post_office_name }}</td>
              <td>
                <div>{{ order.receiver_name }}</div>
                <small class="text-muted">{{ order.receiver_phone }}</small>
              </td>
              <td>{{ order.receiver_address }}</td>
              <td>
                <span 
                  class="status-label"
                  :class="getStatusClass(order.status)"
                >
                  {{ translateStatus(order.status) }}
                </span>
              </td>
              <td>{{ formatDate(order.distributed_at) }}</td>
              <td>
                <router-link 
                  :to="`/orders/deliveryorders/${order.id}`"
                  class="btn btn-primary btn-sm"
                >
                  Chi tiết
                </router-link>
                <button 
                  v-if="canStartDelivery(order)"
                  @click="startDelivery(order.id)" 
                  class="btn btn-success btn-sm ms-2"
                >
                  Bắt đầu giao
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
  
      <nav v-if="totalPages > 1" aria-label="Page navigation">
        <ul class="pagination justify-content-center">
          <li class="page-item" :class="{ disabled: currentPage === 1 }">
            <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">Trước</a>
          </li>
          <li 
            v-for="page in totalPages" 
            :key="page" 
            class="page-item" 
            :class="{ active: page === currentPage }"
          >
            <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
          </li>
          <li class="page-item" :class="{ disabled: currentPage === totalPages }">
            <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">Tiếp</a>
          </li>
        </ul>
      </nav>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted, inject, watch } from 'vue' // Thêm watch vào imports
  import { useShipperStore } from '@/stores/shipper'
  import { useToast } from 'vue-toastification'
  import { useRouter } from 'vue-router' // Thêm router import
  
  const router = useRouter() // Khởi tạo router
  const shipperStore = useShipperStore()
  const toast = useToast()
  const theme = inject('theme')
  
  const deliveryOrders = ref([])
  const statusFilter = ref('')
  const dateFilter = ref('')
  const currentPage = ref(1)
  const totalPages = ref(1)
  const loading = ref(false)
  const error = ref(null)
  
  const loadOrders = async (page = 1) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await shipperStore.getDeliveryOrders(page, statusFilter.value, dateFilter.value);
      if (response && response.data) {
        deliveryOrders.value = response.data;
        currentPage.value = response.current_page;
        totalPages.value = response.last_page;
      } else {
        deliveryOrders.value = [];
      }
    } catch (err) {
      error.value = 'Không thể tải danh sách đơn hàng';
    } finally {
      loading.value = false;
    }
  }
  
  const startDelivery = async (orderId) => {
    console.log('Starting delivery for order:', orderId)
    
    try {
      if (!navigator.geolocation) {
        throw new Error('Trình duyệt không hỗ trợ định vị. Không thể bắt đầu giao hàng.')
      }
  
      const position = await new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 0
        })
      })
  
      console.log('Current position obtained:', {
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        accuracy: position.coords.accuracy
      })
  
      await shipperStore.updateOrderStatus(orderId, 'in_transit', null, null, null, {
        latitude: position.coords.latitude,
        longitude: position.coords.longitude
      })
  
      console.log('Delivery started successfully')
      toast.success('Đã bắt đầu giao hàng')
      
      await loadOrders(currentPage.value)
  
    } catch (error) {
      console.error('Error starting delivery:', {
        orderId,
        error: error.message,
        stack: error.stack
      })
  
      let errorMessage = 'Không thể bắt đầu giao hàng. '
  
      if (error.name === 'GeolocationPositionError') {
        switch (error.code) {
          case 1:
            errorMessage += 'Vui lòng cấp quyền truy cập vị trí.'
            break
          case 2:
            errorMessage += 'Không thể xác định vị trí hiện tại.'
            break
          case 3:
            errorMessage += 'Quá thời gian xác định vị trí.'
            break
        }
      } else if (error.response) {
        errorMessage += error.response.data.message || 'Lỗi từ máy chủ.'
      } else {
        errorMessage += 'Vui lòng thử lại.'
      }
  
      toast.error(errorMessage)
    }
  }
  
  const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
      loadOrders(page)
    }
  }
  
  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('vi-VN', {
      year: 'numeric',
      month: 'long', 
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
  
  const translateStatus = (status) => {
    const statusMap = {
      'assigned': 'Chờ lấy hàng',
      'in_transit': 'Đang giao hàng',
      'delivered': 'Đã giao hàng', 
      'failed_delivery': 'Giao thất bại'
    }
    return statusMap[status] || status
  }
  
  const getStatusClass = (status) => {
    const classMap = {
      'assigned': 'status-assigned',
      'in_transit': 'status-in-transit', 
      'delivered': 'status-delivered',
      'failed_delivery': 'status-failed'
    }
    return classMap[status] || ''
  }
  
  const canStartDelivery = (order) => {
    return order.status === 'assigned' && shipperStore.canDeliverOrders
  }
  
  // Watch handlers
  watch(deliveryOrders, (newValue, oldValue) => {
    console.log('Delivery orders updated:', {
      oldCount: oldValue?.length,
      newCount: newValue?.length,
      firstRecord: newValue?.[0]
    })
  }, { deep: true })
  
  watch([statusFilter, dateFilter], ([newStatus, newDate], [oldStatus, oldDate]) => {
    console.log('Filters changed:', {
      status: { old: oldStatus, new: newStatus },
      date: { old: oldDate, new: newDate }
    })
  })
  
  onMounted(() => {
    console.log('Delivery Orders component mounted')
    loadOrders()
    shipperStore.initializeRealtime()
  })
  </script>
  
  <style scoped>
  .orders-page {
    padding: 20px;
    background-color: var(--bg-primary);
    color: var(--text-primary);
    min-height: calc(100vh - 60px);
  }
  
  .page-title {
    font-size: 24px;
    margin-bottom: 20px;
    color: var(--text-primary);
  }
  
  .status-label {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    text-transform: uppercase;
  }
  
  .status-assigned {
    background-color: var(--warning-bg);
    color: var(--warning-text);
  }
  
  .status-in-transit {
    background-color: var(--info-bg);
    color: var(--info-text);
  }
  
  .status-delivered {
    background-color: var(--success-bg);
    color: var(--success-text);
  }
  
  .status-failed {
    background-color: var(--danger-bg);
    color: var(--danger-text);
  }
  
  .filters {
    display: flex;
    gap: 1rem;
  }
  
  @media (max-width: 768px) {
    .filters {
      flex-direction: column;
    }
  }
  </style>