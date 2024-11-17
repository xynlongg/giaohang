<template>
  <div class="orders-page" :class="theme">
    <h1 class="page-title">Danh sách đơn hàng</h1>
    <div class="filters mb-3">
      <select v-model="statusFilter" @change="loadOrders" class="form-select me-2">
        <option value="">Tất cả trạng thái</option>
        <option value="pending">Đang chờ xử lý</option>
        <option value="in_transit">Đang vận chuyển</option>
        <option value="delivered">Đã giao hàng</option>
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
            <th>Người gửi</th>
            <th>Người nhận</th>
            <th>Trạng thái</th>
            <th>Ngày tạo</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in orders" :key="order.id">
            <td>{{ order.tracking_number }}</td>
            <td>{{ order.sender_name }}</td>
            <td>{{ order.receiver_name }}</td>
            <td>
              <span 
                class="status-label"
                :class="{
                  'pending': order.status === 'pending',
                  'in-transit': order.status === 'in_transit',
                  'delivered': order.status === 'delivered'
                }"
              >
                {{ translateStatus(order.status) }}
              </span>
            </td>
            <td>{{ formatDate(order.created_at) }}</td>
            <td>
              <router-link :to="{ name: 'OrderDetail', params: { id: order.id } }" class="btn btn-primary btn-sm">
                Chi tiết
              </router-link>
              <button v-if="shipperStore.canPickupOrders && order.status === 'pending'" @click="pickupOrder(order.id)" class="btn btn-success btn-sm ms-2">
                Nhận đơn
              </button>
              <button v-if="shipperStore.canDeliverOrders && order.status === 'in_transit'" @click="deliverOrder(order.id)" class="btn btn-info btn-sm ms-2">
                Giao hàng
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
        <li class="page-item" :class="{ active: page === currentPage }" v-for="page in totalPages" :key="page">
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
import { ref, onMounted, inject, watch } from 'vue'
import { useShipperStore } from '@/stores/shipper'
import { storeToRefs } from 'pinia'
import { useToast } from 'vue-toastification'

const shipperStore = useShipperStore()
const { orders } = storeToRefs(shipperStore)
const toast = useToast()
const statusFilter = ref('')
const dateFilter = ref('')
const currentPage = ref(1)
const totalPages = ref(1)
const theme = inject('theme')
const loading = ref(false)
const error = ref(null)

const loadOrders = async (page = 1) => {
  loading.value = true
  error.value = null
  try {
    const response = await shipperStore.getOrders(page, statusFilter.value, dateFilter.value)
    currentPage.value = response.current_page
    totalPages.value = response.last_page
    console.log('Orders loaded:', orders.value)
  } catch (err) {
    console.error('Error loading orders:', err)
    error.value = 'Không thể tải danh sách đơn hàng. Vui lòng thử lại sau.'
    toast.error(error.value)
  } finally {
    loading.value = false
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
    day: 'numeric'
  })
}

const translateStatus = (status) => {
  switch (status) {
    case 'pending': return 'Đang chờ xử lý'
    case 'in_transit': return 'Đang vận chuyển'
    case 'delivered': return 'Đã giao hàng'
    default: return status  
  }
}

const pickupOrder = async (orderId) => {
  try {
    await shipperStore.updateOrderStatus(orderId, 'in_transit')
    toast.success('Đã nhận đơn hàng thành công')
  } catch (error) {
    console.error('Error picking up order:', error)
    toast.error('Không thể nhận đơn hàng. Vui lòng thử lại.')
  }
}

const deliverOrder = async (orderId) => {
  try {
    await shipperStore.updateOrderStatus(orderId, 'delivered')
    toast.success('Đã giao hàng thành công')
  } catch (error) {
    console.error('Error delivering order:', error)
    toast.error('Không thể cập nhật trạng thái giao hàng. Vui lòng thử lại.')
  }
}

const handleNewOrder = (order) => {
  toast.info(`Đơn hàng mới: ${order.tracking_number}`, {
    timeout: 5000,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: false,
    closeButton: "button",
    icon: true,
    rtl: false
  })
}

watch(orders, (newOrders, oldOrders) => {
  console.log('Orders updated:', newOrders)
  if (newOrders.length !== oldOrders.length) {
    console.log('Orders length changed:', newOrders.length)
    if (newOrders.length > oldOrders.length) {
      const newOrder = newOrders[0] 
      handleNewOrder(newOrder)
    } else {
      toast.info('Một đơn hàng đã được xóa hoặc gán lại cho shipper khác')
    }
  } else {
    const updatedOrder = newOrders.find((order, index) => 
      JSON.stringify(order) !== JSON.stringify(oldOrders[index])
    )
    if (updatedOrder) {
      console.log('An order was updated:', updatedOrder)
      toast.info(`Đơn hàng ${updatedOrder.tracking_number} đã được cập nhật`)
    }
  }
}, { deep: true })

onMounted(() => {
  console.log('Orders component mounted')
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

.table-responsive {
  overflow-x: auto;
}

.table {
  margin-bottom: 0;
  background-color: var(--bg-secondary);
  color: var(--text-primary);
}

.table th {
  font-weight: bold;
  color: var(--text-primary);
  background-color: var(--bg-primary);
}

.status-label {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  color: rgb(29, 213, 142);
  font-size: 12px;
  text-transform: uppercase;
}

.status-label.pending {
  background-color: var(--warning-color);
}

.status-label.in-transit {
  background-color: var(--info-color); 
}

.status-label.delivered {
  background-color: var(--success-color);
}

.form-select,
.form-control {
  background-color: var(--bg-secondary);
  color: var(--text-primary);
  border-color: var(--text-secondary);
}

.form-select:focus,
.form-control:focus {
  background-color: var(--bg-secondary);
  color: var(--text-primary);
  border-color: var(--accent-color);
  box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);  
}

.btn-primary {
  background-color: var(--accent-color);
  border-color: var(--accent-color);
}

.btn-primary:hover {
  background-color: darken(var(--accent-color), 10%);
  border-color: darken(var(--accent-color), 10%);
}

.table-hover tbody tr:hover {
  background-color: var(--bg-secondary);
}

.page-link {
  color: var(--text-primary);
  background-color: var(--bg-secondary);
  border-color: var(--text-secondary);
}

.page-link:hover {
  color: var(--accent-color);
  background-color: var(--bg-primary);
  border-color: var(--accent-color);
}

.page-item.active .page-link {
  color: var(--bg-primary);
  background-color: var(--accent-color);
  border-color: var(--accent-color);  
}
</style>