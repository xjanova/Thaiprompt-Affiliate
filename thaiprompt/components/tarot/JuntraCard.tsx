/**
 * JuntraCard — การ์ด "ดูดวงเชิงลึกกับแม่หมอจันทรา" บนหน้าดูดวง (พื้นน้ำเงินกรมท่าลายดาว)
 *
 * กดแล้วเปิดแอปจันทรา ถ้ายังไม่ได้ติดตั้งจะพาไปหน้า Google Play ของจันทรา (ดู services/juntraLauncher.ts)
 * ไม่ลิงก์ไปเว็บจันทรา.online เพราะเว็บขายดูดวงแบบจ่ายเงินนอก Play (ผิดนโยบาย Google Play)
 * iOS ยังไม่มีแอปจันทรา → การ์ดไม่แสดง
 *
 * ต้องวางใต้ <OnHeaderProvider value> (สีตัวอักษรใช้ onHeader / onHeaderMuted)
 *
 * @example
 * <JuntraCard />
 * <JuntraCard variant="after-reading" />
 */

import React from 'react';
import { Alert, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Button3D, Icon, type IconName } from '@/components/ui';
import { GlassPanel, Medallion } from '@/components/tarot/MysticUI';
import { canOfferJuntra, openJuntraApp } from '@/services/juntraLauncher';
import { radii, spacing, typography, useTheme } from '@/theme';

export interface JuntraCardProps {
  /** 'intro' = หน้าเลือกหมวด · 'after-reading' = ท้ายผลทำนาย (ชวนถามต่อ) */
  variant?: 'intro' | 'after-reading';
  style?: StyleProp<ViewStyle>;
}

const COPY: Record<NonNullable<JuntraCardProps['variant']>, { title: string; subtitle: string }> = {
  intro: {
    title: 'ดูดวงเชิงลึกกับแม่หมอจันทรา',
    subtitle: 'อยากได้คำทำนายละเอียดเฉพาะเรื่องของคุณ ปรึกษาแม่หมอในแอปจันทรา',
  },
  'after-reading': {
    title: 'อยากรู้ลึกกว่านี้?',
    subtitle: 'เล่าเรื่องที่ค้างใจให้แม่หมอจันทราฟัง แล้วรับคำทำนายเฉพาะตัวคุณ',
  },
};

const HIGHLIGHTS: { icon: IconName; label: string }[] = [
  { icon: 'moon-stars', label: 'ไพ่และดวงดาวจากวันเดือนปีเกิดของคุณ' },
  { icon: 'hand-heart', label: 'แม่หมอตอบตรงคำถามที่คุณถาม' },
];

export const JuntraCard: React.FC<JuntraCardProps> = ({ variant = 'intro', style }) => {
  const { colors } = useTheme();

  if (!canOfferJuntra()) {
    return null;
  }

  const copy = COPY[variant];

  // Button3D ล็อกปุ่มระหว่างรอ Promise เอง (กันกดซ้ำเปิดแอป/ร้านค้าซ้อน)
  const onOpen = async () => {
    const result = await openJuntraApp();
    if (result === 'unavailable') {
      Alert.alert('ยังเปิดแอปจันทราไม่ได้', 'ค้นหา "จันทรา" ใน Google Play เพื่อติดตั้ง แล้วลองอีกครั้ง');
    }
  };

  return (
    <GlassPanel highlight padding={spacing.xl} radius={radii.xxl} style={style}>
      <View style={styles.head}>
        <Medallion icon="moon-stars" size={56} />
        <View style={styles.flex}>
          <Text accessibilityRole="header" style={[typography.serif, { color: colors.onHeader }]}>
            {copy.title}
          </Text>
          <Text style={[typography.bodySm, styles.subtitle, { color: colors.onHeaderMuted }]}>{copy.subtitle}</Text>
        </View>
      </View>

      <View style={styles.highlights}>
        {HIGHLIGHTS.map((item) => (
          <View key={item.label} style={styles.highlightRow}>
            <Icon name={item.icon} size={16} color={colors.goldLight} weight="fill" />
            <Text style={[typography.bodySm, styles.flex, { color: colors.onHeader }]}>{item.label}</Text>
          </View>
        ))}
      </View>

      <Button3D
        title="เปิดแอปจันทรา"
        icon="moon-stars"
        iconRight="arrow-square-out"
        size="lg"
        fullWidth
        onPress={onOpen}
        accessibilityHint="เปิดแอปจันทรา ถ้ายังไม่มีจะพาไปหน้า Google Play"
      />
      <Text style={[typography.caption, styles.note, { color: colors.onHeaderMuted }]}>
        แอปจันทราดาวน์โหลดฟรีจาก Google Play
      </Text>
    </GlassPanel>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  head: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  subtitle: {
    marginTop: 2,
  },
  highlights: {
    marginTop: spacing.lg,
    marginBottom: spacing.lg,
    gap: spacing.sm,
  },
  highlightRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  note: {
    marginTop: spacing.sm,
    textAlign: 'center',
  },
});

export default JuntraCard;
