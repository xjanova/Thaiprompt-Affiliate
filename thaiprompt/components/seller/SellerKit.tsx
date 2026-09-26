/**
 * ชิ้นส่วนหน้าตาของหลังร้านในแอป (สมัครเปิดร้าน / สินค้า / ตั้งค่าร้าน)
 *
 * - Skeleton / ProductListSkeleton / FormSkeleton  โครงกระพริบตอนโหลดครั้งแรก (ไม่ใช้สปินเนอร์เต็มจอ)
 * - SellerGateNotice  การ์ดอธิบายว่าทำไมยังจัดการร้านไม่ได้ + ปุ่มไปทำขั้นถัดไป (สมัคร/KYC/แพ็กเกจ/ติดต่อทีมงาน)
 * - ToggleRow         แถวสวิตช์ (กล่องไอคอน + หัวข้อ + คำอธิบาย)
 * - FormCard          การ์ดฟอร์มที่มีหัวการ์ด (กล่องไอคอน + ชื่อส่วน)
 * - ErrorNote         กล่องข้อความผิดพลาดใต้ฟอร์ม
 *
 * สีทั้งหมดมาจาก useTheme() — รองรับโหมดสว่าง/มืด
 */

import React, { useEffect, useRef } from 'react';
import { Animated, Easing, StyleSheet, Switch, View, type DimensionValue, type StyleProp, type ViewStyle } from 'react-native';
import { router } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { BrandArt, Button3D, Card3D, Icon, WebsiteButton, type IconName } from '@/components/ui';
import { IconTile, type IconTileTone } from '@/components/merchant';
import type { ApiFailure } from '@/services/api/client';
import { isSellerGateCode } from '@/services/api/sellerProductApi';
import { useTheme, radii, spacing, typography } from '@/theme';

// =====================================================
// Skeleton
// =====================================================

/** กล่องกระพริบหนึ่งชิ้น */
export const Skeleton: React.FC<{
  width?: DimensionValue;
  height?: number;
  radius?: number;
  style?: StyleProp<ViewStyle>;
}> = ({ width = '100%', height = 14, radius = radii.sm, style }) => {
  const { colors } = useTheme();
  const pulse = useRef(new Animated.Value(0.45)).current;

  useEffect(() => {
    const loop = Animated.loop(
      Animated.sequence([
        Animated.timing(pulse, { toValue: 1, duration: 650, easing: Easing.inOut(Easing.quad), useNativeDriver: true }),
        Animated.timing(pulse, { toValue: 0.45, duration: 650, easing: Easing.inOut(Easing.quad), useNativeDriver: true }),
      ])
    );
    loop.start();
    return () => loop.stop();
  }, [pulse]);

  return (
    <Animated.View
      accessibilityElementsHidden
      importantForAccessibility="no-hide-descendants"
      style={[{ width, height, borderRadius: radius, backgroundColor: colors.inset, opacity: pulse }, style]}
    />
  );
};

/** โครงรายการสินค้า (การ์ดแถวละชิ้น) */
export const ProductListSkeleton: React.FC<{ count?: number }> = ({ count = 5 }) => (
  <View accessibilityLabel="กำลังโหลดรายการสินค้า" accessible>
    {Array.from({ length: count }).map((_, i) => (
      <Card3D key={i} padding={spacing.md} radius={radii.xl} shadow="sm" style={styles.skeletonCard}>
        <View style={styles.skeletonRow}>
          <Skeleton width={72} height={72} radius={18} />
          <View style={styles.flex}>
            <Skeleton width="80%" height={16} />
            <Skeleton width="45%" height={12} style={styles.gapSm} />
            <Skeleton width="30%" height={18} style={styles.gapSm} />
          </View>
        </View>
      </Card3D>
    ))}
  </View>
);

/** โครงฟอร์ม (การ์ดหัวข้อ + ช่องกรอก) */
export const FormSkeleton: React.FC<{ sections?: number }> = ({ sections = 3 }) => (
  <View accessibilityLabel="กำลังโหลดข้อมูล" accessible>
    {Array.from({ length: sections }).map((_, i) => (
      <Card3D key={i} padding={spacing.lg} radius={20} style={styles.skeletonSection}>
        <View style={styles.skeletonRow}>
          <Skeleton width={38} height={38} radius={13} />
          <Skeleton width="40%" height={18} />
        </View>
        <Skeleton height={48} radius={14} style={styles.gapMd} />
        <Skeleton height={48} radius={14} style={styles.gapMd} />
      </Card3D>
    ))}
  </View>
);

