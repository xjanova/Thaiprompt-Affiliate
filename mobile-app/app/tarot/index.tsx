/**
 * Tarot Home Screen - หน้าหลักดูดวงไพ่ทาโรต์
 * ออกแบบใหม่สำหรับมือถือ พร้อม Animation สวยงาม
 */

import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  StyleSheet,
  Dimensions,
  Animated,
  StatusBar,
  ActivityIndicator,
} from 'react-native';
import { router } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '@/stores/authStore';

const { width } = Dimensions.get('window');

// Mock categories data (จะเปลี่ยนเป็น API ทีหลัง)
const TAROT_CATEGORIES = [
  {
    id: 1,
    slug: 'love-relationships',
    name_th: 'ความรัก & ความสัมพันธ์',
    name_en: 'Love & Relationships',
    description_th: 'เปิดไพ่ดูดวงความรัก คู่ครอง เนื้อคู่',
    icon: '💕',
    color: '#EC4899',
    gradientStart: '#EC4899',
    gradientEnd: '#BE185D',
    price: 0,
    is_free_first: true,
  },
  {
    id: 2,
    slug: 'career-finance',
    name_th: 'การงาน & การเงิน',
    name_en: 'Career & Finance',
    description_th: 'ดูดวงการงาน การเงิน ธุรกิจ',
    icon: '💼',
    color: '#3B82F6',
    gradientStart: '#3B82F6',
    gradientEnd: '#1D4ED8',
    price: 99,
    is_free_first: true,
  },
  {
    id: 3,
    slug: 'personal-growth',
    name_th: 'พัฒนาตนเอง',
    name_en: 'Personal Growth',
    description_th: 'ค้นหาตัวเอง เส้นทางชีวิต',
    icon: '🌟',
    color: '#8B5CF6',
    gradientStart: '#8B5CF6',
    gradientEnd: '#6D28D9',
    price: 79,
    is_free_first: true,
  },
  {
    id: 4,
    slug: 'health-wellness',
    name_th: 'สุขภาพ & ความเป็นอยู่',
    name_en: 'Health & Wellness',
    description_th: 'ดูดวงสุขภาพ พลังงานชีวิต',
    icon: '🍀',
    color: '#10B981',
    gradientStart: '#10B981',
    gradientEnd: '#059669',
    price: 89,
    is_free_first: true,
  },
  {
    id: 5,
    slug: 'general',
    name_th: 'ดูดวงทั่วไป',
    name_en: 'General Reading',
    description_th: 'เปิดไพ่ดูดวงทั่วไป ฟรี!',
    icon: '🔮',
    color: '#F59E0B',
    gradientStart: '#F59E0B',
    gradientEnd: '#D97706',
    price: 0,
    is_free_first: true,
  },
];

// Animated Star Component
const AnimatedStar = ({ delay, style }: { delay: number; style: object }) => {
  const opacity = useRef(new Animated.Value(0)).current;
  const scale = useRef(new Animated.Value(0.5)).current;

  useEffect(() => {
    const animation = Animated.loop(
      Animated.sequence([
        Animated.delay(delay),
        Animated.parallel([
          Animated.timing(opacity, {
            toValue: 1,
            duration: 1000,
            useNativeDriver: true,
          }),
          Animated.timing(scale, {
            toValue: 1,
            duration: 1000,
            useNativeDriver: true,
          }),
        ]),
        Animated.parallel([
          Animated.timing(opacity, {
            toValue: 0.3,
            duration: 1000,
            useNativeDriver: true,
          }),
          Animated.timing(scale, {
            toValue: 0.5,
            duration: 1000,
            useNativeDriver: true,
          }),
        ]),
      ])
    );
    animation.start();
    return () => animation.stop();
  }, [delay, opacity, scale]);

  return (
    <Animated.Text
      style={[
        styles.star,
        style,
        { opacity, transform: [{ scale }] },
      ]}
    >
      ✦
    </Animated.Text>
  );
};

