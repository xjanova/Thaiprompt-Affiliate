/**
 * useTheme — ดึงชุดสีตามโหมดมืด/สว่าง
 *
 * ลำดับการตัดสินใจ:
 *   1. ผู้ใช้ตั้งธีมในหน้า "ตั้งค่า" (useAppStore.themeMode = light | dark) → ใช้ค่านั้น
 *   2. themeMode = system → ตามระบบ (useColorScheme)
 */

import { useMemo } from 'react';
import { useColorScheme } from 'react-native';
import { useAppStore } from '@/stores/appStore';
import {
  darkColors,
  darkGradients,
  lightColors,
  lightGradients,
  buttonEdgeColors,
  type ThemeColors,
  type ThemeGradients,
} from './tokens';

export interface AppTheme {
  isDark: boolean;
  colors: ThemeColors;
  gradients: ThemeGradients;
  buttonEdges: (typeof buttonEdgeColors)['light'] | (typeof buttonEdgeColors)['dark'];
}

/** ธีมคงที่ (ใช้นอก component เช่นใน StyleSheet ที่ไม่ขึ้นกับโหมด) */
export const LIGHT_THEME: AppTheme = {
  isDark: false,
  colors: lightColors,
  gradients: lightGradients,
  buttonEdges: buttonEdgeColors.light,
};

export const DARK_THEME: AppTheme = {
  isDark: true,
  colors: darkColors,
  gradients: darkGradients,
  buttonEdges: buttonEdgeColors.dark,
};

/**
 * hook หลักของธีม
 *
 * @example
 * const { colors, gradients, isDark } = useTheme();
 * <View style={{ backgroundColor: colors.background }} />
 */
export const useTheme = (): AppTheme => {
  const systemScheme = useColorScheme();
  const themeMode = useAppStore((state) => state.themeMode);

  const isDark = themeMode === 'system' ? systemScheme === 'dark' : themeMode === 'dark';

  return useMemo(() => (isDark ? DARK_THEME : LIGHT_THEME), [isDark]);
};
