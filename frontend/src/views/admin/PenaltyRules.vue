<script setup>
import { onMounted, ref, computed } from 'vue';
import { usePayrollStore } from '../../stores/payroll';
import { 
  AlertTriangle, Plus, Edit, Trash2, X, Check, ShieldAlert, 
  Clock, UserX, Coffee, ToggleLeft, ToggleRight, Percent, DollarSign
} from 'lucide-vue-next';

const payrollStore = usePayrollStore();

const showModal = ref(false);
const editingRule = ref(null);
const saving = ref(false);
const formError = ref('');

const defaultForm = () => ({
  name: '',
  type: 'late',
  deduction_type: 'fixed',
  deduction_value: '',
  grace_minutes: 0,
  applies_after: 1,
  active: true,
});

const form = ref(defaultForm());

const typeConfig = {
  late:     { label: 'Late Arrival',  icon: Clock,   color: 'amber'  },
  absent:   { label: 'Absent',        icon: UserX,   color: 'red'    },
  half_day: { label: 'Half Day',      icon: Coffee,  color: 'purple' },
};

const typeColorClasses = {
  amber:  { badge: 'bg-amber-50 text-amber-700 border-amber-200',  icon: 'text-amber-500' },
  red:    { badge: 'bg-red-50 text-red-700 border-red-200',        icon: 'text-red-500'   },
  purple: { badge: 'bg-purple-50 text-purple-700 border-purple-200', icon: 'text-purple-500' },
};

const rules = computed(() => payrollStore.penaltyRules);

const openCreate = () => {
  editingRule.value = null;
  form.value = defaultForm();
  formError.value = '';
  showModal.value = true;
};

const openEdit = (rule) => {
  editingRule.value = rule;
  form.value = {
    name:            rule.name,
    type:            rule.type,
    deduction_type:  rule.deduction_type,
    deduction_value: rule.deduction_value,
    grace_minutes:   rule.grace_minutes,
    applies_after:   rule.applies_after,
    active:          rule.active,
  };
  formError.value = '';
  showModal.value = true;
};

const handleSave = async () => {
  if (!form.value.name || !form.value.deduction_value) {
    formError.value = 'Name and deduction value are required.';
    return;
  }
  saving.value = true;
  formError.value = '';
  try {
    if (editingRule.value) {
      await payrollStore.updatePenaltyRule(editingRule.value.id, form.value);
    } else {
      await payrollStore.createPenaltyRule(form.value);
    }
    showModal.value = false;
  } catch (err) {
    formError.value = err.response?.data?.message || 'Failed to save rule.';
  } finally {
    saving.value = false;
  }
};

const handleDelete = async (rule) => {
  if (!confirm(`Delete penalty rule "${rule.name}"?`)) return;
  await payrollStore.deletePenaltyRule(rule.id);
};

const toggleActive = async (rule) => {
  await payrollStore.updatePenaltyRule(rule.id, { ...rule, active: !rule.active });
};

const formatDeduction = (rule) => {
  return rule.deduction_type === 'percentage'
    ? `${rule.deduction_value}%`
    : `₹${Number(rule.deduction_value).toLocaleString()}`;
};

onMounted(() => payrollStore.fetchPenaltyRules());
</script>

