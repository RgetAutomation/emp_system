<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRosterStore } from '../../stores/roster';
import { 
  Calendar, ChevronLeft, ChevronRight, Clock, Coffee, AlertCircle, Award, CalendarDays
} from 'lucide-vue-next';

const rosterStore = useRosterStore();

// Calendar configuration
const currentMonthDate = ref(new Date());
const rosterLoading = ref(false);
const error = ref(null);
const rosterMap = ref({}); // dateStr 'YYYY-MM-DD' => roster record

// Helper to format date
const formatDate = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

// Computed property for calendar days
const calendarCells = computed(() => {
  const year = currentMonthDate.value.getFullYear();
  const month = currentMonthDate.value.getMonth();

  // First day of current month
  const firstDayOfMonth = new Date(year, month, 1);
  // Total days in current month
  const totalDays = new Date(year, month + 1, 0).getDate();
  
  // Day of the week for 1st of the month (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
  let startDayIndex = firstDayOfMonth.getDay();
  // Adjust so Monday is 0, Sunday is 6
  startDayIndex = startDayIndex === 0 ? 6 : startDayIndex - 1;

  const cells = [];

  // Previous month padding
  const prevMonthEnd = new Date(year, month, 0).getDate();
  for (let i = startDayIndex - 1; i >= 0; i--) {
    const d = new Date(year, month - 1, prevMonthEnd - i);
    cells.push({
      dateStr: formatDate(d),
      dayNum: d.getDate(),
      isCurrentMonth: false,
      dateObj: d
    });
  }

  // Current month days
  for (let i = 1; i <= totalDays; i++) {
    const d = new Date(year, month, i);
    cells.push({
      dateStr: formatDate(d),
      dayNum: i,
      isCurrentMonth: true,
      dateObj: d,
      isToday: formatDate(d) === formatDate(new Date())
    });
  }

  // Next month padding to complete the grid (usually 42 cells total)
  const remaining = 42 - cells.length;
  for (let i = 1; i <= remaining; i++) {
    const d = new Date(year, month + 1, i);
    cells.push({
      dateStr: formatDate(d),
      dayNum: i,
      isCurrentMonth: false,
      dateObj: d
    });
  }

  return cells;
});

// Month / Year Labels
const monthLabel = computed(() => {
  return currentMonthDate.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
});

// Fetch Personal Roster
const fetchRoster = async () => {
  rosterLoading.value = true;
  error.value = null;
  
  try {
    if (calendarCells.value.length === 0) return;
    
    // Fetch range covering the entire visible calendar cells
    const startStr = calendarCells.value[0].dateStr;
    const endStr = calendarCells.value[calendarCells.value.length - 1].dateStr;
    
    const records = await rosterStore.fetchEmployeeRoster(startStr, endStr);
    
    const mapping = {};
    records.forEach(r => {
      mapping[r.date] = r;
    });
    rosterMap.value = mapping;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to retrieve your work schedule.';
  } finally {
    rosterLoading.value = false;
  }
};

// Today's roster item
const todayRoster = computed(() => {
  const todayStr = formatDate(new Date());
  return rosterMap.value[todayStr] || null;
});

// Scheduled stats this month
const monthStats = computed(() => {
  let workingDaysCount = 0;
  let totalHours = 0;
  let offDaysCount = 0;

  Object.values(rosterMap.value).forEach(r => {
    // Only count records in the current month
    const rDate = new Date(r.date);
    if (rDate.getMonth() === currentMonthDate.value.getMonth() && rDate.getFullYear() === currentMonthDate.value.getFullYear()) {
      if (r.status === 'scheduled' && r.shift) {
        workingDaysCount++;
        // Calculate shift hours
        const [sh, sm] = r.shift.start_time.split(':').map(Number);
        const [eh, em] = r.shift.end_time.split(':').map(Number);
        let diff = (eh * 60 + em) - (sh * 60 + sm);
        if (diff < 0) {
          diff += 24 * 60; // Next day shift wrap-around
        }
        totalHours += diff / 60;
      } else if (r.status === 'off') {
        offDaysCount++;
      }
    }
  });

  return {
    workingDays: workingDaysCount,
    offDays: offDaysCount,
    hours: totalHours
  };
});

// Calendar Navigate
const prevMonth = () => {
  const d = new Date(currentMonthDate.value);
  d.setMonth(currentMonthDate.value.getMonth() - 1);
  currentMonthDate.value = d;
};

const nextMonth = () => {
  const d = new Date(currentMonthDate.value);
  d.setMonth(currentMonthDate.value.getMonth() + 1);
  currentMonthDate.value = d;
};

const jumpToToday = () => {
  currentMonthDate.value = new Date();
};

watch(currentMonthDate, () => {
  fetchRoster();
});

onMounted(() => {
  fetchRoster();
});
</script>

