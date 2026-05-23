<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import IdCardComponent from '../../components/IdCardComponent.vue';
import { ImageIcon, UserCircle } from 'lucide-vue-next';

const authStore = useAuthStore();
const employee = computed(() => authStore.user?.employee);
const company = computed(() => authStore.user?.company);

const idCardImageUrl = computed(() => {
  const imgPath = employee.value?.id_card_image;
  return imgPath ? `http://localhost:8000/api/file/${imgPath}` : null;
});
</script>

<template>
  <div class="max-w-4xl mx-auto pb-12">
    <!-- If a saved ID card image exists, show it -->
    <div v-if="idCardImageUrl" class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <UserCircle class="w-7 h-7 text-blue-600" />
            Your ID Card
          </h2>
          <p class="text-gray-500 mt-1 text-sm">Your official employee ID card issued by the company.</p>
        </div>
        <button 
          @click="() => window.print()"
          class="flex items-center gap-2 px-4 py-2 bg-gray-900 text-white rounded-xl hover:bg-gray-800 font-medium transition-colors"
        >
          Print ID
        </button>
      </div>

      <!-- Show saved ID card image -->
      <div class="flex justify-center">
        <div class="rounded-2xl overflow-hidden shadow-xl id-card-container">
          <img 
            :src="idCardImageUrl" 
            alt="Your Employee ID Card"
            class="w-[340px] object-contain"
          />
        </div>
      </div>
    </div>

    <!-- If no saved image yet, show the live HTML card -->
    <div v-else>
      <IdCardComponent 
        :employeeData="authStore.user" 
        :companyData="company" 
      />
      <!-- Info notice that admin needs to generate -->
      <div class="mt-6 flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl p-4 max-w-sm mx-auto">
        <ImageIcon class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" />
        <p class="text-sm text-amber-700">
          Your official ID card image hasn't been generated yet. Please contact your HR/Admin to generate and save your ID card.
        </p>
      </div>
    </div>
  </div>
</template>

<style>
@media print {
  body * {
    visibility: hidden !important;
  }
  .id-card-container, .id-card-container * {
    visibility: visible !important;
  }
  .id-card-container {
    position: absolute !important;
    left: 0 !important;
    top: 0 !important;
  }
}
</style>