<template>
  <div class="max-w-5xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center gap-2.5">
          <ShieldAlert class="w-8 h-8 text-rose-600" />
          Attendance Penalty Rules
        </h1>
        <p class="text-gray-500 mt-1">Configure automatic salary deductions for attendance violations.</p>
      </div>
      <button
        @click="openCreate"
        class="inline-flex items-center gap-2 px-5 py-2.5 bg-rose-600 text-white font-semibold rounded-xl hover:bg-rose-700 active:scale-95 transition-all shadow-sm"
      >
        <Plus class="w-4 h-4" /> Add Rule
      </button>
    </div>

    <!-- Loading -->
    <div v-if="payrollStore.loading" class="flex justify-center py-16">
      <div class="w-10 h-10 border-4 border-rose-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="rules.length === 0" class="bg-white border border-gray-200 rounded-2xl p-16 text-center">
      <ShieldAlert class="w-12 h-12 text-gray-300 mx-auto mb-3" />
      <p class="text-gray-500 font-medium">No penalty rules configured yet.</p>
      <p class="text-gray-400 text-sm mt-1">Add rules to automatically calculate salary deductions.</p>
      <button @click="openCreate" class="mt-5 px-5 py-2.5 bg-rose-600 text-white font-semibold rounded-xl hover:bg-rose-700 transition-colors text-sm">
        Create First Rule
      </button>
    </div>

    <!-- Rules Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="rule in rules"
        :key="rule.id"
        class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 flex flex-col gap-4 transition-all hover:shadow-md"
        :class="{ 'opacity-60': !rule.active }"
      >
        <!-- Top Row -->
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-center gap-3 min-w-0">
            <div
              class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
              :class="`bg-${typeConfig[rule.type].color}-50`"
            >
              <component
                :is="typeConfig[rule.type].icon"
                class="w-5 h-5"
                :class="`text-${typeConfig[rule.type].color}-600`"
              />
            </div>
            <div class="min-w-0">
              <p class="font-bold text-gray-900 truncate">{{ rule.name }}</p>
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border"
                :class="typeColorClasses[typeConfig[rule.type].color].badge"
              >
                {{ typeConfig[rule.type].label }}
              </span>
            </div>
          </div>

          <!-- Active Toggle -->
          <button @click="toggleActive(rule)" class="shrink-0 text-gray-400 hover:text-gray-600 transition-colors" :title="rule.active ? 'Deactivate' : 'Activate'">
            <ToggleRight v-if="rule.active" class="w-6 h-6 text-emerald-500" />
            <ToggleLeft v-else class="w-6 h-6 text-gray-400" />
          </button>
        </div>

        <!-- Deduction Info -->
        <div class="bg-gray-50 rounded-xl px-4 py-3 flex items-center justify-between">
          <span class="text-xs text-gray-500 font-medium">Deduction per occurrence</span>
          <span class="text-lg font-bold text-rose-600">{{ formatDeduction(rule) }}</span>
        </div>

        <!-- Meta -->
        <div class="grid grid-cols-2 gap-2 text-xs text-gray-500">
          <div class="flex flex-col">
            <span class="text-gray-400">Grace Period</span>
            <span class="font-semibold text-gray-700">{{ rule.grace_minutes }} min</span>
          </div>
          <div class="flex flex-col">
            <span class="text-gray-400">Applies After</span>
            <span class="font-semibold text-gray-700">{{ rule.applies_after }} occurrence{{ rule.applies_after > 1 ? 's' : '' }}</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 pt-1 border-t border-gray-100">
          <button @click="openEdit(rule)" class="flex-1 flex items-center justify-center gap-1.5 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
            <Edit class="w-3.5 h-3.5" /> Edit
          </button>
          <button @click="handleDelete(rule)" class="flex-1 flex items-center justify-center gap-1.5 py-2 text-sm font-medium text-red-500 hover:bg-red-50 rounded-lg transition-colors">
            <Trash2 class="w-3.5 h-3.5" /> Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
          <!-- Modal Header -->
          <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-rose-50 to-orange-50">
            <div>
              <h3 class="font-bold text-gray-900 text-lg">
                {{ editingRule ? 'Edit Penalty Rule' : 'Create Penalty Rule' }}
              </h3>
              <p class="text-xs text-gray-400 mt-0.5">Configure attendance deduction settings</p>
            </div>
            <button @click="showModal = false" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-white/70 transition-all">
              <X class="w-4 h-4" />
            </button>
          </div>

          <form @submit.prevent="handleSave" class="p-6 space-y-5">
            <!-- Name -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5">Rule Name <span class="text-red-500">*</span></label>
              <input
                v-model="form.name"
                type="text"
                placeholder="e.g., Late Arrival Penalty"
                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-rose-500 focus:border-transparent outline-none transition-all bg-gray-50"
              />
            </div>

            <!-- Type -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Violation Type <span class="text-red-500">*</span></label>
              <div class="grid grid-cols-3 gap-2">
                <button
                  v-for="(cfg, key) in typeConfig"
                  :key="key"
                  type="button"
                  @click="form.type = key"
                  class="flex flex-col items-center gap-1.5 py-3 px-2 rounded-xl border-2 text-xs font-semibold transition-all"
                  :class="form.type === key
                    ? `border-${cfg.color}-500 bg-${cfg.color}-50 text-${cfg.color}-700`
                    : 'border-gray-200 text-gray-500 hover:border-gray-300 bg-white'"
                >
                  <component :is="cfg.icon" class="w-4 h-4" />
                  {{ cfg.label }}
                </button>
              </div>
            </div>

            <!-- Deduction -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Deduction Type</label>
                <select
                  v-model="form.deduction_type"
                  class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-rose-500 focus:border-transparent outline-none bg-gray-50"
                >
                  <option value="fixed">Fixed Amount (₹)</option>
                  <option value="percentage">Percentage (%)</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                  Value <span class="text-gray-400">({{ form.deduction_type === 'percentage' ? '%' : '₹' }})</span>
                </label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">
                    {{ form.deduction_type === 'percentage' ? '%' : '₹' }}
                  </span>
                  <input
                    v-model="form.deduction_value"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="0"
                    class="w-full pl-8 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-rose-500 focus:border-transparent outline-none bg-gray-50"
                  />
                </div>
              </div>
            </div>

            <!-- Grace / Applies After -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Grace Period (minutes)</label>
                <input
                  v-model="form.grace_minutes"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-rose-500 focus:border-transparent outline-none bg-gray-50"
                />
                <p class="text-xs text-gray-400 mt-1">No penalty within this window</p>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Applies After (occurrences)</label>
                <input
                  v-model="form.applies_after"
                  type="number"
                  min="1"
                  placeholder="1"
                  class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-rose-500 focus:border-transparent outline-none bg-gray-50"
                />
                <p class="text-xs text-gray-400 mt-1">Start penalizing after N times</p>
              </div>
            </div>

            <!-- Active -->
            <div class="flex items-center gap-3 py-2">
              <button
                type="button"
                @click="form.active = !form.active"
                class="relative w-11 h-6 rounded-full transition-colors duration-200"
                :class="form.active ? 'bg-emerald-500' : 'bg-gray-300'"
              >
                <span
                  class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"
                  :class="form.active ? 'translate-x-5' : 'translate-x-0'"
                ></span>
              </button>
              <span class="text-sm font-medium text-gray-700">Rule is {{ form.active ? 'active' : 'inactive' }}</span>
            </div>

            <!-- Error -->
            <p v-if="formError" class="text-sm text-red-500 font-medium">{{ formError }}</p>

            <!-- Footer -->
            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="showModal = false" class="px-5 py-2.5 text-gray-600 hover:bg-gray-100 rounded-xl text-sm font-medium transition-colors">
                Cancel
              </button>
              <button type="submit" :disabled="saving" class="px-5 py-2.5 bg-rose-600 text-white rounded-xl text-sm font-semibold hover:bg-rose-700 transition-colors disabled:opacity-50 flex items-center gap-2">
                <span v-if="saving" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                {{ saving ? 'Saving...' : (editingRule ? 'Update Rule' : 'Create Rule') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
