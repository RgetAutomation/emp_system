<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-800">Statutory Reports & Challans</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      
      <!-- PF ECR Challan -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-bold text-lg text-gray-800 mb-2 flex items-center gap-2">
          <span>Provident Fund (PF) ECR</span>
        </h3>
        <p class="text-sm text-gray-500 mb-4">Generate text file for EPFO portal upload in ECR format.</p>
        
        <div class="flex gap-4 items-center">
          <input v-model="pfMonth" type="month" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
          <button @click="downloadPF" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
            Download .TXT
          </button>
        </div>
      </div>

      <!-- ESI Challan -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-bold text-lg text-gray-800 mb-2 flex items-center gap-2">
          <span>ESIC Contribution</span>
        </h3>
        <p class="text-sm text-gray-500 mb-4">Generate CSV file for ESIC portal bulk upload.</p>
        
        <div class="flex gap-4 items-center">
          <input v-model="esiMonth" type="month" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
          <button @click="downloadESI" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
            Download .CSV
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const pfMonth = ref(new Date().toISOString().slice(0, 7));
const esiMonth = ref(new Date().toISOString().slice(0, 7));

const downloadPF = async () => {
  const token = localStorage.getItem('token');
  const response = await fetch(`http://localhost:8000/api/reports/pf-challan?month=${pfMonth.value}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  if (response.ok) {
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `PF_ECR_${pfMonth.value}.txt`;
    document.body.appendChild(a);
    a.click();
    a.remove();
  } else {
    alert("Failed to download PF Challan. Ensure payrolls are finalized for this month.");
  }
};

const downloadESI = async () => {
  const token = localStorage.getItem('token');
  const response = await fetch(`http://localhost:8000/api/reports/esi-challan?month=${esiMonth.value}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  if (response.ok) {
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `ESI_${esiMonth.value}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
  } else {
    alert("Failed to download ESI Challan.");
  }
};
</script>
