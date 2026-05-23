<script setup>
import { computed, ref, watch } from 'vue';
import { UserCircle, Printer, Save, CheckCircle2, Loader2, Sparkles, Building2 } from 'lucide-vue-next';

const props = defineProps({
  employeeData: {
    type: Object,
    required: true
  },
  companyData: {
    type: Object,
    required: true
  },
  hideHeader: {
    type: Boolean,
    default: false
  },
  showSaveButton: {
    type: Boolean,
    default: false
  },
  employeeId: {
    type: [Number, String],
    default: null
  }
});

// Setup dynamic settings customizers with robust fallbacks
const idCardSettings = computed(() => {
  const defaultSettings = {
    theme_color: '#4f46e5',
    text_color: '#1e293b',
    bg_gradient_start: '#e0e7ff',
    bg_gradient_end: '#ffffff',
    show_barcode: true,
    show_chip: true,
    layout_type: 'portrait',
    border_radius: '3xl',
    font_style: 'sans-serif',
    border_width: '4px',
    border_color: '#4f46e5',
    header_accent_height: 'medium',
    show_hologram: true,
    watermark_opacity: 0.1,
    card_glow: 'indigo-glow',
    photo_size: 'medium',
    photo_shape: 'rounded-2xl',
    photo_position: 'center',
    photo_x: 0,
    photo_y: 0
  };
  
  const savedSettings = props.companyData?.settings?.id_card || {};
  return { ...defaultSettings, ...savedSettings };
});

const emit = defineEmits(['save-success']);

const isSaving = ref(false);
const savedSuccess = ref(false);
const saveError = ref(null);
const cardRef = ref(null);

// base64 versions — only used for html2canvas capture (avoids canvas CORS taint)
const profilePhotoBase64 = ref(null);
const companyLogoBase64 = ref(null);

// Track image load errors for graceful fallback
const profilePhotoError = ref(false);
const companyLogoError = ref(false);

const user = computed(() => props.employeeData?.user || props.employeeData);
const employee = computed(() => props.employeeData?.employee || props.employeeData);
const company = computed(() => props.companyData);

const getImageUrl = (path) => {
  // Use the proxy API route so CORS headers are included (needed for html2canvas)
  return path ? `http://localhost:8000/api/file/${path}` : null;
};

// Direct display URLs — always works for <img> tags
const profilePhotoUrl = computed(() => {
  profilePhotoError.value = false; // reset error when data changes
  const path = employee.value?.documents?.profile_photo;
  return getImageUrl(path);
});

const companyLogoUrl = computed(() => {
  companyLogoError.value = false; // reset error when data changes
  const path = company.value?.logo;
  return getImageUrl(path);
});

// Convert an image URL to base64 via a canvas proxy (avoids CORS taint)
const toBase64 = (url) => {
  return new Promise((resolve) => {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => {
      try {
        const canvas = document.createElement('canvas');
        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;
        canvas.getContext('2d').drawImage(img, 0, 0);
        resolve(canvas.toDataURL('image/png'));
      } catch {
        resolve(null);
      }
    };
    img.onerror = () => resolve(null);
    img.src = url + '?t=' + Date.now(); // cache-bust
  });
};

// Convert images to base64 only when needed for html2canvas capture
const preloadImagesForCapture = async () => {
  const profilePath = employee.value?.documents?.profile_photo;
  const logoPath = company.value?.logo;

  profilePhotoBase64.value = profilePath ? await toBase64(getImageUrl(profilePath)) : null;
  companyLogoBase64.value = logoPath ? await toBase64(getImageUrl(logoPath)) : null;
};

const handlePrint = () => {
  window.print();
};

