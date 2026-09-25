/**
 * AuthHero — หัวหน้าเข้าสู่ระบบ/สมัครสมาชิก ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * หัวน้ำเงินกรมท่าลายกนก (RoyalHeader) + ไอคอนแอปมุมมนเรืองทอง + ชื่อแอปสีทอง
 * + คำทักทายฟอนต์มีเชิง (serifLg) + คำอธิบาย · เว้นท้ายหัวไว้ให้การ์ดฟอร์มซ้อนขึ้นมา (AUTH_OVERLAP)
 *
 * - onBack = ปุ่มย้อนกลับกระจกมุมซ้าย · right = ปุ่มมุมขวา (Button3D ghost/secondary จะเป็นแบบกระจกเอง)
 * - children = ของเพิ่มใต้คำอธิบาย (เช่น แถบขั้นตอนสมัครสมาชิก)
 *
 * @example
 * <AuthHero title="ยินดีต้อนรับกลับมา" subtitle="เข้าสู่ระบบเพื่อช้อป" onBack={goBack} />
 * <View style={{ marginTop: -AUTH_OVERLAP }}><Card3D>...</Card3D></View>
 */

import React from 'react';
import { Image, Platform, StyleSheet, View, type ViewStyle } from 'react-native';
import Animated, { FadeInDown } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { GlassIconButton, OnHeaderProvider, RoyalHeader } from '@/components/ui/RoyalHeader';
import { Text } from '@/components/ui/Text';
import { APP_INFO } from '@/config/appConfig';
import { useTheme, spacing, typography, withAlpha } from '@/theme';

/** ระยะที่การ์ดฟอร์มซ้อนขึ้นไปบนหัวน้ำเงิน */
export const AUTH_OVERLAP = 44;

const APP_ICON = require('@/assets/images/icon.png');

export interface AuthHeroProps {
  /** คำทักทาย/ชื่อหน้า (ฟอนต์มีเชิง) */
  title: string;
  subtitle?: string;
  /** ปุ่มย้อนกลับกระจก (ไม่ส่ง = ไม่แสดง) */
  onBack?: () => void;
  /** ปุ่มมุมขวาบน */
  right?: React.ReactNode;
  /** ขนาดไอคอนแอป (ค่าเริ่มต้น 84) */
  iconSize?: number;
  /** ของเพิ่มใต้คำอธิบาย */
  children?: React.ReactNode;
}

/** แสงเรืองทองรอบไอคอน (Android เก่ากว่า API 28 วาด boxShadow ไม่ได้ → ข้าม) */
const haloStyle = (color: string): ViewStyle => {
  if (Platform.OS === 'android' && typeof Platform.Version === 'number' && Platform.Version < 28) return {};
  return { boxShadow: `0px 0px 30px 2px ${withAlpha(color, 0.42)}, 0px 14px 24px -12px ${withAlpha(color, 0.7)}` };
};

export const AuthHero: React.FC<AuthHeroProps> = ({ title, subtitle, onBack, right, iconSize = 84, children }) => {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  const radius = Math.round(iconSize * 0.27);

  return (
    <RoyalHeader
      ornamentTop={insets.top - 18}
      ornamentWidth={230}
      style={{ paddingTop: insets.top + spacing.xs, paddingBottom: AUTH_OVERLAP + spacing.xl }}
    >
      {/* แถวบน: ย้อนกลับ + ปุ่มขวา */}
      <View style={styles.topRow}>
        {onBack ? (
          <GlassIconButton icon="caret-left" weight="bold" accessibilityLabel="ย้อนกลับ" onPress={onBack} />
        ) : (
          <View style={styles.topSpacer} />
        )}
        {right ? (
          <OnHeaderProvider value>
            <View style={styles.right}>{right}</View>
          </OnHeaderProvider>
        ) : null}
      </View>

      <Animated.View entering={FadeInDown.duration(420)} style={styles.brand}>
        {/* ไอคอนแอป มุมมน + ขอบทองบาง + แสงเรืองทอง */}
        <View
          style={[
            styles.iconWrap,
            {
              width: iconSize,
              height: iconSize,
              borderRadius: radius,
              borderColor: withAlpha(colors.gold, 0.7),
              backgroundColor: colors.navyDeep,
            },
            haloStyle(colors.gold),
          ]}
        >
          <Image
            source={APP_ICON}
            accessible={false}
            style={{ width: iconSize - 2, height: iconSize - 2, borderRadius: radius - 1 }}
            resizeMode="cover"
          />
        </View>

        <Text style={[typography.overline, styles.appName, { color: colors.goldLight }]}>{APP_INFO.NAME}</Text>
        <Text accessibilityRole="header" style={[typography.serifLg, styles.center, { color: colors.onHeader }]}>
          {title}
        </Text>
        {!!subtitle && (
          <Text style={[typography.body, styles.center, styles.subtitle, { color: colors.onHeaderMuted }]}>{subtitle}</Text>
        )}
        {children}
      </Animated.View>
    </RoyalHeader>
  );
};

const styles = StyleSheet.create({
  topRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.sm,
    minHeight: 48,
  },
  topSpacer: {
    width: 40,
    height: 40,
  },
  right: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  brand: {
    alignItems: 'center',
    paddingHorizontal: spacing.xl,
    marginTop: spacing.xs,
  },
  iconWrap: {
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.lg,
  },
  appName: {
    letterSpacing: 1.6,
    marginBottom: 2,
  },
  center: {
    textAlign: 'center',
  },
  subtitle: {
    marginTop: spacing.xs,
    maxWidth: 340,
  },
});

export default AuthHero;
