import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/auth/Login.vue'),
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
      { path: 'dashboard', name: 'AdminDashboard', component: () => import('../views/admin/Dashboard.vue') },
      { path: 'departments', name: 'AdminDepartments', component: () => import('../views/admin/Departments.vue') },
      { path: 'designations', name: 'AdminDesignations', component: () => import('../views/admin/Designations.vue') },
      { path: 'employees', name: 'AdminEmployees', component: () => import('../views/admin/Employees.vue') }
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

router.beforeEach((to, from) => {
  const authStore = useAuthStore();
  const isAuthenticated = authStore.isAuthenticated;
  const userRole = authStore.user?.role;

  if (to.meta.requiresAuth && !isAuthenticated) {
    return '/login';
  }

  if (to.meta.guest && isAuthenticated) {
    return userRole === 'admin' ? '/admin/dashboard' : '/employee/dashboard';
  }

  if (to.meta.requiresAuth && to.meta.role && to.meta.role !== userRole) {
    // If an employee tries to access admin routes, or vice versa
    return userRole === 'admin' ? '/admin/dashboard' : '/employee/dashboard';
  }
});

export default router;
