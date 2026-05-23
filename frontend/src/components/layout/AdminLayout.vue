<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useLeaveStore } from '../../stores/leave';
import { LogOut, LayoutDashboard, Users, Calendar, Building2, Briefcase, CalendarDays, CalendarCheck, CheckSquare, Settings, Clock, Plane, ShieldAlert, Wallet, DollarSign, FileText, Banknote, Globe } from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();
const leaveStore = useLeaveStore();

const pendingLeaveCount = ref(0);

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};

const loadPendingCount = async () => {
  try {
    await leaveStore.fetchLeaves({ status: 'pending' });
    pendingLeaveCount.value = leaveStore.leaves.length;
  } catch {}
};

onMounted(() => loadPendingCount());
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 hidden md:flex flex-col">
      <div class="h-16 flex items-center px-6 border-b border-gray-200 gap-2">
        <img v-if="authStore.user?.company?.logo" :src="`http://localhost:8000/storage/${authStore.user.company.logo}`" class="w-8 h-8 object-contain rounded-lg shrink-0" alt="Company Logo" />
        <Building2 v-else class="w-6 h-6 text-blue-600 shrink-0" />
        <span class="font-bold text-gray-900 text-lg truncate">{{ authStore.user?.company?.name || 'Admin Portal' }}</span>
      </div>
      
      <div class="flex-1 py-6 px-4 space-y-1">
        <router-link to="/admin/dashboard" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <LayoutDashboard class="w-5 h-5" />
          Dashboard
        </router-link>
        <router-link to="/admin/departments" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <Building2 class="w-5 h-5" />
          Departments
        </router-link>
        <router-link to="/admin/designations" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <Briefcase class="w-5 h-5" />
          Designations
        </router-link>
        <router-link to="/admin/employees" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <Users class="w-5 h-5" />
          Employees
        </router-link>
        <router-link to="/admin/attendance" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <Calendar class="w-5 h-5" />
          Attendance
        </router-link>
        <router-link to="/admin/roster" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <Clock class="w-5 h-5" />
          Duty Roster
        </router-link>
        <router-link to="/admin/leave" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <Plane class="w-5 h-5" />
          <span class="flex-1">Leave Management</span>
          <span v-if="pendingLeaveCount > 0" class="bg-rose-500 text-white text-[10px] font-black rounded-full w-5 h-5 flex items-center justify-center">{{ pendingLeaveCount > 9 ? '9+' : pendingLeaveCount }}</span>
        </router-link>
        <router-link to="/admin/penalty-rules" exact-active-class="bg-rose-50 text-rose-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <ShieldAlert class="w-5 h-5" />
          Penalty Rules
        </router-link>
        <router-link to="/admin/salary-structures" exact-active-class="bg-emerald-50 text-emerald-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <Wallet class="w-5 h-5" />
          Salary Structures
        </router-link>
        <router-link to="/admin/payroll-run" exact-active-class="bg-emerald-50 text-emerald-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <DollarSign class="w-5 h-5" />
          Payroll Run
        </router-link>
        <router-link to="/admin/expenses" exact-active-class="bg-emerald-50 text-emerald-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <Banknote class="w-5 h-5" />
          Expense Payouts
        </router-link>
        <router-link to="/admin/konnect" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <Globe class="w-5 h-5" />
          Konnect Feed
        </router-link>
        <router-link to="/admin/reports" exact-active-class="bg-emerald-50 text-emerald-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <FileText class="w-5 h-5" />
          Reports & Challans
        </router-link>
        <router-link to="/admin/settings" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <Settings class="w-5 h-5" />
          Settings
        </router-link>
      </div>

      <div class="p-4 border-t border-gray-200">
        <div class="flex items-center gap-3 px-3 py-2 mb-2">
          <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">
            {{ authStore.user?.name.charAt(0) }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ authStore.user?.name }}</p>
            <p class="text-xs text-gray-500 truncate">{{ authStore.user?.company?.name }}</p>
          </div>
        </div>
        <button @click="handleLogout" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-red-600 hover:bg-red-50 font-medium transition-colors">
          <LogOut class="w-5 h-5" />
          Sign Out
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Mobile Header -->
      <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 md:hidden">
        <div class="flex items-center gap-2">
          <img v-if="authStore.user?.company?.logo" :src="`http://localhost:8000/storage/${authStore.user.company.logo}`" class="w-8 h-8 object-contain rounded-lg" alt="Company Logo" />
          <Building2 v-else class="w-6 h-6 text-blue-600" />
          <span class="font-bold text-gray-900 truncate max-w-[200px]">{{ authStore.user?.company?.name || 'Admin' }}</span>
        </div>
        <button @click="handleLogout" class="text-gray-500 hover:text-gray-900">
          <LogOut class="w-5 h-5" />
        </button>
      </header>
      
      <!-- Page Content -->
      <div class="flex-1 overflow-y-auto p-4 sm:p-8">
        <router-view></router-view>
      </div>
    </main>
  </div>
</template>
