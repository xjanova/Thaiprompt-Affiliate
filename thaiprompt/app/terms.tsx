/**
 * Terms Screen - เงื่อนไขการใช้งาน
 * แสดงเงื่อนไขและข้อกำหนดในการใช้งานแอพ
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

// เนื้อหาเงื่อนไขการใช้งาน
const TERMS_SECTIONS = [
  {
    title: '1. การยอมรับเงื่อนไข',
    content: `การใช้งานแอปพลิเคชัน Thaiprompt ถือว่าท่านได้อ่านและยอมรับเงื่อนไขการใช้งานทั้งหมดแล้ว หากท่านไม่ยอมรับเงื่อนไขเหล่านี้ กรุณาหยุดใช้งานแอปพลิเคชันทันที

เงื่อนไขเหล่านี้อาจมีการเปลี่ยนแปลงได้โดยไม่ต้องแจ้งให้ทราบล่วงหน้า ท่านมีหน้าที่ตรวจสอบเงื่อนไขเป็นระยะ`,
  },
  {
    title: '2. การสมัครสมาชิก',
    content: `• ผู้ใช้ต้องมีอายุ 18 ปีขึ้นไป
• ข้อมูลที่ให้ในการสมัครต้องเป็นความจริงและถูกต้อง
• ผู้ใช้ต้องรักษาความลับของข้อมูลการเข้าสู่ระบบ
• ห้ามใช้บัญชีผู้อื่นหรืออนุญาตให้ผู้อื่นใช้บัญชีของท่าน
• บริษัทขอสงวนสิทธิ์ในการระงับหรือยกเลิกบัญชีที่ละเมิดเงื่อนไข`,
  },
  {
    title: '3. การใช้งานแอปพลิเคชัน',
    content: `ผู้ใช้ตกลงที่จะ:
• ใช้งานแอปพลิเคชันเพื่อวัตถุประสงค์ที่ถูกกฎหมายเท่านั้น
• ไม่ใช้แอปพลิเคชันในทางที่ผิดกฎหมายหรือฉ้อโกง
• ไม่พยายามเข้าถึงระบบโดยไม่ได้รับอนุญาต
• ไม่แพร่กระจายไวรัสหรือโค้ดที่เป็นอันตราย
• ไม่ละเมิดสิทธิ์ของผู้อื่น`,
  },
  {
    title: '4. ระบบ Affiliate',
    content: `• รายได้จากการแนะนำจะถูกคำนวณตามเงื่อนไขที่กำหนด
• การเบิกเงินต้องผ่านการยืนยันตัวตน (KYC)
• บริษัทขอสงวนสิทธิ์ในการตรวจสอบและระงับธุรกรรมที่สงสัย
• ห้ามใช้วิธีการที่ไม่เหมาะสมในการหาผู้แนะนำ
• ค่าคอมมิชชั่นอาจมีการเปลี่ยนแปลงตามนโยบายของบริษัท`,
  },
  {
    title: '5. การชำระเงินและการเบิกเงิน',
    content: `• การชำระเงินต้องเป็นไปตามวิธีการที่กำหนด
• การเบิกเงินจะดำเนินการภายใน 3-7 วันทำการ
• ขั้นต่ำในการเบิกเงินคือ 100 บาท
• บริษัทขอสงวนสิทธิ์ในการหักค่าธรรมเนียมตามที่กำหนด
• ธุรกรรมที่สำเร็จแล้วไม่สามารถยกเลิกได้`,
  },
  {
    title: '6. ทรัพย์สินทางปัญญา',
    content: `เนื้อหา โลโก้ กราฟิก และส่วนประกอบอื่นๆ ในแอปพลิเคชันเป็นทรัพย์สินทางปัญญาของบริษัท ห้ามคัดลอก ดัดแปลง หรือเผยแพร่โดยไม่ได้รับอนุญาต`,
  },
  {
    title: '7. การจำกัดความรับผิดชอบ',
    content: `บริษัทไม่รับผิดชอบต่อ:
• ความเสียหายที่เกิดจากการใช้งานแอปพลิเคชัน
• การสูญหายของข้อมูลหรือรายได้
• การหยุดให้บริการชั่วคราวเพื่อบำรุงรักษา
• ความผิดพลาดทางเทคนิคที่อยู่นอกเหนือการควบคุม`,
  },
  {
    title: '8. การยกเลิกบัญชี',
    content: `• ผู้ใช้สามารถยกเลิกบัญชีได้ตลอดเวลา
• การยกเลิกบัญชีจะส่งผลให้สูญเสียสิทธิ์ทั้งหมด
• เงินคงเหลือในบัญชีต้องเบิกออกก่อนยกเลิก
• บริษัทอาจยกเลิกบัญชีที่ละเมิดเงื่อนไขโดยไม่ต้องแจ้งล่วงหน้า`,
  },
  {
    title: '9. กฎหมายที่ใช้บังคับ',
    content: `เงื่อนไขนี้อยู่ภายใต้กฎหมายแห่งราชอาณาจักรไทย ข้อพิพาทใดๆ จะถูกระงับโดยศาลที่มีเขตอำนาจในประเทศไทย`,
  },
  {
    title: '10. การติดต่อ',
    content: `หากมีข้อสงสัยเกี่ยวกับเงื่อนไขการใช้งาน สามารถติดต่อได้ที่:
• อีเมล: support@thaiprompt.online
• LINE Official: @thaiprompt`,
  },
];

export default function TermsScreen() {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  return (
    <View style={[styles.container, !isDark && styles.containerLight]}>
      <StatusBar
        barStyle="light-content"
        backgroundColor={isDark ? '#0F172A' : '#3B82F6'}
      />

      {/* Header */}
      <LinearGradient
        colors={isDark ? ['#1E3A8A', '#1E40AF'] : ['#3B82F6', '#2563EB']}
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
          <Text style={styles.headerTitle}>เงื่อนไขการใช้งาน</Text>
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
          <Text style={{ fontSize: 32, textAlign: 'center' }}>📋</Text>
          <Text style={[styles.introTitle, !isDark && styles.textDark]}>
            เงื่อนไขและข้อกำหนด
          </Text>
          <Text style={[styles.introSubtitle, !isDark && styles.textMuted]}>
            Terms and Conditions
          </Text>
          <Text style={[styles.introDate, !isDark && styles.textMuted]}>
            อัปเดตล่าสุด: 1 มกราคม 2568
          </Text>
        </View>

        {/* Terms Sections */}
        {TERMS_SECTIONS.map((section, index) => (
          <View
            key={index}
            style={[styles.sectionCard, !isDark && styles.cardLight]}
          >
            <Text style={[styles.sectionTitle, !isDark && styles.textDark]}>
              {section.title}
            </Text>
            <Text style={[styles.sectionContent, !isDark && styles.textMuted]}>
              {section.content}
            </Text>
          </View>
        ))}

        {/* Footer */}
        <View style={styles.footer}>
          <Text style={[styles.footerText, !isDark && styles.textMuted]}>
            การใช้งานแอปพลิเคชันถือว่าท่านยอมรับเงื่อนไขทั้งหมด
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
    backgroundColor: 'rgba(59, 130, 246, 0.1)',
    borderRadius: 16,
    padding: 24,
    marginBottom: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(59, 130, 246, 0.2)',
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
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 8,
  },
  sectionContent: {
    fontSize: 14,
    color: '#94A3B8',
    lineHeight: 22,
  },
  footer: {
    marginTop: 16,
    padding: 16,
    alignItems: 'center',
  },
  footerText: {
    fontSize: 12,
    color: '#64748B',
    textAlign: 'center',
  },
});