const captureAndSave = async () => {
  if (!cardRef.value) return;
  isSaving.value = true;
  savedSuccess.value = false;
  saveError.value = null;

  try {
    // Convert images to base64 just before capture (needed for html2canvas CORS)
    await preloadImagesForCapture();

    // Wait a tick so base64 images render in the DOM before capture
    await new Promise(resolve => setTimeout(resolve, 300));

    const html2canvas = (await import('html2canvas')).default;

    const canvas = await html2canvas(cardRef.value, {
      scale: 2,
      useCORS: true,
      allowTaint: false,
      backgroundColor: '#ffffff',
      logging: false,
    });

    const base64Image = canvas.toDataURL('image/png');

    const { default: api } = await import('../axios');
    const employeeId = props.employeeId || employee.value?.id;

    const response = await api.post(`/employees/${employeeId}/save-id-card`, {
      image: base64Image
    });

    savedSuccess.value = true;
    emit('save-success', response.data);

    setTimeout(() => {
      savedSuccess.value = false;
      // Clear base64 after save to restore direct URL display
      profilePhotoBase64.value = null;
      companyLogoBase64.value = null;
    }, 3000);
  } catch (err) {
    console.error('Error saving ID card:', err);
    saveError.value = err?.response?.data?.message || 'Failed to save ID card image.';
    // Clear base64 on error too
    profilePhotoBase64.value = null;
    companyLogoBase64.value = null;
  } finally {
    isSaving.value = false;
  }
};
</script>

