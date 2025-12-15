/**
 * AnimatedBackground Component
 * สร้างพื้นหลังแบบหิ่งห้อย/อะตอมที่เคลื่อนไหวสวยงาม
 * สำหรับแอพหลักล้าน
 *
 * v1.8.0 - ปรับปรุงเอฟเฟคเบลอฟุ้งให้ชัดเจนขึ้นมาก
 * - เพิ่มขนาด glow layers ทั้งหมด
 * - เพิ่ม opacity ให้สว่างขึ้น
 * - เพิ่ม outer glow layer เพิ่มเติม
 * - ปรับ shadow blur radius ให้กว้างขึ้น
 *
 * v1.7.0 - ปรับปรุงเอฟเฟคหิ่งห้อยให้เบลอฟุ้งสวยขึ้น
 * - ใช้ BlurView จาก expo-blur สำหรับ glow effect
 * - เพิ่ม radial gradient simulation ด้วย multiple layers
 * - ปรับ opacity และ size ให้ดูเป็นธรรมชาติขึ้น
 *
 * v1.6.5 - เพิ่ม delay ก่อนเริ่ม animations
 * - เพิ่ม isReady state รอ 300ms ก่อนแสดง particles
 * - ป้องกัน crash เมื่อ app ยังไม่พร้อม
 *
 * v1.6.3 - แก้ไข crash เมื่อ navigate ระหว่างหน้า
 * - ใช้ useRef แทน useMemo สำหรับ Animated.Value
 * - เพิ่ม isMounted check ป้องกัน memory leak
 * - เพิ่ม error handling
 */

import React, { useEffect, useRef, useMemo, useState } from 'react';
import { View, StyleSheet, Dimensions, Animated, Easing, Platform } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { BlurView } from 'expo-blur';

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

// ===== Firefly Particle Component =====

const FireflyParticle = React.memo(({ particle }: { particle: Particle }) => {
  const animatedStyle = {
    transform: [
      { translateX: particle.translateX },
      { translateY: particle.translateY },
      { scale: particle.scale },
    ],
    opacity: particle.opacity,
  };

  // ขนาด glow แต่ละชั้น - ขยายใหญ่ขึ้นเพื่อให้เห็นฟุ้งชัดเจน
  const glowSize = particle.size * 20; // เพิ่มจาก 12 เป็น 20
  const glowOffset = -glowSize / 2 + particle.size / 2;

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
      {/* ชั้นที่ 0: Outer blur glow (ฟุ้งนอกสุด - ใหม่) */}
      <View
        style={[
          styles.particleGlow,
          {
            width: particle.size * 16,
            height: particle.size * 16,
            backgroundColor: particle.color,
            opacity: 0.08,
            marginLeft: -particle.size * 7.5,
            marginTop: -particle.size * 7.5,
          },
        ]}
      />

      {/* ชั้นที่ 1: Blur glow นอกสุด (เบลอมากสุด) */}
      <View
        style={[
          styles.glowContainer,
          {
            width: glowSize,
            height: glowSize,
            marginLeft: glowOffset,
            marginTop: glowOffset,
          },
        ]}
      >
        <BlurView
          intensity={Platform.OS === 'ios' ? 50 : 25}
          tint="dark"
          style={[
            styles.blurGlow,
            {
              backgroundColor: particle.color,
              opacity: 0.25,
            },
          ]}
        />
      </View>

      {/* ชั้นที่ 2: Soft glow กลาง - ขยายใหญ่ขึ้น */}
      <View
        style={[
          styles.particleGlow,
          {
            width: particle.size * 10,
            height: particle.size * 10,
            backgroundColor: particle.color,
            opacity: 0.15,
            marginLeft: -particle.size * 4.5,
            marginTop: -particle.size * 4.5,
          },
        ]}
      />

      {/* ชั้นที่ 3: Medium glow - ขยายใหญ่ขึ้น */}
      <View
        style={[
          styles.particleGlow,
          {
            width: particle.size * 6,
            height: particle.size * 6,
            backgroundColor: particle.color,
            opacity: 0.25,
            marginLeft: -particle.size * 2.5,
            marginTop: -particle.size * 2.5,
          },
        ]}
      />

      {/* ชั้นที่ 4: Inner glow (ฟุ้งใกล้แกน) */}
      <View
        style={[
          styles.particleGlow,
          {
            width: particle.size * 4,
            height: particle.size * 4,
            backgroundColor: particle.color,
            opacity: 0.4,
            marginLeft: -particle.size * 1.5,
            marginTop: -particle.size * 1.5,
          },
        ]}
      />

      {/* ชั้นที่ 5: Core glow (แกนกลางเรืองแสง) */}
      <View
        style={[
          styles.particleInner,
          {
            width: particle.size * 2,
            height: particle.size * 2,
            backgroundColor: particle.color,
            opacity: 0.8,
            marginLeft: -particle.size * 0.5,
            marginTop: -particle.size * 0.5,
            shadowColor: particle.color,
            shadowOpacity: 1,
            shadowRadius: particle.size * 10,
            shadowOffset: { width: 0, height: 0 },
            // Android shadow
            elevation: 20,
          },
        ]}
      />

      {/* ชั้นที่ 6: Core center (แกนกลางสว่างสุด) */}
      <View
        style={[
          styles.particleCore,
          {
            width: particle.size,
            height: particle.size,
            backgroundColor: '#FFFFFF',
            shadowColor: particle.color,
            shadowOpacity: 1,
            shadowRadius: particle.size * 5,
            shadowOffset: { width: 0, height: 0 },
          },
        ]}
      />
    </Animated.View>
  );
});