<template>
  <div class="max-w-6xl mx-auto space-y-8">
    
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
          <CalendarDays class="w-8 h-8 text-indigo-600" />
          My Work Schedule
        </h1>
        <p class="text-gray-500 mt-1.5 text-sm sm:text-base">View shift hours, calendar rosters, and off days assigned by your manager.</p>
      </div>

      <button 
        @click="jumpToToday" 
        class="px-4 py-2 hover:bg-gray-100 border border-gray-200 text-sm font-semibold rounded-xl text-gray-700 transition-all self-start sm:self-auto"
      >
        Jump to Today
      </button>
    </div>

    <!-- Error Banner -->
    <div v-if="error" class="p-4 bg-rose-50 border border-rose-100 rounded-2xl flex items-center gap-3 text-rose-700 font-medium">
      <AlertCircle class="w-5 h-5 shrink-0" />
      {{ error }}
    </div>

    <!-- Top Summary Dashboard Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      
      <!-- Card 1: Today's Shift Card (Glassmorphism highlight) -->
      <div class="bg-gradient-to-tr from-indigo-600 to-indigo-800 p-6 rounded-3xl text-white shadow-xl shadow-indigo-100 flex flex-col justify-between min-h-[160px]">
        <div>
          <div class="text-xs uppercase tracking-wider font-extrabold opacity-80 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
            Today's Schedule
          </div>
          
          <div class="mt-4">
            <template v-if="todayRoster && todayRoster.status === 'scheduled'">
              <h3 class="text-2xl font-black">{{ todayRoster.shift?.name }}</h3>
              <p class="text-sm font-mono mt-1 opacity-90 flex items-center gap-1.5 font-bold">
                <Clock class="w-4 h-4" />
                {{ todayRoster.shift?.start_time.slice(0, 5) }} - {{ todayRoster.shift?.end_time.slice(0, 5) }}
              </p>
            </template>
            <template v-else-if="todayRoster && todayRoster.status === 'off'">
              <h3 class="text-2xl font-black">Rest Day (Off Day)</h3>
              <p class="text-sm mt-1 opacity-80">Enjoy your rest day!</p>
            </template>
            <template v-else-if="todayRoster && todayRoster.status === 'absent'">
              <h3 class="text-2xl font-black text-rose-200">Marked Absent</h3>
              <p class="text-sm mt-1 opacity-85">You were marked absent today by your manager.</p>
            </template>
            <template v-else>
              <h3 class="text-2xl font-black text-white/90">No Shift Assigned</h3>
              <p class="text-sm mt-1 opacity-70">No active shift scheduled for today.</p>
            </template>
          </div>
        </div>

        <div class="text-[10px] opacity-60 font-semibold tracking-wide mt-4">
          Date: {{ new Date().toLocaleDateString('en-US', { weekday: 'short', month: 'long', day: 'numeric' }) }}
        </div>
      </div>

      <!-- Card 2: Total Scheduled Hours -->
      <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm flex items-center gap-5">
        <div class="p-4 bg-emerald-50 rounded-2xl text-emerald-600 shrink-0">
          <Clock class="w-8 h-8" />
        </div>
        <div>
          <div class="text-3xl font-black text-gray-900">{{ monthStats.hours }} Hrs</div>
          <div class="text-xs uppercase font-extrabold tracking-wider text-gray-400 mt-1">Scheduled Hours this Month</div>
          <div class="text-xs text-gray-400 mt-0.5">Based on active shifts in your calendar</div>
        </div>
      </div>

      <!-- Card 3: Rest/Working Days -->
      <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm flex items-center gap-5">
        <div class="p-4 bg-indigo-50 rounded-2xl text-indigo-600 shrink-0">
          <Award class="w-8 h-8" />
        </div>
        <div>
          <div class="text-3xl font-black text-gray-900">{{ monthStats.workingDays }} Days</div>
          <div class="text-xs uppercase font-extrabold tracking-wider text-gray-400 mt-1">Working Days this Month</div>
          <div class="text-xs text-gray-400 mt-0.5">{{ monthStats.offDays }} rest days scheduled</div>
        </div>
      </div>

    </div>

    <!-- MAIN MONTH CALENDAR INTERACTIVE VIEW -->
    <div class="bg-white border border-gray-200 rounded-3xl shadow-sm overflow-hidden relative">
      <!-- Calendar loading overlay -->
      <div v-if="rosterLoading" class="absolute inset-0 bg-white/70 backdrop-blur-[2px] z-10 flex items-center justify-center">
        <div class="flex flex-col items-center">
          <div class="animate-spin rounded-full h-10 w-10 border-4 border-indigo-600 border-t-transparent mb-3"></div>
          <p class="text-sm font-semibold text-gray-600">Retrieving calendar roster...</p>
        </div>
      </div>

      <!-- Calendar Header Action bar -->
      <div class="p-6 border-b border-gray-200 flex items-center justify-between bg-gray-50/50">
        <h3 class="font-extrabold text-gray-900 text-lg sm:text-xl flex items-center gap-2">
          <Calendar class="w-5 h-5 text-indigo-600" />
          {{ monthLabel }}
        </h3>

        <div class="flex items-center gap-2">
          <button 
            @click="prevMonth"
            class="p-2 hover:bg-gray-150 active:bg-gray-200 rounded-xl border border-gray-200 transition-colors bg-white shadow-sm"
          >
            <ChevronLeft class="w-4 h-4 text-gray-600" />
          </button>
          <button 
            @click="nextMonth"
            class="p-2 hover:bg-gray-150 active:bg-gray-200 rounded-xl border border-gray-200 transition-colors bg-white shadow-sm"
          >
            <ChevronRight class="w-4 h-4 text-gray-600" />
          </button>
        </div>
      </div>

      <!-- Calendar Grid Body -->
      <div class="p-6">
        <!-- Weekday columns headers -->
        <div class="grid grid-cols-7 gap-3 mb-3 text-center">
          <div 
            v-for="dName in ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']" 
            :key="dName"
            class="text-xs uppercase font-extrabold tracking-wider text-gray-400 py-1"
          >
            {{ dName }}
          </div>
        </div>

        <!-- Month Days Grid -->
        <div class="grid grid-cols-7 gap-3">
          <div 
            v-for="cell in calendarCells" 
            :key="cell.dateStr"
            class="min-h-[100px] border border-gray-150 rounded-2xl p-2.5 flex flex-col justify-between transition-all duration-200 relative group"
            :class="[
              cell.isCurrentMonth ? 'bg-white' : 'bg-gray-50/40 text-gray-400 opacity-60',
              cell.isToday ? 'ring-2 ring-indigo-600 ring-offset-2' : ''
            ]"
          >
            <!-- Day number & indicators -->
            <div class="flex justify-between items-start">
              <span 
                :class="[
                  'text-sm font-black w-6 h-6 flex items-center justify-center rounded-full',
                  cell.isToday ? 'bg-indigo-600 text-white shadow-md shadow-indigo-150' : 'text-gray-900'
                ]"
              >
                {{ cell.dayNum }}
              </span>
              
              <!-- Indicator today dot -->
              <span v-if="cell.isToday && !rosterMap[cell.dateStr]" class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
            </div>

            <!-- Shift scheduling inside day cells -->
            <div class="mt-2 flex-1 flex flex-col justify-end">
              <!-- Active Scheduled shift -->
              <template v-if="rosterMap[cell.dateStr]">
                
                <div v-if="rosterMap[cell.dateStr].status === 'scheduled' && rosterMap[cell.dateStr].shift" class="space-y-1">
                  <div 
                    class="text-[10px] sm:text-xs font-black text-white px-2 py-1 rounded-lg text-center truncate shadow-sm transition-all"
                    :style="{ backgroundColor: rosterMap[cell.dateStr].shift.color_code }"
                    :title="`${rosterMap[cell.dateStr].shift.name}: ${rosterMap[cell.dateStr].shift.start_time.slice(0,5)} - ${rosterMap[cell.dateStr].shift.end_time.slice(0,5)}`"
                  >
                    {{ rosterMap[cell.dateStr].shift.name }}
                  </div>
                  <div class="text-[9px] font-bold text-gray-500 font-mono text-center">
                    {{ rosterMap[cell.dateStr].shift.start_time.slice(0, 5) }} -
                    {{ rosterMap[cell.dateStr].shift.end_time.slice(0, 5) }}
                  </div>
                </div>

                <div v-else-if="rosterMap[cell.dateStr].status === 'off'" class="text-center py-1 rounded-lg bg-gray-100 border border-gray-150">
                  <span class="text-[10px] sm:text-xs font-extrabold text-gray-400">Day Off</span>
                </div>

                <div v-else-if="rosterMap[cell.dateStr].status === 'absent'" class="text-center py-1 rounded-lg bg-rose-50 border border-rose-100 flex items-center justify-center gap-1">
                  <span class="w-1 h-1 rounded-full bg-rose-500 shrink-0"></span>
                  <span class="text-[10px] sm:text-xs font-extrabold text-rose-500 uppercase tracking-wider">Absent</span>
                </div>

              </template>

              <!-- Not Scheduled status fallback -->
              <template v-else-if="cell.isCurrentMonth">
                <div class="text-center py-1 select-none">
                  <span class="text-[9px] font-bold text-gray-300">Not Assigned</span>
                </div>
              </template>
            </div>
          </div>
        </div>

      </div>

    </div>

  </div>
</template>

<style scoped>
.grid-cols-7 {
  grid-template-columns: repeat(7, minmax(0, 1fr));
}
</style>
