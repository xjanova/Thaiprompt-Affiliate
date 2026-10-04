/**
 * JobOfferCard — การ์ดข้อเสนองานในหน้า "งานใกล้ฉัน" (ไรเดอร์รอบ 2 ตามม็อกอัป RiderOffer)
 *
 * - ภาพเส้นทางจริงด้านบนเมื่อ server ส่ง route_polyline มา (ไม่มี = ป้ายประเภทงานแบบเดิม)
 * - ลูกค้าประจำล็อกเรียก → แถบชมพูมีรูปลูกค้า + จำนวนหัวใจที่ให้ไรเดอร์คนนี้
 * - การ์ดน้ำเงินลายกนก "คุณได้รับ" = rider_total จาก server (ค่าส่ง + โบนัสจากร้าน แยกให้เห็น)
 *   งานจ่ายก่อน (ส่งมอบด้วย QR) = ป้าย "จ่ายแน่นอน" เพราะเงินลูกค้าพักไว้ในระบบแล้ว
 * - จุดรับ/จุดส่งพร้อมระยะ (ระบุว่าตามถนนจริงหรือโดยประมาณ) · ชิปเวลา/การชำระเงิน
 * - ปุ่ม "ข้าม" / "รับงานนี้" ใช้ตัวรับงานเดิม (useAcceptJob) — ตัวเลขทุกตัวมาจาก server
 */

import React from 'react';
import { StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Button3D, Card3D, Icon, Pill, PriceText, formatBaht, type IconName } from '@/components/ui';
import { useTheme, radii, spacing, typography } from '@/theme';
import { PersonAvatar } from '@/components/people/PersonAvatar';
import type { RiderJobSummary } from '@/services/api/riderApi';
import { personPhotoUri } from '@/services/api/handoverApi';
import { isVerifiedFlag } from '@/components/people/VerifiedBadge';
import {
  distanceSourceLabel,
  formatKm,
  formatMinutes,
  formatTime,
  riderTotalOf,
  shopBonusOf,
} from './riderHelpers';
import { IconTile, MapTag, NavyCard, jobTypeIcon } from './RiderVisuals';
import { RouteSketch, hasDrawableRoute } from './RouteSketch';

export interface JobOfferCardProps {
  job: RiderJobSummary;
  accepting: boolean;
  disabled: boolean;
  onAccept: () => Promise<void>;
  onSkip: () => Promise<void>;
  onOpen: () => void;
}

// =====================================================
// ชิ้นส่วนย่อย
// =====================================================

/** แถบ "ลูกค้าล็อกเรียกคุณโดยตรง" (พื้นชมพูอ่อน + หัวใจแดง) */
const LockedBanner: React.FC<{ job: RiderJobSummary }> = ({ job }) => {
  const { colors } = useTheme();
  const name = job.buyer?.display_name || 'ลูกค้าประจำ';
  const hearts = Math.max(0, Math.round(Number(job.buyer?.hearts_given) || 0));
  return (
    <View
      style={[styles.locked, { backgroundColor: colors.dangerSoft }]}
      accessible
      accessibilityLabel={`${name} ล็อกเรียกคุณโดยตรง${hearts > 0 ? ` ให้หัวใจคุณ ${hearts} ดวง` : ''}`}
    >
      {/* รูปลูกค้าผ่าน allowlist เดียวกับฝั่งผู้ซื้อ (รูปลายน้ำจากเว็บเราเท่านั้น — L5) */}
      <PersonAvatar
        uri={personPhotoUri(job.buyer?.photo_url)}
        name={name}
        size={44}
        ring="none"
        verified={isVerifiedFlag(job.buyer?.verified)}
        surfaceColor={colors.dangerSoft}
      />
      <View style={styles.flex}>
        <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
          {name}ล็อกเรียกคุณโดยตรง
        </Text>
        <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
          {hearts > 0 ? `ลูกค้าประจำ · ให้หัวใจคุณ ${hearts.toLocaleString('th-TH')} ดวง` : 'ลูกค้าประจำของคุณ'}
        </Text>
      </View>
      <Icon name="heart" size={24} color={colors.danger} weight="fill" />
    </View>
  );
};

