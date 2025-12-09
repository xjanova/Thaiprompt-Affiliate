/**
 * หน้าหนังสือเส้นทางเศรษฐี
 * แสดง E-Book 2 เวอร์ชัน:
 * 1. เส้นทางเศรษฐี (Basic) - 10 บทเรียน
 * 2. คู่มือเสริมทางเศรษฐี Pro - พร้อมเครื่องมือขั้นสูง
 */

import React, { useState, useMemo } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  StyleSheet,
  StatusBar,
  Modal,
  Dimensions,
  Linking,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { useColorScheme } from 'react-native';

const { width: screenWidth } = Dimensions.get('window');

// ข้อมูลหนังสือ
interface WealthBook {
  id: string;
  title: string;
  subtitle: string;
  icon: string;
  chapters: Chapter[];
  gradientColors: [string, string];
  description: string;
  features: string[];
}

interface Chapter {
  id: number;
  title: string;
  icon: string;
  content: ContentBlock[];
  gradientColors: [string, string];
}

interface ContentBlock {
  type: 'heading' | 'paragraph' | 'list' | 'tip' | 'step' | 'highlight';
  content: string | string[];
}

// ข้อมูลหนังสือเส้นทางเศรษฐี (Basic)
const WEALTH_GUIDE_BASIC: WealthBook = {
  id: 'basic',
  title: 'เส้นทางเศรษฐี',
  subtitle: 'คู่มือสู่ความร่ำรวย',
  icon: '💰',
  gradientColors: ['#F59E0B', '#D97706'],
  description: 'คู่มือฉบับสมบูรณ์สำหรับเริ่มต้นสร้างรายได้กับระบบ Affiliate ของเรา',
  features: ['10 บทเรียน', 'จากมือใหม่สู่แม่ทีม', '5 ระดับยศ'],
  chapters: [
    {
      id: 1,
      title: 'เริ่มต้นอย่างไร',
      icon: '🚀',
      gradientColors: ['#3B82F6', '#1D4ED8'],
      content: [
        { type: 'heading', content: 'ยินดีต้อนรับสู่ระบบ Thaiprompt-Affiliate!' },
        { type: 'paragraph', content: 'ระบบของเราคือโอกาสทองที่จะเปลี่ยนความพยายามของคุณให้กลายเป็นรายได้ที่มั่นคง ไม่ว่าคุณจะเป็นมือใหม่หรือมืออาชีพ เรามีเครื่องมือและระบบที่จะช่วยให้คุณประสบความสำเร็จ' },
        { type: 'heading', content: 'ขั้นตอนเริ่มต้น 5 ขั้นตอน' },
        { type: 'step', content: ['สมัครสมาชิก - กรอกข้อมูลให้ครบถ้วน ใช้ลิงก์แนะนำจากผู้สปอนเซอร์', 'ยืนยันตัวตน (KYC) - อัพโหลดบัตรประชาชนและรูปถ่าย', 'ทำความเข้าใจระบบ - อ่านคู่มือนี้ให้จบ', 'รับลิงก์แนะนำ - ไปที่เมนู "แนะนำเพื่อน" เพื่อรับลิงก์ส่วนตัว', 'เริ่มแนะนำ - แชร์ลิงก์และเริ่มสร้างทีมของคุณ'] },
        { type: 'tip', content: 'การสมัครฟรี! ไม่มีค่าใช้จ่าย ไม่ต้องลงทุน' },
      ],
    },
    {
      id: 2,
      title: 'ระบบทำงานอย่างไร',
      icon: '💡',
      gradientColors: ['#8B5CF6', '#6D28D9'],
      content: [
        { type: 'heading', content: 'หลักการทำงานของระบบ Affiliate' },
        { type: 'paragraph', content: 'ระบบ Affiliate ของเราทำงานบนหลักการ "แนะนำ ได้รับ" - คุณแนะนำลูกค้าหรือสมาชิกใหม่ คุณก็ได้รับค่าคอมมิชชั่น' },
        { type: 'highlight', content: 'ระบบ Binary Matrix - สร้างทีม 2 ขา ซ้ายและขวา สร้างรายได้แบบทวีคูณ' },
        { type: 'list', content: ['ขาซ้าย: สำหรับสมาชิกที่คุณแนะนำโดยตรง', 'ขาขวา: สำหรับสมาชิกที่ทีมแนะนำ', 'Matching Bonus: เมื่อทั้งสองขาเติบโต คุณจะได้โบนัสพิเศษ'] },
      ],
    },
    {
      id: 3,
      title: '4 ช่องทางรายได้',
      icon: '💰',
      gradientColors: ['#10B981', '#059669'],
      content: [
        { type: 'heading', content: 'รายได้ 4 ทาง ที่คุณจะได้รับ' },
        { type: 'highlight', content: '1. ค่าคอมมิชชั่นตรง (Direct Commission)' },
        { type: 'paragraph', content: 'รับ 10-30% จากทุกการซื้อของคนที่คุณแนะนำ' },
        { type: 'highlight', content: '2. Binary Matching Bonus' },
        { type: 'paragraph', content: 'เมื่อยอดขายทั้ง 2 ขาเท่ากัน รับโบนัส 10% ของยอดที่น้อยกว่า' },
        { type: 'highlight', content: '3. Rank Bonus' },
        { type: 'paragraph', content: 'เลื่อนยศได้รับโบนัสพิเศษ และเปอร์เซ็นต์คอมมิชชั่นที่สูงขึ้น' },
        { type: 'highlight', content: '4. Sponsor Bonus' },
        { type: 'paragraph', content: 'รับโบนัสจากยอดขายของทีมลึกถึง 10 ชั้น' },
      ],
    },
    {
      id: 4,
      title: 'ระบบยศและอันดับ',
      icon: '⭐',
      gradientColors: ['#F59E0B', '#D97706'],
      content: [
        { type: 'heading', content: '5 ระดับยศที่คุณสามารถไต่ไป' },
        { type: 'list', content: ['🥉 Bronze - เริ่มต้น: มียอด PV รวม 500', '🥈 Silver - กลาง: มียอด PV รวม 2,000', '🥇 Gold - สูง: มียอด PV รวม 5,000', '💎 Platinum - เหนือชั้น: มียอด PV รวม 15,000', '👑 Diamond - สุดยอด: มียอด PV รวม 50,000'] },
        { type: 'tip', content: 'PV = Point Value คือคะแนนจากยอดซื้อสินค้า โดย 1 บาท = 1 PV' },
        { type: 'paragraph', content: 'เมื่อเลื่อนยศ คุณจะได้รับ: อัตราคอมมิชชั่นสูงขึ้น, โบนัสพิเศษ, สิทธิพิเศษในการเข้าถึงสินค้า' },
      ],
    },
    {
      id: 5,
      title: 'สร้างทีมที่แข็งแกร่ง',
      icon: '👥',
      gradientColors: ['#EC4899', '#BE185D'],
      content: [
        { type: 'heading', content: 'เคล็ดลับสร้างทีมที่ประสบความสำเร็จ' },
        { type: 'step', content: ['เลือกคนที่มีความมุ่งมั่น ไม่ใช่แค่คนที่สมัครเพราะเกรงใจ', 'สอนทีมให้เข้าใจระบบ อย่าปล่อยให้เขาเรียนรู้เอง', 'ช่วยทีมตั้งเป้าหมาย และติดตามความก้าวหน้า', 'สร้างกลุ่มแชทเพื่อแบ่งปันความรู้และประสบการณ์', 'ยกย่องและให้กำลังใจเมื่อทีมทำได้ดี'] },
        { type: 'tip', content: 'ทีมที่แข็งแกร่ง = รายได้ที่มั่นคง ลงทุนเวลากับทีมของคุณ!' },
      ],
    },
    {
      id: 6,
      title: 'กลยุทธ์ขายที่ได้ผล',
      icon: '📈',
      gradientColors: ['#6366F1', '#4F46E5'],
      content: [
        { type: 'heading', content: '7 กลยุทธ์ขายที่ทำให้ประสบความสำเร็จ' },
        { type: 'list', content: ['ใช้ผลิตภัณฑ์เอง - ขายจากประสบการณ์จริง น่าเชื่อถือกว่า', 'เล่าเรื่องราว - คนชอบฟังเรื่องราว ไม่ใช่ขายตรงๆ', 'โพสต์สม่ำเสมอ - สร้าง presence ในโซเชียลมีเดีย', 'ตอบคำถามทันที - ความรวดเร็ว = ความน่าเชื่อถือ', 'รีวิวก่อน-หลัง - ภาพเปรียบเทียบทำให้เห็นผลลัพธ์ชัด', 'สร้างความสัมพันธ์ - ไม่ใช่แค่ขาย แต่สร้างมิตรภาพ', 'ติดตามหลังขาย - ดูแลลูกค้า สร้างความภักดี'] },
      ],
    },
    {
      id: 7,
      title: 'กระเป๋าเงินและถอนเงิน',
      icon: '💳',
      gradientColors: ['#EF4444', '#DC2626'],
      content: [
        { type: 'heading', content: 'ระบบกระเป๋าเงินของคุณ' },
        { type: 'paragraph', content: 'รายได้ทั้งหมดจะเข้ากระเป๋าเงินในระบบ คุณสามารถ: ดูยอดคงเหลือ, ดูประวัติรายได้, ถอนเงินเข้าบัญชีธนาคาร' },
        { type: 'heading', content: 'วิธีถอนเงิน' },
        { type: 'step', content: ['ไปที่เมนู "กระเป๋าเงิน"', 'กด "ถอนเงิน"', 'กรอกจำนวนเงินที่ต้องการถอน (ขั้นต่ำ 300 บาท)', 'เลือกบัญชีธนาคารที่ผูกไว้', 'ยืนยันการถอน', 'รอรับเงินภายใน 1-3 วันทำการ'] },
        { type: 'tip', content: 'ไม่มีค่าธรรมเนียมการถอน! ถอนได้ไม่จำกัดครั้ง' },
      ],
    },
    {
      id: 8,
      title: 'เป็นแม่ทีมมืออาชีพ',
      icon: '👑',
      gradientColors: ['#14B8A6', '#0D9488'],
      content: [
        { type: 'heading', content: 'คุณสมบัติของแม่ทีมมืออาชีพ' },
        { type: 'list', content: ['เป็นตัวอย่างที่ดี - ทำให้เห็นว่าระบบใช้ได้จริง', 'สอนอย่างมีระบบ - มี step-by-step ให้ทีมทำตาม', 'อดทนกับทีม - ทุกคนเรียนรู้ในจังหวะที่ต่างกัน', 'เข้าถึงได้ - พร้อมตอบคำถามและช่วยแก้ปัญหา', 'มีวิสัยทัศน์ - สร้างแรงบันดาลใจให้ทีม'] },
        { type: 'highlight', content: 'แม่ทีมที่ดี ไม่ใช่คนที่ทำยอดสูงสุด แต่คือคนที่ช่วยให้ทีมทำยอดได้สูงสุด!' },
      ],
    },
    {
      id: 9,
      title: 'เคล็ดลับและคำแนะนำ',
      icon: '🎓',
      gradientColors: ['#F97316', '#EA580C'],
      content: [
        { type: 'heading', content: 'เคล็ดลับความสำเร็จจากแม่ทีมระดับ Diamond' },
        { type: 'list', content: ['ตื่นเช้า - ช่วงเช้าคือเวลาทองในการตอบแชท', 'ทำทุกวัน - สม่ำเสมอสำคัญกว่าทำมากๆ ครั้งเดียว', 'เรียนรู้ตลอด - ตลาดเปลี่ยน ต้องปรับตัว', 'อย่าย่อท้อ - ความสำเร็จไม่มาในคืนเดียว', 'ช่วยเหลือคนอื่น - ยิ่งให้ ยิ่งได้', 'ตั้งเป้าหมาย - เขียนลงกระดาษ ติดไว้ให้เห็นทุกวัน'] },
        { type: 'tip', content: 'จดทุกวันว่าทำอะไรไปบ้าง - ช่วยให้เห็นความก้าวหน้าของตัวเอง' },
      ],
    },
    {
      id: 10,
      title: 'คำถามที่พบบ่อย',
      icon: '❓',
      gradientColors: ['#06B6D4', '#0891B2'],
      content: [
        { type: 'heading', content: 'FAQ - คำถามที่ถูกถามบ่อย' },
        { type: 'highlight', content: 'Q: ต้องลงทุนเท่าไหร่?' },
        { type: 'paragraph', content: 'A: สมัครฟรี! ไม่ต้องลงทุน แค่ซื้อสินค้าตามปกติก็เริ่มได้' },
        { type: 'highlight', content: 'Q: รายได้เข้าเมื่อไหร่?' },
        { type: 'paragraph', content: 'A: รายได้เข้าทันทีที่มีการซื้อจากลิงก์ของคุณ' },
        { type: 'highlight', content: 'Q: ถอนเงินขั้นต่ำเท่าไหร่?' },
        { type: 'paragraph', content: 'A: 300 บาท และไม่มีค่าธรรมเนียม' },
        { type: 'highlight', content: 'Q: ต้องขายเก่งไหม?' },
        { type: 'paragraph', content: 'A: ไม่จำเป็น! แค่แชร์ลิงก์และเล่าประสบการณ์จริงก็พอ' },
      ],
    },
  ],
};

