import { defineStore } from 'pinia'
import axios from 'axios'
import { useToast } from 'vue-toastification'

export const useShipperStore = defineStore('shipper', {
  state: () => ({
    shipper: null,
    isAuthenticated: false,
    token: null,
    orders: [],
  }),

  getters: {
    getShipperName: (state) => state.shipper ? state.shipper.name : '',
    getShipperAvatar: (state) => {
      if (state.shipper && state.shipper.avatar) {
        return `${import.meta.env.VITE_APP_URL}/storage/${state.shipper.avatar}`
      }
      return '/default-avatar.png'
    },
    canPickupOrders: (state) => state.shipper && state.shipper.roles && state.shipper.roles.includes('pickup'),
    canDeliverOrders: (state) => state.shipper && state.shipper.roles && state.shipper.roles.includes('delivery'),
  },

  actions: {
    async login(email, password) {
      try {
        const response = await axios.post('/api/shipper/login', { email, password })
        if (response.data.shipper && response.data.token) {
          this.setShipperData(response.data.shipper, response.data.token)
          this.initializeRealtime()
          return true
        }
        return false
      } catch (error) {
        console.error('Login error:', error)
        throw error
      }
    },

    setShipperData(shipper, token) {
      this.shipper = shipper
      this.token = token
      this.isAuthenticated = true
      localStorage.setItem('token', token)
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    },

    async checkAuth() {
      const token = localStorage.getItem('token')
      if (token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        try {
          const response = await axios.get('/api/shipper/me')
          this.setShipperData(response.data, token)
          return true
        } catch (error) {
          console.error('Auth check error:', error.response || error)
          if (error.message === 'Network Error') {
            console.log('Network error occurred, unable to check authentication')
            return false
          }
          this.clearShipperData()
          return false
        }
      }
      return false
    },

    async fetchProfile() {
      try {
        const response = await axios.get('/api/shipper/me')
        this.shipper = response.data
        return this.shipper
      } catch (error) {
        console.error('Error fetching profile:', error)
        throw error
      }
    },

    async uploadAvatar(formData) {
      try {
        const response = await axios.post('/api/shipper/avatar', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        this.shipper = { ...this.shipper, avatar: response.data.avatar }
        return response.data
      } catch (error) {
        console.error('Error uploading avatar:', error)
        throw error
      }
    },

    async updateProfile(profileData) {
      try {
        const response = await axios.put('/api/shipper/profile', profileData)
        this.shipper = { ...this.shipper, ...response.data }
        return this.shipper
      } catch (error) {
        console.error('Error updating profile:', error)
        throw error
      }
    },

    async removeAvatar() {
      try {
        await axios.delete('/api/shipper/avatar')
        this.shipper = { ...this.shipper, avatar: null }
      } catch (error) {
        console.error('Error removing avatar:', error)
        throw error
      }
    },

    async changePassword(passwordData) {
      try {
        const response = await axios.post('/api/shipper/change-password', passwordData)
        return response.data
      } catch (error) {
        console.error('Error changing password:', error)
        throw error
      }
    },

    async logout() {
      try {
        await axios.post('/api/shipper/logout')
      } catch (error) {
        console.error('Logout error:', error.response || error)
      } finally {
        this.clearShipperData()
      }
    },

    clearShipperData() {
      this.shipper = null
      this.token = null
      this.isAuthenticated = false
      this.orders = []
      localStorage.removeItem('token')
      localStorage.removeItem('shipperId')
      delete axios.defaults.headers.common['Authorization']
      if (window.Echo) {
        window.Echo.disconnect()
      }
    },

    async getOrders(page = 1, status = '', date = '') {
      try {
        const response = await axios.get('/api/shipper/orders', {
          params: { page, status, date }
        })
        this.orders = response.data.data
        console.log('Orders fetched:', this.orders)
        return response.data
      } catch (error) {
        console.error('Error fetching orders:', error)
        throw error
      }
    },

    async getOrderDetail(orderId) {
      try {
        const response = await axios.get(`/api/shipper/orders/${orderId}`)
        return response.data
      } catch (error) {
        console.error('Error fetching order detail:', error)
        throw error
      }
    },

    async updateOrderStatus(orderId, status, reason = null, customReason = null, image = null) {
      try {
        const formData = new FormData()
        formData.append('status', status)
        if (reason) formData.append('reason', reason)
        if (customReason) formData.append('custom_reason', customReason)
        if (image) formData.append('image', image)

        const response = await axios.post(`/api/shipper/orders/${orderId}/status`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        
        // Update the order in the store
        this.updateOrderInStore(response.data.order)
        
        // Show a success message
        const toast = useToast()
        toast.success(`Đơn hàng #${response.data.order.tracking_number} đã được cập nhật thành công`)
        
        return response.data
      } catch (error) {
        console.error('Error updating order status:', error)
        const toast = useToast()
        toast.error('Có lỗi xảy ra khi cập nhật trạng thái đơn hàng')
        throw error
      }
    },

    async getPostOffice(orderId) {
      try {
        const response = await axios.get(`/api/shipper/orders/${orderId}/post-office`)
        return response.data
      } catch (error) {
        console.error('Error fetching post office info:', error)
        throw error
      }
    },

    initializeRealtime() {
      console.log('Đang khởi tạo kết nối realtime')
      if (!window.Echo) {
        console.error('Echo chưa được khởi tạo')
        return
      }

      const currentToken = localStorage.getItem('token')
      if (currentToken) {
        window.Echo.connector.options.auth.headers['Authorization'] = `Bearer ${currentToken}`
      }

      this.subscribeToChannels()
    },

    subscribeToChannels() {
      console.log('Đang đăng ký các kênh')
      
      if (this.shipper && this.shipper.id) {
        window.Echo.private(`shipper.${this.shipper.id}`)
          .listen('.OrderReassigned', (e) => {
            console.log('Sự kiện OrderReassigned nhận được:', JSON.stringify(e, null, 2))
            this.removeOrder(e.order.id)
            useToast().info(`Đơn hàng #${e.order.tracking_number} đã được gán cho shipper khác`)
          })
      }
    
      window.Echo.channel('orders')
        .listen('.OrderCreated', (e) => {
          console.log('New order created:', e.order)
          this.updateOrdersRealtime(e.order)
        })
        .listen('.OrderUpdated', (e) => {
          console.log('Order updated:', e.order)
          this.updateOrdersRealtime(e.order)
        })
        .listen('.ShipperAssigned', (e) => {
          console.log('Sự kiện ShipperAssigned nhận được:', JSON.stringify(e, null, 2))
          if (this.shipper && e.shipper.id === this.shipper.id) {
            console.log('Đơn hàng mới được chỉ định cho shipper hiện tại')
            this.addNewOrder(e.order)
          } else {
            console.log('Đơn hàng được chỉ định cho shipper khác')
          }
        })
        .listen('.OrderStatusUpdated', (e) => {
          console.log('Sự kiện OrderStatusUpdated nhận được:', JSON.stringify(e, null, 2))
          this.updateOrderInStore(e.order)
        })
    
      console.log('Đã đăng ký các kênh')
    },

    removeOrder(orderId) {
      this.orders = this.orders.filter(order => order.id !== orderId)
      console.log(`Đã xóa đơn hàng có ID ${orderId} khỏi danh sách`)
    },

    addNewOrder(order) {
      console.log('Đang thêm đơn hàng mới:', order)
      const existingOrderIndex = this.orders.findIndex(o => o.id === order.id)
      if (existingOrderIndex === -1) {
        this.orders = [order, ...this.orders]
        console.log('Đã thêm đơn hàng mới vào đầu danh sách')
      } else {
        this.orders = this.orders.map(o => o.id === order.id ? order : o)
        console.log('Đã cập nhật đơn hàng hiện có')
      }
      console.log('Danh sách đơn hàng sau khi cập nhật:', this.orders)
    },

    updateOrderInStore(updatedOrder) {
      console.log('Đang cập nhật đơn hàng trong store:', updatedOrder)
      const index = this.orders.findIndex(order => order.id === updatedOrder.id)
      if (index !== -1) {
        this.orders = [
          ...this.orders.slice(0, index),
          { ...this.orders[index], ...updatedOrder },
          ...this.orders.slice(index + 1)
        ]
        console.log('Đã cập nhật đơn hàng hiện có')
      } else {
        this.orders = [...this.orders, updatedOrder]
        console.log('Đã thêm đơn hàng mới vào cuối danh sách')
      }
      console.log('Danh sách đơn hàng sau khi cập nhật:', this.orders)
    },

    updateOrdersRealtime(newOrder) {
      const existingOrderIndex = this.orders.findIndex(order => order.id === newOrder.id)
      if (existingOrderIndex !== -1) {
        this.orders = [
          ...this.orders.slice(0, existingOrderIndex),
          { ...this.orders[existingOrderIndex], ...newOrder },
          ...this.orders.slice(existingOrderIndex + 1)
        ]
      } else {
        this.orders = [newOrder, ...this.orders]  
      }
    },
    async getDeliveryOrders(page = 1, status = '', date = '') {
      try {
        const response = await axios.get('/api/shipper/deliveryorders', {
          headers: {
            'Authorization': `Bearer ${this.token}`,
            'Accept': 'application/json'
          },
          params: { page, status, date }
        });
        return response.data;
      } catch (error) {
        throw error;
      }
    },
    getCurrentPosition() {
      return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
          reject(new Error('Geolocation is not supported by this browser.'))
          return
        }
        
        navigator.geolocation.getCurrentPosition(resolve, reject, {
          enableHighAccuracy: true,
          timeout: 5000,
          maximumAge: 0
        })
      })
    }
  }
})