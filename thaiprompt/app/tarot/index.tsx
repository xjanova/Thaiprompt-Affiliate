/**
 * Tarot Home Screen - หน้าหลักดูดวงไพ่ทาโรต์
 * พร้อม 78 ไพ่ครบ, 5 หมวด, 5 โหมด — ดูฟรีทุกหมวด ไม่ต้องเข้าสู่ระบบ (ไพ่และคำทำนายอยู่ในเครื่อง)
 * ท้ายรายการหมวดมีการ์ด "ดูดวงเชิงลึกกับแม่หมอจันทรา" → เปิดแอปจันทรา (components/tarot/JuntraCard.tsx)
 *
 * หน้าตา: ธีมรอยัล "มิดไนท์-ทอง" ทั้งโหมดสว่างและมืด — พื้นน้ำเงินกรมท่า ลายกนกทอง ดาวระยิบ
 * การ์ดหมวดเป็นแผงกระจก + เหรียญตราทอง · ไม่แสดงอีโมจิจากข้อมูลไพ่ (ใช้ไอคอนเส้นแทน)
 */

import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  ScrollView,
  Pressable,
  StyleSheet,
  Animated,
  StatusBar,
  Modal,
} from 'react-native';
import { Text } from '@/components/ui/Text';
import { router } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import {
  TAROT_CATEGORIES,
  SPREAD_TYPES,
  TarotCategory,
  SpreadType,
} from '@/data/tarotData';
import { BrandArt, Button3D, GlassIconButton, Icon, OnHeaderProvider, Pill } from '@/components/ui';
import { useTheme, radii, spacing, typography, withAlpha } from '@/theme';
import { GlassPanel, GlowHalo, GoldDivider, Medallion, MysticBackground } from '@/components/tarot/MysticUI';
import { categoryIcon, spreadIcon } from '@/components/tarot/tarotVisuals';
import { JuntraCard } from '@/components/tarot/JuntraCard';

/** ขั้นตอนการใช้งาน (แสดงเป็นรายการมีเลขกำกับ) */
const HOW_TO_STEPS = [
  'เลือกหมวดหมู่ที่ต้องการดู',
  'เลือกโหมดการเปิดไพ่ (1-10 ใบ)',
  'สัมผัสไพ่จาก 78 ใบเพื่อเลือก',
  'ดูความหมายและคำแนะนำ',
];

/** ป้ายราคา: ฟรี / ฿ราคา */
const priceLabel = (price: number): string => (price === 0 ? 'ฟรี' : `฿${price}`);

// Category Card Component — แผงกระจก + เหรียญตราทอง + ป้ายราคา
const CategoryCard = ({
  category,
  index,
  onPress,
}: {
  category: TarotCategory;
  index: number;
  onPress: () => void;
}) => {
  const { colors } = useTheme();
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
        accessibilityRole="button"
        accessibilityLabel={`${category.name_th} ${priceLabel(category.price)}`}
        style={({ pressed }) => [pressed && styles.cardPressed]}
      >
        <GlassPanel padding={spacing.lg} radius={radii.xl}>
          <View style={styles.cardRow}>
            {/* เหรียญตราประจำหมวด */}
            <Medallion icon={categoryIcon(category.slug)} size={54} />

            <View style={styles.cardBody}>
              <Text numberOfLines={1} style={[typography.serifSm, { color: colors.onHeader }]}>
                {category.name_th}
              </Text>
              <Text numberOfLines={2} style={[typography.bodySm, { color: colors.onHeaderMuted }]}>
                {category.description_th}
              </Text>
            </View>

            <View style={styles.cardSide}>
              {/* Price Badge */}
              <Pill label={priceLabel(category.price)} tone={category.price === 0 ? 'success' : 'gold'} />
              {/* Arrow */}
              <View style={[styles.arrowCircle, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}>
                <Icon name="arrow-right" size={15} color={colors.goldLight} weight="bold" />
              </View>
            </View>
          </View>
        </GlassPanel>
      </Pressable>
    </Animated.View>
  );
};