// Category Card Component
const CategoryCard = ({
  category,
  index,
  onPress,
}: {
  category: typeof TAROT_CATEGORIES[0];
  index: number;
  onPress: () => void;
}) => {
  const scaleAnim = useRef(new Animated.Value(0)).current;
  const translateY = useRef(new Animated.Value(50)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.spring(scaleAnim, {
        toValue: 1,
        delay: index * 100,
        useNativeDriver: true,
        tension: 50,
        friction: 7,
      }),
      Animated.timing(translateY, {
        toValue: 0,
        duration: 500,
        delay: index * 100,
        useNativeDriver: true,
      }),
    ]).start();
  }, [index, scaleAnim, translateY]);

  return (
    <Animated.View
      style={[
        styles.cardContainer,
        {
          transform: [{ scale: scaleAnim }, { translateY }],
        },
      ]}
    >
      <Pressable
        onPress={onPress}
        style={({ pressed }) => [
          styles.card,
          pressed && styles.cardPressed,
        ]}
      >
        <LinearGradient
          colors={[category.gradientStart, category.gradientEnd]}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.cardGradient}
        >
          {/* Decorative Circle */}
          <View style={styles.decorativeCircle} />

          {/* Icon */}
          <Text style={styles.cardIcon}>{category.icon}</Text>

          {/* Title */}
          <Text style={styles.cardTitle}>{category.name_th}</Text>

          {/* Description */}
          <Text style={styles.cardDescription} numberOfLines={2}>
            {category.description_th}
          </Text>

          {/* Price Badge */}
          <View style={styles.priceBadge}>
            <Text style={styles.priceText}>
              {category.price === 0 ? 'ฟรี' : `฿${category.price}`}
            </Text>
          </View>

          {/* Arrow */}
          <View style={styles.arrowContainer}>
            <Ionicons name="arrow-forward" size={20} color="rgba(255,255,255,0.8)" />
          </View>
        </LinearGradient>
      </Pressable>
    </Animated.View>
  );
};

