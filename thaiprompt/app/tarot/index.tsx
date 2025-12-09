/**
 * Tarot Home Screen - หน้าหลักดูดวงไพ่ทาโรต์
 * พร้อม 78 ไพ่ครบ, 5 หมวด, 5 โหมด, ระบบตรวจสอบ Wallet
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
  Alert,
  Modal,
} from 'react-native';
import { router } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '@/stores/authStore';
import { getWallet } from '@/services/api';
import {
  TAROT_CATEGORIES,
  SPREAD_TYPES,
  TarotCategory,
  SpreadType,
} from '@/data/tarotData';

const { width } = Dimensions.get('window');

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
  category: TarotCategory;
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

// Spread Type Card Component
const SpreadCard = ({
  spread,
  isSelected,
  onPress,
}: {
  spread: SpreadType;
  isSelected: boolean;
  onPress: () => void;
}) => (
  <Pressable
    onPress={onPress}
    style={[
      styles.spreadCard,
      isSelected && { borderColor: spread.color, borderWidth: 2 },
    ]}
  >
    <Text style={styles.spreadIcon}>{spread.icon}</Text>
    <Text style={styles.spreadName}>{spread.name_th}</Text>
    <Text style={styles.spreadCount}>{spread.card_count} ใบ</Text>
    {isSelected && (
      <View style={[styles.spreadSelected, { backgroundColor: spread.color }]}>
        <Ionicons name="checkmark" size={14} color="#fff" />
      </View>
    )}
  </Pressable>
);

export default function TarotHomeScreen() {
  const { isAuthenticated, user } = useAuthStore();
  const [loading, setLoading] = useState(false);
  const [walletBalance, setWalletBalance] = useState<number>(0);
  const [showSpreadModal, setShowSpreadModal] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState<TarotCategory | null>(null);
  const [selectedSpread, setSelectedSpread] = useState<SpreadType>(SPREAD_TYPES[1]); // Default: Past, Present, Future
  const [showPaymentModal, setShowPaymentModal] = useState(false);

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

    // โหลดยอดเงินใน Wallet
    loadWalletBalance();
  }, [headerOpacity, headerTranslateY]);

  const loadWalletBalance = async () => {
    if (isAuthenticated) {
      try {
        const response = await getWallet();
        if (response?.success && response.data) {
          setWalletBalance(response.data.availableBalance || 0);
        }
      } catch (error) {
        console.error('Error loading wallet:', error);
      }
    }
  };

  const handleCategoryPress = (category: TarotCategory) => {
    if (!isAuthenticated) {
      Alert.alert(
        'กรุณาเข้าสู่ระบบ',
        'คุณต้องเข้าสู่ระบบเพื่อใช้บริการดูดวงไพ่ทาโรต์',
        [
          { text: 'ยกเลิก', style: 'cancel' },
          { text: 'เข้าสู่ระบบ', onPress: () => router.push('/auth/login') },
        ]
      );
      return;
    }

    setSelectedCategory(category);
    setShowSpreadModal(true);
  };

  const handleSpreadSelect = (spread: SpreadType) => {
    setSelectedSpread(spread);
  };

  const handleConfirmSpread = () => {
    setShowSpreadModal(false);

    if (!selectedCategory) return;

    // ถ้าเป็นหมวดฟรี ไปหน้าเลือกไพ่เลย
    if (selectedCategory.price === 0) {
      navigateToSelectCards();
      return;
    }

    // ถ้าเป็นหมวดเสียเงิน ตรวจสอบยอดเงิน
    if (walletBalance < selectedCategory.price) {
      Alert.alert(
        'ยอดเงินไม่เพียงพอ',
        `ยอดเงินในกระเป๋า: ฿${walletBalance.toLocaleString()}\nค่าบริการ: ฿${selectedCategory.price}\n\nกรุณาเติมเงินก่อนใช้บริการ`,
        [
          { text: 'ยกเลิก', style: 'cancel' },
          { text: 'เติมเงิน', onPress: () => router.push('/(tabs)/wallet') },
        ]
      );
      return;
    }

    // มีเงินพอ - ถามยืนยันก่อนหักเงิน
    setShowPaymentModal(true);
  };

  const handleConfirmPayment = () => {
    setShowPaymentModal(false);
    // หักเงินและไปหน้าเลือกไพ่
    navigateToSelectCards();
  };

  const navigateToSelectCards = () => {
    if (!selectedCategory) return;

    router.push({
      pathname: '/tarot/select-cards',
      params: {
        categoryId: selectedCategory.id.toString(),
        categorySlug: selectedCategory.slug,
        categoryName: selectedCategory.name_th,
        categoryPrice: selectedCategory.price.toString(),
        spreadId: selectedSpread.id.toString(),
        spreadSlug: selectedSpread.slug,
        spreadName: selectedSpread.name_th,
        cardCount: selectedSpread.card_count.toString(),
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
              ไพ่ 78 ใบ • 5 หมวดหมู่ • 5 โหมดการเปิด
            </Text>
          </View>

          {/* Wallet Badge (if authenticated) */}
          {isAuthenticated && (
            <View style={styles.walletBadge}>
              <Ionicons name="wallet-outline" size={16} color="#10B981" />
              <Text style={styles.walletAmount}>฿{walletBalance.toLocaleString()}</Text>
            </View>
          )}

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
              2. เลือกโหมดการเปิดไพ่ (1-10 ใบ){'\n'}
              3. สัมผัสไพ่จาก 78 ใบเพื่อเลือก{'\n'}
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

      {/* Spread Selection Modal */}
      <Modal
        visible={showSpreadModal}
        transparent
        animationType="slide"
        onRequestClose={() => setShowSpreadModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>เลือกโหมดการเปิดไพ่</Text>
              <Pressable
                style={styles.modalClose}
                onPress={() => setShowSpreadModal(false)}
              >
                <Ionicons name="close" size={24} color="#fff" />
              </Pressable>
            </View>

            {/* Selected Category Info */}
            {selectedCategory && (
              <View style={styles.selectedCategoryInfo}>
                <Text style={styles.selectedCategoryIcon}>
                  {selectedCategory.icon}
                </Text>
                <Text style={styles.selectedCategoryName}>
                  {selectedCategory.name_th}
                </Text>
                <View style={[
                  styles.categoryPriceBadge,
                  { backgroundColor: selectedCategory.price === 0 ? '#10B981' : '#F59E0B' }
                ]}>
                  <Text style={styles.categoryPriceText}>
                    {selectedCategory.price === 0 ? 'ฟรี' : `฿${selectedCategory.price}`}
                  </Text>
                </View>
              </View>
            )}

            {/* Spread Types */}
            <ScrollView style={styles.spreadList}>
              {SPREAD_TYPES.map((spread) => (
                <SpreadCard
                  key={spread.id}
                  spread={spread}
                  isSelected={selectedSpread.id === spread.id}
                  onPress={() => handleSpreadSelect(spread)}
                />
              ))}
            </ScrollView>

            {/* Selected Spread Details */}
            <View style={styles.spreadDetails}>
              <Text style={styles.spreadDetailsTitle}>
                {selectedSpread.name_th} - {selectedSpread.card_count} ใบ
              </Text>
              <Text style={styles.spreadDetailsDesc}>
                {selectedSpread.description_th}
              </Text>
              <View style={styles.positionsPreview}>
                {selectedSpread.positions.map((pos, idx) => (
                  <View key={idx} style={styles.positionBadge}>
                    <Text style={styles.positionText}>{pos.name_th}</Text>
                  </View>
                ))}
              </View>
            </View>

            {/* Confirm Button */}
            <Pressable style={styles.confirmButton} onPress={handleConfirmSpread}>
              <LinearGradient
                colors={['#8B5CF6', '#EC4899']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.confirmGradient}
              >
                <Text style={styles.confirmText}>🔮 เริ่มเปิดไพ่</Text>
                <Ionicons name="arrow-forward" size={20} color="#fff" />
              </LinearGradient>
            </Pressable>
          </View>
        </View>
      </Modal>

      {/* Payment Confirmation Modal */}
      <Modal
        visible={showPaymentModal}
        transparent
        animationType="fade"
        onRequestClose={() => setShowPaymentModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.paymentModal}>
            <View style={styles.paymentIcon}>
              <Ionicons name="wallet" size={48} color="#F59E0B" />
            </View>

            <Text style={styles.paymentTitle}>ยืนยันการชำระเงิน</Text>

            {selectedCategory && (
              <>
                <Text style={styles.paymentCategory}>
                  {selectedCategory.icon} {selectedCategory.name_th}
                </Text>
                <Text style={styles.paymentSpread}>
                  โหมด: {selectedSpread.name_th} ({selectedSpread.card_count} ใบ)
                </Text>

                <View style={styles.paymentDetails}>
                  <View style={styles.paymentRow}>
                    <Text style={styles.paymentLabel}>ค่าบริการ</Text>
                    <Text style={styles.paymentValue}>฿{selectedCategory.price}</Text>
                  </View>
                  <View style={styles.paymentRow}>
                    <Text style={styles.paymentLabel}>ยอดเงินในกระเป๋า</Text>
                    <Text style={styles.paymentBalance}>฿{walletBalance.toLocaleString()}</Text>
                  </View>
                  <View style={[styles.paymentRow, styles.paymentRowTotal]}>
                    <Text style={styles.paymentLabelBold}>ยอดคงเหลือหลังหัก</Text>
                    <Text style={styles.paymentValueBold}>
                      ฿{(walletBalance - selectedCategory.price).toLocaleString()}
                    </Text>
                  </View>
                </View>
              </>
            )}

            <View style={styles.paymentButtons}>
              <Pressable
                style={styles.cancelButton}
                onPress={() => setShowPaymentModal(false)}
              >
                <Text style={styles.cancelButtonText}>ยกเลิก</Text>
              </Pressable>
              <Pressable
                style={styles.payButton}
                onPress={handleConfirmPayment}
              >
                <LinearGradient
                  colors={['#10B981', '#059669']}
                  style={styles.payButtonGradient}
                >
                  <Ionicons name="checkmark-circle" size={20} color="#fff" />
                  <Text style={styles.payButtonText}>ยืนยันหักเงิน</Text>
                </LinearGradient>
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>

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
  walletBadge: {
    position: 'absolute',
    top: 60,
    right: 20,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(16, 185, 129, 0.2)',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 16,
    gap: 4,
  },
  walletAmount: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#10B981',
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
    fontSize: 14,
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
  // Modal Styles
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.8)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#1A1A2E',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    paddingTop: 20,
    paddingBottom: 40,
    maxHeight: '85%',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    marginBottom: 16,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  modalClose: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.1)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  selectedCategoryInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 12,
    backgroundColor: 'rgba(255,255,255,0.05)',
    marginHorizontal: 20,
    borderRadius: 12,
    marginBottom: 16,
  },
  selectedCategoryIcon: {
    fontSize: 24,
    marginRight: 12,
  },
  selectedCategoryName: {
    flex: 1,
    fontSize: 16,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  categoryPriceBadge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 10,
  },
  categoryPriceText: {
    fontSize: 12,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  spreadList: {
    paddingHorizontal: 20,
    maxHeight: 200,
  },
  spreadCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 12,
    padding: 16,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: 'transparent',
  },
  spreadIcon: {
    fontSize: 24,
    marginRight: 12,
  },
  spreadName: {
    flex: 1,
    fontSize: 16,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  spreadCount: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.6)',
    marginRight: 10,
  },
  spreadSelected: {
    width: 24,
    height: 24,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
  },
  spreadDetails: {
    paddingHorizontal: 20,
    paddingVertical: 16,
    backgroundColor: 'rgba(139, 92, 246, 0.1)',
    marginHorizontal: 20,
    borderRadius: 12,
    marginTop: 10,
  },
  spreadDetailsTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  spreadDetailsDesc: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.7)',
    marginBottom: 12,
  },
  positionsPreview: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  positionBadge: {
    backgroundColor: 'rgba(255,255,255,0.1)',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
  },
  positionText: {
    fontSize: 12,
    color: 'rgba(255,255,255,0.8)',
  },
  confirmButton: {
    marginHorizontal: 20,
    marginTop: 20,
    borderRadius: 16,
    overflow: 'hidden',
  },
  confirmGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 16,
    gap: 10,
  },
  confirmText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  // Payment Modal
  paymentModal: {
    backgroundColor: '#1A1A2E',
    marginHorizontal: 20,
    borderRadius: 24,
    padding: 24,
    alignItems: 'center',
    marginTop: 'auto',
    marginBottom: 'auto',
  },
  paymentIcon: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: 'rgba(245, 158, 11, 0.2)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  paymentTitle: {
    fontSize: 22,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 8,
  },
  paymentCategory: {
    fontSize: 18,
    color: '#FFFFFF',
    marginBottom: 4,
  },
  paymentSpread: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.7)',
    marginBottom: 20,
  },
  paymentDetails: {
    width: '100%',
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 12,
    padding: 16,
    marginBottom: 24,
  },
  paymentRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 8,
  },
  paymentRowTotal: {
    borderTopWidth: 1,
    borderTopColor: 'rgba(255,255,255,0.1)',
    marginTop: 8,
    paddingTop: 16,
  },
  paymentLabel: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.7)',
  },
  paymentLabelBold: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  paymentValue: {
    fontSize: 14,
    color: '#F59E0B',
    fontWeight: '600',
  },
  paymentBalance: {
    fontSize: 14,
    color: '#10B981',
    fontWeight: '600',
  },
  paymentValueBold: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#10B981',
  },
  paymentButtons: {
    flexDirection: 'row',
    gap: 12,
    width: '100%',
  },
  cancelButton: {
    flex: 1,
    paddingVertical: 14,
    borderRadius: 12,
    backgroundColor: 'rgba(255,255,255,0.1)',
    alignItems: 'center',
  },
  cancelButtonText: {
    fontSize: 16,
    color: '#FFFFFF',
  },
  payButton: {
    flex: 1,
    borderRadius: 12,
    overflow: 'hidden',
  },
  payButtonGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 14,
    gap: 8,
  },
  payButtonText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
});
