import { defineStore } from 'pinia';
import api from '../axios';

export const useLeaveStore = defineStore('leave', {
  state: () => ({
    leaves: [],
    myLeaves: [],
    holidays: [],
    stats: null,
    loading: false,
    error: null,
  }),
  actions: {
    // Holidays Actions
    async fetchHolidays(year = null) {
      this.loading = true;
      try {
        const y = year || new Date().getFullYear();
        const response = await api.get(`/holidays?year=${y}`);
        this.holidays = response.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load holidays';
        throw err;
      } finally {
        this.loading = false;
      }
    },

    async addHoliday(data) {
      try {
        const response = await api.post('/holidays', data);
        this.holidays.push(response.data);
        return response.data;
      } catch (err) {
        throw err;
      }
    },

    async updateHoliday(id, data) {
      try {
        const response = await api.put(`/holidays/${id}`, data);
        const idx = this.holidays.findIndex(h => h.id === id);
        if (idx !== -1) {
          this.holidays[idx] = response.data;
        }
        return response.data;
      } catch (err) {
        throw err;
      }
    },

    async deleteHoliday(id) {
      try {
        await api.delete(`/holidays/${id}`);
        this.holidays = this.holidays.filter(h => h.id !== id);
      } catch (err) {
        throw err;
      }
    },

    async updateWeeklyOff(weeklyOff) {
      try {
        const response = await api.post('/holidays/weekly-off', { weekly_off: weeklyOff });
        // Update user settings in auth store if needed
        return response.data;
      } catch (err) {
        throw err;
      }
    },

    // Admin: fetch all company leaves (with optional filters)
    async fetchLeaves(filters = {}) {
      this.loading = true;
      try {
        const params = new URLSearchParams(filters).toString();
        const response = await api.get(`/leaves${params ? '?' + params : ''}`);
        this.leaves = response.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load leaves';
        throw err;
      } finally {
        this.loading = false;
      }
    },

    // Employee: fetch own leaves
    async fetchMyLeaves() {
      this.loading = true;
      try {
        const response = await api.get('/leaves');
        this.myLeaves = response.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load your leaves';
        throw err;
      } finally {
        this.loading = false;
      }
    },

    // Employee: apply for leave
    async applyLeave(data) {
      try {
        const response = await api.post('/leaves', data);
        this.myLeaves.unshift(response.data);
        return response.data;
      } catch (err) {
        throw err;
      }
    },

    // Admin: approve or reject
    async updateLeaveStatus(id, status, adminNote = '') {
      try {
        const response = await api.patch(`/leaves/${id}`, { status, admin_note: adminNote });
        const idx = this.leaves.findIndex(l => l.id === id);
        if (idx !== -1) this.leaves[idx] = response.data;
        return response.data;
      } catch (err) {
        throw err;
      }
    },

    // Employee: cancel own pending leave
    async cancelLeave(id) {
      try {
        const response = await api.patch(`/leaves/${id}`, { status: 'cancelled' });
        const idx = this.myLeaves.findIndex(l => l.id === id);
        if (idx !== -1) this.myLeaves[idx] = response.data;
        return response.data;
      } catch (err) {
        throw err;
      }
    },

    // Admin: delete leave record
    async deleteLeave(id) {
      try {
        await api.delete(`/leaves/${id}`);
        this.leaves = this.leaves.filter(l => l.id !== id);
      } catch (err) {
        throw err;
      }
    },

    // Fetch stats
    async fetchStats(year = null) {
      try {
        const params = year ? `?year=${year}` : '';
        const response = await api.get(`/leaves/stats${params}`);
        this.stats = response.data;
        return response.data;
      } catch (err) {
        throw err;
      }
    },

    reset() {
      this.leaves = [];
      this.myLeaves = [];
      this.holidays = [];
      this.stats = null;
      this.loading = false;
      this.error = null;
    },
  },
});
