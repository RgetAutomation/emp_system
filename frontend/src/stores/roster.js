import { defineStore } from 'pinia';
import api from '../axios';

export const useRosterStore = defineStore('roster', {
  state: () => ({
    shifts: [],
    rosters: [],
    employeeRosters: [],
    loading: false,
    error: null,
  }),
  actions: {
    async fetchShifts() {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get('/shifts');
        this.shifts = response.data;
        return this.shifts;
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to fetch shifts';
        throw err;
      } finally {
        this.loading = false;
      }
    },
    async createShift(data) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/shifts', data);
        this.shifts.push(response.data);
        return response.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to create shift';
        throw err;
      } finally {
        this.loading = false;
      }
    },
    async updateShift(id, data) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.put(`/shifts/${id}`, data);
        const index = this.shifts.findIndex(s => s.id === id);
        if (index !== -1) {
          this.shifts[index] = response.data;
        }
        return response.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to update shift';
        throw err;
      } finally {
        this.loading = false;
      }
    },
    async deleteShift(id) {
      this.loading = true;
      this.error = null;
      try {
        await api.delete(`/shifts/${id}`);
        this.shifts = this.shifts.filter(s => s.id !== id);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to delete shift';
        throw err;
      } finally {
        this.loading = false;
      }
    },
    async fetchRosters(startDate, endDate) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get('/rosters', {
          params: { start_date: startDate, end_date: endDate }
        });
        this.rosters = response.data;
        return this.rosters;
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to fetch rosters';
        throw err;
      } finally {
        this.loading = false;
      }
    },
    async assignRosters(assignments) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/rosters/assign', { assignments });
        // Update local rosters with returned values
        // Let's refetch to keep state perfectly synchronized
        return response.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to assign rosters';
        throw err;
      } finally {
        this.loading = false;
      }
    },
    async fetchEmployeeRoster(startDate, endDate) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get('/employee/roster', {
          params: { start_date: startDate, end_date: endDate }
        });
        this.employeeRosters = response.data;
        return this.employeeRosters;
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to fetch employee roster';
        throw err;
      } finally {
        this.loading = false;
      }
    }
  }
});
