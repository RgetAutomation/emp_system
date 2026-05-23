<script setup>
import { onMounted, ref, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { 
  Building2, Upload, FileText, CheckCircle2, AlertCircle, Eye, Download,
  ShieldCheck, CreditCard, FileSignature, Receipt, FileSymlink, PartyPopper,
  Sparkles, Check, Plus, Trash2, HelpCircle, Palette, RefreshCw
} from 'lucide-vue-next';
import api from '../../axios';

const authStore = useAuthStore();
const loading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

// Tabs Configuration
const activeTab = ref('company');
const tabs = [
  { id: 'company', name: 'Company Profile', icon: Building2, subtitle: 'Business identity & branding' },
  { id: 'id_pattern', name: 'Employee ID Pattern', icon: ShieldCheck, subtitle: 'ID auto-generation sequence' },
  { id: 'id_card', name: 'ID Card Designer', icon: CreditCard, subtitle: 'Layout, colors & barcode specs' },
  { id: 'offer_letter', name: 'Offer Letter Builder', icon: FileSignature, subtitle: 'Appointment letter customizer' },
  { id: 'payslip', name: 'Payslip Designer', icon: Receipt, subtitle: 'Salary receipt invoice headers' },
  { id: 'resignation', name: 'Resignation Policy', icon: FileSymlink, subtitle: 'Exit notices & checklists' },
  { id: 'festival', name: 'Festival Message Setup', icon: PartyPopper, subtitle: 'Dashboard greetings manager' },
];

// Profile & Core ID Settings
const form = ref({
  name: '',
  email: '',
  phone: '',
  address: '',
  gst_no: '',
  emp_id_prefix: 'EMP-',
  emp_id_padding: 4,
});

const logoFile = ref(null);
const logoPreview = ref(null);
const tradeLicenseFile = ref(null);
const tradeLicenseName = ref('');

// Comprehensive JSON Settings State (Tab Builders)
const settings = ref({
  id_card: {
    theme_color: '#4f46e5', // default indigo
    text_color: '#1e293b',
    bg_gradient_start: '#e0e7ff', // light indigo
    bg_gradient_end: '#ffffff',
    show_barcode: true,
    show_chip: true,
    layout_type: 'portrait', // portrait or landscape
    border_radius: '3xl', // lg, 3xl, full
    // Advanced Design Systems
    font_style: 'sans-serif', // sans-serif, monospace, serif, cursive, Orbitron
    border_width: '4px', // 0px, 2px, 4px, 6px
    border_color: '#4f46e5',
    header_accent_height: 'medium', // small, medium, large, full
    show_hologram: true,
    watermark_opacity: '0.1', // 0.0 to 0.5
    card_glow: 'indigo-glow', // none, soft-shadow, indigo-glow, golden-glow
    // Advanced Photo Controls
    photo_size: 'medium', // small, medium, large
    photo_shape: 'rounded-2xl', // rounded-none, rounded-xl, rounded-2xl, rounded-full
    photo_position: 'center', // left, center, right
    photo_x: 0,
    photo_y: 0,
  },
  offer_letter: {
    template_body: `# OFFER OF EMPLOYMENT

Dear {employee_name},

On behalf of **{company_name}**, we are absolutely delighted to offer you the position of **{designation}**. We were highly impressed by your qualifications and look forward to welcoming you to our team.

### Employment Terms:
- **Designation / Role**: {designation}
- **Joining Date**: {joining_date}
- **Compensation (CTC)**: BDT {salary} per month
- **Probationary Period**: 6 Months

### General Conditions:
1. **Roster Scheduling**: Your shift timings will be set in accordance with the company duty rosters.
2. **Confidentiality**: You agree to safeguard all proprietary business systems and assets.
3. **Exit Policy**: A notice period of 30 days is required in writing from either party.

Please sign below to convey your official acceptance of this offer.

Warm regards,

**{signer_name}**
*{signer_title}*`,
    signer_name: 'Rumana Rahman',
    signer_title: 'Lead HR Executive',
    show_header: true,
    show_signature: true,
  },
  payslip: {
    header_title: 'Official Salary Receipt',
    show_bank_details: true,
    bank_name: 'Eastern Bank PLC',
    bank_account_label: 'A/C: 102XXXXXX98',
    footer_note: 'This receipt is electronically verified and does not require an ink signature.',
    allowances: [
      { id: 1, name: 'Basic Salary', amount: '60%' },
      { id: 2, name: 'House Rent Allowance', amount: '20%' },
      { id: 3, name: 'Medical Benefit', amount: '10%' },
      { id: 4, name: 'Conveyance Allowance', amount: '10%' }
    ],
    deductions: [
      { id: 1, name: 'Provident Fund', amount: '10%' },
      { id: 2, name: 'Professional Tax', amount: 'BDT 200' }
    ]
  },
  resignation: {
    notice_period_days: 30,
    require_clearance: true,
    exit_body_template: `Dear {employee_name},

This is to officially acknowledge and approve your resignation from your post as **{designation}** at **{company_name}**.

We accept your notice period starting today. Your final date of release is computed to be **{last_working_date}** in compliance with the **{notice_period_days}-day notice policy**.

Please return all IT hardware and clear outstanding dues with the Finance desk. We sincerely thank you for your service and wish you high success in your future pathway.

Best regards,

**HR Services Division**
*{company_name}*`,
    clearance_checklists: [
      'IT Department Asset & Laptop Handover',
      'Finance & Outstanding Reimbursement Settlement',
      'Biometric Access Card & ID Card Return',
      'Pending Work Handover Document upload'
    ]
  },
  festival: {
    festivals: [
      {
        id: 'eid_ul_fitr',
        name: 'Eid-ul-Fitr',
        enabled: true,
        title: 'Eid Mubarak!',
        message: 'Wishing you a blessed and joyous Eid filled with peace, health, and prosperity. May this festive occasion bring endless happiness to you and your loved ones.',
        banner_theme: 'emerald'
      },
      {
        id: 'durga_puja',
        name: 'Durga Puja',
        enabled: true,
        title: 'Shubho Sharadiya!',
        message: 'May Maa Durga bless you with strength, wisdom, and infinite joy. Wishing you a festive season filled with warmth, love, and prosperity.',
        banner_theme: 'orange'
      },
      {
        id: 'diwali',
        name: 'Diwali',
        enabled: true,
        title: 'Happy Diwali!',
        message: 'May the festival of lights brighten your life with peace, joy, and wealth. Wishing you and your family a safe, prosperous, and glowing Diwali!',
        banner_theme: 'yellow'
      },
      {
        id: 'new_year',
        name: 'New Year',
        enabled: true,
        title: 'Happy New Year!',
        message: 'Cheers to a fresh start, new opportunities, and outstanding achievements! Wishing you a brilliant and highly successful year ahead.',
        banner_theme: 'indigo'
      }
    ]
  }
});

// Mock values for visual previews
const mockEmployee = {
  name: 'Mohammad Nayeem',
  designation: 'Senior Lead Developer',
  emp_id: 'EMP-0142',
  photo: null,
  joining_date: 'June 01, 2026',
  salary: '95,000',
  probation_period: '6',
  last_working_date: 'June 22, 2026',
  working_days: '22',
  allowance_payout: 'BDT 95,000',
  deduction_total: 'BDT 9,700',
  net_salary: 'BDT 85,300'
};

// Interactive Profile Photo Drag-and-Drop coordinates engine
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const photoStart = ref({ x: 0, y: 0 });

const startPhotoDrag = (e) => {
  // Prevent default browser image dragging behaviors
  e.preventDefault();
  isDragging.value = true;
  
  // Track mouse or touch starting coordinates
  const clientX = e.type.startsWith('touch') ? e.touches[0].clientX : e.clientX;
  const clientY = e.type.startsWith('touch') ? e.touches[0].clientY : e.clientY;
  
  dragStart.value = { x: clientX, y: clientY };
  photoStart.value = { 
    x: settings.value.id_card.photo_x || 0, 
    y: settings.value.id_card.photo_y || 0 
  };
  
  window.addEventListener('mousemove', handlePhotoDrag);
  window.addEventListener('mouseup', stopPhotoDrag);
  window.addEventListener('touchmove', handlePhotoDrag, { passive: false });
  window.addEventListener('touchend', stopPhotoDrag);
};

const handlePhotoDrag = (e) => {
  if (!isDragging.value) return;
  
  const clientX = e.type.startsWith('touch') ? e.touches[0].clientX : e.clientX;
  const clientY = e.type.startsWith('touch') ? e.touches[0].clientY : e.clientY;
  
  const dx = clientX - dragStart.value.x;
  const dy = clientY - dragStart.value.y;
  
  const newX = photoStart.value.x + dx;
  const newY = photoStart.value.y + dy;
  
  // Keep constraints in place to prevent photo from dragging off-screen entirely
  settings.value.id_card.photo_x = Math.max(-120, Math.min(120, newX));
  settings.value.id_card.photo_y = Math.max(-120, Math.min(150, newY));
};

const stopPhotoDrag = () => {
  isDragging.value = false;
  window.removeEventListener('mousemove', handlePhotoDrag);
  window.removeEventListener('mouseup', stopPhotoDrag);
  window.removeEventListener('touchmove', handlePhotoDrag);
  window.removeEventListener('touchend', stopPhotoDrag);
};

const resetPhotoPosition = () => {
  settings.value.id_card.photo_x = 0;
  settings.value.id_card.photo_y = 0;
};

// Form states for adding custom items
const newAllowanceName = ref('');
const newAllowanceAmount = ref('');
const newDeductionName = ref('');
const newDeductionAmount = ref('');
const newClearanceItem = ref('');

// Load existing data
onMounted(() => {
  const company = authStore.user?.company;
  if (company) {
    form.value.name = company.name || '';
    form.value.email = company.email || '';
    form.value.phone = company.phone || '';
    form.value.address = company.address || '';
    form.value.gst_no = company.gst_no || '';
    form.value.emp_id_prefix = company.emp_id_prefix !== undefined && company.emp_id_prefix !== null ? company.emp_id_prefix : 'EMP-';
    form.value.emp_id_padding = company.emp_id_padding !== undefined && company.emp_id_padding !== null ? company.emp_id_padding : 4;
    
    if (company.logo) {
      logoPreview.value = `http://localhost:8000/storage/${company.logo}`;
    }
    if (company.trade_license) {
      tradeLicenseName.value = company.trade_license.split('/').pop();
    }

    if (company.settings) {
      // Merge values cleanly to support newly-loaded formats
      settings.value = {
        id_card: { ...settings.value.id_card, ...(company.settings.id_card || {}) },
        offer_letter: { ...settings.value.offer_letter, ...(company.settings.offer_letter || {}) },
        payslip: { ...settings.value.payslip, ...(company.settings.payslip || {}) },
        resignation: { ...settings.value.resignation, ...(company.settings.resignation || {}) },
        festival: { ...settings.value.festival, ...(company.settings.festival || {}) },
      };
    }
  }
});

// Dynamic Computed Previews
const parsedOfferLetter = computed(() => {
  let body = settings.value.offer_letter.template_body || '';
  body = body
    .replace(/{employee_name}/g, mockEmployee.name)
    .replace(/{company_name}/g, form.value.name || '[Company Name]')
    .replace(/{designation}/g, mockEmployee.designation)
    .replace(/{joining_date}/g, mockEmployee.joining_date)
    .replace(/{salary}/g, mockEmployee.salary)
    .replace(/{probation_period}/g, mockEmployee.probation_period)
    .replace(/{signer_name}/g, settings.value.offer_letter.signer_name)
    .replace(/{signer_title}/g, settings.value.offer_letter.signer_title);
  return body.replace(/\n/g, '<br>');
});

const parsedResignationLetter = computed(() => {
  let body = settings.value.resignation.exit_body_template || '';
  body = body
    .replace(/{employee_name}/g, mockEmployee.name)
    .replace(/{company_name}/g, form.value.name || '[Company Name]')
    .replace(/{designation}/g, mockEmployee.designation)
    .replace(/{last_working_date}/g, mockEmployee.last_working_date)
    .replace(/{notice_period_days}/g, settings.value.resignation.notice_period_days.toString());
  return body.replace(/\n/g, '<br>');
});

// File upload handlers
const handleLogoUpload = (e) => {
  const file = e.target.files[0];
  if (file) {
    logoFile.value = file;
    const reader = new FileReader();
    reader.onload = (event) => {
      logoPreview.value = event.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const handleTradeLicenseUpload = (e) => {
  const file = e.target.files[0];
  if (file) {
    tradeLicenseFile.value = file;
    tradeLicenseName.value = file.name;
  }
};

// Item Modifiers (allowances, deductions, clearances)
const addAllowance = () => {
  if (newAllowanceName.value && newAllowanceAmount.value) {
    settings.value.payslip.allowances.push({
      id: Date.now(),
      name: newAllowanceName.value,
      amount: newAllowanceAmount.value
    });
    newAllowanceName.value = '';
    newAllowanceAmount.value = '';
  }
};

const removeAllowance = (id) => {
  settings.value.payslip.allowances = settings.value.payslip.allowances.filter(a => a.id !== id);
};

const addDeduction = () => {
  if (newDeductionName.value && newDeductionAmount.value) {
    settings.value.payslip.deductions.push({
      id: Date.now(),
      name: newDeductionName.value,
      amount: newDeductionAmount.value
    });
    newDeductionName.value = '';
    newDeductionAmount.value = '';
  }
};

const removeDeduction = (id) => {
  settings.value.payslip.deductions = settings.value.payslip.deductions.filter(d => d.id !== id);
};

const addClearance = () => {
  if (newClearanceItem.value) {
    settings.value.resignation.clearance_checklists.push(newClearanceItem.value);
    newClearanceItem.value = '';
  }
};

const removeClearance = (index) => {
  settings.value.resignation.clearance_checklists.splice(index, 1);
};

// Submit handler
const handleSubmit = async () => {
  loading.value = true;
  successMessage.value = '';
  errorMessage.value = '';

  const formData = new FormData();
  formData.append('name', form.value.name);
  formData.append('email', form.value.email);
  if (form.value.phone) formData.append('phone', form.value.phone);
  if (form.value.address) formData.append('address', form.value.address);
  if (form.value.gst_no) formData.append('gst_no', form.value.gst_no);
  if (form.value.emp_id_prefix !== undefined) formData.append('emp_id_prefix', form.value.emp_id_prefix);
  if (form.value.emp_id_padding !== undefined) formData.append('emp_id_padding', form.value.emp_id_padding);
  
  // Serialize complex JSON structures
  formData.append('settings', JSON.stringify(settings.value));
  
  if (logoFile.value) {
    formData.append('logo', logoFile.value);
  }
  if (tradeLicenseFile.value) {
    formData.append('trade_license', tradeLicenseFile.value);
  }

  try {
    const response = await api.post('/company/update', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'Accept': 'application/json'
      }
    });

    // Update store and localStorage
    if (authStore.user) {
      authStore.user.company = response.data;
      localStorage.setItem('user', JSON.stringify(authStore.user));
    }
    
    // Refresh prefill references
    if (response.data.logo) {
      logoPreview.value = `http://localhost:8000/storage/${response.data.logo}`;
    }
    if (response.data.trade_license) {
      tradeLicenseName.value = response.data.trade_license.split('/').pop();
    }
    
    successMessage.value = 'Company settings & template layouts updated successfully!';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    setTimeout(() => {
      successMessage.value = '';
    }, 6000);
  } catch (error) {
    console.error('Update company settings failed:', error);
    errorMessage.value = error.response?.data?.message || 'Failed to save settings. Please ensure fields are valid.';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="max-w-7xl mx-auto py-2 px-4 sm:px-6">
    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 flex items-center gap-3">
          <div class="p-2 bg-indigo-50 rounded-2xl text-indigo-600 shadow-inner">
            <Building2 class="w-8 h-8" />
          </div>
          Settings Panel
        </h1>
        <p class="text-slate-500 mt-1 text-sm sm:text-base font-medium">
          Customize corporate identification patterns, layout templates, letter styling, and dashboard greetings.
        </p>
      </div>

      <!-- Unified Save Button -->
      <button @click="handleSubmit" :disabled="loading" class="md:self-end px-7 py-3 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white font-semibold rounded-2xl shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/35 transition-all duration-300 disabled:opacity-75 disabled:cursor-not-allowed flex items-center justify-center gap-2">
        <span v-if="loading" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
        <Check v-else class="w-5 h-5" />
        <span>{{ loading ? 'Saving All...' : 'Save Configuration' }}</span>
      </button>
    </div>

    <!-- Alert Notifications -->
    <div v-if="successMessage" class="mb-6 p-4 rounded-2xl bg-emerald-50/80 backdrop-blur-sm border border-emerald-200 text-emerald-800 flex items-start gap-3 shadow-md animate-fade-in">
      <CheckCircle2 class="w-5.5 h-5.5 text-emerald-600 shrink-0 mt-0.5" />
      <div>
        <p class="font-bold text-sm">Action Completed</p>
        <p class="text-xs text-emerald-700/90 font-medium mt-0.5">{{ successMessage }}</p>
      </div>
    </div>

    <div v-if="errorMessage" class="mb-6 p-4 rounded-2xl bg-rose-50/80 backdrop-blur-sm border border-rose-200 text-rose-800 flex items-start gap-3 shadow-md animate-fade-in">
      <AlertCircle class="w-5.5 h-5.5 text-rose-600 shrink-0 mt-0.5" />
      <div>
        <p class="font-bold text-sm">Failed to Save</p>
        <p class="text-xs text-rose-700/90 font-medium mt-0.5">{{ errorMessage }}</p>
      </div>
    </div>

    <!-- Top Horizontal Navigation Tabs -->
    <div class="mb-8 border-b border-slate-200 bg-white rounded-3xl p-2.5 border shadow-sm">
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            activeTab === tab.id 
              ? 'border-indigo-600 text-indigo-700 bg-indigo-50/50 font-extrabold shadow-sm' 
              : 'border-transparent text-slate-650 hover:text-slate-900 hover:bg-slate-50 font-semibold'
          ]"
          class="flex items-center gap-2 px-4 py-3 border-b-2 text-xs shrink-0 whitespace-nowrap transition-all duration-200 outline-none rounded-xl"
        >
          <component :is="tab.icon" :class="activeTab === tab.id ? 'text-indigo-600' : 'text-slate-400'" class="w-4.5 h-4.5 shrink-0" />
          <span>{{ tab.name }}</span>
        </button>
      </div>
    </div>

    <!-- Main Content Area: Now Full Width! -->
    <div>
        <!-- 1. COMPANY PROFILE TAB -->
        <div v-if="activeTab === 'company'" class="grid grid-cols-1 xl:grid-cols-12 gap-8 animate-fade-in">
          <!-- Editor inputs -->
          <div class="xl:col-span-8 space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
              <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                  <Building2 class="w-5 h-5 text-indigo-600" />
                  Business Core Identity
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Fill out physical address details, tax IDs, and billing contacts.</p>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 border-t border-slate-100 pt-5">
                <div class="sm:col-span-2">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Company Name <span class="text-rose-500">*</span></label>
                  <input v-model="form.name" type="text" required class="block w-full px-4 py-3 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none transition-all placeholder-slate-400 text-xs font-semibold" placeholder="E.g., Acme Solutions Ltd." />
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Official Email Address <span class="text-rose-500">*</span></label>
                  <input v-model="form.email" type="email" required class="block w-full px-4 py-3 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none transition-all placeholder-slate-400 text-xs font-semibold" placeholder="support@acme.com" />
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Contact Phone Number</label>
                  <input v-model="form.phone" type="text" class="block w-full px-4 py-3 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none transition-all placeholder-slate-400 text-xs font-semibold" placeholder="+880 17XX-XXXXXX" />
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">GST/Business Tax Registration ID</label>
                  <input v-model="form.gst_no" type="text" class="block w-full px-4 py-3 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none transition-all placeholder-slate-400 text-xs font-semibold" placeholder="E.g., 15-character Trade Tax Identification" />
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Corporate Office Address</label>
                  <textarea v-model="form.address" rows="3" class="block w-full px-4 py-3 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none transition-all placeholder-slate-400 text-xs font-semibold resize-none" placeholder="Provide complete billing/office postal details"></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Document Uploads sidebar -->
          <div class="xl:col-span-4 space-y-6">
            <!-- Logo Card -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex flex-col items-center">
              <h3 class="text-xs font-bold text-slate-900 self-start border-b border-slate-100 pb-3 mb-4 w-full flex items-center gap-1.5">
                <Palette class="w-4 h-4 text-indigo-500" /> Branding Logo
              </h3>
              
              <div class="relative w-32 h-32 rounded-2xl border border-slate-200 bg-slate-50/50 flex items-center justify-center overflow-hidden mb-4 group shadow-inner">
                <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-contain p-2" />
                <Building2 v-else class="w-12 h-12 text-slate-300" />
                
                <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                  <Upload class="w-5 h-5 text-white" />
                </div>
              </div>
              
              <div class="w-full">
                <label class="block w-full py-2.5 px-4 bg-indigo-50 hover:bg-indigo-100 border border-indigo-100 text-indigo-700 font-bold rounded-xl text-center cursor-pointer transition-colors text-[10px] shadow-sm">
                  <Upload class="w-3.5 h-3.5 inline-block mr-1 text-indigo-600" />
                  Upload Square Logo
                  <input type="file" @change="handleLogoUpload" accept="image/*" class="hidden" />
                </label>
                <p class="text-[9px] text-slate-400 text-center mt-2">Recommended: Standard PNG/JPG (Max 2MB)</p>
              </div>
            </div>

            <!-- Verification Card -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
              <h3 class="text-xs font-bold text-slate-900 border-b border-slate-100 pb-3 mb-4 w-full flex items-center gap-1.5">
                <FileText class="w-4 h-4 text-indigo-500" /> Trade License File
              </h3>
              
              <div class="bg-slate-50 rounded-2xl p-4 border border-dashed border-slate-200 flex flex-col items-center justify-center text-center min-h-[120px]">
                <FileText class="w-7 h-7 text-indigo-400 mb-2" />
                
                <div v-if="tradeLicenseName" class="w-full">
                  <p class="text-[10px] font-bold text-slate-700 truncate px-2" :title="tradeLicenseName">{{ tradeLicenseName }}</p>
                  
                  <a v-if="authStore.user?.company?.trade_license" :href="`http://localhost:8000/storage/${authStore.user.company.trade_license}`" target="_blank" class="inline-flex items-center gap-1 mt-2 text-[9px] font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-lg border border-indigo-100 transition-colors">
                    <Download class="w-3 h-3" />
                    Download File
                  </a>
                </div>
                
                <div v-else>
                  <p class="text-[10px] font-bold text-slate-600">No Document Uploaded</p>
                  <p class="text-[9px] text-slate-400 mt-0.5">Required for corporate verification</p>
                </div>
              </div>
              
              <div class="w-full mt-4">
                <label class="block w-full py-2.5 px-4 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-bold rounded-xl text-center cursor-pointer transition-colors text-[10px] shadow-sm">
                  <Upload class="w-3.5 h-3.5 inline-block mr-1 text-slate-500" />
                  Select Document File
                  <input type="file" @change="handleTradeLicenseUpload" accept="image/*,application/pdf" class="hidden" />
                </label>
                <p class="text-[9px] text-slate-400 text-center mt-2">PDF, JPG, or PNG (Max 5MB)</p>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. EMPLOYEE ID PATTERN TAB -->
        <div v-if="activeTab === 'id_pattern'" class="grid grid-cols-1 xl:grid-cols-12 gap-8 animate-fade-in">
          <!-- Editor inputs -->
          <div class="xl:col-span-7 space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
              <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                  <ShieldCheck class="w-5 h-5 text-indigo-600" />
                  Employee ID Pattern Setup
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Customize prefix codes and sequential digits when generating system IDs.</p>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 border-t border-slate-100 pt-5">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">ID Prefix / Standard Code</label>
                  <input v-model="form.emp_id_prefix" type="text" class="block w-full px-4 py-3 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none transition-all placeholder-slate-400 text-xs font-bold" placeholder="E.g., EMP-, RGET-" />
                  <p class="text-[9px] text-slate-400 mt-1 font-medium">Typically contains capital letters and a dash.</p>
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Sequential Padding Length</label>
                  <select v-model.number="form.emp_id_padding" class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none transition-all text-xs font-bold">
                    <option :value="2">2 Digits (e.g. 01)</option>
                    <option :value="3">3 Digits (e.g. 001)</option>
                    <option :value="4">4 Digits (e.g. 0001)</option>
                    <option :value="5">5 Digits (e.g. 00001)</option>
                    <option :value="6">6 Digits (e.g. 000001)</option>
                  </select>
                  <p class="text-[9px] text-slate-400 mt-1 font-medium">Controls the length of number placeholders.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Preview Mockup -->
          <div class="xl:col-span-5 flex flex-col justify-center">
            <div class="bg-slate-900 rounded-3xl p-6 border border-slate-800 text-white shadow-xl flex flex-col items-center justify-center text-center">
              <span class="text-[8px] font-bold tracking-widest text-indigo-400 uppercase bg-indigo-950/70 border border-indigo-900 px-3 py-1 rounded-full mb-3">Live Generation Engine</span>
              <p class="text-xs text-slate-400 font-medium">Standard Employee Code Preview</p>
              
              <div class="mt-4 px-6 py-4 bg-slate-800/80 border border-slate-700 rounded-2xl shadow-inner font-mono text-2xl tracking-widest text-indigo-300 font-black">
                {{ form.emp_id_prefix }}{{ '1'.padStart(form.emp_id_padding, '0') }}
              </div>

              <div class="mt-5 w-full flex items-center justify-between text-[9px] font-mono text-slate-400 border-t border-slate-800 pt-4 px-2">
                <span>Seq Start: {{ form.emp_id_prefix }}{{ '1'.padStart(form.emp_id_padding, '0') }}</span>
                <span>Seq Next: {{ form.emp_id_prefix }}{{ '2'.padStart(form.emp_id_padding, '0') }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. ID CARD DESIGNER TAB -->
        <div v-if="activeTab === 'id_card'" class="grid grid-cols-1 xl:grid-cols-12 gap-8 animate-fade-in">
          <!-- Editor inputs -->
          <div class="xl:col-span-7 space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
              <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                  <CreditCard class="w-5 h-5 text-indigo-600" />
                  ID Card Branding Setup
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Adjust custom theme colors, orientation, barcodes, and chip parameters.</p>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 border-t border-slate-100 pt-5">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Card Orientation Layout</label>
                  <div class="grid grid-cols-2 gap-2">
                    <button type="button" @click="settings.id_card.layout_type = 'portrait'" :class="settings.id_card.layout_type === 'portrait' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-700 font-bold' : 'border-slate-200 text-slate-600'" class="py-2.5 border rounded-xl text-xs transition-all font-semibold">Portrait</button>
                    <button type="button" @click="settings.id_card.layout_type = 'landscape'" :class="settings.id_card.layout_type === 'landscape' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-700 font-bold' : 'border-slate-200 text-slate-600'" class="py-2.5 border rounded-xl text-xs transition-all font-semibold">Landscape</button>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Card Border Style</label>
                  <select v-model="settings.id_card.border_radius" class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-bold">
                    <option value="none">Sharp edges (none)</option>
                    <option value="lg">Soft Rounded (lg)</option>
                    <option value="2xl">Deep Curved (2xl)</option>
                    <option value="3xl">Premium Capsule (3xl)</option>
                  </select>
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Theme Primary Accent Color</label>
                  <div class="flex gap-2 items-center">
                    <input v-model="settings.id_card.theme_color" type="color" class="w-10 h-10 border border-slate-200 rounded-xl bg-transparent p-0 cursor-pointer shrink-0" />
                    <input v-model="settings.id_card.theme_color" type="text" class="block w-full px-3 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 text-xs font-bold font-mono outline-none" />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Text & Detail Color</label>
                  <div class="flex gap-2 items-center">
                    <input v-model="settings.id_card.text_color" type="color" class="w-10 h-10 border border-slate-200 rounded-xl bg-transparent p-0 cursor-pointer shrink-0" />
                    <input v-model="settings.id_card.text_color" type="text" class="block w-full px-3 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 text-xs font-bold font-mono outline-none" />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">BG Gradient Start</label>
                  <div class="flex gap-2 items-center">
                    <input v-model="settings.id_card.bg_gradient_start" type="color" class="w-10 h-10 border border-slate-200 rounded-xl bg-transparent p-0 cursor-pointer shrink-0" />
                    <input v-model="settings.id_card.bg_gradient_start" type="text" class="block w-full px-3 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 text-xs font-bold font-mono outline-none" />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">BG Gradient End</label>
                  <div class="flex gap-2 items-center">
                    <input v-model="settings.id_card.bg_gradient_end" type="color" class="w-10 h-10 border border-slate-200 rounded-xl bg-transparent p-0 cursor-pointer shrink-0" />
                    <input v-model="settings.id_card.bg_gradient_end" type="text" class="block w-full px-3 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 text-xs font-bold font-mono outline-none" />
                  </div>
                </div>

                <!-- Typography Font Selection -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Card Typography Font</label>
                  <select v-model="settings.id_card.font_style" class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-bold">
                    <option value="sans-serif">Modern Sans-Serif (Default)</option>
                    <option value="monospace font-mono">Minimal Monospace (Tech)</option>
                    <option value="serif font-serif">Corporate Serif (Classic)</option>
                    <option value="cursive">Elegant Script Handwriting</option>
                  </select>
                </div>

                <!-- Card Glow style -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Physical Glow Aura</label>
                  <select v-model="settings.id_card.card_glow" class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-bold">
                    <option value="none">Standard shadow (none)</option>
                    <option value="soft-shadow">Deep Soft Shadow</option>
                    <option value="indigo-glow">Vibrant Blue Glow</option>
                    <option value="golden-glow">Luxury Amber Gold Glow</option>
                  </select>
                </div>

                <!-- Custom Outline Border Width -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Card Outline Thickness</label>
                  <select v-model="settings.id_card.border_width" class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-bold">
                    <option value="0px">No Border Outline (0px)</option>
                    <option value="2px">Slim Outline (2px)</option>
                    <option value="4px">Standard Outline (4px)</option>
                    <option value="6px">Intense Thick Outline (6px)</option>
                  </select>
                </div>

                <!-- Custom Outline Border Color Picker -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Card Outline Color</label>
                  <div class="flex gap-2 items-center">
                    <input v-model="settings.id_card.border_color" type="color" class="w-10 h-10 border border-slate-200 rounded-xl bg-transparent p-0 cursor-pointer shrink-0" />
                    <input v-model="settings.id_card.border_color" type="text" class="block w-full px-3 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 text-xs font-bold font-mono outline-none" />
                  </div>
                </div>

                <!-- Logo Watermark transparency -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Background Logo Watermark Opacity</label>
                  <div class="flex gap-3 items-center pt-2">
                    <input v-model="settings.id_card.watermark_opacity" type="range" min="0" max="0.5" step="0.05" class="grow h-1.5 bg-slate-250 rounded-lg appearance-none cursor-pointer accent-indigo-650" />
                    <span class="text-xs font-mono font-bold text-slate-500 shrink-0 w-8 text-right">{{ Math.round(settings.id_card.watermark_opacity * 100) }}%</span>
                  </div>
                </div>

                <!-- Header Accent Overlay Height Preset -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Decorative Header Height</label>
                  <select v-model="settings.id_card.header_accent_height" class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-bold">
                    <option value="small">Slim Strip Accent</option>
                    <option value="medium">Standard Curved Accent</option>
                    <option value="large">Deep Header Banner Overlay</option>
                    <option value="full">Unified Gradient Background (none)</option>
                  </select>
                </div>

                <!-- Custom Profile Photo Size Selection -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Profile Photo Dimensions</label>
                  <select v-model="settings.id_card.photo_size" class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-bold">
                    <option value="small">Compact & Petite (w-16 h-16)</option>
                    <option value="medium">Standard Corporate (w-20 h-20)</option>
                    <option value="large">Enlarged Spotlight (w-24 h-24)</option>
                  </select>
                </div>

                <!-- Custom Profile Photo Shape -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Profile Photo Shape</label>
                  <select v-model="settings.id_card.photo_shape" class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-bold">
                    <option value="rounded-none">Sharp Square (rounded-none)</option>
                    <option value="rounded-xl">Sleek Rounded (rounded-xl)</option>
                    <option value="rounded-2xl">Modern Soft Curved (rounded-2xl)</option>
                    <option value="rounded-full">Perfect Rounded Circle (rounded-full)</option>
                  </select>
                </div>

                <!-- Custom Profile Alignment Position -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Photo & Details Alignment</label>
                  <div class="flex gap-2">
                    <select v-model="settings.id_card.photo_position" class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-bold grow">
                      <option value="left">Left Aligned Placement</option>
                      <option value="center">Centered Balance Alignment</option>
                      <option value="right">Right Aligned Placement</option>
                    </select>
                    <button type="button" @click="resetPhotoPosition" title="Reset dragged position coordinates to 0" class="px-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl border border-slate-200 transition-colors flex items-center justify-center shrink-0">
                      <RefreshCw class="w-4 h-4" />
                    </button>
                  </div>
                </div>

                <div class="sm:col-span-2 grid grid-cols-3 gap-4 border-t border-slate-100 pt-4">
                  <label class="flex items-center gap-3 cursor-pointer p-3 rounded-2xl bg-slate-50/50 border border-slate-150 hover:bg-slate-50">
                    <input type="checkbox" v-model="settings.id_card.show_chip" class="rounded text-indigo-600 focus:ring-indigo-500 w-4.5 h-4.5" />
                    <div>
                      <p class="text-xs font-bold text-slate-700">Gold Smart Chip</p>
                      <p class="text-[9px] text-slate-400 font-medium">Sensor overlay</p>
                    </div>
                  </label>

                  <label class="flex items-center gap-3 cursor-pointer p-3 rounded-2xl bg-slate-50/50 border border-slate-150 hover:bg-slate-50">
                    <input type="checkbox" v-model="settings.id_card.show_barcode" class="rounded text-indigo-600 focus:ring-indigo-500 w-4.5 h-4.5" />
                    <div>
                      <p class="text-xs font-bold text-slate-700">Identity Barcode</p>
                      <p class="text-[9px] text-slate-400 font-medium">Security barcode</p>
                    </div>
                  </label>

                  <label class="flex items-center gap-3 cursor-pointer p-3 rounded-2xl bg-slate-50/50 border border-slate-150 hover:bg-slate-50">
                    <input type="checkbox" v-model="settings.id_card.show_hologram" class="rounded text-indigo-600 focus:ring-indigo-500 w-4.5 h-4.5" />
                    <div>
                      <p class="text-xs font-bold text-slate-700">Holographic Seal</p>
                      <p class="text-[9px] text-slate-400 font-medium">Metallic shield</p>
                    </div>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Preview Mockup -->
          <div class="xl:col-span-5 flex flex-col items-center justify-center space-y-4">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest self-center flex items-center gap-1.5"><Eye class="w-3.5 h-3.5" /> Real-time Live Badge preview</p>
            
            <div 
              :class="[
                settings.id_card.layout_type === 'portrait' ? 'w-60 h-[360px]' : 'w-[360px] h-60',
                settings.id_card.border_radius === 'none' ? 'rounded-none' : 
                settings.id_card.border_radius === 'lg' ? 'rounded-xl' : 
                settings.id_card.border_radius === '2xl' ? 'rounded-[2rem]' : 'rounded-[2.8rem]',
                settings.id_card.font_style
              ]"
              :style="{
                background: `linear-gradient(135deg, ${settings.id_card.bg_gradient_start}, ${settings.id_card.bg_gradient_end})`,
                color: settings.id_card.text_color,
                borderColor: settings.id_card.border_color,
                borderWidth: settings.id_card.border_width,
                boxShadow: settings.id_card.card_glow === 'indigo-glow' ? `0 25px 50px -12px ${settings.id_card.theme_color}65` :
                           settings.id_card.card_glow === 'golden-glow' ? '0 25px 50px -12px rgba(245, 158, 11, 0.55)' :
                           settings.id_card.card_glow === 'soft-shadow' ? '0 30px 60px -15px rgba(0, 0, 0, 0.35)' : ''
              }"
              class="bg-white relative p-5 flex flex-col justify-between overflow-hidden transition-all duration-300 select-none"
            >
              <!-- Dynamic Header Banner Background Overlay -->
              <div 
                v-if="settings.id_card.header_accent_height !== 'full'" 
                :class="[
                  settings.id_card.header_accent_height === 'small' ? 'h-3' : 
                  settings.id_card.header_accent_height === 'medium' ? 'h-14 rounded-b-[2rem]' : 'h-24 rounded-b-[1rem]'
                ]"
                :style="{ backgroundColor: `${settings.id_card.theme_color}15` }"
                class="absolute top-0 left-0 w-full pointer-events-none transition-all duration-300"
              ></div>

              <!-- Centered watermark logo logo -->
              <div 
                class="absolute inset-0 flex items-center justify-center pointer-events-none transition-all duration-300" 
                :style="{ opacity: settings.id_card.watermark_opacity }"
              >
                <img v-if="logoPreview" :src="logoPreview" class="w-24 h-24 object-contain filter grayscale" />
                <Building2 v-else class="w-24 h-24 text-slate-400" />
              </div>

              <!-- Card Header -->
              <div class="flex items-center justify-between border-b pb-2 z-10" :style="{ borderColor: `${settings.id_card.theme_color}30` }">
                <div class="flex items-center gap-2">
                  <img v-if="logoPreview" :src="logoPreview" class="w-6 h-6 object-contain" />
                  <Building2 v-else class="w-6 h-6" :style="{ color: settings.id_card.theme_color }" />
                  <p class="text-[9px] font-extrabold uppercase tracking-wide truncate max-w-[120px]">{{ form.name || 'ACME CORP' }}</p>
                </div>
                <span class="text-[8px] font-black tracking-widest opacity-60">MEMBER</span>
              </div>

              <!-- Card Body Content Layout depending on orientation -->
              <div 
                :class="[
                  settings.id_card.layout_type === 'portrait' ? 'flex-col mt-4' : (settings.id_card.photo_position === 'right' ? 'flex-row-reverse text-right' : 'flex-row') + ' items-center gap-4'
                ]" 
                :style="{
                  alignItems: settings.id_card.layout_type === 'portrait' ? (settings.id_card.photo_position === 'left' ? 'flex-start' : settings.id_card.photo_position === 'right' ? 'flex-end' : 'center') : 'center',
                  textAlign: settings.id_card.layout_type === 'portrait' ? settings.id_card.photo_position : ''
                }"
                class="flex grow justify-center z-10 min-w-0"
              >
                <!-- Photo Avatar placeholder -->
                <div 
                  @mousedown="startPhotoDrag"
                  @touchstart="startPhotoDrag"
                  class="relative shrink-0 flex items-center justify-center border-2 bg-slate-100 overflow-hidden shadow-md cursor-move select-none active:scale-105 active:shadow-lg transition-transform duration-75" 
                  :class="[
                    settings.id_card.layout_type === 'portrait' ? 
                      (settings.id_card.photo_size === 'small' ? 'w-16 h-16 mb-2' : settings.id_card.photo_size === 'large' ? 'w-24 h-24 mb-4' : 'w-20 h-20 mb-3') : 
                      (settings.id_card.photo_size === 'small' ? 'w-16 h-16' : settings.id_card.photo_size === 'large' ? 'w-24 h-24' : 'w-20 h-20'),
                    settings.id_card.layout_type === 'portrait' ? 
                      (settings.id_card.photo_position === 'left' ? 'ml-2' : settings.id_card.photo_position === 'right' ? 'mr-2' : 'mx-auto') : 'mx-0',
                    settings.id_card.photo_shape
                  ]"
                  :style="{ 
                    borderColor: settings.id_card.theme_color,
                    transform: `translate(${settings.id_card.photo_x || 0}px, ${settings.id_card.photo_y || 0}px)`
                  }"
                >
                  <span class="text-xs font-bold text-slate-400">Photo</span>
                  <div v-if="settings.id_card.show_chip" class="absolute -bottom-0.5 -right-0.5 bg-gradient-to-tr from-yellow-300 to-yellow-500 border border-yellow-600 rounded-sm w-5 h-4 flex flex-col justify-between p-0.5 shadow-sm">
                    <div class="flex justify-between w-full h-[1.5px] border-b border-yellow-700/35"></div>
                    <div class="flex justify-between w-full h-[1.5px] border-b border-yellow-700/35"></div>
                  </div>
                </div>

                <!-- Text Details -->
                <div class="min-w-0" :class="settings.id_card.layout_type === 'portrait' ? 'w-full' : 'grow'">
                  <h4 class="text-xs font-black truncate">{{ mockEmployee.name }}</h4>
                  <p class="text-[9px] font-bold mt-0.5 truncate opacity-75" :style="{ color: settings.id_card.theme_color }">{{ mockEmployee.designation }}</p>
                  
                  <div class="mt-2.5 inline-block px-3 py-0.5 rounded-full text-[8px] font-black font-mono tracking-wider text-white" :style="{ backgroundColor: settings.id_card.theme_color }">
                    ID: {{ form.emp_id_prefix }}{{ '142'.padStart(form.emp_id_padding, '0') }}
                  </div>
                </div>
              </div>

              <!-- Hologram seal seal graphic -->
              <div v-if="settings.id_card.show_hologram" class="absolute bottom-16 right-5 w-8 h-8 rounded-full border border-yellow-400/40 bg-gradient-to-tr from-yellow-300 via-amber-200 to-yellow-500 shadow flex items-center justify-center overflow-hidden animate-pulse z-10">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-indigo-300/30 via-pink-300/30 to-yellow-300/30 mix-blend-color-dodge"></div>
                <Sparkles class="w-4 h-4 text-yellow-600/80 drop-shadow-sm" />
              </div>

              <!-- Barcode Mockup -->
              <div v-if="settings.id_card.show_barcode" class="w-full flex flex-col items-center border-t pt-2 z-10" :style="{ borderColor: `${settings.id_card.theme_color}20` }">
                <!-- CSS generated bars -->
                <div class="h-6 flex items-end gap-[1.5px] overflow-hidden opacity-85">
                  <div v-for="i in 28" :key="i" :class="i % 3 === 0 ? 'w-[2.5px] h-full' : i % 5 === 0 ? 'w-[0.8px] h-3/4' : 'w-[1.2px] h-5/6'" class="bg-slate-900 rounded-sm"></div>
                </div>
                <span class="text-[7px] font-mono tracking-widest mt-0.5 font-bold opacity-60">1029485736209</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. OFFER LETTER BUILDER TAB -->
        <div v-if="activeTab === 'offer_letter'" class="grid grid-cols-1 xl:grid-cols-12 gap-8 animate-fade-in">
          <!-- Editor inputs -->
          <div class="xl:col-span-7 space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
              <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                  <FileSignature class="w-5 h-5 text-indigo-600" />
                  Dynamic Offer Letter Editor
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Customize letter text templates and insert quick placeholders for hires.</p>
              </div>

              <div class="space-y-4 border-t border-slate-100 pt-5">
                <!-- Variable Helpers -->
                <div class="p-3 bg-slate-50 border border-slate-150 rounded-2xl">
                  <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 flex items-center gap-1"><Sparkles class="w-3 h-3 text-indigo-500" /> Available Dynamic Placeholders</p>
                  <div class="flex flex-wrap gap-1.5">
                    <span class="text-[8px] font-mono font-bold bg-white border px-2 py-0.5 rounded-lg text-indigo-600" title="Will replace with candidate name">{employee_name}</span>
                    <span class="text-[8px] font-mono font-bold bg-white border px-2 py-0.5 rounded-lg text-indigo-600" title="Will replace with company name">{company_name}</span>
                    <span class="text-[8px] font-mono font-bold bg-white border px-2 py-0.5 rounded-lg text-indigo-600" title="Will replace with official designation">{designation}</span>
                    <span class="text-[8px] font-mono font-bold bg-white border px-2 py-0.5 rounded-lg text-indigo-600" title="Will replace with joining date">{joining_date}</span>
                    <span class="text-[8px] font-mono font-bold bg-white border px-2 py-0.5 rounded-lg text-indigo-600" title="Will replace with CTC salary">{salary}</span>
                    <span class="text-[8px] font-mono font-bold bg-white border px-2 py-0.5 rounded-lg text-indigo-600" title="Will replace with probation period">{probation_period}</span>
                    <span class="text-[8px] font-mono font-bold bg-white border px-2 py-0.5 rounded-lg text-indigo-600" title="Will replace with signer name">{signer_name}</span>
                    <span class="text-[8px] font-mono font-bold bg-white border px-2 py-0.5 rounded-lg text-indigo-600" title="Will replace with signer role">{signer_title}</span>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Template Letterhead Document Body</label>
                  <textarea v-model="settings.offer_letter.template_body" rows="12" class="block w-full px-4 py-3 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-semibold resize-y font-mono leading-relaxed" placeholder="Type document body text here..."></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Signatory Representative Name</label>
                    <input v-model="settings.offer_letter.signer_name" type="text" class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-semibold" placeholder="Sarah Rahman" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Signatory Corporate Title</label>
                    <input v-model="settings.offer_letter.signer_title" type="text" class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-semibold" placeholder="Human Resources Director" />
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4">
                  <label class="flex items-center gap-3 cursor-pointer p-3 rounded-2xl bg-slate-50/50 border border-slate-150">
                    <input type="checkbox" v-model="settings.offer_letter.show_header" class="rounded text-indigo-600 focus:ring-indigo-500 w-4.5 h-4.5" />
                    <div>
                      <p class="text-xs font-bold text-slate-700">Show Company Header</p>
                      <p class="text-[9px] text-slate-400 font-medium">Adds official branding banner at top</p>
                    </div>
                  </label>

                  <label class="flex items-center gap-3 cursor-pointer p-3 rounded-2xl bg-slate-50/50 border border-slate-150">
                    <input type="checkbox" v-model="settings.offer_letter.show_signature" class="rounded text-indigo-600 focus:ring-indigo-500 w-4.5 h-4.5" />
                    <div>
                      <p class="text-xs font-bold text-slate-700">Display Signature Section</p>
                      <p class="text-[9px] text-slate-400 font-medium">Renders representative sign-off at bottom</p>
                    </div>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Preview Mockup -->
          <div class="xl:col-span-5 flex flex-col items-center justify-start space-y-4">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1.5"><Eye class="w-3.5 h-3.5" /> Live A4 Letterhead Preview</p>
            
            <div class="w-full aspect-[1/1.41] shadow-2xl rounded-sm border border-slate-200 bg-white p-6 relative overflow-hidden select-none text-[8px] leading-relaxed text-slate-700 flex flex-col justify-between">
              <div>
                <!-- Company Letterhead Top -->
                <div v-if="settings.offer_letter.show_header" class="border-b-2 border-indigo-600 pb-3 flex items-center justify-between mb-4">
                  <div class="flex items-center gap-1.5">
                    <img v-if="logoPreview" :src="logoPreview" class="w-5 h-5 object-contain" />
                    <Building2 v-else class="w-5 h-5 text-indigo-600" />
                    <div>
                      <h4 class="text-[9px] font-extrabold uppercase leading-none tracking-tight">{{ form.name || 'ACME CORP' }}</h4>
                      <p class="text-[6px] text-slate-400 mt-0.5">{{ form.address || 'Dhaka, Bangladesh' }}</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-[6px] text-slate-400">Tel: {{ form.phone || '+880-XXXX-XXXX' }}</p>
                    <p class="text-[6px] text-slate-400">{{ form.email || 'info@acme.com' }}</p>
                  </div>
                </div>

                <!-- Letter Body -->
                <div class="text-[7px] text-slate-600 font-medium space-y-2 whitespace-pre-line" v-html="parsedOfferLetter"></div>
              </div>

              <!-- Sign-off Section -->
              <div v-if="settings.offer_letter.show_signature" class="mt-6 border-t pt-3 flex justify-between items-end border-slate-100">
                <div>
                  <p class="text-[5px] text-slate-400">Representative Authorization</p>
                  <!-- Beautiful mock digital ink signature -->
                  <div class="my-1.5 font-serif text-indigo-600 text-xs italic tracking-widest opacity-80 leading-none">
                    {{ settings.offer_letter.signer_name }}
                  </div>
                  <p class="font-bold text-[6px] text-slate-800 leading-none">{{ settings.offer_letter.signer_name }}</p>
                  <p class="text-[5px] text-slate-400 leading-none mt-0.5">{{ settings.offer_letter.signer_title }}</p>
                </div>
                
                <div class="text-center border border-dashed border-slate-200 px-4 py-2 bg-slate-50/50 rounded">
                  <p class="text-[5px] text-slate-400">Candidate Acceptance</p>
                  <div class="h-4"></div>
                  <p class="text-[5px] text-slate-500 font-bold">Signature / Date</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 5. PAYSLIP DESIGNER TAB -->
        <div v-if="activeTab === 'payslip'" class="grid grid-cols-1 xl:grid-cols-12 gap-8 animate-fade-in">
          <!-- Editor inputs -->
          <div class="xl:col-span-7 space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
              <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                  <Receipt class="w-5 h-5 text-indigo-600" />
                  Salary Payslip Receipt Parameters
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Control earning allowances, standard bank details visibility, and footer legal statements.</p>
              </div>

              <div class="space-y-6 border-t border-slate-100 pt-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Payslip Heading Title</label>
                    <input v-model="settings.payslip.header_title" type="text" class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-semibold" placeholder="Salary Payslip Receipt" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Disbursement Bank Name</label>
                    <input v-model="settings.payslip.bank_name" type="text" class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-semibold" placeholder="Standard Chartered Bank" />
                  </div>
                  <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Account Reference Label</label>
                    <input v-model="settings.payslip.bank_account_label" type="text" class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-semibold" placeholder="A/C: XXXX-XXXX-4567" />
                  </div>
                </div>

                <!-- Earnings Allowances Manager -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-150">
                  <h4 class="text-xs font-bold text-slate-800 mb-3 flex items-center gap-1.5"><Palette class="w-4 h-4 text-emerald-500" /> Standard Earnings Allowances</h4>
                  
                  <div class="space-y-2 max-h-44 overflow-y-auto mb-3">
                    <div v-for="allow in settings.payslip.allowances" :key="allow.id" class="flex items-center justify-between bg-white border border-slate-150 rounded-xl p-2.5 shadow-2xs">
                      <span class="text-xs font-bold text-slate-700">{{ allow.name }}</span>
                      <div class="flex items-center gap-3">
                        <span class="text-xs font-semibold text-slate-500 bg-slate-50 border px-2 py-0.5 rounded">{{ allow.amount }}</span>
                        <button type="button" @click="removeAllowance(allow.id)" class="text-rose-500 hover:bg-rose-50 p-1.5 rounded-lg"><Trash2 class="w-3.5 h-3.5" /></button>
                      </div>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <input v-model="newAllowanceName" type="text" placeholder="Allowance (e.g., LTA)" class="sm:col-span-2 block w-full px-3 py-2 bg-white border border-slate-250 rounded-xl text-xs outline-none" />
                    <div class="flex gap-2">
                      <input v-model="newAllowanceAmount" type="text" placeholder="Amount (e.g. 5%)" class="block w-full px-3 py-2 bg-white border border-slate-250 rounded-xl text-xs outline-none" />
                      <button type="button" @click="addAllowance" class="px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl flex items-center justify-center font-bold text-sm">+</button>
                    </div>
                  </div>
                </div>

                <!-- Deductions Manager -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-150">
                  <h4 class="text-xs font-bold text-slate-800 mb-3 flex items-center gap-1.5"><Palette class="w-4 h-4 text-rose-500" /> Standard Deductions</h4>
                  
                  <div class="space-y-2 max-h-44 overflow-y-auto mb-3">
                    <div v-for="ded in settings.payslip.deductions" :key="ded.id" class="flex items-center justify-between bg-white border border-slate-150 rounded-xl p-2.5 shadow-2xs">
                      <span class="text-xs font-bold text-slate-700">{{ ded.name }}</span>
                      <div class="flex items-center gap-3">
                        <span class="text-xs font-semibold text-slate-500 bg-slate-50 border px-2 py-0.5 rounded">{{ ded.amount }}</span>
                        <button type="button" @click="removeDeduction(ded.id)" class="text-rose-500 hover:bg-rose-50 p-1.5 rounded-lg"><Trash2 class="w-3.5 h-3.5" /></button>
                      </div>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <input v-model="newDeductionName" type="text" placeholder="Deduction (e.g., PF)" class="sm:col-span-2 block w-full px-3 py-2 bg-white border border-slate-250 rounded-xl text-xs outline-none" />
                    <div class="flex gap-2">
                      <input v-model="newDeductionAmount" type="text" placeholder="Amount (e.g. 10%)" class="block w-full px-3 py-2 bg-white border border-slate-250 rounded-xl text-xs outline-none" />
                      <button type="button" @click="addDeduction" class="px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl flex items-center justify-center font-bold text-sm">+</button>
                    </div>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Verification Footer Note</label>
                  <textarea v-model="settings.payslip.footer_note" rows="2" class="block w-full px-4 py-3 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-semibold resize-none" placeholder="Enter declaration notes..."></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Preview Mockup -->
          <div class="xl:col-span-5 flex flex-col items-center justify-start space-y-4">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1.5"><Eye class="w-3.5 h-3.5" /> Dynamic Receipt invoice Preview</p>
            
            <div class="w-full shadow-2xl rounded-2xl border border-slate-250 bg-white p-5 select-none text-[8px] leading-relaxed text-slate-700 space-y-4">
              <!-- Header -->
              <div class="border-b pb-3 flex justify-between items-center border-slate-100">
                <div>
                  <h4 class="text-[10px] font-black text-slate-950 uppercase tracking-tight">{{ form.name || 'ACME CORP' }}</h4>
                  <p class="text-[6px] text-slate-400 leading-none mt-0.5">Corporate Office Payroll Board</p>
                </div>
                <div class="text-right">
                  <span class="text-[8px] font-extrabold text-indigo-700 uppercase bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-md">{{ settings.payslip.header_title || 'Salary slip' }}</span>
                </div>
              </div>

              <!-- Metadata -->
              <div class="grid grid-cols-2 gap-3 text-[6px] text-slate-500 font-medium">
                <div>
                  <p><span class="font-bold text-slate-800">Employee Name:</span> {{ mockEmployee.name }}</p>
                  <p class="mt-0.5"><span class="font-bold text-slate-800">Designation:</span> {{ mockEmployee.designation }}</p>
                  <p class="mt-0.5"><span class="font-bold text-slate-800">ID Code:</span> {{ mockEmployee.emp_id }}</p>
                </div>
                <div>
                  <p><span class="font-bold text-slate-800">Payout Period:</span> May 2026</p>
                  <p class="mt-0.5"><span class="font-bold text-slate-800">Working Days:</span> {{ mockEmployee.working_days }} Days</p>
                  <p class="mt-0.5" v-if="settings.payslip.show_bank_details">
                    <span class="font-bold text-slate-800">Bank:</span> {{ settings.payslip.bank_name }} ({{ settings.payslip.bank_account_label }})
                  </p>
                </div>
              </div>

              <!-- Table grids -->
              <div class="border rounded-lg overflow-hidden border-slate-150">
                <table class="w-full text-left text-[6px]">
                  <thead class="bg-slate-50 border-b border-slate-150 font-bold text-slate-800">
                    <tr>
                      <th class="p-1.5">Earnings Allowances</th>
                      <th class="p-1.5 text-right">Computed Allocation</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 font-medium">
                    <tr v-for="allow in settings.payslip.allowances" :key="allow.id">
                      <td class="p-1.5 text-slate-700">{{ allow.name }}</td>
                      <td class="p-1.5 text-right font-semibold text-slate-800">{{ allow.amount }}</td>
                    </tr>
                    <tr class="bg-emerald-50/30 text-emerald-800 font-bold border-t border-slate-200">
                      <td class="p-1.5">Total Gross Allowance Estimation</td>
                      <td class="p-1.5 text-right">{{ mockEmployee.allowance_payout }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="border rounded-lg overflow-hidden border-slate-150">
                <table class="w-full text-left text-[6px]">
                  <thead class="bg-slate-50 border-b border-slate-150 font-bold text-slate-800">
                    <tr>
                      <th class="p-1.5">Deductions Summary</th>
                      <th class="p-1.5 text-right">Debit Contribution</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 font-medium">
                    <tr v-for="ded in settings.payslip.deductions" :key="ded.id">
                      <td class="p-1.5 text-slate-700">{{ ded.name }}</td>
                      <td class="p-1.5 text-right font-semibold text-slate-800">{{ ded.amount }}</td>
                    </tr>
                    <tr class="bg-rose-50/30 text-rose-800 font-bold border-t border-slate-200">
                      <td class="p-1.5">Total Deductions Debit</td>
                      <td class="p-1.5 text-right">{{ mockEmployee.deduction_total }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Net Salary summary -->
              <div class="bg-slate-900 text-white rounded-xl p-3 flex justify-between items-center shadow-lg">
                <div>
                  <p class="text-[5px] text-slate-400 uppercase tracking-widest font-bold">Consolidated Net Disbursed</p>
                  <h4 class="text-xs font-black tracking-tight text-indigo-300">{{ mockEmployee.net_salary }}</h4>
                </div>
                <span class="text-[5px] font-extrabold bg-indigo-950 border border-indigo-900/60 px-2 py-0.5 rounded uppercase tracking-wider text-indigo-400">Disbursed successfully</span>
              </div>

              <!-- Footer -->
              <p class="text-[5px] text-slate-400 text-center font-semibold leading-none border-t border-slate-100 pt-2.5">
                {{ settings.payslip.footer_note }}
              </p>
            </div>
          </div>
        </div>

        <!-- 6. RESIGNATION LETTER TAB -->
        <div v-if="activeTab === 'resignation'" class="grid grid-cols-1 xl:grid-cols-12 gap-8 animate-fade-in">
          <!-- Editor inputs -->
          <div class="xl:col-span-7 space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
              <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                  <FileSymlink class="w-5 h-5 text-indigo-600" />
                  Exit Policy & Resignation Setup
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Control required notice period timelines and departmental clearance checklists.</p>
              </div>

              <div class="grid grid-cols-1 gap-5 border-t border-slate-100 pt-5">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Required Notice Period (Days)</label>
                  <select v-model.number="settings.resignation.notice_period_days" class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-bold">
                    <option :value="15">15 Days (Quick release)</option>
                    <option :value="30">30 Days (Standard 1-Month)</option>
                    <option :value="60">60 Days (Management / Senior)</option>
                    <option :value="90">90 Days (Executive level)</option>
                  </select>
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-0.5">Resignation Acknowledgement Body Template</label>
                  <textarea v-model="settings.resignation.exit_body_template" rows="9" class="block w-full px-4 py-3 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none text-xs font-semibold resize-y font-mono" placeholder="Letter of acknowledgement..."></textarea>
                  <p class="text-[9px] text-slate-400 mt-1 font-medium">Use placeholders: <span class="font-bold">{employee_name}</span>, <span class="font-bold">{designation}</span>, <span class="font-bold">{company_name}</span>, <span class="font-bold">{last_working_date}</span>.</p>
                </div>

                <!-- Clearance Checklist Item Manager -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-150">
                  <h4 class="text-xs font-bold text-slate-800 mb-3 flex items-center gap-1.5"><Palette class="w-4 h-4 text-indigo-500" /> Mandatory Exit Clearance Checklist</h4>
                  
                  <div class="space-y-1.5 mb-3 max-h-40 overflow-y-auto">
                    <div v-for="(item, idx) in settings.resignation.clearance_checklists" :key="idx" class="flex items-center justify-between bg-white border border-slate-150 rounded-xl p-2.5 shadow-2xs">
                      <span class="text-xs font-bold text-slate-700 flex items-center gap-2"><Check class="w-3.5 h-3.5 text-indigo-500 shrink-0" /> {{ item }}</span>
                      <button type="button" @click="removeClearance(idx)" class="text-rose-500 hover:bg-rose-50 p-1.5 rounded-lg"><Trash2 class="w-3.5 h-3.5" /></button>
                    </div>
                  </div>

                  <div class="flex gap-2">
                    <input v-model="newClearanceItem" type="text" placeholder="Add clearance department/item (e.g. IT Desk)" class="block w-full px-3 py-2 bg-white border border-slate-250 rounded-xl text-xs outline-none" />
                    <button type="button" @click="addClearance" class="px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl flex items-center justify-center font-bold text-sm">+</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Preview Mockup -->
          <div class="xl:col-span-5 flex flex-col items-center justify-start space-y-4">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1.5"><Eye class="w-3.5 h-3.5" /> Exit Approval Letter Preview</p>
            
            <div class="w-full shadow-2xl rounded-2xl border border-slate-200 bg-white p-5 select-none text-[8px] leading-relaxed text-slate-700 space-y-4">
              <div class="border-b pb-3 flex justify-between items-center border-slate-100">
                <div>
                  <h4 class="text-[9px] font-black uppercase text-slate-900 tracking-tight">{{ form.name || 'ACME CORP' }}</h4>
                  <span class="text-[6px] text-slate-400">Release Acknowledgement Protocol</span>
                </div>
                <span class="text-[5px] font-bold text-indigo-600 bg-indigo-50 border border-indigo-150 px-2 py-0.5 rounded">Exit Approval</span>
              </div>

              <!-- Main Document text -->
              <div class="text-[7.5px] text-slate-600 space-y-2 whitespace-pre-line font-medium leading-relaxed" v-html="parsedResignationLetter"></div>

              <!-- Checklists panel -->
              <div class="bg-slate-50 border rounded-xl p-3 border-slate-150">
                <p class="text-[7px] font-bold text-slate-800 border-b pb-1 mb-2">Internal Handover Requirements</p>
                <div class="space-y-1">
                  <div v-for="(item, idx) in settings.resignation.clearance_checklists" :key="idx" class="flex items-center gap-1 text-[6.5px] text-slate-600 font-medium">
                    <div class="w-1.5 h-1.5 rounded-full bg-indigo-500"></div>
                    <span>{{ item }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 7. FESTIVAL MESSAGE SETUP TAB -->
        <div v-if="activeTab === 'festival'" class="grid grid-cols-1 xl:grid-cols-12 gap-8 animate-fade-in">
          <!-- Editor inputs -->
          <div class="xl:col-span-7 space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
              <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                  <PartyPopper class="w-5 h-5 text-indigo-600" />
                  Dashboard Festival Wishes Setup
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Toggle active holidays and draft custom greetings for employee announcement boards.</p>
              </div>

              <div class="space-y-6 border-t border-slate-100 pt-5">
                <div v-for="fest in settings.festival.festivals" :key="fest.id" class="p-4 bg-slate-50/50 rounded-2xl border border-slate-200/80 hover:border-slate-350 transition-all space-y-4">
                  <!-- Header switch -->
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                      <div :class="[
                        fest.banner_theme === 'emerald' ? 'bg-emerald-100 text-emerald-700' :
                        fest.banner_theme === 'orange' ? 'bg-orange-100 text-orange-700' :
                        fest.banner_theme === 'yellow' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700'
                      ]" class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs">
                        🎉
                      </div>
                      <h4 class="text-xs font-extrabold text-slate-800">{{ fest.name }}</h4>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" v-model="fest.enabled" class="sr-only peer" />
                      <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:height-4 after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                      <span class="ml-2 text-[10px] font-bold text-slate-600 select-none">{{ fest.enabled ? 'Broadcast On' : 'Broadcast Off' }}</span>
                    </label>
                  </div>

                  <!-- Details (only if enabled) -->
                  <div v-if="fest.enabled" class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-3 border-t border-slate-200/60 animate-fade-in">
                    <div class="sm:col-span-2">
                      <label class="block text-[10px] font-bold text-slate-500 mb-1">Greeting Title</label>
                      <input v-model="fest.title" type="text" class="block w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-xs font-bold outline-none" />
                    </div>
                    <div class="sm:col-span-2">
                      <label class="block text-[10px] font-bold text-slate-500 mb-1">Greeting Message Body</label>
                      <textarea v-model="fest.message" rows="2.5" class="block w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-xs font-semibold outline-none resize-none"></textarea>
                    </div>
                    <div>
                      <label class="block text-[10px] font-bold text-slate-500 mb-1">Banner Accent Theme</label>
                      <select v-model="fest.banner_theme" class="block w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold">
                        <option value="emerald">Emerald Green (E.g. Eid)</option>
                        <option value="orange">Mandala Orange (E.g. Puja)</option>
                        <option value="yellow">Golden Amber (E.g. Diwali)</option>
                        <option value="indigo">Festive Indigo (E.g. New Year)</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Preview Mockup -->
          <div class="xl:col-span-5 flex flex-col items-center justify-start space-y-4">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1.5"><Eye class="w-3.5 h-3.5" /> Employee Dashboard Banner preview</p>
            
            <div class="space-y-4 w-full">
              <div v-for="fest in settings.festival.festivals.filter(f => f.enabled)" :key="fest.id" class="relative overflow-hidden rounded-2xl p-4 shadow-xl text-white select-none transition-all duration-300"
                :class="[
                  fest.banner_theme === 'emerald' ? 'bg-gradient-to-r from-emerald-600 via-teal-700 to-green-600' :
                  fest.banner_theme === 'orange' ? 'bg-gradient-to-r from-orange-500 via-amber-600 to-red-500' :
                  fest.banner_theme === 'yellow' ? 'bg-gradient-to-r from-yellow-500 via-amber-600 to-orange-500' :
                  'bg-gradient-to-r from-indigo-600 via-violet-700 to-purple-600'
                ]"
              >
                <!-- Sticker/Graphic overlay in background -->
                <div class="absolute right-[-10px] bottom-[-20px] text-7xl opacity-15 pointer-events-none transform rotate-12">
                  <span v-if="fest.banner_theme === 'emerald'">🕌</span>
                  <span v-else-if="fest.banner_theme === 'orange'">🕉️</span>
                  <span v-else-if="fest.banner_theme === 'yellow'">🪔</span>
                  <span v-else>🎆</span>
                </div>

                <div class="relative flex items-start justify-between">
                  <div class="space-y-1.5">
                    <span class="text-[7px] font-bold tracking-widest uppercase bg-white/20 border border-white/35 px-2 py-0.5 rounded">Dashboard greeting broadcast</span>
                    <h3 class="text-sm font-extrabold tracking-tight">{{ fest.title }}</h3>
                    <p class="text-[8px] text-white/90 font-medium leading-relaxed max-w-[200px]">{{ fest.message }}</p>
                  </div>

                  <span class="text-[6px] font-black uppercase bg-emerald-500 border border-emerald-400 px-2 py-0.5 rounded text-white shadow-sm flex items-center gap-1 shrink-0 mt-0.5">
                    <div class="w-1 h-1 rounded-full bg-white animate-ping"></div>
                    Active Live
                  </span>
                </div>
              </div>

              <div v-if="settings.festival.festivals.filter(f => f.enabled).length === 0" class="bg-slate-100 rounded-2xl border-2 border-dashed border-slate-300 p-6 text-center text-slate-400">
                <PartyPopper class="w-7 h-7 mx-auto text-slate-350 mb-2" />
                <p class="text-[9px] font-bold">No active holiday greeting broadcasts</p>
                <p class="text-[8px] mt-0.5">Enable an event switch on the left editor.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
