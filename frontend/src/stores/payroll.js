import { defineStore } from 'pinia';
import api from '../axios';

export const usePayrollStore = defineStore('payroll', {
  state: () => ({
    penaltyRules: [],
    salaryStructures: [],
    loading: false,
    error: null,
  }),

  actions: {
    // ─── Penalty Rules ───────────────────────────────────────────────────
    async fetchPenaltyRules() {
      this.loading = true;
      try {
        const res = await api.get('/penalty-rules');
        this.penaltyRules = res.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load penalty rules';
      } finally {
        this.loading = false;
      }
    },

    async createPenaltyRule(data) {
      const res = await api.post('/penalty-rules', data);
      this.penaltyRules.push(res.data);
      return res.data;
    },

    async updatePenaltyRule(id, data) {
      const res = await api.put(`/penalty-rules/${id}`, data);
      const idx = this.penaltyRules.findIndex(r => r.id === id);
      if (idx !== -1) this.penaltyRules[idx] = res.data;
      return res.data;
    },

    async deletePenaltyRule(id) {
      await api.delete(`/penalty-rules/${id}`);
      this.penaltyRules = this.penaltyRules.filter(r => r.id !== id);
    },

    // ─── Salary Structures ───────────────────────────────────────────────
    async fetchSalaryStructures() {
      this.loading = true;
      try {
        const res = await api.get('/salary-structures');
        this.salaryStructures = res.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load salary structures';
      } finally {
        this.loading = false;
      }
    },

    async createSalaryStructure(data) {
      const res = await api.post('/salary-structures', data);
      this.salaryStructures.unshift(res.data);
      return res.data;
    },

    async updateSalaryStructure(id, data) {
      const res = await api.put(`/salary-structures/${id}`, data);
      const idx = this.salaryStructures.findIndex(s => s.id === id);
      if (idx !== -1) this.salaryStructures[idx] = res.data;
      return res.data;
    },

    async deleteSalaryStructure(id) {
      await api.delete(`/salary-structures/${id}`);
      this.salaryStructures = this.salaryStructures.filter(s => s.id !== id);
    },

    async calculateSalary(employeeId, month) {
      const res = await api.get(`/salary-structures/calculate/${employeeId}`, {
        params: { month }
      });
      return res.data;
    },

    resetStore() {
      this.penaltyRules = [];
      this.salaryStructures = [];
      this.loading = false;
      this.error = null;
    }
  }
});
