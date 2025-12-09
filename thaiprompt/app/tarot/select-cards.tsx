/**
 * Tarot Card Selection Screen - หน้าเลือกไพ่ทาโรต์
 * รองรับ 78 ใบ และจำนวนไพ่ตามโหมดที่เลือก
 */

import React, { useState, useEffect, useRef, useCallback, useMemo } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  StyleSheet,
  Dimensions,
  Animated,
  StatusBar,
  Alert,
  FlatList,
} from 'react-native';
import { router, useLocalSearchParams } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import * as Haptics from 'expo-haptics';
import {
  ALL_TAROT_CARDS,
  getSpreadBySlug,
  TarotCard,
  SpreadType,
} from '@/data/tarotData';

const { width, height } = Dimensions.get('window');
const CARD_SIZE = (width - 60) / 6; // 6 cards per row

// Card Component
const TarotCardItem = ({
  card,
  isSelected,
  selectionOrder,
  onSelect,
  disabled,
}: {
  card: TarotCard;
  isSelected: boolean;
  selectionOrder: number | null;
  onSelect: () => void;
  disabled: boolean;
}) => {
  const scaleAnim = useRef(new Animated.Value(1)).current;
  const glowAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    if (isSelected) {
      // Glow animation
      Animated.loop(
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
      ).start();
    } else {
      glowAnim.setValue(0);
    }
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

  return (
    <Pressable
      onPress={handlePress}
      disabled={disabled && !isSelected}
      style={styles.cardWrapper}
    >
      <Animated.View
        style={[
          styles.cardItem,
          { transform: [{ scale: scaleAnim }] },
        ]}
      >
        {/* Glow Effect */}
        {isSelected && (
          <Animated.View
            style={[
              styles.cardGlow,
              { opacity: glowOpacity },
            ]}
          />
        )}

        <LinearGradient
          colors={
            isSelected
              ? ['#8B5CF6', '#EC4899']
              : ['#4C1D95', '#7C3AED', '#4C1D95']
          }
          style={styles.cardGradient}
        >
          {isSelected ? (
            // Selected - Show order number and icon
            <>
              <View style={styles.selectionBadge}>
                <Text style={styles.selectionNumber}>{selectionOrder}</Text>
              </View>
              <Text style={styles.cardIconSelected}>{card.icon}</Text>
            </>
          ) : (
            // Not selected - Show card back
            <View style={styles.cardBackPattern}>
              <Text style={styles.cardBackSymbol}>✦</Text>
              <View style={styles.cardBackBorder}>
                <Text style={styles.cardBackCenter}>☽</Text>
              </View>
              <Text style={styles.cardBackSymbol}>✦</Text>
            </View>
          )}
        </LinearGradient>

        {/* Disabled overlay */}
        {disabled && !isSelected && (
          <View style={styles.disabledOverlay} />
        )}
      </Animated.View>
    </Pressable>
  );
};

// Floating Particle Component
const FloatingParticle = ({ delay }: { delay: number }) => {
  const translateY = useRef(new Animated.Value(height)).current;
  const translateX = useRef(new Animated.Value(Math.random() * width)).current;
  const opacity = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    const animate = () => {
      translateY.setValue(height);
      translateX.setValue(Math.random() * width);
      opacity.setValue(0);

      Animated.parallel([
        Animated.timing(translateY, {
          toValue: -50,
          duration: 8000 + Math.random() * 4000,
          useNativeDriver: true,
        }),
        Animated.sequence([
          Animated.delay(delay),
          Animated.timing(opacity, {
            toValue: 0.8,
            duration: 1000,
            useNativeDriver: true,
          }),
          Animated.delay(5000),
          Animated.timing(opacity, {
            toValue: 0,
            duration: 1000,
            useNativeDriver: true,
          }),
        ]),
      ]).start(() => animate());
    };

    const timer = setTimeout(animate, delay);
    return () => clearTimeout(timer);
  }, [delay, translateY, translateX, opacity]);

  return (
    <Animated.Text
      style={[
        styles.particle,
        {
          transform: [{ translateX }, { translateY }],
          opacity,
        },
      ]}
    >
      ✦
    </Animated.Text>
  );
};

