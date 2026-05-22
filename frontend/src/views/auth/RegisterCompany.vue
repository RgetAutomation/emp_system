<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { Building2, Mail, Lock, User, ArrowRight, CheckCircle2, Phone, MapPin, FileText, Image as ImageIcon, Eye, EyeOff } from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();

const form = ref({
  company_name: '',
  admin_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
  address: '',
  gst_no: ''
});

const files = ref({
  logo: null,
  trade_license: null
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const error = ref('');
const fieldErrors = ref({});
const loading = ref(false);

const handleFileUpload = (event, key) => {
  files.value[key] = event.target.files[0];
};

const handleRegister = async () => {
  if (form.value.password !== form.value.password_confirmation) {
    error.value = "Passwords do not match";
    return;
  }
  
  loading.value = true;
  error.value = '';
  fieldErrors.value = {};
  
  try {
    const formData = new FormData();
    
    // Append text fields
    Object.keys(form.value).forEach(key => {
      formData.append(key, form.value[key]);
    });
    
    // Append files
    if (files.value.logo) formData.append('logo', files.value.logo);
    if (files.value.trade_license) formData.append('trade_license', files.value.trade_license);

    await authStore.registerCompany(formData);
    router.push('/admin/dashboard');
  } catch (err) {
    error.value = err.response?.data?.message || 'Registration failed. Please try again.';
    fieldErrors.value = err.response?.data?.errors || {};
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen flex w-full bg-gray-50">
    <!-- Left Side - Form -->
    <div class="w-full lg:w-3/5 flex items-center justify-center p-6 sm:p-12 relative z-10">
      <div class="w-full max-w-2xl bg-white p-8 sm:p-10 rounded-3xl shadow-xl border border-gray-100">
        
        <div class="mb-8">
          <div class="h-12 w-12 bg-blue-600 rounded-xl flex items-center justify-center mb-6 shadow-lg shadow-blue-200">
            <Building2 class="text-white w-6 h-6" />
          </div>
          <h1 class="text-3xl font-bold text-gray-900 tracking-tight mb-2">Register Your Company</h1>
          <p class="text-gray-500">Set up your workspace and start managing your team.</p>
        </div>

        <div v-if="error" class="mb-6 p-4 rounded-xl bg-red-50 text-red-600 border border-red-100 flex items-start gap-3">
          <div class="mt-0.5">⚠️</div>
          <p class="text-sm font-medium">{{ error }}</p>
        </div>

        <form @submit.prevent="handleRegister" class="space-y-6">
          
          <!-- Basic Info Group -->
          <div>
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 border-b pb-2">Basic Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Company Name *</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><Building2 class="h-5 w-5 text-gray-400" /></div>
                  <input v-model="form.company_name" type="text" required class="form-input-icon" :class="{'border-red-300 ring-1 ring-red-300': fieldErrors.company_name}" placeholder="Acme Inc." />
                </div>
                <p v-if="fieldErrors.company_name" class="text-xs text-red-500 mt-1 font-medium">{{ fieldErrors.company_name[0] }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Owner / Admin Name *</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><User class="h-5 w-5 text-gray-400" /></div>
                  <input v-model="form.admin_name" type="text" required class="form-input-icon" :class="{'border-red-300 ring-1 ring-red-300': fieldErrors.admin_name}" placeholder="John Doe" />
                </div>
                <p v-if="fieldErrors.admin_name" class="text-xs text-red-500 mt-1 font-medium">{{ fieldErrors.admin_name[0] }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Work Email *</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><Mail class="h-5 w-5 text-gray-400" /></div>
                  <input v-model="form.email" type="email" required class="form-input-icon" :class="{'border-red-300 ring-1 ring-red-300': fieldErrors.email}" placeholder="john@acme.com" />
                </div>
                <p v-if="fieldErrors.email" class="text-xs text-red-500 mt-1 font-medium">{{ fieldErrors.email[0] }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><Phone class="h-5 w-5 text-gray-400" /></div>
                  <input v-model="form.phone" type="text" class="form-input-icon" :class="{'border-red-300 ring-1 ring-red-300': fieldErrors.phone}" placeholder="+1 234 567 8900" />
                </div>
                <p v-if="fieldErrors.phone" class="text-xs text-red-500 mt-1 font-medium">{{ fieldErrors.phone[0] }}</p>
              </div>
            </div>
          </div>

          <!-- Business Details Group -->
          <div>
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 border-b pb-2 mt-2">Business Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Company Address</label>
                <div class="relative">
                  <div class="absolute top-3 left-0 pl-3.5 flex items-start pointer-events-none"><MapPin class="h-5 w-5 text-gray-400" /></div>
                  <textarea v-model="form.address" class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all outline-none h-24" :class="{'border-red-300 ring-1 ring-red-300': fieldErrors.address}" placeholder="123 Business Avenue..."></textarea>
                </div>
                <p v-if="fieldErrors.address" class="text-xs text-red-500 mt-1 font-medium">{{ fieldErrors.address[0] }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">GST Number</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><FileText class="h-5 w-5 text-gray-400" /></div>
                  <input v-model="form.gst_no" type="text" class="form-input-icon" :class="{'border-red-300 ring-1 ring-red-300': fieldErrors.gst_no}" placeholder="22AAAAA0000A1Z5" />
                </div>
                <p v-if="fieldErrors.gst_no" class="text-xs text-red-500 mt-1 font-medium">{{ fieldErrors.gst_no[0] }}</p>
              </div>
              
              <div class="file-input-wrapper">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Trade License Document</label>
                <input type="file" @change="e => handleFileUpload(e, 'trade_license')" accept=".pdf,.jpg,.jpeg,.png" class="file-input-modern" :class="{'border-red-300 ring-1 ring-red-300': fieldErrors.trade_license}" />
                <p v-if="fieldErrors.trade_license" class="text-xs text-red-500 mt-1 font-medium">{{ fieldErrors.trade_license[0] }}</p>
              </div>

              <div class="file-input-wrapper md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Company Logo</label>
                <input type="file" @change="e => handleFileUpload(e, 'logo')" accept="image/*" class="file-input-modern" :class="{'border-red-300 ring-1 ring-red-300': fieldErrors.logo}" />
                <p v-if="fieldErrors.logo" class="text-xs text-red-500 mt-1 font-medium">{{ fieldErrors.logo[0] }}</p>
              </div>
            </div>
          </div>

          <!-- Security Group -->
          <div>
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 border-b pb-2 mt-2">Security</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Password *</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><Lock class="h-5 w-5 text-gray-400" /></div>
                  <input v-model="form.password" :type="showPassword ? 'text' : 'password'" required autocomplete="new-password" class="form-input-icon pr-10" :class="{'border-red-300 ring-1 ring-red-300': fieldErrors.password}" placeholder="••••••••" />
                  <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                    <Eye v-if="!showPassword" class="h-5 w-5" />
                    <EyeOff v-else class="h-5 w-5" />
                  </button>
                </div>
                <p v-if="fieldErrors.password" class="text-xs text-red-500 mt-1 font-medium">{{ fieldErrors.password[0] }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password *</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><Lock class="h-5 w-5 text-gray-400" /></div>
                  <input v-model="form.password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" required autocomplete="new-password" class="form-input-icon pr-10" placeholder="••••••••" />
                  <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                    <Eye v-if="!showConfirmPassword" class="h-5 w-5" />
                    <EyeOff v-else class="h-5 w-5" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <button type="submit" :disabled="loading" class="w-full flex items-center justify-center gap-2 py-4 px-4 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-xl transition-all disabled:opacity-70 disabled:cursor-not-allowed mt-8 shadow-xl shadow-gray-200">
            <span v-if="loading">Creating workspace...</span>
            <template v-else>
              <span>Create Company Workspace</span>
              <ArrowRight class="w-5 h-5" />
            </template>
          </button>
        </form>

        <p class="mt-8 text-center text-gray-500 text-sm">
          Already have an account? 
          <router-link to="/login" class="text-blue-600 font-bold hover:underline">Sign in</router-link>
        </p>
      </div>
    </div>

    <!-- Right Side - Graphic -->
    <div class="hidden lg:flex lg:w-2/5 bg-blue-600 relative overflow-hidden flex-col items-center justify-center p-12 fixed right-0 top-0 bottom-0">
      <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-indigo-700"></div>
      
      <!-- Decorative circles -->
      <div class="absolute top-0 right-0 -mt-20 -mr-20 w-80 h-80 bg-white opacity-10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-80 h-80 bg-blue-400 opacity-20 rounded-full blur-3xl"></div>
      
      <div class="relative z-10 w-full max-w-md text-white">
        <h2 class="text-4xl font-bold mb-6 leading-tight">Your complete HR ecosystem.</h2>
        <div class="space-y-5">
          <div class="flex items-center gap-4 bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/20">
            <CheckCircle2 class="w-6 h-6 text-blue-200 shrink-0" />
            <p class="text-blue-50 font-medium">Digital document storage (Trade Licenses, Logos)</p>
          </div>
          <div class="flex items-center gap-4 bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/20">
            <CheckCircle2 class="w-6 h-6 text-blue-200 shrink-0" />
            <p class="text-blue-50 font-medium">Manage employees, departments, and payroll</p>
          </div>
          <div class="flex items-center gap-4 bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/20">
            <CheckCircle2 class="w-6 h-6 text-blue-200 shrink-0" />
            <p class="text-blue-50 font-medium">Multi-tenant secure architecture</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@reference "tailwindcss";

.form-input-icon {
  @apply block w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all outline-none;
}
.file-input-modern {
  @apply block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all border border-gray-200 rounded-xl bg-gray-50 p-1.5 cursor-pointer outline-none focus:ring-2 focus:ring-blue-600;
}
</style>
