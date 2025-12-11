/**
 * Privacy Policy Screen - นโยบายความเป็นส่วนตัว
 * แสดงนโยบายการเก็บรวบรวมและใช้ข้อมูลส่วนบุคคล
 */

import React from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  StyleSheet,
  StatusBar,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { useAppStore } from '@/stores/appStore';

// เนื้อหานโยบายความเป็นส่วนตัว
const PRIVACY_SECTIONS = [
  {
    title: 'บทนำ',
    icon: '📖',
    content: `บริษัท ไทยพรอมท์ จำกัด ("บริษัท" หรือ "เรา") เคารพในความเป็นส่วนตัวของท่าน และมุ่งมั่นที่จะปกป้องข้อมูลส่วนบุคคลของท่าน นโยบายนี้อธิบายถึงวิธีการที่เราเก็บรวบรวม ใช้ เปิดเผย และปกป้องข้อมูลของท่าน

นโยบายนี้สอดคล้องกับ พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA)`,
  },
  {
    title: 'ข้อมูลที่เราเก็บรวบรวม',
    icon: '📋',
    content: `เราเก็บรวบรวมข้อมูลดังต่อไปนี้:

ข้อมูลที่ท่านให้โดยตรง:
• ชื่อ-นามสกุล
• อีเมล และเบอร์โทรศัพท์
• ที่อยู่
• ข้อมูลบัญชีธนาคาร (สำหรับการเบิกเงิน)
• รูปถ่ายใบหน้าและบัตรประชาชน (สำหรับ KYC)

ข้อมูลที่เก็บอัตโนมัติ:
• ข้อมูลอุปกรณ์ (รุ่น, ระบบปฏิบัติการ)
• ข้อมูลการใช้งานแอป
• IP Address และตำแหน่งที่ตั้ง (ถ้าอนุญาต)
• Cookies และ tracking data`,
  },
  {
    title: 'วัตถุประสงค์ในการใช้ข้อมูล',
    icon: '🎯',
    content: `เราใช้ข้อมูลของท่านเพื่อ:

• ให้บริการและดำเนินการตามคำร้องขอ
• ยืนยันตัวตนและป้องกันการฉ้อโกง
• คำนวณและจ่ายค่าคอมมิชชั่น
• ส่งการแจ้งเตือนและข่าวสาร
• ปรับปรุงและพัฒนาบริการ
• วิเคราะห์การใช้งานเพื่อปรับปรุง UX
• ปฏิบัติตามข้อกำหนดทางกฎหมาย
• ติดต่อท่านเมื่อจำเป็น`,
  },
  {
    title: 'การแชร์ข้อมูล',
    icon: '🔄',
    content: `เราอาจแชร์ข้อมูลกับ:

• บริษัทในเครือและพันธมิตรทางธุรกิจ
• ผู้ให้บริการชำระเงิน
• ผู้ให้บริการ Cloud และ IT
• หน่วยงานราชการตามที่กฎหมายกำหนด

เราจะไม่ขายข้อมูลส่วนบุคคลของท่านให้กับบุคคลที่สาม`,
  },
  {
    title: 'การรักษาความปลอดภัย',
    icon: '🔒',
    content: `เราใช้มาตรการรักษาความปลอดภัยดังนี้:

• การเข้ารหัสข้อมูล (SSL/TLS Encryption)
• การยืนยันตัวตนแบบหลายชั้น
• การเข้าถึงข้อมูลแบบจำกัด
• การตรวจสอบและเฝ้าระวังระบบ
• การสำรองข้อมูลเป็นประจำ
• การฝึกอบรมพนักงานด้านความปลอดภัย`,
  },
  {
    title: 'สิทธิ์ของเจ้าของข้อมูล',
    icon: '⚖️',
    content: `ท่านมีสิทธิ์ดังนี้ตาม PDPA:

• สิทธิ์ในการเข้าถึงข้อมูล
• สิทธิ์ในการแก้ไขข้อมูลให้ถูกต้อง
• สิทธิ์ในการลบข้อมูล
• สิทธิ์ในการระงับการใช้ข้อมูล
• สิทธิ์ในการคัดค้านการประมวลผล
• สิทธิ์ในการโอนย้ายข้อมูล
• สิทธิ์ในการถอนความยินยอม

ติดต่อ DPO: privacy@thaiprompt.online`,
  },
  {
    title: 'Cookies และ Tracking',
    icon: '🍪',
    content: `เราใช้ Cookies เพื่อ:

• จดจำการเข้าสู่ระบบ
• บันทึกการตั้งค่าของท่าน
• วิเคราะห์การใช้งาน
• ปรับปรุงประสบการณ์ผู้ใช้

ท่านสามารถปิดการใช้งาน Cookies ได้ในการตั้งค่าอุปกรณ์ แต่อาจส่งผลต่อการใช้งานบางฟีเจอร์`,
  },
  {
    title: 'การเก็บรักษาข้อมูล',
    icon: '📦',
    content: `เราเก็บรักษาข้อมูลตามระยะเวลาดังนี้:

• ข้อมูลบัญชี: ตลอดระยะเวลาที่ใช้บริการ + 5 ปี
• ข้อมูลธุรกรรม: 7 ปี (ตามกฎหมายภาษี)
• ข้อมูล KYC: 5 ปีหลังปิดบัญชี
• ข้อมูลการใช้งาน: 2 ปี

หลังครบกำหนด ข้อมูลจะถูกลบหรือทำให้ไม่สามารถระบุตัวตนได้`,
  },
  {
    title: 'การเปลี่ยนแปลงนโยบาย',
    icon: '📝',
    content: `เราอาจปรับปรุงนโยบายนี้เป็นครั้งคราว โดยจะแจ้งให้ท่านทราบผ่าน:

• การแจ้งเตือนในแอป
• อีเมล
• ประกาศบนเว็บไซต์

การใช้งานต่อหลังการเปลี่ยนแปลงถือว่าท่านยอมรับนโยบายใหม่`,
  },
  {
    title: 'ติดต่อเรา',
    icon: '📞',
    content: `หากมีคำถามเกี่ยวกับนโยบายนี้ ติดต่อ:

บริษัท ไทยพรอมท์ จำกัด
อีเมล: privacy@thaiprompt.online
โทร: 02-XXX-XXXX
LINE: @thaiprompt

เจ้าหน้าที่คุ้มครองข้อมูลส่วนบุคคล (DPO)
อีเมล: dpo@thaiprompt.online`,
  },
];

