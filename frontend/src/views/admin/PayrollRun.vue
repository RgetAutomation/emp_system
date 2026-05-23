<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-800">Payroll Run</h2>
      <div class="flex gap-4 items-center">
        <input v-model="selectedMonth" type="month" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button @click="loadEmployees" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
          Refresh List
        </button>
      </div>
    </div>

    <!-- Error Alert -->
    <div v-if="error" class="bg-red-50 text-red-600 p-4 rounded-lg">
      {{ error }}
    </div>

    <!-- Employee List -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100">
              <th class="p-4 font-semibold text-gray-600">Employee</th>
              <th class="p-4 font-semibold text-gray-600">Basic</th>
              <th class="p-4 font-semibold text-gray-600">Gross</th>
              <th class="p-4 font-semibold text-gray-600">Net Salary</th>
              <th class="p-4 font-semibold text-gray-600">Status</th>
              <th class="p-4 font-semibold text-gray-600 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="emp in employees" :key="emp.id" class="border-b border-gray-50">
              <td class="p-4 font-medium text-gray-800">{{ emp.user?.name }} <br><span class="text-xs text-gray-500">{{ emp.employee_id }}</span></td>
              <td class="p-4">
                 <span v-if="payrolls[emp.id]">₹{{ payrolls[emp.id].basic_salary }}</span>
                 <span v-else class="text-gray-400">-</span>
              </td>
              <td class="p-4">
                 <span v-if="payrolls[emp.id]">₹{{ payrolls[emp.id].gross_adjusted || payrolls[emp.id].gross_salary }}</span>
                 <span v-else class="text-gray-400">-</span>
              </td>
              <td class="p-4 font-bold text-green-600">
                 <span v-if="payrolls[emp.id]">₹{{ payrolls[emp.id].net_salary }}</span>
                 <span v-else class="text-gray-400">-</span>
              </td>
              <td class="p-4">
                <span v-if="payrolls[emp.id]?.is_saved" class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold uppercase">Paid</span>
                <span v-else-if="payrolls[emp.id]" class="bg-amber-100 text-amber-700 px-2 py-1 rounded text-xs font-bold uppercase">Preview</span>
                <span v-else class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-bold uppercase">Pending</span>
              </td>
              <td class="p-4 text-right">
                <button v-if="!payrolls[emp.id]" @click="previewPayroll(emp.id)" class="text-blue-600 hover:underline text-sm font-medium">Generate Preview</button>
                <button v-if="payrolls[emp.id] && !payrolls[emp.id].is_saved" @click="savePayroll(emp.id)" class="text-green-600 hover:underline text-sm font-medium ml-3 border border-green-200 px-3 py-1 rounded bg-green-50">Approve & Save</button>
                <button v-if="payrolls[emp.id]" @click="viewDetails(emp.id)" class="text-gray-600 hover:underline text-sm font-medium ml-3">View Breakdown</button>
              </td>
            </tr>
            <tr v-if="employees.length === 0">
              <td colspan="6" class="p-8 text-center text-gray-500">No employees found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Details Modal -->
    <div v-if="selectedPayroll" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
        <div class="flex justify-between items-center p-6 border-b border-gray-100">
          <h3 class="text-xl font-bold text-gray-800">Salary Breakdown</h3>
          <button @click="selectedPayroll = null" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="text-sm font-bold text-gray-500 uppercase">Earnings</h4>
              <div class="mt-2 space-y-2 text-sm">
                <div class="flex justify-between"><span>Basic Salary</span><span>₹{{ selectedPayroll.basic_salary }}</span></div>
                <div class="flex justify-between"><span>Gross Salary Component</span><span>₹{{ selectedPayroll.gross_salary }}</span></div>
                <div class="flex justify-between text-green-600"><span>Overtime Pay</span><span>+ ₹{{ selectedPayroll.overtime_pay }}</span></div>
                <div class="flex justify-between text-red-600"><span>Penalties/Lates</span><span>- ₹{{ selectedPayroll.total_penalty || 0 }}</span></div>
                <div class="flex justify-between font-bold pt-2 border-t"><span>Adjusted Gross</span><span>₹{{ selectedPayroll.gross_adjusted || selectedPayroll.gross_salary }}</span></div>
              </div>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="text-sm font-bold text-gray-500 uppercase">Statutory Deductions</h4>
              <div class="mt-2 space-y-2 text-sm">
                <div class="flex justify-between text-red-600"><span>Provident Fund (PF)</span><span>₹{{ selectedPayroll.deductions?.pf || selectedPayroll.pf_deduction || 0 }}</span></div>
                <div class="flex justify-between text-red-600"><span>ESI</span><span>₹{{ selectedPayroll.deductions?.esi || selectedPayroll.esi_deduction || 0 }}</span></div>
                <div class="flex justify-between text-red-600"><span>Professional Tax (PT)</span><span>₹{{ selectedPayroll.deductions?.pt || selectedPayroll.pt_deduction || 0 }}</span></div>
                <div class="flex justify-between text-red-600"><span>TDS</span><span>₹{{ selectedPayroll.deductions?.tds || selectedPayroll.tds_deduction || 0 }}</span></div>
              </div>
            </div>
          </div>
          <div class="flex justify-between items-center bg-blue-50 text-blue-800 p-4 rounded-xl text-lg font-bold border border-blue-100">
            <span>Net Take Home</span>
            <span>₹{{ selectedPayroll.net_salary }}</span>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const selectedMonth = ref(new Date().toISOString().slice(0, 7)); // YYYY-MM
