<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useAdminStore } from '../../stores/admin';
import { useRosterStore } from '../../stores/roster';
import { 
  Calendar, ChevronLeft, ChevronRight, Clock, Plus, Trash2, 
  Save, RotateCcw, Copy, Sparkles, Check, AlertCircle, ChevronDown, Zap
} from 'lucide-vue-next';

const adminStore = useAdminStore();
const rosterStore = useRosterStore();

const activeTab = ref('scheduler'); // scheduler | shifts

// Roster state
const gridData = ref({}); // key: 'employeeId_YYYY-MM-DD' => { id, shift_id, status, isDirty }
const initialGridDataJson = ref(''); // For tracking dirty state
const selectedWeekStart = ref(null); // Date object (Monday)
const employees = computed(() => adminStore.employees);
const shifts = computed(() => rosterStore.shifts);
const rosterLoading = ref(false);
const saveLoading = ref(false);
const feedbackMessage = ref(null);
const feedbackType = ref('success'); // success | error

// Active cell dropdown state
const activeDropdownCell = ref(null); // 'employeeId_date'
const activeQuickFillEmployeeId = ref(null);

// Shift creation/editing state
const showShiftModal = ref(false);
const isEditingShift = ref(false);
const editingShiftId = ref(null);
const shiftForm = ref({
  name: '',
  start_time: '09:00',
  end_time: '17:00',
  color_code: '#3b82f6'
});

const colorPresets = [
  '#3b82f6', // Blue
  '#10b981', // Emerald
  '#f97316', // Orange
  '#8b5cf6', // Purple
  '#f43f5e', // Rose
  '#6366f1', // Indigo
  '#f59e0b', // Amber
  '#14b8a6'  // Teal
];

// Helper to format date
const formatDate = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

// Generate list of 7 days for the selected week
const weekDays = computed(() => {
  if (!selectedWeekStart.value) return [];
  const days = [];
  for (let i = 0; i < 7; i++) {
    const d = new Date(selectedWeekStart.value);
    d.setDate(selectedWeekStart.value.getDate() + i);
    days.push({
      dateStr: formatDate(d),
      dayName: d.toLocaleDateString('en-US', { weekday: 'short' }),
      dayNum: d.getDate(),
      monthLabel: d.toLocaleDateString('en-US', { month: 'short' }),
      isToday: formatDate(d) === formatDate(new Date()),
      fullDate: d
    });
  }
  return days;
});

// Week label for display
const weekLabel = computed(() => {
  if (weekDays.value.length === 0) return '';
  const first = weekDays.value[0];
  const last = weekDays.value[6];
  return `${first.monthLabel} ${first.dayNum} - ${last.monthLabel} ${last.dayNum}, ${first.fullDate.getFullYear()}`;
});

// Unsaved changes count
const unsavedChangesCount = computed(() => {
  return Object.values(gridData.value).filter(cell => cell.isDirty).length;
});

// Initialize week to current week (Monday)
const initCurrentWeek = () => {
  const today = new Date();
  const day = today.getDay();
  // Adjust so Monday is first day
  const diff = today.getDate() - day + (day === 0 ? -6 : 1);
  const monday = new Date(today.setDate(diff));
  monday.setHours(0, 0, 0, 0);
  selectedWeekStart.value = monday;
};

// Fetch data
const fetchData = async () => {
  rosterLoading.value = true;
  try {
    // Make sure we have employees and shifts
    if (adminStore.employees.length === 0) {
      await adminStore.fetchEmployees();
    }
    await rosterStore.fetchShifts();
    
    // Fetch rosters for this week
    if (selectedWeekStart.value) {
      const start = formatDate(selectedWeekStart.value);
      const end = new Date(selectedWeekStart.value);
      end.setDate(selectedWeekStart.value.getDate() + 6);
      const endStr = formatDate(end);
      
      const rosters = await rosterStore.fetchRosters(start, endStr);
      
      // Rebuild gridData
      const newGrid = {};
      
      // Pre-fill all employee days with default 'off' to make editing simple
      employees.value.forEach(emp => {
        weekDays.value.forEach(day => {
          const key = `${emp.id}_${day.dateStr}`;
          newGrid[key] = {
            id: null,
            shift_id: null,
            status: 'off',
            isDirty: false
          };
        });
      });
      
      // Override with backend records
      rosters.forEach(r => {
        const key = `${r.employee_id}_${r.date}`;
        newGrid[key] = {
          id: r.id,
          shift_id: r.shift_id,
          status: r.status,
          isDirty: false
        };
      });
      
      gridData.value = newGrid;
      initialGridDataJson.value = JSON.stringify(newGrid);
    }
  } catch (err) {
    showFeedback('Failed to load roster data', 'error');
  } finally {
    rosterLoading.value = false;
  }
};

