/**
 * LavaBackground Component - พื้นหลัง Lava Lamp Effect
 * ใช้ Animated Gradient + Blob Animation
 */

import React, { useEffect } from 'react';
import { View, StyleSheet, Dimensions } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import Animated, {
  useSharedValue,
  useAnimatedStyle,
  withRepeat,
  withTiming,
  withSequence,
  Easing,
  interpolate,
} from 'react-native-reanimated';
import { useAppStore } from '@/stores/appStore';

const { width, height } = Dimensions.get('window');

// Lava Blob Component
const LavaBlob = ({
  size,
  color,
  startX,
  startY,
  delay = 0,
}: {
  size: number;
  color: string;
  startX: number;
  startY: number;
  delay?: number;
}) => {
  const translateX = useSharedValue(0);
  const translateY = useSharedValue(0);
  const scale = useSharedValue(1);

  useEffect(() => {
    // X movement
    translateX.value = withRepeat(
      withSequence(
        withTiming(50, { duration: 4000 + delay, easing: Easing.inOut(Easing.ease) }),
        withTiming(-30, { duration: 3000 + delay, easing: Easing.inOut(Easing.ease) }),
        withTiming(0, { duration: 3500 + delay, easing: Easing.inOut(Easing.ease) })
      ),
      -1,
      true
    );

    // Y movement
    translateY.value = withRepeat(
      withSequence(
        withTiming(-60, { duration: 5000 + delay, easing: Easing.inOut(Easing.ease) }),
        withTiming(40, { duration: 4000 + delay, easing: Easing.inOut(Easing.ease) }),
        withTiming(0, { duration: 4500 + delay, easing: Easing.inOut(Easing.ease) })
      ),
      -1,
      true
    );

    // Scale
    scale.value = withRepeat(
      withSequence(
        withTiming(1.2, { duration: 3000 + delay, easing: Easing.inOut(Easing.ease) }),
        withTiming(0.8, { duration: 3500 + delay, easing: Easing.inOut(Easing.ease) }),
        withTiming(1, { duration: 3000 + delay, easing: Easing.inOut(Easing.ease) })
      ),
      -1,
      true
    );
  }, []);

  const animatedStyle = useAnimatedStyle(() => ({
    transform: [
      { translateX: translateX.value },
      { translateY: translateY.value },
      { scale: scale.value },
    ],
  }));

  return (
    <Animated.View
      style={[
        {
          position: 'absolute',
          left: startX,
          top: startY,
          width: size,
          height: size,
          borderRadius: size / 2,
          backgroundColor: color,
          opacity: 0.6,
        },
        animatedStyle,
      ]}
    />
  );
};

interface LavaBackgroundProps {
  variant?: 'default' | 'shopping' | 'ocean' | 'sunset' | 'crypto';
  intensity?: 'low' | 'medium' | 'high';
  children?: React.ReactNode;
}

export const LavaBackground: React.FC<LavaBackgroundProps> = ({
  variant = 'default',
  intensity = 'medium',
  children,
}) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  // Gradient colors based on variant
  const getGradientColors = (): string[] => {
    if (isDark) {
      switch (variant) {
        case 'shopping':
          return ['#1A1A2E', '#16213E', '#0F0F23'];
        case 'ocean':
          return ['#0D1B2A', '#1B263B', '#0F0F23'];
        case 'sunset':
          return ['#2D1B4E', '#1A1A2E', '#0F0F23'];
        case 'crypto':
          return ['#1A1A2E', '#0D1B2A', '#0F0F23'];
        default:
          return ['#1A1A2E', '#16213E', '#0F0F23'];
      }
    } else {
      switch (variant) {
        case 'shopping':
          return ['#FFF5F0', '#FFE8DB', '#FFFFFF'];
        case 'ocean':
          return ['#E6FAF8', '#C2F4EF', '#FFFFFF'];
        case 'sunset':
          return ['#F5EAFA', '#FFE8DB', '#FFFFFF'];
        case 'crypto':
          return ['#E6FAF8', '#EFF6FF', '#FFFFFF'];
        default:
          return ['#FFF5F0', '#E6FAF8', '#FFFFFF'];
      }
    }
  };

  // Blob colors based on variant
  const getBlobColors = (): string[] => {
    switch (variant) {
      case 'shopping':
        return ['#FF6B6B', '#FF8E53', '#FFE66D', '#FF9966'];
      case 'ocean':
        return ['#4ECDC4', '#45DBC9', '#2EC4B6', '#6DE4D7'];
      case 'sunset':
        return ['#FF6B35', '#7B2CBF', '#FF6B6B', '#A651D2'];
      case 'crypto':
        return ['#2EC4B6', '#FFD700', '#7B2CBF', '#45DBC9'];
      default:
        return ['#FF6B6B', '#FF8E53', '#FFE66D', '#4ECDC4'];
    }
  };

  // Number of blobs based on intensity
  const getBlobCount = (): number => {
    switch (intensity) {
      case 'low':
        return 3;
      case 'high':
        return 8;
      default:
        return 5;
    }
  };

  const blobColors = getBlobColors();
  const blobCount = getBlobCount();

  // Generate blob configurations
  const blobs = Array.from({ length: blobCount }, (_, i) => ({
    id: i,
    size: 100 + Math.random() * 150,
    color: blobColors[i % blobColors.length],
    startX: Math.random() * (width - 100),
    startY: Math.random() * (height - 100),
    delay: i * 500,
  }));

  return (
    <View style={styles.container}>
      {/* Base Gradient */}
      <LinearGradient
        colors={getGradientColors()}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={styles.gradient}
      />

      {/* Lava Blobs */}
      <View style={styles.blobContainer}>
        {blobs.map((blob) => (
          <LavaBlob
            key={blob.id}
            size={blob.size}
            color={blob.color}
            startX={blob.startX}
            startY={blob.startY}
            delay={blob.delay}
          />
        ))}
      </View>

      {/* Blur Overlay */}
      <View
        style={[
          styles.blurOverlay,
          { backgroundColor: isDark ? 'rgba(15, 15, 35, 0.7)' : 'rgba(255, 255, 255, 0.7)' },
        ]}
      />

      {/* Content */}
      {children && <View style={styles.content}>{children}</View>}
    </View>
  );
};

// Glass Card Component ที่ใช้คู่กับ LavaBackground
export const GlassCard: React.FC<{
  children: React.ReactNode;
  style?: any;
  className?: string;
}> = ({ children, style, className }) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  return (
    <View
      style={[
        styles.glassCard,
        {
          backgroundColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(255, 255, 255, 0.8)',
          borderColor: isDark ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)',
        },
        style,
      ]}
      className={className}
    >
      {children}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    position: 'relative',
  },
  gradient: {
    ...StyleSheet.absoluteFillObject,
  },
  blobContainer: {
    ...StyleSheet.absoluteFillObject,
    overflow: 'hidden',
  },
  blurOverlay: {
    ...StyleSheet.absoluteFillObject,
  },
  content: {
    flex: 1,
    position: 'relative',
    zIndex: 1,
  },
  glassCard: {
    borderRadius: 20,
    borderWidth: 1,
    padding: 16,
    // React Native doesn't support backdrop-blur natively
    // Use expo-blur or react-native-blur if needed
  },
});

export default LavaBackground;