// ข้อมูลหนังสือเส้นทางเศรษฐี Pro
const WEALTH_GUIDE_PRO: WealthBook = {
  id: 'pro',
  title: 'คู่มือเสริมทางเศรษฐี Pro',
  subtitle: 'ฉบับสมบูรณ์พร้อมเครื่องมือขั้นสูง',
  icon: '💎',
  gradientColors: ['#8B5CF6', '#6D28D9'],
  description: 'เรียนรู้ทุกวิธีสร้างรายได้ พร้อมเครื่องมือขั้นสูงที่ช่วยให้คุณเข้าใจแผนธุรกิจได้ง่ายขึ้น',
  features: ['6+ ช่องทางรายได้', 'ไม่จำกัดรายได้', 'เครื่องมือ Pro'],
  chapters: [
    {
      id: 1,
      title: 'แผนธุรกิจ 360°',
      icon: '🧠',
      gradientColors: ['#06B6D4', '#0284C7'],
      content: [
        { type: 'heading', content: 'ภาพรวมระบบรายได้ทั้งหมด' },
        { type: 'paragraph', content: 'ในฉบับ Pro นี้ คุณจะได้เรียนรู้รายละเอียดเชิงลึกของทุกช่องทางรายได้ และวิธีใช้ประโยชน์จากแต่ละช่องทางให้เต็มที่' },
        { type: 'highlight', content: 'เครื่องมือ 3D Mind Map - ดูภาพรวมแผนธุรกิจในมุมมอง 3 มิติบนเว็บ' },
        { type: 'tip', content: 'เปิดเว็บไซต์เพื่อใช้งาน 3D Mind Map แบบ interactive ได้!' },
      ],
    },
    {
      id: 2,
      title: 'กระแสเงินสด 3D',
      icon: '💸',
      gradientColors: ['#10B981', '#059669'],
      content: [
        { type: 'heading', content: 'ดูการไหลของเงินจากทุกช่องทาง' },
        { type: 'paragraph', content: 'เครื่องมือ 3D Income Flow จะแสดงให้เห็นว่าเงินไหลเข้ามาจากช่องทางไหนบ้าง และจำนวนเท่าไหร่' },
        { type: 'list', content: ['รายได้ตรง - จากการขายตรง', 'Binary Matching - จากการสร้างทีม 2 ขา', 'Sponsor Bonus - จากทีมที่คุณสปอนเซอร์', 'Rank Bonus - จากการเลื่อนยศ', 'Pool Bonus - จากการแบ่งปันผลกำไร', 'Leadership Bonus - จากการเป็นผู้นำทีม'] },
      ],
    },
    {
      id: 3,
      title: 'คำนวณรายได้',
      icon: '🔮',
      gradientColors: ['#F59E0B', '#D97706'],
      content: [
        { type: 'heading', content: 'เครื่องคำนวณรายได้อัจฉริยะ' },
        { type: 'paragraph', content: 'ใช้เครื่องมือคำนวณรายได้บนเว็บเพื่อประมาณการรายได้ของคุณ โดยกรอกข้อมูล:' },
        { type: 'list', content: ['จำนวนสมาชิกที่คาดว่าจะแนะนำได้ต่อเดือน', 'ยอดซื้อเฉลี่ยของแต่ละคน', 'อัตราการเติบโตของทีม'] },
        { type: 'tip', content: 'เครื่องคำนวณจะแสดงรายได้ที่เป็นไปได้จากทุกช่องทาง!' },
      ],
    },
    {
      id: 4,
      title: 'Binary Matrix เชิงลึก',
      icon: '⚖️',
      gradientColors: ['#EF4444', '#DC2626'],
      content: [
        { type: 'heading', content: 'ทำความเข้าใจ Binary Matrix อย่างลึกซึ้ง' },
        { type: 'paragraph', content: 'Binary Matrix คือหัวใจของระบบ Affiliate ของเรา เข้าใจหลักการนี้ = เข้าใจวิธีสร้างรายได้' },
        { type: 'highlight', content: 'หลักการ: สร้างทีม 2 ขา ขาซ้ายและขาขวา' },
        { type: 'list', content: ['Spillover - เมื่อทีมของคุณแนะนำคนใหม่ อาจถูกวางในทีมของคุณ', 'Matching - เมื่อยอด PV ทั้ง 2 ขาเท่ากัน คุณจะได้โบนัส', 'Carry Forward - ยอดที่เหลือจากขาที่มากกว่าจะถูกยกไปรอบหน้า'] },
        { type: 'step', content: ['สร้างขาซ้าย: แนะนำสมาชิกคนแรกของคุณ', 'สร้างขาขวา: แนะนำสมาชิกคนที่สอง', 'สอนทีมให้ทำเหมือนกัน: ทีมขยายแบบทวีคูณ', 'รักษาสมดุล: ให้ทั้ง 2 ขาเติบโตพร้อมกัน', 'รับโบนัส: เมื่อยอดเท่ากัน = ได้เงิน!'] },
      ],
    },
    {
      id: 5,
      title: 'กลยุทธ์ขั้นสูง',
      icon: '🎯',
      gradientColors: ['#EC4899', '#BE185D'],
      content: [
        { type: 'heading', content: 'กลยุทธ์ที่แม่ทีมระดับ Diamond ใช้' },
        { type: 'highlight', content: 'Power Leg Strategy - สร้างขาแข็งแรงก่อน' },
        { type: 'paragraph', content: 'โฟกัสที่ขาใดขาหนึ่งให้แข็งแรงก่อน แล้วค่อยสร้างอีกขา' },
        { type: 'highlight', content: 'Team Building System - ระบบสร้างทีม' },
        { type: 'paragraph', content: 'สร้างระบบการสอนที่ทำซ้ำได้ ให้ทีมทุกคนทำตาม' },
        { type: 'highlight', content: 'Social Media Funnel - สร้างช่องทางลูกค้า' },
        { type: 'paragraph', content: 'ใช้โซเชียลมีเดียสร้างความสนใจ และนำเข้าสู่ระบบ' },
        { type: 'tip', content: 'กลยุทธ์ที่ดีที่สุดคือกลยุทธ์ที่คุณทำได้สม่ำเสมอ!' },
      ],
    },
  ],
};

