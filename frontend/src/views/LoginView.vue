<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { LockKeyhole, UserRound, Phone, ArrowRight, Eye, EyeOff, Mail } from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();

// UI State
const loginMode = ref('password'); // 'password' or 'otp'
const otpStep = ref(1); // 1: Enter Email, 2: Enter OTP
const loading = ref(false);
const errorMsg = ref('');
const successMsg = ref('');
const timer = ref(0);
let timerInterval = null;

const formatTime = (seconds) => {
  const m = Math.floor(seconds / 60).toString().padStart(2, '0');
  const s = (seconds % 60).toString().padStart(2, '0');
  return `${m}:${s}`;
};

const startTimer = () => {
  timer.value = 300;
  clearInterval(timerInterval);
  timerInterval = setInterval(() => {
    if (timer.value > 0) {
      timer.value--;
    } else {
      clearInterval(timerInterval);
    }
  }, 1000);
};

// Form Data
const employeeId = ref('');
const phone = ref('');
const password = ref('');
const showPassword = ref(false);

const email = ref('');
const otp = ref([]);
const otpInputRefs = ref([]);

onMounted(() => {
  const deviceId = localStorage.getItem('Device ID') || localStorage.getItem('device_id');
  if (deviceId) {
    loginMode.value = 'otp';
  }
});

const handlePasswordLogin = async () => {
  if (!employeeId.value || !phone.value || !password.value) {
    errorMsg.value = 'Please fill all fields';
    return;
  }
  
  errorMsg.value = '';
  loading.value = true;
  
  try {
    let deviceId = localStorage.getItem('Device ID') || localStorage.getItem('device_id');
    if (!deviceId) {
      deviceId = 'DEV-' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
      localStorage.setItem('device_id', deviceId);
    }

    const success = await authStore.employeeLogin({
      employee_id: employeeId.value,
      phone: phone.value,
      password: password.value,
      device_id: deviceId
    });
    
    if (success) {
      router.push('/employee/dashboard');
    } else {
      errorMsg.value = authStore.error || 'Login failed. Please check your credentials.';
    }
  } catch (error) {
    errorMsg.value = 'An unexpected error occurred.';
  } finally {
    loading.value = false;
  }
};

const handleSendOtp = async () => {
  if (!email.value) {
    errorMsg.value = 'Please enter your email';
    return;
  }
  errorMsg.value = '';
  successMsg.value = '';
  loading.value = true;
  
  try {
    const deviceId = localStorage.getItem('Device ID') || localStorage.getItem('device_id');
    const result = await authStore.sendOtp({ email: email.value, device_id: deviceId });
    if (result.success) {
      successMsg.value = result.message || 'OTP sent to your email!';
      otpStep.value = 2;
      startTimer();
      setTimeout(() => {
        successMsg.value = '';
      }, 4000);
    } else {
      errorMsg.value = authStore.error || 'Failed to send OTP';
    }
  } finally {
    loading.value = false;
  }
};

const handleVerifyOtp = async () => {
  const code = otp.value.join('');
  if (code.length !== 6) return;
  
  errorMsg.value = '';
  loading.value = true;
  
  try {
    const deviceId = localStorage.getItem('Device ID') || localStorage.getItem('device_id');
    const success = await authStore.verifyOtp({ email: email.value, otp: code, device_id: deviceId });
    if (success) {
      router.push('/employee/dashboard');
    } else {
      errorMsg.value = authStore.error || 'Invalid OTP';
    }
  } finally {
    loading.value = false;
  }
};

const handleOtpInput = (e, index) => {
  const val = e.target.value;
  if (/[0-9]/.test(val)) {
    otp.value[index] = val;
    if (index < 5 && otpInputRefs.value[index + 1]) {
      otpInputRefs.value[index + 1].focus();
    }
    if (otp.value.join('').length === 6) {
      handleVerifyOtp();
    }
  } else {
    otp.value[index] = '';
    e.target.value = '';
  }
};

const handleOtpKeydown = (e, index) => {
  if (e.key === 'Backspace' && !otp.value[index] && index > 0) {
    otpInputRefs.value[index - 1].focus();
    otp.value[index - 1] = '';
  }
};
</script>

