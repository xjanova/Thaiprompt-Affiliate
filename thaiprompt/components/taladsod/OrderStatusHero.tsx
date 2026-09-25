/**
 * OrderStatusHero — การ์ดสถานะหลักของหน้าติดตามออเดอร์ตลาดสด (ผู้ซื้อ)
 *
 * - พื้นน้ำเงินกรมท่าแบบหัวหน้าจอ (RoyalHeader) · โหมดมืด = แผ่นกระจกมิดไนท์ + ขอบบาง
 * - แถบความคืบหน้าทอง 4 จุด + ภาพ 3D วิ่งตามสถานะ (ไรเดอร์ส่ง = สกู๊ตเตอร์ · นัดรับ = ถุงช้อปปิ้ง)
 *   เป็นการแสดงผลจาก order_status อย่างเดียว ไม่แตะ logic ของออเดอร์
 * - ยกเลิก/ส่งไม่สำเร็จ = ไม่แสดงแถบ (ไทม์ไลน์ด้านล่างบอกรายละเอียด)
 * - children = ส่วนท้ายการ์ด (ข้อความรอร้าน ปุ่มโทรหาร้าน) — วางบนพื้นน้ำเงิน ปุ่มจะเป็นแบบกระจกเอง
 */

import React, { useEffect } from 'react';
import { StyleSheet, View } from 'react-native';
import Animated, {
  Easing,
  cancelAnimation,
  useAnimatedStyle,
  useSharedValue,
  withTiming,
} from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';
import { Text } from '@/components/ui/Text';
import { BrandArt, Icon, OnHeaderProvider, RoyalHeader, type IconName } from '@/components/ui';
import { formatThaiDateTime } from '@/components/shop';
import { useTheme, radii, shadowStyle, spacing, typography } from '@/theme';
import { FM_ORDER_TERMINAL_STATUSES, type FmOrder } from '@/services/api/taladsodApi';
import { fmOrderLabel } from './helpers';
import { LiveDot } from './BuyerParts';

const MARKER = 46;

/** จุดบนแถบ (ตำแหน่งกึ่งกลางของป้ายแต่ละช่อง 4 ช่องเท่ากัน) */
const MILESTONE_AT = [0.125, 0.375, 0.625, 0.875];

const RIDER_MILESTONES = ['รับออเดอร์', 'ร้านกำลังทำ', 'กำลังส่ง', 'ถึงแล้ว'];
const PICKUP_MILESTONES = ['รับออเดอร์', 'ร้านกำลังทำ', 'พร้อมให้รับ', 'รับของแล้ว'];

/** สถานะ → ตำแหน่งบนแถบ (0-1) */
const RIDER_PROGRESS: Record<string, number> = {
  pending: 0.05,
  accepted: 0.125,
  preparing: 0.375,
  ready: 0.5,
  delivering: 0.625,
  delivered: 0.875,
  completed: 1,
};
const PICKUP_PROGRESS: Record<string, number> = {
  pending: 0.05,
  accepted: 0.125,
  preparing: 0.375,
  ready: 0.625,
  delivered: 0.875,
  completed: 1,
};

export interface OrderStatusHeroProps {
  order: FmOrder;
  children?: React.ReactNode;
}