export default function SelectCardsScreen() {
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

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" />

      {/* Background */}
      <LinearGradient
        colors={['#0F0F23', '#1A1A2E', '#0F0F23']}
        style={StyleSheet.absoluteFill}
      />

      {/* Floating Particles */}
      {[...Array(10)].map((_, i) => (
        <FloatingParticle key={i} delay={i * 300} />
      ))}

      {/* Header */}
      <Animated.View style={[styles.header, { opacity: headerOpacity }]}>
        <Pressable style={styles.backButton} onPress={() => router.back()}>
          <Ionicons name="arrow-back" size={24} color="#fff" />
        </Pressable>

        <View style={styles.headerContent}>
          <Text style={styles.headerTitle}>{categoryName}</Text>
          <Text style={styles.headerSubtitle}>
            {spreadName} • เลือก {cardCount} ใบ ({selectedCards.length}/{cardCount})
          </Text>
        </View>

        <Pressable
          style={styles.resetButton}
          onPress={handleReset}
          disabled={selectedCards.length === 0}
        >
          <Ionicons
            name="refresh"
            size={24}
            color={selectedCards.length === 0 ? 'rgba(255,255,255,0.3)' : '#fff'}
          />
        </Pressable>
      </Animated.View>

      {/* Progress Bar */}
      <View style={styles.progressContainer}>
        <View style={styles.progressBackground}>
          <Animated.View
            style={[styles.progressFill, { width: progressWidth }]}
          >
            <LinearGradient
              colors={['#8B5CF6', '#EC4899']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={StyleSheet.absoluteFill}
            />
          </Animated.View>
        </View>
        <View style={styles.progressDots}>
          {[...Array(cardCount)].map((_, i) => (
            <View
              key={i}
              style={[
                styles.progressDot,
                selectedCards.length > i && styles.progressDotFilled,
              ]}
            />
          ))}
        </View>
      </View>

      {/* Card Count Info */}
      <View style={styles.cardCountInfo}>
        <Text style={styles.cardCountText}>
          🃏 ไพ่ทั้งหมด 78 ใบ (22 Major + 56 Minor Arcana)
        </Text>
      </View>

      {/* Instructions */}
      <View style={styles.instructionContainer}>
        <Text style={styles.instruction}>
          {isComplete
            ? '✨ เลือกครบแล้ว! กดดูผลด้านล่าง'
            : '👆 แตะไพ่เพื่อเลือก'}
        </Text>
      </View>

      {/* Cards Grid */}
      <FlatList
        data={shuffledCards}
        keyExtractor={(item) => item.id.toString()}
        numColumns={6}
        contentContainerStyle={styles.cardsGrid}
        showsVerticalScrollIndicator={false}
        renderItem={({ item }) => (
          <TarotCardItem
            card={item}
            isSelected={selectedCards.includes(item.id)}
            selectionOrder={getSelectionOrder(item.id)}
            onSelect={() => handleCardSelect(item.id)}
            disabled={selectedCards.length >= cardCount}
          />
        )}
      />

      {/* Selected Cards Display */}
      {selectedCards.length > 0 && (
        <View style={styles.selectedCardsContainer}>
          <Text style={styles.selectedLabel}>ไพ่ที่เลือก:</Text>
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
                  <View
                    style={[
                      styles.selectedCardBox,
                      card && styles.selectedCardBoxFilled,
                    ]}
                  >
                    {card ? (
                      <>
                        <Text style={styles.selectedCardIcon}>{card.icon}</Text>
                        <View style={styles.selectedOrderBadge}>
                          <Text style={styles.selectedOrderText}>{i + 1}</Text>
                        </View>
                      </>
                    ) : (
                      <Text style={styles.selectedCardEmpty}>?</Text>
                    )}
                  </View>
                  <Text style={styles.positionLabel} numberOfLines={1}>
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
        <Animated.View style={styles.continueContainer}>
          <Pressable
            style={styles.continueButton}
            onPress={handleContinue}
          >
            <LinearGradient
              colors={['#8B5CF6', '#EC4899']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.continueGradient}
            >
              <Text style={styles.continueText}>🔮 ดูผลทำนาย</Text>
              <Ionicons name="arrow-forward" size={24} color="#fff" />
            </LinearGradient>
          </Pressable>
        </Animated.View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingTop: 60,
    paddingHorizontal: 20,
    paddingBottom: 10,
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
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  headerSubtitle: {
    fontSize: 12,
    color: 'rgba(255,255,255,0.7)',
    marginTop: 2,
  },
  resetButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.1)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  progressContainer: {
    paddingHorizontal: 30,
    marginTop: 10,
  },
  progressBackground: {
    height: 6,
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 3,
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
    backgroundColor: 'rgba(255,255,255,0.2)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.3)',
  },
  progressDotFilled: {
    backgroundColor: '#8B5CF6',
    borderColor: '#EC4899',
  },
  cardCountInfo: {
    alignItems: 'center',
    paddingVertical: 8,
  },
  cardCountText: {
    fontSize: 12,
    color: 'rgba(255,255,255,0.5)',
  },
  instructionContainer: {
    alignItems: 'center',
    paddingVertical: 8,
  },
  instruction: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
  },
  cardsGrid: {
    paddingHorizontal: 10,
    paddingBottom: 20,
  },
  cardWrapper: {
    width: CARD_SIZE,
    height: CARD_SIZE * 1.4,
    padding: 2,
  },
  cardItem: {
    flex: 1,
    borderRadius: 6,
    overflow: 'hidden',
    position: 'relative',
  },
  cardGlow: {
    position: 'absolute',
    top: -4,
    left: -4,
    right: -4,
    bottom: -4,
    borderRadius: 10,
    backgroundColor: '#8B5CF6',
    zIndex: -1,
  },
  cardGradient: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(157, 78, 221, 0.5)',
    borderRadius: 6,
  },
  cardBackPattern: {
    alignItems: 'center',
  },
  cardBackSymbol: {
    fontSize: 8,
    color: '#FFD700',
  },
  cardBackBorder: {
    width: 24,
    height: 30,
    borderWidth: 1,
    borderColor: '#FFD700',
    borderRadius: 3,
    justifyContent: 'center',
    alignItems: 'center',
    marginVertical: 2,
  },
  cardBackCenter: {
    fontSize: 12,
    color: '#FFD700',
  },
  selectionBadge: {
    position: 'absolute',
    top: 2,
    right: 2,
    width: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: '#10B981',
    justifyContent: 'center',
    alignItems: 'center',
  },
  selectionNumber: {
    fontSize: 10,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  cardIconSelected: {
    fontSize: 20,
  },
  disabledOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.5)',
    borderRadius: 6,
  },
  particle: {
    position: 'absolute',
    fontSize: 12,
    color: '#FFD700',
  },
  selectedCardsContainer: {
    paddingHorizontal: 20,
    paddingVertical: 12,
    backgroundColor: 'rgba(0,0,0,0.3)',
  },
  selectedLabel: {
    fontSize: 12,
    color: 'rgba(255,255,255,0.6)',
    marginBottom: 8,
  },
  selectedCardsRow: {
    flexDirection: 'row',
    gap: 12,
    paddingRight: 20,
  },
  selectedCardSlot: {
    alignItems: 'center',
  },
  selectedCardBox: {
    width: 50,
    height: 70,
    borderRadius: 8,
    borderWidth: 2,
    borderColor: 'rgba(255,255,255,0.2)',
    borderStyle: 'dashed',
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    position: 'relative',
  },
  selectedCardBoxFilled: {
    borderStyle: 'solid',
    borderColor: '#8B5CF6',
    backgroundColor: 'rgba(139, 92, 246, 0.2)',
  },
  selectedCardIcon: {
    fontSize: 24,
  },
  selectedOrderBadge: {
    position: 'absolute',
    top: -6,
    right: -6,
    width: 18,
    height: 18,
    borderRadius: 9,
    backgroundColor: '#EC4899',
    justifyContent: 'center',
    alignItems: 'center',
  },
  selectedOrderText: {
    fontSize: 10,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  selectedCardEmpty: {
    fontSize: 20,
    color: 'rgba(255,255,255,0.3)',
  },
  positionLabel: {
    fontSize: 10,
    color: 'rgba(255,255,255,0.6)',
    marginTop: 4,
    maxWidth: 50,
    textAlign: 'center',
  },
  continueContainer: {
    paddingHorizontal: 20,
    paddingBottom: 30,
    paddingTop: 10,
  },
  continueButton: {
    borderRadius: 16,
    overflow: 'hidden',
    shadowColor: '#8B5CF6',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.5,
    shadowRadius: 12,
    elevation: 8,
  },
  continueGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 16,
    gap: 12,
  },
  continueText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
});