const employees = ref([]);
const payrolls = ref({}); // Map of employee_id -> payroll data
const error = ref(null);
const selectedPayroll = ref(null);

const getAuthHeaders = () => {
  const token = localStorage.getItem('token');
  return {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  };
};

const loadEmployees = async () => {
  error.value = null;
  payrolls.value = {};
  try {
    // Load employees
    const empRes = await fetch('http://localhost:8000/api/employees', { headers: getAuthHeaders() });
    if (!empRes.ok) throw new Error('Failed to load employees');
    employees.value = await empRes.json();

    // Load saved payrolls for the month
    const payRes = await fetch(`http://localhost:8000/api/payroll?month=${selectedMonth.value}`, { headers: getAuthHeaders() });
    if (payRes.ok) {
      const savedPayrolls = await payRes.json();
      savedPayrolls.forEach(p => {
        payrolls.value[p.employee_id] = { ...p, is_saved: true };
      });
    }
  } catch (err) {
    error.value = err.message;
  }
};

const previewPayroll = async (empId) => {
  try {
    const res = await fetch(`http://localhost:8000/api/payroll/preview/${empId}?month=${selectedMonth.value}`, { headers: getAuthHeaders() });
    if (!res.ok) {
      const e = await res.json();
      throw new Error(e.message || 'Failed to generate preview');
    }
    const data = await res.json();
    payrolls.value[empId] = { ...data, is_saved: false };
  } catch (err) {
    alert(err.message);
  }
};

const savePayroll = async (empId) => {
  const data = payrolls.value[empId];
  if (!data) return;

  const payload = {
    employee_id: empId,
    month: selectedMonth.value,
    basic_salary: data.basic_salary,
    gross_salary: data.gross_adjusted || data.gross_salary,
    overtime_pay: data.overtime_pay,
    pf_deduction: data.deductions?.pf || 0,
    esi_deduction: data.deductions?.esi || 0,
    pt_deduction: data.deductions?.pt || 0,
    tds_deduction: data.deductions?.tds || 0,
    net_salary: data.net_salary
  };

  try {
    const res = await fetch(`http://localhost:8000/api/payroll`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify(payload)
    });
    if (!res.ok) {
      const e = await res.json();
      throw new Error(e.message || 'Failed to save payroll');
    }
    
    // Mark as saved
    payrolls.value[empId] = { ...payrolls.value[empId], is_saved: true };
    alert('Payroll finalized successfully!');
  } catch (err) {
    alert(err.message);
  }
};

const viewDetails = (empId) => {
  selectedPayroll.value = payrolls.value[empId];
};

onMounted(() => {
  loadEmployees();
});
</script>