export const OrderStatusHero: React.FC<OrderStatusHeroProps> = ({ order, children }) => {
  const { colors, gradients, isDark } = useTheme();
  const isRider = order.delivery_type === 'rider';
  const status = String(order.order_status);
  const failed = status === 'cancelled' || status === 'delivery_failed';
  const terminal = FM_ORDER_TERMINAL_STATUSES.includes(status);
  const progress = (isRider ? RIDER_PROGRESS : PICKUP_PROGRESS)[status] ?? 0.05;
  const milestones = isRider ? RIDER_MILESTONES : PICKUP_MILESTONES;

  // ---------- แอนิเมชันแถบ ----------
  const trackW = useSharedValue(0);
  const fill = useSharedValue(0);

  useEffect(() => {
    fill.value = withTiming(progress, { duration: 900, easing: Easing.out(Easing.cubic) });
    return () => cancelAnimation(fill);
  }, [fill, progress]);

  const fillStyle = useAnimatedStyle(() => ({ width: trackW.value * fill.value }));
  const markerStyle = useAnimatedStyle(() => ({
    transform: [
      { translateX: Math.max(-6, Math.min(trackW.value - MARKER + 6, trackW.value * fill.value - MARKER / 2)) },
    ],
  }));

  const payment = order.payment_method_label || (order.payment_method === 'cod' ? 'เงินสด' : 'กระเป๋าเงิน');

  const leading: React.ReactNode = failed ? (
    <Icon name="x-circle" size={16} color={colors.danger} weight="fill" />
  ) : terminal ? (
    <Icon name="seal-check" size={16} color={colors.goldLight} weight="fill" />
  ) : (
    <LiveDot color={colors.goldLight} size={8} />
  );

  const meta: { icon: IconName; text: string }[] = [
    { icon: 'storefront', text: order.seller?.shop_name || 'ร้านตลาดสด' },
    { icon: isRider ? 'moped' : 'shopping-bag-open', text: isRider ? 'ไรเดอร์ส่ง' : 'นัดรับที่ร้าน' },
    { icon: order.payment_method === 'cod' ? 'money' : 'wallet', text: payment },
  ];

  return (
    <View style={[styles.shadowBox, shadowStyle('lg', colors.shadowDark)]}>
      <RoyalHeader
        ornament={false}
        style={[styles.hero, isDark && { borderWidth: 1, borderColor: colors.border, borderTopColor: colors.shadowLight }]}
      >
        <OnHeaderProvider value>
          {/* ---------- สถานะ ---------- */}
          <View style={styles.overlineRow}>
            {leading}
            <Text style={[typography.overline, { color: colors.onHeaderMuted }]}>สถานะออเดอร์</Text>
          </View>
          <Text accessibilityRole="header" style={[typography.serif, styles.title, { color: colors.onHeader }]}>
            {fmOrderLabel(order)}
          </Text>

          <View style={styles.metaRow}>
            {meta.map((m, i) => (
              <View key={m.icon} style={styles.metaItem}>
                {i > 0 && <View style={[styles.metaDot, { backgroundColor: colors.onHeaderMuted }]} />}
                <Icon name={m.icon} size={14} color={colors.goldLight} />
                <Text numberOfLines={1} style={[typography.bodySm, styles.metaText, { color: colors.onHeaderMuted }]}>
                  {m.text}
                </Text>
              </View>
            ))}
          </View>

          {/* ---------- แถบความคืบหน้า ---------- */}
          {!failed && (
            <View style={styles.progressBox} accessibilityElementsHidden importantForAccessibility="no-hide-descendants">
              <View
                style={styles.trackArea}
                onLayout={(e) => {
                  trackW.value = e.nativeEvent.layout.width;
                }}
              >
                <View style={[styles.track, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}>
                  <Animated.View style={[styles.fill, fillStyle]}>
                    <LinearGradient colors={gradients.gold} start={{ x: 0, y: 0 }} end={{ x: 1, y: 0 }} style={StyleSheet.absoluteFill} />
                  </Animated.View>
                </View>
                {MILESTONE_AT.map((at) => (
                  <View
                    key={at}
                    style={[
                      styles.tick,
                      {
                        left: `${at * 100}%` as const,
                        backgroundColor: progress >= at ? colors.goldLight : colors.headerGlassBorder,
                        borderColor: progress >= at ? colors.gold : 'transparent',
                      },
                    ]}
                  />
                ))}
                <Animated.View style={[styles.marker, markerStyle]}>
                  <View style={[styles.markerGlow, { backgroundColor: colors.gold }]} />
                  <BrandArt
                    name={isRider ? 'scooter' : 'bag'}
                    size={MARKER}
                    // ภาพสกู๊ตเตอร์หันซ้าย → กลับด้านให้วิ่งไปทางขวาตามแถบ
                    style={isRider ? styles.flip : undefined}
                  />
                </Animated.View>
              </View>

              <View style={styles.labels}>
                {milestones.map((label, i) => {
                  // ถึงแล้ว = ทองสว่าง ตัวหนา · ยังไม่ถึง = จาง
                  const reached = progress >= MILESTONE_AT[i];
                  return (
                    <Text
                      key={label}
                      numberOfLines={1}
                      style={[
                        typography.micro,
                        styles.label,
                        {
                          color: reached ? colors.goldLight : colors.onHeaderMuted,
                          fontWeight: reached ? '700' : '500',
                        },
                      ]}
                    >
                      {label}
                    </Text>
                  );
                })}
              </View>
            </View>
          )}

          {/* ---------- เวลาสั่ง / สถานะจ่ายเงิน ---------- */}
          <View style={[styles.footer, { borderTopColor: colors.headerGlassBorder }]}>
            <View style={styles.placedRow}>
              <Icon name="clock" size={14} color={colors.onHeaderMuted} />
              <Text style={[typography.caption, styles.flex, { color: colors.onHeaderMuted }]}>
                สั่งเมื่อ {formatThaiDateTime(order.created_at)} · {order.payment_status_label}
              </Text>
            </View>
            {children}
          </View>
        </OnHeaderProvider>
      </RoyalHeader>
    </View>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  shadowBox: {
    borderRadius: radii.xxl,
  },
  hero: {
    borderRadius: radii.xxl,
    paddingHorizontal: spacing.lg + 2,
    paddingTop: spacing.lg + 2,
    paddingBottom: spacing.lg,
  },
  overlineRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  title: {
    marginTop: 2,
  },
  metaRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
    rowGap: 2,
    marginTop: 2,
  },
  metaItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    maxWidth: '100%',
  },
  metaDot: {
    width: 3,
    height: 3,
    borderRadius: 2,
    marginHorizontal: spacing.sm,
    opacity: 0.7,
  },
  metaText: {
    flexShrink: 1,
  },
  progressBox: {
    marginTop: spacing.lg,
  },
  trackArea: {
    height: MARKER + 4,
    justifyContent: 'flex-end',
  },
  track: {
    height: 8,
    borderRadius: 4,
    borderWidth: 1,
    overflow: 'hidden',
  },
  fill: {
    height: '100%',
    borderRadius: 4,
    overflow: 'hidden',
  },
  tick: {
    position: 'absolute',
    bottom: 0,
    width: 8,
    height: 8,
    marginLeft: -4,
    borderRadius: 4,
    borderWidth: 1,
  },
  marker: {
    position: 'absolute',
    left: 0,
    bottom: 2,
    width: MARKER,
    height: MARKER,
    alignItems: 'center',
    justifyContent: 'flex-end',
  },
  markerGlow: {
    position: 'absolute',
    bottom: 0,
    width: MARKER * 0.7,
    height: 10,
    borderRadius: 5,
    opacity: 0.35,
  },
  flip: {
    transform: [{ scaleX: -1 }],
  },
  labels: {
    flexDirection: 'row',
    marginTop: spacing.sm,
  },
  label: {
    flex: 1,
    // กึ่งกลางช่อง = ตรงกับจุดบนแถบ (12.5% / 37.5% / 62.5% / 87.5%)
    textAlign: 'center',
  },
  footer: {
    marginTop: spacing.lg,
    paddingTop: spacing.md,
    borderTopWidth: 1,
    gap: spacing.sm,
  },
  placedRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
});

export default OrderStatusHero;
