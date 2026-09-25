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
  StatusBar,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import Animated, {
  FadeInDown,
  FadeInUp,
  useSharedValue,
  useAnimatedStyle,
  withSpring,
} from 'react-native-reanimated';
import { useAppStore } from '@/stores/appStore';
import { openUrl } from '@/utils/navigation';

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
      { title: 'วิธีการชำระเงิน', content: 'ชำระด้วย PromptPay กระเป๋าเงินในแอป หรือเก็บเงินปลายทาง (เฉพาะร้านที่ส่งด้วยไรเดอร์)' },
      { title: 'การติดตามพัสดุ', content: 'ตรวจสอบสถานะการจัดส่งได้ในหน้าคำสั่งซื้อ' },
    ],
  },
  {
    id: 'rider',
    title: 'ไรเดอร์และร้านค้า',
    icon: '🛵',
    items: [
      { title: 'สมัครเป็นไรเดอร์', content: 'กรอกข้อมูลและอัปโหลดเอกสารในเมนูไรเดอร์ ทีมงานจะตรวจสอบก่อนเปิดให้รับงาน' },
      { title: 'เปิดร้านค้า', content: 'เปิดร้านและจัดการสินค้าได้บนเว็บไซต์ ส่วนออเดอร์ใหม่ดูได้ในเมนู "ร้านของฉัน"' },
      { title: 'ชวนเพื่อน', content: 'แชร์ลิงก์หรือ QR Code ของคุณ รับค่าแนะนำเมื่อเพื่อนสั่งซื้อครั้งแรก ดูรายละเอียดบนเว็บไซต์' },
    ],
  },
  {
    id: 'wallet',
    title: 'กระเป๋าเงิน',
    icon: '👛',
    items: [
      { title: 'การเติมเงิน', content: 'เติมเงินผ่าน PromptPay เพื่อใช้ชำระค่าสินค้าและค่าจัดส่งในแอป' },
      { title: 'การถอนเงิน', content: 'ถอนเงินเข้าบัญชีธนาคารหรือ PromptPay ที่ผูกไว้ ขั้นต่ำและค่าธรรมเนียมแสดงในหน้าถอนเงิน' },
      { title: 'ประวัติธุรกรรม', content: 'ตรวจสอบประวัติการทำรายการทั้งหมดได้ในหน้ากระเป๋าเงิน' },
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
    question: 'ยกเลิกคำสั่งซื้อได้ไหม?',
    answer: 'ยกเลิกได้ก่อนร้านจัดส่ง ถ้าชำระเงินแล้วระบบจะคืนเงินเข้ากระเป๋าเงินให้อัตโนมัติ',
  },
  {
    question: 'ถอนเงินขั้นต่ำเท่าไหร่?',
    answer: 'ดูขั้นต่ำ ค่าธรรมเนียม และยอดที่ได้รับจริงได้ในหน้าถอนเงินก่อนยืนยันทุกครั้ง',
  },
  {
    question: 'ลบบัญชีได้อย่างไร?',
    answer: 'ไปที่ ตั้งค่า > ลบบัญชี แล้วทำตามขั้นตอน ถ้ายังมียอดเงินหรือคำสั่งซื้อค้างอยู่ ระบบจะแจ้งให้จัดการก่อน',
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
        <Text style={{ fontSize: 24, color: isDark ? '#D4AF37' : '#B8941E' }}>
          {isExpanded ? '▲' : '▼'}
        </Text>
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
        <Text style={{ fontSize: 24, color: '#D4AF37' }}>
          {isExpanded ? '⊖' : '⊕'}
        </Text>
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
    // เปิดอีเมลใน external app (mailto: special URL)
    Linking.openURL('mailto:support@thaiprompt.online').catch(() => {});
  };

  const handleWebsite = () => {
    // เปิดเว็บไซต์ใน WebView ภายในแอพ
    openUrl('https://main.thaiprompt.online', 'Thaiprompt', '🌐');
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
              <Text style={{ fontSize: 24 }}>📧</Text>
              <Text style={[styles.contactButtonText, { color: isDark ? '#FFFFFF' : '#1F2937' }]}>
                อีเมล
              </Text>
            </Pressable>
            <Pressable
              onPress={handleWebsite}
              style={[styles.contactButton, { backgroundColor: isDark ? 'rgba(212,175,55,0.2)' : 'rgba(212,175,55,0.1)' }]}
            >
              <Text style={{ fontSize: 24 }}>🌐</Text>
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
