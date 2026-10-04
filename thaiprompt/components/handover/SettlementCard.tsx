/**
 * SettlementCard — "เงินที่พักไว้ ถูกแบ่งแล้ว" แสดงว่าเงินที่ผู้ซื้อจ่ายไปถึงใครเท่าไร (ตามแบบ Complete.png)
 *
 * - ตัวเลขทุกบรรทัดมาจาก server (Settlement.lines) — แอปไม่คำนวณเอง
 * - แสดงเฉพาะผู้รับเงิน: ร้าน · ไรเดอร์ · ผู้แนะนำ · แพลตฟอร์ม (U6)
 *   บรรทัดฝั่งผู้จ่าย (ค่าสินค้า / ค่าส่งที่คุณจ่าย) ไม่แสดงเป็นผู้รับ — ยอดที่จ่ายรวมอยู่ที่บรรทัด "คุณจ่าย"
 * - เงินคืน (cashback) แยกแสดงใต้ยอดรวม
 * - ไม่มี lines (server เก่า) → ใช้ยอดรวมแต่ละฝ่าย seller/rider/referrer/platform แทน (settlementLines.ts)
 */

import React from 'react';
import { StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Card3D, Icon, Pill, PriceText, type IconName } from '@/components/ui';
import { IconTile, type IconTileTone } from '@/components/shop';
import type { Settlement } from '@/services/api/handoverApi';
import { useTheme, spacing, typography } from '@/theme';
import { settlementCashback, settlementPayeeLines, type PayeeKind } from './settlementLines';

export interface SettlementCardProps {
  settlement: Settlement;
  title?: string;
  style?: object;
}

/** ไอคอน/โทนตามกลุ่มผู้รับ */
const PAYEE_LOOK: Record<PayeeKind, { icon: IconName; tone: IconTileTone }> = {
  seller: { icon: 'storefront', tone: 'gold' },
  rider: { icon: 'moped', tone: 'success' },
  referrer: { icon: 'users-three', tone: 'navy' },
  platform: { icon: 'buildings', tone: 'navy' },
};

export const SettlementCard: React.FC<SettlementCardProps> = ({ settlement, title = 'เงินที่พักไว้ ถูกแบ่งแล้ว', style }) => {
  const { colors } = useTheme();
  const lines = settlementPayeeLines(settlement);
  const cashback = settlementCashback(settlement);

  return (
    <Card3D padding={0} radius={22} style={style}>
      <View style={[styles.head, { borderBottomColor: colors.divider }]}>
        <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>{title}</Text>
        <Pill label="ตรวจย้อนได้ทุกบาท" tone="neutral" icon="shield-check" />
      </View>
      {lines.map((line, i) => {
        const look = PAYEE_LOOK[line.kind];
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
      {cashback > 0 && (
        <View style={[styles.cashback, { backgroundColor: colors.successSoft }]}>
          <Icon name="gift" size={16} color={colors.success} />
          <Text style={[typography.caption, styles.flex, { color: colors.text }]}>เงินคืนเข้ากระเป๋าของคุณ</Text>
          <PriceText amount={cashback} decimals={2} size="sm" tone="success" />
        </View>
      )}
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
  cashback: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginHorizontal: spacing.lg,
    marginBottom: spacing.lg,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    borderRadius: 14,
  },
});

export default SettlementCard;
