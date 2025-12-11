/**
 * หน้า Watch & Earn - ดูคลิปได้เงิน
 * TikTok/YouTube Style Earning Platform
 */

import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  StyleSheet,
  StatusBar,
  Dimensions,
  Image,
  ActivityIndicator,
  Animated,
  Easing,
} from 'react-native';
import { router } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

// รูป Hero
const WATCH_EARN_HERO = require('@/assets/images/2bt/watch&earn.png');

interface Video {
  id: string;
  title: string;
  thumbnail: string;
  duration: string;
  reward: number;
  platform: 'tiktok' | 'youtube' | 'facebook';
  views: string;
  isWatched: boolean;
  category: string;
}

// วิดีโอตัวอย่าง
const VIDEOS: Video[] = [
  {
    id: '1',
    title: 'วิธีสร้างรายได้ออนไลน์ 2024',
    thumbnail: '🎬',
    duration: '2:30',
    reward: 5,
    platform: 'tiktok',
    views: '125K',
    isWatched: false,
    category: 'การเงิน',
  },
  {
    id: '2',
    title: 'เคล็ดลับการลงทุนสำหรับมือใหม่',
    thumbnail: '📈',
    duration: '5:45',
    reward: 10,
    platform: 'youtube',
    views: '89K',
    isWatched: false,
    category: 'การลงทุน',
  },
  {
    id: '3',
    title: 'MLM ทำอย่างไรให้สำเร็จ',
    thumbnail: '👥',
    duration: '3:15',
    reward: 7,
    platform: 'tiktok',
    views: '256K',
    isWatched: true,
    category: 'MLM',
  },
  {
    id: '4',
    title: 'รีวิวสินค้าขายดี',
    thumbnail: '🛒',
    duration: '4:20',
    reward: 8,
    platform: 'facebook',
    views: '67K',
    isWatched: false,
    category: 'รีวิว',
  },
  {
    id: '5',
    title: 'Crypto 101 สำหรับผู้เริ่มต้น',
    thumbnail: '🪙',
    duration: '8:00',
    reward: 15,
    platform: 'youtube',
    views: '432K',
    isWatched: false,
    category: 'Crypto',
  },
  {
    id: '6',
    title: 'เทคนิคการขายออนไลน์',
    thumbnail: '💰',
    duration: '3:45',
    reward: 6,
    platform: 'tiktok',
    views: '198K',
    isWatched: true,
    category: 'การขาย',
  },
];

const CATEGORIES = [
  { id: 'all', label: 'ทั้งหมด' },
  { id: 'new', label: 'ใหม่' },
  { id: 'popular', label: 'ยอดนิยม' },
  { id: 'high-reward', label: 'รางวัลสูง' },
];

// Coin Animation Component
const AnimatedCoin = ({ delay }: { delay: number }) => {
  const translateY = useRef(new Animated.Value(0)).current;
  const opacity = useRef(new Animated.Value(0)).current;
  const scale = useRef(new Animated.Value(0.5)).current;

  useEffect(() => {
    const animation = Animated.loop(
      Animated.sequence([
        Animated.delay(delay),
        Animated.parallel([
          Animated.timing(translateY, {
            toValue: -30,
            duration: 1500,
            easing: Easing.out(Easing.ease),
            useNativeDriver: true,
          }),
          Animated.sequence([
            Animated.timing(opacity, {
              toValue: 1,
              duration: 300,
              useNativeDriver: true,
            }),
            Animated.delay(900),
            Animated.timing(opacity, {
              toValue: 0,
              duration: 300,
              useNativeDriver: true,
            }),
          ]),
          Animated.sequence([
            Animated.timing(scale, {
              toValue: 1,
              duration: 300,
              useNativeDriver: true,
            }),
            Animated.delay(900),
            Animated.timing(scale, {
              toValue: 0.5,
              duration: 300,
              useNativeDriver: true,
            }),
          ]),
        ]),
        Animated.timing(translateY, {
          toValue: 0,
          duration: 0,
          useNativeDriver: true,
        }),
      ])
    );
    animation.start();
    return () => animation.stop();
  }, []);

  return (
    <Animated.Text
      style={{
        fontSize: 20,
        position: 'absolute',
        opacity,
        transform: [{ translateY }, { scale }],
      }}
    >
      🪙
    </Animated.Text>
  );
};

