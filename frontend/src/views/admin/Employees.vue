<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAdminStore } from '../../stores/admin';
import { useAuthStore } from '../../stores/auth';
import { Users, Plus, Trash2, Edit2, Mail, Phone, Calendar, UploadCloud, ChevronRight, ChevronLeft, CheckCircle2, X, FileText, ExternalLink, IdCard, Printer } from 'lucide-vue-next';
import IdCardComponent from '../../components/IdCardComponent.vue';

const adminStore = useAdminStore();
const authStore = useAuthStore();
const showModal = ref(false);
const isEditing = ref(false);
const editId = ref(null);
const activeTab = ref(1);

// Profile View Modal States
const showProfileModal = ref(false);
const selectedEmployee = ref(null);
const profileTab = ref('Job & Account');

// ID Card Modal State
const showIdCardModal = ref(false);

const autoGenerateId = ref(true);

const nextEmployeeIdPreview = computed(() => {
  const prefix = authStore.user?.company?.emp_id_prefix ?? 'EMP-';
  const padding = parseInt(authStore.user?.company?.emp_id_padding ?? 4);
  const count = (adminStore.employees?.length ?? 0) + 1;
  return prefix + String(count).padStart(padding, '0');
});

const openIdCardModal = (emp) => {
  selectedEmployee.value = emp;
  showIdCardModal.value = true;
};

const openProfileModal = (emp) => {
  selectedEmployee.value = emp;
  profileTab.value = 'Job & Account';
  showProfileModal.value = true;
};

const editFromProfile = () => {
  if (selectedEmployee.value) {
    const emp = selectedEmployee.value;
    showProfileModal.value = false;
    openEditModal(emp);
  }
};

const tabs = [
  { id: 1, name: 'Account & Job' },
  { id: 2, name: 'Personal Info' },
  { id: 3, name: 'Payroll & Identity' },
  { id: 4, name: 'Edu & Experience' },
  { id: 5, name: 'Documents' }
];

// Flat form state
const form = ref({
  // Tab 1
  name: '', email: '', password: '', employee_id: '',
  department_id: '', designation_id: '', employment_type: '', join_date: '', leave_structure_id: '',
  
  // Tab 2
  dob: '', gender: '', phone: '',
  personal_details: { father_name: '', mother_name: '', marital_status: '', blood_group: '', current_address: '', permanent_address: '', emergency_contact_name: '', emergency_contact_phone: '' },
  
  // Tab 3
  salary: '', status: 'active',
  bank_details: { salary_type: '', bank_name: '', account_holder_name: '', bank_account_no: '', ifsc_code: '', uan_no: '', pf_applicable: false, esic_no: '', tds_applicable: false },
  identity_docs: { pan_no: '', aadhaar_no: '', biometric_id: '', rfid_number: '' },
  
  // Tab 4
  education_experience: { highest_qualification: '', college_university: '', passing_year: '', skills: '', prev_company_name: '', prev_designation: '', years_of_experience: '', last_salary: '' },
});

// File states
const files = ref({
  profile_photo: null, aadhaar_doc: null, pan_doc: null, other_gov_doc: null,
  resume: null, education_cert: null, experience_letter: null, signature: null, offer_letter: null, appointment_letter: null
});

const existingDocuments = ref({});
const getFileName = (path) => {
  if (!path) return '';
  return path.split('/').pop();
};

onMounted(async () => {
  await Promise.all([
    adminStore.fetchEmployees(),
    adminStore.fetchDepartments(),
    adminStore.fetchDesignations(),
    adminStore.fetchLeaveStructures()
  ]);
});

const openCreateModal = () => {
  isEditing.value = false;
  autoGenerateId.value = true;
  activeTab.value = 1;
  existingDocuments.value = {};
  
  // Reset form
  form.value = {
    name: '', email: '', password: '', employee_id: '',
    department_id: '', designation_id: '', employment_type: '', join_date: '', leave_structure_id: '',
    dob: '', gender: '', phone: '',
    personal_details: { father_name: '', mother_name: '', marital_status: '', blood_group: '', current_address: '', permanent_address: '', emergency_contact_name: '', emergency_contact_phone: '' },
    salary: '', status: 'active',
    bank_details: { salary_type: '', bank_name: '', account_holder_name: '', bank_account_no: '', ifsc_code: '', uan_no: '', pf_applicable: false, esic_no: '', tds_applicable: false },
    identity_docs: { pan_no: '', aadhaar_no: '', biometric_id: '', rfid_number: '' },
    education_experience: { highest_qualification: '', college_university: '', passing_year: '', skills: '', prev_company_name: '', prev_designation: '', years_of_experience: '', last_salary: '' },
  };
  
  // Reset files
  Object.keys(files.value).forEach(key => files.value[key] = null);
  
  showModal.value = true;
};

const handleFileUpload = (event, key) => {
  files.value[key] = event.target.files[0];
};

const nextTab = () => { if (activeTab.value < 5) activeTab.value++; };
const prevTab = () => { if (activeTab.value > 1) activeTab.value--; };

const handleSubmit = async () => {
  try {
    if (autoGenerateId.value && !isEditing.value) {
      form.value.employee_id = '';
    }
    const formData = new FormData();
    
    // Append top-level form fields
    const topLevelFields = ['name', 'email', 'password', 'employee_id', 'department_id', 'designation_id', 'leave_structure_id', 'employment_type', 'join_date', 'dob', 'gender', 'phone', 'salary', 'status'];
    topLevelFields.forEach(field => {
      if (form.value[field] !== null && form.value[field] !== '') {
        formData.append(field, form.value[field]);
      }
    });

    // Append JSON grouped fields
    formData.append('personal_details', JSON.stringify(form.value.personal_details));
    formData.append('bank_details', JSON.stringify(form.value.bank_details));
    formData.append('identity_docs', JSON.stringify(form.value.identity_docs));
    formData.append('education_experience', JSON.stringify(form.value.education_experience));

    // Append Files
    Object.keys(files.value).forEach(key => {
      if (files.value[key]) {
        formData.append(key, files.value[key]);
      }
    });

    if (isEditing.value) {
      await adminStore.updateEmployee(editId.value, formData);
    } else {
      await adminStore.createEmployee(formData);
    }
    showModal.value = false;
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to save employee');
  }
};

