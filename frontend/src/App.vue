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
  <router-view></router-view>
</template>
