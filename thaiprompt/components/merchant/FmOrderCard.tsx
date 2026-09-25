/**
 * FmOrderCard — การ์ดออเดอร์ตลาดสดฝั่งร้าน (รายการ + ตัวเลือก + หมายเหตุ + ปุ่มตามสถานะ)
 *
 * - แถบสีด้านซ้ายบอกสถานะ (ใหม่ = ส้ม, กำลังทำ = ทอง/ฟ้า, พร้อม = เขียว, ยกเลิก = แดง) + ป้ายสถานะพร้อมไอคอน
 * - ปุ่มแสดงตาม allowed_actions ที่ server ส่งมาเท่านั้น — ขั้นถัดไปเป็นปุ่มทองใหญ่ ยกเลิกเป็นปุ่มรองเล็ก
 * - onAction คืน Promise → ปุ่มหมุนโหลดและกันกดซ้ำให้เอง
 */

import React from 'react';
import { StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Image } from 'expo-image';
import { Button3D, Card3D, Icon, Pill, PriceText, formatBaht, type IconName } from '@/components/ui';
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

/** แถวข้อมูลเล็ก: ไอคอนเส้น + ข้อความ (+ ปุ่มด้านขวา) · lines = 0 → ไม่จำกัดบรรทัด */
const MetaRow: React.FC<{ icon: IconName; color: string; children: React.ReactNode; right?: React.ReactNode; lines?: number }> = ({
  icon,
  color,
  children,
  right,
  lines = 1,
}) => (
  <View style={styles.metaRow}>
    <Icon name={icon} size={16} color={color} style={styles.metaIcon} />
    <Text style={[typography.bodySm, styles.flex, { color }]} numberOfLines={lines > 0 ? lines : undefined}>
      {children}
    </Text>
    {right}
  </View>
);

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
      radius={radii.xl}
      shadow="md"
      gradientBorder={highlighted}
      style={styles.card}
      contentStyle={styles.clip}
      accessibilityLabel={`ออเดอร์ ${order.order_number} ${order.status_label || FM_STATUS_LABEL[status]}`}
    >
      {/* แถบสีสถานะด้านซ้าย */}
      <View style={[styles.stripe, { backgroundColor: t.fg }]} />
      <View style={styles.body}>
        {/* ---------- หัว ---------- */}
        <View style={styles.headRow}>
          <View style={styles.flex}>
            {/* เลขออเดอร์ต้องเห็นครบ (จอแคบ = ย่อฟอนต์แทนการตัดท้าย) */}
            <Text style={[typography.h3, { color: colors.textStrong }]} numberOfLines={1} adjustsFontSizeToFit minimumFontScale={0.8}>
              {order.order_number}
            </Text>
            <View style={styles.timeRow}>
              <Icon name="clock" size={13} color={colors.textFaint} />
              <Text style={[typography.caption, { color: colors.textFaint }]}>{timeAgoTh(order.created_at, now)}</Text>
            </View>
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
            icon={order.delivery_type === 'rider' ? 'moped' : 'storefront'}
            tone={order.delivery_type === 'rider' ? 'info' : 'neutral'}
          />
          <Pill
            label={isCod ? 'เก็บเงินปลายทาง' : order.payment_status_label || 'จ่ายแล้ว'}
            icon={isCod ? 'money' : 'wallet'}
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
                      <Icon name="basket" size={22} color={colors.textFaint} />
                    </View>
                  )}
                  <View style={styles.flex}>
                    <View style={styles.titleRow}>
                      <View style={[styles.qty, { backgroundColor: colors.goldSoft }]}>
                        <Text style={[typography.micro, { color: colors.goldDeep }]}>{item.quantity}×</Text>
                      </View>
                      <Text style={[typography.bodyStrong, styles.flex, { color: colors.textStrong }]} numberOfLines={2}>
                        {item.title}
                      </Text>
                    </View>
                    {!!item.options_label && (
                      <View style={styles.subRow}>
                        <Icon name="plus" size={12} color={colors.textMuted} weight="bold" />
                        <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>{item.options_label}</Text>
                      </View>
                    )}
                    {!!item.note && (
                      <View style={styles.subRow}>
                        <Icon name="note-pencil" size={13} color={colors.warning} />
                        <Text style={[typography.bodySm, styles.flex, { color: colors.warning }]}>{item.note}</Text>
                      </View>
                    )}
                  </View>
                  <PriceText amount={item.line_total} size="sm" tone="strong" />
                </View>
              );
            })
          )}
        </View>

        {/* ---------- ลูกค้า / การจัดส่ง ---------- */}
        {(!!order.buyer ||
          (order.delivery_type === 'rider' && !!order.delivery_address) ||
          !!order.delivery_notes ||
          !!order.rider_job ||
          (status === 'cancelled' && !!order.cancel_reason)) && (
          <View style={[styles.metaBox, { borderTopColor: colors.divider }]}>
            {!!order.buyer && (
              <MetaRow
                icon="user"
                color={colors.text}
                right={
                  order.buyer.phone ? (
                    <Button3D
                      title="โทร"
                      icon="phone"
                      size="sm"
                      variant="secondary"
                      onPress={() => callPhone(order.buyer?.phone)}
                    />
                  ) : null
                }
              >
                {order.buyer.name}
              </MetaRow>
            )}
            {order.delivery_type === 'rider' && !!order.delivery_address && (
              <MetaRow icon="map-pin" color={colors.textMuted} lines={2}>
                {order.delivery_address}
              </MetaRow>
            )}
            {!!order.delivery_notes && (
              <MetaRow icon="chat-circle-dots" color={colors.warning} lines={3}>
                {order.delivery_notes}
              </MetaRow>
            )}
            {!!order.rider_job && (
              <MetaRow
                icon="moped"
                color={colors.info}
                right={
                  order.rider_job.rider_phone ? (
                    <Button3D
                      title="โทรหาไรเดอร์"
                      icon="phone"
                      size="sm"
                      variant="secondary"
                      onPress={() => callPhone(order.rider_job?.rider_phone)}
                    />
                  ) : order.rider_job.tracking_url ? (
                    <Button3D
                      title="ติดตาม"
                      icon="map-trifold"
                      size="sm"
                      variant="secondary"
                      onPress={() => openHttpsLink(order.rider_job?.tracking_url, 'ติดตามไรเดอร์')}
                    />
                  ) : null
                }
              >
                {RIDER_STATUS_TEXT[order.rider_job.status] || order.rider_job.status}
                {order.rider_job.rider_name ? ` · ${order.rider_job.rider_name}` : ''}
              </MetaRow>
            )}
            {status === 'cancelled' && !!order.cancel_reason && (
              <MetaRow icon="x-circle" color={colors.danger} lines={0}>
                เหตุผลที่ยกเลิก: {order.cancel_reason}
              </MetaRow>
            )}
          </View>
        )}

        {/* ---------- ยอด ---------- */}
        <View style={[styles.totalRow, { backgroundColor: colors.inset }]}>
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
                  size="lg"
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
            icon={<Icon name="x-circle" size={16} color={colors.danger} />}
            variant="secondary"
            size="sm"
            onPress={() => onAction(order, 'cancel')}
            style={styles.cancel}
            textStyle={{ color: colors.danger }}
          />
        )}
      </View>
    </Card3D>
  );
};