// =====================================================
// SellerGateNotice
// =====================================================

export interface SellerGateNoticeProps {
  /** ผลล้มเหลวจาก API หลังร้าน (403 ด่านผู้ขาย) */
  failure: Pick<ApiFailure, 'code' | 'message' | 'data'>;
  style?: StyleProp<ViewStyle>;
}

/** true = error นี้เป็นด่านผู้ขาย (แสดงด้วย SellerGateNotice) */
export const isGateFailure = (failure: { code?: string } | null | undefined): boolean => isSellerGateCode(failure?.code);

/**
 * การ์ดอธิบายด่านผู้ขาย พร้อมขั้นถัดไปที่ทำได้ทันที
 * NOT_A_SELLER → สมัครเปิดร้าน · STORE_PENDING → ดูสถานะคำขอ · SELLER_KYC_REQUIRED → ยืนยันตัวตน
 * PACKAGE_REQUIRED → เลือกแพ็กเกจบนเว็บ · STORE_SUSPENDED → ติดต่อทีมงาน
 */
export const SellerGateNotice: React.FC<SellerGateNoticeProps> = ({ failure, style }) => {
  const { colors } = useTheme();
  const webPath =
    typeof failure.data?.web_path === 'string' && failure.data.web_path.startsWith('/') ? (failure.data.web_path as string) : null;
  const applicationState = typeof failure.data?.application_state === 'string' ? failure.data.application_state : null;
  const reason = typeof failure.data?.reason === 'string' && failure.data.reason ? (failure.data.reason as string) : null;

  let title = 'ยังจัดการร้านในแอปไม่ได้';
  let body = failure.message;
  let action: React.ReactNode = null;
  let tone: IconTileTone = 'gold';
  let icon: IconName = 'storefront';

  switch (failure.code) {
    case 'NOT_A_SELLER':
      if (webPath) {
        // เป็นผู้ขายแล้วแต่ยังไม่ได้ตั้งร้าน → ตั้งค่าร้านบนเว็บ
        title = 'ตั้งค่าร้านก่อนเริ่มขาย';
        action = <WebsiteButton path={webPath} label="ตั้งค่าร้านบนเว็บไซต์" icon="storefront" variant="primary" fullWidth />;
      } else {
        title = applicationState === 'role_not_eligible' ? 'บัญชีนี้เปิดร้านเองไม่ได้' : 'ยังไม่มีร้านค้า';
        body =
          applicationState === 'role_not_eligible'
            ? 'บัญชีของคุณมีบทบาทเฉพาะอยู่แล้ว หากต้องการเปิดร้านค้าด้วย กรุณาติดต่อทีมงาน'
            : 'สมัครเปิดร้านในแอปได้เลย ทีมงานตรวจสอบแล้วแจ้งผลทางการแจ้งเตือน';
        action =
          applicationState === 'role_not_eligible' ? (
            <Button3D title="ติดต่อทีมงาน" icon="headset" variant="secondary" fullWidth onPress={() => router.push('/support')} />
          ) : (
            <Button3D
              title={applicationState === 'rejected' ? 'แก้ไขแล้วยื่นคำขอใหม่' : 'สมัครเปิดร้าน'}
              icon="storefront"
              iconRight="arrow-right"
              size="lg"
              fullWidth
              onPress={() => router.push('/merchant/apply' as never)}
            />
          );
      }
      break;
    case 'STORE_PENDING':
      title = 'คำขอเปิดร้านรอตรวจสอบ';
      icon = 'hourglass';
      tone = 'info';
      action = (
        <Button3D title="ดูสถานะคำขอ" icon="clipboard-text" variant="secondary" fullWidth onPress={() => router.push('/merchant/apply' as never)} />
      );
      break;
    case 'SELLER_KYC_REQUIRED':
      title = 'ยืนยันตัวตนก่อนเริ่มขาย';
      icon = 'identification-card';
      tone = 'warning';
      action = (
        <Button3D title="ยืนยันตัวตน (KYC)" icon="identification-card" iconRight="arrow-right" size="lg" fullWidth onPress={() => router.push('/kyc')} />
      );
      break;
    case 'PACKAGE_REQUIRED':
      title = 'เลือกแพ็กเกจร้านก่อน';
      icon = 'crown-simple';
      action = <WebsiteButton path={webPath || '/seller/onboarding'} label="เลือกแพ็กเกจบนเว็บไซต์" icon="crown-simple" variant="primary" fullWidth />;
      break;
    case 'STORE_SUSPENDED':
      title = 'ร้านถูกระงับชั่วคราว';
      icon = 'prohibit';
      tone = 'danger';
      body = reason ? `${failure.message}\nเหตุผล: ${reason}` : failure.message;
      action = <Button3D title="ติดต่อทีมงาน" icon="headset" variant="secondary" fullWidth onPress={() => router.push('/support')} />;
      break;
    default:
      break;
  }

  return (
    <Card3D gradientBorder padding={spacing.xl} contentStyle={styles.gateBox} style={style}>
      {failure.code === 'NOT_A_SELLER' && !webPath ? <BrandArt name="store" size={112} /> : <IconTile icon={icon} tone={tone} size={64} />}
      <Text style={[typography.serif, styles.center, styles.gapMd, { color: colors.textStrong }]}>{title}</Text>
      <Text style={[typography.body, styles.center, styles.gapSm, { color: colors.textMuted }]}>{body}</Text>
      {action ? <View style={styles.gateAction}>{action}</View> : null}
    </Card3D>
  );
};

