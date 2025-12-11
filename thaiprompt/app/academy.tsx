/**
 * หน้า Academy - ระบบการเรียนรู้ออนไลน์
 * Premium Learning Platform
 */

import React, { useState, useEffect } from 'react';
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
} from 'react-native';
import { router } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { BlurView } from 'expo-blur';

const { width: SCREEN_WIDTH } = Dimensions.get('window');
const CARD_WIDTH = SCREEN_WIDTH - 32;

// รูป Hero
const ACADEMY_HERO = require('@/assets/images/2bt/academy.png');

interface Course {
  id: string;
  title: string;
  description: string;
  instructor: string;
  duration: string;
  lessons: number;
  level: 'beginner' | 'intermediate' | 'advanced';
  price: number;
  originalPrice?: number;
  rating: number;
  students: number;
  icon: string;
  gradientColors: [string, string];
  isFree?: boolean;
  isNew?: boolean;
  isPopular?: boolean;
}

// หลักสูตรตัวอย่าง
const COURSES: Course[] = [
  {
    id: 'mlm-basics',
    title: 'พื้นฐาน MLM สู่ความสำเร็จ',
    description: 'เรียนรู้หลักการ MLM ตั้งแต่เริ่มต้น สร้างทีมและรายได้อย่างยั่งยืน',
    instructor: 'อ.สมชาย รวยดี',
    duration: '4 ชั่วโมง',
    lessons: 12,
    level: 'beginner',
    price: 0,
    rating: 4.8,
    students: 1250,
    icon: '📚',
    gradientColors: ['#10B981', '#059669'],
    isFree: true,
    isPopular: true,
  },
  {
    id: 'crypto-trading',
    title: 'เทรด Crypto สำหรับมือใหม่',
    description: 'เข้าใจตลาด Cryptocurrency และเริ่มต้นเทรดอย่างมืออาชีพ',
    instructor: 'อ.วิทย์ คริปโต',
    duration: '6 ชั่วโมง',
    lessons: 18,
    level: 'beginner',
    price: 990,
    originalPrice: 1990,
    rating: 4.9,
    students: 856,
    icon: '🪙',
    gradientColors: ['#F59E0B', '#D97706'],
    isNew: true,
  },
  {
    id: 'affiliate-marketing',
    title: 'Affiliate Marketing Masterclass',
    description: 'สร้างรายได้ Passive Income จาก Affiliate Marketing',
    instructor: 'อ.นิดา แอฟฟิลิเอท',
    duration: '8 ชั่วโมง',
    lessons: 24,
    level: 'intermediate',
    price: 1490,
    originalPrice: 2990,
    rating: 4.7,
    students: 632,
    icon: '💰',
    gradientColors: ['#8B5CF6', '#6D28D9'],
    isPopular: true,
  },
  {
    id: 'leadership',
    title: 'ภาวะผู้นำและการสร้างทีม',
    description: 'พัฒนาทักษะผู้นำ สร้างทีมที่แข็งแกร่ง',
    instructor: 'อ.ประสิทธิ์ ลีดเดอร์',
    duration: '5 ชั่วโมง',
    lessons: 15,
    level: 'advanced',
    price: 1990,
    rating: 4.6,
    students: 423,
    icon: '👥',
    gradientColors: ['#EC4899', '#DB2777'],
  },
  {
    id: 'digital-marketing',
    title: 'Digital Marketing 2024',
    description: 'เทคนิคการตลาดดิจิทัลล่าสุด Facebook, TikTok, LINE',
    instructor: 'อ.มาร์ค ดิจิทัล',
    duration: '10 ชั่วโมง',
    lessons: 30,
    level: 'intermediate',
    price: 2490,
    originalPrice: 4990,
    rating: 4.8,
    students: 1089,
    icon: '📱',
    gradientColors: ['#3B82F6', '#2563EB'],
    isNew: true,
    isPopular: true,
  },
  {
    id: 'personal-finance',
    title: 'วางแผนการเงินส่วนบุคคล',
    description: 'จัดการเงิน ออม ลงทุน สู่อิสรภาพทางการเงิน',
    instructor: 'อ.เงินทอง มั่งมี',
    duration: '3 ชั่วโมง',
    lessons: 10,
    level: 'beginner',
    price: 0,
    rating: 4.5,
    students: 2150,
    icon: '💳',
    gradientColors: ['#14B8A6', '#0D9488'],
    isFree: true,
  },
];

const CATEGORIES = [
  { id: 'all', label: 'ทั้งหมด', icon: '📖' },
  { id: 'free', label: 'ฟรี', icon: '🎁' },
  { id: 'popular', label: 'ยอดนิยม', icon: '🔥' },
  { id: 'new', label: 'ใหม่', icon: '✨' },
];

