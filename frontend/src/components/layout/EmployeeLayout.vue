<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';


const router = useRouter();
const authStore = useAuthStore();

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};

const handleCloseApp = () => {
  // Simulate closing the app by clearing the session PIN verification
  authStore.pinVerified = false;
  router.push('/pin-login');
};
</script>

<template>
  <div class="h-screen bg-gray-50 flex overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 hidden md:flex flex-col">
      <div class="h-16 flex items-center px-6 border-b border-gray-200">
        <img v-if="authStore.user?.company?.logo" :src="`http://localhost:8000/storage/${authStore.user.company.logo}`" class="w-8 h-8 object-contain rounded-lg mr-3 shrink-0" alt="Company Logo" />
        <div v-else class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3 shrink-0">
          <span class="text-white font-bold text-sm">{{ authStore.user?.company?.name?.charAt(0) || 'E' }}</span>
        </div>
        <span class="font-bold text-gray-900 text-lg truncate">{{ authStore.user?.company?.name || 'Workspace' }}</span>
      </div>
      
      <div class="flex-1 py-6 px-4 space-y-1 overflow-y-auto custom-scrollbar">
        <router-link to="/employee/dashboard" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <LayoutDashboard class="w-5 h-5" />
          Dashboard
        </router-link>
        <router-link to="/employee/attendance" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <CalendarClock class="w-5 h-5" />
          My Attendance
        </router-link>
        <router-link to="/employee/roster" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <Clock class="w-5 h-5" />
          My Roster
        </router-link>
        <router-link to="/employee/id-card" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-id-card"><path d="M16 10h2"/><path d="M16 14h2"/><path d="M6.17 15a3 3 0 0 1 5.66 0"/><circle cx="9" cy="11" r="2"/><rect x="2" y="5" width="20" height="14" rx="2"/></svg>
          Virtual ID Card
        </router-link>
        <router-link to="/employee/tax-declarations" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <Receipt class="w-5 h-5" />
          Tax Declarations
        </router-link>
        <router-link to="/employee/expenses" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <Banknote class="w-5 h-5" />
          Expenses
        </router-link>
        <router-link to="/employee/konnect" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <Globe class="w-5 h-5" />
          Konnect Feed
        </router-link>
        <router-link to="/employee/leave" exact-active-class="bg-blue-50 text-blue-700" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <Plane class="w-5 h-5" />
          Leave Requests
        </router-link>
      </div>

      <div class="p-4 border-t border-gray-200">
        <div class="flex items-center gap-3 px-3 py-2 mb-2">
          <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-700 flex items-center justify-center font-bold">
            {{ authStore.user?.name?.charAt(0) || 'E' }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ authStore.user?.name }}</p>
            <p class="text-xs text-gray-500 truncate">Employee</p>
          </div>
        </div>
        <div class="flex gap-2">
          <button @click="handleCloseApp" class="flex-1 flex justify-center items-center gap-2 px-2 py-2.5 rounded-lg text-slate-700 hover:bg-slate-100 font-medium transition-colors text-sm">
            <Lock class="w-4 h-4" />
            Lock App
          </button>
          <button @click="handleLogout" class="flex-1 flex justify-center items-center gap-2 px-2 py-2.5 rounded-lg text-red-600 hover:bg-red-50 font-medium transition-colors text-sm">
            <LogOut class="w-4 h-4" />
            Sign Out
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Mobile Header -->
      <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 md:hidden">

      
      <!-- Page Content -->
      <div class="flex-1 overflow-y-auto p-4 sm:p-8">
        <router-view></router-view>
      </div>
    </main>
  </div>
</template>