// Spread Type Card Component — แถวตัวเลือกโหมด (เลือกอยู่ = ขอบทอง + วงติ๊กทอง)
const SpreadCard = ({
  spread,
  isSelected,
  onPress,
}: {
  spread: SpreadType;
  isSelected: boolean;
  onPress: () => void;
}) => {
  const { colors } = useTheme();
  return (
    <Pressable
      onPress={onPress}
      accessibilityRole="radio"
      accessibilityState={{ checked: isSelected }}
      accessibilityLabel={`${spread.name_th} ${spread.card_count} ใบ`}
      style={({ pressed }) => [styles.spreadCard, pressed && styles.spreadPressed]}
    >
      <GlassPanel highlight={isSelected} padding={spacing.md} radius={radii.lg}>
        <View style={styles.spreadRow}>
          <Medallion icon={spreadIcon(spread.slug)} size={40} tone={isSelected ? 'gold' : 'navy'} />
          <Text numberOfLines={1} style={[typography.bodyStrong, styles.flex, { color: colors.onHeader }]}>
            {spread.name_th}
          </Text>
          <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>{spread.card_count} ใบ</Text>
          <View
            style={[
              styles.spreadSelected,
              isSelected
                ? { backgroundColor: colors.gold, borderColor: colors.gold }
                : { borderColor: colors.headerGlassBorder },
            ]}
          >
            {isSelected && <Icon name="check" size={13} color={colors.textOnGold} weight="bold" />}
          </View>
        </View>
      </GlassPanel>
    </Pressable>
  );
};

