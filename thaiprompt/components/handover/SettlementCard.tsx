/**
 * SettlementCard — "เงินที่พักไว้ ถูกแบ่งแล้ว" แสดงว่าเงินที่ผู้ซื้อจ่ายไปถึงใครเท่าไร (ตามแบบ Complete.png)
 *
 * - ตัวเลขทุกบรรทัดมาจาก server (Settlement.lines) — แอปไม่คำนวณเอง
 * - ไม่มี lines (server เก่า) → ใช้ยอดรวมแต่ละฝ่าย seller/rider/referrer/platform แทน
 * - ไอคอนตามชนิดบรรทัด: ร้าน · ไรเดอร์ · ผู้แนะนำ · แพลตฟอร์ม
 */

import React from 'react';
import { StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Card3D, Pill, PriceText, type IconName } from '@/components/ui';
import { IconTile, type IconTileTone } from '@/components/shop';
import type { Settlement, SettlementLine } from '@/services/api/handoverApi';
import { useTheme, spacing, typography } from '@/theme';

export interface SettlementCardProps {
  settlement: Settlement;
  title?: string;
  style?: object;
}

/** ไอคอน/โทนของบรรทัดตาม key (เดาจากคำใน key — server อาจตั้งชื่อละเอียดกว่านี้) */
const lineLook = (key: string): { icon: IconName; tone: IconTileTone } => {
  const k = key.toLowerCase();
  if (k.includes('rider')) return { icon: 'moped', tone: 'success' };
  if (k.includes('refer') || k.includes('mlm') || k.includes('upline')) return { icon: 'users-three', tone: 'navy' };
  if (k.includes('platform') || k.includes('gp') || k.includes('fee')) return { icon: 'tag', tone: 'navy' };
  if (k.includes('cashback') || k.includes('promo') || k.includes('coupon')) return { icon: 'gift', tone: 'gold' };
  if (k.includes('refund') || k.includes('buyer')) return { icon: 'arrow-counter-clockwise', tone: 'info' };
  return { icon: 'storefront', tone: 'gold' };
};

const fallbackLines = (s: Settlement): SettlementLine[] =>
  [
    { key: 'seller', label: 'ร้านค้า', amount: s.seller_amount, note: null },
    { key: 'rider', label: 'ไรเดอร์', amount: s.rider_amount, note: null },
    { key: 'referrer', label: 'ผู้แนะนำของคุณ', amount: s.referrer_amount, note: null },
    { key: 'platform', label: 'แพลตฟอร์ม Thai Prompt', amount: s.platform_amount, note: null },
  ].filter((l) => l.amount > 0);

export const SettlementCard: React.FC<SettlementCardProps> = ({ settlement, title = 'เงินที่พักไว้ ถูกแบ่งแล้ว', style }) => {
  const { colors } = useTheme();
  const lines = settlement.lines.length > 0 ? settlement.lines : fallbackLines(settlement);

  return (
    <Card3D padding={0} radius={22} style={style}>
      <View style={[styles.head, { borderBottomColor: colors.divider }]}>
        <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>{title}</Text>
        <Pill label="ตรวจย้อนได้ทุกบาท" tone="neutral" icon="shield-check" />
      </View>
      {lines.map((line, i) => {
        const look = lineLook(line.key);
        return (
          <View
            key={`${line.key}-${i}`}
            style={[styles.row, i > 0 && { borderTopWidth: 1, borderTopColor: colors.divider }]}
            accessible
            accessibilityLabel={`${line.label} ${line.amount.toFixed(2)} บาท${line.note ? ` ${line.note}` : ''}`}
          >
            <IconTile icon={look.icon} tone={look.tone} size={44} />
            <View style={styles.flex}>
              <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
                {line.label}
              </Text>
              {!!line.note && (
                <Text numberOfLines={2} style={[typography.caption, { color: colors.textMuted }]}>
                  {line.note}
                </Text>
              )}
            </View>
            <PriceText amount={line.amount} decimals={2} size="md" tone="strong" />
          </View>
        );
      })}
      <View style={[styles.total, { borderTopColor: colors.divider }]}>
        <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>คุณจ่าย</Text>
        <PriceText amount={settlement.total_paid} decimals={2} size="lg" tone="strong" />
      </View>
    </Card3D>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  head: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.lg,
    paddingBottom: spacing.md,
    borderBottomWidth: 1,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: 14,
  },
  total: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.lg,
    borderTopWidth: 1,
  },
});

export default SettlementCard;
