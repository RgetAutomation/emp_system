<script setup>
import { onMounted, ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { Clock, CheckCircle2 } from 'lucide-vue-next';
import api from '../../axios';

const authStore = useAuthStore();
const isCheckedIn = ref(false);
const isCheckedOut = ref(false);
const attendanceRecord = ref(null);
const loading = ref(false);

const currentTime = ref(new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));

// Update time every minute
setInterval(() => {
  currentTime.value = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}, 60000);

const fetchTodayStatus = async () => {
  try {
    const response = await api.get('/attendance/status');
    attendanceRecord.value = response.data;
    if (response.data) {
      isCheckedIn.value = true;
      if (response.data.check_out) {
        isCheckedOut.value = true;
      }
    }
  } catch (error) {
    console.error("Failed to load today's status", error);
  }
};

const handleAttendanceAction = async () => {
  loading.value = true;
  try {
    if (!isCheckedIn.value) {
      // Perform Check-in
      const response = await api.post('/attendance/check-in');
      attendanceRecord.value = response.data;
      isCheckedIn.value = true;
    } else if (!isCheckedOut.value) {
      // Perform Check-out
      const response = await api.post('/attendance/check-out');
      attendanceRecord.value = response.data;
      isCheckedOut.value = true;
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to update attendance');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchTodayStatus();
});
</script>

<template>
  <div class="max-w-4xl mx-auto">
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
        
        <div class="flex flex-col items-center md:items-start gap-1">
          <div class="text-4xl font-bold tracking-tight text-gray-900">{{ currentTime }}</div>
          <div v-if="attendanceRecord" class="text-sm font-semibold mt-2">
            <span v-if="attendanceRecord.check_in" class="text-green-600 mr-3 inline-flex items-center gap-1">
              <span class="w-2 h-2 rounded-full bg-green-500 inline-block animate-pulse"></span>
              Checked In: {{ attendanceRecord.check_in }}
            </span>
            <span v-if="attendanceRecord.check_out" class="text-rose-600 inline-flex items-center gap-1">
              <span class="w-2 h-2 rounded-full bg-rose-500 inline-block"></span>
              Checked Out: {{ attendanceRecord.check_out }}
            </span>
          </div>
        </div>
      </div>

      <div class="relative z-10">
        <button 
          @click="handleAttendanceAction"
          :disabled="isCheckedOut || loading"
          :class="[
            'relative overflow-hidden group w-48 h-48 rounded-full flex flex-col items-center justify-center transition-all duration-500 shadow-xl',
            isCheckedOut 
              ? 'bg-gradient-to-br from-gray-300 to-gray-400 shadow-gray-400/20 text-white cursor-not-allowed' 
              : isCheckedIn 
                ? 'bg-gradient-to-br from-red-500 to-rose-600 shadow-red-500/30 text-white animate-pulse' 
                : 'bg-gradient-to-br from-blue-600 to-indigo-600 shadow-blue-600/30 text-white'
          ]"
        >
          <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity"></div>
          
          <Clock v-if="!isCheckedIn" class="w-10 h-10 mb-2 group-hover:scale-110 transition-transform duration-300" />
          <CheckCircle2 v-else class="w-10 h-10 mb-2 group-hover:scale-110 transition-transform duration-300" />
          
          <span class="text-xl font-bold tracking-wide">
            {{ isCheckedOut ? 'COMPLETED' : isCheckedIn ? 'CHECK OUT' : 'CHECK IN' }}
          </span>
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
