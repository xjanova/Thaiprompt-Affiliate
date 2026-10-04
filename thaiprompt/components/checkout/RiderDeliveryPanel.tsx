/**
 * ชิ้นส่วน "ส่งด้วยไรเดอร์" ของหน้าชำระเงิน (ร้านค้า + ตลาดสด) — ตามแบบ Checkout.png ที่เจ้าของอนุมัติ
 *
 * - RiderRouteBlock   แผนที่เส้นทางจริง (route_polyline) + ป้าย "คิดตามถนนจริง" + ช่องระยะ/เวลา/ส่งถึงราว
 * - RiderChoiceBlock  ตัวเลือกไรเดอร์: จับคู่อัตโนมัติ | เรียกคนโปรด (เฉพาะคนที่ can_lock จาก GET /riders/favorites)
 * - RiderFeeLines     บรรทัดค่าส่ง + "ไรเดอร์ได้รับทั้งหมด" (รวมโบนัสจากร้าน) + ส่งฟรีเมื่อร้านออกให้
 *                     + หมายเหตุเมื่อร้านช่วยออกค่าส่งได้ไม่เต็ม (subsidy_capped — B9)
 * - EscrowNotice      การ์ดอธิบายว่าเงินถูกพักไว้จนกว่าจะสแกน QR ใส่กันตอนรับของ
 *
 * ตัวเลขเงินทุกตัวมาจาก server — ชิ้นส่วนนี้แค่แสดง ไม่คำนวณค่าส่งเอง
 */

import React, { useMemo } from 'react';
import { ActivityIndicator, StyleSheet, View } from 'react-native';
import { router } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { Card3D, Icon, LiveMap, Pill, PriceText, decodePolyline, type LiveMapMarker } from '@/components/ui';
import { IconTile, RadioMark } from '@/components/shop';
import { PersonAvatar } from '@/components/people/PersonAvatar';
import type { PersonCard } from '@/services/api/handoverApi';
import { formatDistance } from '@/services/location';
import { useTheme, radii, spacing, typography } from '@/theme';

// =====================================================
// ชนิดข้อมูลกลาง (แปลงจาก quote ของร้านค้า/ตลาดสด)
// =====================================================

export interface RiderQuoteView {
  distance_km: number | null;
  estimated_minutes: number | null;
  distance_source: string | null;
  route_polyline: string | null;
  /** ค่าส่งที่ผู้ซื้อจ่ายจริง */
  buyer_fee: number | null;
  /** ค่าส่งเต็ม */
  fee_full: number | null;
  rider_earnings: number | null;
  shop_bonus: number | null;
  shop_subsidy: number | null;
  rider_total: number | null;
  surcharge: number | null;
  free_delivery: boolean;
  /** ร้านช่วยออกค่าส่งได้ไม่เต็มที่ตั้งไว้ (ยอดออเดอร์น้อย) → ผู้ซื้อจ่ายส่วนที่เหลือ (B9) */
  subsidy_capped?: boolean;
}

type Coord = { latitude: number; longitude: number };

/** ระยะคิดตามถนนจริง (ไม่ใช่เส้นตรง × ตัวคูณ) */
export const isRoadDistance = (source: string | null | undefined): boolean =>
  !!source && source !== 'haversine';

/** เวลาโดยประมาณที่ของถึง (HH:MM) */
const etaText = (minutes: number | null): string | null => {
  if (!minutes || !Number.isFinite(minutes) || minutes <= 0) return null;
  const at = new Date(Date.now() + minutes * 60_000);
  return `${String(at.getHours()).padStart(2, '0')}:${String(at.getMinutes()).padStart(2, '0')}`;
};

// =====================================================
// แผนที่เส้นทาง + ช่องระยะ/เวลา
// =====================================================

export interface RiderRouteBlockProps {
  quote: RiderQuoteView;
  /** ชื่อร้าน (ป้ายหมุดร้าน) */
  storeName?: string | null;
  /** พิกัดร้าน/จุดส่ง (ไม่ส่ง = ใช้ต้น/ปลายของเส้นทาง) */
  pickup?: Coord | null;
  dropoff?: Coord | null;
  /** แสดงแผนที่ (ค่าเริ่มต้น true) */
  showMap?: boolean;
  /** หัวข้อของการ์ด (ค่าเริ่มต้น "ส่งด้วยไรเดอร์") */
  title?: string;
}