const styles = StyleSheet.create({
  card: {
    marginBottom: spacing.md,
  },
  clip: {
    overflow: 'hidden',
  },
  stripe: {
    position: 'absolute',
    left: 0,
    top: 0,
    bottom: 0,
    width: 5,
  },
  body: {
    padding: spacing.lg,
    paddingLeft: spacing.lg + 5,
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
    alignItems: 'flex-start',
    gap: spacing.sm,
  },
  timeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 1,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.md,
  },
  items: {
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
    gap: spacing.md,
  },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
  },
  thumb: {
    width: 48,
    height: 48,
    borderRadius: 12,
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 6,
  },
  qty: {
    minWidth: 26,
    height: 20,
    borderRadius: 6,
    paddingHorizontal: 5,
    marginTop: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  subRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    marginTop: 3,
  },
  metaBox: {
    marginTop: spacing.md,
    paddingTop: spacing.sm,
    borderTopWidth: 1,
    gap: spacing.xs,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    minHeight: 32,
  },
  metaIcon: {
    marginTop: 1,
  },
  totalRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    marginTop: spacing.md,
    borderRadius: radii.md,
    paddingVertical: spacing.sm,
    paddingHorizontal: spacing.md,
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
    marginTop: spacing.md,
  },
});

export default FmOrderCard;
