/**
 * ErrorBoundary Component — ธีมรอยัล น้ำเงินกรมท่า-ทอง
 * จับ error ที่เกิดขึ้นใน child components ป้องกัน white screen crash
 * แสดงรหัส error เสมอเพื่อช่วยในการ debug
 *
 * หน้าตา: หัวน้ำเงินลายกนก + การ์ดขาวกลางจอ (รหัส error · รายละเอียด · คัดลอก/แชร์ · ปุ่มทอง "ลองใหม่")
 * เป็น class component ใช้ hook ไม่ได้ → เลือกชุดสีจากธีมที่ผู้ใช้ตั้ง (useAppStore.themeMode)
 * ถ้าอ่านไม่ได้หรือเป็น "ตามเครื่อง" → ใช้ Appearance.getColorScheme()
 */

import React, { Component, ErrorInfo, ReactNode } from 'react';
import {
  Appearance,
  View,
  Pressable,
  StyleSheet,
  ScrollView,
  Share,
  StatusBar,
} from 'react-native';
import { Text } from '@/components/ui/Text';
import { LinearGradient } from 'expo-linear-gradient';
import { SafeAreaView } from 'react-native-safe-area-context';
import * as Clipboard from 'expo-clipboard';
import { Icon } from '@/components/ui/Icon';
import { RoyalHeader } from '@/components/ui/RoyalHeader';
import { useAppStore } from '@/stores/appStore';
import {
  DARK_THEME,
  LIGHT_THEME,
  radii,
  shadowStyle,
  spacing,
  typography,
  withAlpha,
  type AppTheme,
} from '@/theme';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
  onError?: (error: Error, errorInfo: ErrorInfo) => void;
}

interface State {
  hasError: boolean;
  error: Error | null;
  errorInfo: ErrorInfo | null;
  errorCode: string;
  copied: boolean;
}

/**
 * เลือกชุดสีตามธีมที่ผู้ใช้ตั้งไว้ (สว่าง/มืด) — "ตามเครื่อง" หรืออ่านค่าไม่ได้ = ตามระบบ
 */
const pickTheme = (): AppTheme => {
  try {
    const mode = useAppStore.getState().themeMode;
    if (mode === 'dark') return DARK_THEME;
    if (mode === 'light') return LIGHT_THEME;
  } catch {
    // อ่านค่าธีมไม่ได้ → ใช้ตามระบบ
  }
  return Appearance.getColorScheme() === 'dark' ? DARK_THEME : LIGHT_THEME;
};

/**
 * สร้างรหัส error สั้นๆ จาก error message
 */
const generateErrorCode = (error: Error | null): string => {
  if (!error) return 'ERR-UNKNOWN';

  // สร้าง hash จาก error message
  const message = error.message || error.toString();
  let hash = 0;
  for (let i = 0; i < message.length; i++) {
    const char = message.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash; // Convert to 32bit integer
  }

  // แปลงเป็น hex และตัดให้สั้น
  const hexCode = Math.abs(hash).toString(16).toUpperCase().substring(0, 6);
  return `ERR-${hexCode}`;
};

/**
 * ดึงชื่อ component ที่ทำให้เกิด error
 */
const getFailedComponent = (errorInfo: ErrorInfo | null): string => {
  if (!errorInfo?.componentStack) return 'Unknown';

  // ดึงบรรทัดแรกของ component stack
  const lines = errorInfo.componentStack.trim().split('\n');
  if (lines.length > 0) {
    const firstLine = lines[0].trim();
    // Extract component name (e.g., "in HomeScreen" -> "HomeScreen")
    const match = firstLine.match(/in\s+(\w+)/);
    if (match) return match[1];
  }
  return 'Unknown';
};

class ErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      errorInfo: null,
      errorCode: '',
      copied: false,
    };
  }

  static getDerivedStateFromError(error: Error): Partial<State> {
    // อัพเดท state เพื่อแสดง fallback UI
    return {
      hasError: true,
      error,
      errorCode: generateErrorCode(error),
    };
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    // Log error
    console.error('ErrorBoundary caught an error:', error);
    console.error('Component Stack:', errorInfo.componentStack);

    this.setState({ errorInfo });

    // เรียก callback ถ้ามี
    if (this.props.onError) {
      this.props.onError(error, errorInfo);
    }
  }

  handleRetry = () => {
    this.setState({
      hasError: false,
      error: null,
      errorInfo: null,
      errorCode: '',
      copied: false,
    });
  };

  handleCopyError = async () => {
    const { error, errorInfo, errorCode } = this.state;

    const errorReport = `
Error Report
================
รหัส: ${errorCode}
ข้อผิดพลาด: ${error?.message || 'Unknown'}
Component: ${getFailedComponent(errorInfo)}
เวลา: ${new Date().toLocaleString('th-TH')}

Stack Trace:
${error?.stack?.substring(0, 500) || 'N/A'}

Component Stack:
${errorInfo?.componentStack?.substring(0, 500) || 'N/A'}
    `.trim();

    try {
      await Clipboard.setStringAsync(errorReport);
      this.setState({ copied: true });
      setTimeout(() => this.setState({ copied: false }), 2000);
    } catch (e) {
      console.error('Copy error:', e);
    }
  };

  handleShareError = async () => {
    const { error, errorInfo, errorCode } = this.state;

    const errorReport = `
Error Report - ${errorCode}
ข้อผิดพลาด: ${error?.message || 'Unknown'}
Component: ${getFailedComponent(errorInfo)}
เวลา: ${new Date().toLocaleString('th-TH')}
    `.trim();

    try {
      await Share.share({ message: errorReport });
    } catch (e) {
      console.error('Share error:', e);
    }
  };

  render() {
    if (this.state.hasError) {
      const { error, errorInfo, errorCode, copied } = this.state;
      const failedComponent = getFailedComponent(errorInfo);

      // ถ้ามี custom fallback ให้ใช้
      if (this.props.fallback) {
        return this.props.fallback;
      }

      const { colors, gradients } = pickTheme();

      // Default error UI
      return (
        <View style={[styles.container, { backgroundColor: colors.background }]}>
          <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />
          {/* หัวน้ำเงินลายกนกด้านบน — การ์ดขาวลอยทับครึ่งหนึ่ง */}
          <RoyalHeader style={styles.hero} ornamentWidth={240} ornamentTop={10} />

          <SafeAreaView style={styles.flex} edges={['top', 'bottom']}>
            <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
              <View
                style={[
                  styles.card,
                  { backgroundColor: colors.card, borderColor: colors.border },
                  shadowStyle('lg', colors.shadowDark),
                ]}
              >
                <View style={[styles.iconBox, { backgroundColor: colors.dangerSoft }]}>
                  <Icon name="warning-circle" size={40} color={colors.danger} weight="fill" />
                </View>

                <Text style={[typography.serif, styles.center, { color: colors.textStrong }]}>เกิดข้อผิดพลาด</Text>
                <Text style={[typography.body, styles.center, styles.message, { color: colors.textMuted }]}>
                  ขออภัย เกิดข้อผิดพลาดที่ไม่คาดคิด กรุณาลองใหม่อีกครั้ง
                </Text>

                {/* Error Code - แสดงเสมอ */}
                <View
                  style={[
                    styles.errorCodeBox,
                    { backgroundColor: colors.dangerSoft, borderColor: withAlpha(colors.danger, 0.35) },
                  ]}
                >
                  <Text style={[typography.caption, { color: colors.danger }]}>รหัสข้อผิดพลาด</Text>
                  <Text selectable style={[styles.errorCode, { color: colors.danger }]}>
                    {errorCode}
                  </Text>
                </View>

                {/* Error Details - แสดงเสมอ */}
                <View style={[styles.errorDetails, { backgroundColor: colors.inset, borderColor: colors.border }]}>
                  <View style={styles.errorRow}>
                    <Text style={[styles.errorLabel, { color: colors.textMuted }]}>Component:</Text>
                    <Text style={[styles.errorValue, { color: colors.textStrong }]}>{failedComponent}</Text>
                  </View>
                  <View style={styles.errorRow}>
                    <Text style={[styles.errorLabel, { color: colors.textMuted }]}>Error:</Text>
                    <Text style={[styles.errorValue, { color: colors.textStrong }]} numberOfLines={3}>
                      {error?.message || 'Unknown error'}
                    </Text>
                  </View>
                </View>

                {/* Action Buttons */}
                <View style={styles.buttonRow}>
                  <Pressable
                    onPress={this.handleCopyError}
                    accessibilityRole="button"
                    style={({ pressed }) => [
                      styles.secondaryButton,
                      { backgroundColor: colors.surface, borderColor: colors.border, opacity: pressed ? 0.7 : 1 },
                    ]}
                  >
                    <Icon
                      name={copied ? 'check-circle' : 'copy'}
                      size={18}
                      color={copied ? colors.success : colors.goldDeep}
                      weight={copied ? 'fill' : 'regular'}
                    />
                    <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>
                      {copied ? 'คัดลอกแล้ว' : 'คัดลอก'}
                    </Text>
                  </Pressable>

                  <Pressable
                    onPress={this.handleShareError}
                    accessibilityRole="button"
                    style={({ pressed }) => [
                      styles.secondaryButton,
                      { backgroundColor: colors.surface, borderColor: colors.border, opacity: pressed ? 0.7 : 1 },
                    ]}
                  >
                    <Icon name="share-network" size={18} color={colors.goldDeep} />
                    <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>แชร์</Text>
                  </Pressable>
                </View>

                <Pressable
                  onPress={this.handleRetry}
                  accessibilityRole="button"
                  style={({ pressed }) => [styles.retryButton, { opacity: pressed ? 0.85 : 1 }]}
                >
                  <LinearGradient
                    colors={gradients.primary}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 0.25, y: 1 }}
                    style={styles.retryGradient}
                  >
                    <Icon name="arrows-clockwise" size={20} color={colors.textOnGold} weight="bold" />
                    <Text style={[styles.retryText, { color: colors.textOnGold }]}>ลองใหม่</Text>
                  </LinearGradient>
                </Pressable>

                {/* Hint */}
                <Text style={[typography.caption, styles.center, { color: colors.textFaint }]}>
                  หากปัญหายังคงอยู่ กรุณาแจ้ง รหัส "{errorCode}" ให้ทีมพัฒนา
                </Text>
              </View>
            </ScrollView>
          </SafeAreaView>
        </View>
      );
    }

    return this.props.children;
  }
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  flex: {
    flex: 1,
  },
  hero: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: 300,
  },
  content: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: spacing.screen,
    paddingTop: spacing.xxxl * 2,
  },
  card: {
    alignItems: 'center',
    borderRadius: radii.xxl,
    borderWidth: 1,
    padding: spacing.xl,
    gap: spacing.sm,
  },
  iconBox: {
    width: 80,
    height: 80,
    borderRadius: 28,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.sm,
  },
  center: {
    textAlign: 'center',
  },
  message: {
    marginBottom: spacing.sm,
  },
  // Error Code Box
  errorCodeBox: {
    alignSelf: 'stretch',
    alignItems: 'center',
    borderRadius: radii.md,
    borderWidth: 1,
    borderStyle: 'dashed',
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.xl,
  },
  errorCode: {
    fontSize: 20,
    lineHeight: 28,
    fontWeight: '700',
    letterSpacing: 2,
  },
  // Error Details
  errorDetails: {
    alignSelf: 'stretch',
    borderRadius: radii.md,
    borderWidth: 1,
    padding: spacing.lg,
    gap: spacing.sm,
    marginTop: spacing.xs,
  },
  errorRow: {
    flexDirection: 'row',
  },
  errorLabel: {
    fontSize: 12,
    lineHeight: 18,
    fontWeight: '600',
    width: 84,
  },
  errorValue: {
    flex: 1,
    fontSize: 12,
    lineHeight: 18,
  },
  // Buttons
  buttonRow: {
    alignSelf: 'stretch',
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.sm,
  },
  secondaryButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    height: 46,
    borderRadius: radii.md,
    borderWidth: 1,
  },
  retryButton: {
    alignSelf: 'stretch',
    marginTop: spacing.xs,
    marginBottom: spacing.xs,
  },
  retryGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    height: 54,
    borderRadius: radii.lg,
  },
  retryText: {
    fontSize: 16.5,
    fontWeight: '700',
  },
});

export default ErrorBoundary;
