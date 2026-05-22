<script setup>
import { onMounted, ref, computed } from 'vue';
import api from '../../axios';
import { CalendarDays, Clock, UserCheck, CheckCircle, AlertTriangle, XCircle, Coffee } from 'lucide-vue-next';

const attendances = ref([]);
const loading = ref(false);
const error = ref(null);

const stats = computed(() => {
  const counts = { present: 0, late: 0, absent: 0, half_day: 0 };
  attendances.value.forEach(record => {
    if (counts[record.status] !== undefined) {
      counts[record.status]++;
    }
  });
  return counts;
});

const fetchPersonalAttendance = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get('/attendance');
    attendances.value = response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load your attendance logs';
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateStr) => {
  const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateStr).toLocaleDateString(undefined, options);
};

onMounted(() => {
  fetchPersonalAttendance();
});
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-8">
    <!-- Header -->
    <div>
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center gap-2">
        <CalendarDays class="w-8 h-8 text-blue-600" />
        My Attendance History
      </h1>
      <p class="text-gray-500 mt-1">Review your personal attendance metrics and history logs.</p>
    </div>

    <!-- Stats summary -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-3.5">
        <div class="p-2.5 bg-green-50 rounded-xl text-green-600">
          <CheckCircle class="w-5 h-5" />
        </div>
        <div>
          <div class="text-xl font-bold text-gray-950">{{ stats.present }}</div>
          <div class="text-[10px] uppercase font-bold tracking-wider text-gray-400">Present</div>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-3.5">
        <div class="p-2.5 bg-amber-50 rounded-xl text-amber-600">
          <AlertTriangle class="w-5 h-5" />
        </div>
        <div>
          <div class="text-xl font-bold text-gray-950">{{ stats.late }}</div>
          <div class="text-[10px] uppercase font-bold tracking-wider text-gray-400">Late</div>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-3.5">
        <div class="p-2.5 bg-purple-50 rounded-xl text-purple-600">
          <Coffee class="w-5 h-5" />
        </div>
        <div>
          <div class="text-xl font-bold text-gray-950">{{ stats.half_day }}</div>
          <div class="text-[10px] uppercase font-bold tracking-wider text-gray-400">Half Day</div>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-3.5">
        <div class="p-2.5 bg-red-50 rounded-xl text-red-600">
          <XCircle class="w-5 h-5" />
        </div>
        <div>
          <div class="text-xl font-bold text-gray-950">{{ stats.absent }}</div>
          <div class="text-[10px] uppercase font-bold tracking-wider text-gray-400">Absent</div>
        </div>
      </div>
    </div>

    <!-- Attendance History Table -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
      <!-- Loading State -->
      <div v-if="loading" class="p-12 text-center text-gray-500">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent mb-4"></div>
        <p class="font-medium text-gray-600">Retrieving records...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="p-12 text-center text-red-500 font-medium">
        {{ error }}
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50 text-gray-700 border-b border-gray-200 font-semibold">
            <tr>
              <th class="px-6 py-4">Date</th>
              <th class="px-6 py-4">Check-In</th>
              <th class="px-6 py-4">Check-Out</th>
              <th class="px-6 py-4">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="record in attendances" :key="record.id" class="hover:bg-gray-50/50 transition-colors">
              <td class="px-6 py-4 font-semibold text-gray-900">
                {{ formatDate(record.date) }}
              </td>
              <td class="px-6 py-4 font-mono text-gray-600">
                {{ record.check_in || '--:--' }}
              </td>
              <td class="px-6 py-4 font-mono text-gray-600">
                {{ record.check_out || '--:--' }}
              </td>
              <td class="px-6 py-4">
                <span 
                  :class="[
                    'px-2.5 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1',
                    record.status === 'present' && 'bg-green-50 text-green-700',
                    record.status === 'late' && 'bg-amber-50 text-amber-700',
                    record.status === 'absent' && 'bg-red-50 text-red-700',
                    record.status === 'half_day' && 'bg-purple-50 text-purple-700',
                  ]"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="[
                    record.status === 'present' && 'bg-green-500',
                    record.status === 'late' && 'bg-amber-500',
                    record.status === 'absent' && 'bg-red-500',
                    record.status === 'half_day' && 'bg-purple-500',
                  ]"></span>
                  <span class="capitalize">{{ record.status.replace('_', ' ') }}</span>
                </span>
              </td>
            </tr>
            <tr v-if="attendances.length === 0">
              <td colspan="4" class="px-6 py-12 text-center text-gray-400 font-medium">
                No attendance logs found. Click "Check In" on your Dashboard to log your first record!
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
