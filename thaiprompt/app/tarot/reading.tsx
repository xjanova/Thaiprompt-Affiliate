/**
 * Tarot Reading Result Screen - หน้าแสดงผลการอ่านไพ่ 78 ใบ
 * รองรับทุกโหมดการเปิดไพ่ (1, 3, 5, 10 ใบ)
 *
 * หน้าตา: ธีมรอยัล "มิดไนท์-ทอง" — ไพ่แต่ละใบอยู่บนแผงกระจก หน้าไพ่ทองฟอยล์ + สัญลักษณ์ชุดไพ่
 * คำทำนายอยู่ในการ์ดขอบทอง แบ่งย่อหน้า/หัวข้อให้อ่านง่าย (ข้อความเดิมทุกคำ)
 */

import React, { useState, useEffect, useRef, useMemo } from 'react';
import {
  View,
  ScrollView,
  StyleSheet,
  Animated,
  StatusBar,
  Share,
  ActivityIndicator,
} from 'react-native';
import { Text } from '@/components/ui/Text';
import { router, useLocalSearchParams } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import * as Haptics from 'expo-haptics';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

// นำเข้าข้อมูลไพ่ 78 ใบจาก tarotData.ts
import {
  TarotCard,
  SpreadPosition,
  getCardById,
  getSpreadBySlug,
  SPREAD_TYPES,
} from '../../data/tarotData';
import { BrandArt, Button3D, GlassIconButton, Icon, OnHeaderProvider, Pill } from '@/components/ui';
import { useTheme, glowStyle, radii, spacing, typography, withAlpha } from '@/theme';
import { GlassPanel, GlowHalo, GoldDivider, Medallion, MysticBackground } from '@/components/tarot/MysticUI';
import { TarotCardFace, TarotGlyph } from '@/components/tarot/TarotCardArt';
import { glyphKindOf, spreadIcon } from '@/components/tarot/tarotVisuals';

// Default positions fallback (สำหรับกรณีไม่มี spread)
const DEFAULT_POSITIONS: SpreadPosition[] = [
  { name_en: 'Past', name_th: 'อดีต', description_th: 'สิ่งที่ผ่านมาและส่งผลต่อปัจจุบัน' },
  { name_en: 'Present', name_th: 'ปัจจุบัน', description_th: 'สถานการณ์ที่คุณกำลังเผชิญ' },
  { name_en: 'Future', name_th: 'อนาคต', description_th: 'แนวโน้มและสิ่งที่รออยู่ข้างหน้า' },
];

/** ความกว้างหน้าไพ่ในการ์ดผลอ่าน */
const FACE_WIDTH = 112;

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
  const { colors, gradients } = useTheme();
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

  // แสดง suit badge สำหรับ Minor Arcana (ป้ายน้ำเงินขอบทอง + สัญลักษณ์ชุดไพ่)
  const getSuitBadge = () => {
    if (card.type === 'major_arcana') return null;
    const suitNames: Record<string, string> = {
      wands: 'ไม้เท้า',
      cups: 'ถ้วย',
      swords: 'ดาบ',
      pentacles: 'เหรียญ',
    };
    return (
      <LinearGradient
        colors={gradients.navy}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={[styles.suitBadge, { borderColor: withAlpha(colors.gold, 0.7) }]}
      >
        <TarotGlyph kind={glyphKindOf(card)} size={13} color={colors.goldLight} strokeWidth={1.8} />
        <Text style={[styles.suitBadgeText, { color: colors.goldLight }]}>{suitNames[card.suit || '']}</Text>
      </LinearGradient>
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
      <GlassPanel padding={spacing.xl} radius={radii.xxl}>
        {/* Position Badge */}
        <LinearGradient colors={gradients.gold} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.positionBadge}>
          <Text style={[styles.positionNumber, { color: colors.textOnGold }]}>{index + 1}</Text>
        </LinearGradient>

        {/* Position Name */}
        <Text style={[typography.serifSm, styles.center, styles.positionName, { color: colors.onHeader }]}>
          {position.name_th}
        </Text>
        <Text style={[typography.caption, styles.center, styles.positionDesc, { color: colors.onHeaderMuted }]}>
          {position.description_th}
        </Text>

        {/* Card Display */}
        <View style={styles.cardDisplay}>
          <View style={[styles.faceShadow, glowStyle(colors.gold, 0.7)]}>
            <TarotCardFace card={card} width={FACE_WIDTH} />
          </View>
          {getSuitBadge()}
        </View>

        {/* Card Name */}
        <Text style={[typography.serif, styles.center, { color: colors.onHeader }]}>{card.name_th}</Text>
        <Text style={[typography.caption, styles.center, styles.cardNameEn, { color: colors.goldLight }]}>
          {card.name_en}
        </Text>

        {/* Card Type Badge */}
        <Pill
          label={card.type === 'major_arcana' ? 'Major Arcana' : 'Minor Arcana'}
          tone={card.type === 'major_arcana' ? 'gold' : 'info'}
          style={styles.typeBadge}
        />

        {/* Card Meaning */}
        <View style={[styles.meaningContainer, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}>
          <Text style={[typography.caption, styles.meaningLabel, { color: colors.goldLight }]}>ความหมาย (ด้านตั้ง):</Text>
          <Text style={[typography.body, styles.meaningText, { color: colors.onHeader }]}>{card.upright_meaning_th}</Text>
        </View>

        {/* Keywords */}
        <View style={styles.keywordsContainer}>
          <Text style={[typography.caption, styles.keywordsLabel, { color: colors.onHeaderMuted }]}>คำสำคัญ:</Text>
          <View style={styles.keywordsTags}>
            {card.keywords_th.slice(0, 4).map((keyword, i) => (
              <Pill key={i} label={keyword} tone="gold" />
            ))}
          </View>
        </View>
      </GlassPanel>
    </Animated.View>
  );
};