// ===== Main Component =====

export const AnimatedBackground: React.FC<AnimatedBackgroundProps> = ({
  variant = 'firefly',
  particleCount = 20,
  isDark = true,
  colors,
  intensity = 'medium',
  children,
}) => {
  // Track mount state เพื่อป้องกัน memory leak
  const isMounted = useRef(true);
  const animationsRef = useRef<Animated.CompositeAnimation[]>([]);

  // ⭐ รอให้ component พร้อมก่อนเริ่ม animations (ป้องกัน crash)
  const [isReady, setIsReady] = useState(false);

  // กำหนดจำนวน particle ตาม intensity
  const actualParticleCount = useMemo(() => {
    switch (intensity) {
      case 'low':
        return Math.floor(particleCount * 0.5);
      case 'high':
        return Math.floor(particleCount * 1.5);
      default:
        return particleCount;
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
          return ['#0F0F23', '#1a1a2e', '#16213e'] as const;
        case 'stars':
          return ['#0a0a1a', '#0F0F23', '#1a1a2e'] as const;
        case 'particles':
          return ['#0F0F23', '#1a0a2e', '#2d1b4e'] as const;
        case 'aurora':
          return ['#0F0F23', '#0a1a1a', '#0d2818'] as const;
        default:
          return ['#0F0F23', '#1a1a2e', '#16213e'] as const;
      }
    }
    return ['#F9FAFB', '#F3F4F6', '#E5E7EB'] as const;
  }, [isDark, variant]);

  // ⭐ ใช้ useRef เพื่อเก็บ particles - สร้างครั้งเดียวไม่เปลี่ยน
  const particlesRef = useRef<Particle[] | null>(null);

  // สร้าง particles ครั้งเดียวตอน mount
  // เพิ่มขนาด particle เพื่อให้เห็นเอฟเฟกต์ฟุ้งชัดเจนขึ้น
  if (particlesRef.current === null) {
    particlesRef.current = Array.from({ length: actualParticleCount }, (_, i) => ({
      id: i,
      x: Math.random() * SCREEN_WIDTH,
      y: Math.random() * SCREEN_HEIGHT,
      size: Math.random() * 8 + 4, // เพิ่มจาก 2-8 เป็น 4-12
      opacity: new Animated.Value(0),
      translateX: new Animated.Value(0),
      translateY: new Animated.Value(0),
      scale: new Animated.Value(1),
      color: particleColors[Math.floor(Math.random() * particleColors.length)],
      speed: Math.random() * 3000 + 2000,
      delay: Math.random() * 2000,
    }));
  }

  const particles = particlesRef.current;

  // ⭐ Delay ก่อนเริ่ม animations เพื่อให้ app พร้อม
  useEffect(() => {
    isMounted.current = true;

    // รอ 300ms ก่อนเริ่มแสดง particles และ animations
    const readyTimeout = setTimeout(() => {
      if (isMounted.current) {
        setIsReady(true);
      }
    }, 300);

    return () => {
      isMounted.current = false;
      clearTimeout(readyTimeout);
      // หยุด animations ทั้งหมด
      animationsRef.current.forEach((anim) => {
        try {
          anim.stop();
        } catch (e) {
          // Ignore cleanup errors
        }
      });
      animationsRef.current = [];
    };
  }, []);

  // Animation effect - รอให้ isReady ก่อน
  useEffect(() => {
    if (!isReady || !particles || particles.length === 0) return;

    try {
      const animations = particles.map((particle) => {
        // Opacity animation (breathing effect) - เพิ่ม opacity ให้สว่างขึ้น
        const opacityAnimation = Animated.loop(
          Animated.sequence([
            Animated.delay(particle.delay),
            Animated.timing(particle.opacity, {
              toValue: Math.random() * 0.3 + 0.7, // เพิ่มจาก 0.5-1.0 เป็น 0.7-1.0
              duration: particle.speed,
              easing: Easing.inOut(Easing.sine),
              useNativeDriver: true,
            }),
            Animated.timing(particle.opacity, {
              toValue: 0.3, // เพิ่มจาก 0.1 เป็น 0.3
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

      // เก็บ ref ไว้สำหรับ cleanup
      animationsRef.current = animations;

      // Start all animations
      animations.forEach((anim) => {
        if (isMounted.current) {
          anim.start();
        }
      });
    } catch (error) {
      console.warn('AnimatedBackground animation error:', error);
    }

    // Cleanup
    return () => {
      animationsRef.current.forEach((anim) => {
        try {
          anim.stop();
        } catch (e) {
          // Ignore cleanup errors
        }
      });
    };
  }, [isReady, particles]);

  return (
    <View style={styles.container}>
      {/* Gradient Background */}
      <LinearGradient
        colors={gradientColors as unknown as string[]}
        style={StyleSheet.absoluteFill}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
      />

      {/* Animated Particles Layer - อยู่ด้านล่าง (แสดงเมื่อ ready) */}
      {isReady && (
        <View style={styles.particlesContainer} pointerEvents="none">
          {particles.map((particle) => (
            <FireflyParticle key={particle.id} particle={particle} />
          ))}
        </View>
      )}

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
  const isMounted = useRef(true);

  useEffect(() => {
    isMounted.current = true;

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

    if (isMounted.current) {
      yAnimation.start();
      xAnimation.start();
      opacityAnimation.start();
    }

    return () => {
      isMounted.current = false;
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
  const orbs = useMemo(
    () => [
      { size: 200, color: 'rgba(123, 44, 191, 0.15)', x: -50, y: 100, duration: 4000, delay: 0 },
      {
        size: 150,
        color: 'rgba(59, 130, 246, 0.15)',
        x: SCREEN_WIDTH - 100,
        y: 200,
        duration: 5000,
        delay: 500,
      },
      {
        size: 180,
        color: 'rgba(236, 72, 153, 0.12)',
        x: 50,
        y: SCREEN_HEIGHT - 300,
        duration: 4500,
        delay: 1000,
      },
      {
        size: 120,
        color: 'rgba(16, 185, 129, 0.15)',
        x: SCREEN_WIDTH - 150,
        y: SCREEN_HEIGHT - 200,
        duration: 3500,
        delay: 1500,
      },
      {
        size: 100,
        color: 'rgba(245, 158, 11, 0.12)',
        x: SCREEN_WIDTH / 2 - 50,
        y: 50,
        duration: 5500,
        delay: 2000,
      },
    ],
    []
  );

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
    flex: 1,
    position: 'relative',
    zIndex: 10,
  },
  particle: {
    position: 'absolute',
  },
  particleInner: {
    position: 'absolute',
    borderRadius: 100,
  },
  particleGlow: {
    position: 'absolute',
    borderRadius: 1000,
  },
  particleCore: {
    position: 'absolute',
    borderRadius: 100,
  },
  glowContainer: {
    position: 'absolute',
    borderRadius: 1000,
    overflow: 'hidden',
  },
  blurGlow: {
    flex: 1,
    borderRadius: 1000,
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
