import { createRouter, createWebHistory } from 'vue-router'
import Dashboard from '@/pages/Dashboard.vue'
import Login from '@/pages/auth/Login.vue'
import AdminDashboard from '@/pages/AdminDashboard.vue'
import WebSocketTester from '../components/WebSocketTester.vue'
import UpdateRegister from "@/pages/auth/UpdateRegister.vue";
import SetPassword from "@/pages/auth/SetPassword.vue";
import Settings from "@/pages/user/UserSettings.vue";
import { useUserStore } from "@/stores/user";
import TransactionHistory from "@/pages/user/TransactionHistory.vue"
import Statistics from '@/pages/user/UserStatistics.vue'
import AdminData from '@/pages/admin/AdminData.vue'

let firstRouteHandler = true

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'Dashboard',
      component: Dashboard,
      meta: { adminAuth: false, vCardAuth: true, requiredAuth: true }
    },
    {
      path: '/login',
      name: 'Login',
      component: Login,
      meta: { adminAuth: false, vCardAuth: false, requiredAuth: false }
    },
    {
      path: '/settings',
      name: 'UserSettings',
      component: Settings,
      meta: { adminAuth: false, vCardAuth: true, requiredAuth: true }
    },
    {
      path: '/admin/settings',
      name: 'AdminSettings',
      component: Settings,
      meta: { adminAuth: true, vCardAuth: false, requiredAuth: true }
    },
    {
      path: '/register',
      name: 'NewUser',
      component: UpdateRegister,
      meta: { adminAuth: false, vCardAuth: false, requiredAuth: false }
    },
    {
      path: '/users',
      name: 'User',
      component: UpdateRegister,
      meta: { adminAuth: false, vCardAuth: true, requiredAuth: true }
    },
    {
      path: '/transactions',
      name: 'TransactionHistory',
      component: TransactionHistory,
      meta: { adminAuth: false, vCardAuth: true, requiredAuth: true }
    },
    {
      path: '/stats',
      name: 'UserStats',
      component: Statistics,
      meta: { adminAuth: false, vCardAuth: true, requiredAuth: true }
    },
    {
      path: '/admin/edit',
      name: 'EditAdmin',
      component: UpdateRegister,
      meta: { adminAuth: true, vCardAuth: false, requiredAuth: true }
    },
    {
      path: '/admin/dashboard',
      name: 'AdminDashboard',
      component: AdminDashboard,
      meta: { adminAuth: true, vCardAuth: false, requiredAuth: true }
    },
    {
      path: '/admin/login',
      name: 'AdminLogin',
      component: Login,
      meta: { adminAuth: false, vCardAuth: false, requiredAuth: false }
    },
    {
      path: '/admin/data',
      name: 'AdminData',
      component: AdminData,
      meta: { adminAuth: true, vCardAuth: false, requiredAuth: true }
    },
    {
      path: '/admin/set-password/:token',
      name: 'SetPassword',
      component: SetPassword,
      meta: { adminAuth: false, vCardAuth: false, requiredAuth: false }
    },

    {
      path: '/websockettester',
      name: 'WebSocketTester',
      component: WebSocketTester
    }
  ]
})

router.beforeEach(async (to, from, next) => {
  const userStore = useUserStore()
  if (firstRouteHandler) {
    firstRouteHandler = false
    await userStore.restoreToken()
  }

  if (to.meta.requiredAuth) {
    const authUser = sessionStorage.getItem('token')
    if (!authUser) {
      if (userStore.user?.user_type === 'A') {
        next({ name: 'AdminLogin' })
      } else {
        next({ name: 'Login' })
      }
    }
    else if (to.meta.adminAuth) {
      if (userStore.user?.user_type === 'A') {
        next()
      } else {
        next({ name: 'Dashboard' })
      }
    } else if (to.meta.vCardAuth) {
      if (userStore.user?.user_type === 'V') {
        next()
      } else {
        next({ name: 'AdminDashboard' })
      }
    }
  } else {
    const authUser = sessionStorage.getItem('token')
    if (authUser) {
      if (userStore.user?.user_type === 'A') {
        next({ name: 'AdminDashboard' })
      } else {
        next({ name: 'Dashboard' })
      }
    }
    next()

  }
})

export default router
