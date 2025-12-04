/**
 * Auth Store - จัดการ state ของ authentication
 * ใช้ Zustand สำหรับ state management
 */

import { create } from 'zustand';
import * as SecureStore from 'expo-secure-store';
import {
  login as apiLogin,
  logout as apiLogout,
  getCurrentUser,
  validateToken,
  loadAuthToken,
  setAuthHeader,
} from '@/services/api';
import { STORAGE_KEYS } from '@/constants';
import type { User } from '@/types';

interface AuthState {
  // State
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  isInitialized: boolean;
  error: string | null;

  // Actions
  initialize: () => Promise<void>;
  login: (email: string, password: string) => Promise<boolean>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
  clearError: () => void;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  // Initial State
  user: null,
  token: null,
  isAuthenticated: false,
  isLoading: false,
  isInitialized: false,
  error: null,

  /**
   * Initialize - เรียกตอนเปิด app
   * ตรวจสอบว่ามี token อยู่หรือไม่ และยังใช้ได้หรือไม่
   */
  initialize: async () => {
    try {
      set({ isLoading: true });

      // โหลด token จาก storage
      const token = await loadAuthToken();

      if (!token) {
        set({
          isAuthenticated: false,
          isInitialized: true,
          isLoading: false,
        });
        return;
      }

      // ตั้งค่า header
      setAuthHeader(token);

      // ตรวจสอบ token ยังใช้ได้หรือไม่
      const isValid = await validateToken();

      if (!isValid) {
        // Token หมดอายุ
        set({
          user: null,
          token: null,
          isAuthenticated: false,
          isInitialized: true,
          isLoading: false,
        });
        return;
      }

      // ดึงข้อมูล user
      const user = await getCurrentUser();

      set({
        user,
        token,
        isAuthenticated: true,
        isInitialized: true,
        isLoading: false,
      });
    } catch (error) {
      console.error('Initialize error:', error);
      set({
        user: null,
        token: null,
        isAuthenticated: false,
        isInitialized: true,
        isLoading: false,
        error: 'เกิดข้อผิดพลาดในการเริ่มต้นระบบ',
      });
    }
  },

  /**
   * Login - เข้าสู่ระบบ
   */
  login: async (email: string, password: string) => {
    try {
      set({ isLoading: true, error: null });

      const response = await apiLogin(email, password);

      if (response.success && response.data) {
        set({
          user: response.data.user,
          token: response.data.token,
          isAuthenticated: true,
          isLoading: false,
        });
        return true;
      }

      set({
        error: response.message || 'เข้าสู่ระบบไม่สำเร็จ',
        isLoading: false,
      });
      return false;
    } catch (error) {
      console.error('Login error:', error);
      set({
        error: 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ',
        isLoading: false,
      });
      return false;
    }
  },

  /**
   * Logout - ออกจากระบบ
   */
  logout: async () => {
    try {
      set({ isLoading: true });

      await apiLogout();

      set({
        user: null,
        token: null,
        isAuthenticated: false,
        isLoading: false,
      });
    } catch (error) {
      console.error('Logout error:', error);
      // ล้างข้อมูลแม้จะ error
      set({
        user: null,
        token: null,
        isAuthenticated: false,
        isLoading: false,
      });
    }
  },

  /**
   * Refresh User - ดึงข้อมูล user ใหม่
   */
  refreshUser: async () => {
    try {
      const user = await getCurrentUser();
      if (user) {
        set({ user });
      }
    } catch (error) {
      console.error('Refresh user error:', error);
    }
  },

  /**
   * Clear Error
   */
  clearError: () => {
    set({ error: null });
  },
}));