export default function WatchEarnScreen() {
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [isLoading, setIsLoading] = useState(true);
  const [totalEarned, setTotalEarned] = useState(125);
  const [todayEarned, setTodayEarned] = useState(35);

  useEffect(() => {
    setTimeout(() => setIsLoading(false), 500);
  }, []);

  const getPlatformIcon = (platform: Video['platform']) => {
    switch (platform) {
      case 'tiktok':
        return '🎵';
      case 'youtube':
        return '▶️';
      case 'facebook':
        return '📘';
    }
  };

  const getPlatformColor = (platform: Video['platform']) => {
    switch (platform) {
      case 'tiktok':
        return '#000000';
      case 'youtube':
        return '#FF0000';
      case 'facebook':
        return '#1877F2';
    }
  };

  const handleVideoPress = (video: Video) => {
    // TODO: Open video player
    router.push('/coming-soon');
  };

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#E11D48" />
        <Text style={styles.loadingText}>กำลังโหลด...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0F172A" />

      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backButton}>
          <Text style={{ fontSize: 24 }}>⬅️</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>ดูคลิปได้เงิน</Text>
        <TouchableOpacity style={styles.historyButton}>
          <Text style={{ fontSize: 20 }}>📊</Text>
        </TouchableOpacity>
      </View>

      <ScrollView style={styles.scrollView} showsVerticalScrollIndicator={false}>
        {/* Hero Banner */}
        <View style={styles.heroContainer}>
          <Image source={WATCH_EARN_HERO} style={styles.heroImage} resizeMode="cover" />
          <LinearGradient
            colors={['transparent', 'rgba(15, 23, 42, 0.9)', '#0F172A']}
            style={styles.heroOverlay}
          />
        </View>

        {/* Earnings Card */}
        <View style={styles.earningsContainer}>
          <LinearGradient
            colors={['#E11D48', '#BE123C']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={styles.earningsCard}
          >
            {/* Decorative circles */}
            <View style={styles.decorCircle1} />
            <View style={styles.decorCircle2} />

            <View style={styles.earningsContent}>
              <View style={styles.earningsMain}>
                <Text style={styles.earningsLabel}>รายได้ทั้งหมด</Text>
                <View style={styles.earningsRow}>
                  <Text style={styles.earningsAmount}>฿{totalEarned}</Text>
                  <View style={styles.coinAnimation}>
                    <AnimatedCoin delay={0} />
                    <AnimatedCoin delay={500} />
                    <AnimatedCoin delay={1000} />
                  </View>
                </View>
              </View>

              <View style={styles.earningsDivider} />

              <View style={styles.earningsToday}>
                <Text style={styles.todayLabel}>วันนี้</Text>
                <Text style={styles.todayAmount}>+฿{todayEarned}</Text>
              </View>
            </View>

            <TouchableOpacity style={styles.withdrawButton}>
              <Text style={styles.withdrawText}>ถอนเงิน</Text>
            </TouchableOpacity>
          </LinearGradient>
        </View>

        {/* How it works */}
        <View style={styles.howItWorks}>
          <Text style={styles.howItWorksTitle}>วิธีการทำเงิน</Text>
          <View style={styles.stepsRow}>
            <View style={styles.stepItem}>
              <View style={[styles.stepIcon, { backgroundColor: '#E11D4820' }]}>
                <Text style={{ fontSize: 24 }}>👀</Text>
              </View>
              <Text style={styles.stepText}>ดูคลิป</Text>
            </View>
            <View style={styles.stepArrow}>
              <Text style={{ color: '#64748B' }}>→</Text>
            </View>
            <View style={styles.stepItem}>
              <View style={[styles.stepIcon, { backgroundColor: '#F59E0B20' }]}>
                <Text style={{ fontSize: 24 }}>⏱️</Text>
              </View>
              <Text style={styles.stepText}>ดูจนจบ</Text>
            </View>
            <View style={styles.stepArrow}>
              <Text style={{ color: '#64748B' }}>→</Text>
            </View>
            <View style={styles.stepItem}>
              <View style={[styles.stepIcon, { backgroundColor: '#10B98120' }]}>
                <Text style={{ fontSize: 24 }}>🪙</Text>
              </View>
              <Text style={styles.stepText}>รับเงิน</Text>
            </View>
          </View>
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

        {/* Video List */}
        <View style={styles.videoSection}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>คลิปวิดีโอ</Text>
            <Text style={styles.sectionCount}>{VIDEOS.length} คลิป</Text>
          </View>

          {VIDEOS.map((video) => (
            <TouchableOpacity
              key={video.id}
              style={[styles.videoCard, video.isWatched && styles.videoCardWatched]}
              onPress={() => handleVideoPress(video)}
              activeOpacity={0.8}
            >
              <View style={styles.thumbnailBox}>
                <LinearGradient
                  colors={['#1E293B', '#334155']}
                  style={styles.thumbnailGradient}
                >
                  <Text style={styles.thumbnailIcon}>{video.thumbnail}</Text>
                </LinearGradient>
                <View style={styles.durationBadge}>
                  <Text style={styles.durationText}>{video.duration}</Text>
                </View>
                {video.isWatched && (
                  <View style={styles.watchedOverlay}>
                    <Text style={{ fontSize: 20 }}>✅</Text>
                  </View>
                )}
              </View>

              <View style={styles.videoInfo}>
                <Text style={styles.videoTitle} numberOfLines={2}>
                  {video.title}
                </Text>

                <View style={styles.videoMeta}>
                  <View style={[styles.platformBadge, { backgroundColor: getPlatformColor(video.platform) + '20' }]}>
                    <Text style={{ fontSize: 12 }}>{getPlatformIcon(video.platform)}</Text>
                    <Text style={[styles.platformText, { color: getPlatformColor(video.platform) }]}>
                      {video.platform}
                    </Text>
                  </View>
                  <Text style={styles.viewsText}>{video.views} views</Text>
                </View>

                <View style={styles.videoFooter}>
                  <View style={styles.categoryBadge}>
                    <Text style={styles.categoryBadgeText}>{video.category}</Text>
                  </View>
                  <View style={styles.rewardBox}>
                    <Text style={styles.rewardIcon}>🪙</Text>
                    <Text style={styles.rewardText}>+฿{video.reward}</Text>
                  </View>
                </View>
              </View>
            </TouchableOpacity>
          ))}
        </View>

        {/* Daily Mission */}
        <View style={styles.missionSection}>
          <LinearGradient
            colors={['#1E293B', '#334155']}
            style={styles.missionCard}
          >
            <View style={styles.missionHeader}>
              <Text style={styles.missionIcon}>🎯</Text>
              <Text style={styles.missionTitle}>ภารกิจประจำวัน</Text>
            </View>
            <View style={styles.missionProgress}>
              <View style={styles.progressBar}>
                <View style={[styles.progressFill, { width: '60%' }]} />
              </View>
              <Text style={styles.progressText}>6/10 คลิป</Text>
            </View>
            <Text style={styles.missionReward}>ดูครบ 10 คลิป รับโบนัส ฿50!</Text>
          </LinearGradient>
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
  loadingContainer: {
    flex: 1,
    backgroundColor: '#0F172A',
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    color: '#9CA3AF',
    marginTop: 12,
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
  historyButton: {
    padding: 8,
  },
  scrollView: {
    flex: 1,
  },
  heroContainer: {
    height: 160,
    position: 'relative',
  },
  heroImage: {
    width: '100%',
    height: '100%',
  },
  heroOverlay: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    height: '100%',
  },
  earningsContainer: {
    paddingHorizontal: 16,
    marginTop: -60,
  },
  earningsCard: {
    borderRadius: 20,
    padding: 20,
    overflow: 'hidden',
  },
  decorCircle1: {
    position: 'absolute',
    top: -30,
    right: -30,
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: 'rgba(255,255,255,0.1)',
  },
  decorCircle2: {
    position: 'absolute',
    bottom: -20,
    left: -20,
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: 'rgba(0,0,0,0.1)',
  },
  earningsContent: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  earningsMain: {
    flex: 1,
  },
  earningsLabel: {
    color: 'rgba(255,255,255,0.8)',
    fontSize: 14,
  },
  earningsRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  earningsAmount: {
    color: '#FFF',
    fontSize: 36,
    fontWeight: 'bold',
  },
  coinAnimation: {
    width: 30,
    height: 40,
    marginLeft: 8,
    justifyContent: 'flex-end',
    alignItems: 'center',
  },
  earningsDivider: {
    width: 1,
    height: 50,
    backgroundColor: 'rgba(255,255,255,0.2)',
    marginHorizontal: 16,
  },
  earningsToday: {
    alignItems: 'flex-end',
  },
  todayLabel: {
    color: 'rgba(255,255,255,0.8)',
    fontSize: 12,
  },
  todayAmount: {
    color: '#FFF',
    fontSize: 20,
    fontWeight: 'bold',
  },
  withdrawButton: {
    backgroundColor: 'rgba(255,255,255,0.2)',
    borderRadius: 12,
    paddingVertical: 12,
    alignItems: 'center',
  },
  withdrawText: {
    color: '#FFF',
    fontWeight: '600',
  },
  howItWorks: {
    padding: 16,
    marginTop: 8,
  },
  howItWorksTitle: {
    color: '#FFF',
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 16,
    textAlign: 'center',
  },
  stepsRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
  },
  stepItem: {
    alignItems: 'center',
  },
  stepIcon: {
    width: 50,
    height: 50,
    borderRadius: 25,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 8,
  },
  stepText: {
    color: '#9CA3AF',
    fontSize: 12,
  },
  stepArrow: {
    marginHorizontal: 16,
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
    backgroundColor: '#E11D48',
  },
  categoryText: {
    color: '#9CA3AF',
    fontWeight: '500',
  },
  categoryTextActive: {
    color: '#FFF',
  },
  videoSection: {
    paddingHorizontal: 16,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  sectionTitle: {
    color: '#FFF',
    fontSize: 18,
    fontWeight: 'bold',
  },
  sectionCount: {
    color: '#9CA3AF',
    fontSize: 14,
  },
  videoCard: {
    flexDirection: 'row',
    backgroundColor: '#1E293B',
    borderRadius: 16,
    overflow: 'hidden',
    marginBottom: 12,
  },
  videoCardWatched: {
    opacity: 0.7,
  },
  thumbnailBox: {
    width: 120,
    height: 90,
    position: 'relative',
  },
  thumbnailGradient: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  thumbnailIcon: {
    fontSize: 36,
  },
  durationBadge: {
    position: 'absolute',
    bottom: 4,
    right: 4,
    backgroundColor: 'rgba(0,0,0,0.8)',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  durationText: {
    color: '#FFF',
    fontSize: 10,
  },
  watchedOverlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  videoInfo: {
    flex: 1,
    padding: 10,
  },
  videoTitle: {
    color: '#FFF',
    fontSize: 14,
    fontWeight: '500',
    marginBottom: 6,
  },
  videoMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 6,
  },
  platformBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 8,
    gap: 4,
  },
  platformText: {
    fontSize: 10,
    fontWeight: '600',
    textTransform: 'capitalize',
  },
  viewsText: {
    color: '#64748B',
    fontSize: 11,
  },
  videoFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  categoryBadge: {
    backgroundColor: '#334155',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 8,
  },
  categoryBadgeText: {
    color: '#9CA3AF',
    fontSize: 10,
  },
  rewardBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#10B98120',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
    gap: 4,
  },
  rewardIcon: {
    fontSize: 12,
  },
  rewardText: {
    color: '#10B981',
    fontSize: 12,
    fontWeight: 'bold',
  },
  missionSection: {
    padding: 16,
    marginTop: 8,
  },
  missionCard: {
    borderRadius: 16,
    padding: 16,
  },
  missionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  missionIcon: {
    fontSize: 24,
    marginRight: 8,
  },
  missionTitle: {
    color: '#FFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  missionProgress: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  progressBar: {
    flex: 1,
    height: 8,
    backgroundColor: '#334155',
    borderRadius: 4,
    marginRight: 12,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    backgroundColor: '#E11D48',
    borderRadius: 4,
  },
  progressText: {
    color: '#9CA3AF',
    fontSize: 12,
  },
  missionReward: {
    color: '#F59E0B',
    fontSize: 12,
    fontWeight: '500',
  },
});
