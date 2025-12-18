/**
 * TPIX Token - หน้า Coming Soon สำหรับเหรียญ TPIX
 *
 * เหรียญแห่งอนาคต - เหรียญของคนไทย เพื่อคนไทย
 *
 * Features:
 * - ใช้รูปภาพ TPIX1.png เป็น Hero Image
 * - 3D Floating Animation
 * - Particle Effects (Stars/Coins)
 * - Glowing Text Effects
 * - Gradient Background
 * - Premium Glassmorphism UI Design
 */

import React, { useEffect, useRef } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Dimensions,
  Pressable,
  Animated,
  Easing,
  StatusBar,
  ScrollView,
  Image,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { BlurView } from 'expo-blur';
import { router } from 'expo-router';
import { openUrl } from '@/utils/navigation';

// รูปภาพ TPIX เหรียญทอง 3D
const TPIX_COIN_IMAGE = require('@/assets/images/TPIX1.png');

const { width: SCREEN_WIDTH, height: SCREEN_HEIGHT } = Dimensions.get('window');

// Wiki URL สำหรับ TPIX
const TPIX_WIKI_URL = 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/docs/features/crypto-tpix/TPIX_TOKEN_SYSTEM.md';

// จำนวน particles
const PARTICLE_COUNT = 30;
const STAR_COUNT = 50;

// =====================================================
// Floating Particle Component - อนุภาคลอยขึ้น
// =====================================================
const FloatingParticle = ({ delay, duration, startX, emoji }: {
  delay: number;
  duration: number;
  startX: number;
  emoji: string;
}) => {
  const translateY = useRef(new Animated.Value(SCREEN_HEIGHT + 50)).current;
  const opacity = useRef(new Animated.Value(0)).current;
  const rotate = useRef(new Animated.Value(0)).current;
  const scale = useRef(new Animated.Value(0.5 + Math.random() * 0.5)).current;

  useEffect(() => {
    const animate = () => {
      translateY.setValue(SCREEN_HEIGHT + 50);
      opacity.setValue(0);
      rotate.setValue(0);

      Animated.sequence([
        Animated.delay(delay),
        Animated.parallel([
          Animated.timing(translateY, {
            toValue: -100,
            duration: duration,
            easing: Easing.linear,
            useNativeDriver: true,
          }),
          Animated.sequence([
            Animated.timing(opacity, {
              toValue: 0.8,
              duration: duration * 0.2,
              useNativeDriver: true,
            }),
            Animated.timing(opacity, {
              toValue: 0.8,
              duration: duration * 0.6,
              useNativeDriver: true,
            }),
            Animated.timing(opacity, {
              toValue: 0,
              duration: duration * 0.2,
              useNativeDriver: true,
            }),
          ]),
          Animated.timing(rotate, {
            toValue: 1,
            duration: duration,
            easing: Easing.linear,
            useNativeDriver: true,
          }),
        ]),
      ]).start(() => animate());
    };

    animate();
  }, []);

  const rotateInterpolate = rotate.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg'],
  });

  return (
    <Animated.View
      style={[
        styles.particle,
        {
          left: startX,
          transform: [
            { translateY },
            { rotate: rotateInterpolate },
            { scale },
          ],
          opacity,
        },
      ]}
    >
      <Text style={styles.particleEmoji}>{emoji}</Text>
    </Animated.View>
  );
};

