/**
 * Tarot Reading Result Screen - หน้าแสดงผลการอ่านไพ่ 78 ใบ
 * รองรับทุกโหมดการเปิดไพ่ (1, 3, 5, 10 ใบ)
 */

import React, { useState, useEffect, useRef, useMemo } from 'react';
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

// นำเข้าข้อมูลไพ่ 78 ใบจาก tarotData.ts
import {
  TarotCard,
  SpreadPosition,
  getCardById,
  getSpreadBySlug,
  SPREAD_TYPES,
} from '../../data/tarotData';

const { width } = Dimensions.get('window');

// Default positions fallback (สำหรับกรณีไม่มี spread)
const DEFAULT_POSITIONS: SpreadPosition[] = [
  { name_en: 'Past', name_th: 'อดีต', description_th: 'สิ่งที่ผ่านมาและส่งผลต่อปัจจุบัน' },
  { name_en: 'Present', name_th: 'ปัจจุบัน', description_th: 'สถานการณ์ที่คุณกำลังเผชิญ' },
  { name_en: 'Future', name_th: 'อนาคต', description_th: 'แนวโน้มและสิ่งที่รออยู่ข้างหน้า' },
];

// Reading Card Component - รองรับไพ่ 78 ใบ
const ReadingCard = ({
  card,
  position,
  index,
}: {
  card: TarotCard;
  position: SpreadPosition;
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

  // แสดง suit badge สำหรับ Minor Arcana
  const getSuitBadge = () => {
    if (card.type === 'major_arcana') return null;
    const suitColors: Record<string, string> = {
      wands: '#F59E0B',
      cups: '#3B82F6',
      swords: '#6B7280',
      pentacles: '#10B981',
    };
    const suitNames: Record<string, string> = {
      wands: 'ไม้เท้า',
      cups: 'ถ้วย',
      swords: 'ดาบ',
      pentacles: 'เหรียญ',
    };
    return (
      <View style={[styles.suitBadge, { backgroundColor: suitColors[card.suit || ''] }]}>
        <Text style={styles.suitBadgeText}>{suitNames[card.suit || '']}</Text>
      </View>
    );
  };

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
        <Text style={styles.positionName}>{position.name_th}</Text>
        <Text style={styles.positionDesc}>{position.description_th}</Text>

        {/* Card Display */}
        <View style={styles.cardDisplay}>
          <LinearGradient
            colors={card.type === 'major_arcana'
              ? ['#FEF3C7', '#FDE68A', '#FCD34D']
              : ['#E0E7FF', '#C7D2FE', '#A5B4FC']}
            style={styles.cardImageContainer}
          >
            <Text style={styles.cardIcon}>{card.icon}</Text>
          </LinearGradient>
          {getSuitBadge()}
        </View>

        {/* Card Name */}
        <Text style={styles.cardName}>{card.name_th}</Text>
        <Text style={styles.cardNameEn}>{card.name_en}</Text>

        {/* Card Type Badge */}
        <View style={[
          styles.typeBadge,
          { backgroundColor: card.type === 'major_arcana' ? '#8B5CF6' : '#6366F1' }
        ]}>
          <Text style={styles.typeBadgeText}>
            {card.type === 'major_arcana' ? 'Major Arcana' : 'Minor Arcana'}
          </Text>
        </View>

        {/* Card Meaning */}
        <View style={styles.meaningContainer}>
          <Text style={styles.meaningLabel}>ความหมาย (ด้านตั้ง):</Text>
          <Text style={styles.meaningText}>{card.upright_meaning_th}</Text>
        </View>

        {/* Keywords */}
        <View style={styles.keywordsContainer}>
          <Text style={styles.keywordsLabel}>คำสำคัญ:</Text>
          <View style={styles.keywordsTags}>
            {card.keywords_th.slice(0, 4).map((keyword, i) => (
              <View key={i} style={styles.keywordTag}>
                <Text style={styles.keywordText}>{keyword}</Text>
              </View>
            ))}
          </View>
        </View>
      </LinearGradient>
    </Animated.View>
  );
};