export default function WealthGuideScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';

  // State
  const [selectedBook, setSelectedBook] = useState<WealthBook | null>(null);
  const [selectedChapter, setSelectedChapter] = useState<Chapter | null>(null);
  const [showChapterModal, setShowChapterModal] = useState(false);

  // หนังสือทั้งหมด
  const books = useMemo(() => [WEALTH_GUIDE_BASIC, WEALTH_GUIDE_PRO], []);

  // เปิดหนังสือ
  const handleOpenBook = (book: WealthBook) => {
    setSelectedBook(book);
  };

  // เปิดบท
  const handleOpenChapter = (chapter: Chapter) => {
    setSelectedChapter(chapter);
    setShowChapterModal(true);
  };

  // กลับไปเลือกหนังสือ
  const handleBackToBooks = () => {
    setSelectedBook(null);
    setSelectedChapter(null);
  };

  // เปิดเว็บเวอร์ชัน Pro
  const handleOpenWebVersion = () => {
    Linking.openURL('https://thaiprompt.com/user/wealth-guide-pro');
  };

  // Render content block
  const renderContentBlock = (block: ContentBlock, index: number) => {
    switch (block.type) {
      case 'heading':
        return (
          <Text key={index} style={[styles.contentHeading, isDark && styles.textLight]}>
            {block.content as string}
          </Text>
        );
      case 'paragraph':
        return (
          <Text key={index} style={[styles.contentParagraph, isDark && styles.textMuted]}>
            {block.content as string}
          </Text>
        );
      case 'list':
        return (
          <View key={index} style={styles.listContainer}>
            {(block.content as string[]).map((item, i) => (
              <View key={i} style={styles.listItem}>
                <Text style={[styles.listBullet, isDark && styles.textAccent]}>•</Text>
                <Text style={[styles.listText, isDark && styles.textMuted]}>{item}</Text>
              </View>
            ))}
          </View>
        );
      case 'step':
        return (
          <View key={index} style={styles.stepContainer}>
            {(block.content as string[]).map((item, i) => (
              <View key={i} style={[styles.stepItem, isDark && styles.stepItemDark]}>
                <View style={styles.stepNumber}>
                  <Text style={styles.stepNumberText}>{i + 1}</Text>
                </View>
                <Text style={[styles.stepText, isDark && styles.textMuted]}>{item}</Text>
              </View>
            ))}
          </View>
        );
      case 'tip':
        return (
          <View key={index} style={[styles.tipContainer, isDark && styles.tipContainerDark]}>
            <Text style={styles.tipIcon}>💡</Text>
            <Text style={[styles.tipText, isDark && styles.textMuted]}>{block.content as string}</Text>
          </View>
        );
      case 'highlight':
        return (
          <LinearGradient
            key={index}
            colors={isDark ? ['rgba(139, 92, 246, 0.3)', 'rgba(59, 130, 246, 0.2)'] : ['rgba(139, 92, 246, 0.15)', 'rgba(59, 130, 246, 0.1)']}
            style={styles.highlightContainer}
          >
            <Text style={[styles.highlightText, isDark && styles.textLight]}>
              {block.content as string}
            </Text>
          </LinearGradient>
        );
      default:
        return null;
    }
  };

  return (
    <View style={[styles.container, isDark && styles.containerDark]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />

      {/* Header */}
      <LinearGradient
        colors={isDark ? ['#1F2937', '#111827'] : ['#F59E0B', '#D97706']}
        style={styles.header}
      >
        <TouchableOpacity
          style={styles.backButton}
          onPress={selectedBook ? handleBackToBooks : () => router.back()}
        >
          <Ionicons name="arrow-back" size={24} color="#FFF" />
        </TouchableOpacity>
        <View style={styles.headerTitleContainer}>
          <Text style={styles.headerTitle}>
            {selectedBook ? selectedBook.title : '📚 หนังสือเส้นทางเศรษฐี'}
          </Text>
          {selectedBook && (
            <Text style={styles.headerSubtitle}>{selectedBook.subtitle}</Text>
          )}
        </View>
        {selectedBook?.id === 'pro' && (
          <TouchableOpacity style={styles.webButton} onPress={handleOpenWebVersion}>
            <Ionicons name="globe" size={20} color="#FFF" />
          </TouchableOpacity>
        )}
      </LinearGradient>

      {/* Content */}
      {!selectedBook ? (
        // หน้าเลือกหนังสือ
        <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
          <Text style={[styles.sectionTitle, isDark && styles.textLight]}>
            เลือกหนังสือที่ต้องการอ่าน
          </Text>

          {books.map((book) => (
            <TouchableOpacity
              key={book.id}
              style={styles.bookCard}
              onPress={() => handleOpenBook(book)}
              activeOpacity={0.8}
            >
              <LinearGradient
                colors={book.gradientColors}
                style={styles.bookGradient}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
              >
                <Text style={styles.bookIcon}>{book.icon}</Text>
                <Text style={styles.bookTitle}>{book.title}</Text>
                <Text style={styles.bookSubtitle}>{book.subtitle}</Text>
                <Text style={styles.bookDescription}>{book.description}</Text>

                <View style={styles.bookFeatures}>
                  {book.features.map((feature, i) => (
                    <View key={i} style={styles.bookFeature}>
                      <Text style={styles.bookFeatureText}>{feature}</Text>
                    </View>
                  ))}
                </View>

                <View style={styles.bookCta}>
                  <Text style={styles.bookCtaText}>อ่านเลย</Text>
                  <Ionicons name="arrow-forward" size={16} color="#FFF" />
                </View>
              </LinearGradient>
            </TouchableOpacity>
          ))}

          <View style={styles.bottomPadding} />
        </ScrollView>
      ) : (
        // หน้าสารบัญหนังสือ
        <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
          <Text style={[styles.sectionTitle, isDark && styles.textLight]}>
            สารบัญ - {selectedBook.chapters.length} บท
          </Text>

          {selectedBook.chapters.map((chapter) => (
            <TouchableOpacity
              key={chapter.id}
              style={[styles.chapterCard, isDark && styles.chapterCardDark]}
              onPress={() => handleOpenChapter(chapter)}
              activeOpacity={0.7}
            >
              <LinearGradient
                colors={chapter.gradientColors}
                style={styles.chapterIcon}
              >
                <Text style={styles.chapterIconText}>{chapter.icon}</Text>
              </LinearGradient>
              <View style={styles.chapterInfo}>
                <Text style={[styles.chapterLabel, isDark && styles.textAccent]}>
                  บทที่ {chapter.id}
                </Text>
                <Text style={[styles.chapterTitle, isDark && styles.textLight]}>
                  {chapter.title}
                </Text>
              </View>
              <Ionicons
                name="chevron-forward"
                size={20}
                color={isDark ? '#9CA3AF' : '#6B7280'}
              />
            </TouchableOpacity>
          ))}

          <View style={styles.bottomPadding} />
        </ScrollView>
      )}

      {/* Chapter Modal */}
      <Modal
        visible={showChapterModal}
        animationType="slide"
        presentationStyle="pageSheet"
        onRequestClose={() => setShowChapterModal(false)}
      >
        <View style={[styles.modalContainer, isDark && styles.containerDark]}>
          {/* Modal Header */}
          <LinearGradient
            colors={selectedChapter?.gradientColors || ['#8B5CF6', '#6D28D9']}
            style={styles.modalHeader}
          >
            <TouchableOpacity
              style={styles.modalCloseButton}
              onPress={() => setShowChapterModal(false)}
            >
              <Ionicons name="close" size={24} color="#FFF" />
            </TouchableOpacity>
            <View style={styles.modalHeaderContent}>
              <Text style={styles.modalIcon}>{selectedChapter?.icon}</Text>
              <Text style={styles.modalChapterLabel}>
                บทที่ {selectedChapter?.id}
              </Text>
              <Text style={styles.modalTitle}>{selectedChapter?.title}</Text>
            </View>
          </LinearGradient>

          {/* Modal Content */}
          <ScrollView
            style={styles.modalContent}
            showsVerticalScrollIndicator={false}
          >
            {selectedChapter?.content.map((block, index) =>
              renderContentBlock(block, index)
            )}

            {/* Navigation */}
            <View style={styles.chapterNav}>
              {selectedChapter && selectedChapter.id > 1 && (
                <TouchableOpacity
                  style={[styles.navButton, isDark && styles.navButtonDark]}
                  onPress={() => {
                    const prevChapter = selectedBook?.chapters.find(
                      (c) => c.id === (selectedChapter?.id || 0) - 1
                    );
                    if (prevChapter) setSelectedChapter(prevChapter);
                  }}
                >
                  <Ionicons name="chevron-back" size={20} color={isDark ? '#FFF' : '#374151'} />
                  <Text style={[styles.navButtonText, isDark && styles.textLight]}>
                    บทก่อนหน้า
                  </Text>
                </TouchableOpacity>
              )}

              {selectedChapter &&
                selectedBook &&
                selectedChapter.id < selectedBook.chapters.length && (
                  <TouchableOpacity
                    style={[styles.navButton, styles.navButtonNext]}
                    onPress={() => {
                      const nextChapter = selectedBook?.chapters.find(
                        (c) => c.id === (selectedChapter?.id || 0) + 1
                      );
                      if (nextChapter) setSelectedChapter(nextChapter);
                    }}
                  >
                    <Text style={styles.navButtonTextNext}>บทถัดไป</Text>
                    <Ionicons name="chevron-forward" size={20} color="#FFF" />
                  </TouchableOpacity>
                )}
            </View>

            <View style={styles.bottomPadding} />
          </ScrollView>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F3F4F6',
  },
  containerDark: {
    backgroundColor: '#111827',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingTop: 50,
    paddingBottom: 20,
    paddingHorizontal: 16,
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.2)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  headerTitleContainer: {
    flex: 1,
    marginLeft: 12,
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFF',
  },
  headerSubtitle: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
    marginTop: 2,
  },
  webButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.2)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  content: {
    flex: 1,
    padding: 16,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#374151',
    marginBottom: 16,
  },
  textLight: {
    color: '#F3F4F6',
  },
  textMuted: {
    color: '#9CA3AF',
  },
  textAccent: {
    color: '#8B5CF6',
  },

  // Book Card
  bookCard: {
    marginBottom: 16,
    borderRadius: 20,
    overflow: 'hidden',
    elevation: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
  },
  bookGradient: {
    padding: 24,
    alignItems: 'center',
  },
  bookIcon: {
    fontSize: 64,
    marginBottom: 12,
  },
  bookTitle: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#FFF',
    textAlign: 'center',
    marginBottom: 4,
  },
  bookSubtitle: {
    fontSize: 16,
    color: 'rgba(255,255,255,0.9)',
    textAlign: 'center',
    marginBottom: 12,
  },
  bookDescription: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
    textAlign: 'center',
    marginBottom: 16,
    lineHeight: 20,
  },
  bookFeatures: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'center',
    gap: 8,
    marginBottom: 16,
  },
  bookFeature: {
    backgroundColor: 'rgba(255,255,255,0.2)',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.3)',
  },
  bookFeatureText: {
    color: '#FFF',
    fontSize: 12,
    fontWeight: '600',
  },
  bookCta: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.25)',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 30,
    gap: 8,
  },
  bookCtaText: {
    color: '#FFF',
    fontSize: 16,
    fontWeight: 'bold',
  },

  // Chapter Card
  chapterCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFF',
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
  },
  chapterCardDark: {
    backgroundColor: '#1F2937',
  },
  chapterIcon: {
    width: 50,
    height: 50,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
  },
  chapterIconText: {
    fontSize: 24,
  },
  chapterInfo: {
    flex: 1,
    marginLeft: 12,
  },
  chapterLabel: {
    fontSize: 12,
    color: '#8B5CF6',
    fontWeight: '600',
    marginBottom: 2,
  },
  chapterTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#374151',
  },

  // Modal
  modalContainer: {
    flex: 1,
    backgroundColor: '#F3F4F6',
  },
  modalHeader: {
    paddingTop: 50,
    paddingBottom: 24,
    paddingHorizontal: 16,
  },
  modalCloseButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.2)',
    justifyContent: 'center',
    alignItems: 'center',
    alignSelf: 'flex-end',
  },
  modalHeaderContent: {
    alignItems: 'center',
    marginTop: -20,
  },
  modalIcon: {
    fontSize: 48,
    marginBottom: 8,
  },
  modalChapterLabel: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
    fontWeight: '600',
  },
  modalTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFF',
    textAlign: 'center',
  },
  modalContent: {
    flex: 1,
    padding: 16,
  },

  // Content Blocks
  contentHeading: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1F2937',
    marginTop: 16,
    marginBottom: 12,
  },
  contentParagraph: {
    fontSize: 15,
    color: '#4B5563',
    lineHeight: 24,
    marginBottom: 12,
  },
  listContainer: {
    marginBottom: 16,
  },
  listItem: {
    flexDirection: 'row',
    marginBottom: 8,
  },
  listBullet: {
    fontSize: 16,
    color: '#8B5CF6',
    marginRight: 8,
    lineHeight: 22,
  },
  listText: {
    flex: 1,
    fontSize: 15,
    color: '#4B5563',
    lineHeight: 22,
  },
  stepContainer: {
    marginBottom: 16,
  },
  stepItem: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    backgroundColor: '#EFF6FF',
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
  },
  stepItemDark: {
    backgroundColor: 'rgba(59, 130, 246, 0.15)',
  },
  stepNumber: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: '#3B82F6',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  stepNumberText: {
    color: '#FFF',
    fontWeight: 'bold',
    fontSize: 14,
  },
  stepText: {
    flex: 1,
    fontSize: 15,
    color: '#374151',
    lineHeight: 22,
  },
  tipContainer: {
    flexDirection: 'row',
    backgroundColor: '#FEF3C7',
    borderRadius: 12,
    padding: 12,
    marginBottom: 16,
    borderLeftWidth: 4,
    borderLeftColor: '#F59E0B',
  },
  tipContainerDark: {
    backgroundColor: 'rgba(245, 158, 11, 0.15)',
  },
  tipIcon: {
    fontSize: 18,
    marginRight: 10,
  },
  tipText: {
    flex: 1,
    fontSize: 14,
    color: '#92400E',
    lineHeight: 20,
  },
  highlightContainer: {
    borderRadius: 12,
    padding: 14,
    marginBottom: 12,
  },
  highlightText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#5B21B6',
  },

  // Chapter Navigation
  chapterNav: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 24,
    gap: 12,
  },
  navButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#E5E7EB',
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderRadius: 12,
    gap: 8,
  },
  navButtonDark: {
    backgroundColor: '#374151',
  },
  navButtonNext: {
    backgroundColor: '#8B5CF6',
    marginLeft: 'auto',
  },
  navButtonText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#374151',
  },
  navButtonTextNext: {
    fontSize: 14,
    fontWeight: '600',
    color: '#FFF',
  },

  bottomPadding: {
    height: 32,
  },
});
