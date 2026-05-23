<script setup>
import { ref, computed, onMounted } from 'vue';
import { useLeaveStore } from '../../stores/leave';
import { useAuthStore } from '../../stores/auth';
import {
  Plane, Plus, X, CheckCircle2, XCircle, Clock, AlertCircle,
  CalendarDays, HeartPulse, Briefcase, TreePalm, BadgeInfo,
  Calendar, Check
} from 'lucide-vue-next';

const leaveStore = useLeaveStore();
const authStore = useAuthStore();

const showApplyModal = ref(false);
const applying = ref(false);
const applyError = ref('');
const cancelingId = ref(null);

const form = ref({
  type: '',
  start_date: '',
  end_date: '',
  reason: '',
});

const leaveTypes = [
  { value: 'sick',       label: 'Sick Leave',      icon: HeartPulse,   color: 'text-rose-600 bg-rose-50 border-rose-200',     limit: 12 },
  { value: 'casual',     label: 'Casual Leave',    icon: Plane,        color: 'text-amber-600 bg-amber-50 border-amber-200',  limit: 12 },
  { value: 'annual',     label: 'Annual Leave',    icon: TreePalm,     color: 'text-blue-600 bg-blue-50 border-blue-200',     limit: 18 },
  { value: 'earned',     label: 'Earned Leave',    icon: Briefcase,    color: 'text-violet-600 bg-violet-50 border-violet-200', limit: 15 },
  { value: 'maternity',  label: 'Maternity Leave', icon: BadgeInfo,    color: 'text-pink-600 bg-pink-50 border-pink-200',     limit: 180 },
  { value: 'paternity',  label: 'Paternity Leave', icon: BadgeInfo,    color: 'text-cyan-600 bg-cyan-50 border-cyan-200',     limit: 15 },
  { value: 'unpaid',     label: 'Unpaid Leave',    icon: AlertCircle,  color: 'text-slate-600 bg-slate-100 border-slate-300', limit: null },
];

const getLimit = (type) => {
  if (type === 'unpaid') return null;
  if (leaveStore.stats?.allowances && leaveStore.stats.allowances[type] !== undefined) {
    return leaveStore.stats.allowances[type];
  }
  return leaveTypes.find(t => t.value === type)?.limit || 0;
};

const getTypeInfo = (type) => leaveTypes.find(t => t.value === type) || { label: type, color: 'text-slate-600 bg-slate-100 border-slate-300' };

const statusConfig = {
  pending:   { label: 'Pending',   class: 'text-amber-700 bg-amber-50 border-amber-300',    dot: 'bg-amber-500 animate-pulse' },
  approved:  { label: 'Approved',  class: 'text-emerald-700 bg-emerald-50 border-emerald-300', dot: 'bg-emerald-500' },
  rejected:  { label: 'Rejected',  class: 'text-rose-700 bg-rose-50 border-rose-300',      dot: 'bg-rose-500' },
  cancelled: { label: 'Cancelled', class: 'text-slate-600 bg-slate-100 border-slate-300',   dot: 'bg-slate-400' },
};

const holidayTypes = [
  { value: 'national', label: 'National Holiday' },
  { value: 'company', label: 'Company Holiday' },
  { value: 'optional', label: 'Optional Holiday' }
];

const getHolidayTypeLabel = (type) => {
  return holidayTypes.find(t => t.value === type)?.label || type;
};

const stats = computed(() => leaveStore.stats?.used || {});
const pendingCount = computed(() => leaveStore.stats?.pending_count || 0);

const todayStr = ref(new Date().toISOString().split('T')[0]);

