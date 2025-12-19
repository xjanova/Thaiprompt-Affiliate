/**
 * ErrorBoundary Component
 * จับ error ที่เกิดขึ้นใน child components ป้องกัน white screen crash
 * ⭐ แสดงรหัส error เสมอเพื่อช่วยในการ debug
 */

import React, { Component, ErrorInfo, ReactNode } from 'react';
import {
  View,
  Text,
  Pressable,
  StyleSheet,
  ScrollView,
  Share,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import * as Clipboard from 'expo-clipboard';

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
    console.error('🚨 ErrorBoundary caught an error:', error);
    console.error('📍 Component Stack:', errorInfo.componentStack);

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
🚨 Error Report
================
รหัส: ${errorCode}
ข้อผิดพลาด: ${error?.message || 'Unknown'}
Component: ${getFailedComponent(errorInfo)}
เวลา: ${new Date().toLocaleString('th-TH')}

📍 Stack Trace:
${error?.stack?.substring(0, 500) || 'N/A'}

📦 Component Stack:
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
🚨 Error Report - ${errorCode}
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

      // Default error UI
      return (
        <View style={styles.container}>
          <LinearGradient
            colors={['#0F0F23', '#1a1a2e', '#16213e']}
            style={StyleSheet.absoluteFill}
          />

          <ScrollView
            contentContainerStyle={styles.content}
            showsVerticalScrollIndicator={false}
          >
            <View style={styles.iconBox}>
              <Text style={styles.icon}>⚠️</Text>
            </View>

            <Text style={styles.title}>เกิดข้อผิดพลาด</Text>
            <Text style={styles.message}>
              ขออภัย เกิดข้อผิดพลาดที่ไม่คาดคิด กรุณาลองใหม่อีกครั้ง
            </Text>

            {/* ⭐ Error Code - แสดงเสมอ */}
            <View style={styles.errorCodeBox}>
              <Text style={styles.errorCodeLabel}>รหัสข้อผิดพลาด</Text>
              <Text style={styles.errorCode}>{errorCode}</Text>
            </View>

            {/* Error Details - แสดงเสมอ */}
            <View style={styles.errorDetails}>
              <View style={styles.errorRow}>
                <Text style={styles.errorLabel}>Component:</Text>
                <Text style={styles.errorValue}>{failedComponent}</Text>
              </View>
              <View style={styles.errorRow}>
                <Text style={styles.errorLabel}>Error:</Text>
                <Text style={styles.errorValue} numberOfLines={3}>
                  {error?.message || 'Unknown error'}
                </Text>
              </View>
            </View>

            {/* Action Buttons */}
            <View style={styles.buttonRow}>
              <Pressable style={styles.copyButton} onPress={this.handleCopyError}>
                <Text style={styles.copyButtonText}>
                  {copied ? '✅ คัดลอกแล้ว' : '📋 คัดลอก'}
                </Text>
              </Pressable>

              <Pressable style={styles.shareButton} onPress={this.handleShareError}>
                <Text style={styles.shareButtonText}>📤 แชร์</Text>
              </Pressable>
            </View>

            <Pressable style={styles.retryButton} onPress={this.handleRetry}>
              <LinearGradient
                colors={['#3B82F6', '#2563EB']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.retryGradient}
              >
                <Text style={styles.retryText}>🔄 ลองใหม่</Text>
              </LinearGradient>
            </Pressable>

            {/* Hint */}
            <Text style={styles.hint}>
              หากปัญหายังคงอยู่ กรุณาแจ้ง รหัส "{errorCode}" ให้ทีมพัฒนา
            </Text>
          </ScrollView>
        </View>
      );
    }

    return this.props.children;
  }
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
  },
  content: {
    flexGrow: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  iconBox: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: 'rgba(239, 68, 68, 0.2)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 24,
  },
  icon: {
    fontSize: 40,
  },
  title: {
    fontSize: 22,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 12,
    textAlign: 'center',
  },
  message: {
    fontSize: 14,
    color: '#9CA3AF',
    textAlign: 'center',
    lineHeight: 22,
    marginBottom: 24,
  },
  // Error Code Box
  errorCodeBox: {
    backgroundColor: 'rgba(239, 68, 68, 0.15)',
    borderRadius: 12,
    paddingVertical: 12,
    paddingHorizontal: 24,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: 'rgba(239, 68, 68, 0.3)',
    alignItems: 'center',
  },
  errorCodeLabel: {
    fontSize: 11,
    color: '#F87171',
    marginBottom: 4,
  },
  errorCode: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#EF4444',
    letterSpacing: 2,
  },
  // Error Details
  errorDetails: {
    backgroundColor: 'rgba(255, 255, 255, 0.05)',
    borderRadius: 12,
    padding: 16,
    marginBottom: 20,
    width: '100%',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.1)',
  },
  errorRow: {
    flexDirection: 'row',
    marginBottom: 8,
  },
  errorLabel: {
    fontSize: 12,
    color: '#9CA3AF',
    width: 80,
    fontWeight: '600',
  },
  errorValue: {
    fontSize: 12,
    color: '#FFFFFF',
    flex: 1,
  },
  // Buttons
  buttonRow: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 16,
  },
  copyButton: {
    backgroundColor: 'rgba(255, 255, 255, 0.1)',
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.2)',
  },
  copyButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
  },
  shareButton: {
    backgroundColor: 'rgba(255, 255, 255, 0.1)',
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.2)',
  },
  shareButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
  },
  retryButton: {
    borderRadius: 14,
    overflow: 'hidden',
    shadowColor: '#3B82F6',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 8,
    marginBottom: 20,
  },
  retryGradient: {
    paddingHorizontal: 48,
    paddingVertical: 14,
    borderRadius: 14,
  },
  retryText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
  hint: {
    fontSize: 11,
    color: '#6B7280',
    textAlign: 'center',
    lineHeight: 18,
  },
});

export default ErrorBoundary;
