/**
 * Tarot Reading Result Screen - หน้าแสดงผลการอ่านไพ่
 * แสดงความหมายและคำทำนายอย่างสวยงาม
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
  Share,
  ActivityIndicator,
} from 'react-native';
import { router, useLocalSearchParams } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import * as Haptics from 'expo-haptics';

const { width } = Dimensions.get('window');

// Mock Major Arcana Cards Data
const MAJOR_ARCANA = [
  { id: 0, name: 'The Fool', nameTh: 'คนโง่', icon: '🃏', meaning: 'การเริ่มต้นใหม่ การผจญภัย ความไร้เดียงสา' },
  { id: 1, name: 'The Magician', nameTh: 'นักมายากล', icon: '✨', meaning: 'พลังสร้างสรรค์ ทักษะ ความมุ่งมั่น' },
  { id: 2, name: 'The High Priestess', nameTh: 'นักบวชหญิง', icon: '🌙', meaning: 'สัญชาตญาณ ความลึกลับ ปัญญาภายใน' },
  { id: 3, name: 'The Empress', nameTh: 'จักรพรรดินี', icon: '👑', meaning: 'ความอุดมสมบูรณ์ ความงาม ธรรมชาติ' },
  { id: 4, name: 'The Emperor', nameTh: 'จักรพรรดิ', icon: '🏛️', meaning: 'อำนาจ โครงสร้าง ความมั่นคง' },
  { id: 5, name: 'The Hierophant', nameTh: 'พระสันตะปาปา', icon: '📿', meaning: 'ประเพณี ศรัทธา การศึกษา' },
  { id: 6, name: 'The Lovers', nameTh: 'คู่รัก', icon: '💕', meaning: 'ความรัก ทางเลือก ความสัมพันธ์' },
  { id: 7, name: 'The Chariot', nameTh: 'รถศึก', icon: '🏇', meaning: 'ชัยชนะ ความมุ่งมั่น การควบคุม' },
  { id: 8, name: 'Strength', nameTh: 'พลัง', icon: '🦁', meaning: 'ความกล้าหาญ อดทน พลังภายใน' },
  { id: 9, name: 'The Hermit', nameTh: 'ฤาษี', icon: '🏔️', meaning: 'การค้นหาตัวเอง ความสันโดษ ปัญญา' },
  { id: 10, name: 'Wheel of Fortune', nameTh: 'วงล้อโชคชะตา', icon: '🎡', meaning: 'โชคชะตา การเปลี่ยนแปลง วัฏจักร' },
  { id: 11, name: 'Justice', nameTh: 'ความยุติธรรม', icon: '⚖️', meaning: 'ความเที่ยงธรรม ความจริง กฎหมาย' },
  { id: 12, name: 'The Hanged Man', nameTh: 'ชายถูกแขวน', icon: '🙃', meaning: 'การปล่อยวาง มุมมองใหม่ การเสียสละ' },
  { id: 13, name: 'Death', nameTh: 'ความตาย', icon: '🦋', meaning: 'การเปลี่ยนแปลง จบสิ้น การเกิดใหม่' },
  { id: 14, name: 'Temperance', nameTh: 'ความพอดี', icon: '⚗️', meaning: 'ความสมดุล อดทน การผสมผสาน' },
  { id: 15, name: 'The Devil', nameTh: 'ปีศาจ', icon: '😈', meaning: 'การยึดติด สิ่งล่อใจ ความมืด' },
  { id: 16, name: 'The Tower', nameTh: 'หอคอย', icon: '🗼', meaning: 'การพังทลาย การเปลี่ยนแปลงกะทันหัน ความจริง' },
  { id: 17, name: 'The Star', nameTh: 'ดวงดาว', icon: '⭐', meaning: 'ความหวัง แรงบันดาลใจ ความสงบ' },
  { id: 18, name: 'The Moon', nameTh: 'พระจันทร์', icon: '🌕', meaning: 'ภาพลวง ความกลัว จิตใต้สำนึก' },
  { id: 19, name: 'The Sun', nameTh: 'พระอาทิตย์', icon: '☀️', meaning: 'ความสุข ความสำเร็จ ความมีชีวิตชีวา' },
  { id: 20, name: 'Judgement', nameTh: 'การพิพากษา', icon: '📯', meaning: 'การตัดสิน การฟื้นคืน การเรียก' },
  { id: 21, name: 'The World', nameTh: 'โลก', icon: '🌍', meaning: 'ความสำเร็จ ความสมบูรณ์ การบรรลุ' },
];

// Position meanings for 3-card spread
const POSITIONS = [
  { name: 'อดีต', description: 'สิ่งที่ผ่านมาและส่งผลต่อปัจจุบัน' },
  { name: 'ปัจจุบัน', description: 'สถานการณ์ที่คุณกำลังเผชิญ' },
  { name: 'อนาคต', description: 'แนวโน้มและสิ่งที่รออยู่ข้างหน้า' },
];

// Reading Card Component
const ReadingCard = ({
  card,
  position,
  index,
}: {
  card: typeof MAJOR_ARCANA[0];
  position: typeof POSITIONS[0];
  index: number;
}) => {
  const scaleAnim = useRef(new Animated.Value(0)).current;
  const opacityAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.sequence([
      Animated.delay(index * 300),
      Animated.parallel([
        Animated.spring(scaleAnim, {
          toValue: 1,
          useNativeDriver: true,
          tension: 50,
          friction: 7,
        }),
        Animated.timing(opacityAnim, {
          toValue: 1,
          duration: 500,
          useNativeDriver: true,
        }),
      ]),
    ]).start();
  }, [index, scaleAnim, opacityAnim]);

  return (
    <Animated.View
      style={[
        styles.readingCard,
        {
          opacity: opacityAnim,
          transform: [{ scale: scaleAnim }],
        },
      ]}
    >
      {/* Position Badge */}
      <View style={styles.positionBadge}>
        <Text style={styles.positionNumber}>{index + 1}</Text>
      </View>

      {/* Card Content */}
      <LinearGradient
        colors={['rgba(139, 92, 246, 0.2)', 'rgba(236, 72, 153, 0.1)']}
        style={styles.readingCardGradient}
      >
        {/* Position Name */}
        <Text style={styles.positionName}>{position.name}</Text>
        <Text style={styles.positionDesc}>{position.description}</Text>

        {/* Card Display */}
        <View style={styles.cardDisplay}>
          <LinearGradient
            colors={['#FEF3C7', '#FDE68A', '#FCD34D']}
            style={styles.cardImageContainer}
          >
            <Text style={styles.cardIcon}>{card.icon}</Text>
          </LinearGradient>
        </View>

        {/* Card Name */}
        <Text style={styles.cardName}>{card.nameTh}</Text>
        <Text style={styles.cardNameEn}>{card.name}</Text>

        {/* Card Meaning */}
        <View style={styles.meaningContainer}>
          <Text style={styles.meaningLabel}>ความหมาย:</Text>
          <Text style={styles.meaningText}>{card.meaning}</Text>
        </View>
      </LinearGradient>
    </Animated.View>
  );
};