// Dynamic working day calculation (excluding company weekly off + company holidays)
const estimatedDays = computed(() => {
  if (!form.value.start_date || !form.value.end_date) return 0;
  const start = new Date(form.value.start_date);
  const end = new Date(form.value.end_date);
  if (end < start) return 0;

  // Get weekly off days configured for company
  const company = authStore.user?.company;
  const weeklyOff = company?.settings?.weekly_off || ['saturday', 'sunday'];
  const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
  const offDayNums = weeklyOff.map(d => dayNames.indexOf(d.toLowerCase()));

  // Map company holidays to actual date strings in start_date's year
  const holidaysList = leaveStore.holidays || [];
  const holidayDates = holidaysList.map(h => {
    if (h.is_recurring) {
      const year = start.getFullYear();
      const datePart = h.date ? h.date.split('T')[0].substring(5) : ''; // MM-DD
      return `${year}-${datePart}`;
    }
    return h.date ? h.date.split('T')[0] : '';
  });

  let days = 0;
  const cur = new Date(start);
  while (cur <= end) {
    const dayOfWeek = cur.getDay();
    const isWeeklyOff = offDayNums.includes(dayOfWeek);

    const yyyy = cur.getFullYear();
    const mm = String(cur.getMonth() + 1).padStart(2, '0');
    const dd = String(cur.getDate()).padStart(2, '0');
    const dateStr = `${yyyy}-${mm}-${dd}`;

    const isHoliday = holidayDates.includes(dateStr);

    if (!isWeeklyOff && !isHoliday) {
      days++;
    }
    cur.setDate(cur.getDate() + 1);
  }
  return Math.max(1, days);
});

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

// Computed properties for holiday display
const upcomingHolidays = computed(() => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const holidaysList = leaveStore.holidays || [];
  return holidaysList
    .filter(h => {
      const dateStr = h.display_date || (h.date ? h.date.split('T')[0] : '');
      if (!dateStr) return false;
      const holidayDate = new Date(dateStr);
      return holidayDate >= today;
    })
    .sort((a, b) => {
      const ad = a.display_date || (a.date ? a.date.split('T')[0] : '');
      const bd = b.display_date || (b.date ? b.date.split('T')[0] : '');
      return new Date(ad) - new Date(bd);
    });
});

const weeklyOffDaysList = computed(() => {
  const company = authStore.user?.company;
  const days = company?.settings?.weekly_off || ['saturday', 'sunday'];
  return days.map(d => d.charAt(0).toUpperCase() + d.slice(1));
});

const resetForm = () => {
  form.value = { type: '', start_date: '', end_date: '', reason: '' };
  applyError.value = '';
};

const openApply = () => {
  resetForm();
  showApplyModal.value = true;
};

const submitLeave = async () => {
  if (!form.value.type || !form.value.start_date || !form.value.end_date) {
    applyError.value = 'Please fill in all required fields.';
    return;
  }
  applying.value = true;
  applyError.value = '';
  try {
    await leaveStore.applyLeave(form.value);
    await leaveStore.fetchStats();
    showApplyModal.value = false;
  } catch (err) {
    applyError.value = err.response?.data?.message || (err.response?.data?.errors
      ? Object.values(err.response.data.errors || {}).flat().join(', ')
      : 'Failed to submit leave request.');
  } finally {
    applying.value = false;
  }
};

const cancelLeave = async (id) => {
  if (!confirm('Cancel this leave request?')) return;
  cancelingId.value = id;
  try {
    await leaveStore.cancelLeave(id);
  } catch {}
  cancelingId.value = null;
};

onMounted(async () => {
  await Promise.all([
    leaveStore.fetchMyLeaves(),
    leaveStore.fetchStats(),
    leaveStore.fetchHolidays(),
  ]);
});
</script>

