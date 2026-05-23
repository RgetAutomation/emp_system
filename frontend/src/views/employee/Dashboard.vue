<script setup>


const authStore = useAuthStore();
const isCheckedIn = ref(false);
const isCheckedOut = ref(false);
const attendanceRecord = ref(null);
const loading = ref(false);
const isFlipped = ref(false);

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

const flipCard = () => {
  isFlipped.value = !isFlipped.value;
};

const handlePrint = () => {
  window.print();
};

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A';
  const date = new Date(dateStr);
  return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};



    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
      <!-- Left side: ID Card (1 col) -->
      <div class="lg:col-span-1 bg-white rounded-3xl border border-gray-200 shadow-sm p-6 flex flex-col items-center">
        <h2 class="text-lg font-semibold text-gray-900 mb-1 self-start">Virtual ID Card</h2>
        <p class="text-sm text-gray-500 mb-6 self-start">Your digital workspace identity.</p>

        <div class="w-full flex flex-col items-center">
          <div class="w-[320px] h-[506px] perspective-1000 cursor-pointer" @click="flipCard">
            <div :class="['w-full h-full relative preserve-3d duration-700 transition-transform rounded-2xl shadow-xl', isFlipped ? 'rotate-y-180' : '']">
              
              <!-- Front Side -->
              <div class="absolute inset-0 w-full h-full rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 border border-white/10 p-6 flex flex-col justify-between text-white backface-hidden overflow-hidden shadow-2xl">
                <!-- Abstract Background Blobs -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl -translate-y-12 translate-x-12"></div>
                <div class="absolute bottom-0 left-0 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl translate-y-12 -translate-x-12"></div>
                
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-white/10 pb-3 relative z-10">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center font-bold text-sm shadow-md shadow-blue-500/20 text-white">
                      {{ authStore.user?.company?.name?.charAt(0) || 'E' }}
                    </div>
                    <div>
                      <h4 class="text-xs font-bold tracking-wide truncate max-w-[130px] leading-tight">{{ authStore.user?.company?.name || 'Workspace' }}</h4>
                      <p class="text-[8px] text-indigo-300 font-semibold tracking-widest uppercase leading-none mt-0.5">Workspace</p>
                    </div>
                  </div>
                  <span class="text-[9px] uppercase tracking-widest text-slate-400 font-bold bg-white/5 px-2 py-0.5 rounded-full border border-white/5">ID Card</span>
                </div>

                <!-- Body / Image and Name -->
                <div class="flex flex-col items-center my-auto relative z-10">
                  <div class="relative mb-3 group">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full blur opacity-30 group-hover:opacity-50 transition duration-1000"></div>
                    <div class="relative">
                      <img v-if="authStore.user?.employee?.documents?.profile_photo" :src="`http://localhost:8000/storage/${authStore.user.employee.documents.profile_photo}`" class="w-24 h-24 rounded-full object-cover border-2 border-white/20 shadow-lg" />
                      <div v-else class="w-24 h-24 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-slate-400 shadow-lg">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                      </div>
                    </div>
                  </div>

                  <h3 class="text-base font-bold text-center text-white tracking-wide uppercase leading-tight px-2">{{ authStore.user?.name }}</h3>
                  <p class="text-xs font-semibold text-indigo-300 text-center leading-normal mt-1">{{ authStore.user?.employee?.designation?.name || 'N/A' }}</p>
                  <p class="text-[10px] text-slate-400 text-center uppercase tracking-wider mt-0.5">{{ authStore.user?.employee?.department?.name || 'N/A' }}</p>
                </div>

                <!-- Smart chip mockup -->
                <div class="absolute right-6 top-1/2 -translate-y-1/2 w-8 h-6 rounded border border-yellow-600/40 opacity-20 chip-gradient flex items-center justify-center">
                  <div class="grid grid-cols-3 gap-0.5 w-full h-full p-0.5">
                    <div class="border-r border-b border-slate-700"></div><div class="border-r border-b border-slate-700"></div><div class="border-b border-slate-700"></div>
                    <div class="border-r border-slate-700"></div><div class="border-r border-slate-700"></div><div></div>
                  </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-white/10 pt-3 flex justify-between items-end relative z-10">
                  <div class="text-left">
                    <p class="text-[8px] text-slate-500 uppercase tracking-wider font-semibold">Employee ID</p>
                    <p class="text-xs font-mono font-bold text-white tracking-wide mt-0.5">{{ authStore.user?.employee?.employee_id || 'N/A' }}</p>
                  </div>
                  <div class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded-full text-[9px] font-bold flex items-center gap-1">
                    <span class="w-1 h-1 rounded-full bg-emerald-400 animate-pulse"></span> ACTIVE
                  </div>
                </div>
              </div>

              <!-- Back Side -->
              <div class="absolute inset-0 w-full h-full rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 border border-white/10 p-6 flex flex-col justify-between text-white backface-hidden rotate-y-180 overflow-hidden shadow-2xl">
                <!-- Abstract Background Blobs -->
                <div class="absolute top-0 left-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl -translate-y-12 -translate-x-12"></div>
                <div class="absolute bottom-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl translate-y-12 translate-x-12"></div>

                <!-- Header -->
                <div class="text-center border-b border-white/10 pb-2.5 relative z-10">
                  <h4 class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Workspace Details</h4>
                </div>

                <!-- Info List -->
                <div class="my-auto space-y-2 relative z-10 text-[11px] text-slate-300">
                  <div class="flex items-center gap-2.5 bg-white/5 border border-white/5 rounded-xl p-2">
                    <Mail class="w-3.5 h-3.5 text-indigo-400 shrink-0" />
                    <div class="min-w-0">
                      <p class="text-[8px] text-slate-500 uppercase leading-none font-semibold mb-0.5">Email</p>
                      <p class="font-medium truncate text-white leading-normal">{{ authStore.user?.email }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2.5 bg-white/5 border border-white/5 rounded-xl p-2">
                    <Phone class="w-3.5 h-3.5 text-indigo-400 shrink-0" />
                    <div>
                      <p class="text-[8px] text-slate-500 uppercase leading-none font-semibold mb-0.5">Phone</p>
                      <p class="font-medium text-white leading-normal">{{ authStore.user?.employee?.phone || 'N/A' }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2.5 bg-white/5 border border-white/5 rounded-xl p-2">
                    <Calendar class="w-3.5 h-3.5 text-indigo-400 shrink-0" />
                    <div>
                      <p class="text-[8px] text-slate-500 uppercase leading-none font-semibold mb-0.5">Joining Date</p>
                      <p class="font-medium text-white leading-normal">{{ formatDate(authStore.user?.employee?.join_date) }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2.5 bg-white/5 border border-white/5 rounded-xl p-2">
                    <MapPin class="w-3.5 h-3.5 text-indigo-400 shrink-0" />
                    <div class="min-w-0">
                      <p class="text-[8px] text-slate-500 uppercase leading-none font-semibold mb-0.5">Address</p>
                      <p class="font-medium truncate text-white leading-normal">{{ authStore.user?.company?.address || 'N/A' }}</p>
                    </div>
                  </div>
                </div>

                <!-- Footer / Barcode -->
                <div class="border-t border-white/10 pt-2 flex flex-col items-center gap-1.5 relative z-10">
                  <svg class="h-6 w-full text-slate-300 opacity-60" viewBox="0 0 100 20" preserveAspectRatio="none">
                    <rect x="0" width="2" height="20" fill="currentColor"/>
                    <rect x="4" width="1" height="20" fill="currentColor"/>
                    <rect x="6" width="3" height="20" fill="currentColor"/>
                    <rect x="11" width="1" height="20" fill="currentColor"/>
                    <rect x="14" width="2" height="20" fill="currentColor"/>
                    <rect x="18" width="4" height="20" fill="currentColor"/>
                    <rect x="23" width="1" height="20" fill="currentColor"/>
                    <rect x="26" width="2" height="20" fill="currentColor"/>
                    <rect x="30" width="3" height="20" fill="currentColor"/>
                    <rect x="35" width="1" height="20" fill="currentColor"/>
                    <rect x="38" width="2" height="20" fill="currentColor"/>
                    <rect x="42" width="4" height="20" fill="currentColor"/>
                    <rect x="48" width="1" height="20" fill="currentColor"/>
                    <rect x="51" width="3" height="20" fill="currentColor"/>
                    <rect x="56" width="2" height="20" fill="currentColor"/>
                    <rect x="60" width="1" height="20" fill="currentColor"/>
                    <rect x="63" width="4" height="20" fill="currentColor"/>
                    <rect x="69" width="2" height="20" fill="currentColor"/>
                    <rect x="73" width="1" height="20" fill="currentColor"/>
                    <rect x="76" width="3" height="20" fill="currentColor"/>
                    <rect x="81" width="2" height="20" fill="currentColor"/>
                    <rect x="85" width="4" height="20" fill="currentColor"/>
                    <rect x="91" width="1" height="20" fill="currentColor"/>
                    <rect x="94" width="3" height="20" fill="currentColor"/>
                    <rect x="98" width="2" height="20" fill="currentColor"/>
                  </svg>
                  <p class="text-[6px] text-slate-500 uppercase tracking-widest font-semibold">Security Identifier</p>
                </div>
              </div>

            </div>
          </div>

          <!-- Controls -->
          <div class="flex items-center gap-3 mt-5 w-full">
            <button @click="flipCard" class="flex-1 flex items-center justify-center gap-2 py-2 px-4 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-xl border border-gray-200 shadow-sm transition-all text-xs">
              <RefreshCw class="w-3.5 h-3.5 text-gray-500" />
              Flip Card
            </button>
            <button @click="handlePrint" class="flex-1 flex items-center justify-center gap-2 py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl shadow-md shadow-indigo-600/10 hover:shadow-indigo-600/20 transition-all text-xs">
              <Printer class="w-3.5 h-3.5" />
              Print Card
            </button>
          </div>
        </div>
      </div>

      <!-- Right side: Attendance & Recent Activity (2 cols) -->
      <div class="lg:col-span-2 space-y-8">
        <!-- Attendance Card -->
        <div class="bg-white rounded-3xl p-8 sm:p-10 border border-gray-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-8 relative overflow-hidden">
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
        <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
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
    </div>
  </div>

  <!-- Print Only Container -->
  <div class="print-only-container">
    <div class="flex justify-center gap-8">
      <!-- Physical Front Side -->
      <div class="physical-card-print p-4 flex flex-col justify-between overflow-hidden text-white" style="background: linear-gradient(to bottom, #0f172a, #1e1b4b, #0f172a);">
        <div class="flex items-center justify-between border-b border-white/10 pb-1.5">
          <div class="flex items-center gap-1">
            <div class="w-4 h-4 bg-blue-600 rounded flex items-center justify-center font-bold text-[8px] text-white">
              {{ authStore.user?.company?.name?.charAt(0) || 'E' }}
            </div>
            <span class="text-[9px] font-bold tracking-wider truncate max-w-[80px] text-white">{{ authStore.user?.company?.name || 'Workspace' }}</span>
          </div>
          <span class="text-[6px] uppercase tracking-widest text-slate-400 font-semibold">Employee ID</span>
        </div>
        
        <div class="flex flex-col items-center my-auto">
          <div class="relative mb-2">
            <img v-if="authStore.user?.employee?.documents?.profile_photo" :src="`http://localhost:8000/storage/${authStore.user.employee.documents.profile_photo}`" class="w-14 h-14 rounded-full object-cover border border-white/20 shadow" />
            <div v-else class="w-14 h-14 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-400">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
          </div>
          
          <h3 class="text-[10px] font-bold text-center uppercase tracking-wide text-white leading-tight">{{ authStore.user?.name }}</h3>
          <p class="text-[7px] font-medium text-indigo-300 text-center leading-none mt-0.5">{{ authStore.user?.employee?.designation?.name || 'N/A' }}</p>
          <p class="text-[6px] text-slate-400 text-center uppercase tracking-wider mt-0.5">{{ authStore.user?.employee?.department?.name || 'N/A' }}</p>
        </div>
        
        <div class="border-t border-white/10 pt-1.5 flex justify-between items-end">
          <div class="text-left">
            <p class="text-[5px] text-slate-500 uppercase leading-none">ID Number</p>
            <p class="text-[8px] font-mono font-bold leading-none mt-0.5">{{ authStore.user?.employee?.employee_id || 'N/A' }}</p>
          </div>
          <div class="bg-emerald-500/20 text-emerald-400 px-1.5 py-0.5 rounded text-[6px] font-bold flex items-center gap-0.5">
            <span class="w-1 h-1 rounded-full bg-emerald-400"></span> ACTIVE
          </div>
        </div>
      </div>

      <!-- Physical Back Side -->
      <div class="physical-card-print p-4 flex flex-col justify-between overflow-hidden text-white" style="background: linear-gradient(to bottom, #0f172a, #1e1b4b, #0f172a);">
        <div class="text-center border-b border-white/10 pb-1.5">
          <p class="text-[7px] uppercase tracking-wider text-slate-400 font-semibold">Workspace Details</p>
        </div>

        <div class="space-y-1 my-auto text-[7px] text-slate-300">
          <div class="flex items-center gap-1">
            <span class="text-slate-500 w-8 font-semibold">Email:</span>
            <span class="truncate max-w-[90px]">{{ authStore.user?.email }}</span>
          </div>
          <div class="flex items-center gap-1">
            <span class="text-slate-500 w-8 font-semibold">Phone:</span>
            <span>{{ authStore.user?.employee?.phone || 'N/A' }}</span>
          </div>
          <div class="flex items-center gap-1">
            <span class="text-slate-500 w-8 font-semibold">Joined:</span>
            <span>{{ formatDate(authStore.user?.employee?.join_date) }}</span>
          </div>
          <div class="flex items-center gap-1">
            <span class="text-slate-500 w-8 font-semibold">Office:</span>
            <span class="truncate max-w-[90px]">{{ authStore.user?.company?.address || 'N/A' }}</span>
          </div>
        </div>

        <div class="border-t border-white/10 pt-1.5 flex flex-col items-center gap-1">
          <svg class="h-5 w-full text-slate-300 opacity-60" viewBox="0 0 100 20" preserveAspectRatio="none">
            <rect x="0" width="2" height="20" fill="currentColor"/>
            <rect x="4" width="1" height="20" fill="currentColor"/>
            <rect x="6" width="3" height="20" fill="currentColor"/>
            <rect x="11" width="1" height="20" fill="currentColor"/>
            <rect x="14" width="2" height="20" fill="currentColor"/>
            <rect x="18" width="4" height="20" fill="currentColor"/>
            <rect x="23" width="1" height="20" fill="currentColor"/>
            <rect x="26" width="2" height="20" fill="currentColor"/>
            <rect x="30" width="3" height="20" fill="currentColor"/>
            <rect x="35" width="1" height="20" fill="currentColor"/>
            <rect x="38" width="2" height="20" fill="currentColor"/>
            <rect x="42" width="4" height="20" fill="currentColor"/>
            <rect x="48" width="1" height="20" fill="currentColor"/>
            <rect x="51" width="3" height="20" fill="currentColor"/>
            <rect x="56" width="2" height="20" fill="currentColor"/>
            <rect x="60" width="1" height="20" fill="currentColor"/>
            <rect x="63" width="4" height="20" fill="currentColor"/>
            <rect x="69" width="2" height="20" fill="currentColor"/>
            <rect x="73" width="1" height="20" fill="currentColor"/>
            <rect x="76" width="3" height="20" fill="currentColor"/>
            <rect x="81" width="2" height="20" fill="currentColor"/>
            <rect x="85" width="4" height="20" fill="currentColor"/>
            <rect x="91" width="1" height="20" fill="currentColor"/>
            <rect x="94" width="3" height="20" fill="currentColor"/>
            <rect x="98" width="2" height="20" fill="currentColor"/>
          </svg>
          <p class="text-[5px] text-slate-500 uppercase tracking-widest font-semibold">Security Identifier</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* 3D Flip Card Styles */
.perspective-1000 {
  perspective: 1000px;
}
.preserve-3d {
  transform-style: preserve-3d;
}
.backface-hidden {
  backface-visibility: hidden;
}
.rotate-y-180 {
  transform: rotateY(180deg);
}

.chip-gradient {
  background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 50%, #94a3b8 100%);
}

/* Print CSS */
@media print {
  /* Hide dashboard content */
  body * {
    visibility: hidden !important;
  }
  
  .print-only-container, .print-only-container * {
    visibility: visible !important;
  }
  
  .print-only-container {
    display: flex !important;
    position: absolute !important;
    left: 0 !important;
    top: 0 !important;
    width: 100% !important;
    justify-content: center !important;
    gap: 30px !important;
    padding-top: 3cm !important;
  }
  
  /* Force exact dimensions for physical card */
  .physical-card-print {
    width: 54mm !important;
    height: 85.6mm !important;
    border: 1px dashed #94a3b8 !important;
    border-radius: 8px !important;
    box-sizing: border-box !important;
    margin: 0 !important;
    position: relative !important;
    page-break-inside: avoid !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    background: linear-gradient(to bottom, #0f172a, #1e1b4b, #0f172a) !important;
    color: white !important;
  }
}

/* Ensure print container is hidden on screen */
@media screen {
  .print-only-container {
    display: none !important;
  }
}
</style>