// =====================================================
// Twinkling Star Component - ดาวกระพริบ
// =====================================================
const TwinklingStar = ({ x, y, size, delay }: {
  x: number;
  y: number;
  size: number;
  delay: number;
}) => {
  const opacity = useRef(new Animated.Value(0)).current;
  const scale = useRef(new Animated.Value(0.5)).current;

  useEffect(() => {
    const animate = () => {
      Animated.sequence([
        Animated.delay(delay),
        Animated.parallel([
          Animated.sequence([
            Animated.timing(opacity, {
              toValue: 1,
              duration: 1000 + Math.random() * 1000,
              useNativeDriver: true,
            }),
            Animated.timing(opacity, {
              toValue: 0.2,
              duration: 1000 + Math.random() * 1000,
              useNativeDriver: true,
            }),
          ]),
          Animated.sequence([
            Animated.timing(scale, {
              toValue: 1.2,
              duration: 1000,
              useNativeDriver: true,
            }),
            Animated.timing(scale, {
              toValue: 0.5,
              duration: 1000,
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
        styles.star,
        {
          left: x,
          top: y,
          width: size,
          height: size,
          opacity,
          transform: [{ scale }],
        },
      ]}
    />
  );
};

// =====================================================
// 3D TPIX Coin Hero Image Component - รูปเหรียญ TPIX 3D
// =====================================================
const TPIXCoinHero = () => {
  const glow = useRef(new Animated.Value(0)).current;
  const float = useRef(new Animated.Value(0)).current;
  const scale = useRef(new Animated.Value(1)).current;
  const shimmer = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // เอฟเฟคเรืองแสงรอบเหรียญ
    Animated.loop(
      Animated.sequence([
        Animated.timing(glow, {
          toValue: 1,
          duration: 2000,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
        Animated.timing(glow, {
          toValue: 0,
          duration: 2000,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
      ])
    ).start();

    // ลอยขึ้นลงอย่างนุ่มนวล
    Animated.loop(
      Animated.sequence([
        Animated.timing(float, {
          toValue: -12,
          duration: 2500,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
        Animated.timing(float, {
          toValue: 0,
          duration: 2500,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
      ])
    ).start();

    // Pulse scale effect
    Animated.loop(
      Animated.sequence([
        Animated.timing(scale, {
          toValue: 1.02,
          duration: 3000,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
        Animated.timing(scale, {
          toValue: 1,
          duration: 3000,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
      ])
    ).start();

    // Shimmer effect
    Animated.loop(
      Animated.timing(shimmer, {
        toValue: 1,
        duration: 3000,
        easing: Easing.linear,
        useNativeDriver: true,
      })
    ).start();
  }, []);

  const glowOpacity = glow.interpolate({
    inputRange: [0, 1],
    outputRange: [0.4, 0.9],
  });

  const glowScale = glow.interpolate({
    inputRange: [0, 1],
    outputRange: [1, 1.15],
  });

  const shimmerTranslate = shimmer.interpolate({
    inputRange: [0, 1],
    outputRange: [-SCREEN_WIDTH, SCREEN_WIDTH],
  });

  return (
    <Animated.View
      style={[
        styles.coinHeroContainer,
        {
          transform: [
            { translateY: float },
            { scale },
          ],
        },
      ]}
    >
      {/* Multiple layered glow effects */}
      <Animated.View
        style={[
          styles.coinGlowOuter,
          {
            opacity: glowOpacity,
            transform: [{ scale: glowScale }],
          },
        ]}
      />
      <Animated.View
        style={[
          styles.coinGlowMiddle,
          {
            opacity: glow.interpolate({
              inputRange: [0, 1],
              outputRange: [0.3, 0.7],
            }),
          },
        ]}
      />
      <Animated.View
        style={[
          styles.coinGlowInner,
          {
            opacity: glow.interpolate({
              inputRange: [0, 1],
              outputRange: [0.5, 1],
            }),
          },
        ]}
      />

      {/* รูปเหรียญ TPIX 3D */}
      <View style={styles.coinImageWrapper}>
        <Image
          source={TPIX_COIN_IMAGE}
          style={styles.coinImage}
          resizeMode="contain"
        />

        {/* Shimmer overlay effect */}
        <Animated.View
          style={[
            styles.shimmerOverlay,
            {
              transform: [{ translateX: shimmerTranslate }],
            },
          ]}
        >
          <LinearGradient
            colors={['transparent', 'rgba(255,255,255,0.15)', 'transparent']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.shimmerGradient}
          />
        </Animated.View>
      </View>

      {/* Animated ring effects รอบเหรียญ */}
      <Animated.View
        style={[
          styles.coinRingInner,
          { opacity: glowOpacity },
        ]}
      />
      <Animated.View
        style={[
          styles.coinRingOuter,
          {
            opacity: glow.interpolate({
              inputRange: [0, 1],
              outputRange: [0.2, 0.5],
            }),
          },
        ]}
      />
    </Animated.View>
  );
};

// =====================================================
// Pulsing Text Component - ข้อความกระพริบ
// =====================================================
const PulsingText = ({ children, style, delay = 0 }: { children: React.ReactNode; style?: any; delay?: number }) => {
  const opacity = useRef(new Animated.Value(0.7)).current;
  const scale = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    Animated.loop(
      Animated.sequence([
        Animated.delay(delay),
        Animated.parallel([
          Animated.timing(opacity, {
            toValue: 1,
            duration: 1000,
            useNativeDriver: true,
          }),
          Animated.timing(scale, {
            toValue: 1.02,
            duration: 1000,
            useNativeDriver: true,
          }),
        ]),
        Animated.parallel([
          Animated.timing(opacity, {
            toValue: 0.7,
            duration: 1000,
            useNativeDriver: true,
          }),
          Animated.timing(scale, {
            toValue: 1,
            duration: 1000,
            useNativeDriver: true,
          }),
        ]),
      ])
    ).start();
  }, []);

  return (
    <Animated.Text style={[style, { opacity, transform: [{ scale }] }]}>
      {children}
    </Animated.Text>
  );
};

// =====================================================
// Main Component
// =====================================================
export default function TPIXScreen() {
  // หน้านี้ใช้ dark theme เสมอ (Cyberpunk style)
  const fadeIn = useRef(new Animated.Value(0)).current;

  // Generate particles
  const particles = Array.from({ length: PARTICLE_COUNT }, (_, i) => ({
    id: i,
    delay: Math.random() * 5000,
    duration: 8000 + Math.random() * 4000,
    startX: Math.random() * SCREEN_WIDTH,
    emoji: ['💰', '🪙', '✨', '⭐', '💎'][Math.floor(Math.random() * 5)],
  }));

  // Generate stars
  const stars = Array.from({ length: STAR_COUNT }, (_, i) => ({
    id: i,
    x: Math.random() * SCREEN_WIDTH,
    y: Math.random() * SCREEN_HEIGHT * 0.6,
    size: 2 + Math.random() * 3,
    delay: Math.random() * 2000,
  }));

  useEffect(() => {
    Animated.timing(fadeIn, {
      toValue: 1,
      duration: 1000,
      useNativeDriver: true,
    }).start();
  }, []);

  const handleOpenWiki = () => {
    // เปิด Wiki ใน WebView ภายในแอพ
    openUrl(TPIX_WIKI_URL, 'TPIX Token - Wiki', '📖');
  };

  const handleGoBack = () => {
    router.back();
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0a0a1a" />

      {/* Background Gradient - Cyberpunk Dark Blue */}
      <LinearGradient
        colors={['#050510', '#0a0a20', '#0d1025', '#050515']}
        style={styles.backgroundGradient}
      />

      {/* Stars Background */}
      {stars.map((star) => (
        <TwinklingStar
          key={`star-${star.id}`}
          x={star.x}
          y={star.y}
          size={star.size}
          delay={star.delay}
        />
      ))}

      {/* Floating Particles */}
      {particles.map((particle) => (
        <FloatingParticle
          key={`particle-${particle.id}`}
          delay={particle.delay}
          duration={particle.duration}
          startX={particle.startX}
          emoji={particle.emoji}
        />
      ))}

      {/* Content */}
      <Animated.View style={[styles.content, { opacity: fadeIn }]}>
        <ScrollView
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* Back Button */}
          <Pressable onPress={handleGoBack} style={styles.backButton}>
            <Text style={styles.backButtonText}>← กลับ</Text>
          </Pressable>

          {/* Header */}
          <View style={styles.header}>
            <PulsingText style={styles.comingSoon}>
              🚀 COMING SOON 🚀
            </PulsingText>
          </View>

          {/* 3D TPIX Coin Hero Image */}
          <TPIXCoinHero />

          {/* Title */}
          <View style={styles.titleContainer}>
            <Text style={styles.title}>TPIX TOKEN</Text>
            <Text style={styles.subtitle}>เหรียญแห่งอนาคต</Text>
          </View>

          {/* Main Message - Glassmorphism Card with Cyberpunk Style */}
          <BlurView intensity={30} tint="dark" style={styles.messageCard}>
            <LinearGradient
              colors={['rgba(0,212,255,0.15)', 'rgba(255,215,0,0.1)', 'rgba(0,212,255,0.08)']}
              style={styles.messageGradient}
            >
              {/* Tech corner decorations */}
              <View style={styles.techCornerTL} />
              <View style={styles.techCornerTR} />
              <View style={styles.techCornerBL} />
              <View style={styles.techCornerBR} />

              <Text style={styles.messageTitle}>
                🚀 เตรียมพบกับเราเร็วๆ นี้
              </Text>

              <View style={styles.messageDivider} />

              <Text style={styles.messageText}>
                🇹🇭 เหรียญของคนไทย{'\n'}
                💪 เพื่อคนไทย{'\n'}
                🌏 ข้ามผ่านวิกฤตโลก
              </Text>

              <View style={styles.messageDivider} />

              <View style={styles.features}>
                <View style={styles.featureItem}>
                  <View style={styles.featureIconBox}>
                    <Text style={styles.featureIcon}>🔐</Text>
                  </View>
                  <Text style={styles.featureText}>ปลอดภัย</Text>
                </View>
                <View style={styles.featureItem}>
                  <View style={styles.featureIconBox}>
                    <Text style={styles.featureIcon}>⚡</Text>
                  </View>
                  <Text style={styles.featureText}>รวดเร็ว</Text>
                </View>
                <View style={styles.featureItem}>
                  <View style={styles.featureIconBox}>
                    <Text style={styles.featureIcon}>🌐</Text>
                  </View>
                  <Text style={styles.featureText}>ไร้พรมแดน</Text>
                </View>
              </View>
            </LinearGradient>
          </BlurView>

          {/* Tech Info Cards */}
          <View style={styles.techInfoRow}>
            <View style={styles.techInfoCard}>
              <Text style={styles.techInfoIcon}>🔗</Text>
              <Text style={styles.techInfoLabel}>Blockchain</Text>
              <Text style={styles.techInfoValue}>BSC Network</Text>
            </View>
            <View style={styles.techInfoCard}>
              <Text style={styles.techInfoIcon}>💎</Text>
              <Text style={styles.techInfoLabel}>Token Type</Text>
              <Text style={styles.techInfoValue}>BEP-20</Text>
            </View>
          </View>

          {/* Stats Preview */}
          <View style={styles.statsContainer}>
            <View style={styles.statItem}>
              <Text style={styles.statValue}>100M+</Text>
              <Text style={styles.statLabel}>Total Supply</Text>
            </View>
            <View style={styles.statDivider} />
            <View style={styles.statItem}>
              <Text style={styles.statValue}>DEX</Text>
              <Text style={styles.statLabel}>Ready</Text>
            </View>
            <View style={styles.statDivider} />
            <View style={styles.statItem}>
              <Text style={styles.statValue}>Staking</Text>
              <Text style={styles.statLabel}>Available</Text>
            </View>
          </View>

          {/* Wiki Button - Cyberpunk Gradient */}
          <Pressable onPress={handleOpenWiki} style={styles.wikiButton}>
            <LinearGradient
              colors={['#00d4ff', '#00a8cc', '#FFD700']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.wikiButtonGradient}
            >
              <Text style={styles.wikiButtonIcon}>📖</Text>
              <Text style={styles.wikiButtonText}>อ่านเพิ่มเติมใน Wiki</Text>
              <Text style={styles.wikiButtonArrow}>→</Text>
            </LinearGradient>
          </Pressable>

          {/* Footer */}
          <View style={styles.footer}>
            <Text style={styles.footerText}>
              Powered by ThaiPrompt Ecosystem
            </Text>
            <Text style={styles.footerSubText}>
              🇹🇭 Made in Thailand with ❤️
            </Text>
          </View>
        </ScrollView>
      </Animated.View>
    </View>
  );
}

// =====================================================
// Styles
// =====================================================
const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0a0a1a',
  },
  backgroundGradient: {
    ...StyleSheet.absoluteFillObject,
  },
  content: {
    flex: 1,
    zIndex: 10,
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingTop: 60,
    paddingBottom: 40,
    alignItems: 'center',
  },

  // Back Button
  backButton: {
    position: 'absolute',
    top: 10,
    left: 0,
    padding: 10,
    zIndex: 100,
  },
  backButtonText: {
    color: '#FFD700',
    fontSize: 16,
    fontWeight: '600',
  },

  // Header
  header: {
    alignItems: 'center',
    marginBottom: 20,
  },
  comingSoon: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFD700',
    textShadowColor: 'rgba(255, 215, 0, 0.5)',
    textShadowOffset: { width: 0, height: 0 },
    textShadowRadius: 20,
    letterSpacing: 3,
  },

  // Particles
  particle: {
    position: 'absolute',
    zIndex: 5,
  },
  particleEmoji: {
    fontSize: 24,
  },

  // Stars
  star: {
    position: 'absolute',
    backgroundColor: '#fff',
    borderRadius: 50,
    zIndex: 1,
  },

  // TPIX Coin Hero Image
  coinHeroContainer: {
    width: SCREEN_WIDTH * 0.9,
    height: 220,
    alignItems: 'center',
    justifyContent: 'center',
    marginVertical: 20,
  },
  coinGlowOuter: {
    position: 'absolute',
    width: SCREEN_WIDTH * 0.85,
    height: 200,
    borderRadius: 24,
    backgroundColor: '#00d4ff',
    shadowColor: '#00d4ff',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 1,
    shadowRadius: 50,
  },
  coinGlowMiddle: {
    position: 'absolute',
    width: SCREEN_WIDTH * 0.8,
    height: 180,
    borderRadius: 20,
    backgroundColor: 'rgba(255, 215, 0, 0.3)',
    shadowColor: '#FFD700',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0.8,
    shadowRadius: 30,
  },
  coinGlowInner: {
    position: 'absolute',
    width: SCREEN_WIDTH * 0.75,
    height: 160,
    borderRadius: 16,
    backgroundColor: 'rgba(255, 200, 0, 0.2)',
  },
  coinImageWrapper: {
    width: SCREEN_WIDTH * 0.85,
    height: 200,
    borderRadius: 20,
    overflow: 'hidden',
    shadowColor: '#FFD700',
    shadowOffset: { width: 0, height: 15 },
    shadowOpacity: 0.6,
    shadowRadius: 25,
    elevation: 25,
    backgroundColor: '#0a0a1a',
  },
  coinImage: {
    width: '100%',
    height: '100%',
  },
  shimmerOverlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    width: 100,
    height: '100%',
  },
  shimmerGradient: {
    width: 100,
    height: '100%',
  },
  coinRingInner: {
    position: 'absolute',
    width: SCREEN_WIDTH * 0.88,
    height: 210,
    borderRadius: 22,
    borderWidth: 2,
    borderColor: '#00d4ff',
  },
  coinRingOuter: {
    position: 'absolute',
    width: SCREEN_WIDTH * 0.92,
    height: 220,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#FFD700',
  },

  // Title
  titleContainer: {
    alignItems: 'center',
    marginBottom: 30,
  },
  title: {
    fontSize: 42,
    fontWeight: 'bold',
    color: '#FFD700',
    textShadowColor: 'rgba(255, 215, 0, 0.5)',
    textShadowOffset: { width: 0, height: 0 },
    textShadowRadius: 30,
    letterSpacing: 4,
  },
  subtitle: {
    fontSize: 18,
    color: '#FFA500',
    marginTop: 8,
    letterSpacing: 2,
  },

  // Message Card
  messageCard: {
    width: '100%',
    borderRadius: 24,
    overflow: 'hidden',
    marginBottom: 20,
    borderWidth: 1,
    borderColor: 'rgba(0, 212, 255, 0.3)',
  },
  messageGradient: {
    padding: 24,
    alignItems: 'center',
    position: 'relative',
  },
  // Tech corner decorations
  techCornerTL: {
    position: 'absolute',
    top: 8,
    left: 8,
    width: 20,
    height: 20,
    borderTopWidth: 2,
    borderLeftWidth: 2,
    borderColor: '#00d4ff',
  },
  techCornerTR: {
    position: 'absolute',
    top: 8,
    right: 8,
    width: 20,
    height: 20,
    borderTopWidth: 2,
    borderRightWidth: 2,
    borderColor: '#00d4ff',
  },
  techCornerBL: {
    position: 'absolute',
    bottom: 8,
    left: 8,
    width: 20,
    height: 20,
    borderBottomWidth: 2,
    borderLeftWidth: 2,
    borderColor: '#FFD700',
  },
  techCornerBR: {
    position: 'absolute',
    bottom: 8,
    right: 8,
    width: 20,
    height: 20,
    borderBottomWidth: 2,
    borderRightWidth: 2,
    borderColor: '#FFD700',
  },
  messageTitle: {
    fontSize: 22,
    fontWeight: 'bold',
    color: '#FFD700',
    textAlign: 'center',
    marginBottom: 16,
    textShadowColor: 'rgba(255, 215, 0, 0.5)',
    textShadowOffset: { width: 0, height: 0 },
    textShadowRadius: 10,
  },
  messageDivider: {
    width: 120,
    height: 2,
    backgroundColor: 'rgba(0, 212, 255, 0.5)',
    marginVertical: 16,
    borderRadius: 1,
  },
  messageText: {
    fontSize: 18,
    color: '#fff',
    textAlign: 'center',
    lineHeight: 32,
  },
  features: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    width: '100%',
    marginTop: 12,
  },
  featureItem: {
    alignItems: 'center',
  },
  featureIconBox: {
    width: 50,
    height: 50,
    borderRadius: 14,
    backgroundColor: 'rgba(0, 212, 255, 0.1)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
    borderWidth: 1,
    borderColor: 'rgba(0, 212, 255, 0.3)',
  },
  featureIcon: {
    fontSize: 24,
  },
  featureText: {
    fontSize: 12,
    color: '#00d4ff',
    fontWeight: '500',
  },
  // Tech Info Cards
  techInfoRow: {
    flexDirection: 'row',
    width: '100%',
    marginBottom: 16,
    gap: 12,
  },
  techInfoCard: {
    flex: 1,
    backgroundColor: 'rgba(0, 212, 255, 0.08)',
    borderRadius: 16,
    padding: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(0, 212, 255, 0.2)',
  },
  techInfoIcon: {
    fontSize: 24,
    marginBottom: 8,
  },
  techInfoLabel: {
    fontSize: 11,
    color: '#888',
    marginBottom: 4,
  },
  techInfoValue: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#00d4ff',
  },

  // Stats
  statsContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(0, 212, 255, 0.08)',
    borderRadius: 20,
    padding: 20,
    marginBottom: 20,
    width: '100%',
    borderWidth: 1,
    borderColor: 'rgba(0, 212, 255, 0.2)',
  },
  statItem: {
    flex: 1,
    alignItems: 'center',
  },
  statValue: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFD700',
    textShadowColor: 'rgba(255, 215, 0, 0.3)',
    textShadowOffset: { width: 0, height: 0 },
    textShadowRadius: 8,
  },
  statLabel: {
    fontSize: 11,
    color: '#00d4ff',
    marginTop: 6,
    fontWeight: '500',
  },
  statDivider: {
    width: 1,
    height: 40,
    backgroundColor: 'rgba(0, 212, 255, 0.3)',
  },

  // Wiki Button
  wikiButton: {
    width: '100%',
    borderRadius: 16,
    overflow: 'hidden',
    marginBottom: 24,
    shadowColor: '#00d4ff',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.5,
    shadowRadius: 15,
    elevation: 10,
  },
  wikiButtonGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 18,
    paddingHorizontal: 24,
  },
  wikiButtonIcon: {
    fontSize: 24,
    marginRight: 12,
  },
  wikiButtonText: {
    fontSize: 17,
    fontWeight: 'bold',
    color: '#0a0a1a',
    letterSpacing: 0.5,
  },
  wikiButtonArrow: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#0a0a1a',
    marginLeft: 12,
  },

  // Footer
  footer: {
    alignItems: 'center',
    marginTop: 16,
    paddingTop: 16,
    borderTopWidth: 1,
    borderTopColor: 'rgba(0, 212, 255, 0.1)',
  },
  footerText: {
    fontSize: 12,
    color: '#00d4ff',
    fontWeight: '500',
  },
  footerSubText: {
    fontSize: 12,
    color: '#FFD700',
    marginTop: 6,
  },
});
