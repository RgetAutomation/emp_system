<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { Mail, Lock, ArrowRight, LayoutDashboard, Eye, EyeOff } from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();

const form = ref({
  email: '',
  password: ''
});

const showPassword = ref(false);

const error = ref('');
const loading = ref(false);

const handleLogin = async () => {
  loading.value = true;
  error.value = '';
  
  try {
    await authStore.login(form.value);
    if (authStore.isAdmin) {
      router.push('/admin/dashboard');
    } else {
      router.push('/employee/dashboard');
    }
  } catch (err) {
    console.error('Login error:', err);
    error.value = err.response?.data?.message || err.message || 'Invalid credentials. Please try again.';
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center p-6 relative bg-[#f8fafc] overflow-hidden">
    <!-- Abstract Background -->
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-blue-400 mix-blend-multiply filter blur-[100px] opacity-30 animate-blob"></div>
    <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-purple-400 mix-blend-multiply filter blur-[100px] opacity-30 animate-blob animation-delay-2000"></div>
    <div class="absolute bottom-[-20%] left-[20%] w-[40%] h-[40%] rounded-full bg-pink-400 mix-blend-multiply filter blur-[100px] opacity-30 animate-blob animation-delay-4000"></div>

    <div class="w-full max-w-md relative z-10">
      <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/50 p-8 sm:p-10">
        
        <div class="flex justify-center mb-8">
          <div class="h-14 w-14 bg-gradient-to-tr from-blue-600 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/30">
            <LayoutDashboard class="text-white w-7 h-7" />
          </div>
        </div>
        
        <div class="text-center mb-10">
          <h1 class="text-3xl font-bold text-gray-900 tracking-tight mb-2">Welcome back</h1>
          <p class="text-gray-500">Enter your details to access your dashboard.</p>
        </div>

        <div v-if="error" class="mb-6 p-4 rounded-xl bg-red-50/80 backdrop-blur-sm text-red-600 border border-red-100 flex items-start gap-3">
          <p class="text-sm font-medium">{{ error }}</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5 ml-1">Email address</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <Mail class="h-5 w-5 text-gray-400" />
              </div>
              <input v-model="form.email" type="email" required autocomplete="username" class="block w-full pl-11 pr-4 py-3.5 bg-white/50 backdrop-blur-sm border border-gray-200/80 rounded-2xl text-gray-900 focus:ring-2 focus:ring-blue-600/50 focus:border-blue-600 transition-all outline-none placeholder-gray-400" placeholder="name@company.com" />
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between mb-1.5 ml-1">
              <label class="block text-sm font-medium text-gray-700">Password</label>
              <a href="#" class="text-sm font-medium text-blue-600 hover:text-blue-500">Forgot password?</a>
            </div>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <Lock class="h-5 w-5 text-gray-400" />
              </div>
              <input v-model="form.password" :type="showPassword ? 'text' : 'password'" required autocomplete="current-password" class="block w-full pl-11 pr-12 py-3.5 bg-white/50 backdrop-blur-sm border border-gray-200/80 rounded-2xl text-gray-900 focus:ring-2 focus:ring-blue-600/50 focus:border-blue-600 transition-all outline-none placeholder-gray-400" placeholder="••••••••" />
              <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                <Eye v-if="!showPassword" class="h-5 w-5" />
                <EyeOff v-else class="h-5 w-5" />
              </button>
            </div>
          </div>

          <button type="submit" :disabled="loading" class="w-full flex items-center justify-center gap-2 py-3.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-2xl transition-all disabled:opacity-70 disabled:cursor-not-allowed mt-6 shadow-xl shadow-blue-500/20">
            <span v-if="loading">Signing in...</span>
            <template v-else>
              <span>Sign in to workspace</span>
              <ArrowRight class="w-4 h-4" />
            </template>
          </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
          <p class="text-gray-500 text-sm">
            Company not registered yet? 
            <router-link to="/register" class="text-blue-600 font-medium hover:underline">Start a free trial</router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob {
  animation: blob 7s infinite;
}
.animation-delay-2000 {
  animation-delay: 2s;
}
.animation-delay-4000 {
  animation-delay: 4s;
}
</style>