<template>
  <div class="space-y-6">
    <!-- Full header for employee's own view -->
    <div v-if="!hideHeader" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
          <UserCircle class="w-7 h-7 text-blue-600" />
          Virtual ID Card
        </h2>
        <p class="text-gray-500 mt-1 text-sm">View, print, or download the official employee ID card.</p>
      </div>
      <div class="flex items-center gap-3">
        <button 
          @click="handlePrint"
          class="flex items-center gap-2 px-4 py-2 bg-gray-900 text-white rounded-xl hover:bg-gray-800 font-medium transition-colors"
        >
          <Printer class="w-4 h-4" />
          Print ID
        </button>
      </div>
    </div>

    <!-- Save ID Card button — always shown when showSaveButton=true (admin modal) -->
    <div v-if="showSaveButton" class="flex justify-center">
      <button 
        @click="captureAndSave"
        :disabled="isSaving"
        class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm transition-all duration-200 shadow-sm"
        :class="savedSuccess 
          ? 'bg-green-500 text-white' 
          : 'bg-indigo-600 text-white hover:bg-indigo-700'"
      >
        <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
        <CheckCircle2 v-else-if="savedSuccess" class="w-4 h-4" />
        <Save v-else class="w-4 h-4" />
        {{ isSaving ? 'Generating & Saving...' : savedSuccess ? 'Saved to Database!' : 'Save ID Card to Database' }}
      </button>
    </div>

    <!-- Error message -->
    <div v-if="saveError" class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700">
      {{ saveError }}
    </div>

    <!-- The ID Card -->
    <div class="flex justify-center">
      <div 
        ref="cardRef"
        :class="[
          idCardSettings.layout_type === 'portrait' ? 'w-64 h-[380px]' : 'w-[380px] h-64',
          idCardSettings.border_radius === 'none' ? 'rounded-none' : 
          idCardSettings.border_radius === 'lg' ? 'rounded-xl' : 
          idCardSettings.border_radius === '2xl' ? 'rounded-[2rem]' : 'rounded-[2.8rem]',
          idCardSettings.font_style
        ]"
        :style="{
          background: `linear-gradient(135deg, ${idCardSettings.bg_gradient_start}, ${idCardSettings.bg_gradient_end})`,
          color: idCardSettings.text_color,
          borderColor: idCardSettings.border_color,
          borderWidth: idCardSettings.border_width,
          boxShadow: idCardSettings.card_glow === 'indigo-glow' ? `0 25px 50px -12px ${idCardSettings.theme_color}65` :
                     idCardSettings.card_glow === 'golden-glow' ? '0 25px 50px -12px rgba(245, 158, 11, 0.55)' :
                     idCardSettings.card_glow === 'soft-shadow' ? '0 30px 60px -15px rgba(0, 0, 0, 0.35)' : ''
        }"
        class="bg-white relative p-5 flex flex-col justify-between overflow-hidden transition-all duration-300 select-none id-card-container print:shadow-none print:border print:border-gray-300"
      >
        <!-- Dynamic Header Banner Background Overlay -->
        <div 
          v-if="idCardSettings.header_accent_height !== 'full'" 
          :class="[
            idCardSettings.header_accent_height === 'small' ? 'h-3' : 
            idCardSettings.header_accent_height === 'medium' ? 'h-14 rounded-b-[2rem]' : 'h-24 rounded-b-[1rem]'
          ]"
          :style="{ backgroundColor: `${idCardSettings.theme_color}15` }"
          class="absolute top-0 left-0 w-full pointer-events-none transition-all duration-300"
        ></div>

        <!-- Centered watermark logo logo -->
        <div 
          class="absolute inset-0 flex items-center justify-center pointer-events-none transition-all duration-300" 
          :style="{ opacity: idCardSettings.watermark_opacity }"
        >
          <img v-if="(companyLogoBase64 || companyLogoUrl) && !companyLogoError" :src="companyLogoBase64 || companyLogoUrl" class="w-24 h-24 object-contain filter grayscale" crossorigin="anonymous" @error="companyLogoError = true" />
          <Building2 v-else class="w-24 h-24 text-slate-400" />
        </div>

        <!-- Card Header -->
        <div class="flex items-center justify-between border-b pb-2 z-10" :style="{ borderColor: `${idCardSettings.theme_color}30` }">
          <div class="flex items-center gap-2">
            <img v-if="(companyLogoBase64 || companyLogoUrl) && !companyLogoError" :src="companyLogoBase64 || companyLogoUrl" class="w-6 h-6 object-contain" crossorigin="anonymous" @error="companyLogoError = true" />
            <Building2 v-else class="w-6 h-6" :style="{ color: idCardSettings.theme_color }" />
            <p class="text-[9px] font-extrabold uppercase tracking-wide truncate max-w-[120px]">{{ company?.name || 'Company Name' }}</p>
          </div>
          <span class="text-[8px] font-black tracking-widest opacity-60">MEMBER</span>
        </div>

        <!-- Card Body Content Layout depending on orientation -->
        <div 
          :class="[
            idCardSettings.layout_type === 'portrait' ? 'flex-col mt-4' : (idCardSettings.photo_position === 'right' ? 'flex-row-reverse text-right' : 'flex-row') + ' items-center gap-4'
          ]" 
          :style="{
            alignItems: idCardSettings.layout_type === 'portrait' ? (idCardSettings.photo_position === 'left' ? 'flex-start' : idCardSettings.photo_position === 'right' ? 'flex-end' : 'center') : 'center',
            textAlign: idCardSettings.layout_type === 'portrait' ? idCardSettings.photo_position : ''
          }"
          class="flex grow justify-center z-10 min-w-0"
        >
          <!-- Photo Avatar placeholder -->
          <div 
            class="relative shrink-0 flex items-center justify-center border-2 bg-slate-100 overflow-hidden shadow-md" 
            :class="[
              idCardSettings.layout_type === 'portrait' ? 
                (idCardSettings.photo_size === 'small' ? 'w-16 h-16 mb-2' : idCardSettings.photo_size === 'large' ? 'w-24 h-24 mb-4' : 'w-20 h-20 mb-3') : 
                (idCardSettings.photo_size === 'small' ? 'w-16 h-16' : idCardSettings.photo_size === 'large' ? 'w-24 h-24' : 'w-20 h-20'),
              idCardSettings.layout_type === 'portrait' ? 
                (idCardSettings.photo_position === 'left' ? 'ml-2' : idCardSettings.photo_position === 'right' ? 'mr-2' : 'mx-auto') : 'mx-0',
              idCardSettings.photo_shape
            ]"
            :style="{ 
              borderColor: idCardSettings.theme_color,
              transform: `translate(${idCardSettings.photo_x || 0}px, ${idCardSettings.photo_y || 0}px)`
            }"
          >
            <img 
              v-if="(profilePhotoBase64 || profilePhotoUrl) && !profilePhotoError" 
              :src="profilePhotoBase64 || profilePhotoUrl" 
              alt="Profile Photo" 
              class="w-full h-full object-cover pointer-events-none"
              crossorigin="anonymous"
              @error="profilePhotoError = true"
            />
            <!-- Styled initials avatar when no photo or load error -->
            <div v-else class="w-full h-full bg-gradient-to-br from-indigo-400 to-indigo-700 flex items-center justify-center pointer-events-none">
              <span class="text-3xl font-bold text-white uppercase select-none">
                {{ user?.name?.charAt(0) || '?' }}
              </span>
            </div>
            
            <div v-if="idCardSettings.show_chip" class="absolute -bottom-0.5 -right-0.5 bg-gradient-to-tr from-yellow-300 to-yellow-500 border border-yellow-600 rounded-sm w-5 h-4 flex flex-col justify-between p-0.5 shadow-sm pointer-events-none">
              <div class="flex justify-between w-full h-[1.5px] border-b border-yellow-700/35"></div>
              <div class="flex justify-between w-full h-[1.5px] border-b border-yellow-700/35"></div>
            </div>
          </div>

          <!-- Text Details -->
          <div class="min-w-0" :class="idCardSettings.layout_type === 'portrait' ? 'w-full' : 'grow'">
            <h4 class="text-xs font-black truncate uppercase tracking-wide leading-none">{{ user?.name || 'Employee Name' }}</h4>
            <p class="text-[9px] font-bold mt-1.5 truncate opacity-85 uppercase tracking-wider" :style="{ color: idCardSettings.theme_color }">{{ employee?.designation?.name || 'Employee' }}</p>
            <p class="text-slate-500 text-[8px] font-bold mt-0.5 uppercase tracking-wide">{{ employee?.department?.name || 'Department' }}</p>
            
            <div class="mt-2.5 inline-block px-3 py-0.5 rounded-full text-[8px] font-black font-mono tracking-wider text-white" :style="{ backgroundColor: idCardSettings.theme_color }">
              ID: {{ employee?.employee_id || 'N/A' }}
            </div>
          </div>
        </div>

        <!-- Hologram seal seal graphic -->
        <div v-if="idCardSettings.show_hologram" class="absolute bottom-16 right-5 w-8 h-8 rounded-full border border-yellow-400/40 bg-gradient-to-tr from-yellow-300 via-amber-200 to-yellow-500 shadow flex items-center justify-center overflow-hidden animate-pulse z-10">
          <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-indigo-300/30 via-pink-300/30 to-yellow-300/30 mix-blend-color-dodge"></div>
          <Sparkles class="w-4 h-4 text-yellow-600/80 drop-shadow-sm" />
        </div>

        <!-- Barcode Mockup -->
        <div v-if="idCardSettings.show_barcode" class="w-full flex flex-col items-center border-t pt-2 z-10" :style="{ borderColor: `${idCardSettings.theme_color}20` }">
          <!-- CSS generated bars -->
          <div class="h-6 flex items-end gap-[1.5px] overflow-hidden opacity-85">
            <div v-for="i in 28" :key="i" :class="i % 3 === 0 ? 'w-[2.5px] h-full' : i % 5 === 0 ? 'w-[0.8px] h-3/4' : 'w-[1.2px] h-5/6'" class="bg-slate-900 rounded-sm"></div>
          </div>
          <span class="text-[7px] font-mono tracking-widest mt-0.5 font-bold opacity-60">1029485736209</span>
        </div>

        <!-- Bottom Disclaimer Note -->
        <div class="text-[5px] text-slate-400 text-center leading-tight mt-2.5 pt-2.5 border-t border-slate-100 z-10">
          This card is the property of {{ company?.name }}. If found, please return to: {{ company?.address || 'Company Address' }}.
        </div>
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
    transform: scale(1.5);
    transform-origin: top left;
  }
}
</style>
