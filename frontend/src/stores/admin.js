import { defineStore } from 'pinia';
import api from '../axios';

export const useAdminStore = defineStore('admin', {
  state: () => ({
    departments: [],
    designations: [],
    employees: [],
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
    async deleteEmployee(id) {
      try {
        await api.delete(`/employees/${id}`);
        this.employees = this.employees.filter(e => e.id !== id);
      } catch (err) {
        throw err;
      }
    }
  }
});
