<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useLeaveStore } from '../../stores/leave';
import { useAdminStore } from '../../stores/admin';
import { useAuthStore } from '../../stores/auth';
import {
  Plane, CheckCircle2, XCircle, Clock, Trash2, Search,
  ChevronDown, X, AlertCircle, CalendarDays, Users, TrendingUp, Filter,
  Calendar, Plus, Edit, Check, Settings
} from 'lucide-vue-next';

const leaveStore = useLeaveStore();
const adminStore = useAdminStore();
const authStore = useAuthStore();

// Tab state: 'requests' | 'holidays' | 'weekly-off'
const activeTab = ref('requests');

// Leave Requests Filtering & Actions
const filters = ref({ status: '', user_id: '', type: '', month: '', year: new Date().getFullYear() });
const showApproveModal = ref(false);
const actionLeave = ref(null);
const actionType = ref(''); // 'approve' | 'reject'
const adminNote = ref('');
const actionLoading = ref(false);
const actionError = ref('');
const searchQuery = ref('');

const leaveTypes = [
  { value: 'sick', label: 'Sick Leave', color: 'text-rose-600 bg-rose-50 border-rose-200' },
  { value: 'casual', label: 'Casual Leave', color: 'text-amber-600 bg-amber-50 border-amber-200' },
  { value: 'annual', label: 'Annual Leave', color: 'text-blue-600 bg-blue-50 border-blue-200' },
  { value: 'earned', label: 'Earned Leave', color: 'text-violet-600 bg-violet-50 border-violet-200' },
  { value: 'maternity', label: 'Maternity Leave', color: 'text-pink-600 bg-pink-50 border-pink-200' },
  { value: 'paternity', label: 'Paternity Leave', color: 'text-cyan-600 bg-cyan-50 border-cyan-200' },
  { value: 'unpaid', label: 'Unpaid Leave', color: 'text-slate-600 bg-slate-50 border-slate-200' },
];

const getTypeInfo = (type) => leaveTypes.find(t => t.value === type) || { label: type, color: 'text-slate-600 bg-slate-50 border-slate-200' };

const statusConfig = {
  pending:   { label: 'Pending',   class: 'text-amber-700 bg-amber-50 border-amber-300',   dot: 'bg-amber-500' },
  approved:  { label: 'Approved',  class: 'text-emerald-700 bg-emerald-50 border-emerald-300', dot: 'bg-emerald-500' },
  rejected:  { label: 'Rejected',  class: 'text-rose-700 bg-rose-50 border-rose-300',     dot: 'bg-rose-500' },
  cancelled: { label: 'Cancelled', class: 'text-slate-600 bg-slate-100 border-slate-300',  dot: 'bg-slate-400' },
};

const stats = computed(() => {
  const all = leaveStore.leaves;
  return {
    total:    all.length,
    pending:  all.filter(l => l.status === 'pending').length,
    approved: all.filter(l => l.status === 'approved').length,
    rejected: all.filter(l => l.status === 'rejected').length,
  };
});

const filteredLeaves = computed(() => {
  let list = leaveStore.leaves;
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(l =>
      l.user?.name?.toLowerCase().includes(q) ||
      l.user?.employee?.department?.name?.toLowerCase().includes(q)
    );
  }
  return list;
});

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

const loadLeaves = () => {
  const f = {};
  if (filters.value.status) f.status = filters.value.status;
  if (filters.value.type) f.type = filters.value.type;
  if (filters.value.user_id) f.user_id = filters.value.user_id;
  if (filters.value.month) f.month = filters.value.month;
  if (filters.value.year) f.year = filters.value.year;
  leaveStore.fetchLeaves(f);
};

const openAction = (leave, type) => {
  actionLeave.value = leave;
  actionType.value = type;
  adminNote.value = '';
  actionError.value = '';
  showApproveModal.value = true;
};

