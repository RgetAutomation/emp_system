<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-800">Leave Structures</h2>
      <button @click="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2">
        <span>+ Create Structure</span>
      </button>
    </div>

    <!-- Error/Success Alerts -->
    <div v-if="error" class="bg-red-50 text-red-600 p-4 rounded-lg flex items-start gap-3">
      <p>{{ error }}</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Data Table -->
    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100">
              <th class="p-4 font-semibold text-gray-600">Name</th>
              <th class="p-4 font-semibold text-gray-600">Description</th>
              <th class="p-4 font-semibold text-gray-600">Allowances</th>
              <th class="p-4 font-semibold text-gray-600 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in structures" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
              <td class="p-4 font-medium text-gray-800">{{ item.name }}</td>
              <td class="p-4 text-gray-600 text-sm">{{ item.description || '-' }}</td>
              <td class="p-4 text-sm">
                <div class="flex flex-wrap gap-2">
                  <span v-for="(days, type) in item.allowances" :key="type" class="bg-blue-50 text-blue-700 px-2 py-1 rounded text-xs font-medium capitalize">
                    {{ type }}: {{ days }}
                  </span>
                </div>
              </td>
              <td class="p-4 text-right">
                <button @click="openModal(item)" class="text-blue-600 hover:text-blue-800 font-medium text-sm mr-3">Edit</button>
                <button @click="deleteStructure(item.id)" class="text-red-600 hover:text-red-800 font-medium text-sm">Delete</button>
              </td>
            </tr>
            <tr v-if="structures.length === 0">
              <td colspan="4" class="p-8 text-center text-gray-500">No leave structures found. Create one to get started.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="flex justify-between items-center p-6 border-b border-gray-100">
          <h3 class="text-xl font-bold text-gray-800">{{ isEditing ? 'Edit Structure' : 'New Structure' }}</h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
        </div>
        
        <form @submit.prevent="saveStructure" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input v-model="form.name" type="text" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all" placeholder="e.g. Full Time Employees">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="form.description" rows="2" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all"></textarea>
          </div>

          <div class="space-y-3">
            <h4 class="font-medium text-gray-700">Allowances (Days per year)</h4>
            
            <div class="grid grid-cols-2 gap-4">
              <div v-for="type in leaveTypes" :key="type">
                <label class="block text-xs font-medium text-gray-500 mb-1 capitalize">{{ type }}</label>
                <input v-model.number="form.allowances[type]" type="number" min="0" class="w-full px-3 py-1.5 rounded border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all">
              </div>
            </div>
          </div>

          <div class="pt-4 flex justify-end gap-3">
            <button type="button" @click="closeModal" class="px-5 py-2 rounded-lg font-medium text-gray-600 hover:bg-gray-100 transition-colors">Cancel</button>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-medium transition-colors" :disabled="saving">
              {{ saving ? 'Saving...' : 'Save Structure' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const structures = ref([]);
const loading = ref(true);
const error = ref(null);

const isModalOpen = ref(false);
const isEditing = ref(false);
const saving = ref(false);
const currentId = ref(null);

const leaveTypes = ['sick', 'casual', 'annual', 'earned', 'maternity', 'paternity'];

const form = ref({
  name: '',
  description: '',
  allowances: {}
});

const getAuthHeaders = () => {
  const token = localStorage.getItem('token');
  return {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  };
};

const fetchStructures = async () => {
  loading.value = true;
  try {
    const res = await fetch('http://localhost:8000/api/leave-structures', {
      headers: getAuthHeaders()
    });
    if (!res.ok) throw new Error('Failed to fetch leave structures');
    structures.value = await res.json();
  } catch (err) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
};

const openModal = (item = null) => {
  if (item) {
    isEditing.value = true;
    currentId.value = item.id;
    // Deep copy allowances to avoid reactive changes before save
    const allowances = {};
    leaveTypes.forEach(t => allowances[t] = item.allowances[t] || 0);
    
    form.value = {
      name: item.name,
      description: item.description,
      allowances: allowances
    };
  } else {
    isEditing.value = false;
    currentId.value = null;
    const allowances = {};
    leaveTypes.forEach(t => allowances[t] = 0);
    
    form.value = {
      name: '',
      description: '',
      allowances: allowances
    };
  }
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const saveStructure = async () => {
  saving.value = true;
  error.value = null;
  
  // Clean up allowances (remove 0s if desired, or keep them)
  const data = {
    name: form.value.name,
    description: form.value.description,
    allowances: {}
  };
  
  for (const [key, val] of Object.entries(form.value.allowances)) {
    if (val > 0) {
      data.allowances[key] = val;
    }
  }
  
  const url = isEditing.value 
    ? `http://localhost:8000/api/leave-structures/${currentId.value}`
    : 'http://localhost:8000/api/leave-structures';
    
  const method = isEditing.value ? 'PUT' : 'POST';

  try {
    const res = await fetch(url, {
      method,
      headers: getAuthHeaders(),
      body: JSON.stringify(data)
    });
    
    if (!res.ok) {
      const e = await res.json();
      throw new Error(e.message || 'Failed to save');
    }
    
    await fetchStructures();
    closeModal();
  } catch (err) {
    alert(err.message);
  } finally {
    saving.value = false;
  }
};

const deleteStructure = async (id) => {
  if (!confirm('Are you sure you want to delete this leave structure?')) return;
  
  try {
    const res = await fetch(`http://localhost:8000/api/leave-structures/${id}`, {
      method: 'DELETE',
      headers: getAuthHeaders()
    });
    
    if (!res.ok) {
      const e = await res.json();
      throw new Error(e.message || 'Failed to delete');
    }
    
    await fetchStructures();
  } catch (err) {
    alert(err.message);
  }
};

onMounted(() => {
  fetchStructures();
});
</script>
