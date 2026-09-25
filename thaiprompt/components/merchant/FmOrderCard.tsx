/**
 * FmOrderCard — การ์ดออเดอร์ตลาดสดฝั่งร้าน (รายการ + ตัวเลือก + หมายเหตุ + ปุ่มตามสถานะ)
 *
 * - แถบสีด้านซ้ายบอกสถานะ (ใหม่ = ส้ม, กำลังทำ = ทอง/ฟ้า, พร้อม = เขียว, ยกเลิก = แดง)
 * - ปุ่มแสดงตาม allowed_actions ที่ server ส่งมาเท่านั้น
 * - onAction คืน Promise → ปุ่มหมุนโหลดและกันกดซ้ำให้เอง
 */

import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { Button3D, Card3D, Pill, PriceText, formatBaht } from '@/components/ui';
import { callPhone, openHttpsLink } from '@/components/shop';
import { fmImageUrl, type FmSellerAction, type FmSellerOrder } from '@/services/api/taladsodSellerApi';
import { useTheme, radii, spacing, toneColors, typography } from '@/theme';
import {
  FM_STATUS_ICON,
  FM_STATUS_LABEL,
  FM_STATUS_TONE,
  fmActionLook,
  sortFmActions,
  timeAgoTh,
} from './fmHelpers';

export interface FmOrderCardProps {
  order: FmSellerOrder;
  onAction: (order: FmSellerOrder, action: FmSellerAction) => unknown;
  /** ไฮไลต์ (มาจากแจ้งเตือน) */
  highlighted?: boolean;
  /** เวลาปัจจุบัน (ให้หน้าจอส่งมาเพื่อไม่ให้ทุกการ์ดตั้งนาฬิกาเอง) */
  now?: number;
}

const RIDER_STATUS_TEXT: Record<string, string> = {
  pending: 'กำลังหาไรเดอร์',
  accepted: 'ไรเดอร์รับงานแล้ว',
  picking_up: 'ไรเดอร์กำลังมารับของ',
  picked_up: 'ไรเดอร์รับของแล้ว',
  delivering: 'ไรเดอร์กำลังไปส่ง',
  delivered: 'ไรเดอร์ส่งถึงแล้ว',
  completed: 'ส่งสำเร็จ',
  failed: 'ส่งไม่สำเร็จ',
  cancelled: 'งานไรเดอร์ถูกยกเลิก',
};