// Navigation
const prevWeek = () => {
  const newDate = new Date(selectedWeekStart.value);
  newDate.setDate(selectedWeekStart.value.getDate() - 7);
  selectedWeekStart.value = newDate;
};

const nextWeek = () => {
  const newDate = new Date(selectedWeekStart.value);
  newDate.setDate(selectedWeekStart.value.getDate() + 7);
  selectedWeekStart.value = newDate;
};

const currentWeek = () => {
  initCurrentWeek();
};

// Watch selectedWeekStart to reload
watch(selectedWeekStart, () => {
  fetchData();
});

// Grid assignment helper
const assignCell = (employeeId, dateStr, shiftId, status) => {
  const key = `${employeeId}_${dateStr}`;
  const existing = gridData.value[key] || { id: null, shift_id: null, status: 'off' };
  
  // Parse initial grid configuration to see if we reverted to original
  const initialData = JSON.parse(initialGridDataJson.value);
  const initialCell = initialData[key] || { shift_id: null, status: 'off' };
  
  const isDirty = initialCell.shift_id !== shiftId || initialCell.status !== status;
  
  gridData.value[key] = {
    ...existing,
    shift_id: shiftId,
    status: status,
    isDirty: isDirty
  };
  
  activeDropdownCell.value = null; // Close menu
};

// Quick assignment bulk actions
const fillEmployeeWeek = (employeeId, shiftId, status) => {
  weekDays.value.forEach(day => {
    assignCell(employeeId, day.dateStr, shiftId, status);
  });
};

const fillDayForAll = (dateStr, shiftId, status) => {
  employees.value.forEach(emp => {
    assignCell(emp.id, dateStr, shiftId, status);
  });
};

// Copy previous week's roster
const copyPreviousWeek = async () => {
  if (!selectedWeekStart.value) return;
  rosterLoading.value = true;
  try {
    const prevStart = new Date(selectedWeekStart.value);
    prevStart.setDate(selectedWeekStart.value.getDate() - 7);
    const prevStartStr = formatDate(prevStart);
    const prevEnd = new Date(prevStart);
    prevEnd.setDate(prevStart.getDate() + 6);
    const prevEndStr = formatDate(prevEnd);
    
    // Fetch previous week's roster
    const prevRosters = await rosterStore.fetchRosters(prevStartStr, prevEndStr);
    
    if (prevRosters.length === 0) {
      showFeedback('No roster records found for the previous week to copy', 'error');
      rosterLoading.value = false;
      return;
    }
    
    // Apply previous week assignments shifted by 7 days to the current view
    let matchCount = 0;
    prevRosters.forEach(r => {
      // Find matching weekday offset
      const rDate = new Date(r.date);
      const dayOffset = (rDate.getDay() + 6) % 7; // Monday = 0, Sunday = 6
      
      const targetDay = weekDays.value[dayOffset];
      if (targetDay) {
        assignCell(r.employee_id, targetDay.dateStr, r.shift_id, r.status);
        matchCount++;
      }
    });
    
    showFeedback(`Successfully copied ${matchCount} shift assignments from previous week. Don't forget to click Save!`, 'success');
  } catch (err) {
    showFeedback('Failed to copy previous week roster', 'error');
  } finally {
    rosterLoading.value = false;
  }
};

// Save assignments
const saveRoster = async () => {
  const assignments = Object.keys(gridData.value)
    .filter(key => gridData.value[key].isDirty)
    .map(key => {
      const [employee_id, date] = key.split('_');
      const cell = gridData.value[key];
      return {
        employee_id: parseInt(employee_id),
        date: date,
        shift_id: cell.status === 'scheduled' ? cell.shift_id : null,
        status: cell.status
      };
    });
    
  if (assignments.length === 0) {
    showFeedback('No unsaved changes to commit', 'success');
    return;
  }
  
  saveLoading.value = true;
  try {
    await rosterStore.assignRosters(assignments);
    showFeedback('Weekly roster saved successfully!', 'success');
    await fetchData(); // Refresh with synced DB state
  } catch (err) {
    showFeedback(err.response?.data?.message || 'Failed to save roster', 'error');
  } finally {
    saveLoading.value = false;
  }
};

