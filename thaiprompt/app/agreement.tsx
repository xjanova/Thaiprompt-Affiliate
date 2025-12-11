/**
 * Agreement Screen - ข้อตกลงการใช้งาน
 * แสดงข้อตกลงระหว่างผู้ใช้และบริษัท
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

// เนื้อหาข้อตกลงการใช้งาน
const AGREEMENT_SECTIONS = [
  {
    title: 'ข้อตกลงทั่วไป',
    icon: '📝',
    content: `ข้อตกลงนี้เป็นสัญญาระหว่างผู้ใช้งาน ("ท่าน") และ บริษัท ไทยพรอมท์ จำกัด ("บริษัท") ซึ่งเป็นเจ้าของและผู้ให้บริการแอปพลิเคชัน Thaiprompt

การใช้งานแอปพลิเคชันนี้ ท่านตกลงผูกพันตามข้อตกลงทั้งหมดที่ระบุไว้ในเอกสารนี้`,
  },
  {
    title: 'สิทธิ์และหน้าที่ของผู้ใช้',
    icon: '👤',
    content: `ผู้ใช้มีสิทธิ์:
• ใช้งานฟีเจอร์ทั้งหมดตามสิทธิ์ที่ได้รับ
• รับค่าตอบแทนจากการแนะนำสมาชิกใหม่
• เข้าถึงข้อมูลส่วนตัวและแก้ไขได้
• ร้องขอให้ลบข้อมูลส่วนบุคคล

ผู้ใช้มีหน้าที่:
• ปฏิบัติตามกฎระเบียบของแอปพลิเคชัน
• ให้ข้อมูลที่ถูกต้องและเป็นปัจจุบัน
• รักษาความลับของข้อมูลการเข้าสู่ระบบ
• แจ้งให้ทราบทันทีหากพบการใช้งานที่ผิดปกติ`,
  },
  {
    title: 'สิทธิ์และหน้าที่ของบริษัท',
    icon: '🏢',
    content: `บริษัทมีสิทธิ์:
• แก้ไขเปลี่ยนแปลงข้อตกลงได้ตลอดเวลา
• ระงับหรือยกเลิกบัญชีที่ละเมิดข้อตกลง
• เก็บรวบรวมข้อมูลเพื่อปรับปรุงบริการ
• ส่งการแจ้งเตือนและข่าวสารถึงผู้ใช้

บริษัทมีหน้าที่:
• ให้บริการตามที่ระบุไว้
• รักษาความปลอดภัยของข้อมูลผู้ใช้
• ดำเนินการตามคำร้องขอของผู้ใช้ตามสมควร
• แจ้งให้ทราบเมื่อมีการเปลี่ยนแปลงสำคัญ`,
  },
  {
    title: 'ข้อตกลงเรื่องค่าตอบแทน',
    icon: '💰',
    content: `• ค่าคอมมิชชั่นจะถูกคำนวณตามอัตราที่กำหนด
• การเบิกเงินต้องผ่านการยืนยันตัวตน
• ขั้นต่ำในการเบิกคือ 100 บาท
• บริษัทขอสงวนสิทธิ์ในการปรับเปลี่ยนอัตราค่าตอบแทน
• รายได้ต้องเสียภาษีตามกฎหมาย (ถ้ามี)`,
  },
  {
    title: 'การรับประกันและข้อจำกัด',
    icon: '⚖️',
    content: `บริษัทให้บริการตาม "สภาพที่เป็นอยู่" (As Is) โดย:
• ไม่รับประกันว่าบริการจะไม่มีข้อผิดพลาด
• ไม่รับผิดชอบต่อความเสียหายทางอ้อม
• ไม่รับประกันรายได้หรือผลตอบแทนใดๆ
• จำกัดความรับผิดไม่เกินค่าบริการที่ผู้ใช้ชำระ`,
  },
  {
    title: 'การยกเลิกและระงับบัญชี',
    icon: '🚫',
    content: `ผู้ใช้อาจถูกระงับบัญชีหาก:
• ให้ข้อมูลเท็จในการสมัคร
• ใช้วิธีการฉ้อโกงหรือผิดกฎหมาย
• สร้างความเสียหายให้แก่ระบบหรือผู้ใช้อื่น
• ละเมิดข้อตกลงนี้ซ้ำแล้วซ้ำเล่า

การยกเลิกบัญชีโดยผู้ใช้:
• สามารถทำได้ตลอดเวลาผ่านการติดต่อฝ่ายสนับสนุน
• ต้องเบิกเงินคงเหลือก่อนยกเลิก
• ข้อมูลบางส่วนอาจถูกเก็บรักษาตามกฎหมาย`,
  },
  {
    title: 'การระงับข้อพิพาท',
    icon: '🤝',
    content: `• ข้อพิพาทจะถูกระงับโดยการเจรจาก่อน
• หากไม่สามารถตกลงกันได้ ให้ใช้กระบวนการอนุญาโตตุลาการ
• กฎหมายที่ใช้บังคับคือกฎหมายไทย
• ศาลที่มีเขตอำนาจคือศาลในประเทศไทย`,
  },
  {
    title: 'การยินยอม',
    icon: '✅',
    content: `โดยการใช้งานแอปพลิเคชัน ท่านยืนยันว่า:
• ท่านมีอายุ 18 ปีบริบูรณ์ขึ้นไป
• ท่านได้อ่านและเข้าใจข้อตกลงนี้แล้ว
• ท่านยินยอมปฏิบัติตามข้อตกลงทั้งหมด
• ท่านยินยอมให้เก็บและใช้ข้อมูลตามนโยบายความเป็นส่วนตัว`,
  },
];

export default function AgreementScreen() {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  return (
    <View style={[styles.container, !isDark && styles.containerLight]}>
      <StatusBar
        barStyle="light-content"
        backgroundColor={isDark ? '#0F172A' : '#059669'}
      />

      {/* Header */}
      <LinearGradient
        colors={isDark ? ['#065F46', '#047857'] : ['#059669', '#10B981']}
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
          <Text style={styles.headerTitle}>ข้อตกลงการใช้งาน</Text>
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
          <Text style={{ fontSize: 32, textAlign: 'center' }}>🤝</Text>
          <Text style={[styles.introTitle, !isDark && styles.textDark]}>
            ข้อตกลงผู้ใช้บริการ
          </Text>
          <Text style={[styles.introSubtitle, !isDark && styles.textMuted]}>
            User Agreement
          </Text>
          <Text style={[styles.introDate, !isDark && styles.textMuted]}>
            มีผลบังคับใช้: 1 มกราคม 2568
          </Text>
        </View>

        {/* Agreement Sections */}
        {AGREEMENT_SECTIONS.map((section, index) => (
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
          <View style={[styles.footerCard, !isDark && styles.cardLight]}>
            <Text style={{ fontSize: 24, textAlign: 'center' }}>⚠️</Text>
            <Text style={[styles.footerTitle, !isDark && styles.textDark]}>
              สำคัญ
            </Text>
            <Text style={[styles.footerText, !isDark && styles.textMuted]}>
              หากท่านไม่ยอมรับข้อตกลงนี้ กรุณาหยุดใช้งานแอปพลิเคชันทันที
              การใช้งานต่อถือว่าท่านยอมรับข้อตกลงทั้งหมด
            </Text>
          </View>
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
    backgroundColor: 'rgba(16, 185, 129, 0.1)',
    borderRadius: 16,
    padding: 24,
    marginBottom: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(16, 185, 129, 0.2)',
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
    marginTop: 16,
    paddingBottom: 20,
  },
  footerCard: {
    backgroundColor: 'rgba(245, 158, 11, 0.1)',
    borderRadius: 12,
    padding: 16,
    borderWidth: 1,
    borderColor: 'rgba(245, 158, 11, 0.2)',
  },
  footerTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
    textAlign: 'center',
    marginTop: 8,
    marginBottom: 8,
  },
  footerText: {
    fontSize: 13,
    color: '#94A3B8',
    textAlign: 'center',
    lineHeight: 20,
  },
});
