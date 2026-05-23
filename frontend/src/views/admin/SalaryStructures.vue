<script setup>
import { onMounted, ref, computed, watch } from 'vue';
import { usePayrollStore } from '../../stores/payroll';
import { useAdminStore } from '../../stores/admin';
import api from '../../axios';
import {
  Wallet, Plus, Edit, Trash2, X, Calculator, ChevronDown,
  TrendingUp, TrendingDown, Users, BadgeDollarSign, Search
} from 'lucide-vue-next';

const payrollStore = usePayrollStore();
const adminStore   = useAdminStore();

const activeTab    = ref('structures'); // 'structures' | 'calculator'
const showModal    = ref(false);
const editingId    = ref(null);
const saving       = ref(false);
const formError    = ref('');
const searchQuery  = ref('');

// ── Form ─────────────────────────────────────────────────────────────────────
const defaultForm = () => ({
  employee_id:    '',
  name:           '',
  basic_salary:   '',
  effective_from: new Date().toISOString().slice(0, 10),
  is_active:      true,
  components:     [],
});
const form = ref(defaultForm());

const addComponent = () => {
  form.value.components.push({ name: '', type: 'allowance', amount: '', is_percentage: false });
};
const removeComponent = (i) => form.value.components.splice(i, 1);

const grossPreview = computed(() => {
  let g = parseFloat(form.value.basic_salary) || 0;
  for (const c of form.value.components) {
    const amt = parseFloat(c.amount) || 0;
    const val = c.is_percentage ? (g * amt / 100) : amt;
    g += c.type === 'allowance' ? val : -val;
  }
  return Math.max(0, g);
});

// ── Structures ────────────────────────────────────────────────────────────────
const structures = computed(() => {
  const q = searchQuery.value.toLowerCase();
  return payrollStore.salaryStructures.filter(s => {
    const name = s.employee?.user?.name?.toLowerCase() || '';
    return !q || name.includes(q) || s.name.toLowerCase().includes(q);
  });
});

const openCreate = () => {
  editingId.value = null;
  form.value = defaultForm();
  formError.value = '';
  showModal.value = true;
};

const openEdit = (s) => {
  editingId.value = s.id;
  form.value = {
    employee_id:    s.employee_id,
    name:           s.name,
    basic_salary:   s.basic_salary,
    effective_from: s.effective_from,
    is_active:      s.is_active,
    components:     JSON.parse(JSON.stringify(s.components || [])),
  };
  formError.value = '';
  showModal.value = true;
};

const handleSave = async () => {
  if (!form.value.employee_id || !form.value.name || !form.value.basic_salary) {
    formError.value = 'Employee, name, and basic salary are required.';
    return;
  }
  saving.value = true;
  formError.value = '';
  try {
    if (editingId.value) {
      await payrollStore.updateSalaryStructure(editingId.value, form.value);
    } else {
      await payrollStore.createSalaryStructure(form.value);
    }
    showModal.value = false;
  } catch (err) {
    formError.value = err.response?.data?.message || 'Failed to save.';
  } finally {
    saving.value = false;
  }
};

const handleDelete = async (s) => {
  if (!confirm(`Delete salary structure "${s.name}"?`)) return;
  await payrollStore.deleteSalaryStructure(s.id);
};

// ── Calculator ────────────────────────────────────────────────────────────────
const calcEmployee = ref('');
const calcMonth    = ref(new Date().toISOString().slice(0, 7));
const calcResult   = ref(null);
const calcLoading  = ref(false);
const calcError    = ref('');

const runCalculation = async () => {
  if (!calcEmployee.value) { calcError.value = 'Select an employee.'; return; }
  calcLoading.value = true;
  calcError.value   = '';
  calcResult.value  = null;
  try {
    calcResult.value = await payrollStore.calculateSalary(calcEmployee.value, calcMonth.value);
  } catch (err) {
    calcError.value = err.response?.data?.message || 'Calculation failed.';
  } finally {
    calcLoading.value = false;
  }
};

const fmt = (n) => Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });

onMounted(async () => {
  await Promise.all([
    payrollStore.fetchSalaryStructures(),
    adminStore.fetchEmployees(),
  ]);
});
</script>

