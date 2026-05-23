<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { Lock, LogOut } from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();

const pin = ref('');
const errorMsg = ref('');
const loading = ref(false);
const employeeId = ref('');
const deviceId = ref('');
const userName = ref('');
const pinInputRefs = ref([]);

onMounted(() => {
  employeeId.value = localStorage.getItem('Employee ID');
  deviceId.value = localStorage.getItem('Device ID');
  userName.value = authStore.user?.name || employeeId.value;
  
  if (!employeeId.value || !deviceId.value) {
    router.push('/login');
  }
});

const handlePinInput = (event, index) => {
  const value = event.target.value;
  if (!/^\d*$/.test(value)) {
    event.target.value = '';
    return;
  }
  
  let newPin = pin.value.split('');
  newPin[index] = value;
  pin.value = newPin.join('');
  errorMsg.value = '';

  if (value && index < 3) {
    pinInputRefs.value[index + 1]?.focus();
  }
  
  if (pin.value.length === 4 && !pin.value.includes(undefined)) {
    submitPin();
  }
};

const handlePinKeydown = (event, index) => {
  if (event.key === 'Backspace') {
    if (!pin.value[index] && index > 0) {
      pinInputRefs.value[index - 1]?.focus();
      let newPin = pin.value.split('');
      newPin[index - 1] = '';
      pin.value = newPin.join('');
    }
  }
};

const submitPin = async () => {
  if (pin.value.length !== 4) return;
  
  loading.value = true;
  const success = await authStore.pinLogin({
    employee_id: employeeId.value,
    device_id: deviceId.value,
    pin: pin.value
  });
  
  if (success) {
    router.push('/employee/dashboard');
  } else {
    errorMsg.value = authStore.error || 'Invalid PIN';
    pin.value = ''; 
    pinInputRefs.value[0]?.focus();
  }
  loading.value = false;
};

const logout = () => {
  localStorage.removeItem('Employee ID');
  localStorage.removeItem('Device ID');
  router.push('/login');
};
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="w-full max-w-sm bg-white rounded-[32px] shadow-xl p-8 flex flex-col items-center">
      
      <h2 class="text-2xl font-bold text-gray-900 mb-2">Enter App PIN</h2>
      <p class="text-gray-500 mb-8 text-sm">Welcome back, {{ userName }}</p>
      
      <!-- Native PIN Inputs -->
      <div class="flex gap-3 justify-center mb-8">
        <input 
          v-for="(n, index) in 4" 
          :key="n"
          type="password" 
          inputmode="numeric" 
          pattern="[0-9]*"
          maxlength="1"
          :value="pin[index] || ''"
          @input="handlePinInput($event, index)"
          @keydown="handlePinKeydown($event, index)"
          :ref="el => { if (el) pinInputRefs[index] = el }"
          class="w-14 h-16 text-center text-3xl font-bold text-gray-900 bg-slate-50 border-2 border-gray-200 rounded-2xl focus:border-blue-600 focus:ring-0 transition-colors shadow-sm"
        />
      </div>
      
      <p v-if="errorMsg" class="text-red-500 text-sm mb-4 font-medium">{{ errorMsg }}</p>
      
      <div v-if="loading" class="mt-4 text-sm text-blue-600 font-medium animate-pulse">
        Verifying...
      </div>
    </div>
  </div>
</template>
