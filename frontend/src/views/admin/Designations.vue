<script setup>
import { onMounted, ref } from 'vue';
import { useAdminStore } from '../../stores/admin';
import { Briefcase, Plus, Trash2, Edit2, Building2, Hash, DollarSign, CheckCircle2, XCircle, Users } from 'lucide-vue-next';

const adminStore = useAdminStore();
const showModal = ref(false);
const form = ref({ 
  name: '',
  code: '',
  department_id: null,
  parent_id: null,
  min_salary: '',
  max_salary: '',
  is_active: true
});
const isEditing = ref(false);
const editId = ref(null);

onMounted(async () => {
  await adminStore.fetchDesignations();
  await adminStore.fetchDepartments(); // Need departments for the dropdown
});

const openCreateModal = () => {
  isEditing.value = false;
  form.value = {
    name: '',
    code: '',
    department_id: null,
    parent_id: null,
    min_salary: '',
    max_salary: '',
    is_active: true
  };
  showModal.value = true;
};

const openEditModal = (desig) => {
  isEditing.value = true;
  editId.value = desig.id;
  form.value = {
    name: desig.name,
    code: desig.code || '',
    department_id: desig.department_id || null,
    parent_id: desig.parent_id || null,
    min_salary: desig.min_salary || '',
    max_salary: desig.max_salary || '',
    is_active: desig.is_active === undefined ? true : !!desig.is_active
  };
  showModal.value = true;
};

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await adminStore.updateDesignation(editId.value, form.value);
    } else {
      await adminStore.createDesignation(form.value);
    }
    showModal.value = false;
  } catch (error) {
    console.error(error);
  }
};

const handleDelete = async (id) => {
  if (confirm('Are you sure you want to delete this designation?')) {
    await adminStore.deleteDesignation(id);
  }
};

