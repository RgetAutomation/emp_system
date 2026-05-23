<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-800">Tax Declarations (Form 12BB)</h2>
    </div>

    <!-- Info Box -->
    <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl p-4 text-sm font-medium">
      Submit your investment details for the current financial year to calculate accurate TDS deductions. Selecting the Old Tax Regime allows exemptions; the New Tax Regime offers lower rates but no exemptions except standard deduction.
    </div>

    <div v-if="error" class="bg-red-50 text-red-600 p-4 rounded-xl">
      {{ error }}
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <form @submit.prevent="submitDeclaration" class="space-y-6">
        
        <div class="grid grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Financial Year</label>
            <select v-model="form.financial_year" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="2024-2025">2024-2025</option>
              <option value="2025-2026">2025-2026</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Tax Regime</label>
            <div class="flex gap-4 items-center h-10">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="form.regime" value="new" class="w-4 h-4 text-blue-600">
                <span class="text-sm font-medium text-gray-700">New Regime (Default)</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="form.regime" value="old" class="w-4 h-4 text-blue-600">
                <span class="text-sm font-medium text-gray-700">Old Regime</span>
              </label>
            </div>
          </div>
        </div>

        <div v-if="form.regime === 'old'" class="space-y-6 pt-4 border-t border-gray-100">
          <h3 class="font-bold text-lg text-gray-800">Deductions & Exemptions (Old Regime Only)</h3>
          
          <div class="grid grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Section 80C (Max ₹1.5L)</label>
              <p class="text-xs text-gray-500 mb-2">LIC, PPF, EPF, ELSS, Tuition Fees, etc.</p>
              <input v-model.number="form.section_80c" type="number" min="0" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Section 80D</label>
              <p class="text-xs text-gray-500 mb-2">Medical Insurance Premium</p>
              <input v-model.number="form.section_80d" type="number" min="0" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">House Rent Paid (Annual)</label>
              <p class="text-xs text-gray-500 mb-2">For HRA Exemption calculation</p>
              <input v-model.number="form.rent_paid" type="number" min="0" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Home Loan Interest</label>
              <p class="text-xs text-gray-500 mb-2">Section 24(b)</p>
              <input v-model.number="form.home_loan_interest" type="number" min="0" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between pt-6 border-t border-gray-100">
          <div v-if="currentDeclaration" class="flex items-center gap-2">
            <span class="text-sm font-bold text-gray-500">Current Status:</span>
            <span v-if="currentDeclaration.status === 'pending'" class="bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide">Pending Review</span>
            <span v-else-if="currentDeclaration.status === 'approved'" class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide">Approved</span>
            <span v-else-if="currentDeclaration.status === 'rejected'" class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide">Rejected</span>
            
            <p v-if="currentDeclaration.admin_note" class="text-xs text-red-600 font-medium ml-2">Note: {{ currentDeclaration.admin_note }}</p>
          </div>
          <div v-else>
             <span class="text-sm text-gray-500 font-medium">No declaration submitted yet for this year.</span>
          </div>

          <button type="submit" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-bold transition-colors disabled:opacity-50">
            {{ loading ? 'Submitting...' : (currentDeclaration ? 'Update Declaration' : 'Submit Declaration') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';

const form = ref({
  financial_year: '2024-2025',
  regime: 'new',
  rent_paid: 0,
  section_80c: 0,
  section_80d: 0,
  home_loan_interest: 0
});

const currentDeclaration = ref(null);
const allDeclarations = ref([]);
const loading = ref(false);
const error = ref(null);

const getAuthHeaders = () => {
  const token = localStorage.getItem('token');
  return {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  };
};

const fetchDeclarations = async () => {
  try {
    const res = await fetch('http://localhost:8000/api/tax-declarations', { headers: getAuthHeaders() });
    if (!res.ok) throw new Error('Failed to fetch tax declarations');
    allDeclarations.value = await res.json();
    populateFormForYear();
  } catch (err) {
    error.value = err.message;
  }
};

const populateFormForYear = () => {
  const dec = allDeclarations.value.find(d => d.financial_year === form.value.financial_year);
  if (dec) {
    currentDeclaration.value = dec;
    form.value.regime = dec.regime;
    form.value.rent_paid = parseFloat(dec.rent_paid) || 0;
    form.value.section_80c = parseFloat(dec.section_80c) || 0;
    form.value.section_80d = parseFloat(dec.section_80d) || 0;
    form.value.home_loan_interest = parseFloat(dec.home_loan_interest) || 0;
  } else {
    currentDeclaration.value = null;
    form.value.regime = 'new';
    form.value.rent_paid = 0;
    form.value.section_80c = 0;
    form.value.section_80d = 0;
    form.value.home_loan_interest = 0;
  }
};

watch(() => form.value.financial_year, populateFormForYear);

const submitDeclaration = async () => {
  loading.value = true;
  error.value = null;
  try {
    const res = await fetch('http://localhost:8000/api/tax-declarations', {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify(form.value)
    });
    if (!res.ok) throw new Error('Failed to submit declaration');
    
    await fetchDeclarations();
    alert('Tax Declaration submitted successfully. Pending Admin Approval.');
  } catch (err) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  // Try to set current FY based on month
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth() + 1;
  if (month >= 4) {
    form.value.financial_year = `${year}-${year + 1}`;
  } else {
    form.value.financial_year = `${year - 1}-${year}`;
  }
  
  fetchDeclarations();
});
</script>
