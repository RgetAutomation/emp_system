<script setup>
import { onMounted, ref, computed } from 'vue';
import api from '../../axios';
import { Calendar, UserCheck, UserX, Clock, Coffee, Edit, Trash2, BarChart2, ChevronRight, X } from 'lucide-vue-next';

// ── Shared ─────────────────────────────────────────────────────────────────
const activeTab = ref('daily'); // 'daily' | 'monthly'

const getLocalDateString = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
};

// ── Daily Tab ──────────────────────────────────────────────────────────────
const attendances    = ref([]);
const loadingDaily   = ref(false);
const errorDaily     = ref(null);
const selectedDate   = ref(getLocalDateString());
const showEditModal  = ref(false);
const editingRecord  = ref(null);
const editForm       = ref({ status: 'present', check_in: '', check_out: '', late_minutes: '', notes: '' });

const stats = computed(() => {
  const c = { present: 0, late: 0, absent: 0, half_day: 0 };
  attendances.value.forEach(r => { if (c[r.status] !== undefined) c[r.status]++; });
  return c;
});

const fetchAttendance = async () => {
  loadingDaily.value = true;
  errorDaily.value = null;
  try {
    const res = await api.get('/attendance', { params: { date: selectedDate.value } });
    attendances.value = res.data;
  } catch (err) {
    errorDaily.value = err.response?.data?.message || 'Failed to load attendance logs';
  } finally {
    loadingDaily.value = false;
  }
};

const openEditModal = (record) => {
  editingRecord.value = record;
  editForm.value = {
    status:       record.status,
    check_in:     record.check_in  || '',
    check_out:    record.check_out || '',
    late_minutes: record.late_minutes ?? '',
    notes:        record.notes || '',
  };
  showEditModal.value = true;
};

const handleUpdate = async () => {
  if (!editingRecord.value) return;
  try {
    let response;
    if (editingRecord.value.id) {
      response = await api.put(`/attendance/${editingRecord.value.id}`, editForm.value);
    } else {
      response = await api.post('/attendance', {
        ...editForm.value,
        user_id: editingRecord.value.user_id,
        date:    selectedDate.value
      });
    }
    const index = attendances.value.findIndex(a => a.user_id === editingRecord.value.user_id);
    if (index !== -1) attendances.value[index] = { ...attendances.value[index], ...response.data };
    showEditModal.value = false;
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to update attendance');
  }
};

const handleDelete = async (id) => {
  if (!id) return;
  if (!confirm('Delete this attendance record?')) return;
  try {
    await api.delete(`/attendance/${id}`);
    fetchAttendance();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete');
  }
};

// ── Monthly Tab ────────────────────────────────────────────────────────────
const monthlyData    = ref(null);
const loadingMonthly = ref(false);
const errorMonthly   = ref(null);
const selectedMonth  = ref(new Date().toISOString().slice(0, 7));
const monthlyFilter  = ref('');
const expandedUser   = ref(null);

const fetchMonthly = async () => {
  loadingMonthly.value = true;
  errorMonthly.value = null;
  try {
    const res = await api.get('/attendance/monthly', { params: { month: selectedMonth.value } });
    monthlyData.value = res.data;
  } catch (err) {
    errorMonthly.value = err.response?.data?.message || 'Failed to load monthly report';
  } finally {
    loadingMonthly.value = false;
  }
};

const filteredMonthlyEmployees = computed(() => {
  if (!monthlyData.value) return [];
  const q = monthlyFilter.value.toLowerCase();
  return monthlyData.value.employees.filter(e =>
    !q || (e.user?.name?.toLowerCase().includes(q))
  );
});

const toggleExpand = (userId) => {
  expandedUser.value = expandedUser.value === userId ? null : userId;
};

const fmtTime = (t) => t ? t.slice(0, 5) : '--:--';
const statusColors = {
  present:  'bg-green-50 text-green-700',
  late:     'bg-amber-50 text-amber-700',
  absent:   'bg-red-50 text-red-700',
  half_day: 'bg-purple-50 text-purple-700',
};

