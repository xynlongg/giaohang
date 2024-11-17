<template>
    <div class="pickup-orders-management" :class="theme">
      <h1 class="page-title">Quản lý đơn hàng lấy hàng</h1>
  
      <div class="filters mb-3">
        <select v-model="statusFilter" @change="filterOrders" class="form-select me-2">
          <option value="">Tất cả trạng thái</option>
          <option value="picked_up">Đã lấy hàng</option>
          <option value="arrived_at_post_office">Đã đến bưu cục</option>
          <option value="in_transit">Đang vận chuyển</option>
          <option value="delivered">Đã giao hàng</option>
          <option value="failed_pickup">Không lấy được hàng</option>
        </select>
        <input type="date" v-model="dateFilter" @change="filterOrders" class="form-control">
      </div>
  
      <div v-if="loading" class="text-center">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Đang tải...</span>
        </div>
      </div>
  
      <div v-else-if="filteredOrders.length === 0" class="alert alert-info">
        Không có đơn hàng nào phù hợp với tiêu chí tìm kiếm.
      </div>
  
      <div v-else>
        <table class="table table-hover">
          <thead>
            <tr>
              <th>
                <input type="checkbox" v-model="selectAll" @change="toggleAllSelection">
              </th>
              <th>Mã đơn hàng</th>
              <th>Người gửi</th>
              <th>Người nhận</th>
              <th>Trạng thái</th>
              <th>Ngày tạo</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in filteredOrders" :key="order.id">
              <td>
                <input type="checkbox" v-model="selectedOrders" :value="order.id">
              </td>
              <td>{{ order.tracking_number }}</td>
              <td>{{ order.sender_name }}</td>
              <td>{{ order.receiver_name }}</td>
              <td>
                <span class="status-label" :class="getStatusClass(order.status)">
                  {{ translateStatus(order.status) }}
                </span>
              </td>
              <td>{{ formatDate(order.created_at) }}</td>
              <td>
                <button @click="updateSingleOrderStatus(order.id, 'arrived_at_post_office')" 
                        class="btn btn-success btn-sm" 
                        v-if="order.status === 'picked_up'">
                  Đã đến bưu cục
                </button>
                <button @click="updateSingleOrderStatus(order.id, 'in_transit')" 
                        class="btn btn-primary btn-sm" 
                        v-if="order.status === 'arrived_at_post_office'">
                  Bắt đầu vận chuyển
                </button>
                <button @click="retryPickup(order.id)" 
                        class="btn btn-warning btn-sm" 
                        v-if="order.status === 'failed_pickup'">
                  Thử lại lấy hàng
                </button>
              </td>
            </tr>
          </tbody>
        </table>
  
        <div class="bulk-actions mt-3">
          <button @click="updateBulkOrderStatus('arrived_at_post_office')" 
                  class="btn btn-success" 
                  :disabled="!canUpdateToArrivedAtPostOffice">
            Cập nhật hàng loạt: Đã đến bưu cục
          </button>
          <button @click="updateBulkOrderStatus('in_transit')" 
                  class="btn btn-primary ms-2" 
                  :disabled="!canUpdateToInTransit">
            Cập nhật hàng loạt: Bắt đầu vận chuyển
          </button>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, computed, onMounted, watch } from 'vue'
  import { useShipperStore } from '@/stores/shipper'
  import { storeToRefs } from 'pinia'
  import { useToast } from 'vue-toastification'
  import { useTheme } from '@/composables/useTheme'
  
  export default {
    setup() {
      const shipperStore = useShipperStore()
      const { orders } = storeToRefs(shipperStore)
      const toast = useToast()
      const { theme } = useTheme()
  
      const loading = ref(true)
      const statusFilter = ref('')
      const dateFilter = ref('')
      const selectedOrders = ref([])
      const selectAll = ref(false)
  
      const filteredOrders = computed(() => {
        return orders.value.filter(order => {
          const statusMatch = !statusFilter.value || order.status === statusFilter.value
          const dateMatch = !dateFilter.value || new Date(order.created_at).toDateString() === new Date(dateFilter.value).toDateString()
          return statusMatch && dateMatch
        })
      })
  
      const canUpdateToArrivedAtPostOffice = computed(() => {
        return selectedOrders.value.length > 0 && selectedOrders.value.every(orderId => {
          const order = orders.value.find(o => o.id === orderId)
          return order && order.status === 'picked_up'
        })
      })
  
      const canUpdateToInTransit = computed(() => {
        return selectedOrders.value.length > 0 && selectedOrders.value.every(orderId => {
          const order = orders.value.find(o => o.id === orderId)
          return order && order.status === 'arrived_at_post_office'
        })
      })
  
      const loadOrders = async () => {
        try {
          await shipperStore.getOrders()
          loading.value = false
        } catch (error) {
          console.error('Error loading orders:', error)
          toast.error('Không thể tải danh sách đơn hàng. Vui lòng thử lại sau.')
        }
      }
  
      const translateStatus = (status) => {
        const statusMap = {
          picked_up: 'Đã lấy hàng',
          arrived_at_post_office: 'Đã đến bưu cục',
          in_transit: 'Đang vận chuyển',
          delivered: 'Đã giao hàng',
          failed_pickup: 'Không lấy được hàng',
          failed_delivery: 'Giao hàng thất bại'
        }
        return statusMap[status] || status
      }
  
      const getStatusClass = (status) => {
        const classMap = {
          picked_up: 'bg-info',
          arrived_at_post_office: 'bg-success',
          in_transit: 'bg-primary',
          delivered: 'bg-success',
          failed_pickup: 'bg-danger',
          failed_delivery: 'bg-warning'
        }
        return classMap[status] || 'bg-secondary'
      }
  
      const toggleAllSelection = () => {
        if (selectAll.value) {
          selectedOrders.value = filteredOrders.value.map(order => order.id)
        } else {
          selectedOrders.value = []
        }
      }
  
      const updateSingleOrderStatus = async (orderId, newStatus) => {
      try {
        console.log('Cập nhật trạng thái đơn hàng:', { orderId, newStatus });
        const updatedOrder = await shipperStore.updateOrderStatus(orderId, newStatus);
        console.log('Cập nhật trạng thái thành công', updatedOrder);
        
        if (newStatus === 'arrived_at_post_office') {
          console.log('Đơn hàng đã đến bưu cục:', updatedOrder);
          toast.success(`Đơn hàng đã đến bưu cục ${updatedOrder.current_location}`);
        } else {
          toast.success('Cập nhật trạng thái đơn hàng thành công');
        }
      } catch (error) {
        console.error('Lỗi cập nhật trạng thái đơn hàng:', error);
        handleUpdateError(error);
      }
    };
  
    const updateBulkOrderStatus = async (newStatus) => {
      try {
        for (const orderId of selectedOrders.value) {
          await updateSingleOrderStatus(orderId, newStatus);
        }
        toast.success('Cập nhật hàng loạt thành công');
        selectedOrders.value = [];
        selectAll.value = false;
      } catch (error) {
        console.error('Error updating bulk order status:', error);
        handleUpdateError(error);
      }
    };
  
      const retryPickup = async (orderId) => {
        try {
          await shipperStore.updateOrderStatus(orderId, 'picked_up')
          toast.success('Đã đặt lại trạng thái đơn hàng để thử lấy hàng lại')
        } catch (error) {
          console.error('Error retrying pickup:', error)
          handleUpdateError(error)
        }
      }
  
      const handleUpdateError = (error) => {
        console.error('Detailed error response:', error.response)
        if (error.response && error.response.data && error.response.data.message) {
          toast.error(`Lỗi: ${error.response.data.message}`)
        } else {
          toast.error('Có lỗi xảy ra khi cập nhật trạng thái đơn hàng. Vui lòng thử lại.')
        }
      }
  
      const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('vi-VN', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        })
      }
  
      const filterOrders = () => {
        // This function is called when filters change
        // The computed property 'filteredOrders' will automatically update
      }
  
      onMounted(() => {
        loadOrders()
        shipperStore.initializeRealtime()
      })
  
      return {
        theme,
        loading,
        filteredOrders,
        statusFilter,
        dateFilter,
        selectedOrders,
        selectAll,
        translateStatus,
        getStatusClass,
        toggleAllSelection,
        updateSingleOrderStatus,
        updateBulkOrderStatus,
        retryPickup,
        formatDate,
        filterOrders,
        canUpdateToArrivedAtPostOffice,
        canUpdateToInTransit
      }
    }
  }
  </script>
  <style scoped>

  .pickup-orders-management {
    padding: 20px;
    min-height: calc(100vh - 60px);
  }
  
  .pickup-orders-management.light {
    background-color: #f8f9fa;
    color: #333;
  }
  
  .pickup-orders-management.dark {
    background-color: #2c3e50;
    color: #ecf0f1;
  }
  
  .page-title {
    font-size: 24px;
    margin-bottom: 20px;
  }
  
  .table {
    background-color: var(--bs-table-bg);
  }
  
  .light .table {
    --bs-table-bg: #fff;
    --bs-table-color: #212529;
  }
  
  .dark .table {
    --bs-table-bg: #34495e;
    --bs-table-color: #ecf0f1;
  }
  
  .status-label {
    display: inline-block;
    padding: 0.25em 0.6em;
    font-size: 0.75em;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
    color: #fff;
  }
  
  .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.2rem;
  }
  
  .form-select,
  .form-control {
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    border-color: var(--bs-border-color);
  }
  
  .light .form-select,
  .light .form-control {
    --bs-body-bg: #fff;
    --bs-body-color: #212529;
    --bs-border-color: #ced4da;
  }
  
  .dark .form-select,
  .dark .form-control {
    --bs-body-bg: #2c3e50;
    --bs-body-color: #ecf0f1;
    --bs-border-color: #4a5568;
  }
  
  .form-select:focus,
  .form-control:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
  }
  
  .btn-primary {
    background-color: #007bff;
    border-color: #007bff;
  }
  
  .btn-primary:hover {
    background-color: #0056b3;
    border-color: #0056b3;
  }
  
  .btn-success {
    background-color: #28a745;
    border-color: #28a745;
  }
  
  .btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
  }
  
  .btn-warning {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #212529;
  }
  
  .btn-warning:hover {
    background-color: #e0a800;
    border-color: #d39e00;
    color: #212529;
  }
  
  .dark .btn-warning {
    color: #1a202c;
  }
  
  .spinner-border {
    color: var(--bs-primary);
  }
  </style>