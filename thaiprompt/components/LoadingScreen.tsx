/**
 * Loading Screen Component - หน้า loading พร้อม animation แบบ Premium
 * มีแสงเปล่งออกจากโลโก้แบบ Radiant Glow Effect
 * และมีอะตอมหิ่งห้อยเรืองแสงบินไปมา
 */

import React, { useEffect, useRef, useMemo } from 'react';
import { View, Text, Animated, Easing, StyleSheet, Dimensions } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { APP_INFO } from '@/config/appConfig';

const { width: SCREEN_WIDTH, height: SCREEN_HEIGHT } = Dimensions.get('window');

// จำนวน fireflies
const FIREFLY_COUNT = 15;

interface LoadingScreenProps {
  message?: string;
}

// Firefly Component - อะตอมหิ่งห้อยเรืองแสง
const Firefly = ({ index }: { index: number }) => {
  // Random starting positions and sizes
  const initialX = useMemo(() => Math.random() * SCREEN_WIDTH, []);
  const initialY = useMemo(() => Math.random() * SCREEN_HEIGHT, []);
  const size = useMemo(() => 4 + Math.random() * 6, []);
  const duration = useMemo(() => 3000 + Math.random() * 4000, []);
  const delay = useMemo(() => Math.random() * 2000, []);

  // Animation values
  const translateX = useRef(new Animated.Value(0)).current;
  const translateY = useRef(new Animated.Value(0)).current;
  const opacity = useRef(new Animated.Value(0)).current;
  const scale = useRef(new Animated.Value(0.5)).current;

  // สุ่มทิศทางและระยะทาง
  const targetX = useMemo(() => (Math.random() - 0.5) * 200, []);
  const targetY = useMemo(() => (Math.random() - 0.5) * 200, []);

  // สีสุ่มของหิ่งห้อย
  const colors = useMemo(() => {
    const colorOptions = [
      ['rgba(59, 130, 246, 0.9)', 'rgba(59, 130, 246, 0.3)'], // Blue
      ['rgba(139, 92, 246, 0.9)', 'rgba(139, 92, 246, 0.3)'], // Purple
      ['rgba(6, 182, 212, 0.9)', 'rgba(6, 182, 212, 0.3)'], // Cyan
      ['rgba(16, 185, 129, 0.9)', 'rgba(16, 185, 129, 0.3)'], // Green
      ['rgba(251, 191, 36, 0.9)', 'rgba(251, 191, 36, 0.3)'], // Yellow
    ];
    return colorOptions[Math.floor(Math.random() * colorOptions.length)];
  }, []);

  useEffect(() => {
    let isMounted = true;

    const animate = () => {
      if (!isMounted) return;

      // Reset position
      translateX.setValue(0);
      translateY.setValue(0);

      Animated.parallel([
        // Movement X
        Animated.sequence([
          Animated.delay(delay),
          Animated.timing(translateX, {
            toValue: targetX,
            duration: duration,
            easing: Easing.inOut(Easing.sin),
            useNativeDriver: true,
          }),
        ]),
        // Movement Y
        Animated.sequence([
          Animated.delay(delay),
          Animated.timing(translateY, {
            toValue: targetY,
            duration: duration,
            easing: Easing.inOut(Easing.sin),
            useNativeDriver: true,
          }),
        ]),
        // Glow effect (opacity)
        Animated.sequence([
          Animated.delay(delay),
          Animated.timing(opacity, {
            toValue: 0.8 + Math.random() * 0.2,
            duration: duration / 4,
            easing: Easing.ease,
            useNativeDriver: true,
          }),
          Animated.timing(opacity, {
            toValue: 0.3,
            duration: duration / 4,
            easing: Easing.ease,
            useNativeDriver: true,
          }),
          Animated.timing(opacity, {
            toValue: 0.9,
            duration: duration / 4,
            easing: Easing.ease,
            useNativeDriver: true,
          }),
          Animated.timing(opacity, {
            toValue: 0,
            duration: duration / 4,
            easing: Easing.ease,
            useNativeDriver: true,
          }),
        ]),
        // Scale pulse
        Animated.sequence([
          Animated.delay(delay),
          Animated.timing(scale, {
            toValue: 1.2,
            duration: duration / 2,
            easing: Easing.inOut(Easing.ease),
            useNativeDriver: true,
          }),
          Animated.timing(scale, {
            toValue: 0.5,
            duration: duration / 2,
            easing: Easing.inOut(Easing.ease),
            useNativeDriver: true,
          }),
        ]),
      ]).start(() => {
        if (isMounted) animate();
      });
    };

    animate();

    return () => {
      isMounted = false;
      translateX.stopAnimation();
      translateY.stopAnimation();
      opacity.stopAnimation();
      scale.stopAnimation();
    };
  }, [translateX, translateY, opacity, scale, duration, delay, targetX, targetY]);

  return (
    <Animated.View
      style={[
        fireflyStyles.firefly,
        {
          left: initialX,
          top: initialY,
          width: size,
          height: size,
          borderRadius: size / 2,
          opacity,
          transform: [
            { translateX },
            { translateY },
            { scale },
          ],
        },
      ]}
    >
      {/* Glow effect */}
      <View
        style={[
          fireflyStyles.glow,
          {
            width: size * 3,
            height: size * 3,
            borderRadius: size * 1.5,
            backgroundColor: colors[1],
            left: -size,
            top: -size,
          },
        ]}
      />
      {/* Core */}
      <View
        style={[
          fireflyStyles.core,
          {
            width: size,
            height: size,
            borderRadius: size / 2,
            backgroundColor: colors[0],
          },
        ]}
      />
    </Animated.View>
  );
};

