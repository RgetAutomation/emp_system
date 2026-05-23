<script setup>
import { onMounted } from 'vue';
import api from './axios';
import { useAuthStore } from './stores/auth';

const authStore = useAuthStore();

onMounted(() => {
  if (authStore.token) {
    api.defaults.headers.common['Authorization'] = `Bearer ${authStore.token}`;
  }
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex justify-center w-full">
    <!-- Mobile Container Wrapper -->
    <div class="w-full max-w-md bg-white min-h-screen shadow-2xl relative overflow-hidden flex flex-col">
      <router-view></router-view>
    </div>
  </div>
</template>