// =====================================================
// ToggleRow
// =====================================================

export interface ToggleRowProps {
  icon: IconName;
  title: string;
  description?: string;
  value: boolean;
  onValueChange: (next: boolean) => void;
  disabled?: boolean;
  tone?: IconTileTone;
  style?: StyleProp<ViewStyle>;
}

export const ToggleRow: React.FC<ToggleRowProps> = ({ icon, title, description, value, onValueChange, disabled, tone, style }) => {
  const { colors } = useTheme();
  return (
    <View style={[styles.toggleRow, { borderTopColor: colors.divider }, style]}>
      <IconTile icon={icon} tone={tone ?? (value ? 'success' : 'neutral')} size={40} />
      <View style={styles.flex}>
        <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{title}</Text>
        {!!description && <Text style={[typography.caption, { color: colors.textMuted }]}>{description}</Text>}
      </View>
      <Switch
        value={value}
        onValueChange={onValueChange}
        disabled={disabled}
        trackColor={{ false: colors.border, true: colors.success }}
        thumbColor={colors.card}
        accessibilityLabel={title}
      />
    </View>
  );
};

// =====================================================
// FormCard / ErrorNote
// =====================================================

export const FormCard: React.FC<{
  icon: IconName;
  title: string;
  subtitle?: string;
  tone?: IconTileTone;
  children?: React.ReactNode;
  style?: StyleProp<ViewStyle>;
}> = ({ icon, title, subtitle, tone, children, style }) => {
  const { colors } = useTheme();
  return (
    <Card3D padding={spacing.lg} radius={20} style={[styles.formCard, style]}>
      <View style={styles.cardHead}>
        <IconTile icon={icon} tone={tone} size={38} />
        <View style={styles.flex}>
          <Text style={[typography.h3, { color: colors.textStrong }]}>{title}</Text>
          {!!subtitle && <Text style={[typography.caption, { color: colors.textMuted }]}>{subtitle}</Text>}
        </View>
      </View>
      {children}
    </Card3D>
  );
};

export const ErrorNote: React.FC<{ text: string; style?: StyleProp<ViewStyle> }> = ({ text, style }) => {
  const { colors } = useTheme();
  return (
    <View style={[styles.errorBox, { backgroundColor: colors.dangerSoft }, style]}>
      <Icon name="warning-circle" size={20} color={colors.danger} weight="fill" />
      <Text style={[typography.bodySm, styles.flex, { color: colors.danger }]} accessibilityRole="alert">
        {text}
      </Text>
    </View>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  center: {
    textAlign: 'center',
  },
  gapSm: {
    marginTop: spacing.sm,
  },
  gapMd: {
    marginTop: spacing.md,
  },
  skeletonCard: {
    marginBottom: spacing.sm,
  },
  skeletonSection: {
    marginBottom: spacing.lg,
  },
  skeletonRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  gateBox: {
    alignItems: 'center',
  },
  gateAction: {
    alignSelf: 'stretch',
    marginTop: spacing.xl,
  },
  toggleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginTop: spacing.lg,
    paddingTop: spacing.lg,
    borderTopWidth: 1,
  },
  formCard: {
    marginBottom: spacing.lg,
  },
  cardHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginBottom: spacing.xs,
  },
  errorBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    marginTop: spacing.md,
    borderRadius: radii.md,
    padding: spacing.md,
  },
});