const confirmAction = async () => {
  actionLoading.value = true;
  actionError.value = '';
  try {
    await leaveStore.updateLeaveStatus(
      actionLeave.value.id,
      actionType.value === 'approve' ? 'approved' : 'rejected',
      adminNote.value
    );
    showApproveModal.value = false;
  } catch (err) {
    actionError.value = err.response?.data?.message || 'Action failed.';
  } finally {
    actionLoading.value = false;
  }
};

const handleDelete = async (id) => {
  if (!confirm('Delete this leave record?')) return;
  try {
    await leaveStore.deleteLeave(id);
  } catch {}
};

// --- Holidays Management State & Actions ---
const holidayYear = ref(new Date().getFullYear());
const showHolidayModal = ref(false);
const holidayLoading = ref(false);
const holidayError = ref('');
const holidayForm = ref({
  id: null,
  name: '',
  date: '',
  type: 'national',
  is_recurring: false,
  description: ''
});

const isEditingHoliday = computed(() => !!holidayForm.value.id);

const holidayTypes = [
  { value: 'national', label: 'National Holiday', color: 'text-orange-700 bg-orange-50 border-orange-200' },
  { value: 'company', label: 'Company Holiday', color: 'text-indigo-700 bg-indigo-50 border-indigo-200' },
  { value: 'optional', label: 'Optional Holiday', color: 'text-emerald-700 bg-emerald-50 border-emerald-200' }
];

const getHolidayTypeLabel = (type) => {
  return holidayTypes.find(t => t.value === type)?.label || type;
};

const getHolidayTypeClass = (type) => {
  return holidayTypes.find(t => t.value === type)?.color || 'text-slate-600 bg-slate-50 border-slate-200';
};

const loadHolidays = async () => {
  try {
    await leaveStore.fetchHolidays(holidayYear.value);
  } catch {}
};

const openAddHoliday = () => {
  holidayForm.value = {
    id: null,
    name: '',
    date: new Date().toISOString().split('T')[0],
    type: 'national',
    is_recurring: false,
    description: ''
  };
  holidayError.value = '';
  showHolidayModal.value = true;
};

const openEditHoliday = (h) => {
  holidayForm.value = {
    id: h.id,
    name: h.name,
    date: h.display_date || (h.date ? h.date.split('T')[0] : ''),
    type: h.type,
    is_recurring: !!h.is_recurring,
    description: h.description || ''
  };
  holidayError.value = '';
  showHolidayModal.value = true;
};

const saveHoliday = async () => {
  if (!holidayForm.value.name || !holidayForm.value.date || !holidayForm.value.type) {
    holidayError.value = 'Please fill in all required fields.';
    return;
  }
  holidayLoading.value = true;
  holidayError.value = '';
  try {
    if (isEditingHoliday.value) {
      await leaveStore.updateHoliday(holidayForm.value.id, holidayForm.value);
    } else {
      await leaveStore.addHoliday(holidayForm.value);
    }
    showHolidayModal.value = false;
    await loadHolidays();
  } catch (err) {
    holidayError.value = err.response?.data?.message || 'Failed to save holiday.';
  } finally {
    holidayLoading.value = false;
  }
};

const deleteHoliday = async (id) => {
  if (!confirm('Are you sure you want to delete this holiday?')) return;
  try {
    await leaveStore.deleteHoliday(id);
    await loadHolidays();
  } catch (err) {
    alert('Failed to delete holiday.');
  }
};

// --- Weekly Off Configuration ---
const weekdaysList = [
  { value: 'monday', label: 'Monday' },
  { value: 'tuesday', label: 'Tuesday' },
  { value: 'wednesday', label: 'Wednesday' },
  { value: 'thursday', label: 'Thursday' },
  { value: 'friday', label: 'Friday' },
  { value: 'saturday', label: 'Saturday' },
  { value: 'sunday', label: 'Sunday' }
];

const weeklyOffDays = ref([]);
const savingWeeklyOff = ref(false);
const weeklyOffSuccess = ref('');

