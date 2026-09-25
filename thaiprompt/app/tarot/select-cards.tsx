/**
 * Tarot Card Selection Screen - หน้าเลือกไพ่ทาโรต์
 * รองรับ 78 ใบ และจำนวนไพ่ตามโหมดที่เลือก
 *
 * หน้าตา: ธีมรอยัล "มิดไนท์-ทอง" — หลังไพ่น้ำเงินกรมท่ากรอบทอง (วาดด้วย SVG)
 * ไพ่ที่เลือกพลิกเป็นหน้าทองฟอยล์ + เลขลำดับ · แถบความคืบหน้าสีทอง · ถาดไพ่ที่เลือกด้านล่าง
 */

import React, { useState, useEffect, useRef, useCallback, useMemo } from 'react';
import {
  View,
  ScrollView,
  Pressable,
  StyleSheet,
  Animated,
  StatusBar,
  Alert,
  FlatList,
  useWindowDimensions,
} from 'react-native';
import { Text } from '@/components/ui/Text';
import { router, useLocalSearchParams } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import * as Haptics from 'expo-haptics';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import {
  ALL_TAROT_CARDS,
  getSpreadBySlug,
  TarotCard,
  SpreadType,
} from '@/data/tarotData';
import { Button3D, GlassIconButton, Icon, OnHeaderProvider } from '@/components/ui';
import { useTheme, spacing, typography, withAlpha } from '@/theme';
import { MysticBackground } from '@/components/tarot/MysticUI';
import { TAROT_CARD_RATIO, TarotCardBack, TarotCardFace } from '@/components/tarot/TarotCardArt';

/** จำนวนไพ่ต่อแถว */
const COLUMNS = 6;
/** ระยะขอบซ้ายขวาของกองไพ่ */
const GRID_PAD = 12;
/** ช่องว่างรอบไพ่แต่ละใบ */
const CARD_GAP = 2.5;
/** ความกว้างไพ่ในถาดไพ่ที่เลือก */
const TRAY_CARD_WIDTH = 46;

// Card Component — หลังไพ่ (ยังไม่เลือก) / หน้าไพ่ทอง + เลขลำดับ (เลือกแล้ว)
const TarotCardItem = ({
  card,
  cellWidth,
  isSelected,
  selectionOrder,
  onSelect,
  disabled,
}: {
  card: TarotCard;
  cellWidth: number;
  isSelected: boolean;
  selectionOrder: number | null;
  onSelect: () => void;
  disabled: boolean;
}) => {
  const { colors, gradients } = useTheme();
  const scaleAnim = useRef(new Animated.Value(1)).current;
  const glowAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    if (!isSelected) {
      glowAnim.setValue(0);
      return undefined;
    }
    // Glow animation — กรอบทองกะพริบช้าๆ (หยุดเมื่อยกเลิกเลือก/ออกจากหน้า)
    const glowLoop = Animated.loop(
      Animated.sequence([
        Animated.timing(glowAnim, {
          toValue: 1,
          duration: 1000,
          useNativeDriver: true,
        }),
        Animated.timing(glowAnim, {
          toValue: 0.5,
          duration: 1000,
          useNativeDriver: true,
        }),
      ])
    );
    glowLoop.start();
    return () => glowLoop.stop();
  }, [isSelected, glowAnim]);

  const handlePress = () => {
    if (disabled || isSelected) return;

    Animated.sequence([
      Animated.timing(scaleAnim, {
        toValue: 0.9,
        duration: 100,
        useNativeDriver: true,
      }),
      Animated.timing(scaleAnim, {
        toValue: 1,
        duration: 100,
        useNativeDriver: true,
      }),
    ]).start();

    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Medium);
    onSelect();
  };

  const glowOpacity = glowAnim.interpolate({
    inputRange: [0, 1],
    outputRange: [0, 0.8],
  });

  const cardWidth = cellWidth - CARD_GAP * 2;

  return (
    <Pressable
      onPress={handlePress}
      disabled={disabled && !isSelected}
      accessibilityRole="button"
      accessibilityLabel={isSelected ? `ไพ่ที่เลือก ลำดับ ${selectionOrder}` : 'ไพ่คว่ำ แตะเพื่อเลือก'}
      accessibilityState={{ selected: isSelected, disabled: disabled && !isSelected }}
      style={[styles.cardWrapper, { width: cellWidth, height: cardWidth * TAROT_CARD_RATIO + CARD_GAP * 2 }]}
    >
      <Animated.View
        style={[
          styles.cardItem,
          { transform: [{ scale: scaleAnim }] },
        ]}
      >
        {isSelected ? (
          // Selected - หน้าไพ่ทอง + เลขลำดับ
          <>
            <TarotCardFace card={card} width={cardWidth} radius={6} detailed={false} />
            {/* Glow Effect */}
            <Animated.View
              pointerEvents="none"
              style={[
                styles.cardGlow,
                { borderColor: colors.goldLight, opacity: glowOpacity },
              ]}
            />
            <LinearGradient
              colors={gradients.navy}
              style={[styles.selectionBadge, { borderColor: withAlpha(colors.gold, 0.8) }]}
            >
              <Text style={[styles.selectionNumber, { color: colors.goldLight }]}>{selectionOrder}</Text>
            </LinearGradient>
          </>
        ) : (
          // Not selected - Show card back
          <TarotCardBack width={cardWidth} radius={6} />
        )}

        {/* Disabled overlay */}
        {disabled && !isSelected && (
          <View style={[styles.disabledOverlay, { backgroundColor: withAlpha(gradients.hero[0], 0.6) }]} />
        )}
      </Animated.View>
    </Pressable>
  );
};

