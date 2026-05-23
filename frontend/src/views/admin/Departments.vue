<script setup>
import { onMounted, ref, computed } from 'vue';
import { useAdminStore } from '../../stores/admin';
import { Building2, Plus, Trash2, Edit2, Users, MapPin, Hash, CheckCircle2, XCircle } from 'lucide-vue-next';

const adminStore = useAdminStore();
const showModal = ref(false);
const form = ref({ 
  name: '',
  code: '',
  manager_id: null,
  location: '',
  is_active: true
});
const isEditing = ref(false);
const editId = ref(null);

onMounted(async () => {
  await adminStore.fetchDepartments();
  await adminStore.fetchEmployees(); // fetch employees to populate manager list
});

const openCreateModal = () => {
  isEditing.value = false;
  form.value = {
    name: '',
    code: '',
    manager_id: null,
    location: '',
    is_active: true
  };
  showModal.value = true;
};

const openEditModal = (dept) => {
  isEditing.value = true;
  editId.value = dept.id;
  form.value = {
    name: dept.name,
    code: dept.code || '',
    manager_id: dept.manager_id || null,
    location: dept.location || '',
    is_active: dept.is_active === undefined ? true : !!dept.is_active
  };
  showModal.value = true;
};

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await adminStore.updateDepartment(editId.value, form.value);
    } else {
      await adminStore.createDepartment(form.value);
    }
    showModal.value = false;
  } catch (error) {
    console.error(error);
  }
};

const handleDelete = async (id) => {
  if (confirm('Are you sure you want to delete this department?')) {
    await adminStore.deleteDepartment(id);
  }
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
          <Building2 class="w-6 h-6 text-blue-600" />
          Departments
        </h1>
        <p class="text-gray-500 mt-1 text-sm font-medium">Manage corporate structure, locations, and heads.</p>
      </div>
      <button @click="openCreateModal" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors shadow-sm font-bold text-sm">
        <Plus class="w-4 h-4" />
        Add Department
      </button>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div v-if="adminStore.loading && adminStore.departments.length === 0" class="p-12 flex justify-center">
        <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50 text-gray-700 border-b border-gray-100 uppercase tracking-wider text-xs font-black">
            <tr>
              <th class="px-6 py-4">ID</th>
              <th class="px-6 py-4">Code</th>
              <th class="px-6 py-4">Department Name</th>
              <th class="px-6 py-4">Head / Manager</th>
              <th class="px-6 py-4 text-center">Employees</th>
              <th class="px-6 py-4">Location</th>
              <th class="px-6 py-4">Status</th>
              <th class="px-6 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="dept in adminStore.departments" :key="dept.id" class="hover:bg-gray-50 transition-colors group">
              <td class="px-6 py-4 font-bold text-gray-900">#{{ dept.id }}</td>
              <td class="px-6 py-4 font-mono font-medium text-gray-500">{{ dept.code || '-' }}</td>
              <td class="px-6 py-4 font-bold text-gray-900">{{ dept.name }}</td>
              <td class="px-6 py-4 font-medium">
                <div class="flex items-center gap-2" v-if="dept.manager">
                  <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-[10px] font-bold">
                    {{ dept.manager.name.charAt(0) }}
                  </div>
                  {{ dept.manager.name }}
                </div>
                <span v-else class="text-gray-400 italic">Not assigned</span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center bg-blue-50 text-blue-700 px-2.5 py-0.5 rounded-full font-black text-xs border border-blue-100">
                  {{ dept.employees_count || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-gray-500 flex items-center gap-1.5">
                <MapPin class="w-3.5 h-3.5" v-if="dept.location" />
                {{ dept.location || '-' }}
              </td>
              <td class="px-6 py-4">
                <span v-if="dept.is_active" class="inline-flex items-center gap-1 text-emerald-600 bg-emerald-50 px-2 py-1 rounded-md text-xs font-bold border border-emerald-100">
                  <CheckCircle2 class="w-3.5 h-3.5" /> Active
                </span>
                <span v-else class="inline-flex items-center gap-1 text-gray-500 bg-gray-100 px-2 py-1 rounded-md text-xs font-bold border border-gray-200">
                  <XCircle class="w-3.5 h-3.5" /> Inactive
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                  <button @click="openEditModal(dept)" class="p-1.5 text-gray-400 hover:text-blue-600 transition-colors rounded-lg hover:bg-blue-50" title="Edit">
                    <Edit2 class="w-4 h-4" />
                  </button>
                  <button @click="handleDelete(dept.id)" class="p-1.5 text-gray-400 hover:text-red-600 transition-colors rounded-lg hover:bg-red-50" title="Delete">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="adminStore.departments.length === 0">
              <td colspan="8" class="px-6 py-12 text-center">
                <Building2 class="w-12 h-12 text-gray-200 mx-auto mb-3" />
                <p class="text-gray-500 font-medium">No departments found.</p>
                <p class="text-sm text-gray-400 mt-1">Create one to start organizing your company.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
          <h3 class="font-bold text-gray-900 flex items-center gap-2">
            <Building2 class="w-5 h-5 text-blue-600" />
            {{ isEditing ? 'Edit Department' : 'Add New Department' }}
          </h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-lg transition-colors">
            <XCircle class="w-5 h-5" />
          </button>
        </div>
        <form @submit.prevent="handleSubmit" class="p-6 space-y-5">
          
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2 sm:col-span-1">
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Department Code</label>
              <div class="relative">
                <Hash class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input v-model="form.code" type="text" class="block w-full pl-9 pr-3 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all font-medium" placeholder="e.g. ENG" />
              </div>
            </div>
            <div class="col-span-2 sm:col-span-1">
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Department Name <span class="text-red-500">*</span></label>
              <input v-model="form.name" type="text" required class="block w-full px-3 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all font-medium" placeholder="e.g. Engineering" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Department Head / Manager</label>
            <div class="relative">
              <Users class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <select v-model="form.manager_id" class="block w-full pl-9 pr-3 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all font-medium appearance-none">
                <option :value="null">-- Select Manager --</option>
                <option v-for="emp in adminStore.employees" :key="emp.id" :value="emp.user?.id">
                  {{ emp.user?.name }} ({{ emp.employee_id }})
                </option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Branch / Location</label>
            <div class="relative">
              <MapPin class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <input v-model="form.location" type="text" class="block w-full pl-9 pr-3 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all font-medium" placeholder="e.g. NY Office, Floor 3" />
            </div>
          </div>

          <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl border border-gray-100">
            <input type="checkbox" id="isActive" v-model="form.is_active" class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500" />
            <label for="isActive" class="text-sm font-bold text-gray-700 cursor-pointer">
              Department is Active
              <span class="block text-xs font-normal text-gray-500 mt-0.5">Inactive departments won't appear in employee assignment dropdowns.</span>
            </label>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
            <button type="button" @click="showModal = false" class="px-5 py-2.5 text-gray-600 hover:bg-gray-100 rounded-xl text-sm font-bold transition-colors">Cancel</button>
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 text-sm font-bold transition-colors shadow-sm disabled:opacity-50">
              {{ isEditing ? 'Save Changes' : 'Create Department' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