export const RiderRouteBlock: React.FC<RiderRouteBlockProps> = ({
  quote,
  storeName,
  pickup,
  dropoff,
  showMap = true,
  title = 'ส่งด้วยไรเดอร์',
}) => {
  const { colors } = useTheme();
  const road = isRoadDistance(quote.distance_source);

  const markers = useMemo<LiveMapMarker[]>(() => {
    const pts = decodePolyline(quote.route_polyline, 6);
    const start = pickup || (pts.length > 1 ? { latitude: pts[0][0], longitude: pts[0][1] } : null);
    const end = dropoff || (pts.length > 1 ? { latitude: pts[pts.length - 1][0], longitude: pts[pts.length - 1][1] } : null);
    const list: LiveMapMarker[] = [];
    if (start) list.push({ id: 'shop', kind: 'shop', latitude: start.latitude, longitude: start.longitude, label: storeName || 'ร้าน' });
    if (end) list.push({ id: 'home', kind: 'home', latitude: end.latitude, longitude: end.longitude, label: 'จุดส่ง' });
    return list;
  }, [quote.route_polyline, pickup, dropoff, storeName]);

  const eta = etaText(quote.estimated_minutes);
  const hasMap = showMap && (markers.length > 0 || !!quote.route_polyline);

  return (
    <View>
      <View style={styles.headRow}>
        <Icon name="path" size={20} color={colors.navy} />
        <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>{title}</Text>
        {road ? <Pill label="คิดตามถนนจริง" tone="success" icon="check" /> : quote.distance_km !== null ? <Pill label="ระยะโดยประมาณ" tone="neutral" /> : null}
      </View>

      {hasMap && (
        <LiveMap
          markers={markers}
          routePolyline={quote.route_polyline}
          height={150}
          openTargetId={null}
          hideRecenter
          accessibilityLabel={`แผนที่เส้นทางจาก${storeName ? `ร้าน ${storeName}` : 'ร้าน'}ถึงจุดส่ง`}
          style={styles.gapTop}
        />
      )}

      <View style={[styles.tiles, styles.gapTop]}>
        <InfoTile label={road ? 'ระยะตามถนน' : 'ระยะทาง'} value={quote.distance_km !== null ? formatDistance(quote.distance_km) : '-'} />
        <InfoTile label="เวลาโดยประมาณ" value={quote.estimated_minutes ? `~${Math.round(quote.estimated_minutes)} นาที` : '-'} />
        <InfoTile label="ส่งถึงราว" value={eta || '-'} />
      </View>
    </View>
  );
};

const InfoTile: React.FC<{ label: string; value: string }> = ({ label, value }) => {
  const { colors } = useTheme();
  return (
    <View style={[styles.tile, { backgroundColor: colors.inset, borderColor: colors.border }]} accessible accessibilityLabel={`${label} ${value}`}>
      <Text numberOfLines={1} style={[typography.micro, { color: colors.textMuted }]}>
        {label}
      </Text>
      <Text numberOfLines={1} style={[typography.h3, styles.tileValue, { color: colors.textStrong }]}>
        {value}
      </Text>
    </View>
  );
};

// =====================================================
// เลือกไรเดอร์: จับคู่อัตโนมัติ / เรียกคนโปรด
// =====================================================

export interface RiderChoiceBlockProps {
  /** ไรเดอร์ที่ล็อกเรียกได้ (can_lock) — เรียงหัวใจมากสุดก่อน */
  lockable: PersonCard[];
  /** id ที่เลือก (null = จับคู่อัตโนมัติ) */
  selectedId: number | null;
  onSelect: (riderId: number | null) => void;
  loading?: boolean;
  /** วินาทีที่ไรเดอร์คนโปรดได้สิทธิ์รับก่อน */
  lockOfferSeconds?: number;
  /** เกณฑ์หัวใจขั้นต่ำ (ใช้ในข้อความแนะนำเมื่อยังไม่มีคนโปรด) */
  lockMinHearts?: number;
  disabled?: boolean;
}

