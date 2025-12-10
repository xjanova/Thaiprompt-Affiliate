/**
 * AnimatedBackground Component
 * สร้างพื้นหลังแบบหิ่งห้อย/อะตอมที่เคลื่อนไหวสวยงาม
 * สำหรับแอพหลักล้าน
 */

import React, { useEffect, useRef, useMemo } from 'react';
import { View, StyleSheet, Dimensions, Animated, Easing } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import Svg, { Circle, Defs, RadialGradient, Stop } from 'react-native-svg';

const { width: SCREEN_WIDTH, height: SCREEN_HEIGHT } = Dimensions.get('window');

// ===== Types =====

interface Particle {
  id: number;
  x: number;
  y: number;
  size: number;
  opacity: Animated.Value;
  translateX: Animated.Value;
  translateY: Animated.Value;
  scale: Animated.Value;
  color: string;
  speed: number;
  delay: number;
}

interface AnimatedBackgroundProps {
  variant?: 'firefly' | 'stars' | 'particles' | 'aurora';
  particleCount?: number;
  isDark?: boolean;
  colors?: string[];
  intensity?: 'low' | 'medium' | 'high';
  children?: React.ReactNode;
}

// ===== Animated SVG Particle =====

const AnimatedCircle = Animated.createAnimatedComponent(Circle);

const FireflyParticle = ({ particle, isDark }: { particle: Particle; isDark: boolean }) => {
  const animatedStyle = {
    transform: [
      { translateX: particle.translateX },
      { translateY: particle.translateY },
      { scale: particle.scale },
    ],
    opacity: particle.opacity,
  };

  return (
    <Animated.View
      style={[
        styles.particle,
        {
          left: particle.x,
          top: particle.y,
          width: particle.size,
          height: particle.size,
        },
        animatedStyle,
      ]}
    >
      <View
        style={[
          styles.particleInner,
          {
            width: particle.size,
            height: particle.size,
            backgroundColor: particle.color,
            shadowColor: particle.color,
            shadowOpacity: 0.8,
            shadowRadius: particle.size * 2,
            shadowOffset: { width: 0, height: 0 },
          },
        ]}
      />
      {/* Glow effect */}
      <View
        style={[
          styles.particleGlow,
          {
            width: particle.size * 3,
            height: particle.size * 3,
            backgroundColor: particle.color,
            opacity: 0.3,
            marginLeft: -particle.size,
            marginTop: -particle.size,
          },
        ]}
      />
    </Animated.View>
  );
};

// ===== Main Component =====