/** การ์ดน้ำเงิน "คุณได้รับ" — ยอดทองตัวใหญ่ + ที่มาของเงิน */
const EarningsPanel: React.FC<{ job: RiderJobSummary }> = ({ job }) => {
  const { colors } = useTheme();
  const total = riderTotalOf(job);
  const bonus = shopBonusOf(job);
  const base = job.earnings_breakdown ? Number(job.earnings_breakdown.rider_earnings) || 0 : job.rider_earnings;
  const prepaid = !!job.handover?.required && !job.is_cod;

  return (
    <NavyCard padding={spacing.lg} radius={radii.lg + 1} ornamentWidth={150} style={styles.block}>
      <View style={styles.earnRow}>
        <View style={styles.flex}>
          <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>คุณได้รับ</Text>
          <PriceText amount={total} size="xl" style={[typography.moneyLg, { color: colors.goldLight }]} numberOfLines={1} />
          <Text style={[typography.caption, { color: colors.onHeaderMuted }]} numberOfLines={2}>
            {bonus > 0 ? `ค่าส่ง ${formatBaht(base)} + โบนัสจากร้าน ${formatBaht(bonus)}` : 'ค่าส่งหลังหักค่าบริการระบบแล้ว'}
          </Text>
        </View>
        {prepaid && (
          <View style={styles.earnSide}>
            <Pill label="จ่ายแน่นอน" tone="gold" icon="lock" />
            <Text style={[typography.micro, styles.earnSideText, { color: colors.onHeaderMuted }]}>
              เงินพักไว้แล้ว{'\n'}จ่ายเมื่อสแกนส่งของ
            </Text>
          </View>
        )}
      </View>
    </NavyCard>
  );
};

/** แถวจุดรับ / จุดส่ง พร้อมระยะทางชิดขวา */
const LegRow: React.FC<{
  kind: 'pickup' | 'dropoff';
  label: string;
  title: string;
  distance: string | null;
  note?: string | null;
  last?: boolean;
}> = ({ kind, label, title, distance, note, last }) => {
  const { colors } = useTheme();
  const icon: IconName = kind === 'pickup' ? 'storefront' : 'map-pin';
  return (
    <View style={styles.leg}>
      <View style={styles.legRail}>
        <IconTile icon={icon} tone={kind === 'pickup' ? 'gold' : 'navy'} size={40} />
        {!last && <View style={[styles.legLine, { backgroundColor: colors.divider }]} />}
      </View>
      <View style={[styles.flex, !last && styles.legGap]}>
        <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
          {label}
        </Text>
        <Text numberOfLines={1} style={[typography.h3, { color: colors.textStrong }]}>
          {title}
        </Text>
        {!!note && (
          <Text numberOfLines={2} style={[typography.micro, { color: colors.textFaint }]}>
            {note}
          </Text>
        )}
      </View>
      {!!distance && <Text style={[typography.bodyStrong, styles.legDistance, { color: colors.textStrong }]}>{distance}</Text>}
    </View>
  );
};

// =====================================================
// การ์ด
// =====================================================

