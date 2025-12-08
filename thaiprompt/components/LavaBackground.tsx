/**
 * LavaBackground Component - พื้นหลัง Lava Lamp Effect
 * Premium Design with RGB Glow in Dark Mode + Enhanced Blur
 */

import React, { useEffect, useMemo } from 'react';
import { View, StyleSheet, Dimensions } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import Animated, {
  useSharedValue,
  useAnimatedStyle,
  withRepeat,
  withTiming,
  withSequence,
  Easing,
} from 'react-native-reanimated';
import { useAppStore } from '@/stores/appStore';

const { width, height } = Dimensions.get('window');

// RGB Glow Blob for Dark Mode
const RGBGlowBlob = ({
  size,
  colors,
  startX,
  startY,
  delay = 0,
}: {
  size: number;
  colors: string[];
  startX: number;
  startY: number;
  delay?: number;
}) => {
  const translateX = useSharedValue(0);
  const translateY = useSharedValue(0);
  const scale = useSharedValue(1);
  const rotate = useSharedValue(0);

  useEffect(() => {
    // X movement
    translateX.value = withRepeat(
      withSequence(
        withTiming(60, { duration: 5000 + delay, easing: Easing.inOut(Easing.ease) }),
        withTiming(-40, { duration: 4000 + delay, easing: Easing.inOut(Easing.ease) }),
        withTiming(0, { duration: 4500 + delay, easing: Easing.inOut(Easing.ease) })
      ),
      -1,
      true
    );

    // Y movement
    translateY.value = withRepeat(
      withSequence(
        withTiming(-70, { duration: 6000 + delay, easing: Easing.inOut(Easing.ease) }),
        withTiming(50, { duration: 5000 + delay, easing: Easing.inOut(Easing.ease) }),
        withTiming(0, { duration: 5500 + delay, easing: Easing.inOut(Easing.ease) })
      ),
      -1,
      true
    );

    // Scale pulsing
    scale.value = withRepeat(
      withSequence(
        withTiming(1.3, { duration: 4000 + delay, easing: Easing.inOut(Easing.ease) }),
        withTiming(0.7, { duration: 4500 + delay, easing: Easing.inOut(Easing.ease) }),
        withTiming(1, { duration: 4000 + delay, easing: Easing.inOut(Easing.ease) })
      ),
      -1,
      true
    );

    // Rotation
    rotate.value = withRepeat(
      withTiming(360, { duration: 20000 + delay, easing: Easing.linear }),
      -1,
      false
    );
  }, []);

  const animatedStyle = useAnimatedStyle(() => ({
    transform: [
      { translateX: translateX.value },
      { translateY: translateY.value },
      { scale: scale.value },
      { rotate: `${rotate.value}deg` },
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
        },
        animatedStyle,
      ]}
    >
      <LinearGradient
        colors={colors}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={{
          width: size,
          height: size,
          borderRadius: size / 2,
          opacity: 0.5,
          // Glow effect via shadow
          shadowColor: colors[0],
          shadowOffset: { width: 0, height: 0 },
          shadowOpacity: 0.8,
          shadowRadius: size * 0.3,
        }}
      />
    </Animated.View>
  );
};

// Regular Lava Blob for Light Mode
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
    translateX.value = withRepeat(
      withSequence(
        withTiming(50, { duration: 4000 + delay, easing: Easing.inOut(Easing.ease) }),
        withTiming(-30, { duration: 3000 + delay, easing: Easing.inOut(Easing.ease) }),
        withTiming(0, { duration: 3500 + delay, easing: Easing.inOut(Easing.ease) })
      ),
      -1,
      true
    );

    translateY.value = withRepeat(
      withSequence(
        withTiming(-60, { duration: 5000 + delay, easing: Easing.inOut(Easing.ease) }),
        withTiming(40, { duration: 4000 + delay, easing: Easing.inOut(Easing.ease) }),
        withTiming(0, { duration: 4500 + delay, easing: Easing.inOut(Easing.ease) })
      ),
      -1,
      true
    );

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
          opacity: 0.5,
        },
        animatedStyle,
      ]}
    />
  );
};

interface LavaBackgroundProps {
  variant?: 'default' | 'shopping' | 'ocean' | 'sunset' | 'crypto' | 'neon';
  intensity?: 'low' | 'medium' | 'high';
  blurIntensity?: 'light' | 'medium' | 'heavy';
  children?: React.ReactNode;
}

