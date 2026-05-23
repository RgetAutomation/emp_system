<template>
  <div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
          <Receipt class="w-6 h-6 text-blue-600" />
          My Expenses
        </h2>
        <p class="text-gray-500 text-sm mt-1">Submit and track your expense reimbursements.</p>
      </div>
      <button @click="showClaimModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center gap-2">
        <Plus class="w-4 h-4" />
        Claim Expense
      </button>
    </div>

    <!-- Error Alert -->
    <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl flex items-center gap-2">
      <AlertCircle class="w-5 h-5" />
      {{ error }}
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
        <p class="text-xs font-bold text-gray-500 uppercase">Total Pending</p>
        <p class="text-2xl font-black text-amber-600 mt-1">₹{{ stats.pending }}</p>
      </div>
      <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
        <p class="text-xs font-bold text-gray-500 uppercase">Total Approved</p>
        <p class="text-2xl font-black text-blue-600 mt-1">₹{{ stats.approved }}</p>
      </div>
      <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
        <p class="text-xs font-bold text-gray-500 uppercase">Total Paid</p>
        <p class="text-2xl font-black text-green-600 mt-1">₹{{ stats.paid }}</p>
      </div>
      <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
        <p class="text-xs font-bold text-gray-500 uppercase">Total Rejected</p>
        <p class="text-2xl font-black text-red-600 mt-1">₹{{ stats.rejected }}</p>
      </div>
    </div>

    <!-- Expense List -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100">
              <th class="p-4 font-bold text-xs text-gray-500 uppercase tracking-wider">Date & Category</th>
              <th class="p-4 font-bold text-xs text-gray-500 uppercase tracking-wider">Details</th>
              <th class="p-4 font-bold text-xs text-gray-500 uppercase tracking-wider text-right">Amount</th>
              <th class="p-4 font-bold text-xs text-gray-500 uppercase tracking-wider text-center">Status</th>
              <th class="p-4 font-bold text-xs text-gray-500 uppercase tracking-wider text-center">Receipt</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="expense in expenses" :key="expense.id" class="hover:bg-gray-50/50 transition-colors">
              <td class="p-4">
                <div class="font-bold text-gray-800 text-sm">{{ formatDate(expense.date_incurred) }}</div>
                <div class="text-xs font-semibold text-blue-600 bg-blue-50 inline-block px-2 py-0.5 rounded-full mt-1 border border-blue-100">{{ expense.category }}</div>
              </td>
              <td class="p-4 max-w-xs">
                <div class="font-bold text-gray-800 text-sm truncate">{{ expense.title }}</div>
                <div v-if="expense.description" class="text-xs text-gray-500 mt-0.5 truncate">{{ expense.description }}</div>
                <div v-if="expense.admin_note" class="text-xs text-red-600 mt-1 font-medium"><span class="font-bold">Note:</span> {{ expense.admin_note }}</div>
              </td>
              <td class="p-4 font-black text-gray-900 text-right">
                ₹{{ expense.amount }}
              </td>
              <td class="p-4 text-center">
                <span v-if="expense.status === 'pending'" class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 px-2.5 py-1 rounded-lg text-xs font-bold border border-amber-200">
                  <Clock class="w-3.5 h-3.5" /> Pending
                </span>
                <span v-else-if="expense.status === 'approved'" class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg text-xs font-bold border border-blue-200">
                  <CheckCircle2 class="w-3.5 h-3.5" /> Approved
                </span>
                <span v-else-if="expense.status === 'paid'" class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 px-2.5 py-1 rounded-lg text-xs font-bold border border-green-200">
                  <Banknote class="w-3.5 h-3.5" /> Paid
                </span>
                <span v-else-if="expense.status === 'rejected'" class="inline-flex items-center gap-1.5 bg-red-50 text-red-700 px-2.5 py-1 rounded-lg text-xs font-bold border border-red-200">
                  <XCircle class="w-3.5 h-3.5" /> Rejected
                </span>
              </td>
              <td class="p-4 text-center">
                <a v-if="expense.receipt_path" :href="getReceiptUrl(expense.receipt_path)" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 hover:text-gray-900 transition-colors" title="View Receipt">
                  <Paperclip class="w-4 h-4" />
                </a>
                <span v-else class="text-gray-300 text-xs">-</span>
              </td>
            </tr>
            <tr v-if="expenses.length === 0">
              <td colspan="5" class="p-8 text-center">
                <div class="flex flex-col items-center justify-center text-gray-400">
                  <Receipt class="w-12 h-12 mb-3 opacity-20" />
                  <p class="font-semibold text-gray-500">No expenses found</p>
                  <p class="text-sm">You haven't submitted any expense claims yet.</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Claim Modal -->
    <div v-if="showClaimModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
          <h3 class="font-bold text-lg text-gray-900 flex items-center gap-2">
            <Plus class="w-5 h-5 text-blue-600" />
            Claim New Expense
          </h3>
          <button @click="showClaimModal = false" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-1.5 transition-colors">
            <X class="w-4 h-4" />
          </button>
        </div>

        <form @submit.prevent="submitExpense" class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="block text-sm font-bold text-gray-700 mb-1.5">Expense Title *</label>
              <input v-model="form.title" type="text" required placeholder="e.g., Client Meeting Dinner" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1.5">Amount (₹) *</label>
              <input v-model.number="form.amount" type="number" min="0.01" step="0.01" required class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-bold text-gray-900">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1.5">Date Incurred *</label>
              <input v-model="form.date_incurred" type="date" required :max="todayStr" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="col-span-2">
              <label class="block text-sm font-bold text-gray-700 mb-1.5">Category *</label>
              <select v-model="form.category" required class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <option value="Travel">Travel & Transportation</option>
                <option value="Meals">Meals & Entertainment</option>
                <option value="Office Supplies">Office Supplies</option>
                <option value="Internet/Phone">Internet & Phone Bill</option>
                <option value="Training/Courses">Training & Courses</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="col-span-2">
              <label class="block text-sm font-bold text-gray-700 mb-1.5">Description</label>
              <textarea v-model="form.description" rows="2" placeholder="Provide any additional details..." class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
            </div>

            <div class="col-span-2">
              <label class="block text-sm font-bold text-gray-700 mb-1.5">Receipt Attachment (Optional)</label>
              <input type="file" @change="handleFileUpload" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-200 rounded-lg bg-gray-50 cursor-pointer">
              <p class="text-xs text-gray-400 mt-1.5">Accepted formats: JPG, PNG, PDF. Max size: 5MB.</p>
            </div>
          </div>

          <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-6">
            <button type="button" @click="showClaimModal = false" class="px-5 py-2.5 border border-gray-200 rounded-lg text-gray-700 font-bold hover:bg-gray-50 transition-colors">
              Cancel
            </button>
            <button type="submit" :disabled="submitting" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold transition-colors disabled:opacity-50 flex items-center gap-2">
              <span v-if="submitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ submitting ? 'Submitting...' : 'Submit Claim' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { Receipt, Plus, X, AlertCircle, Clock, CheckCircle2, Banknote, XCircle, Paperclip } from 'lucide-vue-next';