export const FmOrderCard: React.FC<FmOrderCardProps> = ({ order, onAction, highlighted = false, now }) => {
  const { colors } = useTheme();
  const status = order.order_status;
  const tone = FM_STATUS_TONE[status] || 'neutral';
  const t = toneColors(tone, colors);
  const actions = sortFmActions(order.allowed_actions);
  const primary = actions.filter((a) => a !== 'cancel');
  const canCancel = actions.includes('cancel');
  const isCod = order.payment_method === 'cod';

  return (
    <Card3D
      padding={0}
      radius={radii.lg}
      shadow="sm"
      gradientBorder={highlighted}
      style={styles.card}
      accessibilityLabel={`ออเดอร์ ${order.order_number} ${order.status_label || FM_STATUS_LABEL[status]}`}
    >
      <View style={styles.inner}>
        <View style={[styles.stripe, { backgroundColor: t.fg }]} />
        <View style={styles.body}>
          {/* ---------- หัว ---------- */}
          <View style={styles.headRow}>
            <View style={styles.flex}>
              <Text style={[typography.bodyStrong, { color: colors.textStrong }]} numberOfLines={1}>
                {order.order_number}
              </Text>
              <Text style={[typography.micro, { color: colors.textFaint }]}>{timeAgoTh(order.created_at, now)}</Text>
            </View>
            <Pill
              label={order.status_label || FM_STATUS_LABEL[status] || status}
              icon={FM_STATUS_ICON[status]}
              tone={tone}
              size="md"
            />
          </View>

          <View style={styles.pills}>
            <Pill
              label={order.delivery_type === 'rider' ? 'ส่งด้วยไรเดอร์' : 'ลูกค้ามารับเอง'}
              icon={order.delivery_type === 'rider' ? '🛵' : '🙋'}
              tone={order.delivery_type === 'rider' ? 'info' : 'neutral'}
            />
            <Pill
              label={isCod ? 'เก็บเงินปลายทาง' : order.payment_status_label || 'จ่ายแล้ว'}
              icon={isCod ? '💵' : '👛'}
              tone={isCod ? 'warning' : 'success'}
            />
          </View>

          {/* ---------- รายการ ---------- */}
          <View style={[styles.items, { borderColor: colors.divider }]}>
            {order.items.length === 0 ? (
              <Text style={[typography.body, { color: colors.text }]}>{order.items_summary || 'รายการสินค้า'}</Text>
            ) : (
              order.items.map((item) => {
                const img = fmImageUrl(item.image_url);
                return (
                  <View key={item.id || `${item.listing_id}-${item.title}`} style={styles.itemRow}>
                    {img ? (
                      <Image source={{ uri: img }} style={[styles.thumb, { backgroundColor: colors.inset }]} contentFit="cover" />
                    ) : (
                      <View style={[styles.thumb, styles.center, { backgroundColor: colors.inset }]}>
                        <Text>🥬</Text>
                      </View>
                    )}
                    <View style={styles.flex}>
                      <Text style={[typography.bodyStrong, { color: colors.textStrong }]} numberOfLines={2}>
                        <Text style={{ color: colors.goldDeep }}>{item.quantity}×</Text> {item.title}
                      </Text>
                      {!!item.options_label && (
                        <Text style={[typography.bodySm, { color: colors.text }]}>➕ {item.options_label}</Text>
                      )}
                      {!!item.note && (
                        <Text style={[typography.bodySm, { color: colors.warning }]}>📝 {item.note}</Text>
                      )}
                    </View>
                    <PriceText amount={item.line_total} size="sm" tone="default" />
                  </View>
                );
              })
            )}
          </View>

          {/* ---------- ลูกค้า / การจัดส่ง ---------- */}
          {!!order.buyer && (
            <View style={styles.metaRow}>
              <Text style={[typography.bodySm, styles.flex, { color: colors.text }]} numberOfLines={1}>
                👤 {order.buyer.name}
              </Text>
              {!!order.buyer.phone && (
                <Button3D title="โทร" icon="📞" size="sm" variant="ghost" onPress={() => callPhone(order.buyer?.phone)} />
              )}
            </View>
          )}
          {order.delivery_type === 'rider' && !!order.delivery_address && (
            <Text style={[typography.caption, styles.meta, { color: colors.textMuted }]} numberOfLines={2}>
              🏠 {order.delivery_address}
            </Text>
          )}
          {!!order.delivery_notes && (
            <Text style={[typography.caption, styles.meta, { color: colors.warning }]} numberOfLines={3}>
              💬 {order.delivery_notes}
            </Text>
          )}
          {!!order.rider_job && (
            <View style={styles.metaRow}>
              <Text style={[typography.caption, styles.flex, { color: colors.info }]} numberOfLines={1}>
                🛵 {RIDER_STATUS_TEXT[order.rider_job.status] || order.rider_job.status}
                {order.rider_job.rider_name ? ` · ${order.rider_job.rider_name}` : ''}
              </Text>
              {!!order.rider_job.rider_phone && (
                <Button3D title="โทรหาไรเดอร์" size="sm" variant="ghost" onPress={() => callPhone(order.rider_job?.rider_phone)} />
              )}
              {!order.rider_job.rider_phone && !!order.rider_job.tracking_url && (
                <Button3D
                  title="ติดตาม"
                  size="sm"
                  variant="ghost"
                  onPress={() => openHttpsLink(order.rider_job?.tracking_url, 'ติดตามไรเดอร์')}
                />
              )}
            </View>
          )}
          {status === 'cancelled' && !!order.cancel_reason && (
            <Text style={[typography.caption, styles.meta, { color: colors.danger }]}>เหตุผลที่ยกเลิก: {order.cancel_reason}</Text>
          )}

          {/* ---------- ยอด ---------- */}
          <View style={[styles.totalRow, { borderTopColor: colors.divider }]}>
            <View style={styles.flex}>
              <Text style={[typography.caption, { color: colors.textMuted }]}>
                {isCod ? 'เก็บจากลูกค้า' : 'ยอดออเดอร์'}
                {order.delivery_fee > 0 ? ` (รวมค่าส่ง ${formatBaht(order.delivery_fee)})` : ''}
              </Text>
              <PriceText amount={order.grand_total || order.total_amount} size="lg" tone="strong" />
            </View>
            <View style={styles.right}>
              <Text style={[typography.caption, { color: colors.textMuted }]}>ร้านได้รับ</Text>
              <PriceText amount={order.seller_earning} size="md" tone="success" />
            </View>
          </View>

          {/* ---------- ปุ่ม ---------- */}
          {primary.length > 0 && (
            <View style={styles.actions}>
              {primary.map((action) => {
                const look = fmActionLook(action, order.delivery_type);
                return (
                  <Button3D
                    key={action}
                    title={look.label}
                    icon={look.icon}
                    variant={look.variant}
                    size="md"
                    fullWidth
                    onPress={() => onAction(order, action)}
                  />
                );
              })}
            </View>
          )}
          {canCancel && (
            <Button3D
              title="ยกเลิกออเดอร์"
              variant="ghost"
              size="sm"
              onPress={() => onAction(order, 'cancel')}
              style={styles.cancel}
              textStyle={{ color: colors.danger }}
            />
          )}
        </View>
      </View>
    </Card3D>
  );
};

const styles = StyleSheet.create({
  card: {
    marginBottom: spacing.md,
  },
  inner: {
    flexDirection: 'row',
    overflow: 'hidden',
    borderRadius: radii.lg,
  },
  stripe: {
    width: 6,
  },
  body: {
    flex: 1,
    padding: spacing.md,
  },
  flex: {
    flex: 1,
  },
  center: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  headRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.sm,
  },
  items: {
    marginTop: spacing.md,
    paddingTop: spacing.sm,
    borderTopWidth: StyleSheet.hairlineWidth,
    gap: spacing.sm,
  },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
  },
  thumb: {
    width: 44,
    height: 44,
    borderRadius: radii.sm,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  meta: {
    marginTop: spacing.xs,
  },
  totalRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    marginTop: spacing.md,
    paddingTop: spacing.sm,
    borderTopWidth: StyleSheet.hairlineWidth,
  },
  right: {
    alignItems: 'flex-end',
  },
  actions: {
    marginTop: spacing.md,
    gap: spacing.sm,
  },
  cancel: {
    alignSelf: 'center',
    marginTop: spacing.sm,
  },
});

export default FmOrderCard;
