<script setup>
import { onMounted, computed, ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useAdminStore } from '../../stores/admin';
import { Users, UserPlus, Building, Briefcase } from 'lucide-vue-next';

const authStore = useAuthStore();
const adminStore = useAdminStore();

const loading = ref(true);

onMounted(async () => {
  try {
    await Promise.all([
      adminStore.fetchEmployees(),
      adminStore.fetchDepartments()
    ]);
  } catch (error) {
    console.error('Failed to load dashboard stats:', error);
  } finally {
    loading.value = false;
  }
});

const totalEmployees = computed(() => adminStore.employees.length);
const totalDepartments = computed(() => adminStore.departments.length);

// Default to 0 until the attendance and leaves modules are completed
const presentToday = computed(() => 0);
const onLeave = computed(() => 0);
</script>

<template>
  <div class="max-w-7xl mx-auto">
    <div class="mb-8">
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Welcome back, {{ authStore.user?.name }} 👋</h1>
      <p class="text-gray-500 mt-1">Here is what's happening at {{ authStore.user?.company?.name }} today.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
      <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
            <Users class="w-6 h-6" />
          </div>
        </div>
        <h3 class="text-gray-500 text-sm font-medium">Total Employees</h3>
        <p class="text-3xl font-bold text-gray-900 mt-1">
          <span v-if="loading" class="text-gray-300">...</span>
          <span v-else>{{ totalEmployees }}</span>
        </p>
      </div>
      
      <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center">
            <UserPlus class="w-6 h-6" />
          </div>
          <span class="text-sm font-medium text-gray-500 bg-gray-50 px-2 py-1 rounded-md">Today</span>
        </div>
        <h3 class="text-gray-500 text-sm font-medium">Present Today</h3>
        <p class="text-3xl font-bold text-gray-900 mt-1">
          <span v-if="loading" class="text-gray-300">...</span>
          <span v-else>{{ presentToday }}</span>
        </p>
      </div>
      
      <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center">
            <Briefcase class="w-6 h-6" />
          </div>
        </div>
        <h3 class="text-gray-500 text-sm font-medium">On Leave</h3>
        <p class="text-3xl font-bold text-gray-900 mt-1">
          <span v-if="loading" class="text-gray-300">...</span>
          <span v-else>{{ onLeave }}</span>
        </p>
      </div>
      
      <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
            <Building class="w-6 h-6" />
          </div>
        </div>
        <h3 class="text-gray-500 text-sm font-medium">Departments</h3>
        <p class="text-3xl font-bold text-gray-900 mt-1">
          <span v-if="loading" class="text-gray-300">...</span>
          <span v-else>{{ totalDepartments }}</span>
        </p>
      </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
      </div>
      <div class="p-6">
        <p class="text-gray-500">More administrative features like adding employees, managing departments, and payroll will be built here.</p>
      </div>
    </div>
  </div>
</template>
