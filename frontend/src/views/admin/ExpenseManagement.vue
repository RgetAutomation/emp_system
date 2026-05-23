<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
        <Banknote class="w-6 h-6 text-blue-600" />
        Expense Management & Payouts
      </h2>
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

    <div v-if="error" class="bg-red-50 text-red-600 p-4 rounded-lg flex items-center gap-2">
      <AlertCircle class="w-5 h-5" /> {{ error }}
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100">
              <th class="p-4 font-bold text-xs text-gray-500 uppercase tracking-wider">Employee</th>
              <th class="p-4 font-bold text-xs text-gray-500 uppercase tracking-wider">Date & Category</th>
              <th class="p-4 font-bold text-xs text-gray-500 uppercase tracking-wider">Details</th>
              <th class="p-4 font-bold text-xs text-gray-500 uppercase tracking-wider text-right">Amount</th>
              <th class="p-4 font-bold text-xs text-gray-500 uppercase tracking-wider text-center">Status</th>
              <th class="p-4 font-bold text-xs text-gray-500 uppercase tracking-wider text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="expense in expenses" :key="expense.id" class="hover:bg-gray-50/50 transition-colors">
              <td class="p-4">
                <div class="font-bold text-gray-800 text-sm">{{ expense.employee?.user?.name }}</div>
                <div class="text-xs text-gray-500">{{ expense.employee?.employee_id }}</div>
              </td>
              <td class="p-4">
                <div class="font-bold text-gray-800 text-sm">{{ formatDate(expense.date_incurred) }}</div>
                <div class="text-xs font-semibold text-blue-600 bg-blue-50 inline-block px-2 py-0.5 rounded-full mt-1 border border-blue-100">{{ expense.category }}</div>
              </td>
              <td class="p-4 max-w-xs">
                <div class="font-bold text-gray-800 text-sm truncate">{{ expense.title }}</div>
                <div v-if="expense.description" class="text-xs text-gray-500 mt-0.5 truncate" :title="expense.description">{{ expense.description }}</div>
              </td>
              <td class="p-4 font-black text-gray-900 text-right">
                ₹{{ expense.amount }}
              </td>
              <td class="p-4 text-center">
                <span v-if="expense.status === 'pending'" class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 px-2.5 py-1 rounded-lg text-xs font-bold border border-amber-200">
                  Pending
                </span>
                <span v-else-if="expense.status === 'approved'" class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg text-xs font-bold border border-blue-200">
                  Approved
                </span>
                <span v-else-if="expense.status === 'paid'" class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 px-2.5 py-1 rounded-lg text-xs font-bold border border-green-200">
                  Paid
                </span>
                <span v-else-if="expense.status === 'rejected'" class="inline-flex items-center gap-1.5 bg-red-50 text-red-700 px-2.5 py-1 rounded-lg text-xs font-bold border border-red-200">
                  Rejected
                </span>
              </td>
              <td class="p-4 text-right space-x-2">
                <button @click="viewExpense(expense)" class="text-gray-600 hover:text-blue-600 text-sm font-semibold transition-colors">
                  Review
                </button>
                <button v-if="expense.status === 'approved'" @click="markAsPaid(expense.id)" class="bg-green-100 hover:bg-green-200 text-green-800 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors">
                  Process Payout
                </button>
              </td>
            </tr>
            <tr v-if="expenses.length === 0">
              <td colspan="6" class="p-8 text-center text-gray-500">No expenses found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Review Modal -->
    <div v-if="selectedExpense" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
          <h3 class="font-bold text-lg text-gray-900">Review Expense Claim</h3>
          <button @click="selectedExpense = null" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-1.5 transition-colors">
            <X class="w-4 h-4" />
          </button>
        </div>

        <div class="p-6 space-y-5">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-bold text-gray-500 uppercase">{{ selectedExpense.category }}</p>
              <h4 class="text-xl font-bold text-gray-900">{{ selectedExpense.title }}</h4>
              <p class="text-sm text-gray-500 mt-1">Claimed by {{ selectedExpense.employee?.user?.name }} on {{ formatDate(selectedExpense.date_incurred) }}</p>
            </div>
            <div class="text-right">
              <p class="text-xs font-bold text-gray-500 uppercase">Amount</p>
              <p class="text-2xl font-black text-gray-900">₹{{ selectedExpense.amount }}</p>
            </div>
          </div>

          <div v-if="selectedExpense.description" class="bg-gray-50 rounded-lg p-3 text-sm text-gray-700">
            {{ selectedExpense.description }}
          </div>

          <div v-if="selectedExpense.receipt_path">
            <a :href="getReceiptUrl(selectedExpense.receipt_path)" target="_blank" class="flex items-center gap-2 text-blue-600 font-semibold text-sm hover:underline p-3 bg-blue-50 rounded-lg border border-blue-100">
              <Paperclip class="w-4 h-4" /> View Attached Receipt
            </a>
          </div>
          <div v-else class="text-sm text-amber-600 bg-amber-50 p-3 rounded-lg border border-amber-100 font-medium">
            No receipt was attached to this claim.
          </div>

          <!-- Admin Action Form (Only if Pending) -->
          <div v-if="selectedExpense.status === 'pending'" class="pt-4 border-t border-gray-100 space-y-3">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1.5">Add Note (Optional)</label>
              <textarea v-model="adminNote" rows="2" placeholder="Reason for rejection or approval note..." class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
            </div>
            <div class="flex gap-3">
              <button @click="updateStatus(selectedExpense.id, 'rejected')" class="flex-1 px-4 py-2 bg-red-100 hover:bg-red-200 text-red-800 font-bold rounded-lg transition-colors">
                Reject
              </button>
              <button @click="updateStatus(selectedExpense.id, 'approved')" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition-colors">
                Approve Claim
              </button>
            </div>
          </div>

          <!-- Readonly Note (If not pending) -->
          <div v-else-if="selectedExpense.admin_note" class="pt-4 border-t border-gray-100">
            <p class="text-sm font-bold text-gray-700 mb-1">Admin Note:</p>
            <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">{{ selectedExpense.admin_note }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { Banknote, AlertCircle, Clock, CheckCircle2, XCircle, X, Paperclip } from 'lucide-vue-next';