<template>
  <div class="max-w-6xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center gap-2.5">
          <Wallet class="w-8 h-8 text-emerald-600" />
          Salary Structures
        </h1>
        <p class="text-gray-500 mt-1">Build customizable salary packages and calculate net pay.</p>
      </div>
      <button
        v-if="activeTab === 'structures'"
        @click="openCreate"
        class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 active:scale-95 transition-all shadow-sm"
      >
        <Plus class="w-4 h-4" /> Add Structure
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 p-1 bg-gray-100 rounded-xl w-fit">
      <button
        @click="activeTab = 'structures'"
        class="px-5 py-2 text-sm font-semibold rounded-lg transition-all"
        :class="activeTab === 'structures' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
      >
        <span class="flex items-center gap-1.5"><BadgeDollarSign class="w-4 h-4" /> Structures</span>
      </button>
      <button
        @click="activeTab = 'calculator'"
        class="px-5 py-2 text-sm font-semibold rounded-lg transition-all"
        :class="activeTab === 'calculator' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
      >
        <span class="flex items-center gap-1.5"><Calculator class="w-4 h-4" /> Salary Calculator</span>
      </button>
    </div>

    <!-- ── Structures Tab ── -->
    <div v-if="activeTab === 'structures'">
      <!-- Search -->
      <div class="relative mb-4 max-w-sm">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search employee or structure..."
          class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none bg-white"
        />
      </div>

      <!-- Loading -->
      <div v-if="payrollStore.loading" class="flex justify-center py-16">
        <div class="w-10 h-10 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <!-- Empty -->
      <div v-else-if="structures.length === 0" class="bg-white border border-gray-200 rounded-2xl p-16 text-center">
        <Wallet class="w-12 h-12 text-gray-300 mx-auto mb-3" />
        <p class="text-gray-500 font-medium">No salary structures found.</p>
        <button @click="openCreate" class="mt-4 px-5 py-2.5 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 transition-colors text-sm">
          Create First Structure
        </button>
      </div>

      <!-- Cards Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
          v-for="s in structures"
          :key="s.id"
          class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden hover:shadow-md transition-all"
          :class="{ 'opacity-60': !s.is_active }"
        >
          <!-- Card Top -->
          <div class="bg-gradient-to-br from-emerald-50 to-teal-50 p-5">
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="font-bold text-gray-900">{{ s.employee?.user?.name || 'Unknown' }}</p>
                <p class="text-xs text-gray-500">{{ s.employee?.designation?.name || '' }}</p>
              </div>
              <span
                class="px-2 py-0.5 rounded-full text-xs font-semibold"
                :class="s.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
              >
                {{ s.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <p class="text-xs text-gray-500 mt-2 font-medium">{{ s.name }}</p>
          </div>

          <!-- Salary Breakdown -->
          <div class="p-5 space-y-3">
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-500">Basic Salary</span>
              <span class="font-bold text-gray-900">₹{{ fmt(s.basic_salary) }}</span>
            </div>

            <div v-for="c in (s.components || [])" :key="c.name" class="flex justify-between items-center text-sm">
              <span class="flex items-center gap-1" :class="c.type === 'allowance' ? 'text-emerald-600' : 'text-red-500'">
                <TrendingUp v-if="c.type === 'allowance'" class="w-3 h-3" />
                <TrendingDown v-else class="w-3 h-3" />
                {{ c.name }}
              </span>
              <span :class="c.type === 'allowance' ? 'text-emerald-700 font-medium' : 'text-red-600 font-medium'">
                {{ c.type === 'allowance' ? '+' : '-' }}{{ c.is_percentage ? `${c.amount}%` : `₹${fmt(c.amount)}` }}
              </span>
            </div>

            <div class="border-t border-gray-100 pt-3 flex justify-between items-center">
              <span class="text-sm font-semibold text-gray-700">Gross Salary</span>
              <span class="text-lg font-bold text-emerald-600">₹{{ fmt(s.gross_salary) }}</span>
            </div>

            <p class="text-xs text-gray-400">Effective: {{ s.effective_from }}</p>
          </div>

          <!-- Actions -->
          <div class="flex border-t border-gray-100">
            <button @click="openEdit(s)" class="flex-1 flex items-center justify-center gap-1.5 py-3 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
              <Edit class="w-3.5 h-3.5" /> Edit
            </button>
            <div class="w-px bg-gray-100"></div>
            <button @click="handleDelete(s)" class="flex-1 flex items-center justify-center gap-1.5 py-3 text-sm font-medium text-red-500 hover:bg-red-50 transition-colors">
              <Trash2 class="w-3.5 h-3.5" /> Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Calculator Tab ── -->
    <div v-if="activeTab === 'calculator'" class="grid grid-cols-1 lg:grid-cols-5 gap-6">
      <!-- Left: Input Panel -->
      <div class="lg:col-span-2 space-y-5">
        <div class="bg-white border border-gray-200 rounded-2xl p-6 space-y-5">
          <h3 class="font-bold text-gray-900 flex items-center gap-2">
            <Calculator class="w-5 h-5 text-emerald-600" /> Net Salary Calculator
          </h3>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Employee</label>
            <select
              v-model="calcEmployee"
              class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none bg-gray-50"
            >
              <option value="">Select an employee...</option>
              <option v-for="emp in adminStore.employees" :key="emp.id" :value="emp.id">
                {{ emp.user?.name || emp.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Month</label>
            <input
              v-model="calcMonth"
              type="month"
              class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none bg-gray-50"
            />
          </div>

          <p v-if="calcError" class="text-sm text-red-500 font-medium">{{ calcError }}</p>

          <button
            @click="runCalculation"
            :disabled="calcLoading"
            class="w-full py-3 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
          >
            <span v-if="calcLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            {{ calcLoading ? 'Calculating...' : 'Calculate Net Salary' }}
          </button>
        </div>
      </div>

      <!-- Right: Result Panel -->
      <div class="lg:col-span-3">
        <div v-if="!calcResult" class="bg-white border border-dashed border-gray-200 rounded-2xl p-16 text-center h-full flex flex-col items-center justify-center">
          <Calculator class="w-14 h-14 text-gray-200 mb-4" />
          <p class="text-gray-400 font-medium">Select an employee and month, then click Calculate</p>
        </div>

        <div v-else class="space-y-4">
          <!-- Employee Info -->
          <div class="bg-white border border-gray-200 rounded-2xl p-5">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xl">
                {{ calcResult.employee?.user?.name?.charAt(0) || 'E' }}
              </div>
              <div>
                <p class="font-bold text-gray-900 text-lg">{{ calcResult.employee?.user?.name }}</p>
                <p class="text-sm text-gray-500">{{ calcResult.month }}</p>
              </div>
            </div>
          </div>

          <!-- Attendance Summary -->
          <div class="bg-white border border-gray-200 rounded-2xl p-5">
            <h4 class="font-semibold text-gray-700 mb-4">Attendance Summary</h4>
            <div class="grid grid-cols-3 gap-3">
              <div class="text-center p-3 bg-green-50 rounded-xl">
                <p class="text-2xl font-bold text-green-700">{{ calcResult.attendance_summary.present }}</p>
                <p class="text-xs text-green-600 font-medium mt-0.5">Present</p>
              </div>
              <div class="text-center p-3 bg-amber-50 rounded-xl">
                <p class="text-2xl font-bold text-amber-700">{{ calcResult.attendance_summary.late }}</p>
                <p class="text-xs text-amber-600 font-medium mt-0.5">Late ({{ calcResult.attendance_summary.total_late_minutes }} min)</p>
              </div>
              <div class="text-center p-3 bg-red-50 rounded-xl">
                <p class="text-2xl font-bold text-red-700">{{ calcResult.attendance_summary.absent }}</p>
                <p class="text-xs text-red-600 font-medium mt-0.5">Absent</p>
              </div>
            </div>
          </div>

          <!-- Salary Breakdown -->
          <div class="bg-white border border-gray-200 rounded-2xl p-5 space-y-3">
            <h4 class="font-semibold text-gray-700">Salary Calculation</h4>

            <div class="flex justify-between text-sm py-1">
              <span class="text-gray-500">Gross Salary</span>
              <span class="font-semibold text-gray-900">₹{{ fmt(calcResult.gross_salary) }}</span>
            </div>

            <template v-if="calcResult.penalty_details.length > 0">
              <div class="border-t border-dashed border-gray-200 pt-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Penalty Deductions</p>
                <div v-for="pd in calcResult.penalty_details" :key="pd.rule" class="flex justify-between text-sm py-1">
                  <span class="text-red-500 flex items-center gap-1.5">
                    <TrendingDown class="w-3.5 h-3.5" />
                    {{ pd.rule }} ({{ pd.effective }}× ₹{{ fmt(pd.per_unit) }})
                  </span>
                  <span class="font-semibold text-red-600">-₹{{ fmt(pd.total) }}</span>
                </div>
              </div>
            </template>

            <div v-else class="text-sm text-gray-400 italic py-1">No penalties applied this month. 🎉</div>

            <div class="border-t-2 border-gray-200 pt-3">
              <div class="flex justify-between items-center">
                <span class="font-bold text-gray-800 text-base">Net Salary</span>
                <span class="text-2xl font-bold text-emerald-600">₹{{ fmt(calcResult.net_salary) }}</span>
              </div>
              <div v-if="calcResult.total_penalty > 0" class="text-xs text-red-400 text-right mt-0.5">
                Total deducted: ₹{{ fmt(calcResult.total_penalty) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Create / Edit Modal ── -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-emerald-50 to-teal-50 sticky top-0">
            <div>
              <h3 class="font-bold text-gray-900 text-lg">{{ editingId ? 'Edit Salary Structure' : 'Create Salary Structure' }}</h3>
              <p class="text-xs text-gray-400 mt-0.5">Define employee pay package</p>
            </div>
            <button @click="showModal = false" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-white/70 transition-all">
              <X class="w-4 h-4" />
            </button>
          </div>

          <form @submit.prevent="handleSave" class="p-6 space-y-5">
            <!-- Employee + Name -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Employee <span class="text-red-500">*</span></label>
                <select
                  v-model="form.employee_id"
                  :disabled="!!editingId"
                  class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none bg-gray-50 disabled:opacity-60"
                >
                  <option value="">Select employee...</option>
                  <option v-for="emp in adminStore.employees" :key="emp.id" :value="emp.id">
                    {{ emp.user?.name || emp.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Structure Name <span class="text-red-500">*</span></label>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="e.g., Engineer Package"
                  class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none bg-gray-50"
                />
              </div>
            </div>

            <!-- Basic Salary + Effective From -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Basic Salary (₹) <span class="text-red-500">*</span></label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₹</span>
                  <input
                    v-model="form.basic_salary"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                    class="w-full pl-7 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none bg-gray-50"
                  />
                </div>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Effective From</label>
                <input
                  v-model="form.effective_from"
                  type="date"
                  class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none bg-gray-50"
                />
              </div>
            </div>

            <!-- Components -->
            <div>
              <div class="flex items-center justify-between mb-3">
                <label class="text-sm font-semibold text-gray-700">Allowances & Deductions</label>
                <button
                  type="button"
                  @click="addComponent"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors"
                >
                  <Plus class="w-3.5 h-3.5" /> Add Component
                </button>
              </div>

              <div class="space-y-3">
                <div
                  v-for="(comp, i) in form.components"
                  :key="i"
                  class="grid grid-cols-12 gap-2 items-center bg-gray-50 rounded-xl p-3"
                >
                  <!-- Name -->
                  <input
                    v-model="comp.name"
                    type="text"
                    placeholder="Component name"
                    class="col-span-4 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-1 focus:ring-emerald-500 outline-none bg-white"
                  />
                  <!-- Type -->
                  <select
                    v-model="comp.type"
                    class="col-span-2 px-2 py-2 border border-gray-200 rounded-lg text-sm focus:ring-1 focus:ring-emerald-500 outline-none bg-white"
                  >
                    <option value="allowance">+ Allowance</option>
                    <option value="deduction">- Deduction</option>
                  </select>
                  <!-- Amount -->
                  <input
                    v-model="comp.amount"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="Amount"
                    class="col-span-3 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-1 focus:ring-emerald-500 outline-none bg-white"
                  />
                  <!-- Percentage toggle -->
                  <button
                    type="button"
                    @click="comp.is_percentage = !comp.is_percentage"
                    class="col-span-2 py-2 text-xs font-semibold rounded-lg border transition-all"
                    :class="comp.is_percentage ? 'bg-blue-50 border-blue-300 text-blue-700' : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300'"
                  >
                    {{ comp.is_percentage ? '% Basic' : 'Fixed ₹' }}
                  </button>
                  <!-- Remove -->
                  <button
                    type="button"
                    @click="removeComponent(i)"
                    class="col-span-1 flex items-center justify-center text-gray-400 hover:text-red-500 transition-colors"
                  >
                    <X class="w-4 h-4" />
                  </button>
                </div>

                <div v-if="form.components.length === 0" class="text-center text-sm text-gray-400 py-4 border border-dashed border-gray-200 rounded-xl">
                  No components added. Click "Add Component" to build the pay package.
                </div>
              </div>
            </div>

            <!-- Gross Preview -->
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-4 flex justify-between items-center">
              <span class="font-semibold text-emerald-800 text-sm">Calculated Gross Salary</span>
              <span class="text-2xl font-bold text-emerald-700">₹{{ fmt(grossPreview) }}</span>
            </div>

            <!-- Active -->
            <div class="flex items-center gap-3">
              <button
                type="button"
                @click="form.is_active = !form.is_active"
                class="relative w-11 h-6 rounded-full transition-colors duration-200"
                :class="form.is_active ? 'bg-emerald-500' : 'bg-gray-300'"
              >
                <span
                  class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"
                  :class="form.is_active ? 'translate-x-5' : 'translate-x-0'"
                ></span>
              </button>
              <span class="text-sm font-medium text-gray-700">Set as active structure</span>
            </div>

            <p v-if="formError" class="text-sm text-red-500 font-medium">{{ formError }}</p>

            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="showModal = false" class="px-5 py-2.5 text-gray-600 hover:bg-gray-100 rounded-xl text-sm font-medium transition-colors">Cancel</button>
              <button type="submit" :disabled="saving" class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition-colors disabled:opacity-50 flex items-center gap-2">
                <span v-if="saving" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                {{ saving ? 'Saving...' : (editingId ? 'Update' : 'Create Structure') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
