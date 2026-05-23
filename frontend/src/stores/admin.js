import { defineStore } from 'pinia';
import api from '../axios';

export const useAdminStore = defineStore('admin', {
  state: () => ({
    departments: [],
    designations: [],
    employees: [],
    leaveStructures: [],
    loading: false,
    error: null,
  }),
  actions: {
    async fetchDepartments() {
      this.loading = true;
      try {
        const response = await api.get('/departments');
        this.departments = response.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load departments';
      } finally {
        this.loading = false;
      }
    },
    async createDepartment(data) {
      try {
        const response = await api.post('/departments', data);
        this.departments.push(response.data);
        return true;
      } catch (err) {
        throw err;
      }
    },
    async updateDepartment(id, data) {
      try {
        const response = await api.put(`/departments/${id}`, data);
        const index = this.departments.findIndex(d => d.id === id);
        if (index !== -1) {
          this.departments[index] = response.data;
        }
        return true;
      } catch (err) {
        throw err;
      }
    },
    async deleteDepartment(id) {
      try {
        await api.delete(`/departments/${id}`);
        this.departments = this.departments.filter(d => d.id !== id);
      } catch (err) {
        throw err;
      }
    },
    async fetchDesignations() {
      this.loading = true;
      try {
        const response = await api.get('/designations');
        this.designations = response.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load designations';
      } finally {
        this.loading = false;
      }
    },
    async createDesignation(data) {
      try {
        const response = await api.post('/designations', data);
        this.designations.push(response.data);
        return true;
      } catch (err) {
        throw err;
      }
    },
    async updateDesignation(id, data) {
      try {
        const response = await api.put(`/designations/${id}`, data);
        const index = this.designations.findIndex(d => d.id === id);
        if (index !== -1) {
          this.designations[index] = response.data;
        }
        return true;
      } catch (err) {
        throw err;
      }
    },
    async deleteDesignation(id) {
      try {
        await api.delete(`/designations/${id}`);
        this.designations = this.designations.filter(d => d.id !== id);
      } catch (err) {
        throw err;
      }
    },
    async fetchEmployees() {
      this.loading = true;
      try {
        const response = await api.get('/employees');
        this.employees = response.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load employees';
      } finally {
        this.loading = false;
      }
    },
    async fetchLeaveStructures() {
      try {
        const response = await api.get('/leave-structures');
        this.leaveStructures = response.data;
      } catch (err) {
        console.error('Failed to load leave structures', err);
      }
    },
    async createEmployee(formData) {
      try {
        const response = await api.post('/employees', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });
        this.employees.push(response.data);
        return true;
      } catch (err) {
        throw err;
      }
    },
    async updateEmployee(id, formData) {
      try {
        formData.append('_method', 'PUT');
        const response = await api.post(`/employees/${id}`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });
        const index = this.employees.findIndex(e => e.id === id);
        if (index !== -1) {
          this.employees[index] = response.data;
        }
        return true;
      } catch (err) {
        throw err;
      }
    },
    async deleteEmployee(id) {
      try {
        await api.delete(`/employees/${id}`);
        this.employees = this.employees.filter(e => e.id !== id);
      } catch (err) {
        throw err;
      }
    },
    async importEmployees(file) {
      try {
        const formData = new FormData();
        formData.append('file', file);
        const response = await api.post('/employees/import', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });
        await this.fetchEmployees(); // refresh list
        return response.data;
      } catch (err) {
        throw err;
      }
    },
    async saveIdCardImage(employeeId, base64Image) {
      try {
        const response = await api.post(`/employees/${employeeId}/save-id-card`, {
          image: base64Image
        });
        // Update the employee's id_card_image in the local store
        const index = this.employees.findIndex(e => e.id === employeeId);
        if (index !== -1) {
          this.employees[index] = {
            ...this.employees[index],
            id_card_image: response.data.id_card_image
          };
        }
        return response.data;
      } catch (err) {
        throw err;
      }
    },
    resetStore() {
      this.departments = [];
      this.designations = [];
      this.employees = [];
      this.loading = false;
      this.error = null;
    }
  }
});
