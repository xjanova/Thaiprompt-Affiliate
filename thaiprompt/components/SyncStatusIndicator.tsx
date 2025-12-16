/**
 * SyncStatusIndicator Component
 * แสดงสถานะการ sync กับเซิร์ฟเวอร์แบบสวยงาม
 *
 * Features:
 * - แสดงสถานะ online/offline/syncing/error
 * - Animation กระพริบเมื่อกำลัง sync
 * - แสดงเวลา sync ล่าสุด
 * - รองรับ dark mode
 */

import React, { useEffect, useRef } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Animated,
  Easing,
  Pressable,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { BlurView } from 'expo-blur';
import { useSyncStore, SyncStatus } from '@/stores/syncStore';

interface SyncStatusConfig {
  icon: string;
  label: string;
  colors: readonly [string, string];
  glowColor: string;
}

const STATUS_CONFIG: Record<SyncStatus, SyncStatusConfig> = {
  online: {
    icon: '🟢',
    label: 'ออนไลน์',
    colors: ['#10B981', '#059669'] as const,
    glowColor: 'rgba(16, 185, 129, 0.5)',
  },
  offline: {
    icon: '🔴',
    label: 'ออฟไลน์',
    colors: ['#EF4444', '#DC2626'] as const,
    glowColor: 'rgba(239, 68, 68, 0.5)',
  },
  syncing: {
    icon: '🔄',
    label: 'กำลัง Sync...',
    colors: ['#3B82F6', '#2563EB'] as const,
    glowColor: 'rgba(59, 130, 246, 0.5)',
  },
  error: {
    icon: '⚠️',
    label: 'ผิดพลาด',
    colors: ['#F59E0B', '#D97706'] as const,
    glowColor: 'rgba(245, 158, 11, 0.5)',
  },
};

interface Props {
  compact?: boolean;
  showLastSync?: boolean;
  onPress?: () => void;
}