export default function TarotHomeScreen() {
  const { colors, gradients } = useTheme();
  const insets = useSafeAreaInsets();
  const [showSpreadModal, setShowSpreadModal] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState<TarotCategory | null>(null);
  const [selectedSpread, setSelectedSpread] = useState<SpreadType>(SPREAD_TYPES[1]); // Default: Past, Present, Future

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

  // ดูฟรีไม่ต้องเข้าสู่ระบบ — ตรงกับไทล์หน้าแรก (requiresLogin: false) และไม่มีการบันทึกข้อมูลผู้ใช้
  const handleCategoryPress = (category: TarotCategory) => {
    setSelectedCategory(category);
    setShowSpreadModal(true);
  };

  const handleSpreadSelect = (spread: SpreadType) => {
    setSelectedSpread(spread);
  };

  const handleConfirmSpread = () => {
    setShowSpreadModal(false);

    if (!selectedCategory) return;

    // PLAY-12: ทุกหมวดฟรีในแอป → ไปหน้าเลือกไพ่เลย (ไม่มีการตรวจยอด/หักกระเป๋าเงิน/ปุ่มเติมเงิน)
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
    <OnHeaderProvider value>
      <View style={[styles.container, { backgroundColor: gradients.hero[gradients.hero.length - 1] }]}>
        <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />

        {/* พื้นน้ำเงินกรมท่า + ลายกนก + ดาวระยิบ */}
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
          <Animated.View
            style={[
              styles.header,
              {
                opacity: headerOpacity,
                transform: [{ translateY: headerTranslateY }],
              },
            ]}
          >
            <View style={styles.topBar}>
              {/* Back Button */}
              <GlassIconButton icon="caret-left" weight="bold" accessibilityLabel="ย้อนกลับ" onPress={() => router.back()} />

              {/* PLAY-12: ดูดวงในแอปฟรี — ไม่แสดงยอดกระเป๋าเงิน/ไม่มีการหักเงิน */}
              <Pill label="ดูฟรีทุกหมวด" tone="success" icon="seal-check" size="md" />
            </View>

            {/* ภาพไพ่ 3D ประจำแบรนด์ + รัศมีทอง */}
            <View style={styles.heroArt}>
              <GlowHalo size={176} />
              <BrandArt name="tarot" size={150} />
            </View>

            {/* Title */}
            <Text accessibilityRole="header" style={[typography.serifLg, styles.center, { color: colors.onHeader }]}>
              ดูดวงไพ่ทาโรต์
            </Text>
            <Text style={[typography.bodySm, styles.center, styles.subtitle, { color: colors.onHeaderMuted }]}>
              ไพ่ 78 ใบ · 5 หมวดหมู่ · 5 โหมดการเปิด
            </Text>
            <GoldDivider style={styles.heroDivider} />
          </Animated.View>

          {/* Categories Section */}
          <View style={styles.section}>
            <Text accessibilityRole="header" style={[typography.serif, { color: colors.onHeader }]}>
              เลือกหมวดหมู่
            </Text>
            <Text style={[typography.bodySm, styles.sectionSubtitle, { color: colors.onHeaderMuted }]}>
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

          {/* ดูดวงเชิงลึก → แอปจันทรา (Android เท่านั้น — การ์ดซ่อนตัวเองบน iOS) */}
          <JuntraCard style={styles.section} />

          {/* Info Section — วิธีใช้งาน */}
          <View style={styles.section}>
            <GlassPanel padding={spacing.xl}>
              <View style={styles.infoHead}>
                <Icon name="sparkle" size={20} color={colors.goldLight} weight="fill" />
                <Text style={[typography.h3, { color: colors.onHeader }]}>วิธีใช้งาน</Text>
              </View>
              {HOW_TO_STEPS.map((step, i) => (
                <View key={step} style={styles.stepRow}>
                  <LinearGradient colors={gradients.gold} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.stepNum}>
                    <Text style={[styles.stepNumText, { color: colors.textOnGold }]}>{i + 1}</Text>
                  </LinearGradient>
                  <Text style={[typography.body, styles.flex, { color: colors.onHeaderMuted }]}>{step}</Text>
                </View>
              ))}
            </GlassPanel>
          </View>

          {/* Footer */}
          <View style={styles.footer}>
            <Icon name="moon" size={14} color={colors.onHeaderMuted} />
            <Text style={[typography.caption, styles.footerText, { color: colors.onHeaderMuted }]}>
              ผลการทำนายเป็นเพียงแนวทางเท่านั้น
            </Text>
          </View>
        </ScrollView>

        {/* Spread Selection Modal — แผ่นล่างน้ำเงินกรมท่าขอบทอง */}
        <Modal
          visible={showSpreadModal}
          transparent
          animationType="slide"
          statusBarTranslucent
          onRequestClose={() => setShowSpreadModal(false)}
        >
          <View style={[styles.modalOverlay, { backgroundColor: colors.overlay }]}>
            <View
              style={[
                styles.modalContent,
                { paddingBottom: insets.bottom + spacing.xl, borderColor: withAlpha(colors.gold, 0.4) },
              ]}
            >
              <LinearGradient colors={gradients.hero} start={{ x: 0, y: 0 }} end={{ x: 0, y: 1 }} style={StyleSheet.absoluteFill} />
              <View style={[styles.grabber, { backgroundColor: colors.headerGlassBorder }]} />

              <View style={styles.modalHeader}>
                <Text accessibilityRole="header" style={[typography.serif, styles.flex, { color: colors.onHeader }]}>
                  เลือกโหมดการเปิดไพ่
                </Text>
                <GlassIconButton icon="x" size={36} accessibilityLabel="ปิด" onPress={() => setShowSpreadModal(false)} />
              </View>

              {/* Selected Category Info */}
              {selectedCategory && (
                <GlassPanel padding={spacing.md} radius={radii.lg} style={styles.selectedCategoryInfo}>
                  <View style={styles.spreadRow}>
                    <Medallion icon={categoryIcon(selectedCategory.slug)} size={38} />
                    <Text numberOfLines={1} style={[typography.bodyStrong, styles.flex, { color: colors.onHeader }]}>
                      {selectedCategory.name_th}
                    </Text>
                    <Pill
                      label={priceLabel(selectedCategory.price)}
                      tone={selectedCategory.price === 0 ? 'success' : 'gold'}
                    />
                  </View>
                </GlassPanel>
              )}

              {/* Spread Types */}
              <ScrollView style={styles.spreadList} contentContainerStyle={styles.spreadListContent}>
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
              <GlassPanel padding={spacing.lg} radius={radii.lg} style={styles.spreadDetails}>
                <Text style={[typography.h3, { color: colors.goldLight }]}>
                  {selectedSpread.name_th} - {selectedSpread.card_count} ใบ
                </Text>
                <Text style={[typography.bodySm, styles.spreadDetailsDesc, { color: colors.onHeaderMuted }]}>
                  {selectedSpread.description_th}
                </Text>
                <View style={styles.positionsPreview}>
                  {selectedSpread.positions.map((pos, idx) => (
                    <Pill key={idx} label={pos.name_th} tone="neutral" />
                  ))}
                </View>
              </GlassPanel>

              {/* Confirm Button */}
              <Button3D
                title="เริ่มเปิดไพ่"
                icon="sparkle"
                iconRight="arrow-right"
                size="lg"
                fullWidth
                onPress={handleConfirmSpread}
                style={styles.confirmButton}
              />
            </View>
          </View>
        </Modal>

      </View>
    </OnHeaderProvider>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  flex: {
    flex: 1,
  },
  center: {
    textAlign: 'center',
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: spacing.screen,
  },
  header: {
    alignItems: 'center',
    paddingBottom: spacing.sm,
  },
  topBar: {
    alignSelf: 'stretch',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  heroArt: {
    width: 190,
    height: 190,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: spacing.sm,
    marginBottom: spacing.sm,
  },
  subtitle: {
    marginTop: spacing.xs,
  },
  heroDivider: {
    marginTop: spacing.lg,
  },
  section: {
    marginTop: spacing.xxl,
  },
  sectionSubtitle: {
    marginTop: 2,
    marginBottom: spacing.lg,
  },
  cardContainer: {
    marginBottom: spacing.md,
  },
  cardPressed: {
    opacity: 0.9,
    transform: [{ scale: 0.98 }],
  },
  cardRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  cardBody: {
    flex: 1,
    gap: 2,
  },
  cardSide: {
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    alignSelf: 'stretch',
    gap: spacing.sm,
  },
  arrowCircle: {
    width: 30,
    height: 30,
    borderRadius: 15,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  infoHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  stepRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: 6,
  },
  stepNum: {
    width: 26,
    height: 26,
    borderRadius: 13,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepNumText: {
    fontSize: 12.5,
    fontWeight: '700',
  },
  footer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingVertical: spacing.xxxl,
  },
  footerText: {
    textAlign: 'center',
  },
  // Modal Styles
  modalOverlay: {
    flex: 1,
    justifyContent: 'flex-end',
  },
  modalContent: {
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    borderWidth: 1,
    borderBottomWidth: 0,
    overflow: 'hidden',
    paddingTop: spacing.sm,
    paddingHorizontal: spacing.xl,
    maxHeight: '88%',
  },
  grabber: {
    alignSelf: 'center',
    width: 42,
    height: 4,
    borderRadius: 2,
    marginBottom: spacing.md,
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginBottom: spacing.lg,
  },
  selectedCategoryInfo: {
    marginBottom: spacing.lg,
  },
  spreadList: {
    maxHeight: 236,
    // จอเตี้ย: รายการหดลงเอง ปุ่ม "เริ่มเปิดไพ่" ไม่ถูกดันล้นแผ่น
    flexShrink: 1,
  },
  spreadListContent: {
    paddingBottom: spacing.xs,
  },
  spreadCard: {
    marginBottom: spacing.sm,
  },
  spreadPressed: {
    opacity: 0.85,
  },
  spreadRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  spreadSelected: {
    width: 24,
    height: 24,
    borderRadius: 12,
    borderWidth: 1.5,
    justifyContent: 'center',
    alignItems: 'center',
  },
  spreadDetails: {
    marginTop: spacing.md,
  },
  spreadDetailsDesc: {
    marginTop: 2,
    marginBottom: spacing.md,
  },
  positionsPreview: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  confirmButton: {
    marginTop: spacing.xl,
  },
});
