/**
 * Auth Store - จัดการ state ของ authentication
 * ใช้ Zustand สำหรับ state management
 * รองรับ Offline Mode - เก็บข้อมูล user ใน local storage
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
import * as Network from '@/services/network';
import type { User } from '@/types';

interface AuthState {
  // State
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  isInitialized: boolean;
  error: string | null;
  isOfflineMode: boolean;

  // Actions
  initialize: () => Promise<void>;
  login: (email: string, password: string) => Promise<boolean>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
  clearError: () => void;
  updateUser: (user: Partial<User>) => void;
}

/**
 * บันทึกข้อมูล user ลง SecureStore
 */
const saveUserToStorage = async (user: User): Promise<void> => {
  try {
    await SecureStore.setItemAsync(STORAGE_KEYS.USER_DATA, JSON.stringify(user));
  } catch (error) {
    console.error('Save user to storage error:', error);
  }
};

/**
 * โหลดข้อมูล user จาก SecureStore
 */
const loadUserFromStorage = async (): Promise<User | null> => {
  try {
    const userData = await SecureStore.getItemAsync(STORAGE_KEYS.USER_DATA);
    if (userData) {
      return JSON.parse(userData) as User;
    }
    return null;
  } catch (error) {
    console.error('Load user from storage error:', error);
    return null;
  }
};

export const useAuthStore = create<AuthState>((set, get) => ({
  // Initial State
  user: null,
  token: null,
  isAuthenticated: false,
  isLoading: false,
  isInitialized: false,
  error: null,
  isOfflineMode: false,

  /**
   * Initialize - เรียกตอนเปิด app
   * ตรวจสอบว่ามี token อยู่หรือไม่ และยังใช้ได้หรือไม่
   * ถ้า offline จะใช้ข้อมูลจาก local storage
   * มี timeout 10 วินาที เพื่อไม่ให้ค้างนาน
   */
  initialize: async () => {
    const INIT_TIMEOUT = 10000; // 10 seconds timeout

    // Helper function สำหรับ timeout
    const withTimeout = <T>(promise: Promise<T>, ms: number): Promise<T> => {
      return Promise.race([
        promise,
        new Promise<T>((_, reject) =>
          setTimeout(() => reject(new Error('Timeout')), ms)
        ),
      ]);
    };

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

      // ตรวจสอบว่าออนไลน์หรือไม่ (with timeout)
      let isOnline = false;
      try {
        isOnline = await withTimeout(Network.checkNetworkStatus(), 5000);
      } catch {
        console.log('Network check timeout, assuming offline');
        isOnline = false;
      }

      if (!isOnline) {
        // Offline Mode - โหลดข้อมูล user จาก local storage
        const cachedUser = await loadUserFromStorage();

        if (cachedUser) {
          set({
            user: cachedUser,
            token,
            isAuthenticated: true,
            isInitialized: true,
            isLoading: false,
            isOfflineMode: true,
          });
          return;
        }

        // ไม่มี cached user
        set({
          isAuthenticated: false,
          isInitialized: true,
          isLoading: false,
          isOfflineMode: true,
        });
        return;
      }

      // Online Mode - ตรวจสอบ token (with timeout)
      let isValid = false;
      try {
        isValid = await withTimeout(validateToken(), INIT_TIMEOUT);
      } catch {
        console.log('Token validation timeout');
        isValid = false;
      }

      if (!isValid) {
        // Token หมดอายุ - ลองใช้ cached user
        const cachedUser = await loadUserFromStorage();
        if (cachedUser) {
          set({
            user: cachedUser,
            token: null,
            isAuthenticated: false,
            isInitialized: true,
            isLoading: false,
            isOfflineMode: true,
          });
        } else {
          set({
            user: null,
            token: null,
            isAuthenticated: false,
            isInitialized: true,
            isLoading: false,
          });
        }
        return;
      }

      // ดึงข้อมูล user จาก server (with timeout)
      let user = null;
      try {
        user = await withTimeout(getCurrentUser(), INIT_TIMEOUT);
      } catch {
        console.log('Get user timeout, using cached');
        user = await loadUserFromStorage();
      }

      if (user) {
        // บันทึก user ลง storage สำหรับใช้ offline
        await saveUserToStorage(user);
      }

      set({
        user,
        token,
        isAuthenticated: true,
        isInitialized: true,
        isLoading: false,
        isOfflineMode: false,
      });
    } catch (error) {
      console.error('Initialize error:', error);

      // ลองโหลด cached user
      const cachedUser = await loadUserFromStorage();
      const token = await loadAuthToken();

      if (cachedUser && token) {
        set({
          user: cachedUser,
          token,
          isAuthenticated: true,
          isInitialized: true,
          isLoading: false,
          isOfflineMode: true,
        });
      } else {
        set({
          user: null,
          token: null,
          isAuthenticated: false,
          isInitialized: true,
          isLoading: false,
          error: 'เกิดข้อผิดพลาดในการเริ่มต้นระบบ',
        });
      }
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
        // บันทึก user ลง storage สำหรับใช้ offline
        await saveUserToStorage(response.data.user);

        set({
          user: response.data.user,
          token: response.data.token,
          isAuthenticated: true,
          isLoading: false,
          isOfflineMode: false,
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
   * ⚠️ จะไม่ลบ cached user เพื่อให้ดู profile offline ได้
   */
  logout: async () => {
    try {
      set({ isLoading: true });

      await apiLogout();

      // ลบ token แต่เก็บ user data ไว้สำหรับ offline view
      set({
        user: null,
        token: null,
        isAuthenticated: false,
        isLoading: false,
        isOfflineMode: false,
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
      const isOnline = await Network.checkNetworkStatus();

      if (!isOnline) {
        // Offline - ใช้ cached user
        const cachedUser = await loadUserFromStorage();
        if (cachedUser) {
          set({ user: cachedUser, isOfflineMode: true });
        }
        return;
      }

      const user = await getCurrentUser();
      if (user) {
        await saveUserToStorage(user);
        set({ user, isOfflineMode: false });
      }
    } catch (error) {
      console.error('Refresh user error:', error);
      // ถ้า error ลองใช้ cached user
      const cachedUser = await loadUserFromStorage();
      if (cachedUser) {
        set({ user: cachedUser, isOfflineMode: true });
      }
    }
  },

  /**
   * Update User - อัพเดทข้อมูล user ใน state และ storage
   */
  updateUser: (updatedUser: Partial<User>) => {
    const currentUser = get().user;
    if (currentUser) {
      const newUser = { ...currentUser, ...updatedUser };
      set({ user: newUser });
      saveUserToStorage(newUser);
    }
  },

  /**
   * Clear Error
   */
  clearError: () => {
    set({ error: null });
  },
}));
