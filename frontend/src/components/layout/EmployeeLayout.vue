<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { LogOut, LayoutDashboard, CalendarClock, Plane, Lock } from 'lucide-vue-next';

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
  <div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 hidden md:flex flex-col">
      <div class="h-16 flex items-center px-6 border-b border-gray-200">
        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
          <span class="text-white font-bold text-sm">{{ authStore.user?.company?.name?.charAt(0) || 'E' }}</span>
        </div>
        <span class="font-bold text-gray-900 text-lg truncate">{{ authStore.user?.company?.name || 'Workspace' }}</span>
      </div>
      
      <div class="flex-1 py-6 px-4 space-y-1">
        <router-link to="/employee/dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-blue-50 text-blue-700 font-medium">
          <LayoutDashboard class="w-5 h-5" />
          Dashboard
        </router-link>
        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <CalendarClock class="w-5 h-5" />
          My Attendance
        </a>
        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">
          <Plane class="w-5 h-5" />
          Leave Requests
        </a>
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
        <span class="font-bold text-gray-900">{{ authStore.user?.company?.name || 'Workspace' }}</span>
        <div class="flex gap-4 items-center">
          <button @click="handleCloseApp" class="text-slate-500 hover:text-slate-800 transition-colors flex items-center gap-1 text-sm font-medium">
            <Lock class="w-5 h-5" />
          </button>
          <button @click="handleLogout" class="text-red-500 hover:text-red-700 transition-colors">
            <LogOut class="w-5 h-5" />
          </button>
        </div>
      </header>
      
      <!-- Page Content -->
      <div class="flex-1 overflow-y-auto p-4 sm:p-8">
        <router-view></router-view>
      </div>
    </main>
  </div>
</template>
