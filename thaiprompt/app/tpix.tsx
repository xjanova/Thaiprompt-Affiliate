/**
 * TPIX Token - หน้า Coming Soon สำหรับเหรียญ TPIX
 *
 * เหรียญแห่งอนาคต - เหรียญของคนไทย เพื่อคนไทย
 *
 * Features:
 * - 3D Rotating Coin Animation
 * - Particle Effects (Stars/Coins)
 * - Glowing Text Effects
 * - Gradient Background
 * - Premium UI Design
 */

import React, { useEffect, useRef, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Dimensions,
  Pressable,
  Animated,
  Easing,
  Linking,
  StatusBar,
  ScrollView,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { BlurView } from 'expo-blur';
import { router } from 'expo-router';
import { useAppStore } from '@/stores/appStore';

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
// 3D Rotating Coin Component - เหรียญ 3D หมุน
// =====================================================
const RotatingCoin = () => {
  const rotateY = useRef(new Animated.Value(0)).current;
  const glow = useRef(new Animated.Value(0)).current;
  const float = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // หมุนเหรียญ
    Animated.loop(
      Animated.timing(rotateY, {
        toValue: 1,
        duration: 4000,
        easing: Easing.linear,
        useNativeDriver: true,
      })
    ).start();

    // เอฟเฟคเรืองแสง
    Animated.loop(
      Animated.sequence([
        Animated.timing(glow, {
          toValue: 1,
          duration: 1500,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
        Animated.timing(glow, {
          toValue: 0,
          duration: 1500,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
      ])
    ).start();

    // ลอยขึ้นลง
    Animated.loop(
      Animated.sequence([
        Animated.timing(float, {
          toValue: -15,
          duration: 2000,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
        Animated.timing(float, {
          toValue: 0,
          duration: 2000,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
      ])
    ).start();
  }, []);

  const rotateInterpolate = rotateY.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg'],
  });

  const glowOpacity = glow.interpolate({
    inputRange: [0, 1],
    outputRange: [0.3, 0.8],
  });

  const glowScale = glow.interpolate({
    inputRange: [0, 1],
    outputRange: [1, 1.3],
  });

  return (
    <Animated.View style={[styles.coinContainer, { transform: [{ translateY: float }] }]}>
      {/* Outer Glow */}
      <Animated.View
        style={[
          styles.coinGlow,
          {
            opacity: glowOpacity,
            transform: [{ scale: glowScale }],
          },
        ]}
      />

      {/* Inner Glow */}
      <Animated.View
        style={[
          styles.coinInnerGlow,
          {
            opacity: glow.interpolate({
              inputRange: [0, 1],
              outputRange: [0.5, 1],
            }),
          },
        ]}
      />

      {/* Coin */}
      <Animated.View
        style={[
          styles.coin,
          {
            transform: [{ rotateY: rotateInterpolate }],
          },
        ]}
      >
        <LinearGradient
          colors={['#FFD700', '#FFA500', '#FF8C00', '#FFD700']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.coinGradient}
        >
          {/* Coin Face */}
          <View style={styles.coinFace}>
            <Text style={styles.coinText}>TPIX</Text>
            <Text style={styles.coinSubText}>🇹🇭</Text>
          </View>

          {/* Coin Shine */}
          <View style={styles.coinShine} />
        </LinearGradient>
      </Animated.View>

      {/* Ring Effects */}
      <Animated.View style={[styles.coinRing, { opacity: glowOpacity }]} />
      <Animated.View style={[styles.coinRing2, { opacity: glow.interpolate({ inputRange: [0, 1], outputRange: [0.2, 0.5] }) }]} />
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
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';
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
    Linking.openURL(TPIX_WIKI_URL);
  };

  const handleGoBack = () => {
    router.back();
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0a0a1a" />

      {/* Background Gradient */}
      <LinearGradient
        colors={['#0a0a1a', '#1a1a3a', '#0f0f2f', '#0a0a1a']}
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

          {/* 3D Coin */}
          <RotatingCoin />

          {/* Title */}
          <View style={styles.titleContainer}>
            <Text style={styles.title}>TPIX TOKEN</Text>
            <Text style={styles.subtitle}>เหรียญแห่งอนาคต</Text>
          </View>

          {/* Main Message - Glass Card */}
          <BlurView intensity={20} tint="dark" style={styles.messageCard}>
            <LinearGradient
              colors={['rgba(255,215,0,0.1)', 'rgba(255,140,0,0.05)']}
              style={styles.messageGradient}
            >
              <Text style={styles.messageTitle}>
                เตรียมพบกับเราเร็วๆ นี้
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
                  <Text style={styles.featureIcon}>🔐</Text>
                  <Text style={styles.featureText}>ปลอดภัย</Text>
                </View>
                <View style={styles.featureItem}>
                  <Text style={styles.featureIcon}>⚡</Text>
                  <Text style={styles.featureText}>รวดเร็ว</Text>
                </View>
                <View style={styles.featureItem}>
                  <Text style={styles.featureIcon}>🌐</Text>
                  <Text style={styles.featureText}>ไร้พรมแดน</Text>
                </View>
              </View>
            </LinearGradient>
          </BlurView>

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

          {/* Wiki Button */}
          <Pressable onPress={handleOpenWiki} style={styles.wikiButton}>
            <LinearGradient
              colors={['#FFD700', '#FFA500', '#FF8C00']}
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

  // Coin
  coinContainer: {
    width: 180,
    height: 180,
    alignItems: 'center',
    justifyContent: 'center',
    marginVertical: 30,
  },
  coinGlow: {
    position: 'absolute',
    width: 200,
    height: 200,
    borderRadius: 100,
    backgroundColor: '#FFD700',
    shadowColor: '#FFD700',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 1,
    shadowRadius: 40,
  },
  coinInnerGlow: {
    position: 'absolute',
    width: 160,
    height: 160,
    borderRadius: 80,
    backgroundColor: 'rgba(255, 200, 0, 0.3)',
  },
  coin: {
    width: 140,
    height: 140,
    borderRadius: 70,
    overflow: 'hidden',
    shadowColor: '#FFD700',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.8,
    shadowRadius: 20,
    elevation: 20,
  },
  coinGradient: {
    width: '100%',
    height: '100%',
    alignItems: 'center',
    justifyContent: 'center',
  },
  coinFace: {
    alignItems: 'center',
  },
  coinText: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#8B4513',
    textShadowColor: 'rgba(0, 0, 0, 0.3)',
    textShadowOffset: { width: 1, height: 1 },
    textShadowRadius: 2,
  },
  coinSubText: {
    fontSize: 24,
    marginTop: 4,
  },
  coinShine: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: '50%',
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    borderTopLeftRadius: 70,
    borderTopRightRadius: 70,
  },
  coinRing: {
    position: 'absolute',
    width: 170,
    height: 170,
    borderRadius: 85,
    borderWidth: 2,
    borderColor: '#FFD700',
  },
  coinRing2: {
    position: 'absolute',
    width: 200,
    height: 200,
    borderRadius: 100,
    borderWidth: 1,
    borderColor: '#FFA500',
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
    marginBottom: 24,
  },
  messageGradient: {
    padding: 24,
    alignItems: 'center',
  },
  messageTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFD700',
    textAlign: 'center',
    marginBottom: 16,
  },
  messageDivider: {
    width: 100,
    height: 2,
    backgroundColor: 'rgba(255, 215, 0, 0.3)',
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
    marginTop: 8,
  },
  featureItem: {
    alignItems: 'center',
  },
  featureIcon: {
    fontSize: 28,
    marginBottom: 4,
  },
  featureText: {
    fontSize: 12,
    color: '#ccc',
  },

  // Stats
  statsContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(255, 215, 0, 0.1)',
    borderRadius: 16,
    padding: 16,
    marginBottom: 24,
    width: '100%',
  },
  statItem: {
    flex: 1,
    alignItems: 'center',
  },
  statValue: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFD700',
  },
  statLabel: {
    fontSize: 12,
    color: '#888',
    marginTop: 4,
  },
  statDivider: {
    width: 1,
    height: 30,
    backgroundColor: 'rgba(255, 215, 0, 0.3)',
  },

  // Wiki Button
  wikiButton: {
    width: '100%',
    borderRadius: 16,
    overflow: 'hidden',
    marginBottom: 30,
    shadowColor: '#FFD700',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.4,
    shadowRadius: 12,
    elevation: 8,
  },
  wikiButtonGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 16,
    paddingHorizontal: 24,
  },
  wikiButtonIcon: {
    fontSize: 24,
    marginRight: 10,
  },
  wikiButtonText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1a1a1a',
  },
  wikiButtonArrow: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1a1a1a',
    marginLeft: 10,
  },

  // Footer
  footer: {
    alignItems: 'center',
    marginTop: 10,
  },
  footerText: {
    fontSize: 12,
    color: '#666',
  },
  footerSubText: {
    fontSize: 12,
    color: '#888',
    marginTop: 4,
  },
});
