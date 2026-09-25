/**
 * ตลาดสด (ชั่วคราว) — เปิดตลาดสดบนเว็บแบบล็อกอินให้อัตโนมัติ
 *
 * หน้าจอตลาดสดเต็มรูปแบบในแอป (รายการร้านใกล้บ้าน, เลือกตัวเลือกอาหาร, ตะกร้า, ติดตามไรเดอร์)
 * จะมาแทนไฟล์นี้ในรอบถัดไป — ระหว่างนี้ใช้หน้าเว็บ /taladsod ผ่าน web-session
 */

import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useAuthStore } from '@/stores/authStore';
import { router } from 'expo-router';
import { BannerSlider, Button3D, Card3D, Screen, SectionHeader, WebsiteButton } from '@/components/ui';
import { useTheme, spacing, typography } from '@/theme';

const HIGHLIGHTS = [
  { icon: '🍛', title: 'อาหารร้อนๆ จากร้านใกล้บ้าน', desc: 'เลือกเมนู เลือกเนื้อสัตว์ เพิ่มไข่ดาวได้' },
  { icon: '🛵', title: 'ไรเดอร์ในชุมชนส่งให้', desc: 'ดูตำแหน่งไรเดอร์ระหว่างส่งได้' },
  { icon: '💳', title: 'จ่ายง่าย', desc: 'จ่ายด้วยกระเป๋าเงิน หรือเก็บเงินปลายทาง' },
];

export default function TaladsodScreen() {
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);

  return (
    <Screen title="ตลาดสด" subtitle="ของสดและอาหารจากร้านใกล้บ้าน">
      <View style={styles.bannerWrap}>
        <BannerSlider placement="taladsod" height={180} />
      </View>

      <Card3D gradientBorder padding={spacing.xl}>
        <Text style={[typography.h2, { color: colors.textStrong }]}>สั่งจากตลาดสดได้เลย</Text>
        <Text style={[typography.body, styles.lead, { color: colors.textMuted }]}>
          ตอนนี้สั่งผ่านหน้าเว็บตลาดสด ระบบจะล็อกอินให้อัตโนมัติ คำสั่งซื้อและกระเป๋าเงินใช้บัญชีเดียวกับในแอป
        </Text>

        {isAuthenticated ? (
          <WebsiteButton
            path="/taladsod"
            label="เปิดตลาดสด"
            icon="🥬"
            variant="primary"
            size="lg"
            fullWidth
            style={styles.cta}
          />
        ) : (
          <Button3D
            title="เข้าสู่ระบบเพื่อสั่งซื้อ"
            icon="🔓"
            size="lg"
            fullWidth
            onPress={() => router.push('/login')}
            style={styles.cta}
          />
        )}

        {!isAuthenticated && (
          <WebsiteButton
            path="/taladsod"
            label="ดูร้านในตลาดสดก่อน"
            variant="ghost"
            size="md"
            fullWidth
            style={styles.secondary}
          />
        )}
      </Card3D>

      <SectionHeader title="ทำไมต้องตลาดสด" style={styles.sectionHeader} />
      {HIGHLIGHTS.map((item) => (
        <Card3D key={item.title} variant="flat" padding={spacing.md} style={styles.highlight}>
          <View style={styles.highlightRow}>
            <Text style={styles.highlightIcon}>{item.icon}</Text>
            <View style={styles.flex}>
              <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{item.title}</Text>
              <Text style={[typography.caption, { color: colors.textMuted }]}>{item.desc}</Text>
            </View>
          </View>
        </Card3D>
      ))}
    </Screen>
  );
}

const styles = StyleSheet.create({
  bannerWrap: {
    // แบนเนอร์เว้นขอบเอง → ดึงให้เต็มความกว้างจอ
    marginHorizontal: -spacing.screen,
    marginBottom: spacing.lg,
  },
  lead: {
    marginTop: spacing.sm,
  },
  cta: {
    marginTop: spacing.xl,
  },
  secondary: {
    marginTop: spacing.sm,
  },
  sectionHeader: {
    marginTop: spacing.xxl,
  },
  highlight: {
    marginBottom: spacing.sm,
  },
  highlightRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  highlightIcon: {
    fontSize: 26,
  },
  flex: {
    flex: 1,
  },
});
