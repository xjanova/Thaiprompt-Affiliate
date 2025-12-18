/**
 * Index Screen - Premium Landing Page V3
 * ออกแบบใหม่ให้สวยงาม มี Animated Effects
 */

import React, { useEffect, useRef, useState } from 'react';
import {
  View,
  Text,
  Pressable,
  StyleSheet,
  StatusBar,
  Dimensions,
  ScrollView,
  Animated,
  Easing,
  Image,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router, Redirect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO } from '@/config/appConfig';

const { width, height } = Dimensions.get('window');

// =====================================================
// Animated Components
// =====================================================

/**
 * Floating Particle - อนุภาคลอยเลื่อนสวยๆ
 */
const FloatingParticle = ({ delay, size, color, startX, startY }: {
  delay: number;
  size: number;
  color: string;
  startX: number;
  startY: number;
}) => {
  const translateY = useRef(new Animated.Value(0)).current;
  const translateX = useRef(new Animated.Value(0)).current;
  const opacity = useRef(new Animated.Value(0)).current;
  const scale = useRef(new Animated.Value(0.5)).current;

  useEffect(() => {
    const animate = () => {
      // Reset values
      translateY.setValue(0);
      translateX.setValue(0);
      opacity.setValue(0);
      scale.setValue(0.5);

      Animated.sequence([
        Animated.delay(delay),
        Animated.parallel([
          // Fade in and scale up
          Animated.timing(opacity, {
            toValue: 0.8,
            duration: 800,
            useNativeDriver: true,
          }),
          Animated.timing(scale, {
            toValue: 1,
            duration: 800,
            useNativeDriver: true,
          }),
        ]),
        // Float animation
        Animated.parallel([
          Animated.timing(translateY, {
            toValue: -height * 0.3,
            duration: 4000 + Math.random() * 2000,
            easing: Easing.out(Easing.quad),
            useNativeDriver: true,
          }),
          Animated.timing(translateX, {
            toValue: (Math.random() - 0.5) * 100,
            duration: 4000 + Math.random() * 2000,
            easing: Easing.inOut(Easing.sin),
            useNativeDriver: true,
          }),
          // Fade out
          Animated.sequence([
            Animated.delay(3000),
            Animated.timing(opacity, {
              toValue: 0,
              duration: 1500,
              useNativeDriver: true,
            }),
          ]),
        ]),
      ]).start(() => animate());
    };

    animate();
  }, []);

  return (
    <Animated.View
      style={[
        styles.particle,
        {
          left: startX,
          top: startY,
          width: size,
          height: size,
          borderRadius: size / 2,
          backgroundColor: color,
          opacity,
          transform: [{ translateX }, { translateY }, { scale }],
        },
      ]}
    />
  );
};

/**
 * Glowing Orb - วงกลมเรืองแสงลอยขึ้นลง
 */
const GlowingOrb = ({ delay, size, color, position }: {
  delay: number;
  size: number;
  color: string;
  position: { top?: number; bottom?: number; left?: number; right?: number };
}) => {
  const translateY = useRef(new Animated.Value(0)).current;
  const opacity = useRef(new Animated.Value(0.2)).current;
  const scale = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    const floatAnimation = Animated.loop(
      Animated.sequence([
        Animated.parallel([
          Animated.timing(translateY, {
            toValue: -25,
            duration: 3000,
            easing: Easing.inOut(Easing.sin),
            useNativeDriver: true,
          }),
          Animated.timing(opacity, {
            toValue: 0.5,
            duration: 3000,
            useNativeDriver: true,
          }),
          Animated.timing(scale, {
            toValue: 1.1,
            duration: 3000,
            useNativeDriver: true,
          }),
        ]),
        Animated.parallel([
          Animated.timing(translateY, {
            toValue: 0,
            duration: 3000,
            easing: Easing.inOut(Easing.sin),
            useNativeDriver: true,
          }),
          Animated.timing(opacity, {
            toValue: 0.2,
            duration: 3000,
            useNativeDriver: true,
          }),
          Animated.timing(scale, {
            toValue: 1,
            duration: 3000,
            useNativeDriver: true,
          }),
        ]),
      ])
    );

    const timeout = setTimeout(() => floatAnimation.start(), delay);
    return () => {
      clearTimeout(timeout);
      floatAnimation.stop();
    };
  }, []);

  return (
    <Animated.View
      style={[
        styles.orb,
        {
          width: size,
          height: size,
          borderRadius: size / 2,
          backgroundColor: color,
          ...position,
          opacity,
          transform: [{ translateY }, { scale }],
        },
      ]}
    />
  );
};