onMounted(() => {
  fetchAttendance();
  fetchMonthly();
});
</script>

<template>
  <div class="max-w-6xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center gap-2">
          <Calendar class="w-8 h-8 text-blue-600" />
          Attendance Management
        </h1>
        <p class="text-gray-500 mt-1">Monitor and manage employee daily check-ins and statuses.</p>
      </div>
    </div>

    <!-- Tab Bar -->
    <div class="flex gap-1 p-1 bg-gray-100 rounded-xl w-fit">
      <button
        @click="activeTab = 'daily'"
        class="px-5 py-2 text-sm font-semibold rounded-lg transition-all"
        :class="activeTab === 'daily' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
      >
        <span class="flex items-center gap-1.5"><Calendar class="w-4 h-4" /> Daily View</span>
      </button>
      <button
        @click="activeTab = 'monthly'; fetchMonthly()"
        class="px-5 py-2 text-sm font-semibold rounded-lg transition-all"
        :class="activeTab === 'monthly' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
      >
        <span class="flex items-center gap-1.5"><BarChart2 class="w-4 h-4" /> Monthly Report</span>
      </button>
    </div>

    <!-- ════ DAILY TAB ════ -->
    <template v-if="activeTab === 'daily'">
      <!-- Date Picker + Stats -->
      <div class="flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-4 py-2 shadow-sm">
          <span class="text-sm font-medium text-gray-500">Date:</span>
          <input type="date" v-model="selectedDate" @change="fetchAttendance"
            class="text-sm font-semibold text-gray-900 border-none outline-none focus:ring-0 cursor-pointer" />
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4">
          <div class="p-3 bg-green-50 rounded-xl text-green-600"><UserCheck class="w-6 h-6" /></div>
          <div><div class="text-2xl font-bold text-gray-900">{{ stats.present }}</div>
          <div class="text-xs font-semibold tracking-wider uppercase text-gray-400">Present</div></div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4">
          <div class="p-3 bg-amber-50 rounded-xl text-amber-600"><Clock class="w-6 h-6" /></div>
          <div><div class="text-2xl font-bold text-gray-900">{{ stats.late }}</div>
          <div class="text-xs font-semibold tracking-wider uppercase text-gray-400">Late</div></div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4">
          <div class="p-3 bg-red-50 rounded-xl text-red-600"><UserX class="w-6 h-6" /></div>
          <div><div class="text-2xl font-bold text-gray-900">{{ stats.absent }}</div>
          <div class="text-xs font-semibold tracking-wider uppercase text-gray-400">Absent</div></div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center gap-4">
          <div class="p-3 bg-purple-50 rounded-xl text-purple-600"><Coffee class="w-6 h-6" /></div>
          <div><div class="text-2xl font-bold text-gray-900">{{ stats.half_day }}</div>
          <div class="text-xs font-semibold tracking-wider uppercase text-gray-400">Half Day</div></div>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div v-if="loadingDaily" class="p-12 text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent mb-4"></div>
          <p class="text-gray-500 font-medium">Loading...</p>
        </div>
        <div v-else-if="errorDaily" class="p-12 text-center text-red-500 font-medium">{{ errorDaily }}</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-gray-50 text-gray-700 border-b border-gray-200 font-semibold">
              <tr>
                <th class="px-6 py-4">Employee</th>
                <th class="px-6 py-4">Dept / Role</th>
                <th class="px-6 py-4">Check-In</th>
                <th class="px-6 py-4">Check-Out</th>
                <th class="px-6 py-4">Late (min)</th>
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
                <td class="px-6 py-4 font-mono text-gray-900">{{ record.check_in ? fmtTime(record.check_in) : '--:--' }}</td>
                <td class="px-6 py-4 font-mono text-gray-900">{{ record.check_out ? fmtTime(record.check_out) : '--:--' }}</td>
                <td class="px-6 py-4">
                  <span v-if="record.late_minutes" class="px-2 py-0.5 bg-amber-50 text-amber-700 rounded-full text-xs font-semibold">
                    {{ record.late_minutes }} min
                  </span>
                  <span v-else class="text-gray-300">—</span>
                </td>
                <td class="px-6 py-4">
                  <span class="px-2.5 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1"
                    :class="[
                      record.status === 'present' && 'bg-green-50 text-green-700',
                      record.status === 'late'    && 'bg-amber-50 text-amber-700',
                      record.status === 'absent'  && 'bg-red-50 text-red-700',
                      record.status === 'half_day'&& 'bg-purple-50 text-purple-700',
                    ]"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="[
                      record.status === 'present' && 'bg-green-500',
                      record.status === 'late'    && 'bg-amber-500',
                      record.status === 'absent'  && 'bg-red-500',
                      record.status === 'half_day'&& 'bg-purple-500',
                    ]"></span>
                    <span class="capitalize">{{ record.status.replace('_', ' ') }}</span>
                  </span>
                </td>
                <td class="px-6 py-4 text-right flex justify-end gap-2">
                  <button @click="openEditModal(record)" class="p-1.5 text-gray-400 hover:text-blue-600 transition-colors rounded-lg hover:bg-blue-50" title="Edit">
                    <Edit class="w-4 h-4" />
                  </button>
                  <button v-if="record.id" @click="handleDelete(record.id)" class="p-1.5 text-gray-400 hover:text-red-600 transition-colors rounded-lg hover:bg-red-50" title="Delete">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </td>
              </tr>
              <tr v-if="attendances.length === 0">
                <td colspan="7" class="px-6 py-12 text-center text-gray-400 font-medium">No attendance logs found for this date.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- ════ MONTHLY TAB ════ -->
    <template v-if="activeTab === 'monthly'">
      <!-- Controls -->
      <div class="flex flex-wrap gap-3 items-center">
        <input
          type="month"
          v-model="selectedMonth"
          @change="fetchMonthly"
          class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold text-gray-900 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none shadow-sm"
        />
        <input
          v-model="monthlyFilter"
          type="text"
          placeholder="Filter employee..."
          class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none shadow-sm"
        />
      </div>

      <!-- Loading -->
      <div v-if="loadingMonthly" class="flex justify-center py-16">
        <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
      </div>
      <div v-else-if="errorMonthly" class="text-center text-red-500 font-medium py-8">{{ errorMonthly }}</div>

      <!-- Monthly Table -->
      <div v-else class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-left text-sm">
          <thead class="bg-gray-50 border-b border-gray-200 text-gray-700 font-semibold">
            <tr>
              <th class="px-6 py-4">Employee</th>
              <th class="px-6 py-4 text-center">Present</th>
              <th class="px-6 py-4 text-center">Late</th>
              <th class="px-6 py-4 text-center">Absent</th>
              <th class="px-6 py-4 text-center">Half Day</th>
              <th class="px-6 py-4 text-center">Late Minutes</th>
              <th class="px-6 py-4 text-center">Working Days</th>
              <th class="px-6 py-4"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <template v-for="emp in filteredMonthlyEmployees" :key="emp.user?.id">
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">
                      {{ emp.user?.name?.charAt(0) || 'E' }}
                    </div>
                    <div>
                      <div class="font-semibold text-gray-900">{{ emp.user?.name }}</div>
                      <div class="text-xs text-gray-400">{{ emp.user?.employee?.department?.name }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-center"><span class="px-2 py-1 bg-green-50 text-green-700 rounded-full text-xs font-bold">{{ emp.present }}</span></td>
                <td class="px-6 py-4 text-center"><span class="px-2 py-1 bg-amber-50 text-amber-700 rounded-full text-xs font-bold">{{ emp.late }}</span></td>
                <td class="px-6 py-4 text-center"><span class="px-2 py-1 bg-red-50 text-red-700 rounded-full text-xs font-bold">{{ emp.absent }}</span></td>
                <td class="px-6 py-4 text-center"><span class="px-2 py-1 bg-purple-50 text-purple-700 rounded-full text-xs font-bold">{{ emp.half_day }}</span></td>
                <td class="px-6 py-4 text-center font-mono text-amber-600 font-semibold text-sm">{{ emp.total_late_minutes || 0 }} min</td>
                <td class="px-6 py-4 text-center font-bold text-gray-700">{{ emp.working_days }}</td>
                <td class="px-6 py-4 text-right">
                  <button
                    @click="toggleExpand(emp.user?.id)"
                    class="p-1.5 text-gray-400 hover:text-blue-600 transition-colors rounded-lg hover:bg-blue-50"
                    :title="expandedUser === emp.user?.id ? 'Collapse' : 'View Daily'"
                  >
                    <ChevronRight class="w-4 h-4 transition-transform" :class="expandedUser === emp.user?.id ? 'rotate-90' : ''" />
                  </button>
                </td>
              </tr>

              <!-- Expanded Daily Records -->
              <tr v-if="expandedUser === emp.user?.id">
                <td colspan="8" class="bg-gray-50/80 px-6 py-4">
                  <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full text-xs text-left">
                      <thead class="bg-white border-b border-gray-200 text-gray-500 font-semibold">
                        <tr>
                          <th class="px-4 py-2.5">Date</th>
                          <th class="px-4 py-2.5">Check-In</th>
                          <th class="px-4 py-2.5">Check-Out</th>
                          <th class="px-4 py-2.5">Late (min)</th>
                          <th class="px-4 py-2.5">Status</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="rec in emp.records" :key="rec.id">
                          <td class="px-4 py-2.5 font-medium text-gray-700">{{ rec.date }}</td>
                          <td class="px-4 py-2.5 font-mono">{{ rec.check_in ? fmtTime(rec.check_in) : '--:--' }}</td>
                          <td class="px-4 py-2.5 font-mono">{{ rec.check_out ? fmtTime(rec.check_out) : '--:--' }}</td>
                          <td class="px-4 py-2.5 text-amber-600 font-medium">{{ rec.late_minutes || '—' }}</td>
                          <td class="px-4 py-2.5">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold capitalize" :class="statusColors[rec.status]">
                              {{ rec.status.replace('_', ' ') }}
                            </span>
                          </td>
                        </tr>
                        <tr v-if="emp.records.length === 0">
                          <td colspan="5" class="px-4 py-4 text-center text-gray-400">No records this month.</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </td>
              </tr>
            </template>
            <tr v-if="filteredMonthlyEmployees.length === 0">
              <td colspan="8" class="px-6 py-12 text-center text-gray-400 font-medium">No data found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- ════ Edit Modal ════ -->
    <Teleport to="body">
      <div v-if="showEditModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <div>
              <h3 class="font-bold text-gray-900 text-lg">Update Attendance Log</h3>
              <p class="text-xs text-gray-400 mt-0.5">Editing: {{ editingRecord?.user?.name }}</p>
            </div>
            <button @click="showEditModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all">
              <X class="w-4 h-4" />
            </button>
          </div>
          <form @submit.prevent="handleUpdate" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5">Status</label>
              <select v-model="editForm.status" class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all">
                <option value="present">Present</option>
                <option value="late">Late</option>
                <option value="absent">Absent</option>
                <option value="half_day">Half Day</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Check-In</label>
                <input v-model="editForm.check_in" type="text" placeholder="09:00:00" class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all" />
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Check-Out</label>
                <input v-model="editForm.check_out" type="text" placeholder="17:00:00" class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Late Minutes</label>
                <input v-model="editForm.late_minutes" type="number" min="0" placeholder="0" class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all" />
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Notes</label>
                <input v-model="editForm.notes" type="text" placeholder="Optional note" class="block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all" />
              </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
              <button type="button" @click="showEditModal = false" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-xl text-sm font-medium transition-colors">Cancel</button>
              <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 text-sm font-semibold transition-colors">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
