/**
 * Tarot Card Selection Screen - หน้าเลือกไพ่ทาโรต์
 * Animation สวยงาม พร้อมเอฟเฟกต์ mystical
 */

import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
  View,
  Text,
  Pressable,
  StyleSheet,
  Dimensions,
  Animated,
  StatusBar,
  Alert,
  PanResponder,
} from 'react-native';
import { router, useLocalSearchParams } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import * as Haptics from 'expo-haptics';

const { width, height } = Dimensions.get('window');
const CARD_WIDTH = 70;
const CARD_HEIGHT = 110;
const TOTAL_CARDS = 22; // Major Arcana
const CARDS_NEEDED = 3; // จำนวนไพ่ที่ต้องเลือก

// Card Component with Flip Animation
const TarotCard = ({
  index,
  isSelected,
  isRevealed,
  onSelect,
  cardPosition,
}: {
  index: number;
  isSelected: boolean;
  isRevealed: boolean;
  onSelect: () => void;
  cardPosition: { x: number; y: number; rotation: number };
}) => {
  const flipAnim = useRef(new Animated.Value(0)).current;
  const scaleAnim = useRef(new Animated.Value(1)).current;
  const glowAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    if (isSelected) {
      // Flip animation
      Animated.sequence([
        Animated.timing(scaleAnim, {
          toValue: 1.2,
          duration: 200,
          useNativeDriver: true,
        }),
        Animated.parallel([
          Animated.timing(flipAnim, {
            toValue: 1,
            duration: 400,
            useNativeDriver: true,
          }),
          Animated.timing(scaleAnim, {
            toValue: 1,
            duration: 400,
            useNativeDriver: true,
          }),
        ]),
      ]).start();

      // Glow effect
      Animated.loop(
        Animated.sequence([
          Animated.timing(glowAnim, {
            toValue: 1,
            duration: 1000,
            useNativeDriver: true,
          }),
          Animated.timing(glowAnim, {
            toValue: 0,
            duration: 1000,
            useNativeDriver: true,
          }),
        ])
      ).start();
    }
  }, [isSelected, flipAnim, scaleAnim, glowAnim]);

  const frontInterpolate = flipAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '180deg'],
  });

  const backInterpolate = flipAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['180deg', '360deg'],
  });

  const glowOpacity = glowAnim.interpolate({
    inputRange: [0, 1],
    outputRange: [0.3, 0.8],
  });

  return (
    <Pressable
      onPress={onSelect}
      disabled={isSelected}
      style={[
        styles.cardWrapper,
        {
          left: cardPosition.x,
          top: cardPosition.y,
          transform: [{ rotate: `${cardPosition.rotation}deg` }],
        },
      ]}
    >
      <Animated.View
        style={[
          styles.cardContainer,
          {
            transform: [{ scale: scaleAnim }],
          },
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

        {/* Card Back */}
        <Animated.View
          style={[
            styles.card,
            styles.cardBack,
            { transform: [{ rotateY: frontInterpolate }] },
          ]}
        >
          <LinearGradient
            colors={['#4C1D95', '#7C3AED', '#4C1D95']}
            style={styles.cardBackGradient}
          >
            <View style={styles.cardBackPattern}>
              <Text style={styles.cardBackSymbol}>✦</Text>
              <View style={styles.cardBackBorder}>
                <Text style={styles.cardBackCenter}>☽</Text>
              </View>
              <Text style={styles.cardBackSymbol}>✦</Text>
            </View>
          </LinearGradient>
        </Animated.View>

        {/* Card Front (shown when selected) */}
        <Animated.View
          style={[
            styles.card,
            styles.cardFront,
            { transform: [{ rotateY: backInterpolate }] },
          ]}
        >
          <LinearGradient
            colors={['#FEF3C7', '#FDE68A', '#FCD34D']}
            style={styles.cardFrontGradient}
          >
            <Text style={styles.cardNumber}>{index + 1}</Text>
            <Text style={styles.cardSymbol}>🌟</Text>
          </LinearGradient>
        </Animated.View>
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
  const { categoryName } = params;

  const [selectedCards, setSelectedCards] = useState<number[]>([]);
  const [cardPositions, setCardPositions] = useState<
    Array<{ x: number; y: number; rotation: number }>
  >([]);
  const [isComplete, setIsComplete] = useState(false);

  const progressAnim = useRef(new Animated.Value(0)).current;
  const headerOpacity = useRef(new Animated.Value(0)).current;

  // Generate card positions in arc formation
  useEffect(() => {
    const positions = [];
    const centerX = width / 2 - CARD_WIDTH / 2;
    const centerY = height / 2 - CARD_HEIGHT / 2 + 50;
    const radius = Math.min(width, height) * 0.35;

    for (let i = 0; i < TOTAL_CARDS; i++) {
      const angle = (Math.PI * (i / (TOTAL_CARDS - 1))) - Math.PI / 2;
      const x = centerX + Math.cos(angle) * radius - (CARD_WIDTH / 2) * Math.cos(angle);
      const y = centerY + Math.sin(angle) * radius * 0.6;
      const rotation = ((i - (TOTAL_CARDS - 1) / 2) / (TOTAL_CARDS - 1)) * 60;

      positions.push({ x, y, rotation });
    }

    setCardPositions(positions);

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
      toValue: selectedCards.length / CARDS_NEEDED,
      duration: 300,
      useNativeDriver: false,
    }).start();

    if (selectedCards.length === CARDS_NEEDED) {
      setIsComplete(true);
    }
  }, [selectedCards, progressAnim]);

  const handleCardSelect = useCallback((index: number) => {
    if (selectedCards.includes(index) || selectedCards.length >= CARDS_NEEDED) {
      return;
    }

    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Medium);
    setSelectedCards((prev) => [...prev, index]);
  }, [selectedCards]);

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
          },
        },
      ]
    );
  };

  const progressWidth = progressAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0%', '100%'],
  });

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" />

      {/* Background */}
      <LinearGradient
        colors={['#0F0F23', '#1A1A2E', '#0F0F23']}
        style={StyleSheet.absoluteFill}
      />

      {/* Floating Particles */}
      {[...Array(15)].map((_, i) => (
        <FloatingParticle key={i} delay={i * 500} />
      ))}

      {/* Header */}
      <Animated.View style={[styles.header, { opacity: headerOpacity }]}>
        <Pressable style={styles.backButton} onPress={() => router.back()}>
          <Ionicons name="arrow-back" size={24} color="#fff" />
        </Pressable>

        <View style={styles.headerContent}>
          <Text style={styles.headerTitle}>{categoryName || 'เลือกไพ่'}</Text>
          <Text style={styles.headerSubtitle}>
            เลือกไพ่ {CARDS_NEEDED} ใบ ({selectedCards.length}/{CARDS_NEEDED})
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
          {[...Array(CARDS_NEEDED)].map((_, i) => (
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

      {/* Instructions */}
      <View style={styles.instructionContainer}>
        <Text style={styles.instruction}>
          {isComplete
            ? '✨ เลือกครบแล้ว! กดดูผลด้านล่าง'
            : '👆 แตะไพ่เพื่อเลือก'}
        </Text>
      </View>

      {/* Cards Area */}
      <View style={styles.cardsArea}>
        {cardPositions.map((position, index) => (
          <TarotCard
            key={index}
            index={index}
            isSelected={selectedCards.includes(index)}
            isRevealed={false}
            onSelect={() => handleCardSelect(index)}
            cardPosition={position}
          />
        ))}
      </View>

      {/* Selected Cards Display */}
      <View style={styles.selectedCardsContainer}>
        <Text style={styles.selectedLabel}>ไพ่ที่เลือก:</Text>
        <View style={styles.selectedCardsRow}>
          {[...Array(CARDS_NEEDED)].map((_, i) => (
            <View
              key={i}
              style={[
                styles.selectedCardSlot,
                selectedCards[i] !== undefined && styles.selectedCardSlotFilled,
              ]}
            >
              {selectedCards[i] !== undefined && (
                <Text style={styles.selectedCardNumber}>
                  {selectedCards[i] + 1}
                </Text>
              )}
            </View>
          ))}
        </View>
      </View>

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
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  headerSubtitle: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.7)',
    marginTop: 4,
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
    justifyContent: 'space-around',
    marginTop: 10,
  },
  progressDot: {
    width: 12,
    height: 12,
    borderRadius: 6,
    backgroundColor: 'rgba(255,255,255,0.2)',
    borderWidth: 2,
    borderColor: 'rgba(255,255,255,0.3)',
  },
  progressDotFilled: {
    backgroundColor: '#8B5CF6',
    borderColor: '#EC4899',
  },
  instructionContainer: {
    alignItems: 'center',
    paddingVertical: 15,
  },
  instruction: {
    fontSize: 16,
    color: 'rgba(255,255,255,0.8)',
  },
  cardsArea: {
    flex: 1,
    position: 'relative',
  },
  cardWrapper: {
    position: 'absolute',
  },
  cardContainer: {
    width: CARD_WIDTH,
    height: CARD_HEIGHT,
    position: 'relative',
  },
  cardGlow: {
    position: 'absolute',
    top: -8,
    left: -8,
    right: -8,
    bottom: -8,
    borderRadius: 12,
    backgroundColor: '#8B5CF6',
    shadowColor: '#8B5CF6',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 1,
    shadowRadius: 20,
  },
  card: {
    position: 'absolute',
    width: '100%',
    height: '100%',
    backfaceVisibility: 'hidden',
    borderRadius: 8,
    overflow: 'hidden',
  },
  cardBack: {
    zIndex: 1,
  },
  cardFront: {
    zIndex: 0,
  },
  cardBackGradient: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 2,
    borderColor: '#9D4EDD',
    borderRadius: 8,
  },
  cardBackPattern: {
    alignItems: 'center',
  },
  cardBackSymbol: {
    fontSize: 14,
    color: '#FFD700',
  },
  cardBackBorder: {
    width: 40,
    height: 50,
    borderWidth: 1,
    borderColor: '#FFD700',
    borderRadius: 4,
    justifyContent: 'center',
    alignItems: 'center',
    marginVertical: 4,
  },
  cardBackCenter: {
    fontSize: 20,
    color: '#FFD700',
  },
  cardFrontGradient: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 2,
    borderColor: '#D97706',
    borderRadius: 8,
  },
  cardNumber: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#92400E',
  },
  cardSymbol: {
    fontSize: 24,
    marginTop: 4,
  },
  particle: {
    position: 'absolute',
    fontSize: 12,
    color: '#FFD700',
  },
  selectedCardsContainer: {
    paddingHorizontal: 30,
    paddingBottom: 20,
  },
  selectedLabel: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.6)',
    marginBottom: 10,
    textAlign: 'center',
  },
  selectedCardsRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 16,
  },
  selectedCardSlot: {
    width: 50,
    height: 70,
    borderRadius: 8,
    borderWidth: 2,
    borderColor: 'rgba(255,255,255,0.2)',
    borderStyle: 'dashed',
    justifyContent: 'center',
    alignItems: 'center',
  },
  selectedCardSlotFilled: {
    borderStyle: 'solid',
    borderColor: '#8B5CF6',
    backgroundColor: 'rgba(139, 92, 246, 0.2)',
  },
  selectedCardNumber: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  continueContainer: {
    paddingHorizontal: 30,
    paddingBottom: 40,
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
    paddingVertical: 18,
    gap: 12,
  },
  continueText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
});
