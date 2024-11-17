import { createRouter, createWebHistory } from 'vue-router'
import Layout from '../layouts/MainLayout.vue'
import Login from '../views/Login.vue'
import Dashboard from '../views/Dashboard.vue'
import Profile from '../views/Profile.vue'
import Orders from '../views/Orders.vue'
import OrderDetail from '../views/OrderDetail.vue'
import PickupOrdersManagement from '../views/PickupOrdersManagement.vue'
import DeliveryOrdersManagement from '../views/DeliveryOrdersManagement.vue'
import { useShipperStore } from '@/stores/shipper'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login
  },
  {
    path: '/',
    component: Layout,
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: Dashboard,
        meta: { requiresAuth: true }
      },
      {
        path: 'profile',
        name: 'Profile',
        component: Profile,
        meta: { requiresAuth: true }
      },
      {
        path: 'orders',
        name: 'Orders',
        component: Orders,
        meta: { requiresAuth: true }
      },
      {
        path: 'orders/:id',
        name: 'OrderDetail',
        component: OrderDetail,
        meta: { requiresAuth: true }
      },
      {
        path: 'orders/pickuporders',
        name: 'PickupOrdersManagement',
        component: PickupOrdersManagement,
        meta: { requiresAuth: true }
      },
      {
        path: 'orders/deliveryorders',
        name: 'DeliveryOrdersManagement',
        component: DeliveryOrdersManagement,
        meta: { requiresAuth: true }
      },
      {
        path: 'orders/deliveryorders/:id',
        name: 'DeliveryOrderDetail',
        component: () => import('../views/DeliveryOrderDetail.vue'),
        meta: { requiresAuth: true }
      },
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach(async (to, from, next) => {
  console.log(`Navigating to: ${to.path}`)
  const shipperStore = useShipperStore()
  
  if (to.matched.some(record => record.meta.requiresAuth)) {
    console.log('Route requires authentication')
    if (!shipperStore.isAuthenticated) {
      console.log('User is not authenticated, checking auth...')
      try {
        const isAuth = await shipperStore.checkAuth()
        if (!isAuth) {
          console.log('Auth check failed, redirecting to login')
          next('/login')
          return
        }
        console.log('Auth check passed, proceeding to route')
      } catch (error) {
        console.error('Authentication check failed:', error)
        next('/login')
        return
      }
    } else {
      console.log('User is already authenticated')
    }
  }
  next()
})

export default router