const fireflyStyles = StyleSheet.create({
  firefly: {
    position: 'absolute',
    zIndex: 1,
  },
  glow: {
    position: 'absolute',
  },
  core: {
    position: 'absolute',
    shadowColor: '#FFF',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 1,
    shadowRadius: 6,
    elevation: 6,
  },
});

export const LoadingScreen: React.FC<LoadingScreenProps> = ({
  message = 'กำลังโหลด...',
}) => {
  // Animation values สำหรับ dots
  const dot1Opacity = useRef(new Animated.Value(0.3)).current;
  const dot2Opacity = useRef(new Animated.Value(0.3)).current;
  const dot3Opacity = useRef(new Animated.Value(0.3)).current;

  // Animation value สำหรับ logo pulse และ glow
  const logoScale = useRef(new Animated.Value(1)).current;
  const glowOpacity = useRef(new Animated.Value(0.3)).current;
  const glowScale = useRef(new Animated.Value(1)).current;

  // Animation สำหรับวงแสงหลายวง
  const ring1Scale = useRef(new Animated.Value(0.5)).current;
  const ring1Opacity = useRef(new Animated.Value(0.8)).current;
  const ring2Scale = useRef(new Animated.Value(0.5)).current;
  const ring2Opacity = useRef(new Animated.Value(0.8)).current;
  const ring3Scale = useRef(new Animated.Value(0.5)).current;
  const ring3Opacity = useRef(new Animated.Value(0.8)).current;

  // Animation value สำหรับ spinner rotation
  const spinValue = useRef(new Animated.Value(0)).current;

  // Animation สำหรับ particle glow รอบโลโก้
  const particleRotation = useRef(new Animated.Value(0)).current;

  // Animation สำหรับ orbit particles
  const orbitRotation = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    let isMounted = true;

    // Dot animation - sequential fade
    const dotAnimation = () => {
      if (!isMounted) return;

      Animated.sequence([
        Animated.timing(dot1Opacity, {
          toValue: 1,
          duration: 300,
          useNativeDriver: true,
        }),
        Animated.timing(dot2Opacity, {
          toValue: 1,
          duration: 300,
          useNativeDriver: true,
        }),
        Animated.timing(dot3Opacity, {
          toValue: 1,
          duration: 300,
          useNativeDriver: true,
        }),
        Animated.parallel([
          Animated.timing(dot1Opacity, {
            toValue: 0.3,
            duration: 300,
            useNativeDriver: true,
          }),
          Animated.timing(dot2Opacity, {
            toValue: 0.3,
            duration: 300,
            useNativeDriver: true,
          }),
          Animated.timing(dot3Opacity, {
            toValue: 0.3,
            duration: 300,
            useNativeDriver: true,
          }),
        ]),
      ]).start(() => {
        if (isMounted) dotAnimation();
      });
    };

    // Logo pulse animation พร้อม glow
    const pulseAnimation = () => {
      if (!isMounted) return;

      Animated.parallel([
        // Logo scale
        Animated.sequence([
          Animated.timing(logoScale, {
            toValue: 1.15,
            duration: 1200,
            easing: Easing.inOut(Easing.ease),
            useNativeDriver: true,
          }),
          Animated.timing(logoScale, {
            toValue: 1,
            duration: 1200,
            easing: Easing.inOut(Easing.ease),
            useNativeDriver: true,
          }),
        ]),
        // Glow intensity
        Animated.sequence([
          Animated.timing(glowOpacity, {
            toValue: 0.8,
            duration: 1200,
            easing: Easing.inOut(Easing.ease),
            useNativeDriver: true,
          }),
          Animated.timing(glowOpacity, {
            toValue: 0.3,
            duration: 1200,
            easing: Easing.inOut(Easing.ease),
            useNativeDriver: true,
          }),
        ]),
        // Glow scale
        Animated.sequence([
          Animated.timing(glowScale, {
            toValue: 1.5,
            duration: 1200,
            easing: Easing.inOut(Easing.ease),
            useNativeDriver: true,
          }),
          Animated.timing(glowScale, {
            toValue: 1,
            duration: 1200,
            easing: Easing.inOut(Easing.ease),
            useNativeDriver: true,
          }),
        ]),
      ]).start(() => {
        if (isMounted) pulseAnimation();
      });
    };

    // Radiant rings animation - วงแสงขยายออก
    const ringAnimation = (
      scaleValue: Animated.Value,
      opacityValue: Animated.Value,
      delay: number
    ) => {
      const animate = () => {
        if (!isMounted) return;

        scaleValue.setValue(0.5);
        opacityValue.setValue(0.8);

        Animated.parallel([
          Animated.timing(scaleValue, {
            toValue: 2.5,
            duration: 2000,
            delay,
            easing: Easing.out(Easing.ease),
            useNativeDriver: true,
          }),
          Animated.timing(opacityValue, {
            toValue: 0,
            duration: 2000,
            delay,
            easing: Easing.out(Easing.ease),
            useNativeDriver: true,
          }),
        ]).start(() => {
          if (isMounted) animate();
        });
      };
      animate();
    };

    // Spinner rotation
    const spinAnimation = () => {
      if (!isMounted) return;

      spinValue.setValue(0);
      Animated.timing(spinValue, {
        toValue: 1,
        duration: 1500,
        easing: Easing.linear,
        useNativeDriver: true,
      }).start(() => {
        if (isMounted) spinAnimation();
      });
    };

    // Particle rotation (หมุนรอบโลโก้)
    const particleAnim = () => {
      if (!isMounted) return;

      particleRotation.setValue(0);
      Animated.timing(particleRotation, {
        toValue: 1,
        duration: 4000,
        easing: Easing.linear,
        useNativeDriver: true,
      }).start(() => {
        if (isMounted) particleAnim();
      });
    };

    // Orbit rotation (วงโคจรอะตอม)
    const orbitAnim = () => {
      if (!isMounted) return;

      orbitRotation.setValue(0);
      Animated.timing(orbitRotation, {
        toValue: 1,
        duration: 6000,
        easing: Easing.linear,
        useNativeDriver: true,
      }).start(() => {
        if (isMounted) orbitAnim();
      });
    };

    dotAnimation();
    pulseAnimation();
    ringAnimation(ring1Scale, ring1Opacity, 0);
    ringAnimation(ring2Scale, ring2Opacity, 600);
    ringAnimation(ring3Scale, ring3Opacity, 1200);
    spinAnimation();
    particleAnim();
    orbitAnim();

    // Cleanup function - หยุด animations เมื่อ component unmount
    return () => {
      isMounted = false;
      dot1Opacity.stopAnimation();
      dot2Opacity.stopAnimation();
      dot3Opacity.stopAnimation();
      logoScale.stopAnimation();
      glowOpacity.stopAnimation();
      glowScale.stopAnimation();
      ring1Scale.stopAnimation();
      ring1Opacity.stopAnimation();
      ring2Scale.stopAnimation();
      ring2Opacity.stopAnimation();
      ring3Scale.stopAnimation();
      ring3Opacity.stopAnimation();
      spinValue.stopAnimation();
      particleRotation.stopAnimation();
      orbitRotation.stopAnimation();
    };
  }, [
    dot1Opacity,
    dot2Opacity,
    dot3Opacity,
    logoScale,
    glowOpacity,
    glowScale,
    ring1Scale,
    ring1Opacity,
    ring2Scale,
    ring2Opacity,
    ring3Scale,
    ring3Opacity,
    spinValue,
    particleRotation,
    orbitRotation,
  ]);

  const spin = spinValue.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg'],
  });

  const particleSpin = particleRotation.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg'],
  });

  const orbitSpin = orbitRotation.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg'],
  });

  const reverseOrbitSpin = orbitRotation.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '-360deg'],
  });

  // Generate fireflies
  const fireflies = useMemo(() => {
    return Array.from({ length: FIREFLY_COUNT }, (_, i) => (
      <Firefly key={i} index={i} />
    ));
  }, []);

  return (
    <LinearGradient
      colors={['#0A0A1A', '#0F0F23', '#1A1A2E']}
      style={styles.container}
    >
      {/* Fireflies - อะตอมหิ่งห้อยเรืองแสง */}
      {fireflies}

      {/* Orbiting atoms - วงโคจรอะตอมรอบโลโก้ */}
      <View style={styles.orbitContainer}>
        {/* Orbit Ring 1 - แนวนอน */}
        <Animated.View
          style={[
            styles.orbit,
            styles.orbit1,
            { transform: [{ rotate: orbitSpin }, { rotateX: '70deg' }] },
          ]}
        >
          <View style={[styles.orbitParticle, styles.orbitParticle1]} />
        </Animated.View>

        {/* Orbit Ring 2 - แนวเฉียง */}
        <Animated.View
          style={[
            styles.orbit,
            styles.orbit2,
            { transform: [{ rotate: reverseOrbitSpin }, { rotateX: '60deg' }, { rotateY: '45deg' }] },
          ]}
        >
          <View style={[styles.orbitParticle, styles.orbitParticle2]} />
        </Animated.View>

        {/* Orbit Ring 3 - แนวตั้ง */}
        <Animated.View
          style={[
            styles.orbit,
            styles.orbit3,
            { transform: [{ rotate: orbitSpin }, { rotateY: '80deg' }] },
          ]}
        >
          <View style={[styles.orbitParticle, styles.orbitParticle3]} />
        </Animated.View>
      </View>

      {/* Background Particles - particle เดิม */}
      <Animated.View
        style={[
          styles.particleContainer,
          { transform: [{ rotate: particleSpin }] },
        ]}
      >
        {[...Array(8)].map((_, i) => (
          <View
            key={i}
            style={[
              styles.particle,
              {
                transform: [
                  { rotate: `${i * 45}deg` },
                  { translateY: -120 },
                ],
              },
            ]}
          />
        ))}
      </Animated.View>

      {/* Logo Container with Glow Effect */}
      <View style={styles.logoContainer}>
        {/* Radiant Rings - แสงเปล่งออกเป็นวง */}
        <Animated.View
          style={[
            styles.glowRing,
            {
              opacity: ring1Opacity,
              transform: [{ scale: ring1Scale }],
              borderColor: '#3B82F6',
            },
          ]}
        />
        <Animated.View
          style={[
            styles.glowRing,
            {
              opacity: ring2Opacity,
              transform: [{ scale: ring2Scale }],
              borderColor: '#8B5CF6',
            },
          ]}
        />
        <Animated.View
          style={[
            styles.glowRing,
            {
              opacity: ring3Opacity,
              transform: [{ scale: ring3Scale }],
              borderColor: '#06B6D4',
            },
          ]}
        />

        {/* Main Glow Effect - หลังโลโก้ */}
        <Animated.View
          style={[
            styles.glowEffect,
            {
              opacity: glowOpacity,
              transform: [{ scale: glowScale }],
            },
          ]}
        >
          <LinearGradient
            colors={['rgba(59, 130, 246, 0.6)', 'rgba(139, 92, 246, 0.4)', 'rgba(6, 182, 212, 0.2)']}
            style={styles.glowGradient}
            start={{ x: 0.5, y: 0.5 }}
            end={{ x: 1, y: 1 }}
          />
        </Animated.View>

        {/* Logo with pulse animation */}
        <Animated.View
          style={[
            styles.logoWrapper,
            { transform: [{ scale: logoScale }] },
          ]}
        >
          {/* Inner glow circle */}
          <LinearGradient
            colors={['rgba(59, 130, 246, 0.3)', 'rgba(139, 92, 246, 0.2)', 'transparent']}
            style={styles.logoBackground}
          />
          <Text style={styles.logo}>🚀</Text>
        </Animated.View>
      </View>

      {/* App Name with gradient text effect */}
      <View style={styles.titleContainer}>
        <Text style={styles.appName}>Thaiprompt</Text>
        <Text style={styles.appNameSub}>Affiliate</Text>
      </View>

      {/* Custom Spinner with gradient */}
      <Animated.View
        style={[
          styles.spinnerContainer,
          { transform: [{ rotate: spin }] },
        ]}
      >
        <LinearGradient
          colors={['#3B82F6', '#8B5CF6', '#06B6D4']}
          style={styles.spinnerGradient}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
        />
      </Animated.View>

      {/* Message */}
      <Text style={styles.message}>{message}</Text>

      {/* Animated Dots */}
      <View style={styles.dotsContainer}>
        <Animated.View style={[styles.dot, { opacity: dot1Opacity }]} />
        <Animated.View style={[styles.dot, styles.dotMiddle, { opacity: dot2Opacity }]} />
        <Animated.View style={[styles.dot, { opacity: dot3Opacity }]} />
      </View>

      {/* Version - แสดง build date เพื่อยืนยันเวอร์ชั่น */}
      <Text style={styles.version}>
        v{APP_INFO?.VERSION || '1.1.1'} ({APP_INFO?.BUILD_DATE || 'unknown'})
      </Text>
    </LinearGradient>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  // Orbit styles - วงโคจรอะตอม
  orbitContainer: {
    position: 'absolute',
    width: 200,
    height: 200,
    justifyContent: 'center',
    alignItems: 'center',
  },
  orbit: {
    position: 'absolute',
    width: 180,
    height: 180,
    borderRadius: 90,
    borderWidth: 1,
    borderColor: 'rgba(59, 130, 246, 0.2)',
    borderStyle: 'dashed',
  },
  orbit1: {
    width: 180,
    height: 180,
    borderRadius: 90,
  },
  orbit2: {
    width: 160,
    height: 160,
    borderRadius: 80,
    borderColor: 'rgba(139, 92, 246, 0.2)',
  },
  orbit3: {
    width: 140,
    height: 140,
    borderRadius: 70,
    borderColor: 'rgba(6, 182, 212, 0.2)',
  },
  orbitParticle: {
    position: 'absolute',
    width: 10,
    height: 10,
    borderRadius: 5,
  },
  orbitParticle1: {
    top: -5,
    left: '50%',
    marginLeft: -5,
    backgroundColor: '#3B82F6',
    shadowColor: '#3B82F6',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 1,
    shadowRadius: 8,
    elevation: 8,
  },
  orbitParticle2: {
    top: -5,
    left: '50%',
    marginLeft: -5,
    backgroundColor: '#8B5CF6',
    shadowColor: '#8B5CF6',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 1,
    shadowRadius: 8,
    elevation: 8,
  },
  orbitParticle3: {
    top: -5,
    left: '50%',
    marginLeft: -5,
    backgroundColor: '#06B6D4',
    shadowColor: '#06B6D4',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 1,
    shadowRadius: 8,
    elevation: 8,
  },
  particleContainer: {
    position: 'absolute',
    width: 300,
    height: 300,
    justifyContent: 'center',
    alignItems: 'center',
  },
  particle: {
    position: 'absolute',
    width: 4,
    height: 4,
    borderRadius: 2,
    backgroundColor: 'rgba(59, 130, 246, 0.5)',
  },
  logoContainer: {
    width: 160,
    height: 160,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 24,
    zIndex: 10,
  },
  glowRing: {
    position: 'absolute',
    width: 120,
    height: 120,
    borderRadius: 60,
    borderWidth: 2,
  },
  glowEffect: {
    position: 'absolute',
    width: 140,
    height: 140,
    borderRadius: 70,
    overflow: 'hidden',
  },
  glowGradient: {
    width: '100%',
    height: '100%',
    borderRadius: 70,
  },
  logoWrapper: {
    width: 100,
    height: 100,
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 10,
  },
  logoBackground: {
    position: 'absolute',
    width: 100,
    height: 100,
    borderRadius: 50,
  },
  logo: {
    fontSize: 64,
  },
  titleContainer: {
    alignItems: 'center',
    marginBottom: 24,
  },
  appName: {
    color: '#FFFFFF',
    fontSize: 28,
    fontWeight: 'bold',
    letterSpacing: 1,
  },
  appNameSub: {
    color: '#3B82F6',
    fontSize: 20,
    fontWeight: '600',
    letterSpacing: 2,
    marginTop: 4,
  },
  spinnerContainer: {
    width: 44,
    height: 44,
    marginVertical: 20,
    borderRadius: 22,
    borderWidth: 3,
    borderColor: 'transparent',
    borderTopColor: 'transparent',
    overflow: 'hidden',
  },
  spinnerGradient: {
    position: 'absolute',
    top: -3,
    left: -3,
    right: -3,
    bottom: -3,
    borderRadius: 22,
    borderWidth: 3,
    borderColor: 'transparent',
    borderTopColor: '#3B82F6',
    borderRightColor: '#8B5CF6',
  },
  message: {
    color: '#9CA3AF',
    fontSize: 16,
    marginTop: 8,
  },
  dotsContainer: {
    flexDirection: 'row',
    marginTop: 16,
  },
  dot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#3B82F6',
  },
  dotMiddle: {
    marginHorizontal: 8,
    backgroundColor: '#8B5CF6',
  },
  version: {
    position: 'absolute',
    bottom: 40,
    color: '#6B7280',
    fontSize: 12,
  },
});
