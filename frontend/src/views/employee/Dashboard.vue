<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { Clock, CheckCircle2, MapPin, Camera, ShieldCheck, Lock } from 'lucide-vue-next';

const authStore = useAuthStore();
const isCheckedIn = ref(false);
const currentTime = ref(new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));

// Update time every minute
setInterval(() => {
  currentTime.value = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}, 60000);

const toggleAttendance = () => {
  isCheckedIn.value = !isCheckedIn.value;
};

// Permissions Setup Logic
const showPermissionSetup = ref(false);
const permissionLoading = ref(false);
const permissionError = ref('');

// PIN Setup Logic
const showPinSetup = ref(false);
const setupPin = ref('');
const confirmPin = ref('');
const pinStep = ref(1); // 1 = enter, 2 = confirm
const pinError = ref('');
const pinLoading = ref(false);
const pinInputRefs = ref([]);

const handlePinInput = (event, index) => {
  const value = event.target.value;
  // Ensure only numeric values
  if (!/^\d*$/.test(value)) {
    event.target.value = '';
    return;
  }
  
  // Update the correct pin state
  const currentPinStr = pinStep.value === 1 ? setupPin.value : confirmPin.value;
  let newPin = currentPinStr.split('');
  newPin[index] = value;
  const newPinStr = newPin.join('');
  
  if (pinStep.value === 1) {
    setupPin.value = newPinStr;
  } else {
    confirmPin.value = newPinStr;
  }
  
  pinError.value = '';

  // Auto-advance focus
  if (value && index < 3) {
    pinInputRefs.value[index + 1]?.focus();
  }
  
  // If complete
  if (newPinStr.length === 4 && !newPinStr.includes(undefined)) {
    if (pinStep.value === 1) {
      setTimeout(() => {
        pinStep.value = 2;
        // Focus first input of confirm step
        pinInputRefs.value[0]?.focus();
      }, 300);
    } else {
      submitPinSetup();
    }
  }
};

const handlePinKeydown = (event, index) => {
  // Handle backspace to delete and focus previous
  if (event.key === 'Backspace') {
    const currentPinStr = pinStep.value === 1 ? setupPin.value : confirmPin.value;
    if (!currentPinStr[index] && index > 0) {
      // Focus previous and clear it
      pinInputRefs.value[index - 1]?.focus();
      // The actual deletion of the value will happen naturally or can be forced:
      let newPin = currentPinStr.split('');
      newPin[index - 1] = '';
      if (pinStep.value === 1) setupPin.value = newPin.join('');
      else confirmPin.value = newPin.join('');
    }
  }
};

onMounted(() => {
  if (authStore.user && authStore.user.has_pin === false) {
    showPinSetup.value = true;
  } else if (localStorage.getItem('permissions_granted') !== 'true') {
    showPermissionSetup.value = true;
  }
});

const appendPinSetup = (num) => {
  if (pinStep.value === 1 && setupPin.value.length < 4) {
    setupPin.value += num;
    pinError.value = '';
    if (setupPin.value.length === 4) {
      setTimeout(() => { pinStep.value = 2; }, 300);
    }
  } else if (pinStep.value === 2 && confirmPin.value.length < 4) {
    confirmPin.value += num;
    pinError.value = '';
    if (confirmPin.value.length === 4) {
      submitPinSetup();
    }
  }
};

const deletePinSetup = () => {
  if (pinStep.value === 1) {
    setupPin.value = setupPin.value.slice(0, -1);
  } else {
    confirmPin.value = confirmPin.value.slice(0, -1);
  }
  pinError.value = '';
};

const submitPinSetup = async () => {
  if (setupPin.value !== confirmPin.value) {
    pinError.value = "PINs do not match. Try again.";
    setupPin.value = '';
    confirmPin.value = '';
    pinStep.value = 1;
    return;
  }
  
  pinLoading.value = true;
  try {
    await authStore.setPin(setupPin.value);
    showPinSetup.value = false;
    
    // Move on to permissions if needed
    if (localStorage.getItem('permissions_granted') !== 'true') {
      showPermissionSetup.value = true;
    }
  } catch (err) {
    pinError.value = err.response?.data?.message || "Failed to save PIN";
    setupPin.value = '';
    confirmPin.value = '';
    pinStep.value = 1;
  } finally {
    pinLoading.value = false;
  }
};

const requestPermissions = async () => {
  permissionLoading.value = true;
  permissionError.value = '';
  
  try {
    // 1. Request Camera Permission
    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    stream.getTracks().forEach(track => track.stop()); // Stop the stream immediately

    // 2. Request Location Permission
    await new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject, { 
        enableHighAccuracy: true,
        timeout: 10000 
      });
    });

    // Both permissions granted successfully
    localStorage.setItem('permissions_granted', 'true');
    showPermissionSetup.value = false;
  } catch (error) {
    console.error('Permission error:', error);
    permissionError.value = "Please allow both Camera and Location access to use the attendance system. You may need to check your browser settings.";
  } finally {
    permissionLoading.value = false;
  }
};
</script>

