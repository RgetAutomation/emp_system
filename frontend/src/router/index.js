import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
  {
    path: '/',
    redirect: () => {
      const deviceId = localStorage.getItem('Device ID');
      return deviceId ? '/pin-login' : '/login';
    }
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/LoginView.vue'),
    meta: { guest: true }
  },
  {
    path: '/pin-login',
    name: 'PinLogin',
    component: () => import('../views/PinLoginView.vue'),
    meta: { guest: true }
  },
  {
    path: '/register',
    name: 'RegisterCompany',
    component: () => import('../views/auth/RegisterCompany.vue'),
    meta: { guest: true }
  },
  {
    path: '/admin',
    component: () => import('../components/layout/AdminLayout.vue'),
    meta: { requiresAuth: true, role: 'admin' },
    children: [
      { path: 'dashboard', name: 'AdminDashboard', component: () => import('../views/admin/Dashboard.vue') }
    ]
  },
  {
    path: '/employee',
    component: () => import('../components/layout/EmployeeLayout.vue'),
    meta: { requiresAuth: true, role: 'employee' },
    children: [
      { path: 'dashboard', name: 'EmployeeDashboard', component: () => import('../views/employee/Dashboard.vue') }
    ]
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  const isAuthenticated = authStore.isAuthenticated;
  const userRole = authStore.user?.role;
  const pinVerified = authStore.isPinVerified;
  const hasDeviceId = !!localStorage.getItem('Device ID');

  // 1. If authenticated but PIN not verified, lock them to pin-login
  if (isAuthenticated && !pinVerified) {
    if (hasDeviceId) {
      if (to.path !== '/pin-login') {
        return next('/pin-login');
      }
      return next(); // Allow them to stay on pin-login
    } else {
      if (to.path !== '/login') {
        return next('/login');
      }
      return next();
    }
  }

  // 2. Unauthenticated user trying to access secure routes
  if (to.meta.requiresAuth && !isAuthenticated) {
    return next('/login');
  }

  // 3. Authenticated (and pin verified) trying to access guest routes
  if (to.meta.guest && isAuthenticated) {
    return next(userRole === 'admin' ? '/admin/dashboard' : '/employee/dashboard');
  }

  // 4. Role checking
  if (to.meta.requiresAuth && to.meta.role && to.meta.role !== userRole) {
    return next(userRole === 'admin' ? '/admin/dashboard' : '/employee/dashboard');
  }

  // 5. Allow access
  next();
});

export default router;