const expenses = ref([]);
const loading = ref(false);
const submitting = ref(false);
const error = ref(null);
const showClaimModal = ref(false);

const todayStr = new Date().toISOString().split('T')[0];

const form = ref({
  title: '',
  amount: '',
  date_incurred: todayStr,
  category: 'Travel',
  description: '',
  receipt: null
});

const stats = computed(() => {
  const s = { pending: 0, approved: 0, paid: 0, rejected: 0 };
  expenses.value.forEach(e => {
    if (s[e.status] !== undefined) {
      s[e.status] += parseFloat(e.amount);
    }
  });
  // Format to 2 decimals
  Object.keys(s).forEach(k => s[k] = s[k].toFixed(2));
  return s;
});

const getAuthHeaders = () => {
  const token = localStorage.getItem('token');
  return {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  };
};

const fetchExpenses = async () => {
  loading.value = true;
  error.value = null;
  try {
    const res = await fetch('http://localhost:8000/api/expenses', { headers: getAuthHeaders() });
    if (!res.ok) throw new Error('Failed to fetch expenses');
    expenses.value = await res.json();
  } catch (err) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
};

const handleFileUpload = (event) => {
  form.value.receipt = event.target.files[0];
};

const submitExpense = async () => {
  submitting.value = true;
  error.value = null;

  const formData = new FormData();
  formData.append('title', form.value.title);
  formData.append('amount', form.value.amount);
  formData.append('date_incurred', form.value.date_incurred);
  formData.append('category', form.value.category);
  if (form.value.description) formData.append('description', form.value.description);
  if (form.value.receipt) formData.append('receipt', form.value.receipt);

  try {
    const res = await fetch('http://localhost:8000/api/expenses', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      },
      body: formData
    });

    if (!res.ok) {
      const e = await res.json();
      throw new Error(e.message || 'Failed to submit expense');
    }

    await fetchExpenses();
    showClaimModal.value = false;
    
    // Reset form
    form.value = {
      title: '', amount: '', date_incurred: todayStr, category: 'Travel', description: '', receipt: null
    };
  } catch (err) {
    alert(err.message);
  } finally {
    submitting.value = false;
  }
};

const formatDate = (d) => new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });

const getReceiptUrl = (path) => `http://localhost:8000/api/file/${path}`;

onMounted(() => {
  fetchExpenses();
});
</script>
