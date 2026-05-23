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
      { path: 'dashboard', name: 'AdminDashboard', component: () => import('../views/admin/Dashboard.vue') },
      { path: 'departments', name: 'AdminDepartments', component: () => import('../views/admin/Departments.vue') },
      { path: 'designations', name: 'AdminDesignations', component: () => import('../views/admin/Designations.vue') },
      { path: 'employees', name: 'AdminEmployees', component: () => import('../views/admin/Employees.vue') },
      { path: 'attendance', name: 'AdminAttendance', component: () => import('../views/admin/Attendance.vue') },
      { path: 'roster', name: 'AdminRoster', component: () => import('../views/admin/Roster.vue') },
      { path: 'leave', name: 'AdminLeave', component: () => import('../views/admin/Leave.vue') },
      { path: 'leave-structures', name: 'AdminLeaveStructures', component: () => import('../views/admin/LeaveStructures.vue') },
      { path: 'penalty-rules', name: 'AdminPenaltyRules', component: () => import('../views/admin/PenaltyRules.vue') },
      { path: 'salary-structures', name: 'AdminSalaryStructures', component: () => import('../views/admin/SalaryStructures.vue') },
      { path: 'payroll-run', name: 'AdminPayrollRun', component: () => import('../views/admin/PayrollRun.vue') },
      { path: 'expenses', name: 'AdminExpenseManagement', component: () => import('../views/admin/ExpenseManagement.vue') },
      { path: 'reports', name: 'AdminReports', component: () => import('../views/admin/Reports.vue') },
      { path: 'konnect', name: 'AdminKonnect', component: () => import('../views/shared/Konnect.vue') },
      { path: 'settings', name: 'AdminSettings', component: () => import('../views/admin/Settings.vue') }
    ]
  },
  {
    path: '/employee',
    component: () => import('../components/layout/EmployeeLayout.vue'),
    meta: { requiresAuth: true, role: 'employee' },
    children: [
      { path: 'dashboard', name: 'EmployeeDashboard', component: () => import('../views/employee/Dashboard.vue') },
      { path: 'attendance', name: 'EmployeeAttendance', component: () => import('../views/employee/Attendance.vue') },
      { path: 'id-card', name: 'EmployeeIdCard', component: () => import('../views/employee/IdCard.vue') },
      { path: 'tax-declarations', name: 'EmployeeTaxDeclarations', component: () => import('../views/employee/TaxDeclarations.vue') },
      { path: 'expenses', name: 'EmployeeExpenses', component: () => import('../views/employee/Expenses.vue') },
      { path: 'konnect', name: 'EmployeeKonnect', component: () => import('../views/shared/Konnect.vue') },
      { path: 'roster', name: 'EmployeeRoster', component: () => import('../views/employee/Roster.vue') },
      { path: 'leave', name: 'EmployeeLeave', component: () => import('../views/employee/Leave.vue') }
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
    return '/login';
  }

  // 3. Authenticated (and pin verified) trying to access guest routes
  if (to.meta.guest && isAuthenticated) {
    return userRole === 'admin' ? '/admin/dashboard' : '/employee/dashboard';
  }

  // 4. Role checking
  if (to.meta.requiresAuth && to.meta.role && to.meta.role !== userRole) {

});

export default router;