/**
 * แสดงคำทำนายเป็นย่อหน้า (แยกด้วยบรรทัดว่าง)
 * ย่อหน้าที่บรรทัดแรกลงท้ายด้วย ":" = หัวข้อทอง + เนื้อความ · ย่อหน้าแรกที่เป็นบรรทัดเดียว = หัวเรื่อง
 */
const InterpretationBody = ({ text }: { text: string }) => {
  const { colors } = useTheme();
  const paragraphs = text.split('\n\n').filter((p) => p.trim().length > 0);

  return (
    <View style={styles.interpretationBody}>
      {paragraphs.map((paragraph, i) => {
        const [firstLine, ...rest] = paragraph.split('\n');
        const isHeading = rest.length > 0 && firstLine.trim().endsWith(':');
        if (i === 0 && rest.length === 0 && paragraphs.length > 1) {
          return (
            <Text key={i} style={[typography.serifSm, { color: colors.goldLight }]}>
              {firstLine}
            </Text>
          );
        }
        return (
          <View key={i}>
            {isHeading && (
              <Text style={[typography.bodyStrong, styles.paragraphHeading, { color: colors.goldLight }]}>{firstLine}</Text>
            )}
            <Text style={[typography.body, styles.interpretationText, { color: colors.onHeader }]}>
              {isHeading ? rest.join('\n') : paragraph}
            </Text>
          </View>
        );
      })}
    </View>
  );
};

