/**
 * หน้า Coming Soon - แสดง Features ที่จะมาเร็วๆ นี้
 */

import React, { useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';

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
    icon: String.fromCodePoint(0x1F3EA),
    title: 'ตลาดออนไลน์',
    description: 'ช้อปปิ้งสินค้า, สั่งซื้อและติดตามออเดอร์',
    category: 'E-commerce',
    gradientColors: ['#F59E0B', '#D97706'],
    status: 'in_development',
  },
  {
    id: 'cart',
    icon: String.fromCodePoint(0x1F6D2),
    title: 'ตะกร้าสินค้า',
    description: 'จัดการตะกร้าและชำระเงินออนไลน์',
    category: 'E-commerce',
    gradientColors: ['#EF4444', '#DC2626'],
    status: 'in_development',
  },
  // Hotel
  {
    id: 'hotel',
    icon: String.fromCodePoint(0x1F3E8),
    title: 'จองโรงแรม',
    description: 'ค้นหาและจองที่พักทั่วไทย',
    category: 'Booking',
    gradientColors: ['#8B5CF6', '#7C3AED'],
    status: 'coming_soon',
  },
  // POS
  {
    id: 'pos',
    icon: String.fromCodePoint(0x1F4B0),
    title: 'ระบบ POS',
    description: 'จุดขายสำหรับร้านค้าปลีก',
    category: 'Business',
    gradientColors: ['#10B981', '#059669'],
    status: 'coming_soon',
  },
  // Gaming
  {
    id: 'games',
    icon: String.fromCodePoint(0x1F3AE),
    title: 'เกมและรางวัล',
    description: 'เล่นเกม, ภารกิจ, รับรางวัล',
    category: 'Gaming',
    gradientColors: ['#EC4899', '#DB2777'],
    status: 'in_development',
  },
  {
    id: 'snake',
    icon: String.fromCodePoint(0x1F40D),
    title: 'เกมงูสายรวย',
    description: 'เล่นงูเก็บเหรียญแข่งกับเพื่อน',
    category: 'Gaming',
    gradientColors: ['#22C55E', '#16A34A'],
    status: 'coming_soon',
  },
  // Crypto & Blockchain
  {
    id: 'crypto',
    icon: String.fromCodePoint(0x1FA99),
    title: 'กระเป๋าคริปโต',
    description: 'ซื้อขายและเก็บ Cryptocurrency',
    category: 'Crypto',
    gradientColors: ['#F59E0B', '#B45309'],
    status: 'coming_soon',
  },
  {
    id: 'tpix',
    icon: String.fromCodePoint(0x1F48E),
    title: 'TPIX Token',
    description: 'โทเค็น TPIX สำหรับ Ecosystem',
    category: 'Crypto',
    gradientColors: ['#3B82F6', '#1D4ED8'],
    status: 'planned',
  },
  {
    id: 'dex',
    icon: String.fromCodePoint(0x1F500),
    title: 'แลกเปลี่ยน DEX',
    description: 'แลกเปลี่ยนแบบกระจายศูนย์',
    category: 'Crypto',
    gradientColors: ['#8B5CF6', '#6D28D9'],
    status: 'planned',
  },
  {
    id: 'staking',
    icon: String.fromCodePoint(0x1F3C6),
    title: 'Staking',
    description: 'ล็อคเงินรับผลตอบแทน',
    category: 'Crypto',
    gradientColors: ['#F97316', '#EA580C'],
    status: 'planned',
  },
  // Trading Bot
  {
    id: 'trading_bot',
    icon: String.fromCodePoint(0x1F916),
    title: 'Trading Bot',
    description: 'บอทเทรดอัตโนมัติ',
    category: 'Trading',
    gradientColors: ['#06B6D4', '#0891B2'],
    status: 'coming_soon',
  },
  {
    id: 'signals',
    icon: String.fromCodePoint(0x1F4C8),
    title: 'สัญญาณการเทรด',
    description: 'รับสัญญาณซื้อขาย',
    category: 'Trading',
    gradientColors: ['#14B8A6', '#0D9488'],
    status: 'planned',
  },
  // AI Bot
  {
    id: 'ai_bot',
    icon: String.fromCodePoint(0x1F916),
    title: 'AI Bot',
    description: 'เช่าและจัดการ AI Bot',
    category: 'AI',
    gradientColors: ['#EC4899', '#BE185D'],
    status: 'coming_soon',
  },
  {
    id: 'line_bot',
    icon: String.fromCodePoint(0x1F4AC),
    title: 'LINE Bot AI',
    description: 'บอทตอบแชท LINE อัตโนมัติ',
    category: 'AI',
    gradientColors: ['#22C55E', '#15803D'],
    status: 'in_development',
  },
  {
    id: 'content_gen',
    icon: String.fromCodePoint(0x270D),
    title: 'AI Content',
    description: 'สร้างเนื้อหาด้วย AI',
    category: 'AI',
    gradientColors: ['#8B5CF6', '#6D28D9'],
    status: 'planned',
  },
  // Forum & Community
  {
    id: 'forum',
    icon: String.fromCodePoint(0x1F4AC),
    title: 'ฟอรั่ม',
    description: 'ชุมชนพูดคุยและแชร์ความรู้',
    category: 'Community',
    gradientColors: ['#3B82F6', '#2563EB'],
    status: 'coming_soon',
  },
  // NFC Card
  {
    id: 'nfc',
    icon: String.fromCodePoint(0x1F4B3),
    title: 'NFC Card',
    description: 'บัตรแตะจ่ายและเติมเงิน',
    category: 'Payment',
    gradientColors: ['#6366F1', '#4F46E5'],
    status: 'planned',
  },
  // Investment
  {
    id: 'invest',
    icon: String.fromCodePoint(0x1F4BC),
    title: 'แผนการลงทุน',
    description: 'ลงทุนและติดตามผลตอบแทน',
    category: 'Investment',
    gradientColors: ['#10B981', '#047857'],
    status: 'coming_soon',
  },
  // Tarot
  {
    id: 'tarot',
    icon: String.fromCodePoint(0x1F0CF),
    title: 'ไพ่ทาโรต์',
    description: 'ดูดวงและอ่านไพ่ทาโรต์',
    category: 'Entertainment',
    gradientColors: ['#9333EA', '#7E22CE'],
    status: 'coming_soon',
  },
  // Seller
  {
    id: 'seller',
    icon: String.fromCodePoint(0x1F3EA),
    title: 'Seller Dashboard',
    description: 'จัดการร้านค้าและสินค้า',
    category: 'Business',
    gradientColors: ['#F59E0B', '#D97706'],
    status: 'coming_soon',
  },
  // Software Sales
  {
    id: 'software',
    icon: String.fromCodePoint(0x1F4BB),
    title: 'ซอฟต์แวร์',
    description: 'ซื้อและดาวน์โหลดซอฟต์แวร์',
    category: 'Business',
    gradientColors: ['#06B6D4', '#0284C7'],
    status: 'planned',
  },
  // QR Scanner
  {
    id: 'qr',
    icon: String.fromCodePoint(0x1F4F1),
    title: 'สแกน QR',
    description: 'สแกน QR Code และบาร์โค้ด',
    category: 'Utility',
    gradientColors: ['#64748B', '#475569'],
    status: 'in_development',
  },
  // Food Passport
  {
    id: 'food_passport',
    icon: String.fromCodePoint(0x1F35C),
    title: 'Food Passport',
    description: 'ตรวจสอบแหล่งที่มาของอาหาร',
    category: 'Supply Chain',
    gradientColors: ['#22C55E', '#15803D'],
    status: 'planned',
  },
  // Academy
  {
    id: 'academy',
    icon: String.fromCodePoint(0x1F393),
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
        return { text: 'กำลังพัฒนา', color: '#F59E0B', bgColor: '#F59E0B20' };
      case 'coming_soon':
        return { text: 'เร็วๆ นี้', color: '#3B82F6', bgColor: '#3B82F620' };
      case 'planned':
        return { text: 'วางแผนไว้', color: '#8B5CF6', bgColor: '#8B5CF620' };
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
    <SafeAreaView className="flex-1 bg-slate-900">
      {/* Header */}
      <View className="flex-row items-center justify-between px-4 py-3 border-b border-gray-800">
        <TouchableOpacity onPress={() => router.back()} className="p-2">
          <Ionicons name="arrow-back" size={24} color="white" />
        </TouchableOpacity>
        <Text className="text-white text-lg font-bold">Coming Soon</Text>
        <View className="w-10" />
      </View>

      <ScrollView className="flex-1">
        {/* Hero Section */}
        <View className="px-4 py-6">
          <LinearGradient
            colors={['#3B82F6', '#8B5CF6']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={{
              borderRadius: 24,
              padding: 24,
              overflow: 'hidden',
            }}
          >
            <View className="absolute top-0 right-0 w-40 h-40 rounded-full bg-white/10 -mr-20 -mt-20" />
            <View className="absolute bottom-0 left-0 w-32 h-32 rounded-full bg-black/10 -ml-16 -mb-16" />

            <View className="relative z-10">
              <Text className="text-5xl mb-3">
                {String.fromCodePoint(0x1F680)}
              </Text>
              <Text className="text-white text-2xl font-bold mb-2">
                Features ที่กำลังจะมา
              </Text>
              <Text className="text-white/80 text-sm">
                เรากำลังพัฒนาฟีเจอร์ใหม่ๆ มากมาย{'\n'}
                เพื่อประสบการณ์ที่ดีที่สุดของคุณ
              </Text>
            </View>
          </LinearGradient>
        </View>

        {/* Category Filter */}
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          className="px-4 mb-4"
        >
          {CATEGORIES.map((category) => (
            <TouchableOpacity
              key={category.id}
              className={`mr-2 px-4 py-2 rounded-full ${
                selectedCategory === category.id
                  ? 'bg-blue-600'
                  : 'bg-slate-800'
              }`}
              onPress={() => setSelectedCategory(category.id)}
            >
              <Text
                className={
                  selectedCategory === category.id
                    ? 'text-white font-medium'
                    : 'text-gray-400'
                }
              >
                {category.label}
              </Text>
            </TouchableOpacity>
          ))}
        </ScrollView>

        {/* Features Count */}
        <View className="px-4 mb-4">
          <Text className="text-gray-400 text-sm">
            {filteredFeatures.length} ฟีเจอร์
          </Text>
        </View>

        {/* Features Grid */}
        <View className="px-4">
          <View className="flex-row flex-wrap -mx-1">
            {filteredFeatures.map((feature) => {
              const badge = getStatusBadge(feature.status);
              return (
                <View key={feature.id} className="w-1/2 p-1">
                  <TouchableOpacity
                    className="bg-slate-800 rounded-2xl overflow-hidden"
                    onPress={() => handleFeaturePress(feature)}
                    activeOpacity={0.7}
                  >
                    <LinearGradient
                      colors={feature.gradientColors}
                      style={{
                        height: 96,
                        alignItems: 'center',
                        justifyContent: 'center',
                      }}
                    >
                      <Text className="text-4xl">{feature.icon}</Text>
                    </LinearGradient>
                    <View className="p-3">
                      <View className="flex-row items-center justify-between mb-1">
                        <Text
                          className="text-white font-medium flex-1"
                          numberOfLines={1}
                        >
                          {feature.title}
                        </Text>
                      </View>
                      <Text
                        className="text-gray-400 text-xs mb-2"
                        numberOfLines={2}
                      >
                        {feature.description}
                      </Text>
                      <View
                        className="self-start rounded-full px-2 py-0.5"
                        style={{ backgroundColor: badge.bgColor }}
                      >
                        <Text
                          className="text-xs font-medium"
                          style={{ color: badge.color }}
                        >
                          {badge.text}
                        </Text>
                      </View>
                    </View>
                  </TouchableOpacity>
                </View>
              );
            })}
          </View>
        </View>

        {/* Stay Updated Section */}
        <View className="px-4 py-6">
          <View className="bg-slate-800 rounded-2xl p-4 items-center">
            <Text className="text-3xl mb-2">
              {String.fromCodePoint(0x1F514)}
            </Text>
            <Text className="text-white font-medium text-center mb-2">
              อยากรู้ก่อนใคร?
            </Text>
            <Text className="text-gray-400 text-sm text-center mb-4">
              เปิดการแจ้งเตือนเพื่อรับข่าวสาร{'\n'}
              เกี่ยวกับฟีเจอร์ใหม่ๆ
            </Text>
            <TouchableOpacity
              className="bg-blue-600 rounded-xl px-6 py-3"
              onPress={() => {
                Alert.alert('เปิดการแจ้งเตือน', 'คุณจะได้รับการแจ้งเตือนเมื่อมีฟีเจอร์ใหม่');
              }}
            >
              <Text className="text-white font-medium">เปิดการแจ้งเตือน</Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* Bottom Padding */}
        <View className="h-8" />
      </ScrollView>
    </SafeAreaView>
  );
}
