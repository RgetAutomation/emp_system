import { defineStore } from 'pinia';
import api from '../axios';

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
    async employeeLogin(credentials) {
      try {
        this.error = null;
        const response = await api.post('/employee-login', credentials);
        this.setAuthData(response.data);
        // If they already have a PIN, leave pinVerified false to force them to the lock screen!
        this.pinVerified = !response.data.user.has_pin;
        return true;
      } catch (error) {
        this.error = error.response?.data?.message || 'Login failed';
        return false;
      }
    },
    async pinLogin(credentials) {
      try {
        this.error = null;
        const response = await api.post('/pin-login', credentials);
        this.setAuthData(response.data);
        this.pinVerified = true;
        return true;
      } catch (error) {
        this.error = error.response?.data?.message || 'Login failed';
        return false;
      }
    },
    async setPin(pin) {
      try {
        await api.post('/set-app-pin', { pin });
        if (this.user) {
          this.user.has_pin = true;
          localStorage.setItem('user', JSON.stringify(this.user));
        }
        this.pinVerified = true;
        return true;
      } catch (error) {
        throw error;
      }
    },
    async sendOtp(credentials) {
      try {
        this.error = null;
        const response = await api.post('/send-otp', credentials);
        return { success: true, message: response.data.message };
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to send OTP';
        return { success: false };
      }
    },
    async verifyOtp(credentials) {
      try {
        this.error = null;
        const response = await api.post('/verify-otp', credentials);
        this.setAuthData(response.data);
        this.pinVerified = !response.data.user.has_pin;
        return true;
      } catch (error) {
        this.error = error.response?.data?.message || 'Invalid OTP';
        return false;
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
    }
  }
});