// Reset local changes
const resetRoster = () => {
  if (confirm('Are you sure you want to discard all unsaved changes for this week?')) {
    fetchData();
  }
};

// Open shift modal
const openShiftCreate = () => {
  isEditingShift.value = false;
  editingShiftId.value = null;
  shiftForm.value = {
    name: '',
    start_time: '09:00',
    end_time: '17:00',
    color_code: '#3b82f6'
  };
  showShiftModal.value = true;
};

const openShiftEdit = (shift) => {
  isEditingShift.value = true;
  editingShiftId.value = shift.id;
  // Format times from HH:MM:SS to HH:MM if needed
  const start = shift.start_time.slice(0, 5);
  const end = shift.end_time.slice(0, 5);
  shiftForm.value = {
    name: shift.name,
    start_time: start,
    end_time: end,
    color_code: shift.color_code
  };
  showShiftModal.value = true;
};

// Save Shift
const handleSaveShift = async () => {
  try {
    if (isEditingShift.value) {
      await rosterStore.updateShift(editingShiftId.value, shiftForm.value);
      showFeedback('Shift updated successfully!', 'success');
    } else {
      await rosterStore.createShift(shiftForm.value);
      showFeedback('New shift created successfully!', 'success');
    }
    showShiftModal.value = false;
  } catch (err) {
    showFeedback(err.response?.data?.message || 'Failed to save shift', 'error');
  }
};

// Delete Shift
const handleDeleteShift = async (id) => {
  if (confirm('Are you sure you want to delete this shift? Note: It will be unassigned from any roster on this shift.')) {
    try {
      await rosterStore.deleteShift(id);
      showFeedback('Shift deleted successfully', 'success');
      fetchData(); // Reload to refresh grid in case any cell was on this shift
    } catch (err) {
      showFeedback(err.response?.data?.message || 'Failed to delete shift', 'error');
    }
  }
};

// Toast notification trigger
const showFeedback = (msg, type = 'success') => {
  feedbackMessage.value = msg;
  feedbackType.value = type;
  setTimeout(() => {
    feedbackMessage.value = null;
  }, 4000);
};

// Dropdown handler
const toggleDropdown = (cellKey) => {
  if (activeDropdownCell.value === cellKey) {
    activeDropdownCell.value = null;
  } else {
    activeDropdownCell.value = cellKey;
    activeQuickFillEmployeeId.value = null; // Close quick fill
  }
};

const toggleQuickFillDropdown = (employeeId) => {
  if (activeQuickFillEmployeeId.value === employeeId) {
    activeQuickFillEmployeeId.value = null;
  } else {
    activeQuickFillEmployeeId.value = employeeId;
    activeDropdownCell.value = null; // Close cell dropdown
  }
};

const handleGlobalClick = () => {
  activeDropdownCell.value = null;
  activeQuickFillEmployeeId.value = null;
};

onMounted(() => {
  initCurrentWeek();
  fetchData();
  window.addEventListener('click', handleGlobalClick);
});

onUnmounted(() => {
  window.removeEventListener('click', handleGlobalClick);
});
</script>