<template>
  <div class="max-w-5xl mx-auto space-y-6 pb-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
          <Plane class="w-7 h-7 text-indigo-600" />
          My Leave Requests
        </h1>
        <p class="text-gray-500 mt-1 text-sm">Apply for leave and track your requests.</p>
      </div>
      <button @click="openApply" class="flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold text-sm transition-all shadow-sm">
        <Plus class="w-4 h-4" />
        Apply for Leave
      </button>
    </div>

    <!-- Leave Balance Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <div v-for="type in leaveTypes.slice(0, 4)" :key="type.value"
        :class="['rounded-2xl border p-4 shadow-sm', type.color]"
      >
        <div class="flex items-center gap-2 mb-2">
          <component :is="type.icon" class="w-4 h-4" />
          <span class="text-[10px] font-extrabold uppercase tracking-wider">{{ type.label }}</span>
        </div>
        <div class="flex items-baseline gap-1">
          <span class="text-2xl font-black">{{ stats[type.value] || 0 }}</span>
          <span class="text-xs font-semibold opacity-70" v-if="getLimit(type.value) !== null">/ {{ getLimit(type.value) }} days used</span>
          <span class="text-xs font-semibold opacity-70" v-else> days used</span>
        </div>
      </div>
    </div>

    <!-- Pending indicator -->
    <div v-if="pendingCount > 0" class="flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-700">
      <Clock class="w-4 h-4 shrink-0 text-amber-500" />
      <span>You have <strong>{{ pendingCount }}</strong> pending leave request{{ pendingCount > 1 ? 's' : '' }} awaiting approval.</span>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column: Leave History (span 2) -->
      <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-slate-800">Leave History</h2>
            <span class="text-xs text-slate-400 font-semibold">{{ leaveStore.myLeaves.length }} records</span>
          </div>

          <div v-if="leaveStore.loading" class="p-12 flex flex-col items-center gap-3 text-slate-500">
            <div class="w-8 h-8 border-3 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
            <span class="text-sm font-semibold">Loading...</span>
          </div>

          <div v-else-if="leaveStore.myLeaves.length === 0" class="p-12 text-center text-slate-400">
            <Plane class="w-12 h-12 mx-auto mb-3 opacity-30" />
            <p class="font-semibold">No leave requests yet.</p>
            <p class="text-sm mt-1">Click "Apply for Leave" to submit your first request.</p>
          </div>

          <div v-else class="divide-y divide-slate-100">
            <div v-for="leave in leaveStore.myLeaves" :key="leave.id"
              class="px-5 py-4 hover:bg-slate-50/60 transition-colors"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 flex-wrap mb-1">
                    <span :class="['inline-block px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border', getTypeInfo(leave.type).color]">
                      {{ getTypeInfo(leave.type).label }}
                    </span>
                    <span :class="['inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border', statusConfig[leave.status]?.class]">
                      <span :class="['w-1.5 h-1.5 rounded-full', statusConfig[leave.status]?.dot]"></span>
                      {{ statusConfig[leave.status]?.label || leave.status }}
                    </span>
                  </div>
                  <div class="flex items-center gap-2 text-sm text-slate-700 font-semibold">
                    <CalendarDays class="w-3.5 h-3.5 text-slate-400" />
                    {{ formatDate(leave.start_date) }} — {{ formatDate(leave.end_date) }}
                    <span class="text-xs text-slate-400">({{ leave.days ?? estimatedDays }} day{{ (leave.days || 1) > 1 ? 's' : '' }})</span>
                  </div>
                  <p v-if="leave.reason" class="text-xs text-slate-500 mt-1 leading-relaxed">{{ leave.reason }}</p>
                  <div v-if="leave.admin_note" class="mt-2 flex items-start gap-1.5 text-xs text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-1.5">
                    <BadgeInfo class="w-3.5 h-3.5 shrink-0 mt-0.5" />
                    <span><strong>HR Note:</strong> {{ leave.admin_note }}</span>
                  </div>
                </div>
                <div class="shrink-0">
                  <button
                    v-if="leave.status === 'pending'"
                    @click="cancelLeave(leave.id)"
                    :disabled="cancelingId === leave.id"
                    class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 border border-slate-200 hover:border-rose-200 rounded-lg text-xs font-semibold transition-all"
                  >
                    <X class="w-3.5 h-3.5" />
                    Cancel
                  </button>
                  <div v-else-if="leave.status === 'approved'" class="flex items-center gap-1 text-emerald-500 text-xs font-bold">
                    <CheckCircle2 class="w-4 h-4" />
                    Approved
                  </div>
                  <div v-else-if="leave.status === 'rejected'" class="flex items-center gap-1 text-rose-500 text-xs font-bold">
                    <XCircle class="w-4 h-4" />
                    Rejected
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Holidays and Weekly Off -->
      <div class="space-y-6">
        <!-- Weekly Off Days Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
          <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
            <Clock class="w-4 h-4 text-indigo-500" />
            Weekly Off Days
          </h3>
          <div class="flex flex-wrap gap-2">
            <span v-for="day in weeklyOffDaysList" :key="day" class="px-2.5 py-1 bg-indigo-50 border border-indigo-150 text-indigo-700 rounded-lg text-xs font-bold">
              {{ day }}
            </span>
            <span v-if="weeklyOffDaysList.length === 0" class="text-xs text-slate-400 font-medium italic">
              No weekly off configured
            </span>
          </div>
        </div>

        <!-- Upcoming Holidays Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
          <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
            <Calendar class="w-4 h-4 text-indigo-500" />
            Upcoming Holidays
          </h3>
          <div v-if="upcomingHolidays.length === 0" class="text-center py-6 text-slate-400">
            <Calendar class="w-8 h-8 mx-auto mb-2 opacity-30" />
            <p class="text-xs font-semibold">No upcoming holidays</p>
          </div>
          <div v-else class="divide-y divide-slate-100 max-h-[350px] overflow-y-auto pr-1">
            <div v-for="holiday in upcomingHolidays" :key="holiday.id" class="flex justify-between items-center py-2.5 first:pt-0 last:pb-0">
              <div>
                <p class="font-bold text-slate-800 text-xs">{{ holiday.name }}</p>
                <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">
                  {{ getHolidayTypeLabel(holiday.type) }}
                </span>
              </div>
              <div class="text-right">
                <p class="text-xs font-bold text-slate-700">
                  {{ formatDate(holiday.display_date || holiday.date) }}
                </p>
                <span v-if="holiday.is_recurring" class="inline-block text-[9px] text-indigo-600 bg-indigo-50 border border-indigo-100 px-1.5 py-0.5 rounded-full font-black uppercase tracking-wider">
                  Annual
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Apply Leave Modal -->
    <div v-if="showApplyModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg animate-in fade-in zoom-in-95 duration-200">
        <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center">
          <h3 class="font-bold text-gray-900 flex items-center gap-2">
            <Plane class="w-5 h-5 text-indigo-600" />
            Apply for Leave
          </h3>
          <button @click="showApplyModal = false" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-1.5 transition-colors">
            <X class="w-4 h-4" />
          </button>
        </div>

        <div class="p-6 space-y-4">
          <!-- Leave Type -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Leave Type *</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                v-for="type in leaveTypes" :key="type.value"
                @click="form.type = type.value"
                :class="['px-3 py-2 rounded-xl border text-xs font-bold text-left transition-all flex items-center gap-2',
                  form.type === type.value ? type.color + ' ring-2 ring-indigo-400 ring-offset-1' : 'border-slate-200 text-slate-600 hover:bg-slate-50']"
              >
                <component :is="type.icon" class="w-3.5 h-3.5" />
                {{ type.label }}
              </button>
            </div>
          </div>

          <!-- Date Range -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1.5">Start Date *</label>
              <input v-model="form.start_date" type="date" :min="todayStr" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300" />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1.5">End Date *</label>
              <input v-model="form.end_date" type="date" :min="form.start_date || todayStr" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300" />
            </div>
          </div>

          <!-- Days preview -->
          <div v-if="estimatedDays > 0" class="flex items-center gap-2 text-sm text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-xl px-4 py-2.5 font-semibold">
            <CalendarDays class="w-4 h-4" />
            {{ estimatedDays }} working day{{ estimatedDays > 1 ? 's' : '' }} selected
          </div>

          <!-- Reason -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Reason <span class="text-slate-400 font-normal">(optional)</span></label>
            <textarea v-model="form.reason" rows="3" placeholder="Briefly describe the reason for leave..." class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none"></textarea>
          </div>

          <!-- Error -->
          <div v-if="applyError" class="flex items-center gap-2 text-sm text-rose-600 bg-rose-50 border border-rose-200 rounded-xl px-4 py-3">
            <AlertCircle class="w-4 h-4 shrink-0" />
            {{ applyError }}
          </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-3">
          <button @click="showApplyModal = false" class="px-4 py-2 border border-slate-200 rounded-xl text-slate-700 hover:bg-slate-50 font-semibold text-sm transition-colors">
            Cancel
          </button>
          <button
            @click="submitLeave"
            :disabled="applying"
            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-sm transition-all flex items-center gap-2 disabled:opacity-60"
          >
            <span v-if="applying" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            {{ applying ? 'Submitting...' : 'Submit Request' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