export const AnimatedBackground: React.FC<AnimatedBackgroundProps> = ({
  variant = 'firefly',
  particleCount = 20,
  isDark = true,
  colors,
  intensity = 'medium',
  children,
}) => {
  // กำหนดจำนวน particle ตาม intensity
  const actualParticleCount = useMemo(() => {
    switch (intensity) {
      case 'low': return Math.floor(particleCount * 0.5);
      case 'high': return Math.floor(particleCount * 1.5);
      default: return particleCount;
    }
  }, [particleCount, intensity]);

  // กำหนดสีตาม variant
  const particleColors = useMemo(() => {
    if (colors) return colors;
    switch (variant) {
      case 'firefly':
        return isDark
          ? ['#FFD700', '#FFA500', '#FFFF00', '#FFE4B5', '#F0E68C']
          : ['#3B82F6', '#60A5FA', '#93C5FD', '#BFDBFE', '#DBEAFE'];
      case 'stars':
        return ['#FFFFFF', '#F0F8FF', '#E6E6FA', '#B0C4DE', '#87CEEB'];
      case 'particles':
        return isDark
          ? ['#8B5CF6', '#A78BFA', '#C4B5FD', '#7C3AED', '#6D28D9']
          : ['#EC4899', '#F472B6', '#F9A8D4', '#DB2777', '#BE185D'];
      case 'aurora':
        return ['#10B981', '#34D399', '#6EE7B7', '#A7F3D0', '#059669'];
      default:
        return ['#FFD700', '#FFA500', '#FFFF00'];
    }
  }, [variant, isDark, colors]);

  // สร้าง gradient colors สำหรับพื้นหลัง
  const gradientColors = useMemo(() => {
    if (isDark) {
      switch (variant) {
        case 'firefly':
          return ['#0F0F23', '#1a1a2e', '#16213e'];
        case 'stars':
          return ['#0a0a1a', '#0F0F23', '#1a1a2e'];
        case 'particles':
          return ['#0F0F23', '#1a0a2e', '#2d1b4e'];
        case 'aurora':
          return ['#0F0F23', '#0a1a1a', '#0d2818'];
        default:
          return ['#0F0F23', '#1a1a2e', '#16213e'];
      }
    }
    return ['#F9FAFB', '#F3F4F6', '#E5E7EB'];
  }, [isDark, variant]);

  // สร้าง particles
  const particles = useMemo<Particle[]>(() => {
    return Array.from({ length: actualParticleCount }, (_, i) => ({
      id: i,
      x: Math.random() * SCREEN_WIDTH,
      y: Math.random() * SCREEN_HEIGHT,
      size: Math.random() * 6 + 2,
      opacity: new Animated.Value(0),
      translateX: new Animated.Value(0),
      translateY: new Animated.Value(0),
      scale: new Animated.Value(1),
      color: particleColors[Math.floor(Math.random() * particleColors.length)],
      speed: Math.random() * 3000 + 2000,
      delay: Math.random() * 2000,
    }));
  }, [actualParticleCount, particleColors]);

  // Animation effect
  useEffect(() => {
    const animations = particles.map((particle) => {
      // Opacity animation (breathing effect)
      const opacityAnimation = Animated.loop(
        Animated.sequence([
          Animated.delay(particle.delay),
          Animated.timing(particle.opacity, {
            toValue: Math.random() * 0.5 + 0.5,
            duration: particle.speed,
            easing: Easing.inOut(Easing.sine),
            useNativeDriver: true,
          }),
          Animated.timing(particle.opacity, {
            toValue: 0.1,
            duration: particle.speed,
            easing: Easing.inOut(Easing.sine),
            useNativeDriver: true,
          }),
        ])
      );

      // Movement animation
      const moveRange = 50 + Math.random() * 50;
      const movementAnimation = Animated.loop(
        Animated.sequence([
          Animated.delay(particle.delay),
          Animated.parallel([
            Animated.timing(particle.translateX, {
              toValue: (Math.random() - 0.5) * moveRange,
              duration: particle.speed * 1.5,
              easing: Easing.inOut(Easing.sine),
              useNativeDriver: true,
            }),
            Animated.timing(particle.translateY, {
              toValue: (Math.random() - 0.5) * moveRange,
              duration: particle.speed * 1.5,
              easing: Easing.inOut(Easing.sine),
              useNativeDriver: true,
            }),
          ]),
          Animated.parallel([
            Animated.timing(particle.translateX, {
              toValue: 0,
              duration: particle.speed * 1.5,
              easing: Easing.inOut(Easing.sine),
              useNativeDriver: true,
            }),
            Animated.timing(particle.translateY, {
              toValue: 0,
              duration: particle.speed * 1.5,
              easing: Easing.inOut(Easing.sine),
              useNativeDriver: true,
            }),
          ]),
        ])
      );

      // Scale animation (pulse effect)
      const scaleAnimation = Animated.loop(
        Animated.sequence([
          Animated.delay(particle.delay + 500),
          Animated.timing(particle.scale, {
            toValue: 1.3,
            duration: particle.speed * 0.8,
            easing: Easing.inOut(Easing.ease),
            useNativeDriver: true,
          }),
          Animated.timing(particle.scale, {
            toValue: 0.8,
            duration: particle.speed * 0.8,
            easing: Easing.inOut(Easing.ease),
            useNativeDriver: true,
          }),
        ])
      );

      return Animated.parallel([opacityAnimation, movementAnimation, scaleAnimation]);
    });

    // Start all animations
    animations.forEach((anim) => anim.start());

    // Cleanup
    return () => {
      animations.forEach((anim) => anim.stop());
    };
  }, [particles]);

  return (
    <View style={styles.container}>
      {/* Gradient Background */}
      <LinearGradient
        colors={gradientColors}
        style={StyleSheet.absoluteFill}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
      />

      {/* Animated Particles Layer - อยู่ด้านล่าง */}
      <View style={styles.particlesContainer} pointerEvents="none">
        {particles.map((particle) => (
          <FireflyParticle key={particle.id} particle={particle} isDark={isDark} />
        ))}
      </View>

      {/* Content Layer - อยู่บนสุด พร้อม zIndex */}
      <View style={styles.contentLayer} pointerEvents="box-none">
        {children}
      </View>
    </View>
  );
};

// ===== Floating Orbs Component (Alternative Style) =====

interface OrbProps {
  size: number;
  color: string;
  x: number;
  y: number;
  duration: number;
  delay: number;
}