export default function PrivacyScreen() {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  return (
    <View style={[styles.container, !isDark && styles.containerLight]}>
      <StatusBar
        barStyle="light-content"
        backgroundColor={isDark ? '#0F172A' : '#7C3AED'}
      />

      {/* Header */}
      <LinearGradient
        colors={isDark ? ['#5B21B6', '#6D28D9'] : ['#7C3AED', '#8B5CF6']}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={styles.header}
      >
        <View style={styles.headerContent}>
          <Pressable
            style={styles.backButton}
            onPress={() => router.back()}
          >
            <Text style={styles.backButtonText}>‹</Text>
          </Pressable>
          <Text style={styles.headerTitle}>นโยบายความเป็นส่วนตัว</Text>
          <View style={styles.placeholder} />
        </View>
      </LinearGradient>

      {/* Content */}
      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* Intro */}
        <View style={[styles.introCard, !isDark && styles.cardLight]}>
          <Text style={{ fontSize: 32, textAlign: 'center' }}>🔐</Text>
          <Text style={[styles.introTitle, !isDark && styles.textDark]}>
            นโยบายความเป็นส่วนตัว
          </Text>
          <Text style={[styles.introSubtitle, !isDark && styles.textMuted]}>
            Privacy Policy
          </Text>
          <Text style={[styles.introDate, !isDark && styles.textMuted]}>
            อัปเดตล่าสุด: 1 มกราคม 2568
          </Text>
        </View>

        {/* PDPA Badge */}
        <View style={[styles.pdpaBadge, !isDark && styles.pdpaBadgeLight]}>
          <Text style={{ fontSize: 20 }}>🛡️</Text>
          <Text style={[styles.pdpaText, !isDark && styles.textDark]}>
            สอดคล้องกับ พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA)
          </Text>
        </View>

        {/* Privacy Sections */}
        {PRIVACY_SECTIONS.map((section, index) => (
          <View
            key={index}
            style={[styles.sectionCard, !isDark && styles.cardLight]}
          >
            <View style={styles.sectionHeader}>
              <Text style={{ fontSize: 24 }}>{section.icon}</Text>
              <Text style={[styles.sectionTitle, !isDark && styles.textDark]}>
                {section.title}
              </Text>
            </View>
            <Text style={[styles.sectionContent, !isDark && styles.textMuted]}>
              {section.content}
            </Text>
          </View>
        ))}

        {/* Footer */}
        <View style={styles.footer}>
          <Text style={[styles.footerText, !isDark && styles.textMuted]}>
            ความเป็นส่วนตัวของท่านเป็นสิ่งสำคัญสำหรับเรา
          </Text>
          <Text style={[styles.footerSubtext, !isDark && styles.textMuted]}>
            © 2024-2025 Thaiprompt Co., Ltd. All rights reserved.
          </Text>
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F172A',
  },
  containerLight: {
    backgroundColor: '#F8FAFC',
  },
  header: {
    paddingTop: 50,
    paddingBottom: 16,
    paddingHorizontal: 16,
  },
  headerContent: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  backButtonText: {
    fontSize: 28,
    color: '#FFFFFF',
    fontWeight: 'bold',
    marginTop: -2,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  placeholder: {
    width: 40,
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 100,
  },
  introCard: {
    backgroundColor: 'rgba(139, 92, 246, 0.1)',
    borderRadius: 16,
    padding: 24,
    marginBottom: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(139, 92, 246, 0.2)',
  },
  cardLight: {
    backgroundColor: '#FFFFFF',
    borderColor: '#E2E8F0',
  },
  introTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 12,
  },
  introSubtitle: {
    fontSize: 14,
    color: '#94A3B8',
    marginTop: 4,
  },
  introDate: {
    fontSize: 12,
    color: '#64748B',
    marginTop: 8,
  },
  textDark: {
    color: '#1E293B',
  },
  textMuted: {
    color: '#64748B',
  },
  pdpaBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(16, 185, 129, 0.1)',
    borderRadius: 12,
    padding: 12,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: 'rgba(16, 185, 129, 0.2)',
  },
  pdpaBadgeLight: {
    backgroundColor: '#ECFDF5',
    borderColor: '#A7F3D0',
  },
  pdpaText: {
    fontSize: 13,
    color: '#10B981',
    marginLeft: 10,
    flex: 1,
    fontWeight: '500',
  },
  sectionCard: {
    backgroundColor: 'rgba(255, 255, 255, 0.05)',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.1)',
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginLeft: 12,
  },
  sectionContent: {
    fontSize: 14,
    color: '#94A3B8',
    lineHeight: 22,
  },
  footer: {
    marginTop: 24,
    padding: 16,
    alignItems: 'center',
  },
  footerText: {
    fontSize: 14,
    color: '#94A3B8',
    textAlign: 'center',
    fontWeight: '500',
  },
  footerSubtext: {
    fontSize: 12,
    color: '#64748B',
    textAlign: 'center',
    marginTop: 8,
  },
});
