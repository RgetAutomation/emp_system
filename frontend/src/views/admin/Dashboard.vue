<template>
  <div class="max-w-7xl mx-auto space-y-8">
    <div class="mb-4">
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Company Overview Analytics</h1>
      <p class="text-gray-500 mt-1">Real-time insights for {{ authStore.user?.company?.name }}.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
      <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
        <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center shrink-0">
          <Users class="w-7 h-7" />
        </div>
        <div>
          <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Total Headcount</h3>
          <p class="text-3xl font-black text-gray-900 mt-1">
            <span v-if="loading" class="text-gray-300 animate-pulse">...</span>
            <span v-else>{{ data?.overview?.total_employees || 0 }}</span>
          </p>
        </div>
      </div>
      
      <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
        <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center shrink-0">
          <UserCheck class="w-7 h-7" />
        </div>
        <div>
          <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Present Today</h3>
          <p class="text-3xl font-black text-gray-900 mt-1">
            <span v-if="loading" class="text-gray-300 animate-pulse">...</span>
            <span v-else>{{ data?.overview?.present_today || 0 }}</span>
          </p>
        </div>
      </div>
      
      <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
        <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center shrink-0">
          <Plane class="w-7 h-7" />
        </div>
        <div>
          <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">On Leave</h3>
          <p class="text-3xl font-black text-gray-900 mt-1">
            <span v-if="loading" class="text-gray-300 animate-pulse">...</span>
            <span v-else>{{ data?.overview?.on_leave || 0 }}</span>
          </p>
        </div>
      </div>
      
      <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
        <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center shrink-0">
          <Building2 class="w-7 h-7" />
        </div>
        <div>
          <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Departments</h3>
          <p class="text-3xl font-black text-gray-900 mt-1">
            <span v-if="loading" class="text-gray-300 animate-pulse">...</span>
            <span v-else>{{ data?.overview?.total_departments || 0 }}</span>
          </p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Payroll Trend (Bar Chart CSS) -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm lg:col-span-2 p-6 flex flex-col">
        <div class="flex items-center justify-between mb-8">
          <div>
            <h2 class="text-lg font-bold text-gray-900">Payroll Cost Trend</h2>
            <p class="text-sm text-gray-500">Total company payroll cost over the last 6 months</p>
          </div>
          <div class="bg-gray-50 p-2 rounded-lg">
            <LineChart class="w-5 h-5 text-gray-400" />
          </div>
        </div>

        <div v-if="loading" class="flex-1 flex items-center justify-center">
          <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
        </div>
        
        <div v-else class="flex-1 flex items-end gap-2 sm:gap-6 h-64 mt-auto">
          <div v-for="(item, index) in data?.payroll_trend || []" :key="index" class="flex-1 flex flex-col items-center group">
            <!-- Tooltip -->
            <div class="opacity-0 group-hover:opacity-100 transition-opacity bg-gray-900 text-white text-xs py-1 px-2 rounded mb-2 whitespace-nowrap z-10 pointer-events-none">
              ₹{{ formatCurrency(item.cost) }}
            </div>
            <!-- Bar -->
            <div class="w-full max-w-[48px] bg-blue-100 rounded-t-lg relative flex items-end justify-center transition-all duration-500"
                 :style="{ height: getBarHeight(item.cost) }">
              <div class="absolute bottom-0 w-full bg-blue-500 rounded-t-lg transition-all duration-500 hover:bg-blue-600"
                   :style="{ height: '100%' }"></div>
            </div>
            <!-- Label -->
            <span class="text-xs font-bold text-gray-500 mt-3 rotate-45 sm:rotate-0 origin-left whitespace-nowrap">{{ item.month }}</span>
          </div>
        </div>
      </div>

      <!-- Attendance Today (CSS Ring / Distribution) -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
          <PieChart class="w-5 h-5 text-gray-400" />
          Today's Attendance
        </h2>

        <div v-if="loading" class="flex items-center justify-center h-48">
          <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
        </div>

        <div v-else class="space-y-5">
          <!-- Total -->
          <div class="text-center mb-6">
            <span class="text-4xl font-black text-gray-900">{{ data?.overview?.total_employees }}</span>
            <p class="text-sm font-bold text-gray-500 uppercase tracking-wider mt-1">Total Expected</p>
          </div>

          <div class="space-y-4">
            <div class="flex items-center justify-between group">
              <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                <span class="text-sm font-bold text-gray-700">Present</span>
              </div>
              <div class="flex items-center gap-4">
                <span class="text-sm font-black text-gray-900">{{ data?.attendance_today?.present || 0 }}</span>
                <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full bg-emerald-500 rounded-full transition-all duration-1000" :style="{ width: getPercentage(data?.attendance_today?.present, data?.overview?.total_employees) + '%' }"></div>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between group">
              <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                <span class="text-sm font-bold text-gray-700">On Leave</span>
              </div>
              <div class="flex items-center gap-4">
                <span class="text-sm font-black text-gray-900">{{ data?.attendance_today?.on_leave || 0 }}</span>
                <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full bg-amber-500 rounded-full transition-all duration-1000" :style="{ width: getPercentage(data?.attendance_today?.on_leave, data?.overview?.total_employees) + '%' }"></div>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between group">
              <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                <span class="text-sm font-bold text-gray-700">Absent</span>
              </div>
              <div class="flex items-center gap-4">
                <span class="text-sm font-black text-gray-900">{{ data?.attendance_today?.absent || 0 }}</span>
                <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full bg-red-500 rounded-full transition-all duration-1000" :style="{ width: getPercentage(data?.attendance_today?.absent, data?.overview?.total_employees) + '%' }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- Department Distribution -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
          <Layers class="w-5 h-5 text-gray-400" />
          Headcount by Department
        </h2>
        
        <div v-if="loading" class="flex items-center justify-center h-48">
          <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
        </div>
        
        <div v-else class="space-y-4">
          <div v-for="(dept, index) in data?.department_distribution || []" :key="index" class="flex items-center justify-between">
            <span class="text-sm font-bold text-gray-700 truncate max-w-[50%]">{{ dept.name }}</span>
            <div class="flex-1 mx-4 flex items-center">
              <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-indigo-500 rounded-full transition-all duration-1000" 
                     :style="{ width: getPercentage(dept.count, data?.overview?.total_employees) + '%' }"></div>
              </div>
            </div>
            <span class="text-sm font-black text-gray-900 w-8 text-right">{{ dept.count }}</span>
          </div>
          <div v-if="!data?.department_distribution?.length" class="text-center text-gray-400 text-sm py-4">
            No departments found.
          </div>
        </div>
      </div>

      <!-- Expense Categories -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
          <Banknote class="w-5 h-5 text-gray-400" />
          Expense Distribution (YTD)
        </h2>
        
        <div v-if="loading" class="flex items-center justify-center h-48">
          <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
        </div>
        
        <div v-else class="space-y-4">
          <div v-for="(exp, index) in data?.expense_distribution || []" :key="index" class="flex items-center justify-between">
            <span class="text-sm font-bold text-gray-700 w-1/3 truncate">{{ exp.category }}</span>
            <div class="flex-1 mx-4 flex items-center">
               <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-amber-500 rounded-full transition-all duration-1000" 
                     :style="{ width: getExpensePercentage(exp.total) + '%' }"></div>
              </div>
            </div>
            <span class="text-sm font-black text-gray-900 w-24 text-right">₹{{ formatCurrency(exp.total) }}</span>
          </div>
          <div v-if="!data?.expense_distribution?.length" class="text-center text-gray-400 text-sm py-4">
            No expenses processed this year.
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { Users, UserCheck, Building2, Plane, LineChart, PieChart, Layers, Banknote } from 'lucide-vue-next';