const openEditModal = (emp) => {
  isEditing.value = true;
  autoGenerateId.value = false;
  editId.value = emp.id;
  activeTab.value = 1;
  
  // Populate form with all employee details
  form.value = {
    name: emp.user?.name || '',
    email: emp.user?.email || '',
    password: '',
    employee_id: emp.employee_id || '',
    department_id: emp.department_id || '',
    designation_id: emp.designation_id || '',
    leave_structure_id: emp.leave_structure_id || '',
    employment_type: emp.employment_type || '',
    join_date: emp.join_date ? emp.join_date.split('T')[0] : '',
    dob: emp.dob ? emp.dob.split('T')[0] : '',
    gender: emp.gender || '',
    phone: emp.phone || '',
    salary: emp.salary || '',
    status: emp.status || 'active',
    personal_details: {
      father_name: emp.personal_details?.father_name || '',
      mother_name: emp.personal_details?.mother_name || '',
      marital_status: emp.personal_details?.marital_status || '',
      blood_group: emp.personal_details?.blood_group || '',
      current_address: emp.personal_details?.current_address || '',
      permanent_address: emp.personal_details?.permanent_address || '',
      emergency_contact_name: emp.personal_details?.emergency_contact_name || '',
      emergency_contact_phone: emp.personal_details?.emergency_contact_phone || ''
    },
    bank_details: {
      salary_type: emp.bank_details?.salary_type || '',
      bank_name: emp.bank_details?.bank_name || '',
      account_holder_name: emp.bank_details?.account_holder_name || '',
      bank_account_no: emp.bank_details?.bank_account_no || '',
      ifsc_code: emp.bank_details?.ifsc_code || '',
      uan_no: emp.bank_details?.uan_no || '',
      pf_applicable: emp.bank_details?.pf_applicable || false,
      esic_no: emp.bank_details?.esic_no || '',
      tds_applicable: emp.bank_details?.tds_applicable || false
    },
    identity_docs: {
      pan_no: emp.identity_docs?.pan_no || '',
      aadhaar_no: emp.identity_docs?.aadhaar_no || '',
      biometric_id: emp.identity_docs?.biometric_id || '',
      rfid_number: emp.identity_docs?.rfid_number || ''
    },
    education_experience: {
      highest_qualification: emp.education_experience?.highest_qualification || '',
      college_university: emp.education_experience?.college_university || '',
      passing_year: emp.education_experience?.passing_year || '',
      skills: emp.education_experience?.skills || '',
      prev_company_name: emp.education_experience?.prev_company_name || '',
      prev_designation: emp.education_experience?.prev_designation || '',
      years_of_experience: emp.education_experience?.years_of_experience || '',
      last_salary: emp.education_experience?.last_salary || ''
    }
  };
  
  // Reset files state
  Object.keys(files.value).forEach(key => files.value[key] = null);
  existingDocuments.value = emp.documents || {};
  
  showModal.value = true;
};

const handleDelete = async (id) => {
  if (confirm('Are you sure you want to completely remove this employee?')) {
    await adminStore.deleteEmployee(id);
  }
};
</script>

