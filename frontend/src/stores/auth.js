import { defineStore } from 'pinia';
import api from '../axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('token') || null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.role === 'admin',
    isEmployee: (state) => state.user?.role === 'employee',
  },
  actions: {
    async login(credentials) {
      try {
        const response = await api.post('/login', credentials);
        this.setAuthData(response.data);
        return true;
      } catch (error) {
        throw error;
      }
    },
    async registerCompany(data) {
      try {
        const response = await api.post('/register-company', data);
        this.setAuthData(response.data);
        return true;
      } catch (error) {
        throw error;
      }
    },
    async logout() {
      try {
        await api.post('/logout', null, {
          headers: { Authorization: `Bearer ${this.token}` }
        });
      } catch (error) {
        console.error("Logout failed", error);
      } finally {
        this.clearAuthData();
      }
    },
    setAuthData(data) {
      this.user = data.user;
      this.token = data.access_token;
      localStorage.setItem('user', JSON.stringify(data.user));
      localStorage.setItem('token', data.access_token);
      // Update axios default header
      api.defaults.headers.common['Authorization'] = `Bearer ${data.access_token}`;
    },
    clearAuthData() {
      this.user = null;
      this.token = null;
      localStorage.removeItem('user');
      localStorage.removeItem('token');
      delete api.defaults.headers.common['Authorization'];
    }
  }
});