const expenses = ref([]);
const loading = ref(false);
const error = ref(null);
const selectedExpense = ref(null);
const adminNote = ref('');

const stats = computed(() => {
  const s = { pending: 0, approved: 0, paid: 0, rejected: 0 };
  expenses.value.forEach(e => {
    if (s[e.status] !== undefined) {
      s[e.status] += parseFloat(e.amount);
    }
  });
  Object.keys(s).forEach(k => s[k] = s[k].toFixed(2));
  return s;
});

const getAuthHeaders = () => {
  const token = localStorage.getItem('token');
  return {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
    'Content-Type': 'application/json'
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

const viewExpense = (expense) => {
  selectedExpense.value = expense;
  adminNote.value = expense.admin_note || '';
};

const updateStatus = async (id, status) => {
  try {
    const res = await fetch(`http://localhost:8000/api/expenses/${id}/status`, {
      method: 'PATCH',
      headers: getAuthHeaders(),
      body: JSON.stringify({ status, admin_note: adminNote.value })
    });

    if (!res.ok) {
      const e = await res.json();
      throw new Error(e.message || 'Failed to update status');
    }

    await fetchExpenses();
    selectedExpense.value = null;
  } catch (err) {
    alert(err.message);
  }
};

const markAsPaid = async (id) => {
  if (!confirm('Process this payout? This will mark the expense as Paid.')) return;
  try {
    const res = await fetch(`http://localhost:8000/api/expenses/${id}/pay`, {
      method: 'POST',
      headers: getAuthHeaders()
    });

    if (!res.ok) throw new Error('Failed to process payout');
    await fetchExpenses();
  } catch (err) {
    alert(err.message);
  }
};

const formatDate = (d) => new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
const getReceiptUrl = (path) => `http://localhost:8000/api/file/${path}`;

onMounted(() => {
  fetchExpenses();
});
</script>
