<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { LogOut, LayoutDashboard, CalendarClock, Plane } from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
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
            {{ authStore.user?.name.charAt(0) }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ authStore.user?.name }}</p>
            <p class="text-xs text-gray-500 truncate">Employee</p>
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
        <span class="font-bold text-gray-900">{{ authStore.user?.company?.name }}</span>
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
