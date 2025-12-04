/**
 * App Store - จัดการ state ทั่วไปของ app
 */

import { create } from 'zustand';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Appearance } from 'react-native';

type ThemeMode = 'light' | 'dark' | 'system';
type Language = 'th' | 'en';

interface AppState {
  // State
  themeMode: ThemeMode;
  resolvedTheme: 'light' | 'dark';
  language: Language;
  isOnline: boolean;
  isAppReady: boolean;

  // Actions
  setThemeMode: (mode: ThemeMode) => void;
  setLanguage: (lang: Language) => void;
  setIsOnline: (online: boolean) => void;
  setAppReady: (ready: boolean) => void;
  loadSettings: () => Promise<void>;
}

export const useAppStore = create<AppState>((set, get) => ({
  // Initial State
  themeMode: 'system',
  resolvedTheme: Appearance.getColorScheme() || 'light',
  language: 'th',
  isOnline: true,
  isAppReady: false,

  /**
   * ตั้งค่า Theme Mode
   */
  setThemeMode: (mode: ThemeMode) => {
    let resolvedTheme: 'light' | 'dark';

    if (mode === 'system') {
      resolvedTheme = Appearance.getColorScheme() || 'light';
    } else {
      resolvedTheme = mode;
    }

    set({ themeMode: mode, resolvedTheme });

    // บันทึกลง storage
    AsyncStorage.setItem('themeMode', mode).catch(console.error);
  },

  /**
   * ตั้งค่าภาษา
   */
  setLanguage: (lang: Language) => {
    set({ language: lang });
    AsyncStorage.setItem('language', lang).catch(console.error);
  },

  /**
   * ตั้งค่าสถานะ online
   */
  setIsOnline: (online: boolean) => {
    set({ isOnline: online });
  },

  /**
   * ตั้งค่า app ready
   */
  setAppReady: (ready: boolean) => {
    set({ isAppReady: ready });
  },

  /**
   * โหลด settings จาก storage
   */
  loadSettings: async () => {
    try {
      const [themeMode, language] = await Promise.all([
        AsyncStorage.getItem('themeMode'),
        AsyncStorage.getItem('language'),
      ]);

      const mode = (themeMode as ThemeMode) || 'system';
      let resolvedTheme: 'light' | 'dark';

      if (mode === 'system') {
        resolvedTheme = Appearance.getColorScheme() || 'light';
      } else {
        resolvedTheme = mode;
      }

      set({
        themeMode: mode,
        resolvedTheme,
        language: (language as Language) || 'th',
      });
    } catch (error) {
      console.error('Load settings error:', error);
    }
  },
}));

// Listen to system theme changes
Appearance.addChangeListener(({ colorScheme }) => {
  const { themeMode } = useAppStore.getState();
  if (themeMode === 'system') {
    useAppStore.setState({ resolvedTheme: colorScheme || 'light' });
  }
});