export const RiderChoiceBlock: React.FC<RiderChoiceBlockProps> = ({
  lockable,
  selectedId,
  onSelect,
  loading = false,
  lockOfferSeconds = 60,
  lockMinHearts = 11,
  disabled = false,
}) => {
  const { colors } = useTheme();
  const options = lockable.slice(0, 3);

  return (
    <View style={styles.choiceWrap} accessibilityRole="radiogroup">
      {options.map((rider) => {
        const selected = selectedId === rider.id;
        return (
          <Card3D
            key={rider.id}
            onPress={disabled ? undefined : () => onSelect(rider.id)}
            gradientBorder={selected}
            variant={selected ? 'raised' : 'flat'}
            shadow="sm"
            padding={spacing.md}
            radius={18}
            accessibilityRole="radio"
            accessibilityState={{ checked: selected }}
            accessibilityLabel={`เรียกคนโปรด ${rider.display_name}`}
          >
            <View style={styles.choiceRow}>
              <RadioMark selected={selected} disabled={disabled} />
              <PersonAvatar uri={rider.photo_url} name={rider.display_name} size={42} />
              <View style={styles.flex}>
                <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
                  เรียกคนโปรด · {rider.display_name}
                </Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>
                  ได้สิทธิ์รับก่อน {lockOfferSeconds} วินาที ไม่รับ ระบบหาคนอื่นให้
                </Text>
              </View>
            </View>
          </Card3D>
        );
      })}

      <Card3D
        onPress={disabled ? undefined : () => onSelect(null)}
        gradientBorder={selectedId === null && options.length > 0}
        variant={selectedId === null ? 'raised' : 'flat'}
        shadow="sm"
        padding={spacing.md}
        radius={18}
        accessibilityRole="radio"
        accessibilityState={{ checked: selectedId === null }}
        accessibilityLabel="จับคู่ไรเดอร์อัตโนมัติ"
      >
        <View style={styles.choiceRow}>
          <RadioMark selected={selectedId === null} disabled={disabled} />
          <IconTile icon="lightning" size={42} />
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>จับคู่อัตโนมัติ</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>ไรเดอร์ที่ว่างและใกล้ร้านที่สุด</Text>
          </View>
        </View>
      </Card3D>

      {loading ? (
        <View style={styles.inlineRow}>
          <ActivityIndicator size="small" color={colors.gold} />
          <Text style={[typography.caption, { color: colors.textMuted }]}>กำลังโหลดไรเดอร์คนโปรด…</Text>
        </View>
      ) : (
        <Text
          onPress={() => router.push('/riders/nearby?from=checkout' as never)}
          accessibilityRole="link"
          style={[typography.caption, styles.link, { color: colors.goldDeep }]}
        >
          {options.length > 0
            ? 'ดูไรเดอร์ใกล้ฉันและคนโปรดทั้งหมด ›'
            : `ให้หัวใจไรเดอร์คนเดิมครบ ${lockMinHearts} ดวง แล้วล็อกเรียกคนโปรดได้ · ดูไรเดอร์ใกล้ฉัน ›`}
        </Text>
      )}
    </View>
  );
};

// =====================================================
// บรรทัดค่าส่ง (ใส่ในการ์ดสรุปยอด)
// =====================================================

export interface RiderFeeLinesProps {
  quote: RiderQuoteView;
  /** แสดงบรรทัด "ไรเดอร์ได้รับทั้งหมด" (ค่าเริ่มต้น true) */
  showRiderTotal?: boolean;
  /** ขนาดตัวอักษรหัวบรรทัด */
  compact?: boolean;
}

