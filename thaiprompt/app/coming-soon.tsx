/**
 * หน้า Coming Soon - แสดง Features ที่จะมาเร็วๆ นี้
 * ใช้ StyleSheet แทน NativeWind
 */

import React, { useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  Alert,
  StyleSheet,
  StatusBar,
  Dimensions,
} from 'react-native';
import { router } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';

const { width: SCREEN_WIDTH } = Dimensions.get('window');
const CARD_WIDTH = (SCREEN_WIDTH - 48) / 2;

interface Feature {
  id: string;
  icon: string;
  title: string;
  description: string;
  category: string;
  gradientColors: [string, string];
  status: 'coming_soon' | 'in_development' | 'planned';
}

const FEATURES: Feature[] = [
  // E-commerce & Shop
  {
    id: 'shop',
    icon: '🏪',
    title: 'ตลาดออนไลน์',
    description: 'ช้อปปิ้งสินค้า, สั่งซื้อและติดตามออเดอร์',
    category: 'E-commerce',
    gradientColors: ['#F59E0B', '#D97706'],
    status: 'in_development',
  },
  {
    id: 'cart',
    icon: '🛒',
    title: 'ตะกร้าสินค้า',
    description: 'จัดการตะกร้าและชำระเงินออนไลน์',
    category: 'E-commerce',
    gradientColors: ['#EF4444', '#DC2626'],
    status: 'in_development',
  },
  // Hotel
  {
    id: 'hotel',
    icon: '🏨',
    title: 'จองโรงแรม',
    description: 'ค้นหาและจองที่พักทั่วไทย',
    category: 'Booking',
    gradientColors: ['#8B5CF6', '#7C3AED'],
    status: 'coming_soon',
  },
  // POS
  {
    id: 'pos',
    icon: '💰',
    title: 'ระบบ POS',
    description: 'จุดขายสำหรับร้านค้าปลีก',
    category: 'Business',
    gradientColors: ['#10B981', '#059669'],
    status: 'coming_soon',
  },
  // Gaming
  {
    id: 'games',
    icon: '🎮',
    title: 'เกมและรางวัล',
    description: 'เล่นเกม, ภารกิจ, รับรางวัล',
    category: 'Gaming',
    gradientColors: ['#EC4899', '#DB2777'],
    status: 'in_development',
  },
  {
    id: 'snake',
    icon: '🐍',
    title: 'เกมงูสายรวย',
    description: 'เล่นงูเก็บเหรียญแข่งกับเพื่อน',
    category: 'Gaming',
    gradientColors: ['#22C55E', '#16A34A'],
    status: 'coming_soon',
  },
  // Crypto & Blockchain
  {
    id: 'crypto',
    icon: '🪙',
    title: 'กระเป๋าคริปโต',
    description: 'ซื้อขายและเก็บ Cryptocurrency',
    category: 'Crypto',
    gradientColors: ['#F59E0B', '#B45309'],
    status: 'coming_soon',
  },
  {
    id: 'tpix',
    icon: '💎',
    title: 'TPIX Token',
    description: 'โทเค็น TPIX สำหรับ Ecosystem',
    category: 'Crypto',
    gradientColors: ['#3B82F6', '#1D4ED8'],
    status: 'planned',
  },
  {
    id: 'dex',
    icon: '🔀',
    title: 'แลกเปลี่ยน DEX',
    description: 'แลกเปลี่ยนแบบกระจายศูนย์',
    category: 'Crypto',
    gradientColors: ['#8B5CF6', '#6D28D9'],
    status: 'planned',
  },
  {
    id: 'staking',
    icon: '🏆',
    title: 'Staking',
    description: 'ล็อคเงินรับผลตอบแทน',
    category: 'Crypto',
    gradientColors: ['#F97316', '#EA580C'],
    status: 'planned',
  },
  // Trading Bot
  {
    id: 'trading_bot',
    icon: '🤖',
    title: 'Trading Bot',
    description: 'บอทเทรดอัตโนมัติ',
    category: 'Trading',
    gradientColors: ['#06B6D4', '#0891B2'],
    status: 'coming_soon',
  },
  {
    id: 'signals',
    icon: '📈',
    title: 'สัญญาณการเทรด',
    description: 'รับสัญญาณซื้อขาย',
    category: 'Trading',
    gradientColors: ['#14B8A6', '#0D9488'],
    status: 'planned',
  },
  // AI Bot
  {
    id: 'ai_bot',
    icon: '🤖',
    title: 'AI Bot',
    description: 'เช่าและจัดการ AI Bot',
    category: 'AI',
    gradientColors: ['#EC4899', '#BE185D'],
    status: 'coming_soon',
  },
  {
    id: 'line_bot',
    icon: '💬',
    title: 'LINE Bot AI',
    description: 'บอทตอบแชท LINE อัตโนมัติ',
    category: 'AI',
    gradientColors: ['#22C55E', '#15803D'],
    status: 'in_development',
  },
  {
    id: 'content_gen',
    icon: '✍️',
    title: 'AI Content',
    description: 'สร้างเนื้อหาด้วย AI',
    category: 'AI',
    gradientColors: ['#8B5CF6', '#6D28D9'],
    status: 'planned',
  },
  // Forum & Community
  {
    id: 'forum',
    icon: '💬',
    title: 'ฟอรั่ม',
    description: 'ชุมชนพูดคุยและแชร์ความรู้',
    category: 'Community',
    gradientColors: ['#3B82F6', '#2563EB'],
    status: 'coming_soon',
  },
  // NFC Card
  {
    id: 'nfc',
    icon: '💳',
    title: 'NFC Card',
    description: 'บัตรแตะจ่ายและเติมเงิน',
    category: 'Payment',
    gradientColors: ['#6366F1', '#4F46E5'],
    status: 'planned',
  },
  // Investment
  {
    id: 'invest',
    icon: '💼',
    title: 'แผนการลงทุน',
    description: 'ลงทุนและติดตามผลตอบแทน',
    category: 'Investment',
    gradientColors: ['#10B981', '#047857'],
    status: 'coming_soon',
  },
  // Seller
  {
    id: 'seller',
    icon: '🏪',
    title: 'Seller Dashboard',
    description: 'จัดการร้านค้าและสินค้า',
    category: 'Business',
    gradientColors: ['#F59E0B', '#D97706'],
    status: 'coming_soon',
  },
  // Software Sales
  {
    id: 'software',
    icon: '💻',
    title: 'ซอฟต์แวร์',
    description: 'ซื้อและดาวน์โหลดซอฟต์แวร์',
    category: 'Business',
    gradientColors: ['#06B6D4', '#0284C7'],
    status: 'planned',
  },
  // QR Scanner
  {
    id: 'qr',
    icon: '📱',
    title: 'สแกน QR',
    description: 'สแกน QR Code และบาร์โค้ด',
    category: 'Utility',
    gradientColors: ['#64748B', '#475569'],
    status: 'in_development',
  },
  // Food Passport
  {
    id: 'food_passport',
    icon: '🍜',
    title: 'Food Passport',
    description: 'ตรวจสอบแหล่งที่มาของอาหาร',
    category: 'Supply Chain',
    gradientColors: ['#22C55E', '#15803D'],
    status: 'planned',
  },
  // Academy
  {
    id: 'academy',
    icon: '🎓',
    title: 'Academy',
    description: 'หลักสูตรและการเรียนรู้',
    category: 'Education',
    gradientColors: ['#14B8A6', '#0D9488'],
    status: 'coming_soon',
  },
];

