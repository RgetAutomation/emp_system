<script setup>
import { onMounted, ref } from 'vue';
import { useAdminStore } from '../../stores/admin';
import { Briefcase, Plus, Trash2, Edit2 } from 'lucide-vue-next';

const adminStore = useAdminStore();
const showModal = ref(false);
const form = ref({ name: '' });
const isEditing = ref(false);
const editId = ref(null);

onMounted(() => {
  adminStore.fetchDesignations();
});

const openCreateModal = () => {
  isEditing.value = false;
  form.value.name = '';
  showModal.value = true;
};

const openEditModal = (desig) => {
  isEditing.value = true;
  editId.value = desig.id;
  form.value.name = desig.name;
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
</script>

<template>
  <div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
          <Briefcase class="w-6 h-6 text-blue-600" />
          Designations
        </h1>
        <p class="text-gray-500 mt-1 text-sm">Manage job titles in your company</p>
      </div>
      <button @click="openCreateModal" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
        <Plus class="w-4 h-4" />
        Add Designation
      </button>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
      <div v-if="adminStore.loading" class="p-8 text-center text-gray-500">
        Loading designations...
      </div>
      
      <table v-else class="w-full text-left text-sm text-gray-600">
        <thead class="bg-gray-50 text-gray-700 border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 font-semibold">ID</th>
            <th class="px-6 py-4 font-semibold">Designation Title</th>
            <th class="px-6 py-4 font-semibold text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="desig in adminStore.designations" :key="desig.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 font-medium text-gray-900">#{{ desig.id }}</td>
            <td class="px-6 py-4">{{ desig.name }}</td>
            <td class="px-6 py-4 text-right flex justify-end gap-2">
              <button @click="openEditModal(desig)" class="p-1.5 text-gray-400 hover:text-blue-600 transition-colors rounded-lg hover:bg-blue-50">
                <Edit2 class="w-4 h-4" />
              </button>
              <button @click="handleDelete(desig.id)" class="p-1.5 text-gray-400 hover:text-red-600 transition-colors rounded-lg hover:bg-red-50">
                <Trash2 class="w-4 h-4" />
              </button>
            </td>
          </tr>
          <tr v-if="adminStore.designations.length === 0">
            <td colspan="3" class="px-6 py-8 text-center text-gray-500">No designations found. Create one to get started.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
          <h3 class="font-semibold text-gray-900">{{ isEditing ? 'Edit' : 'Add' }} Designation</h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form @submit.prevent="handleSubmit" class="p-6">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Designation Title</label>
            <input v-model="form.name" type="text" required class="block w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-gray-900 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all" placeholder="e.g. Senior Developer" />
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="showModal = false" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg text-sm font-medium transition-colors">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition-colors">Save Designation</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