export default function ReadingScreen() {
  const params = useLocalSearchParams();
  const {
    categoryName,
    selectedCards: selectedCardsStr,
    spreadSlug = 'past-present-future',
  } = params;

  const [loading, setLoading] = useState(true);
  const [interpretation, setInterpretation] = useState('');

  // แปลง selectedCards string เป็น array ของ IDs
  const selectedCardIds = useMemo(() => {
    return (selectedCardsStr as string)?.split(',').map(Number) || [];
  }, [selectedCardsStr]);

  // ดึงข้อมูลไพ่จาก ID (รองรับ 78 ใบ)
  const cards = useMemo(() => {
    return selectedCardIds
      .map((id) => getCardById(id))
      .filter((card): card is TarotCard => card !== undefined);
  }, [selectedCardIds]);

  // ดึงข้อมูล spread type
  const spread = useMemo(() => {
    return getSpreadBySlug(spreadSlug as string);
  }, [spreadSlug]);

  // ดึงตำแหน่งไพ่ตาม spread
  const positions = useMemo(() => {
    if (spread && spread.positions.length > 0) {
      return spread.positions;
    }
    // Fallback positions ตามจำนวนไพ่
    const fallback: SpreadPosition[] = [];
    for (let i = 0; i < cards.length; i++) {
      fallback.push(DEFAULT_POSITIONS[i % DEFAULT_POSITIONS.length]);
    }
    return fallback;
  }, [spread, cards.length]);

  const headerOpacity = useRef(new Animated.Value(0)).current;
  const interpretationOpacity = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // Simulate loading
    const timer = setTimeout(() => {
      setLoading(false);

      // Generate interpretation
      const interp = generateInterpretation(cards, positions, categoryName as string);
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
      }, cards.length * 300 + 500);
    }, 1500);

    return () => clearTimeout(timer);
  }, [cards, positions, categoryName, headerOpacity, interpretationOpacity]);

  // สร้างคำทำนายแบบ dynamic ตามจำนวนไพ่
  const generateInterpretation = (
    cards: TarotCard[],
    positions: SpreadPosition[],
    category: string
  ): string => {
    if (cards.length === 0) {
      return '🔮 ไม่พบข้อมูลไพ่ กรุณาลองใหม่อีกครั้ง';
    }

    // สำหรับไพ่ใบเดียว
    if (cards.length === 1) {
      const card = cards[0];
      return `🔮 การทำนายของคุณ\n\n` +
        `ไพ่ "${card.name_th}" (${card.name_en}) ได้ปรากฏขึ้นเพื่อตอบคำถามของคุณ\n\n` +
        `${card.upright_meaning_th}\n\n` +
        `คำสำคัญ: ${card.keywords_th.join(', ')}\n\n` +
        `✨ คำแนะนำ: จงใคร่ครวญความหมายของไพ่นี้ และปรับใช้กับสถานการณ์ของคุณ`;
    }

    // สำหรับหลายไพ่
    let text = `🔮 การทำนายของคุณ\n\n`;

    cards.forEach((card, index) => {
      const pos = positions[index];
      if (pos) {
        text += `📍 ${pos.name_th}:\n`;
        text += `ไพ่ "${card.name_th}" บ่งบอกว่า ${card.keywords_th.slice(0, 2).join(' และ ')} `;
        text += `กำลังมีบทบาทสำคัญในช่วงนี้\n\n`;
      }
    });

    // สรุป
    text += `✨ คำแนะนำรวม:\n`;
    text += `จากการรวมความหมายของไพ่ทั้งหมด คุณควรเปิดใจรับสิ่งใหม่ๆ `;
    text += `และเชื่อมั่นในการตัดสินใจของตัวเอง ผลลัพธ์ที่ดีกำลังรอคุณอยู่`;

    return text;
  };

  const handleShare = async () => {
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    try {
      // สร้างรายการไพ่สำหรับแชร์
      let cardsList = '';
      cards.forEach((card, index) => {
        const pos = positions[index];
        cardsList += `${index + 1}. ${pos?.name_th || `ไพ่ที่ ${index + 1}`}: ${card.name_th}\n`;
      });

      await Share.share({
        message:
          `🔮 ผลดูดวงไพ่ทาโรต์\n\n` +
          `หมวด: ${categoryName}\n` +
          `โหมด: ${spread?.name_th || 'ทำนายทั่วไป'}\n\n` +
          `ไพ่ที่ได้:\n${cardsList}\n` +
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

        {/* Spread Info */}
        {spread && (
          <View style={styles.spreadInfo}>
            <Text style={styles.spreadIcon}>{spread.icon}</Text>
            <Text style={styles.spreadName}>{spread.name_th}</Text>
            <Text style={styles.spreadDesc}>{spread.description_th}</Text>
          </View>
        )}

        {/* Cards Section */}
        <View style={styles.cardsSection}>
          <Text style={styles.sectionTitle}>
            🃏 ไพ่ที่คุณเลือก ({cards.length} ใบ)
          </Text>

          {cards.map((card, i) => (
            <ReadingCard
              key={card.id}
              card={card}
              position={positions[i] || DEFAULT_POSITIONS[0]}
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
  // Suit badge สำหรับ Minor Arcana
  suitBadge: {
    position: 'absolute',
    bottom: -8,
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  suitBadgeText: {
    fontSize: 10,
    color: '#FFFFFF',
    fontWeight: 'bold',
  },
  // Type badge
  typeBadge: {
    alignSelf: 'center',
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 12,
    marginTop: 8,
  },
  typeBadgeText: {
    fontSize: 11,
    color: '#FFFFFF',
    fontWeight: '600',
  },
  // Keywords
  keywordsContainer: {
    marginTop: 12,
    backgroundColor: 'rgba(255,255,255,0.03)',
    borderRadius: 10,
    padding: 12,
  },
  keywordsLabel: {
    fontSize: 11,
    color: 'rgba(255,255,255,0.5)',
    marginBottom: 8,
  },
  keywordsTags: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
  },
  keywordTag: {
    backgroundColor: 'rgba(139, 92, 246, 0.3)',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  keywordText: {
    fontSize: 12,
    color: '#E9D5FF',
  },
  // Spread info
  spreadInfo: {
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 16,
    marginBottom: 10,
  },
  spreadIcon: {
    fontSize: 32,
    marginBottom: 8,
  },
  spreadName: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  spreadDesc: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.6)',
    textAlign: 'center',
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