<template>
  <div class="min-h-[100dvh] flex flex-col bg-emerald-50/30 overflow-y-auto">
    <!-- Header Graphic Area -->
    <div class="h-[35dvh] min-h-[220px] bg-primary relative overflow-hidden rounded-b-[40px] shadow-lg flex-shrink-0 z-0">
      <!-- Decorative circles -->
      <div class="absolute -top-16 -right-16 w-48 h-48 bg-white opacity-10 rounded-full blur-xl"></div>
      <div class="absolute top-16 -left-16 w-32 h-32 bg-white opacity-10 rounded-full blur-xl"></div>
      
      <div class="absolute inset-0 flex flex-col items-center justify-center pb-8 text-white px-6">
        <h1 class="text-3xl font-bold tracking-tight mb-2">Welcome Back</h1>
        <p class="text-primary-foreground/80 text-center text-sm font-medium">Log in to manage your profile</p>
      </div>
    </div>

    <!-- Login Form Area -->
    <div class="flex-1 px-6 -mt-16 z-10 flex flex-col justify-start pb-12">
      <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-6 text-center">Employee Login</h2>
        
        <!-- OTP Login Mode -->
        <div v-if="loginMode === 'otp'">
          
          <div v-if="otpStep === 1" class="space-y-4">
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                <Mail class="w-5 h-5" />
              </div>
              <input 
                v-model="email"
                type="email" 
                class="w-full pl-12 pr-4 py-4 bg-slate-100 border-none rounded-2xl focus:bg-slate-200 focus:ring-0 transition-all outline-none text-slate-800 placeholder:text-slate-400 text-base"
                placeholder="Registered Email Address"
                required
              />
            </div>
            <button 
              @click="handleSendOtp"
              :disabled="loading"
              class="w-full bg-primary hover:bg-primary/90 text-white font-semibold py-4 px-4 rounded-2xl shadow-lg shadow-primary/25 transition-all flex items-center justify-center gap-2 active:scale-[0.98] disabled:opacity-70 disabled:active:scale-100 text-lg mt-6"
            >
              <span v-if="!loading">Send OTP Code</span>
              <span v-else>Sending...</span>
            </button>
          </div>
          <!-- Error / Success Messages (Floating) -->
          <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-500 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
          >
            <div v-if="errorMsg" class="fixed top-8 left-1/2 -translate-x-1/2 z-[100] bg-white border-l-4 border-red-500 text-red-700 p-4 rounded-xl text-sm flex items-center gap-3 shadow-2xl w-11/12 max-w-sm">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <span class="font-medium">{{ errorMsg }}</span>
            </div>
          </Transition>

          <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-500 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
          >
            <div v-if="successMsg" class="fixed top-8 left-1/2 -translate-x-1/2 z-[100] bg-white border-l-4 border-green-500 text-green-700 p-4 rounded-xl text-sm flex items-center gap-3 shadow-2xl w-11/12 max-w-sm">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
              <span class="font-medium">{{ successMsg }}</span>
            </div>
          </Transition>

          <div v-if="otpStep === 2" class="space-y-4 flex flex-col items-center">
            <p class="text-sm text-gray-500 mb-6 text-center leading-relaxed">
              6-digit verification code sent to<br>
              <strong class="text-gray-900 font-semibold">{{ email }}</strong>
            </p>
            <div class="flex gap-2 justify-center mb-4">
              <input 
                v-for="(n, index) in 6" 
                :key="n"
                type="text" 
                inputmode="numeric" 
                pattern="[0-9]*"
                maxlength="1"
                :value="otp[index] || ''"
                @input="handleOtpInput($event, index)"
                @keydown="handleOtpKeydown($event, index)"
                :ref="el => { if (el) otpInputRefs[index] = el }"
                class="w-10 sm:w-12 h-12 sm:h-14 text-center text-xl sm:text-2xl font-bold text-gray-900 bg-slate-50 border-2 border-gray-200 rounded-xl focus:border-primary focus:bg-white focus:ring-0 transition-all shadow-sm outline-none"
              />
            </div>
            <button 
              @click="handleVerifyOtp"
              :disabled="loading || timer === 0"
              class="w-full bg-primary hover:bg-primary/90 text-white font-semibold py-4 px-4 rounded-2xl shadow-lg shadow-primary/25 transition-all flex items-center justify-center gap-2 active:scale-[0.98] disabled:opacity-70 disabled:active:scale-100 text-lg mt-2"
            >
              <span v-if="!loading">Verify & Login</span>
              <span v-else>Verifying...</span>
            </button>
            
            <div class="flex flex-col items-center mt-6 w-full">
              <p v-if="timer > 0" class="text-sm font-medium text-gray-500 mb-3">
                OTP expires in <span class="text-red-500 font-bold">{{ formatTime(timer) }}</span>
              </p>
              <p v-else class="text-sm font-bold text-red-500 mb-3">
                OTP Expired
              </p>
              
              <button 
                @click="handleSendOtp" 
                :disabled="timer > 0 || loading"
                class="text-sm font-semibold text-primary hover:text-primary/80 hover:underline disabled:opacity-50 disabled:hover:no-underline transition-all mt-2"
              >
                Resend OTP
              </button>
            </div>
          </div>
          
          <div class="mt-8 pt-6 border-t border-gray-100 text-center">
            <button @click="loginMode = 'password'" class="text-sm text-gray-500 hover:text-gray-900 font-medium transition-colors">
              Login with Password instead
            </button>
          </div>

        </div>

        <!-- Password Login Mode -->
        <form v-else @submit.prevent="handlePasswordLogin" class="space-y-4">
          <!-- Employee ID -->
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
              <UserRound class="w-5 h-5" />
            </div>
            <input 
              v-model="employeeId"
              type="text" 
              class="w-full pl-12 pr-4 py-4 bg-slate-100 border-none rounded-2xl focus:bg-slate-200 focus:ring-0 transition-all outline-none text-slate-800 placeholder:text-slate-400 text-base"
              placeholder="Employee ID (e.g. EMP-001)"
              required
            />
          </div>

          <!-- Mobile Number -->
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
              <Phone class="w-5 h-5" />
            </div>
            <input 
              v-model="phone"
              type="tel" 
              class="w-full pl-12 pr-4 py-4 bg-slate-100 border-none rounded-2xl focus:bg-slate-200 focus:ring-0 transition-all outline-none text-slate-800 placeholder:text-slate-400 text-base"
              placeholder="Registered Mobile Number"
              required
            />
          </div>

          <!-- Password -->
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
              <LockKeyhole class="w-5 h-5" />
            </div>
            <input 
              v-model="password"
              :type="showPassword ? 'text' : 'password'" 
              class="w-full pl-12 pr-12 py-4 bg-slate-100 border-none rounded-2xl focus:bg-slate-200 focus:ring-0 transition-all outline-none text-slate-800 placeholder:text-slate-400 text-base"
              placeholder="Temporary Password"
              required
            />
            <button 
              type="button"
              @click="showPassword = !showPassword"
              class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
            >
              <EyeOff v-if="showPassword" class="w-5 h-5" />
              <Eye v-else class="w-5 h-5" />
            </button>
          </div>

          <div class="pt-6">
            <button 
              type="submit" 
              :disabled="loading"
              class="w-full bg-primary hover:bg-primary/90 text-white font-semibold py-4 px-4 rounded-2xl shadow-lg shadow-primary/25 transition-all flex items-center justify-center gap-2 active:scale-[0.98] disabled:opacity-70 disabled:active:scale-100 text-lg"
            >
              <span v-if="!loading">Sign In</span>
              <span v-else class="flex items-center gap-2">
                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Authenticating...
              </span>
              <ArrowRight v-if="!loading" class="w-5 h-5 ml-1" />
            </button>
          </div>
          
          <div class="mt-4 text-center">
            <button type="button" @click="loginMode = 'otp'" class="text-sm text-gray-500 hover:text-gray-900 font-medium transition-colors">
              Login with Email OTP instead
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