<template>
  <div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
          <Users class="w-6 h-6 text-blue-600" />
          Employees
        </h1>
        <p class="text-gray-500 mt-1 text-sm">Manage comprehensive employee profiles</p>
      </div>
      <button @click="openCreateModal" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
        <Plus class="w-4 h-4" />
        Add Employee
      </button>
    </div>

    <!-- Data Table -->
    <div class="bg-white border border-slate-200/85 rounded-2xl shadow-sm overflow-hidden">
      <div v-if="adminStore.loading" class="p-12 text-center text-slate-500 font-semibold flex flex-col items-center justify-center gap-3">
        <div class="animate-spin rounded-full h-8 w-8 border-3 border-blue-600 border-t-transparent"></div>
        <span>Retrieving employee database...</span>
      </div>
      <table v-else class="w-full text-left text-sm text-slate-600">
        <thead class="bg-slate-50/75 text-slate-500 border-b border-slate-200/80">
          <tr>
            <th class="px-6 py-4 text-[10px] font-extrabold uppercase tracking-widest">Employee</th>
            <th class="px-6 py-4 text-[10px] font-extrabold uppercase tracking-widest">Role</th>
            <th class="px-6 py-4 text-[10px] font-extrabold uppercase tracking-widest">Contact Info</th>
            <th class="px-6 py-4 text-[10px] font-extrabold uppercase tracking-widest">Joined Date</th>
            <th class="px-6 py-4 text-[10px] font-extrabold uppercase tracking-widest">Status</th>
            <th class="px-6 py-4 text-[10px] font-extrabold uppercase tracking-widest text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr 
            v-for="emp in adminStore.employees" 
            :key="emp.id" 
            class="hover:bg-slate-50/60 border-l-3 border-l-transparent hover:border-l-indigo-600 transition-all duration-200 cursor-pointer group" 
            @click="openProfileModal(emp)"
          >
            <td class="px-6 py-3.5">
              <div class="flex items-center gap-3">
                <img v-if="emp.documents?.profile_photo" :src="`http://localhost:8000/storage/${emp.documents.profile_photo}`" class="w-10 h-10 rounded-full object-cover border border-slate-200/80 shadow-sm" />
                <div v-else class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-50 to-indigo-100 text-indigo-700 flex items-center justify-center font-extrabold text-sm border border-indigo-200/40 shadow-sm">
                  {{ emp.user?.name?.charAt(0) }}
                </div>
                <div>
                  <div class="font-extrabold text-slate-900 group-hover:text-indigo-600 transition-colors leading-snug">{{ emp.user?.name }}</div>
                  <div class="text-[10px] font-extrabold text-slate-400 mt-0.5 tracking-wider uppercase font-mono bg-slate-100 px-1.5 py-0.5 rounded w-fit leading-none">
                    {{ emp.employee_id || `EMP-${emp.id.toString().padStart(4, '0')}` }}
                  </div>
                </div>
              </div>
            </td>
            <td class="px-6 py-3.5">
              <div class="text-slate-800 font-extrabold leading-tight text-xs uppercase tracking-wide">{{ emp.designation?.name || 'No Designation' }}</div>
              <div class="text-[11px] font-semibold text-slate-400 mt-0.5">{{ emp.department?.name || 'No Department' }}</div>
            </td>
            <td class="px-6 py-3.5">
              <div class="space-y-1">
                <div class="flex items-center gap-1.5 text-xs text-slate-600">
                  <Mail class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  <span class="truncate max-w-[170px] font-medium" :title="emp.email || emp.user?.email">{{ emp.email || emp.user?.email }}</span>
                </div>
                <div v-if="emp.phone" class="flex items-center gap-1.5 text-xs text-slate-600">
                  <Phone class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  <span class="font-semibold text-slate-700">{{ emp.phone }}</span>
                </div>
              </div>
            </td>
            <td class="px-6 py-3.5">
              <div class="flex items-center gap-1.5 text-xs text-slate-600 font-semibold">
                <Calendar class="w-3.5 h-3.5 text-slate-400" />
                {{ emp.join_date ? new Date(emp.join_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A' }}
              </div>
            </td>
            <td class="px-6 py-3.5">
              <span :class="[
                'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider',
                emp.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/50' : 'bg-rose-50 text-rose-700 border border-rose-200/50'
              ]">
                <span class="w-1.5 h-1.5 rounded-full" :class="emp.status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"></span>
                {{ emp.status || 'active' }}
              </span>
            </td>
            <td class="px-6 py-3.5 text-right cursor-default" @click.stop>
              <div class="flex justify-end gap-2">
                <button 
                  @click="openIdCardModal(emp)" 
                  class="p-2 text-indigo-600 hover:text-indigo-700 bg-indigo-50/50 hover:bg-indigo-50 border border-indigo-100/30 rounded-xl transition-all shadow-sm" 
                  title="View Virtual ID Card"
                >
                  <IdCard class="w-3.5 h-3.5" />
                </button>
                <button 
                  @click="openEditModal(emp)" 
                  class="p-2 text-blue-600 hover:text-blue-700 bg-blue-50/50 hover:bg-blue-50 border border-blue-100/30 rounded-xl transition-all shadow-sm"
                  title="Edit Profile"
                >
                  <Edit2 class="w-3.5 h-3.5" />
                </button>
                <button 
                  @click="handleDelete(emp.id)" 
                  class="p-2 text-rose-600 hover:text-rose-700 bg-rose-50/50 hover:bg-rose-50 border border-rose-100/30 rounded-xl transition-all shadow-sm"
                  title="Delete Record"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="adminStore.employees.length === 0">
            <td colspan="6" class="px-6 py-12 text-center text-slate-500 font-semibold italic bg-slate-50/30">
              No employee profiles found in directory. Click "Add Employee" to create one.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Profile View Modal -->
    <div v-if="showProfileModal && selectedEmployee" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[85vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 via-indigo-650 to-indigo-750 px-6 py-5 text-white relative">
          <button @click="showProfileModal = false" class="absolute top-4 right-4 text-white/80 hover:text-white bg-white/10 hover:bg-white/20 rounded-full p-2 border border-white/10 transition-all flex items-center justify-center">
            <X class="w-4.5 h-4.5" />
          </button>
          
          <div class="flex flex-col sm:flex-row items-center gap-4">
            <img v-if="selectedEmployee.documents?.profile_photo" :src="`http://localhost:8000/storage/${selectedEmployee.documents.profile_photo}`" class="w-20 h-20 rounded-full object-cover border-3 border-white/20 shadow-md" />
            <div v-else class="w-20 h-20 rounded-full bg-white/15 text-white flex items-center justify-center font-bold text-3xl border-3 border-white/20 shadow-md">
              {{ selectedEmployee.user?.name?.charAt(0) }}
            </div>
            
            <div class="text-center sm:text-left space-y-1">
              <h3 class="font-bold text-xl tracking-tight leading-tight">{{ selectedEmployee.user?.name }}</h3>
              <p class="text-blue-100/90 font-medium text-xs flex items-center justify-center sm:justify-start gap-2">
                <span>{{ selectedEmployee.designation?.name || 'No Designation' }}</span>
                <span class="w-1 h-1 rounded-full bg-blue-200/65"></span>
                <span>{{ selectedEmployee.department?.name || 'No Department' }}</span>
              </p>
              <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mt-1.5">
                <span class="px-2 py-0.5 rounded-full bg-white/15 text-[10px] font-bold uppercase tracking-wider">
                  {{ selectedEmployee.employee_id || `EMP-${selectedEmployee.id.toString().padStart(4, '0')}` }}
                </span>
                <span 
                  :class="[
                    'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                    selectedEmployee.status === 'active' ? 'bg-green-400/20 text-green-200' : 'bg-red-400/20 text-red-200'
                  ]"
                >
                  {{ selectedEmployee.status }}
                </span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Tab Selectors -->
        <div class="flex border-b border-slate-250/30 overflow-x-auto bg-slate-50">
          <button 
            v-for="t in ['Job & Account', 'Personal Details', 'Bank & Tax Info', 'Edu & Experience', 'Documents']" 
            :key="t"
            @click="profileTab = t"
            class="px-5 py-3 text-xs font-bold border-b-2 whitespace-nowrap transition-colors uppercase tracking-wider"
            :class="profileTab === t ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-900'"
          >
            {{ t }}
          </button>
        </div>
        
        <!-- Content -->
        <div class="p-6 overflow-y-auto flex-1 min-h-0 custom-scrollbar space-y-4">
          <!-- Tab 1: Job & Account -->
          <div v-if="profileTab === 'Job & Account'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="info-group">
              <span class="info-label">Email Address</span>
              <span class="info-value">{{ selectedEmployee.user?.email }}</span>
            </div>
            <div class="info-group">
              <span class="info-label">Phone Number</span>
              <span class="info-value">{{ selectedEmployee.phone || 'N/A' }}</span>
            </div>
            <div class="info-group">
              <span class="info-label">Employment Type</span>
              <span class="info-value">{{ selectedEmployee.employment_type || 'N/A' }}</span>
            </div>
            <div class="info-group">
              <span class="info-label">Leave Structure</span>
              <span class="info-value">{{ selectedEmployee.leave_structure?.name || 'N/A' }}</span>
            </div>
            <div class="info-group">
              <span class="info-label">Joining Date</span>
              <span class="info-value">{{ selectedEmployee.join_date ? new Date(selectedEmployee.join_date).toLocaleDateString() : 'N/A' }}</span>
            </div>
            <div class="info-group">
              <span class="info-label">Basic Salary</span>
              <span class="info-value font-bold text-slate-950">{{ selectedEmployee.salary ? `${selectedEmployee.salary} / month` : 'N/A' }}</span>
            </div>
          </div>
          
          <!-- Tab 2: Personal Details -->
          <div v-if="profileTab === 'Personal Details'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="info-group">
              <span class="info-label">Date of Birth</span>
              <span class="info-value">{{ selectedEmployee.dob ? new Date(selectedEmployee.dob).toLocaleDateString() : 'N/A' }}</span>
            </div>
            <div class="info-group">
              <span class="info-label">Gender</span>
              <span class="info-value">{{ selectedEmployee.gender || 'N/A' }}</span>
            </div>
            <div class="info-group">
              <span class="info-label">Blood Group</span>
              <span class="info-value">{{ selectedEmployee.personal_details?.blood_group || 'N/A' }}</span>
            </div>
            <div class="info-group">
              <span class="info-label">Marital Status</span>
              <span class="info-value">{{ selectedEmployee.personal_details?.marital_status || 'N/A' }}</span>
            </div>
            <div class="info-group">
              <span class="info-label">Father's Name</span>
              <span class="info-value">{{ selectedEmployee.personal_details?.father_name || 'N/A' }}</span>
            </div>
            <div class="info-group">
              <span class="info-label">Mother's Name</span>
              <span class="info-value">{{ selectedEmployee.personal_details?.mother_name || 'N/A' }}</span>
            </div>
            <div class="info-group md:col-span-2">
              <span class="info-label">Current Address</span>
              <span class="info-value whitespace-pre-line leading-relaxed">{{ selectedEmployee.personal_details?.current_address || 'N/A' }}</span>
            </div>
            <div class="info-group md:col-span-2">
              <span class="info-label">Permanent Address</span>
              <span class="info-value whitespace-pre-line leading-relaxed">{{ selectedEmployee.personal_details?.permanent_address || 'N/A' }}</span>
            </div>
            
            <div class="md:col-span-2 border-t border-slate-100 pt-3.5 mt-1">
              <h4 class="font-extrabold text-slate-800 mb-3 text-xs uppercase tracking-wider flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                Emergency Contact Details
              </h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="info-group">
                  <span class="info-label">Contact Name</span>
                  <span class="info-value">{{ selectedEmployee.personal_details?.emergency_contact_name || 'N/A' }}</span>
                </div>
                <div class="info-group">
                  <span class="info-label">Contact Phone</span>
                  <span class="info-value">{{ selectedEmployee.personal_details?.emergency_contact_phone || 'N/A' }}</span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Tab 3: Bank & Tax Info -->
          <div v-if="profileTab === 'Bank & Tax Info'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="info-group">
                <span class="info-label">Salary Type</span>
                <span class="info-value">{{ selectedEmployee.bank_details?.salary_type || 'N/A' }}</span>
              </div>
              <div class="info-group">
                <span class="info-label">Bank Name</span>
                <span class="info-value">{{ selectedEmployee.bank_details?.bank_name || 'N/A' }}</span>
              </div>
              <div class="info-group">
                <span class="info-label">Account Holder Name</span>
                <span class="info-value">{{ selectedEmployee.bank_details?.account_holder_name || 'N/A' }}</span>
              </div>
              <div class="info-group">
                <span class="info-label">Account Number</span>
                <span class="info-value font-mono">{{ selectedEmployee.bank_details?.bank_account_no || 'N/A' }}</span>
              </div>
              <div class="info-group">
                <span class="info-label">IFSC Code</span>
                <span class="info-value font-mono">{{ selectedEmployee.bank_details?.ifsc_code || 'N/A' }}</span>
              </div>
            </div>
            
            <div class="border-t border-slate-100 pt-3.5 mt-2">
              <h4 class="font-extrabold text-slate-800 mb-3 text-xs uppercase tracking-wider flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                Identity &amp; Tax Identifications
              </h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="info-group">
                  <span class="info-label">PAN Card Number</span>
                  <span class="info-value font-mono">{{ selectedEmployee.identity_docs?.pan_no || 'N/A' }}</span>
                </div>
                <div class="info-group">
                  <span class="info-label">Aadhaar Card Number</span>
                  <span class="info-value font-mono">{{ selectedEmployee.identity_docs?.aadhaar_no || 'N/A' }}</span>
                </div>
                <div class="info-group">
                  <span class="info-label">UAN Number (PF)</span>
                  <span class="info-value font-mono">{{ selectedEmployee.bank_details?.uan_no || 'N/A' }}</span>
                </div>
                <div class="info-group">
                  <span class="info-label">ESIC Number</span>
                  <span class="info-value font-mono">{{ selectedEmployee.bank_details?.esic_no || 'N/A' }}</span>
                </div>
                <div class="info-group">
                  <span class="info-label">PF Status</span>
                  <span class="info-value">{{ selectedEmployee.bank_details?.pf_applicable ? 'Applicable' : 'Not Applicable' }}</span>
                </div>
                <div class="info-group">
                  <span class="info-label">TDS Status</span>
                  <span class="info-value">{{ selectedEmployee.bank_details?.tds_applicable ? 'Applicable' : 'Not Applicable' }}</span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Tab 4: Edu & Experience -->
          <div v-if="profileTab === 'Edu & Experience'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="info-group">
                <span class="info-label">Highest Qualification</span>
                <span class="info-value">{{ selectedEmployee.education_experience?.highest_qualification || 'N/A' }}</span>
              </div>
              <div class="info-group">
                <span class="info-label">College / University</span>
                <span class="info-value">{{ selectedEmployee.education_experience?.college_university || 'N/A' }}</span>
              </div>
              <div class="info-group">
                <span class="info-label">Passing Year</span>
                <span class="info-value">{{ selectedEmployee.education_experience?.passing_year || 'N/A' }}</span>
              </div>
              <div class="info-group">
                <span class="info-label">Professional Skills</span>
                <div class="flex flex-wrap gap-1.5 mt-1">
                  <span v-for="skill in (selectedEmployee.education_experience?.skills?.split(',') || [])" :key="skill" class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md text-[11px] font-bold border border-slate-200/55">
                    {{ skill.trim() }}
                  </span>
                  <span v-if="!selectedEmployee.education_experience?.skills" class="text-sm font-semibold text-slate-800">N/A</span>
                </div>
              </div>
            </div>
            
            <div class="border-t border-slate-100 pt-3.5 mt-2">
              <h4 class="font-extrabold text-slate-800 mb-3 text-xs uppercase tracking-wider flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                Past Employment History
              </h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="info-group">
                  <span class="info-label">Previous Company</span>
                  <span class="info-value">{{ selectedEmployee.education_experience?.prev_company_name || 'N/A' }}</span>
                </div>
                <div class="info-group">
                  <span class="info-label">Previous Designation</span>
                  <span class="info-value">{{ selectedEmployee.education_experience?.prev_designation || 'N/A' }}</span>
                </div>
                <div class="info-group">
                  <span class="info-label">Years of Experience</span>
                  <span class="info-value">{{ selectedEmployee.education_experience?.years_of_experience ? `${selectedEmployee.education_experience.years_of_experience} yrs` : 'N/A' }}</span>
                </div>
                <div class="info-group">
                  <span class="info-label">Last Drawn Salary</span>
                  <span class="info-value">{{ selectedEmployee.education_experience?.last_salary || 'N/A' }}</span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Tab 5: Documents -->
          <div v-if="profileTab === 'Documents'" class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div 
              v-for="docName in [
                { key: 'resume', label: 'Resume / CV' },
                { key: 'aadhaar_doc', label: 'Aadhaar Card' },
                { key: 'pan_doc', label: 'PAN Card' },
                { key: 'education_cert', label: 'Education Certificate' },
                { key: 'experience_letter', label: 'Experience Letter' },
                { key: 'offer_letter', label: 'Offer Letter' },
                { key: 'signature', label: 'Digital Signature' },
                { key: 'appointment_letter', label: 'Appointment Letter' }
              ]" 
              :key="docName.key" 
              class="p-3.5 bg-slate-50/50 hover:bg-slate-50 border border-slate-200/50 hover:border-indigo-100 rounded-xl flex items-center justify-between gap-4 transition-all duration-200"
            >
              <div>
                <div class="font-bold text-slate-800 text-sm leading-snug">{{ docName.label }}</div>
                <div class="text-[10px] uppercase font-bold mt-1 tracking-wider" :class="selectedEmployee.documents?.[docName.key] ? 'text-emerald-500' : 'text-slate-400'">
                  {{ selectedEmployee.documents?.[docName.key] ? 'Uploaded' : 'Not Provided' }}
                </div>
              </div>
              <a 
                v-if="selectedEmployee.documents?.[docName.key]" 
                :href="`http://localhost:8000/storage/${selectedEmployee.documents[docName.key]}`" 
                target="_blank"
                class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 hover:text-blue-800 rounded-lg text-[11px] font-extrabold border border-blue-100 transition-colors whitespace-nowrap"
              >
                View File
              </a>
              <span v-else class="text-[10px] text-slate-350 font-bold uppercase tracking-wider px-2 py-1 bg-slate-100/55 rounded-md border border-slate-200/35">N/A</span>
            </div>
          </div>
        </div>
        
        <!-- Footer -->
        <div class="px-6 py-3.5 border-t border-slate-100 bg-slate-50 flex justify-between items-center">
          <button @click="editFromProfile" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs transition-colors flex items-center gap-1.5 shadow-sm uppercase tracking-wide">
            <Edit2 class="w-3.5 h-3.5" />
            Edit Profile
          </button>
          <button @click="showProfileModal = false" class="px-5 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold rounded-xl text-xs transition-colors shadow-sm uppercase tracking-wide">
            Close
          </button>
        </div>
      </div>
    </div>

    <!-- Multi-Step Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
        
        <!-- Header & Tab Progress -->
        <div class="bg-gray-50 border-b border-gray-200">
          <div class="px-6 py-4 flex justify-between items-center">
            <h3 class="font-bold text-xl text-gray-900">{{ isEditing ? 'Edit' : 'Register' }} Employee</h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 bg-white rounded-full p-1 border border-gray-200 transition-colors flex items-center justify-center">
              <X class="w-4 h-4" />
            </button>
          </div>
          
          <div class="px-6 pb-4">
            <div class="flex justify-between items-center relative">
              <div class="absolute inset-0 top-1/2 -translate-y-1/2 h-0.5 bg-gray-200 z-0 hidden md:block"></div>
              <div v-for="tab in tabs" :key="tab.id" class="relative z-10 flex flex-col items-center gap-2">
                <div 
                  class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-colors"
                  :class="activeTab === tab.id ? 'bg-blue-600 border-blue-600 text-white' : (activeTab > tab.id ? 'bg-green-500 border-green-500 text-white' : 'bg-white border-gray-300 text-gray-400')"
                >
                  <CheckCircle2 v-if="activeTab > tab.id" class="w-5 h-5" />
                  <span v-else>{{ tab.id }}</span>
                </div>
                <span class="text-xs font-medium hidden md:block" :class="activeTab === tab.id ? 'text-blue-600' : 'text-gray-500'">{{ tab.name }}</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Form Body -->
        <div class="p-6 overflow-y-auto flex-1 custom-scrollbar">
          <form @submit.prevent="handleSubmit" id="employeeForm">
            
            <!-- TAB 1: Account & Job -->
            <div v-if="activeTab === 1" class="space-y-6">
              <h4 class="text-lg font-semibold text-gray-800 border-b pb-2">Account & Employment Details</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
                  <div class="flex items-center gap-2">
                    <input 
                      :value="autoGenerateId ? nextEmployeeIdPreview : form.employee_id" 
                      @input="e => { if (!autoGenerateId) form.employee_id = e.target.value }"
                      type="text" 
                      :disabled="autoGenerateId" 
                      class="form-input flex-1 disabled:bg-gray-50 disabled:text-gray-900 disabled:font-bold disabled:opacity-100 disabled:cursor-not-allowed disabled:border-gray-200" 
                      placeholder="e.g. EMP-0001" 
                    />
                    <label v-if="!isEditing" class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer select-none bg-gray-50 border border-gray-200 px-3 py-2.5 rounded-xl hover:bg-gray-100 transition-colors">
                      <input 
                        type="checkbox" 
                        v-model="autoGenerateId" 
                        class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500" 
                      />
                      Auto-generate
                    </label>
                  </div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label><input v-model="form.name" type="text" required class="form-input" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label><input v-model="form.email" type="email" required class="form-input" /></div>
                <div v-if="!isEditing"><label class="block text-sm font-medium text-gray-700 mb-1">Initial Password *</label><input v-model="form.password" type="text" required class="form-input" /></div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                  <select v-model="form.department_id" class="form-input">
                    <option value="">Select Department</option>
                    <option v-for="d in adminStore.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
                  <select v-model="form.designation_id" class="form-input">
                    <option value="">Select Designation</option>
                    <option v-for="d in adminStore.designations" :key="d.id" :value="d.id">{{ d.name }}</option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Employment Type</label>
                  <select v-model="form.employment_type" class="form-input">
                    <option value="">Select Type</option>
                    <option value="Full-time">Full-time</option>
                    <option value="Part-time">Part-time</option>
                    <option value="Contract">Contract</option>
                    <option value="Intern">Intern</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Leave Structure</label>
                  <select v-model="form.leave_structure_id" class="form-input">
                    <option value="">Select Structure</option>
                    <option v-for="s in adminStore.leaveStructures" :key="s.id" :value="s.id">{{ s.name }}</option>
                  </select>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Joining Date</label><input v-model="form.join_date" type="date" class="form-input" /></div>
              </div>
            </div>

            <!-- TAB 2: Personal Info -->
            <div v-if="activeTab === 2" class="space-y-6">
              <h4 class="text-lg font-semibold text-gray-800 border-b pb-2">Personal Information</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label><input v-model="form.phone" type="text" class="form-input" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label><input v-model="form.dob" type="date" class="form-input" /></div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                  <select v-model="form.gender" class="form-input">
                    <option value="">Select Gender</option><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                  <select v-model="form.personal_details.blood_group" class="form-input">
                    <option value="">Select Blood Group</option><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>O+</option><option>O-</option><option>AB+</option><option>AB-</option>
                  </select>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Father's Name</label><input v-model="form.personal_details.father_name" type="text" class="form-input" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Mother's Name</label><input v-model="form.personal_details.mother_name" type="text" class="form-input" /></div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Marital Status</label>
                  <select v-model="form.personal_details.marital_status" class="form-input">
                    <option value="">Select Status</option><option>Single</option><option>Married</option><option>Divorced</option>
                  </select>
                </div>
                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Current Address</label><textarea v-model="form.personal_details.current_address" class="form-input h-20"></textarea></div>
                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Permanent Address</label><textarea v-model="form.personal_details.permanent_address" class="form-input h-20"></textarea></div>
                
                <h5 class="md:col-span-2 text-md font-semibold text-gray-700 mt-4">Emergency Contact</h5>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label><input v-model="form.personal_details.emergency_contact_name" type="text" class="form-input" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label><input v-model="form.personal_details.emergency_contact_phone" type="text" class="form-input" /></div>
              </div>
            </div>

            <!-- TAB 3: Payroll & Identity -->
            <div v-if="activeTab === 3" class="space-y-6">
              <h4 class="text-lg font-semibold text-gray-800 border-b pb-2">Payroll & Government IDs</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Basic Salary</label><input v-model="form.salary" type="number" step="0.01" class="form-input" /></div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Salary Type</label>
                  <select v-model="form.bank_details.salary_type" class="form-input"><option value="">Select Type</option><option>Monthly</option><option>Daily</option></select>
                </div>
                
                <h5 class="md:col-span-2 text-md font-semibold text-gray-700 mt-4 border-b pb-2">Bank Details</h5>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label><input v-model="form.bank_details.bank_name" type="text" class="form-input" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Account Holder Name</label><input v-model="form.bank_details.account_holder_name" type="text" class="form-input" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Account Number</label><input v-model="form.bank_details.bank_account_no" type="text" class="form-input" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">IFSC Code</label><input v-model="form.bank_details.ifsc_code" type="text" class="form-input" /></div>
                
                <h5 class="md:col-span-2 text-md font-semibold text-gray-700 mt-4 border-b pb-2">Tax & Government IDs</h5>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">PAN Number</label><input v-model="form.identity_docs.pan_no" type="text" class="form-input" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Aadhaar Number</label><input v-model="form.identity_docs.aadhaar_no" type="text" class="form-input" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">UAN Number (PF)</label><input v-model="form.bank_details.uan_no" type="text" class="form-input" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">ESIC Number</label><input v-model="form.bank_details.esic_no" type="text" class="form-input" /></div>
                
                <div class="flex items-center gap-4 mt-2">
                  <label class="flex items-center gap-2"><input type="checkbox" v-model="form.bank_details.pf_applicable" class="w-4 h-4 text-blue-600 rounded" /> PF Applicable</label>
                  <label class="flex items-center gap-2"><input type="checkbox" v-model="form.bank_details.tds_applicable" class="w-4 h-4 text-blue-600 rounded" /> TDS Applicable</label>
                </div>
              </div>
            </div>

            <!-- TAB 4: Education & Experience -->
            <div v-if="activeTab === 4" class="space-y-6">
              <h4 class="text-lg font-semibold text-gray-800 border-b pb-2">Education & Experience</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <h5 class="md:col-span-2 text-md font-semibold text-gray-700">Education Background</h5>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Highest Qualification</label><input v-model="form.education_experience.highest_qualification" type="text" class="form-input" placeholder="e.g. B.Sc in Computer Science" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">College / University</label><input v-model="form.education_experience.college_university" type="text" class="form-input" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Passing Year</label><input v-model="form.education_experience.passing_year" type="text" class="form-input" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Skills (Comma separated)</label><input v-model="form.education_experience.skills" type="text" class="form-input" placeholder="e.g. Java, Python, Management" /></div>
                
                <h5 class="md:col-span-2 text-md font-semibold text-gray-700 mt-4">Work Experience</h5>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Previous Company</label><input v-model="form.education_experience.prev_company_name" type="text" class="form-input" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Previous Designation</label><input v-model="form.education_experience.prev_designation" type="text" class="form-input" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Years of Experience</label><input v-model="form.education_experience.years_of_experience" type="number" step="0.1" class="form-input" /></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Last Salary</label><input v-model="form.education_experience.last_salary" type="number" step="0.01" class="form-input" /></div>
              </div>
            </div>

            <!-- TAB 5: Documents Upload -->
            <div v-if="activeTab === 5" class="space-y-6">
              <h4 class="text-lg font-semibold text-gray-800 border-b pb-2">Document Uploads</h4>
              <p class="text-sm text-gray-500 mb-4">Upload all relevant documents. Supported formats: JPG, PNG, PDF.</p>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="file-input-group">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Profile Photo</label>
                  <input type="file" @change="e => handleFileUpload(e, 'profile_photo')" accept="image/*" class="file-input" />
                  <div v-if="isEditing && existingDocuments.profile_photo" class="mt-1.5 flex items-center gap-2 text-xs text-blue-600 bg-blue-50/50 px-2.5 py-1.5 rounded-lg border border-blue-100/50 w-fit">
                    <FileText class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" />
                    <span class="text-gray-500 font-medium">Current:</span>
                    <a :href="`http://localhost:8000/storage/${existingDocuments.profile_photo}`" target="_blank" class="font-semibold hover:underline flex items-center gap-0.5 truncate max-w-[200px]" :title="getFileName(existingDocuments.profile_photo)">
                      {{ getFileName(existingDocuments.profile_photo) }}
                      <ExternalLink class="w-3 h-3 flex-shrink-0 inline" />
                    </a>
                  </div>
                </div>
                <div class="file-input-group">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Resume / CV</label>
                  <input type="file" @change="e => handleFileUpload(e, 'resume')" accept=".pdf,.doc,.docx" class="file-input" />
                  <div v-if="isEditing && existingDocuments.resume" class="mt-1.5 flex items-center gap-2 text-xs text-blue-600 bg-blue-50/50 px-2.5 py-1.5 rounded-lg border border-blue-100/50 w-fit">
                    <FileText class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" />
                    <span class="text-gray-500 font-medium">Current:</span>
                    <a :href="`http://localhost:8000/storage/${existingDocuments.resume}`" target="_blank" class="font-semibold hover:underline flex items-center gap-0.5 truncate max-w-[200px]" :title="getFileName(existingDocuments.resume)">
                      {{ getFileName(existingDocuments.resume) }}
                      <ExternalLink class="w-3 h-3 flex-shrink-0 inline" />
                    </a>
                  </div>
                </div>
                <div class="file-input-group">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Aadhaar Card</label>
                  <input type="file" @change="e => handleFileUpload(e, 'aadhaar_doc')" class="file-input" />
                  <div v-if="isEditing && existingDocuments.aadhaar_doc" class="mt-1.5 flex items-center gap-2 text-xs text-blue-600 bg-blue-50/50 px-2.5 py-1.5 rounded-lg border border-blue-100/50 w-fit">
                    <FileText class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" />
                    <span class="text-gray-500 font-medium">Current:</span>
                    <a :href="`http://localhost:8000/storage/${existingDocuments.aadhaar_doc}`" target="_blank" class="font-semibold hover:underline flex items-center gap-0.5 truncate max-w-[200px]" :title="getFileName(existingDocuments.aadhaar_doc)">
                      {{ getFileName(existingDocuments.aadhaar_doc) }}
                      <ExternalLink class="w-3 h-3 flex-shrink-0 inline" />
                    </a>
                  </div>
                </div>
                <div class="file-input-group">
                  <label class="block text-sm font-medium text-gray-700 mb-1">PAN Card</label>
                  <input type="file" @change="e => handleFileUpload(e, 'pan_doc')" class="file-input" />
                  <div v-if="isEditing && existingDocuments.pan_doc" class="mt-1.5 flex items-center gap-2 text-xs text-blue-600 bg-blue-50/50 px-2.5 py-1.5 rounded-lg border border-blue-100/50 w-fit">
                    <FileText class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" />
                    <span class="text-gray-500 font-medium">Current:</span>
                    <a :href="`http://localhost:8000/storage/${existingDocuments.pan_doc}`" target="_blank" class="font-semibold hover:underline flex items-center gap-0.5 truncate max-w-[200px]" :title="getFileName(existingDocuments.pan_doc)">
                      {{ getFileName(existingDocuments.pan_doc) }}
                      <ExternalLink class="w-3 h-3 flex-shrink-0 inline" />
                    </a>
                  </div>
                </div>
                <div class="file-input-group">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Education Certificate</label>
                  <input type="file" @change="e => handleFileUpload(e, 'education_cert')" class="file-input" />
                  <div v-if="isEditing && existingDocuments.education_cert" class="mt-1.5 flex items-center gap-2 text-xs text-blue-600 bg-blue-50/50 px-2.5 py-1.5 rounded-lg border border-blue-100/50 w-fit">
                    <FileText class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" />
                    <span class="text-gray-500 font-medium">Current:</span>
                    <a :href="`http://localhost:8000/storage/${existingDocuments.education_cert}`" target="_blank" class="font-semibold hover:underline flex items-center gap-0.5 truncate max-w-[200px]" :title="getFileName(existingDocuments.education_cert)">
                      {{ getFileName(existingDocuments.education_cert) }}
                      <ExternalLink class="w-3 h-3 flex-shrink-0 inline" />
                    </a>
                  </div>
                </div>
                <div class="file-input-group">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Experience Letter</label>
                  <input type="file" @change="e => handleFileUpload(e, 'experience_letter')" class="file-input" />
                  <div v-if="isEditing && existingDocuments.experience_letter" class="mt-1.5 flex items-center gap-2 text-xs text-blue-600 bg-blue-50/50 px-2.5 py-1.5 rounded-lg border border-blue-100/50 w-fit">
                    <FileText class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" />
                    <span class="text-gray-500 font-medium">Current:</span>
                    <a :href="`http://localhost:8000/storage/${existingDocuments.experience_letter}`" target="_blank" class="font-semibold hover:underline flex items-center gap-0.5 truncate max-w-[200px]" :title="getFileName(existingDocuments.experience_letter)">
                      {{ getFileName(existingDocuments.experience_letter) }}
                      <ExternalLink class="w-3 h-3 flex-shrink-0 inline" />
                    </a>
                  </div>
                </div>
                <div class="file-input-group">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Offer Letter (Current)</label>
                  <input type="file" @change="e => handleFileUpload(e, 'offer_letter')" class="file-input" />
                  <div v-if="isEditing && existingDocuments.offer_letter" class="mt-1.5 flex items-center gap-2 text-xs text-blue-600 bg-blue-50/50 px-2.5 py-1.5 rounded-lg border border-blue-100/50 w-fit">
                    <FileText class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" />
                    <span class="text-gray-500 font-medium">Current:</span>
                    <a :href="`http://localhost:8000/storage/${existingDocuments.offer_letter}`" target="_blank" class="font-semibold hover:underline flex items-center gap-0.5 truncate max-w-[200px]" :title="getFileName(existingDocuments.offer_letter)">
                      {{ getFileName(existingDocuments.offer_letter) }}
                      <ExternalLink class="w-3 h-3 flex-shrink-0 inline" />
                    </a>
                  </div>
                </div>
                <div class="file-input-group">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Digital Signature</label>
                  <input type="file" @change="e => handleFileUpload(e, 'signature')" accept="image/*" class="file-input" />
                  <div v-if="isEditing && existingDocuments.signature" class="mt-1.5 flex items-center gap-2 text-xs text-blue-600 bg-blue-50/50 px-2.5 py-1.5 rounded-lg border border-blue-100/50 w-fit">
                    <FileText class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" />
                    <span class="text-gray-500 font-medium">Current:</span>
                    <a :href="`http://localhost:8000/storage/${existingDocuments.signature}`" target="_blank" class="font-semibold hover:underline flex items-center gap-0.5 truncate max-w-[200px]" :title="getFileName(existingDocuments.signature)">
                      {{ getFileName(existingDocuments.signature) }}
                      <ExternalLink class="w-3 h-3 flex-shrink-0 inline" />
                    </a>
                  </div>
                </div>
              </div>
            </div>

          </form>
        </div>
        
        <!-- Footer Buttons -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
          <button 
            type="button" 
            @click="prevTab" 
            :disabled="activeTab === 1"
            class="flex items-center gap-2 px-4 py-2 text-gray-600 bg-white border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <ChevronLeft class="w-4 h-4" /> Previous
          </button>
          
          <button 
            v-if="activeTab < 5" 
            type="button" 
            @click="nextTab"
            class="flex items-center gap-2 px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition-colors"
          >
            Next <ChevronRight class="w-4 h-4" />
          </button>
          
          <button 
            v-if="activeTab === 5" 
            type="submit" 
            form="employeeForm"
            class="flex items-center gap-2 px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium transition-colors"
          >
            <CheckCircle2 class="w-4 h-4" />
            {{ isEditing ? 'Update Profile' : 'Complete Registration' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Virtual ID Card Modal -->
    <div v-if="showIdCardModal && selectedEmployee" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 overflow-y-auto">
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/80">
          <h3 class="font-bold text-gray-900 text-lg">Virtual ID Card</h3>
          <button @click="showIdCardModal = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-200 bg-white rounded-full p-1.5 transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <div class="p-8 bg-gray-100 flex justify-center">
          <IdCardComponent 
            :employeeData="{
              ...selectedEmployee.user,
              employee: selectedEmployee
            }" 
            :companyData="authStore.user?.company" 
            :hideHeader="true"
            :showSaveButton="true"
            :employeeId="selectedEmployee.id"
            @save-success="(data) => { selectedEmployee.id_card_image = data.id_card_image; }"
          />
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center print:hidden">
          <p class="text-xs text-gray-400">Click "Save ID Card" to generate &amp; store the image to the database.</p>
          <div class="flex gap-3">
            <button @click="showIdCardModal = false" class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-xl text-sm transition-colors">
              Close
            </button>
            <button @click="() => window.print()" class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white font-semibold rounded-xl text-sm transition-colors flex items-center gap-2">
              <Printer class="w-4 h-4" />
              Print ID Card
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
@reference "tailwindcss";

.info-group {
  @apply flex flex-col gap-1 bg-slate-50/40 px-4 py-2.5 rounded-xl border border-slate-200/50 hover:bg-slate-50 hover:border-indigo-100/80 transition-all duration-200;
}
.info-label {
  @apply text-[10px] font-extrabold text-slate-400 uppercase tracking-widest leading-none;
}
.info-value {
  @apply text-xs sm:text-sm font-bold text-slate-800 leading-normal;
}

.form-input {
  @apply block w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 shadow-sm focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all text-sm;
}
.file-input {
  @apply block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all border border-gray-200 rounded-lg bg-white p-1;
}
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 10px;
}
</style>