const formatCurrency = (val) => {
  if (!val) return '';
  return parseFloat(val).toLocaleString('en-IN');
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const d = new Date(dateString);
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
  <div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-black text-gray-900 flex items-center gap-2">
          <Briefcase class="w-6 h-6 text-blue-600" />
          Designations
        </h1>
        <p class="text-gray-500 mt-1 text-sm font-medium">Manage job titles, reporting hierarchy, and salary bands.</p>
      </div>
      <button @click="openCreateModal" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors shadow-sm font-bold text-sm">
        <Plus class="w-4 h-4" />
        Add Designation
      </button>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div v-if="adminStore.loading && adminStore.designations.length === 0" class="p-12 flex justify-center">
        <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50 text-gray-700 border-b border-gray-100 uppercase tracking-wider text-xs font-black">
            <tr>
              <th class="px-6 py-4">ID</th>
              <th class="px-6 py-4">Code</th>
              <th class="px-6 py-4">Designation Name</th>
              <th class="px-6 py-4">Department Name</th>
              <th class="px-6 py-4">Reporting To</th>
              <th class="px-6 py-4 text-center">Employees</th>
              <th class="px-6 py-4">Salary Range</th>
              <th class="px-6 py-4">Created Date</th>
              <th class="px-6 py-4">Status</th>
              <th class="px-6 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="desig in adminStore.designations" :key="desig.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 font-bold text-gray-900">#{{ desig.id }}</td>
              <td class="px-6 py-4 font-mono font-medium text-gray-500">{{ desig.code || '-' }}</td>
              <td class="px-6 py-4 font-bold text-gray-900">{{ desig.name }}</td>
              <td class="px-6 py-4 font-medium text-gray-700">
                <span v-if="desig.department" class="flex items-center gap-1.5">
                  <Building2 class="w-3.5 h-3.5 text-blue-500" />
                  {{ desig.department.name }}
                </span>
                <span v-else class="text-gray-400 italic">Not assigned</span>
              </td>
              <td class="px-6 py-4 font-medium text-gray-700">
                <span v-if="desig.parent" class="flex items-center gap-1.5">
                  <Briefcase class="w-3.5 h-3.5 text-emerald-500" />
                  {{ desig.parent.name }}
                </span>
                <span v-else class="text-gray-400 italic">-</span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center bg-blue-50 text-blue-700 px-2.5 py-0.5 rounded-full font-black text-xs border border-blue-100">
                  {{ desig.employees_count || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 font-mono text-gray-500 text-xs">
                <span v-if="desig.min_salary || desig.max_salary">
                  ₹{{ formatCurrency(desig.min_salary) || 0 }} - ₹{{ formatCurrency(desig.max_salary) || 'Any' }}
                </span>
                <span v-else class="italic">-</span>
              </td>
              <td class="px-6 py-4 text-gray-500">{{ formatDate(desig.created_at) }}</td>
              <td class="px-6 py-4">
                <span v-if="desig.is_active" class="inline-flex items-center gap-1 text-emerald-600 bg-emerald-50 px-2 py-1 rounded-md text-xs font-bold border border-emerald-100">
                  <CheckCircle2 class="w-3.5 h-3.5" /> Active
                </span>
                <span v-else class="inline-flex items-center gap-1 text-gray-500 bg-gray-100 px-2 py-1 rounded-md text-xs font-bold border border-gray-200">
                  <XCircle class="w-3.5 h-3.5" /> Inactive
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                  <button @click="openEditModal(desig)" class="p-1.5 text-gray-400 hover:text-blue-600 transition-colors rounded-lg hover:bg-blue-50" title="Edit">
                    <Edit2 class="w-4 h-4" />
                  </button>
                  <button @click="handleDelete(desig.id)" class="p-1.5 text-gray-400 hover:text-red-600 transition-colors rounded-lg hover:bg-red-50" title="Delete">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="adminStore.designations.length === 0">
              <td colspan="10" class="px-6 py-12 text-center">
                <Briefcase class="w-12 h-12 text-gray-200 mx-auto mb-3" />
                <p class="text-gray-500 font-medium">No designations found.</p>
                <p class="text-sm text-gray-400 mt-1">Create one to start building your hierarchy.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4 overflow-y-auto">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200 my-8">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 sticky top-0 z-10">
          <h3 class="font-bold text-gray-900 flex items-center gap-2">
            <Briefcase class="w-5 h-5 text-blue-600" />
            {{ isEditing ? 'Edit Designation' : 'Add New Designation' }}
          </h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-lg transition-colors">
            <XCircle class="w-5 h-5" />
          </button>
        </div>
        
        <form @submit.prevent="handleSubmit" class="p-6 space-y-5">
          <div class="grid grid-cols-2 gap-5">
            
            <div class="col-span-2 sm:col-span-1">
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Designation Code</label>
              <div class="relative">
                <Hash class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input v-model="form.code" type="text" class="block w-full pl-9 pr-3 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all font-medium" placeholder="e.g. SDE-1" />
              </div>
            </div>
            
            <div class="col-span-2 sm:col-span-1">
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Designation Title <span class="text-red-500">*</span></label>
              <input v-model="form.name" type="text" required class="block w-full px-3 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all font-medium" placeholder="e.g. Senior Developer" />
            </div>

            <div class="col-span-2 sm:col-span-1">
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Department</label>
              <div class="relative">
                <Building2 class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <select v-model="form.department_id" class="block w-full pl-9 pr-3 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all font-medium appearance-none">
                  <option :value="null">-- Select Department --</option>
                  <option v-for="dept in adminStore.departments" :key="dept.id" :value="dept.id">
                    {{ dept.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="col-span-2 sm:col-span-1">
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Reporting To (Parent Role)</label>
              <div class="relative">
                <Users class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <select v-model="form.parent_id" class="block w-full pl-9 pr-3 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all font-medium appearance-none">
                  <option :value="null">-- Top Level (No Manager) --</option>
                  <option v-for="desig in adminStore.designations" :key="desig.id" :value="desig.id" :disabled="desig.id === editId">
                    {{ desig.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="col-span-2 sm:col-span-1">
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Min Salary</label>
              <div class="relative">
                <DollarSign class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input v-model="form.min_salary" type="number" step="0.01" class="block w-full pl-9 pr-3 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all font-medium" placeholder="0.00" />
              </div>
            </div>

            <div class="col-span-2 sm:col-span-1">
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Max Salary</label>
              <div class="relative">
                <DollarSign class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input v-model="form.max_salary" type="number" step="0.01" class="block w-full pl-9 pr-3 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all font-medium" placeholder="0.00" />
              </div>
            </div>

            <div class="col-span-2">
              <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl border border-gray-100">
                <input type="checkbox" id="isActiveDesig" v-model="form.is_active" class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500" />
                <label for="isActiveDesig" class="text-sm font-bold text-gray-700 cursor-pointer">
                  Designation is Active
                  <span class="block text-xs font-normal text-gray-500 mt-0.5">Inactive designations cannot be assigned to new employees.</span>
                </label>
              </div>
            </div>

          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
            <button type="button" @click="showModal = false" class="px-5 py-2.5 text-gray-600 hover:bg-gray-100 rounded-xl text-sm font-bold transition-colors">Cancel</button>
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 text-sm font-bold transition-colors shadow-sm disabled:opacity-50">
              {{ isEditing ? 'Save Changes' : 'Create Designation' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