const CATEGORIES = [
  { id: 'all', label: 'ทั้งหมด' },
  { id: 'E-commerce', label: 'E-commerce' },
  { id: 'Crypto', label: 'Crypto' },
  { id: 'Trading', label: 'Trading' },
  { id: 'AI', label: 'AI' },
  { id: 'Gaming', label: 'Gaming' },
  { id: 'Business', label: 'Business' },
];

export default function ComingSoonScreen() {
  const [selectedCategory, setSelectedCategory] = useState('all');

  const filteredFeatures = selectedCategory === 'all'
    ? FEATURES
    : FEATURES.filter((f) => f.category === selectedCategory);

  const getStatusBadge = (status: Feature['status']) => {
    switch (status) {
      case 'in_development':
        return { text: 'กำลังพัฒนา', color: '#F59E0B', bgColor: 'rgba(245, 158, 11, 0.2)' };
      case 'coming_soon':
        return { text: 'เร็วๆ นี้', color: '#3B82F6', bgColor: 'rgba(59, 130, 246, 0.2)' };
      case 'planned':
        return { text: 'วางแผนไว้', color: '#8B5CF6', bgColor: 'rgba(139, 92, 246, 0.2)' };
      default:
        return { text: '', color: '', bgColor: '' };
    }
  };

  const handleFeaturePress = (feature: Feature) => {
    const statusText = {
      in_development: 'กำลังพัฒนาอยู่',
      coming_soon: 'จะพร้อมให้บริการเร็วๆ นี้',
      planned: 'อยู่ในแผนพัฒนา',
    };

    Alert.alert(
      feature.title,
      `${feature.description}\n\nสถานะ: ${statusText[feature.status]}`,
      [
        { text: 'เข้าใจแล้ว', style: 'default' },
        {
          text: 'แจ้งเตือนเมื่อพร้อม',
          onPress: () => {
            Alert.alert('ลงทะเบียนแล้ว', 'เราจะแจ้งเตือนคุณเมื่อฟีเจอร์นี้พร้อมใช้งาน');
          },
        },
      ]
    );
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0F172A" />

      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backButton}>
          <Text style={{ fontSize: 24, color: '#FFF' }}>⬅️</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Coming Soon</Text>
        <View style={styles.placeholder} />
      </View>

      <ScrollView style={styles.scrollView} showsVerticalScrollIndicator={false}>
        {/* Hero Section */}
        <View style={styles.heroContainer}>
          <LinearGradient
            colors={['#3B82F6', '#8B5CF6']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={styles.heroGradient}
          >
            <View style={styles.heroCircle1} />
            <View style={styles.heroCircle2} />
            <View style={styles.heroContent}>
              <Text style={styles.heroIcon}>🚀</Text>
              <Text style={styles.heroTitle}>Features ที่กำลังจะมา</Text>
              <Text style={styles.heroSubtitle}>
                เรากำลังพัฒนาฟีเจอร์ใหม่ๆ มากมาย{'\n'}เพื่อประสบการณ์ที่ดีที่สุดของคุณ
              </Text>
            </View>
          </LinearGradient>
        </View>

        {/* Category Filter */}
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          style={styles.categoryScroll}
          contentContainerStyle={styles.categoryContent}
        >
          {CATEGORIES.map((category) => (
            <TouchableOpacity
              key={category.id}
              style={[
                styles.categoryButton,
                selectedCategory === category.id && styles.categoryButtonActive,
              ]}
              onPress={() => setSelectedCategory(category.id)}
            >
              <Text
                style={[
                  styles.categoryText,
                  selectedCategory === category.id && styles.categoryTextActive,
                ]}
              >
                {category.label}
              </Text>
            </TouchableOpacity>
          ))}
        </ScrollView>

        {/* Features Count */}
        <View style={styles.countContainer}>
          <Text style={styles.countText}>{filteredFeatures.length} ฟีเจอร์</Text>
        </View>

        {/* Features Grid */}
        <View style={styles.gridContainer}>
          {filteredFeatures.map((feature) => {
            const badge = getStatusBadge(feature.status);
            return (
              <TouchableOpacity
                key={feature.id}
                style={styles.featureCard}
                onPress={() => handleFeaturePress(feature)}
                activeOpacity={0.7}
              >
                <LinearGradient
                  colors={feature.gradientColors}
                  style={styles.featureIconBox}
                >
                  <Text style={styles.featureIcon}>{feature.icon}</Text>
                </LinearGradient>
                <View style={styles.featureInfo}>
                  <Text style={styles.featureTitle} numberOfLines={1}>
                    {feature.title}
                  </Text>
                  <Text style={styles.featureDescription} numberOfLines={2}>
                    {feature.description}
                  </Text>
                  <View style={[styles.statusBadge, { backgroundColor: badge.bgColor }]}>
                    <Text style={[styles.statusText, { color: badge.color }]}>
                      {badge.text}
                    </Text>
                  </View>
                </View>
              </TouchableOpacity>
            );
          })}
        </View>

        {/* Stay Updated Section */}
        <View style={styles.updateSection}>
          <View style={styles.updateCard}>
            <Text style={styles.updateIcon}>🔔</Text>
            <Text style={styles.updateTitle}>อยากรู้ก่อนใคร?</Text>
            <Text style={styles.updateSubtitle}>
              เปิดการแจ้งเตือนเพื่อรับข่าวสาร{'\n'}เกี่ยวกับฟีเจอร์ใหม่ๆ
            </Text>
            <TouchableOpacity
              style={styles.updateButton}
              onPress={() => {
                Alert.alert('เปิดการแจ้งเตือน', 'คุณจะได้รับการแจ้งเตือนเมื่อมีฟีเจอร์ใหม่');
              }}
            >
              <Text style={styles.updateButtonText}>เปิดการแจ้งเตือน</Text>
            </TouchableOpacity>
          </View>
        </View>

        <View style={{ height: 32 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F172A',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingTop: 50,
    paddingBottom: 12,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(255,255,255,0.1)',
  },
  backButton: {
    padding: 8,
  },
  headerTitle: {
    color: '#FFF',
    fontSize: 18,
    fontWeight: 'bold',
  },
  placeholder: {
    width: 40,
  },
  scrollView: {
    flex: 1,
  },
  heroContainer: {
    padding: 16,
  },
  heroGradient: {
    borderRadius: 24,
    padding: 24,
    overflow: 'hidden',
    position: 'relative',
  },
  heroCircle1: {
    position: 'absolute',
    top: -40,
    right: -40,
    width: 160,
    height: 160,
    borderRadius: 80,
    backgroundColor: 'rgba(255,255,255,0.1)',
  },
  heroCircle2: {
    position: 'absolute',
    bottom: -32,
    left: -32,
    width: 128,
    height: 128,
    borderRadius: 64,
    backgroundColor: 'rgba(0,0,0,0.1)',
  },
  heroContent: {
    position: 'relative',
    zIndex: 10,
  },
  heroIcon: {
    fontSize: 48,
    marginBottom: 12,
  },
  heroTitle: {
    color: '#FFF',
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 8,
  },
  heroSubtitle: {
    color: 'rgba(255,255,255,0.8)',
    fontSize: 14,
    lineHeight: 20,
  },
  categoryScroll: {
    marginBottom: 16,
  },
  categoryContent: {
    paddingHorizontal: 16,
    gap: 8,
  },
  categoryButton: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: '#1E293B',
    marginRight: 8,
  },
  categoryButtonActive: {
    backgroundColor: '#3B82F6',
  },
  categoryText: {
    color: '#9CA3AF',
    fontWeight: '500',
  },
  categoryTextActive: {
    color: '#FFF',
  },
  countContainer: {
    paddingHorizontal: 16,
    marginBottom: 16,
  },
  countText: {
    color: '#9CA3AF',
    fontSize: 14,
  },
  gridContainer: {
    paddingHorizontal: 16,
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  featureCard: {
    width: CARD_WIDTH,
    backgroundColor: '#1E293B',
    borderRadius: 16,
    overflow: 'hidden',
    marginBottom: 8,
  },
  featureIconBox: {
    height: 96,
    alignItems: 'center',
    justifyContent: 'center',
  },
  featureIcon: {
    fontSize: 40,
  },
  featureInfo: {
    padding: 12,
  },
  featureTitle: {
    color: '#FFF',
    fontWeight: '600',
    fontSize: 14,
    marginBottom: 4,
  },
  featureDescription: {
    color: '#9CA3AF',
    fontSize: 12,
    marginBottom: 8,
    lineHeight: 16,
  },
  statusBadge: {
    alignSelf: 'flex-start',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
  },
  statusText: {
    fontSize: 11,
    fontWeight: '600',
  },
  updateSection: {
    padding: 16,
    marginTop: 8,
  },
  updateCard: {
    backgroundColor: '#1E293B',
    borderRadius: 16,
    padding: 20,
    alignItems: 'center',
  },
  updateIcon: {
    fontSize: 32,
    marginBottom: 8,
  },
  updateTitle: {
    color: '#FFF',
    fontWeight: '600',
    fontSize: 16,
    textAlign: 'center',
    marginBottom: 8,
  },
  updateSubtitle: {
    color: '#9CA3AF',
    fontSize: 14,
    textAlign: 'center',
    marginBottom: 16,
    lineHeight: 20,
  },
  updateButton: {
    backgroundColor: '#3B82F6',
    borderRadius: 12,
    paddingHorizontal: 24,
    paddingVertical: 12,
  },
  updateButtonText: {
    color: '#FFF',
    fontWeight: '600',
  },
});