export default function SelectCardsScreen() {
  const { colors, gradients } = useTheme();
  const insets = useSafeAreaInsets();
  const { width } = useWindowDimensions();
  const params = useLocalSearchParams();
  const {
    categoryName,
    spreadSlug,
    spreadName,
    cardCount: cardCountStr,
    categoryPrice,
  } = params;

  const cardCount = parseInt(cardCountStr as string) || 3;
  const spread = useMemo(() => getSpreadBySlug(spreadSlug as string), [spreadSlug]);

  const [selectedCards, setSelectedCards] = useState<number[]>([]);
  const [shuffledCards, setShuffledCards] = useState<TarotCard[]>([]);
  const [isComplete, setIsComplete] = useState(false);

  const progressAnim = useRef(new Animated.Value(0)).current;
  const headerOpacity = useRef(new Animated.Value(0)).current;

  // Shuffle cards on mount
  useEffect(() => {
    const shuffled = [...ALL_TAROT_CARDS].sort(() => Math.random() - 0.5);
    setShuffledCards(shuffled);

    // Header animation
    Animated.timing(headerOpacity, {
      toValue: 1,
      duration: 800,
      useNativeDriver: true,
    }).start();
  }, [headerOpacity]);

  // Update progress bar
  useEffect(() => {
    Animated.timing(progressAnim, {
      toValue: selectedCards.length / cardCount,
      duration: 300,
      useNativeDriver: false,
    }).start();

    if (selectedCards.length === cardCount) {
      setIsComplete(true);
    }
  }, [selectedCards, cardCount, progressAnim]);

  const handleCardSelect = useCallback((cardId: number) => {
    if (selectedCards.includes(cardId) || selectedCards.length >= cardCount) {
      return;
    }

    setSelectedCards((prev) => [...prev, cardId]);
  }, [selectedCards, cardCount]);

  const handleContinue = () => {
    Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    router.push({
      pathname: '/tarot/reading',
      params: {
        ...params,
        selectedCards: selectedCards.join(','),
      },
    });
  };

  const handleReset = () => {
    Alert.alert(
      'เลือกใหม่',
      'คุณต้องการเลือกไพ่ใหม่หรือไม่?',
      [
        { text: 'ยกเลิก', style: 'cancel' },
        {
          text: 'เลือกใหม่',
          onPress: () => {
            setSelectedCards([]);
            setIsComplete(false);
            // Re-shuffle
            const shuffled = [...ALL_TAROT_CARDS].sort(() => Math.random() - 0.5);
            setShuffledCards(shuffled);
          },
        },
      ]
    );
  };

  const progressWidth = progressAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0%', '100%'],
  });

  const getSelectionOrder = (cardId: number): number | null => {
    const index = selectedCards.indexOf(cardId);
    return index !== -1 ? index + 1 : null;
  };

  // ความกว้างช่องไพ่ = แบ่งความกว้างจอ (หักขอบซ้ายขวา) เท่าๆ กัน 6 ช่อง — กองไพ่อยู่กึ่งกลางพอดีทุกขนาดจอ
  const cellWidth = Math.floor(((width - GRID_PAD * 2) / COLUMNS) * 100) / 100;
  const nothingSelected = selectedCards.length === 0;
  const trayBackground = withAlpha(gradients.hero[0], 0.78);

  return (
    <OnHeaderProvider value>
      <View style={[styles.container, { backgroundColor: gradients.hero[gradients.hero.length - 1] }]}>
        <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />

        {/* Background + Floating Sparkles */}
        <MysticBackground stars="rise" />

        {/* Header */}
        <Animated.View style={[styles.header, { paddingTop: insets.top + spacing.sm, opacity: headerOpacity }]}>
          <GlassIconButton icon="caret-left" weight="bold" accessibilityLabel="ย้อนกลับ" onPress={() => router.back()} />

          <View style={styles.headerContent}>
            <Text numberOfLines={1} style={[typography.serifSm, styles.center, { color: colors.onHeader }]}>
              {categoryName}
            </Text>
            <Text numberOfLines={1} style={[typography.caption, styles.center, { color: colors.onHeaderMuted }]}>
              {spreadName} · เลือก {cardCount} ใบ ({selectedCards.length}/{cardCount})
            </Text>
          </View>

          <GlassIconButton
            icon="arrows-clockwise"
            accessibilityLabel="เลือกไพ่ใหม่"
            onPress={nothingSelected ? undefined : handleReset}
            style={nothingSelected && styles.dimmed}
          />
        </Animated.View>

        {/* Progress Bar */}
        <View style={styles.progressContainer}>
          <View style={[styles.progressBackground, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}>
            <Animated.View
              style={[styles.progressFill, { width: progressWidth }]}
            >
              <LinearGradient
                colors={gradients.primary}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={StyleSheet.absoluteFill}
              />
            </Animated.View>
          </View>
          <View style={styles.progressDots}>
            {[...Array(cardCount)].map((_, i) => {
              const filled = selectedCards.length > i;
              return (
                <View
                  key={i}
                  style={[
                    styles.progressDot,
                    filled
                      ? { backgroundColor: colors.gold, borderColor: colors.goldLight }
                      : { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder },
                  ]}
                />
              );
            })}
          </View>
        </View>

        {/* Card Count Info */}
        <View style={styles.cardCountInfo}>
          <Icon name="cards" size={14} color={colors.goldLight} />
          <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>
            ไพ่ทั้งหมด 78 ใบ (22 Major + 56 Minor Arcana)
          </Text>
        </View>

        {/* Instructions */}
        <View style={styles.instructionContainer}>
          <View
            style={[
              styles.instructionPill,
              isComplete
                ? { backgroundColor: withAlpha(colors.gold, 0.16), borderColor: withAlpha(colors.gold, 0.55) }
                : { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder },
            ]}
          >
            <Icon
              name={isComplete ? 'sparkle' : 'hand-tap'}
              size={16}
              color={colors.goldLight}
              weight={isComplete ? 'fill' : 'regular'}
            />
            <Text style={[typography.bodySm, styles.instruction, { color: colors.onHeader }]}>
              {isComplete
                ? 'เลือกครบแล้ว! กดดูผลด้านล่าง'
                : 'แตะไพ่เพื่อเลือก'}
            </Text>
          </View>
        </View>

        {/* Cards Grid */}
        <FlatList
          data={shuffledCards}
          keyExtractor={(item) => item.id.toString()}
          numColumns={COLUMNS}
          style={styles.grid}
          contentContainerStyle={[
            styles.cardsGrid,
            { paddingBottom: nothingSelected ? insets.bottom + spacing.xl : spacing.xl },
          ]}
          showsVerticalScrollIndicator={false}
          renderItem={({ item }) => (
            <TarotCardItem
              card={item}
              cellWidth={cellWidth}
              isSelected={selectedCards.includes(item.id)}
              selectionOrder={getSelectionOrder(item.id)}
              onSelect={() => handleCardSelect(item.id)}
              disabled={selectedCards.length >= cardCount}
            />
          )}
        />

        {/* Selected Cards Display */}
        {selectedCards.length > 0 && (
          <View
            style={[
              styles.selectedCardsContainer,
              {
                backgroundColor: trayBackground,
                borderTopColor: colors.headerGlassBorder,
                paddingBottom: isComplete ? spacing.sm : insets.bottom + spacing.md,
              },
            ]}
          >
            <Text style={[typography.caption, styles.selectedLabel, { color: colors.onHeaderMuted }]}>ไพ่ที่เลือก:</Text>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.selectedCardsRow}
            >
              {spread?.positions.map((pos, i) => {
                const selectedCardId = selectedCards[i];
                const card = selectedCardId
                  ? ALL_TAROT_CARDS.find((c) => c.id === selectedCardId)
                  : null;

                return (
                  <View key={i} style={styles.selectedCardSlot}>
                    {card ? (
                      <View>
                        <TarotCardFace card={card} width={TRAY_CARD_WIDTH} radius={7} detailed={false} />
                        <LinearGradient
                          colors={gradients.navy}
                          style={[styles.selectedOrderBadge, { borderColor: withAlpha(colors.gold, 0.8) }]}
                        >
                          <Text style={[styles.selectedOrderText, { color: colors.goldLight }]}>{i + 1}</Text>
                        </LinearGradient>
                      </View>
                    ) : (
                      <View
                        style={[
                          styles.selectedCardBox,
                          { borderColor: colors.headerGlassBorder, backgroundColor: colors.headerGlass },
                        ]}
                      >
                        <Text style={[typography.h3, { color: colors.onHeaderMuted }]}>?</Text>
                      </View>
                    )}
                    <Text style={[typography.micro, styles.positionLabel, { color: colors.onHeaderMuted }]} numberOfLines={1}>
                      {pos.name_th}
                    </Text>
                  </View>
                );
              })}
            </ScrollView>
          </View>
        )}

        {/* Continue Button */}
        {isComplete && (
          <Animated.View
            style={[
              styles.continueContainer,
              { backgroundColor: trayBackground, paddingBottom: insets.bottom + spacing.lg },
            ]}
          >
            <Button3D
              title="ดูผลทำนาย"
              icon="sparkle"
              iconRight="arrow-right"
              size="lg"
              fullWidth
              onPress={handleContinue}
            />
          </Animated.View>
        )}
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
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.sm,
    gap: spacing.md,
  },
  headerContent: {
    flex: 1,
    alignItems: 'center',
  },
  dimmed: {
    opacity: 0.4,
  },
  progressContainer: {
    paddingHorizontal: 30,
    marginTop: spacing.sm,
  },
  progressBackground: {
    height: 6,
    borderRadius: 3,
    borderWidth: StyleSheet.hairlineWidth,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    borderRadius: 3,
    overflow: 'hidden',
  },
  progressDots: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginTop: 10,
    gap: 8,
  },
  progressDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    borderWidth: 1,
  },
  cardCountInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingTop: spacing.md,
    paddingBottom: spacing.sm,
  },
  instructionContainer: {
    alignItems: 'center',
    paddingBottom: spacing.md,
  },
  instructionPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 999,
    borderWidth: 1,
  },
  instruction: {
    fontWeight: '600',
  },
  grid: {
    flex: 1,
  },
  cardsGrid: {
    paddingHorizontal: GRID_PAD,
  },
  cardWrapper: {
    padding: CARD_GAP,
  },
  cardItem: {
    flex: 1,
    borderRadius: 6,
    overflow: 'hidden',
    position: 'relative',
  },
  cardGlow: {
    ...StyleSheet.absoluteFill,
    borderRadius: 6,
    borderWidth: 2,
  },
  selectionBadge: {
    position: 'absolute',
    top: 3,
    right: 3,
    width: 17,
    height: 17,
    borderRadius: 9,
    borderWidth: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  selectionNumber: {
    fontSize: 10,
    fontWeight: 'bold',
  },
  disabledOverlay: {
    ...StyleSheet.absoluteFill,
    borderRadius: 6,
  },
  selectedCardsContainer: {
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.md,
    borderTopWidth: StyleSheet.hairlineWidth,
  },
  selectedLabel: {
    marginBottom: spacing.sm,
  },
  selectedCardsRow: {
    flexDirection: 'row',
    gap: spacing.md,
    paddingTop: 6,
    paddingRight: spacing.xl,
  },
  selectedCardSlot: {
    alignItems: 'center',
  },
  selectedCardBox: {
    width: TRAY_CARD_WIDTH,
    height: TRAY_CARD_WIDTH * TAROT_CARD_RATIO,
    borderRadius: 7,
    borderWidth: 1.5,
    borderStyle: 'dashed',
    justifyContent: 'center',
    alignItems: 'center',
  },
  selectedOrderBadge: {
    position: 'absolute',
    top: -6,
    right: -6,
    width: 19,
    height: 19,
    borderRadius: 10,
    borderWidth: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  selectedOrderText: {
    fontSize: 10,
    fontWeight: 'bold',
  },
  positionLabel: {
    marginTop: 4,
    maxWidth: 56,
    textAlign: 'center',
  },
  continueContainer: {
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.sm,
  },
});