export const LavaBackground: React.FC<LavaBackgroundProps> = ({
  variant = 'default',
  intensity = 'medium',
  blurIntensity = 'medium',
  children,
}) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  // Gradient colors
  const getGradientColors = (): string[] => {
    if (isDark) {
      switch (variant) {
        case 'shopping':
          return ['#0F0F1A', '#1A1A2E', '#0A0A15'];
        case 'ocean':
          return ['#0A1628', '#0D1B2A', '#050A10'];
        case 'sunset':
          return ['#1A0F2E', '#2D1B4E', '#0F0A1A'];
        case 'crypto':
          return ['#0F1A2E', '#1A0F2E', '#0A0F1A'];
        case 'neon':
          return ['#0A0A1A', '#0F0F2A', '#050510'];
        default:
          return ['#0F0F1A', '#1A1A2E', '#0A0A15'];
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

  // RGB glow colors for dark mode
  const getRGBGlowColors = (): string[][] => {
    switch (variant) {
      case 'shopping':
        return [
          ['#FF6B6B', '#FF3366', '#FF6B6B'],
          ['#FF8E53', '#FFAA33', '#FF8E53'],
          ['#FFE66D', '#FFCC00', '#FFE66D'],
        ];
      case 'ocean':
        return [
          ['#00D9FF', '#0099CC', '#00D9FF'],
          ['#4ECDC4', '#33AAAA', '#4ECDC4'],
          ['#00FF88', '#00CC66', '#00FF88'],
        ];
      case 'sunset':
        return [
          ['#FF6B35', '#FF4400', '#FF6B35'],
          ['#FF00FF', '#CC00CC', '#FF00FF'],
          ['#7B2CBF', '#5500AA', '#7B2CBF'],
        ];
      case 'crypto':
        return [
          ['#00FF88', '#00CC66', '#00FF88'],
          ['#FFD700', '#CCAA00', '#FFD700'],
          ['#00D9FF', '#0099CC', '#00D9FF'],
        ];
      case 'neon':
        return [
          ['#FF00FF', '#CC00CC', '#FF00FF'],
          ['#00FFFF', '#00CCCC', '#00FFFF'],
          ['#FF00AA', '#CC0088', '#FF00AA'],
          ['#00FF00', '#00CC00', '#00FF00'],
        ];
      default:
        return [
          ['#FF6B6B', '#FF3366', '#FF6B6B'],
          ['#4ECDC4', '#33AAAA', '#4ECDC4'],
          ['#FFE66D', '#FFCC00', '#FFE66D'],
          ['#7B2CBF', '#5500AA', '#7B2CBF'],
        ];
    }
  };

  // Regular blob colors for light mode
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

  // Blob count based on intensity
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

  // Blur overlay opacity
  const getBlurOpacity = (): number => {
    if (isDark) {
      switch (blurIntensity) {
        case 'light':
          return 0.5;
        case 'heavy':
          return 0.85;
        default:
          return 0.7;
      }
    } else {
      switch (blurIntensity) {
        case 'light':
          return 0.6;
        case 'heavy':
          return 0.9;
        default:
          return 0.75;
      }
    }
  };

  const blobCount = getBlobCount();
  const rgbColors = getRGBGlowColors();
  const blobColors = getBlobColors();
  const blurOpacity = getBlurOpacity();

  // Generate blobs with stable positions
  const blobs = useMemo(() => {
    return Array.from({ length: blobCount }, (_, i) => ({
      id: i,
      size: 120 + (i * 30) % 100,
      colors: rgbColors[i % rgbColors.length],
      color: blobColors[i % blobColors.length],
      startX: (width * 0.1) + (i * width * 0.2) % (width * 0.8),
      startY: (height * 0.1) + (i * height * 0.15) % (height * 0.6),
      delay: i * 600,
    }));
  }, [blobCount, variant]);

  return (
    <View style={styles.container}>
      {/* Base Gradient */}
      <LinearGradient
        colors={getGradientColors()}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={styles.gradient}
      />

      {/* Blobs */}
      <View style={styles.blobContainer}>
        {blobs.map((blob) =>
          isDark ? (
            <RGBGlowBlob
              key={blob.id}
              size={blob.size}
              colors={blob.colors}
              startX={blob.startX}
              startY={blob.startY}
              delay={blob.delay}
            />
          ) : (
            <LavaBlob
              key={blob.id}
              size={blob.size}
              color={blob.color}
              startX={blob.startX}
              startY={blob.startY}
              delay={blob.delay}
            />
          )
        )}
      </View>

      {/* Enhanced Blur Overlay */}
      <View
        style={[
          styles.blurOverlay,
          {
            backgroundColor: isDark
              ? `rgba(10, 10, 20, ${blurOpacity})`
              : `rgba(255, 255, 255, ${blurOpacity})`,
          },
        ]}
      />

      {/* Content */}
      {children && <View style={styles.content}>{children}</View>}
    </View>
  );
};

// Glass Card Component
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
          backgroundColor: isDark
            ? 'rgba(255, 255, 255, 0.08)'
            : 'rgba(255, 255, 255, 0.85)',
          borderColor: isDark
            ? 'rgba(255, 255, 255, 0.15)'
            : 'rgba(0, 0, 0, 0.08)',
          // Glow effect in dark mode
          ...(isDark && {
            shadowColor: '#3B82F6',
            shadowOffset: { width: 0, height: 0 },
            shadowOpacity: 0.2,
            shadowRadius: 15,
          }),
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
  },
});

export default LavaBackground;