const authStore = useAuthStore();
const loading = ref(true);
const data = ref(null);

const maxPayrollCost = computed(() => {
  if (!data.value?.payroll_trend?.length) return 0;
  return Math.max(...data.value.payroll_trend.map(item => item.cost));
});

const maxExpense = computed(() => {
  if (!data.value?.expense_distribution?.length) return 0;
  return Math.max(...data.value.expense_distribution.map(item => parseFloat(item.total)));
});

const getBarHeight = (cost) => {
  if (maxPayrollCost.value === 0) return '0%';
  const percentage = (cost / maxPayrollCost.value) * 100;
  // Ensure a minimum height if there is a cost so the bar is visible
  return cost > 0 ? `${Math.max(percentage, 5)}%` : '0%';
};

const getPercentage = (value, total) => {
  if (!total || total === 0) return 0;
  return Math.round((value / total) * 100);
};

const getExpensePercentage = (value) => {
  if (maxExpense.value === 0) return 0;
  return Math.round((parseFloat(value) / maxExpense.value) * 100);
};

const formatCurrency = (val) => {
  return parseFloat(val).toLocaleString('en-IN', { maximumFractionDigits: 0 });
};

onMounted(async () => {
  try {
    const token = localStorage.getItem('token');
    const res = await fetch('http://localhost:8000/api/analytics/dashboard', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });
    
    if (res.ok) {
      data.value = await res.json();
    }
  } catch (error) {
    console.error('Failed to load dashboard stats:', error);
  } finally {
    loading.value = false;
  }
});
</script>