const initWeeklyOff = () => {
  const company = authStore.user?.company;
  if (company && company.settings && company.settings.weekly_off) {
    weeklyOffDays.value = [...company.settings.weekly_off];
  } else {
    weeklyOffDays.value = ['saturday', 'sunday'];
  }
};

const toggleWeeklyOff = (day) => {
  if (weeklyOffDays.value.includes(day)) {
    weeklyOffDays.value = weeklyOffDays.value.filter(d => d !== day);
  } else {
    weeklyOffDays.value.push(day);
  }
};

const saveWeeklyOff = async () => {
  savingWeeklyOff.value = true;
  weeklyOffSuccess.value = '';
  try {
    await leaveStore.updateWeeklyOff(weeklyOffDays.value);
    await authStore.fetchCurrentUser(); // Refresh company settings locally
    weeklyOffSuccess.value = 'Weekly off settings updated successfully!';
    setTimeout(() => {
      weeklyOffSuccess.value = '';
    }, 4000);
  } catch (err) {
    alert('Failed to update weekly off settings.');
  } finally {
    savingWeeklyOff.value = false;
  }
};

onMounted(async () => {
  await Promise.all([
    loadLeaves(),
    adminStore.fetchEmployees(),
    loadHolidays(),
  ]);
  initWeeklyOff();
});

watch(() => authStore.user, () => {
  initWeeklyOff();
}, { deep: true });
</script>

