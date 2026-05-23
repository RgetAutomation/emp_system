import { defineStore } from 'pinia';
import api from '../axios';
import { useAdminStore } from './admin';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('token') || null,
    error: null,
    pinVerified: false,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.role === 'admin',
    isEmployee: (state) => state.user?.role === 'employee',
    isPinVerified: (state) => state.pinVerified,
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

      try {
        const response = await api.post('/register-company', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
            'Accept': 'application/json'
          }
        });
        
        // Ensure response structure matches new API return
        const token = response.data.access_token || response.data.token;
        if (token) {
          this.setAuthData({
            user: response.data.user,
            access_token: token
          });
          return true;
        } else {
          throw new Error('Registration succeeded but no token received.');
        }
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
    async fetchCurrentUser() {
      try {
        const response = await api.get('/user');
        this.user = response.data;
        localStorage.setItem('user', JSON.stringify(response.data));
      } catch (error) {
        console.error("Fetch current user failed", error);
        if (error.response?.status === 401) {
          this.clearAuthData();
        }
      }
    },
    setAuthData(data) {
      this.user = data.user;
      this.token = data.access_token;
      
      // Store standard auth data
      localStorage.setItem('user', JSON.stringify(data.user));
      localStorage.setItem('token', data.access_token);
      
      // Explicitly store the specific fields requested for persistent login
      if (data.user && data.user.employee_id) {
        localStorage.setItem('Employee ID', data.user.employee_id);
        localStorage.setItem('App Token', data.access_token);
        if (data.user.device_id) {
          localStorage.setItem('Device ID', data.user.device_id);
        }
      }

      // Update axios default header
      api.defaults.headers.common['Authorization'] = `Bearer ${data.access_token}`;
    },
    clearAuthData() {
      this.user = null;
      this.token = null;
      localStorage.removeItem('user');
      localStorage.removeItem('token');
      delete api.defaults.headers.common['Authorization'];
      
      const adminStore = useAdminStore();
      adminStore.resetStore();
    }
  }
});