export default function ReadingScreen() {
  const { colors, gradients } = useTheme();
  const insets = useSafeAreaInsets();
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
    // ตัวจับเวลาของคำทำนายที่ค่อยๆ ปรากฏ — ต้องยกเลิกด้วยเมื่อออกจากหน้า
    let revealTimer: ReturnType<typeof setTimeout> | undefined;

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

      revealTimer = setTimeout(() => {
        Animated.timing(interpretationOpacity, {
          toValue: 1,
          duration: 600,
          useNativeDriver: true,
        }).start();
      }, cards.length * 300 + 500);
    }, 1500);

    return () => {
      clearTimeout(timer);
      if (revealTimer) clearTimeout(revealTimer);
    };
  }, [cards, positions, categoryName, headerOpacity, interpretationOpacity]);

  // สร้างคำทำนายแบบ dynamic ตามจำนวนไพ่
  const generateInterpretation = (
    cards: TarotCard[],
    positions: SpreadPosition[],
    category: string
  ): string => {
    if (cards.length === 0) {
      return 'ไม่พบข้อมูลไพ่ กรุณาลองใหม่อีกครั้ง';
    }

    // สำหรับไพ่ใบเดียว
    if (cards.length === 1) {
      const card = cards[0];
      return `การทำนายของคุณ\n\n` +
        `ไพ่ "${card.name_th}" (${card.name_en}) ได้ปรากฏขึ้นเพื่อตอบคำถามของคุณ\n\n` +
        `${card.upright_meaning_th}\n\n` +
        `คำสำคัญ: ${card.keywords_th.join(', ')}\n\n` +
        `คำแนะนำ: จงใคร่ครวญความหมายของไพ่นี้ และปรับใช้กับสถานการณ์ของคุณ`;
    }

    // สำหรับหลายไพ่
    let text = `การทำนายของคุณ\n\n`;

    cards.forEach((card, index) => {
      const pos = positions[index];
      if (pos) {
        text += `${pos.name_th}:\n`;
        text += `ไพ่ "${card.name_th}" บ่งบอกว่า ${card.keywords_th.slice(0, 2).join(' และ ')} `;
        text += `กำลังมีบทบาทสำคัญในช่วงนี้\n\n`;
      }
    });

    // สรุป
    text += `คำแนะนำรวม:\n`;
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
          `ผลดูดวงไพ่ทาโรต์\n\n` +
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

  const heroEnd = gradients.hero[gradients.hero.length - 1];

  if (loading) {
    return (
      <View style={[styles.loadingContainer, { backgroundColor: heroEnd }]}>
        <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />
        <MysticBackground stars="twinkle" />
        <View style={styles.loadingArt}>
          <GlowHalo size={170} />
          <BrandArt name="tarot" size={128} />
        </View>
        <ActivityIndicator size="large" color={colors.gold} />
        <Text style={[typography.h2, styles.loadingText, { color: colors.onHeader }]}>กำลังอ่านไพ่...</Text>
        <Text style={[typography.bodySm, styles.loadingSubtext, { color: colors.onHeaderMuted }]}>
          โปรดรอสักครู่ขณะที่เรากำลังตีความไพ่ของคุณ
        </Text>
      </View>
    );
  }

  return (
    <OnHeaderProvider value>
      <View style={[styles.container, { backgroundColor: heroEnd }]}>
        <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />

        {/* Background */}
        <MysticBackground stars="twinkle" />

        <ScrollView
          style={styles.scrollView}
          contentContainerStyle={[
            styles.scrollContent,
            { paddingTop: insets.top + spacing.sm, paddingBottom: insets.bottom + spacing.xxxl },
          ]}
          showsVerticalScrollIndicator={false}
        >
          {/* Header */}
          <Animated.View style={[styles.header, { opacity: headerOpacity }]}>
            <GlassIconButton icon="caret-left" weight="bold" accessibilityLabel="ย้อนกลับ" onPress={() => router.back()} />

            <View style={styles.headerContent}>
              <Text accessibilityRole="header" style={[typography.serif, styles.center, { color: colors.onHeader }]}>
                ผลการอ่านไพ่
              </Text>
              <Text numberOfLines={1} style={[typography.bodySm, styles.center, { color: colors.onHeaderMuted }]}>
                {categoryName}
              </Text>
            </View>

            <GlassIconButton icon="share-network" accessibilityLabel="แชร์ผลดูดวง" onPress={handleShare} />
          </Animated.View>

          {/* Spread Info */}
          {spread && (
            <View style={styles.spreadInfo}>
              <Medallion icon={spreadIcon(spread.slug)} size={56} style={styles.spreadIcon} />
              <Text style={[typography.serifSm, styles.center, { color: colors.goldLight }]}>{spread.name_th}</Text>
              <Text style={[typography.bodySm, styles.center, { color: colors.onHeaderMuted }]}>{spread.description_th}</Text>
            </View>
          )}

          <GoldDivider style={styles.divider} />

          {/* Cards Section */}
          <View style={styles.cardsSection}>
            <View style={styles.sectionTitleRow}>
              <Icon name="cards" size={20} color={colors.goldLight} />
              <Text style={[typography.h2, { color: colors.onHeader }]}>
                ไพ่ที่คุณเลือก ({cards.length} ใบ)
              </Text>
            </View>

            {cards.map((card, i) => (
              <ReadingCard
                key={card.id}
                card={card}
                position={positions[i] || DEFAULT_POSITIONS[0]}
                index={i}
              />
            ))}
          </View>

          {/* Interpretation Section — การ์ดขอบทอง */}
          <Animated.View
            style={[styles.interpretationSection, { opacity: interpretationOpacity }]}
          >
            <LinearGradient
              colors={gradients.goldBorder}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={styles.interpretationBorder}
            >
              <View style={[styles.interpretationCard, { backgroundColor: gradients.hero[1] }]}>
                <LinearGradient
                  colors={gradients.glass}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 0, y: 1 }}
                  style={StyleSheet.absoluteFill}
                  pointerEvents="none"
                />
                <View style={styles.interpretationTitleRow}>
                  <Icon name="book-open" size={22} color={colors.goldLight} />
                  <Text style={[typography.serif, { color: colors.onHeader }]}>คำทำนาย</Text>
                </View>
                <InterpretationBody text={interpretation} />
              </View>
            </LinearGradient>
          </Animated.View>

          {/* Action Buttons */}
          <View style={styles.actionSection}>
            <Button3D
              title="ดูดวงใหม่"
              icon="arrows-clockwise"
              size="lg"
              fullWidth
              onPress={handleNewReading}
            />
            <Button3D
              title="กลับหน้าหลัก"
              variant="ghost"
              icon="house"
              fullWidth
              onPress={() => router.replace('/(tabs)')}
            />
          </View>

          {/* Footer */}
          <View style={styles.footer}>
            <View style={styles.footerRow}>
              <Icon name="moon" size={14} color={colors.onHeaderMuted} />
              <Text style={[typography.caption, styles.footerText, { color: colors.onHeaderMuted }]}>
                ผลการทำนายเป็นเพียงแนวทางในการดำเนินชีวิต
              </Text>
            </View>
            <Text style={[typography.caption, styles.footerText, { color: colors.onHeaderMuted }]}>
              ความสำเร็จขึ้นอยู่กับการกระทำของคุณเอง
            </Text>
          </View>
        </ScrollView>
      </View>
    </OnHeaderProvider>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  center: {
    textAlign: 'center',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingArt: {
    width: 180,
    height: 180,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.xl,
  },
  loadingText: {
    marginTop: spacing.xl,
  },
  loadingSubtext: {
    marginTop: spacing.sm,
    textAlign: 'center',
    paddingHorizontal: 40,
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: spacing.screen,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.md,
    paddingBottom: spacing.lg,
  },
  headerContent: {
    flex: 1,
    alignItems: 'center',
  },
  spreadInfo: {
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.sm,
    gap: 2,
  },
  spreadIcon: {
    marginBottom: spacing.sm,
  },
  divider: {
    marginTop: spacing.xl,
    marginBottom: spacing.xl,
  },
  cardsSection: {
    gap: 0,
  },
  sectionTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginBottom: spacing.lg,
  },
  readingCard: {
    marginBottom: spacing.xl,
  },
  positionBadge: {
    position: 'absolute',
    top: spacing.lg,
    left: spacing.lg,
    width: 32,
    height: 32,
    borderRadius: 16,
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 1,
  },
  positionNumber: {
    fontSize: 15,
    fontWeight: '700',
  },
  positionName: {
    marginTop: spacing.xs,
    paddingHorizontal: 36,
  },
  positionDesc: {
    marginTop: 2,
    marginBottom: spacing.lg,
    paddingHorizontal: spacing.lg,
  },
  cardDisplay: {
    alignItems: 'center',
    marginBottom: spacing.xl,
  },
  faceShadow: {
    borderRadius: 9,
  },
  cardNameEn: {
    marginTop: 2,
    letterSpacing: 0.6,
  },
  // Suit badge สำหรับ Minor Arcana
  suitBadge: {
    position: 'absolute',
    bottom: -12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
    borderWidth: 1,
  },
  suitBadgeText: {
    fontSize: 11,
    fontWeight: '700',
  },
  // Type badge
  typeBadge: {
    alignSelf: 'center',
    marginTop: spacing.sm,
  },
  meaningContainer: {
    borderRadius: radii.md,
    borderWidth: 1,
    padding: spacing.lg,
    marginTop: spacing.lg,
  },
  meaningLabel: {
    marginBottom: spacing.xs,
  },
  meaningText: {
    lineHeight: 24,
  },
  // Keywords
  keywordsContainer: {
    marginTop: spacing.md,
  },
  keywordsLabel: {
    marginBottom: spacing.sm,
  },
  keywordsTags: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
  },
  interpretationSection: {
    marginTop: spacing.sm,
  },
  interpretationBorder: {
    borderRadius: radii.xxl,
    padding: 1.5,
  },
  interpretationCard: {
    borderRadius: radii.xxl - 1.5,
    padding: spacing.xxl,
    overflow: 'hidden',
  },
  interpretationTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginBottom: spacing.lg,
  },
  interpretationBody: {
    gap: spacing.lg,
  },
  paragraphHeading: {
    marginBottom: 2,
  },
  interpretationText: {
    lineHeight: 26,
  },
  actionSection: {
    marginTop: spacing.xxxl,
    gap: spacing.md,
  },
  footer: {
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.xxxl,
    alignItems: 'center',
    gap: 4,
  },
  footerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  footerText: {
    textAlign: 'center',
  },
});