export const JobOfferCard: React.FC<JobOfferCardProps> = ({ job, accepting, disabled, onAccept, onSkip, onOpen }) => {
  const { colors } = useTheme();
  const locked = !!job.locked_by_buyer;
  const hasRoute = hasDrawableRoute(job.route_polyline);
  const toPickup = formatKm(job.distance_to_pickup_km);
  const tripKm = formatKm(job.distance_km);
  const eta = formatMinutes(job.estimated_duration_minutes);
  const sourceLabel = distanceSourceLabel(job.distance_source);
  const total = riderTotalOf(job);
  const highlight = locked || total >= 60;
  const innerRadius = highlight ? radii.xl - 1.5 : radii.xl;

  const tags = (
    <View style={styles.jobTags}>
      <MapTag icon={jobTypeIcon(job.job_type)} label={job.job_type_text || 'งานส่ง'} />
      <View style={styles.tagsRight}>
        {locked && <Pill label="ล็อกเรียก" tone="danger" icon="heart" />}
        {!!job.created_at && <MapTag icon="clock" label={formatTime(job.created_at)} iconColor={colors.textMuted} />}
      </View>
    </View>
  );

  return (
    <Card3D
      onPress={onOpen}
      style={styles.card}
      padding={0}
      radius={radii.xl}
      gradientBorder={highlight}
      accessibilityLabel={`${locked ? 'ลูกค้าล็อกเรียกคุณ ' : ''}งาน ${job.title} ได้รับ ${total} บาท`}
      accessibilityHint="แตะเพื่อดูรายละเอียดงาน"
    >
      {hasRoute && (
        <RouteSketch polyline={job.route_polyline} height={148} radiusTop={innerRadius}>
          {tags}
        </RouteSketch>
      )}

      <View style={styles.body}>
        {!hasRoute && tags}
        {locked && <LockedBanner job={job} />}
        <EarningsPanel job={job} />

        {/* ---------- จุดรับ / จุดส่ง ---------- */}
        <View style={[styles.legs, { backgroundColor: colors.inset, borderColor: colors.border }]}>
          <LegRow
            kind="pickup"
            label={toPickup ? `รับของที่ · ห่างคุณ ${toPickup}` : 'รับของที่'}
            title={job.pickup?.name || 'จุดรับของ'}
            distance={toPickup}
          />
          <LegRow
            kind="dropoff"
            label={sourceLabel ? `ส่งที่ · ${sourceLabel}` : 'ส่งที่'}
            title={job.dropoff?.area || job.dropoff?.address || 'จุดส่ง'}
            distance={tripKm}
            note={job.dropoff?.is_approximate ? 'ที่อยู่เต็มจะแสดงหลังรับงาน' : null}
            last
          />
        </View>

        {/* ---------- ชิปสรุป ---------- */}
        <View style={styles.chips}>
          {!!eta && <Pill label={`รวม ${eta}`} tone="neutral" icon="clock" />}
          {job.is_cod ? (
            <Pill label={`เก็บเงินปลายทาง ${formatBaht(job.cod_amount)}`} tone="warning" icon="money" />
          ) : (
            <Pill label="จ่ายแล้ว ไม่มีเก็บเงินปลายทาง" tone="success" icon="wallet" />
          )}
        </View>

        {!!job.items_summary && (
          <View style={[styles.itemsRow, { borderTopColor: colors.divider }]}>
            <Icon name="receipt" size={16} color={colors.textMuted} />
            <Text style={[typography.bodySm, styles.flex, { color: colors.text }]} numberOfLines={2}>
              {job.items_summary}
            </Text>
          </View>
        )}
        {job.is_cod && (
          <View style={[styles.codRow, { backgroundColor: colors.warningSoft }]}>
            <Icon name="money" size={17} color={colors.warning} weight="fill" />
            <Text style={[typography.caption, styles.codText, { color: colors.text }]}>
              ต้องเก็บเงินสดจากลูกค้า {formatBaht(job.cod_amount)}
            </Text>
          </View>
        )}

        <View style={styles.actions}>
          <Button3D
            title="ข้าม"
            variant="secondary"
            size="lg"
            disabled={disabled}
            onPress={onSkip}
            accessibilityHint="ซ่อนงานนี้"
            style={styles.skip}
          />
          <Button3D
            title="รับงานนี้"
            icon="hand-tap"
            variant="primary"
            size="lg"
            disabled={disabled && !accepting}
            loading={accepting}
            loadingText="กำลังรับงาน..."
            onPress={onAccept}
            style={styles.accept}
          />
        </View>
      </View>
    </Card3D>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  card: {
    marginBottom: spacing.lg,
  },
  body: {
    padding: spacing.lg,
  },
  block: {
    marginBottom: spacing.md,
  },
  jobTags: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  tagsRight: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
  },
  locked: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    borderRadius: radii.lg,
    padding: spacing.md,
    marginBottom: spacing.md,
  },
  earnRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  earnSide: {
    alignItems: 'flex-end',
    gap: spacing.xs,
    maxWidth: '46%',
  },
  earnSideText: {
    textAlign: 'right',
  },
  legs: {
    borderRadius: radii.lg,
    borderWidth: 1,
    padding: spacing.md,
  },
  leg: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
  },
  legRail: {
    alignItems: 'center',
  },
  legLine: {
    width: 2,
    flex: 1,
    minHeight: 10,
    marginVertical: 2,
    borderRadius: 1,
  },
  legGap: {
    paddingBottom: spacing.md,
  },
  legDistance: {
    marginTop: 2,
    fontVariant: ['tabular-nums'],
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.md,
  },
  itemsRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
  codRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    borderRadius: radii.sm + 1,
  },
  codText: {
    flex: 1,
    fontWeight: '600',
  },
  actions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm + 2,
    marginTop: spacing.lg,
  },
  skip: {
    flex: 1,
    minWidth: 100,
  },
  accept: {
    flex: 1.8,
  },
});

export default JobOfferCard;