<template>
  <div class="max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
          <Plane class="w-6 h-6 text-indigo-600" />
          Leave Management
        </h1>
        <p class="text-gray-500 mt-1 text-sm">Review leave requests, configure company holidays, and setup weekly off days</p>
      </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-slate-200">
      <nav class="-mb-px flex space-x-6" aria-label="Tabs">
        <button
          @click="activeTab = 'requests'"
          :class="[
            activeTab === 'requests'
              ? 'border-indigo-600 text-indigo-600'
              : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
            'whitespace-nowrap pb-4 px-1 border-b-2 font-bold text-sm transition-all flex items-center gap-2'
          ]"
        >
          <Plane class="w-4 h-4" />
          Leave Requests
        </button>
        <button
          @click="activeTab = 'holidays'"
          :class="[
            activeTab === 'holidays'
              ? 'border-indigo-600 text-indigo-600'
              : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
            'whitespace-nowrap pb-4 px-1 border-b-2 font-bold text-sm transition-all flex items-center gap-2'
          ]"
        >
          <CalendarDays class="w-4 h-4" />
          Company Holidays
        </button>
        <button
          @click="activeTab = 'weekly-off'"
          :class="[
            activeTab === 'weekly-off'
              ? 'border-indigo-600 text-indigo-600'
              : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
            'whitespace-nowrap pb-4 px-1 border-b-2 font-bold text-sm transition-all flex items-center gap-2'
          ]"
        >
          <Settings class="w-4 h-4" />
          Weekly Off Days
        </button>
      </nav>
    </div>

    <!-- TAB 1: LEAVE REQUESTS -->
    <div v-if="activeTab === 'requests'" class="space-y-6">
      <!-- Stats Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
          <div class="flex items-center justify-between">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total</p>
            <TrendingUp class="w-4 h-4 text-slate-400" />
          </div>
          <p class="text-3xl font-black text-slate-900 mt-2">{{ stats.total }}</p>
        </div>
        <div class="bg-amber-50 rounded-2xl border border-amber-200 p-4 shadow-sm">
          <div class="flex items-center justify-between">
            <p class="text-xs font-bold text-amber-600 uppercase tracking-wider">Pending</p>
            <Clock class="w-4 h-4 text-amber-500" />
          </div>
          <p class="text-3xl font-black text-amber-700 mt-2">{{ stats.pending }}</p>
        </div>
        <div class="bg-emerald-50 rounded-2xl border border-emerald-200 p-4 shadow-sm">
          <div class="flex items-center justify-between">
            <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Approved</p>
            <CheckCircle2 class="w-4 h-4 text-emerald-500" />
          </div>
          <p class="text-3xl font-black text-emerald-700 mt-2">{{ stats.approved }}</p>
        </div>
        <div class="bg-rose-50 rounded-2xl border border-rose-200 p-4 shadow-sm">
          <div class="flex items-center justify-between">
            <p class="text-xs font-bold text-rose-600 uppercase tracking-wider">Rejected</p>
            <XCircle class="w-4 h-4 text-rose-500" />
          </div>
          <p class="text-3xl font-black text-rose-700 mt-2">{{ stats.rejected }}</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <div class="flex flex-wrap gap-3 items-center">
          <div class="relative flex-1 min-w-[180px]">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input v-model="searchQuery" placeholder="Search employee..." class="w-full pl-9 pr-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-300" />
          </div>
          <select v-model="filters.status" @change="loadLeaves" class="px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-300">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="cancelled">Cancelled</option>
          </select>
          <select v-model="filters.type" @change="loadLeaves" class="px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-300">
            <option value="">All Types</option>
            <option v-for="t in leaveTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
          <select v-model="filters.user_id" @change="loadLeaves" class="px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-300">
            <option value="">All Employees</option>
            <option v-for="emp in adminStore.employees" :key="emp.user_id" :value="emp.user_id">{{ emp.user?.name }}</option>
          </select>
          <select v-model="filters.month" @change="loadLeaves" class="px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-300">
            <option value="">All Months</option>
            <option v-for="(m, i) in ['January','February','March','April','May','June','July','August','September','October','November','December']" :key="i" :value="i+1">{{ m }}</option>
          </select>
        </div>
      </div>

      <!-- Leaves Table -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div v-if="leaveStore.loading" class="p-12 flex flex-col items-center gap-3 text-slate-500">
          <div class="w-8 h-8 border-3 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
          <span class="text-sm font-semibold">Loading leave requests...</span>
        </div>
        <table v-else class="w-full text-sm text-left">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-5 py-3.5 text-[10px] font-extrabold uppercase tracking-widest text-slate-500">Employee</th>
              <th class="px-5 py-3.5 text-[10px] font-extrabold uppercase tracking-widest text-slate-500">Type</th>
              <th class="px-5 py-3.5 text-[10px] font-extrabold uppercase tracking-widest text-slate-500">Dates</th>
              <th class="px-5 py-3.5 text-[10px] font-extrabold uppercase tracking-widest text-slate-500">Days</th>
              <th class="px-5 py-3.5 text-[10px] font-extrabold uppercase tracking-widest text-slate-500">Reason</th>
              <th class="px-5 py-3.5 text-[10px] font-extrabold uppercase tracking-widest text-slate-500">Status</th>
              <th class="px-5 py-3.5 text-[10px] font-extrabold uppercase tracking-widest text-slate-500 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="leave in filteredLeaves" :key="leave.id" class="hover:bg-slate-50/60 transition-colors">
              <td class="px-5 py-3.5">
                <div class="font-bold text-slate-900 text-sm leading-tight">{{ leave.user?.name || '—' }}</div>
                <div class="text-[10px] text-slate-400 font-semibold mt-0.5">{{ leave.user?.employee?.department?.name || '—' }}</div>
              </td>
              <td class="px-5 py-3.5">
                <span :class="['inline-block px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border', getTypeInfo(leave.type).color]">
                  {{ getTypeInfo(leave.type).label }}
                </span>
              </td>
              <td class="px-5 py-3.5">
                <div class="text-xs font-semibold text-slate-700">{{ formatDate(leave.start_date) }}</div>
                <div class="text-[10px] text-slate-400 font-semibold">to {{ formatDate(leave.end_date) }}</div>
              </td>
              <td class="px-5 py-3.5">
                <span class="text-sm font-black text-slate-800">{{ leave.days ?? '—' }}</span>
                <span class="text-[10px] text-slate-400 ml-1">day{{ (leave.days > 1) ? 's' : '' }}</span>
              </td>
              <td class="px-5 py-3.5 max-w-[180px]">
                <p class="text-xs text-slate-600 truncate" :title="leave.reason">{{ leave.reason || '—' }}</p>
                <p v-if="leave.admin_note" class="text-[10px] text-indigo-500 font-semibold mt-0.5 truncate" :title="leave.admin_note">Note: {{ leave.admin_note }}</p>
              </td>
              <td class="px-5 py-3.5">
                <span :class="['inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border', statusConfig[leave.status]?.class]">
                  <span :class="['w-1.5 h-1.5 rounded-full', statusConfig[leave.status]?.dot]"></span>
                  {{ statusConfig[leave.status]?.label || leave.status }}
                </span>
              </td>
              <td class="px-5 py-3.5 text-right">
                <div class="flex justify-end gap-2">
                  <template v-if="leave.status === 'pending'">
                    <button @click="openAction(leave, 'approve')" class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border border-emerald-200 transition-all" title="Approve">
                      <CheckCircle2 class="w-4 h-4" />
                    </button>
                    <button @click="openAction(leave, 'reject')" class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200 transition-all" title="Reject">
                      <XCircle class="w-4 h-4" />
                    </button>
                  </template>
                  <button @click="handleDelete(leave.id)" class="p-1.5 rounded-lg bg-slate-50 text-slate-500 hover:bg-slate-100 border border-slate-200 transition-all" title="Delete">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredLeaves.length === 0 && !leaveStore.loading">
              <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-semibold italic">
                No leave requests found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Approve/Reject Modal -->
      <div v-if="showApproveModal && actionLeave" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md animate-in fade-in zoom-in-95 duration-200">
          <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-900">
              {{ actionType === 'approve' ? '✅ Approve Leave' : '❌ Reject Leave' }}
            </h3>
            <button @click="showApproveModal = false" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-1.5 transition-colors">
              <X class="w-4 h-4" />
            </button>
          </div>
          <div class="p-6 space-y-4">
            <div class="bg-slate-50 rounded-xl p-4 space-y-2 border border-slate-100">
              <div class="flex justify-between text-sm">
                <span class="text-slate-500 font-medium">Employee</span>
                <span class="font-bold text-slate-800">{{ actionLeave.user?.name }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-500 font-medium">Type</span>
                <span class="font-semibold text-slate-700">{{ getTypeInfo(actionLeave.type).label }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-500 font-medium">Duration</span>
                <span class="font-semibold text-slate-700">{{ formatDate(actionLeave.start_date) }} → {{ formatDate(actionLeave.end_date) }} ({{ actionLeave.days }} day{{ actionLeave.days > 1 ? 's' : '' }})</span>
              </div>
              <div v-if="actionLeave.reason" class="text-sm">
                <span class="text-slate-500 font-medium">Reason: </span>
                <span class="text-slate-700">{{ actionLeave.reason }}</span>
              </div>
            </div>

            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                Admin Note <span class="text-slate-400 font-normal">(optional)</span>
              </label>
              <textarea
                v-model="adminNote"
                rows="3"
                :placeholder="actionType === 'reject' ? 'Reason for rejection...' : 'Any notes for the employee...'"
                class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none"
              ></textarea>
            </div>

            <div v-if="actionError" class="flex items-center gap-2 text-sm text-rose-600 bg-rose-50 border border-rose-200 rounded-xl px-4 py-3">
              <AlertCircle class="w-4 h-4 shrink-0" />
              {{ actionError }}
            </div>
          </div>
          <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-3">
            <button @click="showApproveModal = false" class="px-4 py-2 border border-slate-200 rounded-xl text-slate-700 hover:bg-slate-50 font-semibold text-sm transition-colors">
              Cancel
            </button>
            <button
              @click="confirmAction"
              :disabled="actionLoading"
              :class="['px-5 py-2 rounded-xl font-bold text-sm text-white transition-all flex items-center gap-2', actionType === 'approve' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700']"
            >
              <span v-if="actionLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              {{ actionLoading ? 'Processing...' : (actionType === 'approve' ? 'Approve Leave' : 'Reject Leave') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: COMPANY HOLIDAYS -->
    <div v-else-if="activeTab === 'holidays'" class="space-y-6">
      <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <label class="text-sm font-semibold text-slate-700">Filter Year:</label>
          <select v-model="holidayYear" @change="loadHolidays" class="px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-300">
            <option v-for="y in [2025, 2026, 2027, 2028]" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <button @click="openAddHoliday" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-sm transition-all shadow-sm">
          <Plus class="w-4 h-4" />
          Add Holiday
        </button>
      </div>

      <!-- Holidays Table -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div v-if="leaveStore.loading" class="p-12 flex flex-col items-center gap-3 text-slate-500">
          <div class="w-8 h-8 border-3 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
          <span class="text-sm font-semibold">Loading company holidays...</span>
        </div>
        <table v-else class="w-full text-sm text-left">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-5 py-3.5 text-[10px] font-extrabold uppercase tracking-widest text-slate-500">Holiday Name</th>
              <th class="px-5 py-3.5 text-[10px] font-extrabold uppercase tracking-widest text-slate-500">Date</th>
              <th class="px-5 py-3.5 text-[10px] font-extrabold uppercase tracking-widest text-slate-500">Type</th>
              <th class="px-5 py-3.5 text-[10px] font-extrabold uppercase tracking-widest text-slate-500">Recurring</th>
              <th class="px-5 py-3.5 text-[10px] font-extrabold uppercase tracking-widest text-slate-500">Description</th>
              <th class="px-5 py-3.5 text-[10px] font-extrabold uppercase tracking-widest text-slate-500 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="holiday in (leaveStore.holidays || [])" :key="holiday.id" class="hover:bg-slate-50/60 transition-colors">
              <td class="px-5 py-3.5 font-bold text-slate-900">{{ holiday.name }}</td>
              <td class="px-5 py-3.5 font-semibold text-slate-700">
                {{ formatDate(holiday.display_date || holiday.date) }}
              </td>
              <td class="px-5 py-3.5">
                <span :class="['inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border', getHolidayTypeClass(holiday.type)]">
                  {{ getHolidayTypeLabel(holiday.type) }}
                </span>
              </td>
              <td class="px-5 py-3.5">
                <span v-if="holiday.is_recurring" class="inline-flex items-center gap-1 text-indigo-600 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider">
                  <Check class="w-3 h-3" /> Yes
                </span>
                <span v-else class="text-slate-400 text-xs font-semibold">No (One-time)</span>
              </td>
              <td class="px-5 py-3.5 max-w-[200px]">
                <p class="text-xs text-slate-600 truncate" :title="holiday.description">{{ holiday.description || '—' }}</p>
              </td>
              <td class="px-5 py-3.5 text-right">
                <div class="flex justify-end gap-2">
                  <button @click="openEditHoliday(holiday)" class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 border border-indigo-200 transition-all" title="Edit Holiday">
                    <Edit class="w-4 h-4" />
                  </button>
                  <button @click="deleteHoliday(holiday.id)" class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200 transition-all" title="Delete Holiday">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="(!leaveStore.holidays || leaveStore.holidays.length === 0) && !leaveStore.loading">
              <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-semibold italic">
                No company holidays configured for {{ holidayYear }}.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Add/Edit Holiday Modal -->
      <div v-if="showHolidayModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md animate-in fade-in zoom-in-95 duration-200">
          <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-900 flex items-center gap-2">
              <Calendar class="w-5 h-5 text-indigo-600" />
              {{ isEditingHoliday ? '✏️ Edit Company Holiday' : '📅 Add Company Holiday' }}
            </h3>
            <button @click="showHolidayModal = false" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-1.5 transition-colors">
              <X class="w-4 h-4" />
            </button>
          </div>
          <div class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1.5">Holiday Name *</label>
              <input v-model="holidayForm.name" type="text" placeholder="e.g. Eid-ul-Fitr, Independence Day" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300" />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Holiday Date *</label>
                <input v-model="holidayForm.date" type="date" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300" />
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Holiday Type *</label>
                <select v-model="holidayForm.type" class="w-full px-3 py-2.5 text-sm border border-slate-200 bg-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300">
                  <option v-for="t in holidayTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
              </div>
            </div>

            <!-- Recurring checkbox option -->
            <div class="flex items-center gap-2.5 bg-slate-50 border border-slate-100 p-3 rounded-xl">
              <input v-model="holidayForm.is_recurring" type="checkbox" id="is_recurring" class="w-4.5 h-4.5 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500" />
              <label for="is_recurring" class="text-sm font-bold text-slate-700 cursor-pointer selection:bg-transparent">
                Recurring Holiday <span class="text-xs text-slate-400 font-normal">(occurs on the same month & day every year)</span>
              </label>
            </div>

            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1.5">Description <span class="text-slate-400 font-normal">(optional)</span></label>
              <textarea v-model="holidayForm.description" rows="3" placeholder="Additional details..." class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none"></textarea>
            </div>

            <!-- Error -->
            <div v-if="holidayError" class="flex items-center gap-2 text-sm text-rose-600 bg-rose-50 border border-rose-200 rounded-xl px-4 py-3">
              <AlertCircle class="w-4 h-4 shrink-0" />
              {{ holidayError }}
            </div>
          </div>
          <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-3">
            <button @click="showHolidayModal = false" class="px-4 py-2 border border-slate-200 rounded-xl text-slate-700 hover:bg-slate-50 font-semibold text-sm transition-colors">
              Cancel
            </button>
            <button
              @click="saveHoliday"
              :disabled="holidayLoading"
              class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-sm transition-all flex items-center gap-2"
            >
              <span v-if="holidayLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              {{ holidayLoading ? 'Saving...' : (isEditingHoliday ? 'Save Changes' : 'Add Holiday') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 3: WEEKLY OFF DAYS -->
    <div v-else-if="activeTab === 'weekly-off'" class="space-y-6">
      <div class="max-w-2xl bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-6">
        <div>
          <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <Settings class="w-5 h-5 text-indigo-600" />
            Weekly Off Configuration
          </h2>
          <p class="text-sm text-slate-500 mt-1">
            Define which days of the week are default company off-days.
            These days are excluded automatically when calculating employee leave balances.
          </p>
        </div>

        <!-- Weekly Off Days Selector Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <button
            v-for="day in weekdaysList"
            :key="day.value"
            @click="toggleWeeklyOff(day.value)"
            :class="[
              weeklyOffDays.includes(day.value)
                ? 'border-indigo-600 bg-indigo-50 text-indigo-700 ring-2 ring-indigo-300 ring-offset-1'
                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
              'px-4 py-3 rounded-xl border text-sm font-bold text-center transition-all flex flex-col items-center gap-1.5 justify-center'
            ]"
          >
            <CheckCircle2
              v-if="weeklyOffDays.includes(day.value)"
              class="w-4 h-4 text-indigo-600"
            />
            <Clock
              v-else
              class="w-4 h-4 text-slate-400"
            />
            {{ day.label }}
          </button>
        </div>

        <!-- Save Banner / Status -->
        <div v-if="weeklyOffSuccess" class="flex items-center gap-2.5 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3">
          <CheckCircle2 class="w-4 h-4 shrink-0 text-emerald-600" />
          <span>{{ weeklyOffSuccess }}</span>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end pt-2 border-t border-slate-100">
          <button
            @click="saveWeeklyOff"
            :disabled="savingWeeklyOff"
            class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-sm transition-all shadow-md disabled:opacity-60"
          >
            <span v-if="savingWeeklyOff" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <Check v-else class="w-4 h-4" />
            {{ savingWeeklyOff ? 'Saving Settings...' : 'Save Settings' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
