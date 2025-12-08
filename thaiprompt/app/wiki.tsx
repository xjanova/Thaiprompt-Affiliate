/**
 * Wiki Screen - หน้าคู่มือการใช้งาน
 * แสดงข้อมูลวิธีการใช้งานแอพ
 * - FAQ
 * - วิธีการใช้งานแต่ละฟีเจอร์
 * - เงื่อนไขและข้อกำหนด
 */

import React, { useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  StyleSheet,
  Linking,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import Animated, {
  FadeInDown,
  FadeInUp,
  useSharedValue,
  useAnimatedStyle,
  withSpring,
} from 'react-native-reanimated';
import { useAppStore } from '@/stores/appStore';

// Wiki Categories
const WIKI_CATEGORIES = [
  {
    id: 'getting-started',
    title: 'เริ่มต้นใช้งาน',
    icon: '🚀',
    items: [
      { title: 'วิธีสมัครสมาชิก', content: 'สมัครสมาชิกผ่านแอพหรือเว็บไซต์ โดยกรอกข้อมูลส่วนตัว และยืนยันตัวตนผ่าน OTP' },
      { title: 'การเข้าสู่ระบบ', content: 'ใช้อีเมลหรือเบอร์โทรศัพท์ที่ลงทะเบียนไว้ในการเข้าสู่ระบบ' },
      { title: 'LINE Login', content: 'สามารถเข้าสู่ระบบด้วย LINE Account ได้อย่างรวดเร็ว' },
    ],
  },
  {
    id: 'shopping',
    title: 'การช้อปปิ้ง',
    icon: '🛒',
    items: [
      { title: 'วิธีการสั่งซื้อสินค้า', content: 'เลือกสินค้า > เพิ่มลงตะกร้า > ชำระเงิน > รอรับสินค้า' },
      { title: 'วิธีการชำระเงิน', content: 'รองรับการชำระผ่านบัตรเครดิต, โอนเงิน, PromptPay และ Wallet' },
      { title: 'การติดตามพัสดุ', content: 'ตรวจสอบสถานะการจัดส่งได้ในหน้าคำสั่งซื้อ' },
    ],
  },
  {
    id: 'affiliate',
    title: 'ระบบ Affiliate',
    icon: '💰',
    items: [
      { title: 'วิธีแนะนำเพื่อน', content: 'แชร์ลิงก์หรือ QR Code ของคุณให้เพื่อน เมื่อเพื่อนสมัครและซื้อสินค้า คุณจะได้รับค่าคอมมิชชั่น' },
      { title: 'การคำนวณคอมมิชชั่น', content: 'คอมมิชชั่นคำนวณตามระดับ (Rank) ของคุณ ยิ่งระดับสูง ยิ่งได้รับ % มากขึ้น' },
      { title: 'การถอนเงิน', content: 'สามารถถอนเงินได้เมื่อยอดคอมมิชชั่นถึงขั้นต่ำ 500 บาท' },
    ],
  },
  {
    id: 'wallet',
    title: 'กระเป๋าเงิน',
    icon: '👛',
    items: [
      { title: 'การเติมเงิน', content: 'เติมเงินผ่าน QR Code, โอนเงิน หรือบัตรเครดิต' },
      { title: 'การถอนเงิน', content: 'ถอนเงินเข้าบัญชีธนาคารที่ผูกไว้ ภายใน 1-3 วันทำการ' },
      { title: 'ประวัติธุรกรรม', content: 'ตรวจสอบประวัติการทำรายการทั้งหมดได้ในหน้ากระเป๋าเงิน' },
    ],
  },
  {
    id: 'rank',
    title: 'ระบบระดับ (Rank)',
    icon: '🏆',
    items: [
      { title: 'ระดับต่างๆ', content: 'Bronze > Silver > Gold > Platinum > Diamond > Crown' },
      { title: 'วิธีเลื่อนระดับ', content: 'สะสมยอดขายและสร้างทีม เมื่อถึงเป้าหมายจะเลื่อนระดับอัตโนมัติ' },
      { title: 'สิทธิพิเศษแต่ละระดับ', content: 'ระดับสูงขึ้นจะได้รับ % คอมมิชชั่นมากขึ้น และโบนัสพิเศษ' },
    ],
  },
];

// FAQ Items
const FAQ_ITEMS = [
  {
    question: 'ลืมรหัสผ่านต้องทำอย่างไร?',
    answer: 'กดปุ่ม "ลืมรหัสผ่าน" ที่หน้าเข้าสู่ระบบ ระบบจะส่ง OTP ไปยังเบอร์โทรหรืออีเมลที่ลงทะเบียนไว้',
  },
  {
    question: 'คอมมิชชั่นเข้าเมื่อไหร่?',
    answer: 'คอมมิชชั่นจะเข้าทันทีหลังจากคำสั่งซื้อเสร็จสมบูรณ์ (สถานะ: จัดส่งแล้ว)',
  },
  {
    question: 'สามารถเปลี่ยนผู้แนะนำได้ไหม?',
    answer: 'ไม่สามารถเปลี่ยนผู้แนะนำได้หลังจากสมัครสมาชิกแล้ว',
  },
  {
    question: 'ถอนเงินขั้นต่ำเท่าไหร่?',
    answer: 'ถอนเงินขั้นต่ำ 500 บาท ไม่มีค่าธรรมเนียม',
  },
];

// Expandable Section Component
const ExpandableSection = ({
  title,
  icon,
  items,
  index,
  isDark,
}: {
  title: string;
  icon: string;
  items: { title: string; content: string }[];
  index: number;
  isDark: boolean;
}) => {
  const [isExpanded, setIsExpanded] = useState(false);

  return (
    <Animated.View
      entering={FadeInUp.delay(index * 100).springify()}
      style={[
        styles.sectionCard,
        { backgroundColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(255,255,255,0.9)' },
      ]}
    >
      <Pressable
        onPress={() => setIsExpanded(!isExpanded)}
        style={styles.sectionHeader}
      >
        <View style={styles.sectionTitleRow}>
          <Text style={styles.sectionIcon}>{icon}</Text>
          <Text style={[styles.sectionTitle, { color: isDark ? '#FFFFFF' : '#1F2937' }]}>
            {title}
          </Text>
        </View>
        <Ionicons
          name={isExpanded ? 'chevron-up' : 'chevron-down'}
          size={24}
          color={isDark ? '#D4AF37' : '#B8941E'}
        />
      </Pressable>

      {isExpanded && (
        <View style={styles.sectionContent}>
          {items.map((item, idx) => (
            <View
              key={idx}
              style={[
                styles.contentItem,
                { borderBottomColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)' },
              ]}
            >
              <Text style={[styles.contentTitle, { color: isDark ? '#D4AF37' : '#B8941E' }]}>
                {item.title}
              </Text>
              <Text style={[styles.contentText, { color: isDark ? '#CCCCCC' : '#666666' }]}>
                {item.content}
              </Text>
            </View>
          ))}
        </View>
      )}
    </Animated.View>
  );
};

// FAQ Item Component
const FAQItem = ({
  question,
  answer,
  index,
  isDark,
}: {
  question: string;
  answer: string;
  index: number;
  isDark: boolean;
}) => {
  const [isExpanded, setIsExpanded] = useState(false);

  return (
    <Animated.View
      entering={FadeInUp.delay(index * 50).springify()}
      style={[
        styles.faqCard,
        { backgroundColor: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.03)' },
      ]}
    >
      <Pressable onPress={() => setIsExpanded(!isExpanded)} style={styles.faqHeader}>
        <Text style={[styles.faqQuestion, { color: isDark ? '#FFFFFF' : '#1F2937' }]}>
          {question}
        </Text>
        <Ionicons
          name={isExpanded ? 'remove-circle' : 'add-circle'}
          size={24}
          color="#D4AF37"
        />
      </Pressable>
      {isExpanded && (
        <Text style={[styles.faqAnswer, { color: isDark ? '#CCCCCC' : '#666666' }]}>
          {answer}
        </Text>
      )}
    </Animated.View>
  );
};

export default function WikiScreen() {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const handleContact = () => {
    Linking.openURL('mailto:support@thaiprompt.com');
  };

  const handleWebsite = () => {
    Linking.openURL('https://main.thaiprompt.online');
  };

  return (
    <View style={[styles.container, { backgroundColor: isDark ? '#0F172A' : '#F9FAFB' }]}>
      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* Header */}
        <Animated.View entering={FadeInDown.delay(100).springify()} style={styles.header}>
          <LinearGradient
            colors={['#D4AF37', '#B8941E']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={styles.headerGradient}
          >
            <Text style={styles.headerIcon}>📚</Text>
            <Text style={styles.headerTitle}>คู่มือการใช้งาน</Text>
            <Text style={styles.headerSubtitle}>เรียนรู้วิธีใช้งานแอพอย่างง่ายดาย</Text>
          </LinearGradient>
        </Animated.View>

        {/* Categories */}
        <View style={styles.section}>
          <Text style={[styles.sectionLabel, { color: isDark ? '#999999' : '#666666' }]}>
            หมวดหมู่
          </Text>
          {WIKI_CATEGORIES.map((category, index) => (
            <ExpandableSection
              key={category.id}
              title={category.title}
              icon={category.icon}
              items={category.items}
              index={index}
              isDark={isDark}
            />
          ))}
        </View>

        {/* FAQ */}
        <View style={styles.section}>
          <Text style={[styles.sectionLabel, { color: isDark ? '#999999' : '#666666' }]}>
            คำถามที่พบบ่อย (FAQ)
          </Text>
          {FAQ_ITEMS.map((faq, index) => (
            <FAQItem
              key={index}
              question={faq.question}
              answer={faq.answer}
              index={index}
              isDark={isDark}
            />
          ))}
        </View>

        {/* Contact */}
        <View style={styles.section}>
          <Text style={[styles.sectionLabel, { color: isDark ? '#999999' : '#666666' }]}>
            ติดต่อเรา
          </Text>
          <View style={styles.contactButtons}>
            <Pressable
              onPress={handleContact}
              style={[styles.contactButton, { backgroundColor: isDark ? 'rgba(212,175,55,0.2)' : 'rgba(212,175,55,0.1)' }]}
            >
              <Ionicons name="mail" size={24} color="#D4AF37" />
              <Text style={[styles.contactButtonText, { color: isDark ? '#FFFFFF' : '#1F2937' }]}>
                อีเมล
              </Text>
            </Pressable>
            <Pressable
              onPress={handleWebsite}
              style={[styles.contactButton, { backgroundColor: isDark ? 'rgba(212,175,55,0.2)' : 'rgba(212,175,55,0.1)' }]}
            >
              <Ionicons name="globe" size={24} color="#D4AF37" />
              <Text style={[styles.contactButtonText, { color: isDark ? '#FFFFFF' : '#1F2937' }]}>
                เว็บไซต์
              </Text>
            </Pressable>
          </View>
        </View>

        {/* Bottom Spacer */}
        <View style={{ height: 40 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 16,
    paddingTop: 16,
  },
  header: {
    marginBottom: 24,
    borderRadius: 20,
    overflow: 'hidden',
  },
  headerGradient: {
    padding: 24,
    alignItems: 'center',
  },
  headerIcon: {
    fontSize: 48,
    marginBottom: 12,
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 8,
  },
  headerSubtitle: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
  },
  section: {
    marginBottom: 24,
  },
  sectionLabel: {
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 12,
    textTransform: 'uppercase',
    letterSpacing: 1,
  },
  sectionCard: {
    borderRadius: 16,
    marginBottom: 12,
    overflow: 'hidden',
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
  },
  sectionTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  sectionIcon: {
    fontSize: 24,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
  },
  sectionContent: {
    paddingHorizontal: 16,
    paddingBottom: 16,
  },
  contentItem: {
    paddingVertical: 12,
    borderBottomWidth: 1,
  },
  contentTitle: {
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 4,
  },
  contentText: {
    fontSize: 13,
    lineHeight: 20,
  },
  faqCard: {
    borderRadius: 12,
    marginBottom: 8,
    padding: 16,
  },
  faqHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  faqQuestion: {
    fontSize: 14,
    fontWeight: '600',
    flex: 1,
    marginRight: 12,
  },
  faqAnswer: {
    fontSize: 13,
    lineHeight: 20,
    marginTop: 12,
  },
  contactButtons: {
    flexDirection: 'row',
    gap: 12,
  },
  contactButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    padding: 16,
    borderRadius: 12,
  },
  contactButtonText: {
    fontSize: 14,
    fontWeight: '600',
  },
});