export const RiderFeeLines: React.FC<RiderFeeLinesProps> = ({ quote, showRiderTotal = true, compact = false }) => {
  const { colors } = useTheme();
  const road = isRoadDistance(quote.distance_source);
  const labelStyle = compact ? typography.bodySm : typography.body;
  const fee = quote.free_delivery ? 0 : quote.buyer_fee ?? quote.fee_full ?? 0;
  const bonus = quote.shop_bonus ?? 0;
  const riderTotal = quote.rider_total ?? (quote.rider_earnings !== null ? quote.rider_earnings + bonus : null);

  const feeNote = [
    quote.distance_km !== null ? `ระยะ ${formatDistance(quote.distance_km)}${road ? ' ตามถนน' : ''}` : null,
    quote.surcharge && quote.surcharge > 0 ? `รวมค่าช่วงเร่งด่วน/กลางคืน ${quote.surcharge.toFixed(0)}` : null,
  ]
    .filter(Boolean)
    .join(' · ');

  return (
    <View style={styles.feeLines}>
      <View style={styles.feeRow}>
        <View style={styles.flex}>
          <Text style={[labelStyle, { color: colors.text }]}>ค่าส่ง</Text>
          {quote.free_delivery ? (
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              ร้านออกค่าส่ง{quote.fee_full ? ` ${quote.fee_full.toFixed(0)} บาท` : ''}ให้คุณ
            </Text>
          ) : quote.shop_subsidy && quote.shop_subsidy > 0 ? (
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              ร้านช่วยจ่าย {quote.shop_subsidy.toFixed(0)} บาท{feeNote ? ` · ${feeNote}` : ''}
            </Text>
          ) : feeNote ? (
            <Text style={[typography.caption, { color: colors.textMuted }]}>{feeNote}</Text>
          ) : null}
        </View>
        {quote.free_delivery || fee <= 0 ? (
          <Pill label="ส่งฟรี" tone="success" icon="moped" />
        ) : (
          <PriceText amount={fee} decimals={2} size="sm" tone="strong" />
        )}
      </View>

      {/* ร้านช่วยออกค่าส่งได้ไม่เต็ม (เกินรายได้ร้านจากออเดอร์นี้) → บอกผู้ซื้อว่าทำไมค่าส่งไม่ลดตามที่ร้านตั้งไว้ (B9) */}
      {!!quote.subsidy_capped && !quote.free_delivery && fee > 0 && (
        <View
          style={[styles.cappedNote, { backgroundColor: colors.infoSoft }]}
          accessible
          accessibilityLabel={(quote.shop_subsidy ?? 0) > 0 ? 'ร้านช่วยออกค่าส่งได้บางส่วน คุณจ่ายส่วนที่เหลือ' : 'ออเดอร์นี้ร้านยังช่วยออกค่าส่งไม่ได้ คุณจ่ายค่าส่งเต็ม'}
        >
          <Icon name="info" size={15} color={colors.info} />
          <Text style={[typography.caption, styles.flex, { color: colors.text }]}>
            {(quote.shop_subsidy ?? 0) > 0
              ? 'ร้านช่วยออกค่าส่งได้บางส่วน คุณจ่ายส่วนที่เหลือ'
              : 'ออเดอร์นี้ร้านยังช่วยออกค่าส่งไม่ได้ คุณจ่ายค่าส่งเต็ม'}
          </Text>
        </View>
      )}

      {showRiderTotal && riderTotal !== null && riderTotal > 0 && (
        <View style={styles.feeRow}>
          <View style={styles.flex}>
            <Text style={[labelStyle, { color: colors.text }]}>ไรเดอร์ได้รับทั้งหมด</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              {bonus > 0
                ? `ค่าส่ง ${(quote.rider_earnings ?? riderTotal - bonus).toFixed(0)} + โบนัสจากร้าน ${bonus.toFixed(0)} (ร้านจ่ายให้)`
                : 'ส่วนแบ่งค่าส่งที่ไรเดอร์ได้รับ'}
            </Text>
          </View>
          <PriceText amount={riderTotal} decimals={2} size="sm" tone="success" />
        </View>
      )}
    </View>
  );
};

// =====================================================
// การ์ดเงินพัก
// =====================================================

export const EscrowNotice: React.FC<{ style?: object }> = ({ style }) => {
  const { colors } = useTheme();
  return (
    <View style={[styles.escrow, { backgroundColor: colors.infoSoft, borderColor: colors.border }, style]} accessible>
      <IconTile icon="lock" tone="gold" size={42} />
      <View style={styles.flex}>
        <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>เงินของคุณถูกพักไว้อย่างปลอดภัย</Text>
        <Text style={[typography.caption, { color: colors.textMuted }]}>
          ร้านและไรเดอร์จะได้เงินเมื่อคุณกับไรเดอร์สแกน QR ใส่กันตอนรับของแล้วเท่านั้น
        </Text>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  gapTop: {
    marginTop: spacing.md,
  },
  headRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  tiles: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  tile: {
    flex: 1,
    borderRadius: radii.md,
    borderWidth: 1,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.sm,
  },
  tileValue: {
    marginTop: 1,
  },
  choiceWrap: {
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  choiceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  inlineRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  link: {
    fontWeight: '600',
    paddingVertical: spacing.xs,
  },
  feeLines: {
    gap: spacing.sm,
  },
  feeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  cappedNote: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.xs + 2,
    borderRadius: radii.md,
    paddingHorizontal: spacing.sm + 2,
    paddingVertical: spacing.xs + 2,
  },
  escrow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    borderRadius: radii.lg,
    borderWidth: 1,
    padding: spacing.md,
  },
});