/**
 * Pulsing Ring - วงแหวนพัลส์รอบโลโก้
 */
const PulsingRing = ({ delay, size }: { delay: number; size: number }) => {
  const scale = useRef(new Animated.Value(1)).current;
  const opacity = useRef(new Animated.Value(0.6)).current;

  useEffect(() => {
    const pulseAnimation = Animated.loop(
      Animated.sequence([
        Animated.delay(delay),
        Animated.parallel([
          Animated.timing(scale, {
            toValue: 1.5,
            duration: 2000,
            easing: Easing.out(Easing.quad),
            useNativeDriver: true,
          }),
          Animated.timing(opacity, {
            toValue: 0,
            duration: 2000,
            useNativeDriver: true,
          }),
        ]),
        Animated.parallel([
          Animated.timing(scale, {
            toValue: 1,
            duration: 0,
            useNativeDriver: true,
          }),
          Animated.timing(opacity, {
            toValue: 0.6,
            duration: 0,
            useNativeDriver: true,
          }),
        ]),
      ])
    );

    pulseAnimation.start();
    return () => pulseAnimation.stop();
  }, []);

  return (
    <Animated.View
      style={[
        styles.pulsingRing,
        {
          width: size,
          height: size,
          borderRadius: size / 2,
          opacity,
          transform: [{ scale }],
        },
      ]}
    />
  );
};

// =====================================================
// Main Component
// =====================================================