export default function ReadingScreen() {
  const params = useLocalSearchParams();
  const { categoryName, selectedCards: selectedCardsStr } = params;

  const [loading, setLoading] = useState(true);
  const [interpretation, setInterpretation] = useState('');
  const selectedCards = (selectedCardsStr as string)?.split(',').map(Number) || [];

  const headerOpacity = useRef(new Animated.Value(0)).current;
  const interpretationOpacity = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // Simulate loading
    const timer = setTimeout(() => {
      setLoading(false);

      // Generate interpretation
      const cards = selectedCards.map((i) => MAJOR_ARCANA[i]);
      const interp = generateInterpretation(cards, categoryName as string);
      setInterpretation(interp);

      // Animations
      Animated.timing(headerOpacity, {
        toValue: 1,
        duration: 600,
        useNativeDriver: true,
      }).start();

      setTimeout(() => {
        Animated.timing(interpretationOpacity, {
          toValue: 1,
          duration: 600,
          useNativeDriver: true,
        }).start();
      }, selectedCards.length * 300 + 500);
    }, 1500);

    return () => clearTimeout(timer);
  }, [selectedCards, categoryName, headerOpacity, interpretationOpacity]);

  const generateInterpretation = (
    cards: typeof MAJOR_ARCANA,
    category: string
  ): string => {
    const pastCard = cards[0];
    const presentCard = cards[1];
    const futureCard = cards[2];

    return `🔮 การทำนายของคุณ\n\n` +
      `จากไพ่ที่คุณเลือก ในอดีต "${pastCard?.nameTh}" แสดงถึง${pastCard?.meaning} ` +
      `ซึ่งมีผลต่อสถานการณ์ปัจจุบันของคุณ\n\n` +
      `ปัจจุบัน ไพ่ "${presentCard?.nameTh}" บ่งบอกว่าคุณกำลังอยู่ในช่วงของ${presentCard?.meaning} ` +
      `นี่คือช่วงเวลาที่สำคัญในการตัดสินใจ\n\n` +
      `สำหรับอนาคต ไพ่ "${futureCard?.nameTh}" ชี้ให้เห็นว่า${futureCard?.meaning} ` +
      `กำลังรอคุณอยู่ข้างหน้า หากคุณดำเนินชีวิตอย่างมีสติและรอบคอบ\n\n` +
      `✨ คำแนะนำ: จงเชื่อมั่นในตัวเอง และเปิดใจรับสิ่งใหม่ๆ ที่กำลังจะเข้ามา`;
  };

  const handleShare = async () => {
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    try {
      const cards = selectedCards.map((i) => MAJOR_ARCANA[i]);
      await Share.share({
        message:
          `🔮 ผลดูดวงไพ่ทาโรต์\n\n` +
          `หมวด: ${categoryName}\n\n` +
          `ไพ่ที่ได้:\n` +
          `1. อดีต: ${cards[0]?.nameTh}\n` +
          `2. ปัจจุบัน: ${cards[1]?.nameTh}\n` +
          `3. อนาคต: ${cards[2]?.nameTh}\n\n` +
          `ดูดวงเพิ่มเติมที่ Thaiprompt App`,
      });
    } catch (error) {
      console.error('Share error:', error);
    }
  };

  const handleNewReading = () => {
    router.replace('/tarot');
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <LinearGradient
          colors={['#0F0F23', '#1A1A2E', '#0F0F23']}
          style={StyleSheet.absoluteFill}
        />
        <ActivityIndicator size="large" color="#8B5CF6" />
        <Text style={styles.loadingText}>🔮 กำลังอ่านไพ่...</Text>
        <Text style={styles.loadingSubtext}>
          โปรดรอสักครู่ขณะที่เรากำลังตีความไพ่ของคุณ
        </Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" />

      {/* Background */}
      <LinearGradient
        colors={['#0F0F23', '#1A1A2E', '#16213E']}
        style={StyleSheet.absoluteFill}
      />

      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* Header */}
        <Animated.View style={[styles.header, { opacity: headerOpacity }]}>
          <Pressable style={styles.backButton} onPress={() => router.back()}>
            <Ionicons name="arrow-back" size={24} color="#fff" />
          </Pressable>

          <View style={styles.headerContent}>
            <Text style={styles.headerTitle}>ผลการอ่านไพ่</Text>
            <Text style={styles.headerSubtitle}>{categoryName}</Text>
          </View>

          <Pressable style={styles.shareButton} onPress={handleShare}>
            <Ionicons name="share-outline" size={24} color="#fff" />
          </Pressable>
        </Animated.View>

        {/* Cards Section */}
        <View style={styles.cardsSection}>
          <Text style={styles.sectionTitle}>🃏 ไพ่ที่คุณเลือก</Text>

          {selectedCards.map((cardIndex, i) => (
            <ReadingCard
              key={i}
              card={MAJOR_ARCANA[cardIndex]}
              position={POSITIONS[i]}
              index={i}
            />
          ))}
        </View>

        {/* Interpretation Section */}
        <Animated.View
          style={[styles.interpretationSection, { opacity: interpretationOpacity }]}
        >
          <LinearGradient
            colors={['rgba(139, 92, 246, 0.15)', 'rgba(236, 72, 153, 0.1)']}
            style={styles.interpretationCard}
          >
            <Text style={styles.interpretationTitle}>📜 คำทำนาย</Text>
            <Text style={styles.interpretationText}>{interpretation}</Text>
          </LinearGradient>
        </Animated.View>

        {/* Action Buttons */}
        <View style={styles.actionSection}>
          <Pressable style={styles.newReadingButton} onPress={handleNewReading}>
            <LinearGradient
              colors={['#8B5CF6', '#EC4899']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.newReadingGradient}
            >
              <Ionicons name="refresh" size={24} color="#fff" />
              <Text style={styles.newReadingText}>ดูดวงใหม่</Text>
            </LinearGradient>
          </Pressable>

          <Pressable
            style={styles.homeButton}
            onPress={() => router.replace('/(tabs)')}
          >
            <Text style={styles.homeButtonText}>กลับหน้าหลัก</Text>
          </Pressable>
        </View>

        {/* Footer */}
        <View style={styles.footer}>
          <Text style={styles.footerText}>
            🌙 ผลการทำนายเป็นเพียงแนวทางในการดำเนินชีวิต
          </Text>
          <Text style={styles.footerText}>
            ความสำเร็จขึ้นอยู่กับการกระทำของคุณเอง
          </Text>
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#0F0F23',
  },
  loadingText: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 20,
  },
  loadingSubtext: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.6)',
    marginTop: 8,
    textAlign: 'center',
    paddingHorizontal: 40,
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    paddingBottom: 40,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingTop: 60,
    paddingHorizontal: 20,
    paddingBottom: 20,
  },
  backButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.1)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  headerContent: {
    flex: 1,
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 22,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  headerSubtitle: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.7)',
    marginTop: 4,
  },
  shareButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.1)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  cardsSection: {
    paddingHorizontal: 20,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 20,
  },
  readingCard: {
    marginBottom: 20,
    borderRadius: 20,
    overflow: 'hidden',
    position: 'relative',
  },
  positionBadge: {
    position: 'absolute',
    top: 16,
    left: 16,
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#8B5CF6',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 1,
  },
  positionNumber: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  readingCardGradient: {
    padding: 20,
    paddingTop: 24,
    borderWidth: 1,
    borderColor: 'rgba(139, 92, 246, 0.3)',
    borderRadius: 20,
  },
  positionName: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
    textAlign: 'center',
    marginTop: 20,
  },
  positionDesc: {
    fontSize: 12,
    color: 'rgba(255,255,255,0.6)',
    textAlign: 'center',
    marginTop: 4,
    marginBottom: 16,
  },
  cardDisplay: {
    alignItems: 'center',
    marginBottom: 16,
  },
  cardImageContainer: {
    width: 100,
    height: 140,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 3,
    borderColor: '#D97706',
    shadowColor: '#F59E0B',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.4,
    shadowRadius: 12,
    elevation: 8,
  },
  cardIcon: {
    fontSize: 48,
  },
  cardName: {
    fontSize: 22,
    fontWeight: 'bold',
    color: '#FFFFFF',
    textAlign: 'center',
  },
  cardNameEn: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.6)',
    textAlign: 'center',
    marginTop: 2,
  },
  meaningContainer: {
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 12,
    padding: 16,
    marginTop: 16,
  },
  meaningLabel: {
    fontSize: 12,
    color: 'rgba(255,255,255,0.6)',
    marginBottom: 4,
  },
  meaningText: {
    fontSize: 16,
    color: '#FFFFFF',
    lineHeight: 24,
  },
  interpretationSection: {
    paddingHorizontal: 20,
    marginTop: 10,
  },
  interpretationCard: {
    borderRadius: 20,
    padding: 24,
    borderWidth: 1,
    borderColor: 'rgba(139, 92, 246, 0.3)',
  },
  interpretationTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 16,
  },
  interpretationText: {
    fontSize: 16,
    color: 'rgba(255,255,255,0.9)',
    lineHeight: 28,
  },
  actionSection: {
    paddingHorizontal: 20,
    marginTop: 30,
    gap: 12,
  },
  newReadingButton: {
    borderRadius: 16,
    overflow: 'hidden',
    shadowColor: '#8B5CF6',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.4,
    shadowRadius: 12,
    elevation: 8,
  },
  newReadingGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 18,
    gap: 10,
  },
  newReadingText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  homeButton: {
    paddingVertical: 16,
    alignItems: 'center',
  },
  homeButtonText: {
    fontSize: 16,
    color: 'rgba(255,255,255,0.7)',
  },
  footer: {
    paddingHorizontal: 20,
    paddingVertical: 30,
    alignItems: 'center',
  },
  footerText: {
    fontSize: 12,
    color: 'rgba(255,255,255,0.4)',
    textAlign: 'center',
    marginBottom: 4,
  },
});
