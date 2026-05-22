<script setup>
import { onMounted, ref } from 'vue';
import { useAdminStore } from '../../stores/admin';
import { Users, Plus, Trash2, Edit2, Mail, Phone, Calendar, UploadCloud, ChevronRight, ChevronLeft, CheckCircle2 } from 'lucide-vue-next';

const adminStore = useAdminStore();
const showModal = ref(false);
const isEditing = ref(false);
const activeTab = ref(1);

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
  department_id: '', designation_id: '', employment_type: '', join_date: '',
  
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

onMounted(async () => {
  await Promise.all([
    adminStore.fetchEmployees(),
    adminStore.fetchDepartments(),
    adminStore.fetchDesignations()
  ]);
});

const openCreateModal = () => {
  isEditing.value = false;
  activeTab.value = 1;
  
  // Reset form
  form.value = {
    name: '', email: '', password: '', employee_id: '',
    department_id: '', designation_id: '', employment_type: '', join_date: '',
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
    const formData = new FormData();
    
    // Append top-level form fields
    const topLevelFields = ['name', 'email', 'password', 'employee_id', 'department_id', 'designation_id', 'employment_type', 'join_date', 'dob', 'gender', 'phone', 'salary', 'status'];
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
      // update logic
    } else {
      await adminStore.createEmployee(formData);
    }
    showModal.value = false;
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to save employee');
  }
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
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
      <div v-if="adminStore.loading" class="p-8 text-center text-gray-500">
        Loading employees...
      </div>
      <table v-else class="w-full text-left text-sm text-gray-600">
        <thead class="bg-gray-50 text-gray-700 border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 font-semibold">Employee</th>
            <th class="px-6 py-4 font-semibold">Role</th>
            <th class="px-6 py-4 font-semibold">Contact</th>
            <th class="px-6 py-4 font-semibold">Joined</th>
            <th class="px-6 py-4 font-semibold text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="emp in adminStore.employees" :key="emp.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <img v-if="emp.documents?.profile_photo" :src="`http://localhost:8000/storage/${emp.documents.profile_photo}`" class="w-10 h-10 rounded-full object-cover border border-gray-200" />
                <div v-else class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                  {{ emp.user?.name?.charAt(0) }}
                </div>
                <div>
                  <div class="font-medium text-gray-900">{{ emp.user?.name }}</div>
                  <div class="text-xs text-gray-500">ID: {{ emp.employee_id || `EMP-${emp.id.toString().padStart(4, '0')}` }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4">
              <div class="text-gray-900 font-medium">{{ emp.designation?.name || 'No Designation' }}</div>
              <div class="text-xs text-gray-500">{{ emp.department?.name || 'No Department' }}</div>
            </td>
            <td class="px-6 py-4 space-y-1">
              <div class="flex items-center gap-2 text-gray-600"><Mail class="w-3.5 h-3.5" /> {{ emp.user?.email }}</div>
              <div class="flex items-center gap-2 text-gray-600" v-if="emp.phone"><Phone class="w-3.5 h-3.5" /> {{ emp.phone }}</div>
            </td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-2 text-gray-600">
                <Calendar class="w-3.5 h-3.5" />
                {{ emp.join_date ? new Date(emp.join_date).toLocaleDateString() : 'N/A' }}
              </div>
            </td>
            <td class="px-6 py-4 text-right">
              <div class="flex justify-end gap-2">
                <button class="p-1.5 text-gray-400 hover:text-blue-600 transition-colors rounded-lg hover:bg-blue-50">
                  <Edit2 class="w-4 h-4" />
                </button>
                <button @click="handleDelete(emp.id)" class="p-1.5 text-gray-400 hover:text-red-600 transition-colors rounded-lg hover:bg-red-50">
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="adminStore.employees.length === 0">
            <td colspan="5" class="px-6 py-8 text-center text-gray-500">No employees found. Create one to get started.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Multi-Step Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
        
        <!-- Header & Tab Progress -->
        <div class="bg-gray-50 border-b border-gray-200">
          <div class="px-6 py-4 flex justify-between items-center">
            <h3 class="font-bold text-xl text-gray-900">{{ isEditing ? 'Edit' : 'Register' }} Employee</h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 bg-white rounded-full p-1 border border-gray-200">&times;</button>
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
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label><input v-model="form.employee_id" type="text" class="form-input" /></div>
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
                </div>
                <div class="file-input-group">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Resume / CV</label>
                  <input type="file" @change="e => handleFileUpload(e, 'resume')" accept=".pdf,.doc,.docx" class="file-input" />
                </div>
                <div class="file-input-group">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Aadhaar Card</label>
                  <input type="file" @change="e => handleFileUpload(e, 'aadhaar_doc')" class="file-input" />
                </div>
                <div class="file-input-group">
                  <label class="block text-sm font-medium text-gray-700 mb-1">PAN Card</label>
                  <input type="file" @change="e => handleFileUpload(e, 'pan_doc')" class="file-input" />
                </div>
                <div class="file-input-group">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Education Certificate</label>
                  <input type="file" @change="e => handleFileUpload(e, 'education_cert')" class="file-input" />
                </div>
                <div class="file-input-group">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Experience Letter</label>
                  <input type="file" @change="e => handleFileUpload(e, 'experience_letter')" class="file-input" />
                </div>
                <div class="file-input-group">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Offer Letter (Current)</label>
                  <input type="file" @change="e => handleFileUpload(e, 'offer_letter')" class="file-input" />
                </div>
                <div class="file-input-group">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Digital Signature</label>
                  <input type="file" @change="e => handleFileUpload(e, 'signature')" accept="image/*" class="file-input" />
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
  </div>
</template>

<style scoped>
@reference "tailwindcss";

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