export default function IndexScreen() {
  const { isAuthenticated, isInitialized } = useAuthStore();
  const [logoError, setLogoError] = useState(false);

  // Animations
  const logoScale = useRef(new Animated.Value(0.5)).current;
  const logoOpacity = useRef(new Animated.Value(0)).current;
  const contentOpacity = useRef(new Animated.Value(0)).current;
  const contentSlide = useRef(new Animated.Value(30)).current;
  const buttonScale = useRef(new Animated.Value(0.9)).current;

  useEffect(() => {
    // Logo entrance animation
    Animated.sequence([
      Animated.parallel([
        Animated.spring(logoScale, {
          toValue: 1,
          tension: 50,
          friction: 7,
          useNativeDriver: true,
        }),
        Animated.timing(logoOpacity, {
          toValue: 1,
          duration: 800,
          useNativeDriver: true,
        }),
      ]),
      // Content slide in
      Animated.parallel([
        Animated.timing(contentOpacity, {
          toValue: 1,
          duration: 600,
          useNativeDriver: true,
        }),
        Animated.spring(contentSlide, {
          toValue: 0,
          tension: 50,
          friction: 8,
          useNativeDriver: true,
        }),
        Animated.spring(buttonScale, {
          toValue: 1,
          tension: 50,
          friction: 7,
          useNativeDriver: true,
        }),
      ]),
    ]).start();
  }, []);

  // ถ้า login แล้ว redirect ไป tabs ทันที
  if (isAuthenticated && isInitialized) {
    return <Redirect href="/(tabs)" />;
  }

  const goToLogin = () => router.push('/login');
  const goToRegister = () => router.push('/register');

  // สร้าง particles
  const particles = Array.from({ length: 15 }, (_, i) => ({
    id: i,
    delay: i * 400,
    size: 4 + Math.random() * 8,
    color: ['#3B82F6', '#8B5CF6', '#EC4899', '#10B981', '#F59E0B'][Math.floor(Math.random() * 5)],
    startX: Math.random() * width,
    startY: height * 0.5 + Math.random() * (height * 0.5),
  }));

  return (
    <View style={styles.container}>
      {/* Premium Gradient Background */}
      <LinearGradient
        colors={['#0F0F23', '#1a1a2e', '#16213e', '#0F0F23']}
        locations={[0, 0.3, 0.7, 1]}
        style={StyleSheet.absoluteFill}
      />

      {/* Glowing Orbs Background */}
      <GlowingOrb delay={0} size={200} color="rgba(59,130,246,0.15)" position={{ top: -50, left: -80 }} />
      <GlowingOrb delay={500} size={150} color="rgba(139,92,246,0.12)" position={{ top: 150, right: -60 }} />
      <GlowingOrb delay={1000} size={120} color="rgba(236,72,153,0.1)" position={{ bottom: 200, left: -40 }} />
      <GlowingOrb delay={1500} size={180} color="rgba(16,185,129,0.08)" position={{ bottom: 50, right: -70 }} />

      {/* Floating Particles */}
      {particles.map((p) => (
        <FloatingParticle key={p.id} {...p} />
      ))}

      <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />

      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* Animated Logo Section */}
        <Animated.View
          style={[
            styles.logoSection,
            {
              opacity: logoOpacity,
              transform: [{ scale: logoScale }],
            },
          ]}
        >
          {/* Pulsing Rings */}
          <View style={styles.logoWrapper}>
            <PulsingRing delay={0} size={130} />
            <PulsingRing delay={700} size={130} />
            <PulsingRing delay={1400} size={130} />

            {/* Logo Container with Gradient Border */}
            <LinearGradient
              colors={['#3B82F6', '#8B5CF6', '#EC4899', '#3B82F6']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={styles.logoBorder}
            >
              <View style={styles.logoInner}>
                {!logoError ? (
                  <Image
                    source={require('@/assets/images/icon.png')}
                    style={styles.logo}
                    resizeMode="contain"
                    onError={() => setLogoError(true)}
                  />
                ) : (
                  <Text style={styles.logoEmoji}>💎</Text>
                )}
              </View>
            </LinearGradient>
          </View>

          <Text style={styles.appName}>{APP_INFO.NAME}</Text>
          <View style={styles.subtitleRow}>
            <View style={styles.subtitleLine} />
            <Text style={styles.appSubtitle}>AFFILIATE</Text>
            <View style={styles.subtitleLine} />
          </View>
        </Animated.View>

        {/* Animated Content */}
        <Animated.View
          style={[
            styles.contentSection,
            {
              opacity: contentOpacity,
              transform: [{ translateY: contentSlide }],
            },
          ]}
        >
          {/* Tagline */}
          <View style={styles.taglineSection}>
            <Text style={styles.tagline}>สร้างรายได้</Text>
            <Text style={styles.taglineHighlight}>ไม่จำกัด</Text>
            <Text style={styles.taglineDesc}>
              ร่วมเป็นส่วนหนึ่งของเครือข่ายพันธมิตร{'\n'}ที่เติบโตเร็วที่สุด
            </Text>
          </View>

          {/* Features with Glassmorphism */}
          <View style={styles.featuresSection}>
            {[
              { icon: '💰', text: 'รับค่าคอมมิชชั่นทันที', color: '#10B981' },
              { icon: '👥', text: 'สร้างทีมได้ไม่จำกัด', color: '#3B82F6' },
              { icon: '🛡️', text: 'ปลอดภัย มั่นคง', color: '#8B5CF6' },
            ].map((feature, index) => (
              <View key={index} style={styles.featureRow}>
                <LinearGradient
                  colors={[`${feature.color}30`, `${feature.color}10`]}
                  style={styles.featureIconBg}
                >
                  <Text style={styles.featureIconText}>{feature.icon}</Text>
                </LinearGradient>
                <Text style={styles.featureText}>{feature.text}</Text>
                <View style={[styles.featureAccent, { backgroundColor: feature.color }]} />
              </View>
            ))}
          </View>
        </Animated.View>

        {/* Animated Buttons */}
        <Animated.View
          style={[
            styles.buttonSection,
            {
              opacity: contentOpacity,
              transform: [{ scale: buttonScale }],
            },
          ]}
        >
          <Pressable
            onPress={goToLogin}
            style={({ pressed }) => [
              styles.primaryButtonWrapper,
              pressed && styles.buttonPressed,
            ]}
          >
            <LinearGradient
              colors={['#3B82F6', '#2563EB', '#1D4ED8']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={styles.primaryButton}
            >
              <Text style={styles.buttonIcon}>🔓</Text>
              <Text style={styles.primaryButtonText}>เข้าสู่ระบบ</Text>
            </LinearGradient>
          </Pressable>

          <Pressable
            onPress={goToRegister}
            style={({ pressed }) => [
              styles.secondaryButton,
              pressed && styles.buttonPressed,
            ]}
          >
            <Text style={styles.buttonIcon}>✨</Text>
            <Text style={styles.secondaryButtonText}>สมัครสมาชิกใหม่</Text>
          </Pressable>
        </Animated.View>

        {/* Footer */}
        <View style={styles.footer}>
          <Text style={styles.footerText}>
            v{APP_INFO.VERSION} ({APP_INFO.BUILD_DATE})
          </Text>
        </View>
      </ScrollView>
    </View>
  );
}

// =====================================================
// Styles
// =====================================================

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
    overflow: 'hidden',
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 24,
    paddingTop: height * 0.08,
    paddingBottom: 40,
    minHeight: height,
  },

  // Particles & Orbs
  particle: {
    position: 'absolute',
    shadowColor: '#FFF',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0.8,
    shadowRadius: 4,
    elevation: 4,
  },
  orb: {
    position: 'absolute',
  },

  // Logo Section
  logoSection: {
    alignItems: 'center',
    marginBottom: 32,
  },
  logoWrapper: {
    position: 'relative',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 20,
    width: 130,
    height: 130,
  },
  pulsingRing: {
    position: 'absolute',
    borderWidth: 2,
    borderColor: 'rgba(59,130,246,0.4)',
  },
  logoBorder: {
    padding: 4,
    borderRadius: 32,
    shadowColor: '#3B82F6',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0.5,
    shadowRadius: 20,
    elevation: 10,
  },
  logoInner: {
    backgroundColor: '#0F0F23',
    borderRadius: 28,
    padding: 4,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logo: {
    width: 90,
    height: 90,
    borderRadius: 24,
  },
  logoEmoji: {
    fontSize: 50,
  },
  appName: {
    fontSize: 36,
    fontWeight: 'bold',
    color: '#FFFFFF',
    letterSpacing: 1,
    textShadowColor: 'rgba(59,130,246,0.5)',
    textShadowOffset: { width: 0, height: 2 },
    textShadowRadius: 10,
  },
  subtitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 8,
  },
  subtitleLine: {
    width: 30,
    height: 1,
    backgroundColor: 'rgba(59,130,246,0.5)',
    marginHorizontal: 12,
  },
  appSubtitle: {
    fontSize: 14,
    color: '#3B82F6',
    fontWeight: '700',
    letterSpacing: 6,
  },

  // Content Section
  contentSection: {
    flex: 1,
  },
  taglineSection: {
    alignItems: 'center',
    marginBottom: 28,
  },
  tagline: {
    fontSize: 24,
    color: '#FFFFFF',
    fontWeight: '300',
  },
  taglineHighlight: {
    fontSize: 42,
    fontWeight: 'bold',
    color: '#3B82F6',
    marginBottom: 8,
    textShadowColor: 'rgba(59,130,246,0.3)',
    textShadowOffset: { width: 0, height: 2 },
    textShadowRadius: 10,
  },
  taglineDesc: {
    fontSize: 14,
    color: '#9CA3AF',
    textAlign: 'center',
    lineHeight: 22,
  },

  // Features Section
  featuresSection: {
    marginBottom: 32,
  },
  featureRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    paddingHorizontal: 16,
    paddingVertical: 14,
    borderRadius: 16,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
    overflow: 'hidden',
  },
  featureIconBg: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 14,
  },
  featureIconText: {
    fontSize: 20,
  },
  featureText: {
    flex: 1,
    fontSize: 15,
    color: '#FFFFFF',
    fontWeight: '500',
  },
  featureAccent: {
    position: 'absolute',
    right: 0,
    top: 0,
    bottom: 0,
    width: 3,
    borderTopRightRadius: 16,
    borderBottomRightRadius: 16,
  },

  // Button Section
  buttonSection: {
    marginTop: 'auto',
    gap: 12,
  },
  primaryButtonWrapper: {
    borderRadius: 16,
    overflow: 'hidden',
    shadowColor: '#3B82F6',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.4,
    shadowRadius: 12,
    elevation: 8,
  },
  primaryButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 18,
    gap: 10,
  },
  primaryButtonText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  secondaryButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 18,
    borderRadius: 16,
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.15)',
    gap: 10,
  },
  secondaryButtonText: {
    fontSize: 18,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  buttonIcon: {
    fontSize: 20,
  },
  buttonPressed: {
    opacity: 0.8,
    transform: [{ scale: 0.98 }],
  },

  // Footer
  footer: {
    alignItems: 'center',
    marginTop: 24,
  },
  footerText: {
    fontSize: 11,
    color: '#6B7280',
  },
});