export default function AcademyScreen() {
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // จำลองการโหลดข้อมูล
    setTimeout(() => setIsLoading(false), 500);
  }, []);

  const filteredCourses = COURSES.filter((course) => {
    if (selectedCategory === 'all') return true;
    if (selectedCategory === 'free') return course.isFree;
    if (selectedCategory === 'popular') return course.isPopular;
    if (selectedCategory === 'new') return course.isNew;
    return true;
  });

  const getLevelBadge = (level: Course['level']) => {
    switch (level) {
      case 'beginner':
        return { text: 'เริ่มต้น', color: '#10B981' };
      case 'intermediate':
        return { text: 'ปานกลาง', color: '#F59E0B' };
      case 'advanced':
        return { text: 'ขั้นสูง', color: '#EF4444' };
    }
  };

  const handleCoursePress = (course: Course) => {
    // TODO: Navigate to course detail
    router.push(`/coming-soon`);
  };

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#3B82F6" />
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
        <Text style={styles.headerTitle}>Academy</Text>
        <TouchableOpacity style={styles.searchButton}>
          <Text style={{ fontSize: 20 }}>🔍</Text>
        </TouchableOpacity>
      </View>

      <ScrollView style={styles.scrollView} showsVerticalScrollIndicator={false}>
        {/* Hero Banner */}
        <View style={styles.heroContainer}>
          <Image source={ACADEMY_HERO} style={styles.heroImage} resizeMode="cover" />
          <LinearGradient
            colors={['transparent', 'rgba(15, 23, 42, 0.8)', '#0F172A']}
            style={styles.heroOverlay}
          />
          <View style={styles.heroContent}>
            <Text style={styles.heroTitle}>เรียนรู้ไม่มีขีดจำกัด</Text>
            <Text style={styles.heroSubtitle}>
              หลักสูตรคุณภาพจากผู้เชี่ยวชาญ{'\n'}พัฒนาทักษะ สร้างรายได้
            </Text>
          </View>
        </View>

        {/* Stats */}
        <View style={styles.statsRow}>
          <View style={styles.statItem}>
            <Text style={styles.statValue}>50+</Text>
            <Text style={styles.statLabel}>หลักสูตร</Text>
          </View>
          <View style={styles.statDivider} />
          <View style={styles.statItem}>
            <Text style={styles.statValue}>10K+</Text>
            <Text style={styles.statLabel}>ผู้เรียน</Text>
          </View>
          <View style={styles.statDivider} />
          <View style={styles.statItem}>
            <Text style={styles.statValue}>4.8</Text>
            <Text style={styles.statLabel}>คะแนนเฉลี่ย</Text>
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
              <Text style={styles.categoryIcon}>{category.icon}</Text>
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

        {/* Course List */}
        <View style={styles.courseSection}>
          <Text style={styles.sectionTitle}>
            {selectedCategory === 'all' ? 'หลักสูตรทั้งหมด' :
             selectedCategory === 'free' ? 'หลักสูตรฟรี' :
             selectedCategory === 'popular' ? 'หลักสูตรยอดนิยม' : 'หลักสูตรใหม่'}
          </Text>
          <Text style={styles.sectionCount}>{filteredCourses.length} หลักสูตร</Text>
        </View>

        {filteredCourses.map((course) => {
          const levelBadge = getLevelBadge(course.level);
          return (
            <TouchableOpacity
              key={course.id}
              style={styles.courseCard}
              onPress={() => handleCoursePress(course)}
              activeOpacity={0.8}
            >
              <LinearGradient
                colors={course.gradientColors}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.courseIconBox}
              >
                <Text style={styles.courseIcon}>{course.icon}</Text>
                {course.isNew && (
                  <View style={styles.newBadge}>
                    <Text style={styles.newBadgeText}>ใหม่</Text>
                  </View>
                )}
              </LinearGradient>

              <View style={styles.courseInfo}>
                <View style={styles.courseHeader}>
                  <Text style={styles.courseTitle} numberOfLines={2}>
                    {course.title}
                  </Text>
                  <View style={[styles.levelBadge, { backgroundColor: levelBadge.color + '20' }]}>
                    <Text style={[styles.levelText, { color: levelBadge.color }]}>
                      {levelBadge.text}
                    </Text>
                  </View>
                </View>

                <Text style={styles.courseDescription} numberOfLines={2}>
                  {course.description}
                </Text>

                <View style={styles.courseMeta}>
                  <Text style={styles.instructorText}>👨‍🏫 {course.instructor}</Text>
                  <Text style={styles.durationText}>⏱️ {course.duration}</Text>
                </View>

                <View style={styles.courseFooter}>
                  <View style={styles.ratingBox}>
                    <Text style={styles.ratingText}>⭐ {course.rating}</Text>
                    <Text style={styles.studentsText}>({course.students} คน)</Text>
                  </View>

                  <View style={styles.priceBox}>
                    {course.isFree ? (
                      <Text style={styles.freeText}>ฟรี</Text>
                    ) : (
                      <>
                        {course.originalPrice && (
                          <Text style={styles.originalPrice}>฿{course.originalPrice.toLocaleString()}</Text>
                        )}
                        <Text style={styles.priceText}>฿{course.price.toLocaleString()}</Text>
                      </>
                    )}
                  </View>
                </View>
              </View>
            </TouchableOpacity>
          );
        })}

        {/* My Learning Section */}
        <View style={styles.myLearningSection}>
          <LinearGradient
            colors={['#1E293B', '#334155']}
            style={styles.myLearningCard}
          >
            <Text style={styles.myLearningIcon}>🎓</Text>
            <Text style={styles.myLearningTitle}>หลักสูตรของฉัน</Text>
            <Text style={styles.myLearningSubtitle}>
              ดูความคืบหน้าและเรียนต่อ
            </Text>
            <TouchableOpacity style={styles.myLearningButton}>
              <Text style={styles.myLearningButtonText}>เข้าสู่ระบบเพื่อดู</Text>
            </TouchableOpacity>
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
  searchButton: {
    padding: 8,
  },
  scrollView: {
    flex: 1,
  },
  heroContainer: {
    height: 200,
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
    height: '80%',
  },
  heroContent: {
    position: 'absolute',
    bottom: 20,
    left: 16,
    right: 16,
  },
  heroTitle: {
    color: '#FFF',
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  heroSubtitle: {
    color: 'rgba(255,255,255,0.8)',
    fontSize: 14,
    lineHeight: 20,
  },
  statsRow: {
    flexDirection: 'row',
    backgroundColor: '#1E293B',
    marginHorizontal: 16,
    marginTop: -20,
    borderRadius: 16,
    padding: 16,
    justifyContent: 'space-around',
    alignItems: 'center',
  },
  statItem: {
    alignItems: 'center',
  },
  statValue: {
    color: '#FFF',
    fontSize: 20,
    fontWeight: 'bold',
  },
  statLabel: {
    color: '#9CA3AF',
    fontSize: 12,
    marginTop: 4,
  },
  statDivider: {
    width: 1,
    height: 30,
    backgroundColor: 'rgba(255,255,255,0.1)',
  },
  categoryScroll: {
    marginTop: 20,
    marginBottom: 16,
  },
  categoryContent: {
    paddingHorizontal: 16,
    gap: 8,
  },
  categoryButton: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 20,
    backgroundColor: '#1E293B',
    marginRight: 8,
    gap: 6,
  },
  categoryButtonActive: {
    backgroundColor: '#3B82F6',
  },
  categoryIcon: {
    fontSize: 16,
  },
  categoryText: {
    color: '#9CA3AF',
    fontWeight: '500',
  },
  categoryTextActive: {
    color: '#FFF',
  },
  courseSection: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
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
  courseCard: {
    flexDirection: 'row',
    backgroundColor: '#1E293B',
    marginHorizontal: 16,
    marginBottom: 12,
    borderRadius: 16,
    overflow: 'hidden',
  },
  courseIconBox: {
    width: 100,
    justifyContent: 'center',
    alignItems: 'center',
    position: 'relative',
  },
  courseIcon: {
    fontSize: 40,
  },
  newBadge: {
    position: 'absolute',
    top: 8,
    right: 8,
    backgroundColor: '#EF4444',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  newBadgeText: {
    color: '#FFF',
    fontSize: 10,
    fontWeight: 'bold',
  },
  courseInfo: {
    flex: 1,
    padding: 12,
  },
  courseHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 4,
  },
  courseTitle: {
    color: '#FFF',
    fontSize: 15,
    fontWeight: '600',
    flex: 1,
    marginRight: 8,
  },
  levelBadge: {
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 8,
  },
  levelText: {
    fontSize: 10,
    fontWeight: '600',
  },
  courseDescription: {
    color: '#9CA3AF',
    fontSize: 12,
    lineHeight: 16,
    marginBottom: 8,
  },
  courseMeta: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 8,
  },
  instructorText: {
    color: '#64748B',
    fontSize: 11,
  },
  durationText: {
    color: '#64748B',
    fontSize: 11,
  },
  courseFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  ratingBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  ratingText: {
    color: '#F59E0B',
    fontSize: 12,
    fontWeight: '600',
  },
  studentsText: {
    color: '#64748B',
    fontSize: 11,
  },
  priceBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  originalPrice: {
    color: '#64748B',
    fontSize: 12,
    textDecorationLine: 'line-through',
  },
  priceText: {
    color: '#10B981',
    fontSize: 16,
    fontWeight: 'bold',
  },
  freeText: {
    color: '#10B981',
    fontSize: 16,
    fontWeight: 'bold',
  },
  myLearningSection: {
    padding: 16,
    marginTop: 8,
  },
  myLearningCard: {
    borderRadius: 16,
    padding: 24,
    alignItems: 'center',
  },
  myLearningIcon: {
    fontSize: 48,
    marginBottom: 12,
  },
  myLearningTitle: {
    color: '#FFF',
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  myLearningSubtitle: {
    color: '#9CA3AF',
    fontSize: 14,
    textAlign: 'center',
    marginBottom: 16,
  },
  myLearningButton: {
    backgroundColor: '#3B82F6',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 12,
  },
  myLearningButtonText: {
    color: '#FFF',
    fontWeight: '600',
  },
});
