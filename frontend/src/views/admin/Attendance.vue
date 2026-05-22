<script setup>
import { onMounted, ref, computed } from 'vue';
import api from '../../axios';
import { Calendar, UserCheck, UserX, Clock, Coffee, Edit, Trash2 } from 'lucide-vue-next';

const attendances = ref([]);
const loading = ref(false);
const error = ref(null);
const selectedDate = ref(new Date().toISOString().split('T')[0]);

// Edit Modal State
const showEditModal = ref(false);
const editingRecord = ref(null);
const editForm = ref({
  status: 'present',
  check_in: '',
  check_out: ''
});

const stats = computed(() => {
  const counts = { present: 0, late: 0, absent: 0, half_day: 0 };
  attendances.value.forEach(record => {
    if (counts[record.status] !== undefined) {
      counts[record.status]++;
    }
  });
  return counts;
});

const fetchAttendance = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get(`/attendance`, {
      params: { date: selectedDate.value }
    });
    attendances.value = response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load attendance logs';
  } finally {
    loading.value = false;
  }
};

const openEditModal = (record) => {
  editingRecord.value = record;
  editForm.value = {
    status: record.status,
    check_in: record.check_in || '',
    check_out: record.check_out || ''
  };
  showEditModal.value = true;
};

const handleUpdate = async () => {
  if (!editingRecord.value) return;
  try {
    const response = await api.put(`/attendance/${editingRecord.value.id}`, editForm.value);
    // Update local state
    const index = attendances.value.findIndex(a => a.id === editingRecord.value.id);
    if (index !== -1) {
      attendances.value[index] = {
        ...attendances.value[index],
        ...response.data
      };
    }
    showEditModal.value = false;
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to update attendance');
  }
};

const handleDelete = async (id) => {
  if (!confirm('Are you sure you want to delete this attendance record?')) return;
  try {
    await api.delete(`/attendance/${id}`);
    attendances.value = attendances.value.filter(a => a.id !== id);
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete attendance');
  }
};

onMounted(() => {
  fetchAttendance();
});
</script>

<template>
  <div class="max-w-6xl mx-auto space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center gap-2">
          <Calendar class="w-8 h-8 text-blue-600" />
          Attendance Management
        </h1>
        <p class="text-gray-500 mt-1">Monitor and manage employee daily check-ins and statuses.</p>
      </div>
      
      <!-- Date Picker -->
      <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-4 py-2 shadow-sm self-start sm:self-auto">
        <span class="text-sm font-medium text-gray-500">Date:</span>
        <input 
          type="date" 
          v-model="selectedDate" 
          @change="fetchAttendance"
          class="text-sm font-semibold text-gray-900 border-none outline-none focus:ring-0 cursor-pointer"
        />
      </div>
    </div>

    <!-- Stats Cards Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Present -->
      <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="p-3 bg-green-50 rounded-xl text-green-600">
          <UserCheck class="w-6 h-6" />
        </div>
        <div>
          <div class="text-2xl font-bold text-gray-900">{{ stats.present }}</div>
          <div class="text-xs font-semibold tracking-wider uppercase text-gray-400">Present</div>
        </div>
      </div>

      <!-- Late -->
      <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="p-3 bg-amber-50 rounded-xl text-amber-600">
          <Clock class="w-6 h-6" />
        </div>
        <div>
          <div class="text-2xl font-bold text-gray-900">{{ stats.late }}</div>
          <div class="text-xs font-semibold tracking-wider uppercase text-gray-400">Late</div>
        </div>
      </div>

      <!-- Absent -->
      <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="p-3 bg-red-50 rounded-xl text-red-600">
          <UserX class="w-6 h-6" />
        </div>
        <div>
          <div class="text-2xl font-bold text-gray-900">{{ stats.absent }}</div>
          <div class="text-xs font-semibold tracking-wider uppercase text-gray-400">Absent</div>
        </div>
      </div>

      <!-- Half Day -->
      <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="p-3 bg-purple-50 rounded-xl text-purple-600">
          <Coffee class="w-6 h-6" />
        </div>
        <div>
          <div class="text-2xl font-bold text-gray-900">{{ stats.half_day }}</div>
          <div class="text-xs font-semibold tracking-wider uppercase text-gray-400">Half Day</div>
        </div>
      </div>
    </div>

    <!-- Attendance Table Card -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
      <!-- Loading State -->
      <div v-if="loading" class="p-12 text-center text-gray-500">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent mb-4"></div>
        <p class="font-medium text-gray-600">Loading attendance data...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="p-12 text-center text-red-500 font-medium">
        {{ error }}
      </div>

      <!-- Table Section -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50 text-gray-700 border-b border-gray-200 font-semibold">
            <tr>
              <th class="px-6 py-4">Employee</th>
              <th class="px-6 py-4">Department & Designation</th>
              <th class="px-6 py-4">Check-In</th>
              <th class="px-6 py-4">Check-Out</th>
              <th class="px-6 py-4">Status</th>
              <th class="px-6 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="record in attendances" :key="record.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">
                  {{ record.user?.name?.charAt(0) || 'E' }}
                </div>
                <div>
                  <div class="font-semibold text-gray-950">{{ record.user?.name }}</div>
                  <div class="text-xs text-gray-400">ID: {{ record.user?.employee?.employee_id || 'N/A' }}</div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="font-medium text-gray-900">{{ record.user?.employee?.department?.name || 'N/A' }}</div>
                <div class="text-xs text-gray-400">{{ record.user?.employee?.designation?.name || 'N/A' }}</div>
              </td>
              <td class="px-6 py-4 font-mono text-gray-900">
                {{ record.check_in ? record.check_in : '--:--' }}
              </td>
              <td class="px-6 py-4 font-mono text-gray-900">
                {{ record.check_out ? record.check_out : '--:--' }}
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
              <td class="px-6 py-4 text-right flex justify-end gap-2">
                <button 
                  @click="openEditModal(record)"
                  class="p-1.5 text-gray-400 hover:text-blue-600 transition-colors rounded-lg hover:bg-blue-50"
                  title="Edit Status"
                >
                  <Edit class="w-4 h-4" />
                </button>
                <button 
                  @click="handleDelete(record.id)"
                  class="p-1.5 text-gray-400 hover:text-red-600 transition-colors rounded-lg hover:bg-red-50"
                  title="Delete Log"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </td>
            </tr>
            <tr v-if="attendances.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-gray-400 font-medium">
                No attendance logs found for this date.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Edit Status Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
          <div>
            <h3 class="font-bold text-gray-900 text-lg">Update Attendance Log</h3>
            <p class="text-xs text-gray-400 mt-0.5">Editing: {{ editingRecord?.user?.name }}</p>
          </div>
          <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-light">&times;</button>
        </div>
        
        <form @submit.prevent="handleUpdate" class="p-6 space-y-4">
          <!-- Status Dropdown -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Status</label>
            <select 
              v-model="editForm.status"
              class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all"
            >
              <option value="present">Present</option>
              <option value="late">Late</option>
              <option value="absent">Absent</option>
              <option value="half_day">Half Day</option>
            </select>
          </div>

          <!-- Time Inputs -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5">Check-In</label>
              <input 
                v-model="editForm.check_in"
                type="text" 
                placeholder="e.g. 09:00:00"
                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all"
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5">Check-Out</label>
              <input 
                v-model="editForm.check_out"
                type="text" 
                placeholder="e.g. 17:00:00"
                class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all"
              />
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <button 
              type="button" 
              @click="showEditModal = false" 
              class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-xl text-sm font-medium transition-colors"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 text-sm font-semibold transition-colors"
            >
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
