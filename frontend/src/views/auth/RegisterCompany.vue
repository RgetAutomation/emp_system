<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { Building2, Mail, Lock, User, ArrowRight, CheckCircle2 } from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();

const form = ref({
  company_name: '',
  admin_name: '',
  email: '',
  password: '',
  password_confirmation: ''
});

const error = ref('');
const loading = ref(false);

const handleRegister = async () => {
  if (form.value.password !== form.value.password_confirmation) {
    error.value = "Passwords do not match";
    return;
  }
  
  loading.value = true;
  error.value = '';
  
  try {
    await authStore.registerCompany(form.value);
    router.push('/admin/dashboard');
  } catch (err) {
    error.value = err.response?.data?.message || 'Registration failed. Please try again.';
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen flex w-full">
    <!-- Left Side - Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center bg-white p-8 sm:p-12 lg:p-24 relative z-10">
      <div class="w-full max-w-md">
        <div class="mb-10">
          <div class="h-12 w-12 bg-blue-600 rounded-xl flex items-center justify-center mb-6 shadow-lg shadow-blue-200">
            <Building2 class="text-white w-6 h-6" />
          </div>
          <h1 class="text-4xl font-bold text-gray-900 tracking-tight mb-2">Start your free trial</h1>
          <p class="text-gray-500 text-lg">Manage your team efficiently with our all-in-one platform.</p>
        </div>

        <div v-if="error" class="mb-6 p-4 rounded-xl bg-red-50 text-red-600 border border-red-100 flex items-start gap-3">
          <div class="mt-0.5">⚠️</div>
          <p class="text-sm font-medium">{{ error }}</p>
        </div>

        <form @submit.prevent="handleRegister" class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Company Name</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <Building2 class="h-5 w-5 text-gray-400" />
              </div>
              <input v-model="form.company_name" type="text" required class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all outline-none" placeholder="Acme Inc." />
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Admin Full Name</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <User class="h-5 w-5 text-gray-400" />
              </div>
              <input v-model="form.admin_name" type="text" required class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all outline-none" placeholder="John Doe" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Work Email</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <Mail class="h-5 w-5 text-gray-400" />
              </div>
              <input v-model="form.email" type="email" required class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all outline-none" placeholder="john@acme.com" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <Lock class="h-5 w-5 text-gray-400" />
                </div>
                <input v-model="form.password" type="password" required class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all outline-none" placeholder="••••••••" />
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <Lock class="h-5 w-5 text-gray-400" />
                </div>
                <input v-model="form.password_confirmation" type="password" required class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all outline-none" placeholder="••••••••" />
              </div>
            </div>
          </div>

          <button type="submit" :disabled="loading" class="w-full flex items-center justify-center gap-2 py-3.5 px-4 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-xl transition-all disabled:opacity-70 disabled:cursor-not-allowed mt-4 shadow-xl shadow-gray-200">
            <span v-if="loading">Creating account...</span>
            <template v-else>
              <span>Create Company Account</span>
              <ArrowRight class="w-4 h-4" />
            </template>
          </button>
        </form>

        <p class="mt-8 text-center text-gray-500 text-sm">
          Already have an account? 
          <router-link to="/login" class="text-blue-600 font-medium hover:underline">Sign in</router-link>
        </p>
      </div>
    </div>

    <!-- Right Side - Graphic -->
    <div class="hidden lg:flex lg:w-1/2 bg-blue-600 relative overflow-hidden flex-col items-center justify-center p-12">
      <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-indigo-700"></div>
      
      <!-- Decorative circles -->
      <div class="absolute top-0 right-0 -mt-20 -mr-20 w-80 h-80 bg-white opacity-10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-80 h-80 bg-blue-400 opacity-20 rounded-full blur-3xl"></div>
      
      <div class="relative z-10 w-full max-w-lg text-white">
        <h2 class="text-4xl font-bold mb-6 leading-tight">Everything you need to manage your workforce.</h2>
        <div class="space-y-5">
          <div class="flex items-center gap-4 bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/20">
            <CheckCircle2 class="w-6 h-6 text-blue-200 shrink-0" />
            <p class="text-blue-50 font-medium">Automated attendance & leave management</p>
          </div>
          <div class="flex items-center gap-4 bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/20">
            <CheckCircle2 class="w-6 h-6 text-blue-200 shrink-0" />
            <p class="text-blue-50 font-medium">Role-based access for HR, Managers & Employees</p>
          </div>
          <div class="flex items-center gap-4 bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/20">
            <CheckCircle2 class="w-6 h-6 text-blue-200 shrink-0" />
            <p class="text-blue-50 font-medium">Detailed analytics and reporting tools</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