export default function TarotHomeScreen() {
  const { isAuthenticated } = useAuthStore();
  const [loading, setLoading] = useState(false);
  const headerOpacity = useRef(new Animated.Value(0)).current;
  const headerTranslateY = useRef(new Animated.Value(-30)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(headerOpacity, {
        toValue: 1,
        duration: 800,
        useNativeDriver: true,
      }),
      Animated.spring(headerTranslateY, {
        toValue: 0,
        useNativeDriver: true,
        tension: 50,
        friction: 8,
      }),
    ]).start();
  }, [headerOpacity, headerTranslateY]);

  const handleCategoryPress = (category: typeof TAROT_CATEGORIES[0]) => {
    router.push({
      pathname: '/tarot/select-cards',
      params: {
        categoryId: category.id,
        categorySlug: category.slug,
        categoryName: category.name_th,
      },
    });
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" />

      {/* Background */}
      <LinearGradient
        colors={['#0F0F23', '#1A1A2E', '#16213E']}
        style={StyleSheet.absoluteFill}
      />

      {/* Animated Stars */}
      <AnimatedStar delay={0} style={{ top: 60, left: 30 }} />
      <AnimatedStar delay={500} style={{ top: 120, right: 40 }} />
      <AnimatedStar delay={1000} style={{ top: 200, left: 60 }} />
      <AnimatedStar delay={300} style={{ top: 80, right: 80 }} />
      <AnimatedStar delay={700} style={{ top: 160, left: 100 }} />

      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* Header */}
        <Animated.View
          style={[
            styles.header,
            {
              opacity: headerOpacity,
              transform: [{ translateY: headerTranslateY }],
            },
          ]}
        >
          {/* Back Button */}
          <Pressable
            style={styles.backButton}
            onPress={() => router.back()}
          >
            <Ionicons name="arrow-back" size={24} color="#fff" />
          </Pressable>

          {/* Title */}
          <View style={styles.titleContainer}>
            <Text style={styles.titleIcon}>🔮</Text>
            <Text style={styles.title}>ดูดวงไพ่ทาโรต์</Text>
            <Text style={styles.subtitle}>
              เปิดไพ่เพื่อดูอนาคตของคุณ
            </Text>
          </View>

          {/* Mystical Orb */}
          <View style={styles.orbContainer}>
            <LinearGradient
              colors={['#8B5CF6', '#EC4899', '#F59E0B']}
              style={styles.orb}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
            />
            <View style={styles.orbGlow} />
          </View>
        </Animated.View>

        {/* Categories Section */}
        <View style={styles.categoriesSection}>
          <Text style={styles.sectionTitle}>เลือกหมวดหมู่</Text>
          <Text style={styles.sectionSubtitle}>
            แต่ละหมวดจะให้ความหมายที่แตกต่างกัน
          </Text>

          {TAROT_CATEGORIES.map((category, index) => (
            <CategoryCard
              key={category.id}
              category={category}
              index={index}
              onPress={() => handleCategoryPress(category)}
            />
          ))}
        </View>

        {/* Info Section */}
        <View style={styles.infoSection}>
          <View style={styles.infoCard}>
            <Text style={styles.infoIcon}>✨</Text>
            <Text style={styles.infoTitle}>วิธีใช้งาน</Text>
            <Text style={styles.infoText}>
              1. เลือกหมวดหมู่ที่ต้องการดู{'\n'}
              2. เลือกจำนวนไพ่ที่ต้องการเปิด{'\n'}
              3. สัมผัสไพ่เพื่อเลือก{'\n'}
              4. ดูความหมายและคำแนะนำ
            </Text>
          </View>
        </View>

        {/* Footer */}
        <View style={styles.footer}>
          <Text style={styles.footerText}>
            🌙 ผลการทำนายเป็นเพียงแนวทางเท่านั้น
          </Text>
        </View>
      </ScrollView>

      {/* Loading Overlay */}
      {loading && (
        <View style={styles.loadingOverlay}>
          <ActivityIndicator size="large" color="#8B5CF6" />
          <Text style={styles.loadingText}>กำลังโหลด...</Text>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    paddingBottom: 40,
  },
  star: {
    position: 'absolute',
    fontSize: 16,
    color: '#FFD700',
  },
  header: {
    paddingTop: 60,
    paddingHorizontal: 20,
    paddingBottom: 30,
    alignItems: 'center',
  },
  backButton: {
    position: 'absolute',
    top: 60,
    left: 20,
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.1)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  titleContainer: {
    alignItems: 'center',
    marginTop: 20,
  },
  titleIcon: {
    fontSize: 48,
    marginBottom: 12,
  },
  title: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#FFFFFF',
    textShadowColor: 'rgba(139, 92, 246, 0.5)',
    textShadowOffset: { width: 0, height: 2 },
    textShadowRadius: 10,
  },
  subtitle: {
    fontSize: 16,
    color: 'rgba(255,255,255,0.7)',
    marginTop: 8,
  },
  orbContainer: {
    marginTop: 20,
    position: 'relative',
  },
  orb: {
    width: 80,
    height: 80,
    borderRadius: 40,
  },
  orbGlow: {
    position: 'absolute',
    top: -10,
    left: -10,
    right: -10,
    bottom: -10,
    borderRadius: 50,
    backgroundColor: 'rgba(139, 92, 246, 0.3)',
  },
  categoriesSection: {
    paddingHorizontal: 20,
    marginTop: 20,
  },
  sectionTitle: {
    fontSize: 22,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  sectionSubtitle: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.6)',
    marginBottom: 20,
  },
  cardContainer: {
    marginBottom: 16,
  },
  card: {
    borderRadius: 20,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 8,
  },
  cardPressed: {
    opacity: 0.9,
    transform: [{ scale: 0.98 }],
  },
  cardGradient: {
    padding: 20,
    minHeight: 140,
    position: 'relative',
    overflow: 'hidden',
  },
  decorativeCircle: {
    position: 'absolute',
    top: -30,
    right: -30,
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: 'rgba(255,255,255,0.1)',
  },
  cardIcon: {
    fontSize: 36,
    marginBottom: 8,
  },
  cardTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  cardDescription: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
    marginBottom: 8,
    maxWidth: '80%',
  },
  priceBadge: {
    position: 'absolute',
    top: 16,
    right: 16,
    backgroundColor: 'rgba(255,255,255,0.2)',
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 12,
  },
  priceText: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  arrowContainer: {
    position: 'absolute',
    bottom: 16,
    right: 16,
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.2)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  infoSection: {
    paddingHorizontal: 20,
    marginTop: 30,
  },
  infoCard: {
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 16,
    padding: 20,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  infoIcon: {
    fontSize: 32,
    marginBottom: 12,
  },
  infoTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 12,
  },
  infoText: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.7)',
    lineHeight: 24,
  },
  footer: {
    paddingHorizontal: 20,
    paddingVertical: 30,
    alignItems: 'center',
  },
  footerText: {
    fontSize: 12,
    color: 'rgba(255,255,255,0.5)',
    textAlign: 'center',
  },
  loadingOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(15, 15, 35, 0.9)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 16,
    fontSize: 16,
    color: '#FFFFFF',
  },
});