export const SyncStatusIndicator: React.FC<Props> = ({
  compact = false,
  showLastSync = true,
  onPress,
}) => {
  const { status, lastSyncTime, pendingCount, isConnected } = useSyncStore();
  const config = STATUS_CONFIG[status];

  // Animations
  const pulseAnim = useRef(new Animated.Value(1)).current;
  const rotateAnim = useRef(new Animated.Value(0)).current;
  const glowAnim = useRef(new Animated.Value(0.5)).current;

  // Pulse animation สำหรับสถานะ syncing
  useEffect(() => {
    if (status === 'syncing') {
      // Rotate animation
      const rotate = Animated.loop(
        Animated.timing(rotateAnim, {
          toValue: 1,
          duration: 1000,
          easing: Easing.linear,
          useNativeDriver: true,
        })
      );
      rotate.start();

      // Pulse animation
      const pulse = Animated.loop(
        Animated.sequence([
          Animated.timing(pulseAnim, {
            toValue: 1.1,
            duration: 500,
            easing: Easing.inOut(Easing.ease),
            useNativeDriver: true,
          }),
          Animated.timing(pulseAnim, {
            toValue: 1,
            duration: 500,
            easing: Easing.inOut(Easing.ease),
            useNativeDriver: true,
          }),
        ])
      );
      pulse.start();

      return () => {
        rotate.stop();
        pulse.stop();
        rotateAnim.setValue(0);
        pulseAnim.setValue(1);
      };
    } else {
      // Reset animations
      rotateAnim.setValue(0);
      pulseAnim.setValue(1);
    }
  }, [status, rotateAnim, pulseAnim]);

  // Glow animation
  useEffect(() => {
    const glow = Animated.loop(
      Animated.sequence([
        Animated.timing(glowAnim, {
          toValue: 1,
          duration: 1500,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: false,
        }),
        Animated.timing(glowAnim, {
          toValue: 0.5,
          duration: 1500,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: false,
        }),
      ])
    );
    glow.start();
    return () => glow.stop();
  }, [glowAnim]);

  // Format last sync time
  const formatLastSync = (): string => {
    if (!lastSyncTime) return '';
    const now = new Date();
    const diff = now.getTime() - lastSyncTime.getTime();
    const minutes = Math.floor(diff / 60000);

    if (minutes < 1) return 'เมื่อสักครู่';
    if (minutes < 60) return `${minutes} นาทีที่แล้ว`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours} ชม.ที่แล้ว`;
    return lastSyncTime.toLocaleDateString('th-TH');
  };

  const spin = rotateAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg'],
  });

  // Compact version - แสดงแค่ไอคอนเล็กๆ
  if (compact) {
    return (
      <Pressable onPress={onPress}>
        <Animated.View
          style={[
            styles.compactContainer,
            { transform: [{ scale: pulseAnim }] },
          ]}
        >
          {/* Glow effect */}
          <Animated.View
            style={[
              styles.compactGlow,
              {
                backgroundColor: config.glowColor,
                opacity: glowAnim,
              },
            ]}
          />

          <LinearGradient
            colors={config.colors as unknown as string[]}
            style={styles.compactBadge}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
          >
            <Animated.Text
              style={[
                styles.compactIcon,
                status === 'syncing' && { transform: [{ rotate: spin }] },
              ]}
            >
              {config.icon}
            </Animated.Text>
          </LinearGradient>

          {/* Pending count badge */}
          {pendingCount > 0 && (
            <View style={styles.pendingBadge}>
              <Text style={styles.pendingText}>
                {pendingCount > 9 ? '9+' : pendingCount}
              </Text>
            </View>
          )}
        </Animated.View>
      </Pressable>
    );
  }

  // Full version - แสดงข้อมูลครบถ้วน
  return (
    <Pressable onPress={onPress} style={styles.container}>
      <BlurView intensity={20} tint="dark" style={styles.blurContainer}>
        <LinearGradient
          colors={['rgba(15, 23, 42, 0.9)', 'rgba(30, 41, 59, 0.9)']}
          style={styles.gradientContainer}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
        >
          {/* Status indicator */}
          <Animated.View
            style={[
              styles.statusDot,
              { transform: [{ scale: pulseAnim }] },
            ]}
          >
            {/* Glow ring */}
            <Animated.View
              style={[
                styles.glowRing,
                {
                  backgroundColor: config.glowColor,
                  opacity: glowAnim,
                },
              ]}
            />

            <LinearGradient
              colors={config.colors as unknown as string[]}
              style={styles.statusDotInner}
            >
              <Animated.Text
                style={[
                  styles.statusIcon,
                  status === 'syncing' && { transform: [{ rotate: spin }] },
                ]}
              >
                {config.icon}
              </Animated.Text>
            </LinearGradient>
          </Animated.View>

          {/* Status text */}
          <View style={styles.textContainer}>
            <Text style={styles.statusLabel}>{config.label}</Text>
            {showLastSync && lastSyncTime && status === 'online' && (
              <Text style={styles.lastSyncText}>
                Sync: {formatLastSync()}
              </Text>
            )}
            {pendingCount > 0 && (
              <Text style={styles.pendingCountText}>
                รอส่ง: {pendingCount} รายการ
              </Text>
            )}
          </View>

          {/* Connection type indicator */}
          <View style={styles.connectionBadge}>
            <Text style={styles.connectionIcon}>
              {isConnected ? '📶' : '📵'}
            </Text>
          </View>
        </LinearGradient>
      </BlurView>
    </Pressable>
  );
};

const styles = StyleSheet.create({
  // Full version styles
  container: {
    marginHorizontal: 16,
    marginVertical: 4,
    borderRadius: 16,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 8,
  },
  blurContainer: {
    borderRadius: 16,
    overflow: 'hidden',
  },
  gradientContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.1)',
  },
  statusDot: {
    width: 36,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
  },
  glowRing: {
    position: 'absolute',
    width: 40,
    height: 40,
    borderRadius: 20,
  },
  statusDotInner: {
    width: 32,
    height: 32,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 4,
    elevation: 4,
  },
  statusIcon: {
    fontSize: 16,
  },
  textContainer: {
    flex: 1,
    marginLeft: 10,
  },
  statusLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  lastSyncText: {
    fontSize: 11,
    color: 'rgba(255, 255, 255, 0.6)',
    marginTop: 2,
  },
  pendingCountText: {
    fontSize: 11,
    color: '#F59E0B',
    marginTop: 2,
    fontWeight: '500',
  },
  connectionBadge: {
    paddingHorizontal: 8,
  },
  connectionIcon: {
    fontSize: 18,
  },

  // Compact version styles
  compactContainer: {
    width: 32,
    height: 32,
    alignItems: 'center',
    justifyContent: 'center',
  },
  compactGlow: {
    position: 'absolute',
    width: 36,
    height: 36,
    borderRadius: 18,
  },
  compactBadge: {
    width: 28,
    height: 28,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 4,
    elevation: 4,
  },
  compactIcon: {
    fontSize: 14,
  },
  pendingBadge: {
    position: 'absolute',
    top: -4,
    right: -4,
    minWidth: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: '#EF4444',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 4,
    borderWidth: 2,
    borderColor: '#0F172A',
  },
  pendingText: {
    fontSize: 9,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
});

export default SyncStatusIndicator;