const FloatingOrb: React.FC<OrbProps> = ({ size, color, x, y, duration, delay }) => {
  const translateY = useRef(new Animated.Value(0)).current;
  const translateX = useRef(new Animated.Value(0)).current;
  const opacity = useRef(new Animated.Value(0.3)).current;

  useEffect(() => {
    const yAnimation = Animated.loop(
      Animated.sequence([
        Animated.delay(delay),
        Animated.timing(translateY, {
          toValue: -30,
          duration: duration,
          easing: Easing.inOut(Easing.sine),
          useNativeDriver: true,
        }),
        Animated.timing(translateY, {
          toValue: 30,
          duration: duration,
          easing: Easing.inOut(Easing.sine),
          useNativeDriver: true,
        }),
        Animated.timing(translateY, {
          toValue: 0,
          duration: duration,
          easing: Easing.inOut(Easing.sine),
          useNativeDriver: true,
        }),
      ])
    );

    const xAnimation = Animated.loop(
      Animated.sequence([
        Animated.delay(delay + 500),
        Animated.timing(translateX, {
          toValue: 20,
          duration: duration * 1.2,
          easing: Easing.inOut(Easing.sine),
          useNativeDriver: true,
        }),
        Animated.timing(translateX, {
          toValue: -20,
          duration: duration * 1.2,
          easing: Easing.inOut(Easing.sine),
          useNativeDriver: true,
        }),
        Animated.timing(translateX, {
          toValue: 0,
          duration: duration * 1.2,
          easing: Easing.inOut(Easing.sine),
          useNativeDriver: true,
        }),
      ])
    );

    const opacityAnimation = Animated.loop(
      Animated.sequence([
        Animated.delay(delay),
        Animated.timing(opacity, {
          toValue: 0.6,
          duration: duration * 0.5,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
        Animated.timing(opacity, {
          toValue: 0.2,
          duration: duration * 0.5,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
      ])
    );

    yAnimation.start();
    xAnimation.start();
    opacityAnimation.start();

    return () => {
      yAnimation.stop();
      xAnimation.stop();
      opacityAnimation.stop();
    };
  }, []);

  return (
    <Animated.View
      style={[
        styles.orb,
        {
          width: size,
          height: size,
          left: x,
          top: y,
          backgroundColor: color,
          transform: [{ translateX }, { translateY }],
          opacity,
        },
      ]}
    />
  );
};

export const FloatingOrbsBackground: React.FC<{
  isDark?: boolean;
  children?: React.ReactNode;
}> = ({ isDark = true, children }) => {
  const orbs = useMemo(() => [
    { size: 200, color: 'rgba(123, 44, 191, 0.15)', x: -50, y: 100, duration: 4000, delay: 0 },
    { size: 150, color: 'rgba(59, 130, 246, 0.15)', x: SCREEN_WIDTH - 100, y: 200, duration: 5000, delay: 500 },
    { size: 180, color: 'rgba(236, 72, 153, 0.12)', x: 50, y: SCREEN_HEIGHT - 300, duration: 4500, delay: 1000 },
    { size: 120, color: 'rgba(16, 185, 129, 0.15)', x: SCREEN_WIDTH - 150, y: SCREEN_HEIGHT - 200, duration: 3500, delay: 1500 },
    { size: 100, color: 'rgba(245, 158, 11, 0.12)', x: SCREEN_WIDTH / 2 - 50, y: 50, duration: 5500, delay: 2000 },
  ], []);

  return (
    <View style={styles.container}>
      <LinearGradient
        colors={isDark ? ['#0F0F23', '#1a1a2e', '#0F0F23'] : ['#F9FAFB', '#F3F4F6', '#E5E7EB']}
        style={StyleSheet.absoluteFill}
      />
      <View style={styles.orbsContainer} pointerEvents="none">
        {orbs.map((orb, index) => (
          <FloatingOrb key={index} {...orb} />
        ))}
      </View>
      {children}
    </View>
  );
};

// ===== Styles =====

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  particlesContainer: {
    ...StyleSheet.absoluteFillObject,
    overflow: 'hidden',
    zIndex: 1,
  },
  contentLayer: {
    ...StyleSheet.absoluteFillObject,
    zIndex: 10,
  },
  particle: {
    position: 'absolute',
  },
  particleInner: {
    borderRadius: 100,
  },
  particleGlow: {
    position: 'absolute',
    borderRadius: 100,
  },
  orbsContainer: {
    ...StyleSheet.absoluteFillObject,
    overflow: 'hidden',
  },
  orb: {
    position: 'absolute',
    borderRadius: 1000,
  },
});

export default AnimatedBackground;