<template>
  <div class="max-w-7xl mx-auto space-y-8 relative">
    <!-- Toast Feedback Notification -->
    <transition name="fade">
      <div 
        v-if="feedbackMessage" 
        :class="[
          'fixed bottom-6 right-6 z-50 flex items-center gap-3 px-6 py-4 rounded-xl shadow-xl transition-all duration-300 transform translate-y-0',
          feedbackType === 'success' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'
        ]"
      >
        <Check v-if="feedbackType === 'success'" class="w-5 h-5 shrink-0" />
        <AlertCircle v-else class="w-5 h-5 shrink-0" />
        <span class="font-medium text-sm">{{ feedbackMessage }}</span>
      </div>
    </transition>

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
      <div>
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
          <Clock class="w-8 h-8 text-indigo-600" />
          Duty Roster Management
        </h1>
        <p class="text-gray-500 mt-1.5 text-sm sm:text-base">Schedule working hours, organize shifts, and track employee availability.</p>
      </div>

      <!-- Tab Buttons -->
      <div class="flex bg-gray-100 p-1 rounded-xl self-start md:self-center border border-gray-200">
        <button 
          @click="activeTab = 'scheduler'" 
          :class="[
            'px-5 py-2 rounded-lg text-sm font-semibold transition-all duration-200',
            activeTab === 'scheduler' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          Weekly Roster
        </button>
        <button 
          @click="activeTab = 'shifts'" 
          :class="[
            'px-5 py-2 rounded-lg text-sm font-semibold transition-all duration-200',
            activeTab === 'shifts' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          Shift Configuration
        </button>
      </div>
    </div>

    <!-- Active Tab: WEEKLY ROSTER SCHEDULER -->
    <div v-if="activeTab === 'scheduler'" class="space-y-6">
      
      <!-- Action & Filter Bar -->
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-white p-4 rounded-2xl border border-gray-200 shadow-sm">
        
        <!-- Date Selector Navigation -->
        <div class="flex items-center gap-3">
          <button 
            @click="prevWeek" 
            class="p-2 hover:bg-gray-100 active:bg-gray-200 rounded-xl border border-gray-200 transition-colors"
            title="Previous Week"
          >
            <ChevronLeft class="w-5 h-5 text-gray-600" />
          </button>
          
          <button 
            @click="currentWeek" 
            class="px-4 py-2 hover:bg-gray-100 active:bg-gray-200 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 transition-colors"
          >
            This Week
          </button>

          <button 
            @click="nextWeek" 
            class="p-2 hover:bg-gray-100 active:bg-gray-200 rounded-xl border border-gray-200 transition-colors"
            title="Next Week"
          >
            <ChevronRight class="w-5 h-5 text-gray-600" />
          </button>
          
          <div class="h-6 w-px bg-gray-200 mx-1"></div>
          
          <span class="text-sm sm:text-base font-bold text-gray-900 flex items-center gap-2">
            <Calendar class="w-4 h-4 text-indigo-500" />
            {{ weekLabel }}
          </span>
        </div>

        <!-- Quick & Unsaved Actions -->
        <div class="flex flex-wrap items-center gap-3">
          <!-- Copy Prev Week Button -->
          <button 
            @click="copyPreviousWeek" 
            class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 hover:bg-gray-100 active:bg-gray-200 text-xs sm:text-sm font-semibold transition-all"
            title="Copy all shift assignments from the preceding week"
          >
            <Copy class="w-4 h-4 text-gray-500" />
            Copy Prev Week
          </button>

          <!-- Save/Discard changes banner if dirty -->
          <div v-if="unsavedChangesCount > 0" class="flex items-center gap-3 pl-2">
            <span class="text-xs text-amber-600 font-semibold flex items-center gap-1.5 animate-pulse">
              <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
              {{ unsavedChangesCount }} unsaved cell(s)
            </span>

            <button 
              @click="resetRoster" 
              class="flex items-center gap-1.5 px-3 py-2 text-rose-600 hover:bg-rose-50 rounded-xl text-xs font-semibold transition-all border border-transparent hover:border-rose-100"
            >
              <RotateCcw class="w-3.5 h-3.5" />
              Discard
            </button>

            <button 
              @click="saveRoster" 
              :disabled="saveLoading"
              class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 disabled:bg-indigo-300 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-md shadow-indigo-100 hover:shadow-lg transition-all"
            >
              <Save v-if="!saveLoading" class="w-4 h-4" />
              <div v-else class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
              Save Roster
            </button>
          </div>
        </div>

      </div>

      <!-- Weekly Grid Scheduling Card -->
      <div class="bg-white border border-gray-200 rounded-3xl shadow-sm overflow-hidden relative">
        <!-- Roster Loading Overlay -->
        <div v-if="rosterLoading" class="absolute inset-0 bg-white/70 backdrop-blur-[2px] z-20 flex items-center justify-center">
          <div class="flex flex-col items-center">
            <div class="animate-spin rounded-full h-10 w-10 border-4 border-indigo-600 border-t-transparent mb-3"></div>
            <p class="text-sm font-semibold text-gray-600">Retrieving duty roster...</p>
          </div>
        </div>

        <div v-if="employees.length === 0 && !rosterLoading" class="p-16 text-center text-gray-500">
          <Sparkles class="w-12 h-12 text-indigo-200 mx-auto mb-4" />
          <h3 class="font-bold text-gray-800 text-lg">No active employees found</h3>
          <p class="text-sm text-gray-400 mt-1">Please add employee accounts in the Employee directory before scheduling shifts.</p>
        </div>

        <!-- Roster Grid Table -->
        <div v-else class="overflow-x-auto min-h-[420px] pb-36">
          <table class="w-full text-left border-collapse table-fixed min-w-[1050px]">
            <!-- Table Columns width definitions -->
            <colgroup>
              <col class="w-[280px]" /> <!-- Employee details -->
              <col class="w-[110px]" v-for="i in 7" :key="i" />
            </colgroup>

            <thead class="bg-gray-50 text-gray-700 border-b border-gray-200">
              <tr>
                <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider text-gray-500">Employee Details</th>
                <th 
                  v-for="day in weekDays" 
                  :key="day.dateStr"
                  :class="[
                    'px-3 py-3 text-center border-l border-gray-100 font-semibold',
                    day.isToday ? 'bg-indigo-50/50' : ''
                  ]"
                >
                  <div class="flex flex-col items-center justify-center">
                    <span :class="['text-xs uppercase tracking-wider', day.isToday ? 'text-indigo-600 font-bold' : 'text-gray-400']">
                      {{ day.dayName }}
                    </span>
                    <span :class="[
                      'text-lg font-extrabold mt-0.5 w-8 h-8 flex items-center justify-center rounded-full transition-all',
                      day.isToday ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100' : 'text-gray-900'
                    ]">
                      {{ day.dayNum }}
                    </span>
                  </div>
                </th>
              </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
              <tr 
                v-for="(emp, empIndex) in employees" 
                :key="emp.id" 
                class="hover:bg-gray-50/50 transition-colors"
              >
                <!-- Column 1: Employee Header Card -->
                <td class="px-6 py-4 flex items-center gap-3">
                  <img 
                    v-if="emp.documents?.profile_photo" 
                    :src="`http://localhost:8000/storage/${emp.documents.profile_photo}`" 
                    class="w-10 h-10 rounded-full object-cover border border-gray-200 shadow-sm shrink-0" 
                  />
                  <div 
                    v-else 
                    class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-100 to-blue-50 text-indigo-700 border border-indigo-200 flex items-center justify-center font-bold shadow-sm shrink-0"
                  >
                    {{ emp.user?.name?.charAt(0) }}
                  </div>
                  
                  <div class="flex-1 min-w-0 text-left">
                    <div class="font-bold text-gray-900 text-sm truncate" :title="emp.user?.name">
                      {{ emp.user?.name }}
                    </div>
                    <div class="text-xs text-gray-400 truncate mt-0.5">
                      {{ emp.designation?.name || 'No Role Assigned' }}
                    </div>

                    <!-- Bulk Row actions (Dropdown quick fill) -->
                    <div class="relative mt-2">
                      <button 
                        @click.stop="toggleQuickFillDropdown(emp.id)"
                        class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-slate-50 hover:bg-slate-100 text-slate-600 hover:text-slate-800 text-[10px] font-black rounded-lg border border-slate-200/60 shadow-sm transition-all uppercase tracking-wider cursor-pointer"
                      >
                        <Zap class="w-3 h-3 text-amber-500" />
                        Quick Fill
                        <ChevronDown class="w-2.5 h-2.5 transition-transform" :class="activeQuickFillEmployeeId === emp.id ? 'rotate-180' : ''" />
                      </button>

                      <!-- Dropdown menu -->
                      <div 
                        v-if="activeQuickFillEmployeeId === emp.id" 
                        class="absolute left-0 mt-1.5 w-48 bg-white border border-slate-200/80 rounded-xl shadow-xl z-30 p-1 text-left"
                        @click.stop
                      >
                        <div class="px-2.5 py-1 text-[9px] uppercase font-extrabold tracking-widest text-slate-400 border-b border-slate-100 mb-1">
                          Select Shift to Fill Week
                        </div>
                        <button 
                          @click="() => { fillEmployeeWeek(emp.id, null, 'off'); activeQuickFillEmployeeId = null; }"
                          class="w-full text-left px-2.5 py-1.5 hover:bg-slate-50 rounded-lg text-xs font-semibold text-slate-700 flex items-center gap-2 cursor-pointer transition-colors"
                        >
                          <span class="w-2.5 h-2.5 rounded-full bg-slate-200 border border-slate-300/40"></span>
                          All Off (Day Off)
                        </button>
                        <button 
                          v-for="sh in shifts" 
                          :key="sh.id"
                          @click="() => { fillEmployeeWeek(emp.id, sh.id, 'scheduled'); activeQuickFillEmployeeId = null; }"
                          class="w-full text-left px-2.5 py-1.5 hover:bg-slate-50 rounded-lg text-xs font-semibold text-slate-700 flex items-center gap-2 cursor-pointer transition-colors"
                        >
                          <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: sh.color_code }"></span>
                          Fill: {{ sh.name }}
                        </button>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Columns 2-8: Days of the week roster cells -->
                <td 
                  v-for="day in weekDays" 
                  :key="day.dateStr"
                  :class="[
                    'p-2 text-center border-l border-slate-100 relative group/cell transition-colors align-middle',
                    day.isToday ? 'bg-indigo-50/20' : '',
                    activeDropdownCell === `${emp.id}_${day.dateStr}` ? 'z-50' : 'z-10'
                  ]"
                >
                  <!-- Active Cell View State -->
                  <div 
                    @click.stop="toggleDropdown(`${emp.id}_${day.dateStr}`)"
                    class="mx-auto w-full min-h-[66px] rounded-2xl flex flex-col items-center justify-center p-2 cursor-pointer transition-all duration-200 relative select-none border border-transparent"
                    :class="[
                      gridData[`${emp.id}_${day.dateStr}`]?.isDirty ? 'border-dashed border-amber-300 bg-amber-50/10' : '',
                      gridData[`${emp.id}_${day.dateStr}`]?.status === 'off' ? 'hover:bg-slate-50' : 'hover:scale-[1.02] shadow-sm hover:shadow-md'
                    ]"
                  >
                    <!-- Day Off badge -->
                    <template v-if="gridData[`${emp.id}_${day.dateStr}`]?.status === 'off'">
                      <span class="px-2 py-0.5 rounded-lg bg-slate-100/60 border border-slate-200/30 text-[10px] font-black text-slate-400 uppercase tracking-wider">Off</span>
                      <span class="text-[9px] font-bold text-slate-300 mt-1 font-mono">--:--</span>
                    </template>

                    <!-- Absent badge -->
                    <template v-else-if="gridData[`${emp.id}_${day.dateStr}`]?.status === 'absent'">
                      <span :class="[
                        'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-rose-50 text-rose-500 border border-rose-200/50'
                      ]">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                        Absent
                      </span>
                    </template>

                    <!-- Scheduled Shift Card -->
                    <template v-else-if="gridData[`${emp.id}_${day.dateStr}`]?.status === 'scheduled'">
                      <!-- We need to resolve shift object from shift_id -->
                      <template v-if="shifts.find(s => s.id === gridData[`${emp.id}_${day.dateStr}`]?.shift_id)">
                        <div 
                          class="w-full text-center py-1 rounded-lg text-white text-[10px] font-extrabold uppercase tracking-wide truncate px-1.5 shadow-sm transition-all"
                          :style="{ backgroundColor: shifts.find(s => s.id === gridData[`${emp.id}_${day.dateStr}`]?.shift_id).color_code }"
                        >
                          {{ shifts.find(s => s.id === gridData[`${emp.id}_${day.dateStr}`]?.shift_id).name }}
                        </div>
                        <div class="text-[9px] font-extrabold text-slate-500 mt-1 font-mono tracking-tight leading-none">
                          {{ shifts.find(s => s.id === gridData[`${emp.id}_${day.dateStr}`]?.shift_id).start_time.slice(0, 5) }} -
                          {{ shifts.find(s => s.id === gridData[`${emp.id}_${day.dateStr}`]?.shift_id).end_time.slice(0, 5) }}
                        </div>
                      </template>
                      <template v-else>
                        <span class="text-[10px] font-black text-slate-400 bg-slate-100 px-2 py-0.5 rounded uppercase tracking-wider">Unknown</span>
                      </template>
                    </template>

                    <!-- Modified Marker Indicator -->
                    <span 
                      v-if="gridData[`${emp.id}_${day.dateStr}`]?.isDirty"
                      class="absolute top-1 right-1 w-2.5 h-2.5 rounded-full bg-amber-500 border-2 border-white shadow animate-bounce"
                      title="Unsaved changes in this slot"
                    ></span>

                    <ChevronDown class="w-3.5 h-3.5 text-slate-300 opacity-0 group-hover/cell:opacity-100 transition-opacity absolute bottom-1 right-1" />
                  </div>

                  <!-- Dropdown Quick Shift Assign Menu -->
                  <div 
                    v-if="activeDropdownCell === `${emp.id}_${day.dateStr}`"
                    class="absolute left-1/2 -translate-x-1/2 w-48 bg-white border border-gray-200 rounded-2xl shadow-xl z-50 p-2 text-left"
                    :class="[
                      empIndex >= employees.length / 2 && employees.length > 1 ? 'bottom-full mb-2' : 'top-full mt-1'
                    ]"
                  >
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-2 py-1 mb-1">
                      Set Schedule
                    </div>

                    <!-- Predefined shifts lists -->
                    <button 
                      v-for="shift in shifts" 
                      :key="shift.id"
                      @click="assignCell(emp.id, day.dateStr, shift.id, 'scheduled')"
                      class="w-full flex items-center gap-2 px-2.5 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 rounded-lg transition-all"
                    >
                      <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: shift.color_code }"></span>
                      <div class="flex-1 truncate">
                        {{ shift.name }}
                        <div class="text-[9px] font-medium text-gray-400 font-mono mt-0.5">
                          {{ shift.start_time.slice(0,5) }} - {{ shift.end_time.slice(0,5) }}
                        </div>
                      </div>
                      <Check 
                        v-if="gridData[`${emp.id}_${day.dateStr}`]?.shift_id === shift.id && gridData[`${emp.id}_${day.dateStr}`]?.status === 'scheduled'"
                        class="w-3.5 h-3.5 text-indigo-600 shrink-0"
                      />
                    </button>

                    <div class="h-px bg-gray-100 my-1.5"></div>

                    <!-- Day Off Option -->
                    <button 
                      @click="assignCell(emp.id, day.dateStr, null, 'off')"
                      class="w-full flex items-center gap-2 px-2.5 py-2 text-xs font-bold text-gray-600 hover:bg-gray-50 rounded-lg transition-all"
                    >
                      <span class="w-3 h-3 rounded-full shrink-0 bg-gray-300"></span>
                      <span class="flex-1">Day Off</span>
                      <Check 
                        v-if="gridData[`${emp.id}_${day.dateStr}`]?.status === 'off'"
                        class="w-3.5 h-3.5 text-gray-600 shrink-0"
                      />
                    </button>

                    <!-- Absent Option -->
                    <button 
                      @click="assignCell(emp.id, day.dateStr, null, 'absent')"
                      class="w-full flex items-center gap-2 px-2.5 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-lg transition-all"
                    >
                      <span class="w-3 h-3 rounded-full shrink-0 bg-rose-500"></span>
                      <span class="flex-1">Absent</span>
                      <Check 
                        v-if="gridData[`${emp.id}_${day.dateStr}`]?.status === 'absent'"
                        class="w-3.5 h-3.5 text-rose-600 shrink-0"
                      />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Active Tab: SHIFT CONFIGURATOR -->
    <div v-else-if="activeTab === 'shifts'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      
      <!-- Create & Preset Shift Info -->
      <div class="lg:col-span-1 space-y-6">
        <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm space-y-6">
          <div>
            <h2 class="text-xl font-bold text-gray-900">Define Working Shifts</h2>
            <p class="text-sm text-gray-400 mt-1">Configure company-wide shift codes, times, and colored calendar tags.</p>
          </div>

          <button 
            @click="openShiftCreate" 
            class="w-full flex items-center justify-center gap-2 py-3 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-sm font-semibold transition-all shadow-md shadow-indigo-100 hover:shadow-lg"
          >
            <Plus class="w-4 h-4" />
            Add Custom Shift
          </button>

          <div class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100">
            <h4 class="text-xs font-bold text-indigo-800 uppercase tracking-wider">Default Shift Seeders</h4>
            <p class="text-xs text-indigo-600/90 mt-1 leading-relaxed">
              Upon visiting this tab for the first time, default company shifts (Morning, Evening, and Night) are auto-populated for easy configuration.
            </p>
          </div>
        </div>
      </div>

      <!-- Shifts List Card -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="p-6 border-b border-gray-100">
            <h3 class="font-bold text-gray-900 text-lg">Active Shifts</h3>
          </div>

          <div v-if="shifts.length === 0" class="p-16 text-center text-gray-400">
            <Clock class="w-12 h-12 text-gray-200 mx-auto mb-3 animate-pulse" />
            <span class="text-sm font-medium">No shifts defined yet.</span>
          </div>

          <div v-else class="divide-y divide-gray-100">
            <div 
              v-for="shift in shifts" 
              :key="shift.id"
              class="p-6 flex items-center justify-between hover:bg-gray-50/40 transition-colors"
            >
              <div class="flex items-center gap-4">
                <span class="w-4 h-4 rounded-full" :style="{ backgroundColor: shift.color_code }"></span>
                <div>
                  <h4 class="font-extrabold text-gray-900 text-sm sm:text-base">{{ shift.name }}</h4>
                  <div class="flex items-center gap-2 text-gray-400 text-xs mt-1 font-semibold">
                    <Clock class="w-3.5 h-3.5 text-gray-300" />
                    <span class="font-mono text-gray-700 bg-gray-100 px-1.5 py-0.5 rounded">{{ shift.start_time.slice(0, 5) }}</span>
                    <span>to</span>
                    <span class="font-mono text-gray-700 bg-gray-100 px-1.5 py-0.5 rounded">{{ shift.end_time.slice(0, 5) }}</span>
                  </div>
                </div>
              </div>

              <!-- Action buttons -->
              <div class="flex items-center gap-2">
                <button 
                  @click="openShiftEdit(shift)"
                  class="px-3 py-1.5 hover:bg-indigo-50 text-indigo-600 rounded-lg text-xs font-semibold transition-colors"
                >
                  Edit
                </button>
                <button 
                  @click="handleDeleteShift(shift.id)"
                  class="p-1.5 hover:bg-rose-50 text-rose-500 rounded-lg transition-colors"
                  title="Remove this shift"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- CREATE/EDIT SHIFT DIALOG MODAL -->
    <div 
      v-if="showShiftModal" 
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
    >
      <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden transform transition-all">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
          <h3 class="text-lg font-bold text-gray-900">
            {{ isEditingShift ? 'Modify Shift Details' : 'Create Custom Shift' }}
          </h3>
          <button 
            @click="showShiftModal = false"
            class="text-gray-400 hover:text-gray-900 hover:bg-gray-100 p-1.5 rounded-full transition-all"
          >
            <Plus class="w-5 h-5 rotate-45" />
          </button>
        </div>

        <form @submit.prevent="handleSaveShift" class="p-6 space-y-5">
          <!-- Shift Name -->
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Shift Label/Name</label>
            <input 
              v-model="shiftForm.name"
              type="text" 
              required
              placeholder="e.g. Morning Shift, Day Work"
              class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
            />
          </div>

          <!-- Times (Start / End) -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Start Time</label>
              <input 
                v-model="shiftForm.start_time"
                type="time" 
                required
                class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm font-bold focus:outline-none focus:border-indigo-500 transition-all font-mono"
              />
            </div>
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">End Time</label>
              <input 
                v-model="shiftForm.end_time"
                type="time" 
                required
                class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm font-bold focus:outline-none focus:border-indigo-500 transition-all font-mono"
              />
            </div>
          </div>

          <!-- Color presets picker -->
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Calendar Badge Color</label>
            <div class="flex flex-wrap gap-2.5">
              <button 
                v-for="color in colorPresets" 
                :key="color"
                type="button"
                @click="shiftForm.color_code = color"
                class="w-8 h-8 rounded-full border border-black/5 hover:scale-105 active:scale-95 transition-all flex items-center justify-center text-white"
                :style="{ backgroundColor: color }"
              >
                <Check v-if="shiftForm.color_code === color" class="w-4 h-4 shadow-sm" />
              </button>
            </div>
            
            <!-- Custom HTML Color input -->
            <div class="mt-4 flex items-center gap-3">
              <span class="text-xs font-semibold text-gray-400">Custom Color:</span>
              <input 
                v-model="shiftForm.color_code"
                type="color"
                class="w-8 h-8 rounded-xl border border-gray-200 p-0.5 cursor-pointer shrink-0"
              />
              <input 
                v-model="shiftForm.color_code"
                type="text"
                class="w-24 px-2 py-1 text-xs border border-gray-200 rounded-lg font-mono uppercase focus:outline-none text-gray-600"
              />
            </div>
          </div>

          <!-- Buttons -->
          <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
            <button 
              type="button"
              @click="showShiftModal = false"
              class="px-4 py-2.5 hover:bg-gray-100 text-gray-700 rounded-xl text-sm font-semibold transition-colors"
            >
              Cancel
            </button>
            <button 
              type="submit"
              class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold transition-all shadow-md shadow-indigo-100"
            >
              {{ isEditingShift ? 'Apply Changes' : 'Create Shift' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s, transform 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(12px);
}
</style>