<template>
  <div class="max-w-4xl mx-auto">
    <!-- PIN Setup Overlay (First Time Only) -->
    <div v-if="showPinSetup" class="fixed inset-0 bg-slate-50 z-[110] flex flex-col items-center justify-center p-6 sm:p-8">
      <div class="w-full max-w-sm bg-white rounded-[32px] shadow-xl p-8 flex flex-col items-center text-center">
        <div class="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mb-6 relative">
          <Lock class="w-10 h-10 text-indigo-600 absolute" />
        </div>
        
        <h2 class="text-2xl font-bold text-gray-900 mb-2">
          {{ pinStep === 1 ? 'Set App PIN' : 'Confirm PIN' }}
        </h2>
        <p class="text-gray-500 mb-8 text-sm">
          {{ pinStep === 1 ? 'Create a 4-digit PIN for quick login.' : 'Please enter the same PIN to confirm.' }}
        </p>
        
        <!-- Native PIN Inputs -->
        <div class="flex gap-3 justify-center mb-8">
          <input 
            v-for="(n, index) in 4" 
            :key="n"
            type="password" 
            inputmode="numeric" 
            pattern="[0-9]*"
            maxlength="1"
            :value="(pinStep === 1 ? setupPin : confirmPin)[index] || ''"
            @input="handlePinInput($event, index)"
            @keydown="handlePinKeydown($event, index)"
            :ref="el => { if (el) pinInputRefs[index] = el }"
            class="w-14 h-16 text-center text-3xl font-bold text-gray-900 bg-slate-50 border-2 border-gray-200 rounded-2xl focus:border-indigo-600 focus:ring-0 transition-colors shadow-sm"
          />
        </div>
        
        <p v-if="pinError" class="text-red-500 text-sm mb-4 font-medium">{{ pinError }}</p>
        
        <div v-if="pinLoading" class="text-sm text-indigo-600 font-medium animate-pulse">
          Saving...
        </div>
      </div>
    </div>

    <!-- Permissions Overlay (One-Time Setup) -->
    <div v-if="showPermissionSetup && !showPinSetup" class="fixed inset-0 bg-slate-50 z-[100] flex flex-col items-center justify-center p-6 sm:p-8">
      <div class="w-full max-w-sm bg-white rounded-[32px] shadow-xl p-8 flex flex-col items-center text-center">
        <div class="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center mb-6 shadow-inner relative">
          <ShieldCheck class="w-12 h-12 text-blue-600 absolute" />
        </div>
        
        <h2 class="text-2xl font-bold text-gray-900 mb-3">App Setup</h2>
        <p class="text-gray-500 mb-8 text-sm">To verify your attendance, the app requires one-time access to your camera and GPS location.</p>
        
        <div class="w-full space-y-4 mb-8">
          <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl">
            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm shrink-0">
              <MapPin class="w-5 h-5 text-emerald-500" />
            </div>
            <div class="text-left">
              <h3 class="text-sm font-semibold text-gray-900">GPS Location</h3>
              <p class="text-xs text-gray-500">To verify you are at the workplace</p>
            </div>
          </div>
          
          <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl">
            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm shrink-0">
              <Camera class="w-5 h-5 text-indigo-500" />
            </div>
            <div class="text-left">
              <h3 class="text-sm font-semibold text-gray-900">Camera Access</h3>
              <p class="text-xs text-gray-500">To take a live selfie for attendance</p>
            </div>
          </div>
        </div>

        <p v-if="permissionError" class="text-red-500 text-xs mb-4">{{ permissionError }}</p>

        <button 
          @click="requestPermissions"
          :disabled="permissionLoading"
          class="w-full bg-primary hover:bg-primary/90 text-white font-semibold py-4 rounded-2xl shadow-lg shadow-primary/30 transition-all flex items-center justify-center gap-2 active:scale-95"
        >
          <span v-if="!permissionLoading">Allow Access</span>
          <span v-else class="flex items-center gap-2">
            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Requesting...
          </span>
        </button>
      </div>
    </div>

    <div class="mb-8">
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Good {{ new Date().getHours() < 12 ? 'Morning' : 'Afternoon' }}, {{ authStore.user?.name }}!</h1>
      <p class="text-gray-500 mt-1">Here is your overview for today.</p>
    </div>

    <!-- Attendance Card -->
    <div class="bg-white rounded-3xl p-8 sm:p-10 border border-gray-200 shadow-sm mb-8 flex flex-col md:flex-row items-center justify-between gap-8 relative overflow-hidden">
      <!-- Decorative background -->
      <div class="absolute right-0 top-0 w-64 h-64 bg-blue-50 rounded-full translate-x-1/2 -translate-y-1/2 opacity-50"></div>
      
      <div class="relative z-10 text-center md:text-left">
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Today's Attendance</h2>
        <p class="text-gray-500 mb-6">Mark your attendance for {{ new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'short', day: 'numeric' }) }}</p>
        
        <div class="flex items-center justify-center md:justify-start gap-3">
          <div class="text-4xl font-bold tracking-tight text-gray-900">{{ currentTime }}</div>
        </div>
      </div>

      <div class="relative z-10">
        <button 
          @click="toggleAttendance"
          :class="[
            'relative overflow-hidden group w-48 h-48 rounded-full flex flex-col items-center justify-center transition-all duration-500 shadow-xl',
            isCheckedIn 
              ? 'bg-gradient-to-br from-red-500 to-rose-600 shadow-red-500/30 text-white' 
              : 'bg-gradient-to-br from-blue-600 to-indigo-600 shadow-blue-600/30 text-white'
          ]"
        >
          <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity"></div>
          
          <Clock v-if="!isCheckedIn" class="w-10 h-10 mb-2 group-hover:scale-110 transition-transform duration-300" />
          <CheckCircle2 v-else class="w-10 h-10 mb-2 group-hover:scale-110 transition-transform duration-300" />
          
          <span class="text-xl font-bold tracking-wide">{{ isCheckedIn ? 'CHECK OUT' : 'CHECK IN' }}</span>
        </button>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Recent Activity</h2>
      </div>
      <div class="p-6 flex flex-col items-center justify-center text-center py-12">
        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
          <Clock class="w-8 h-8 text-gray-400" />
        </div>
        <h3 class="text-gray-900 font-medium">No recent activity</h3>
        <p class="text-gray-500 text-sm mt-1">Your attendance logs and leave requests will appear here.</p>
      </div>
    </div>
  </div>
</template>
