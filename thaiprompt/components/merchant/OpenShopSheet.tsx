/**
 * OpenShopSheet — "เปิดร้านที่นี่วันนี้" (ร้านเคลื่อนที่) / "เปิดร้านวันนี้" (ร้านประจำที่)
 *
 * ขั้นตอน (ร้านเคลื่อนที่): หาตำแหน่ง GPS → ชื่อจุดขาย (เดาจากที่อยู่ แก้ได้) → เวลาปิดร้านอัตโนมัติ → แชร์ตำแหน่งสด
 * ร้านประจำที่: เปิดที่ที่อยู่ร้านที่ลงทะเบียนไว้ เลือกแค่เวลาปิด
 *
 * สิทธิ์ตำแหน่ง: หน้าจอแม่แสดง ConsentSheet + ขอสิทธิ์ก่อนเปิดแผ่นนี้ (แผ่นนี้แค่อ่านตำแหน่ง)
 * mode 'update' = ร้านเปิดอยู่แล้ว ใช้ย้ายจุดขาย/เปลี่ยนเวลาปิด (POST /seller/open ซ้ำ = อัปเดต)
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, StyleSheet, Switch, Text, View } from 'react-native';
import * as Location from 'expo-location';
import { Button3D, Chip, resultHaptic } from '@/components/ui';
import { FormSheet, Field } from '@/components/shop';
import { getCurrentCoords, type Coords } from '@/services/location';
import {
  FM_LIMITS,
  openFmShop,
  type OpenShopResult,
  type OwnerPresence,
} from '@/services/api/taladsodSellerApi';
import { useTheme, radii, spacing, typography } from '@/theme';
import { clockTh, toHHMM } from './fmHelpers';

export interface OpenShopSheetProps {
  visible: boolean;
  presence: OwnerPresence;
  /** ต้องใช้ GPS (ร้านเคลื่อนที่ หรือร้านที่ยังไม่มีที่อยู่ประจำ) */
  needsGps: boolean;
  mode?: 'open' | 'update';
  onClose: () => void;
  onDone: (result: OpenShopResult, message: string) => void;
}

const STEP_MIN = 30;
const PRESETS: Array<{ key: string; label: string; hours: number }> = [
  { key: 'h2', label: 'อีก 2 ชม.', hours: 2 },
  { key: 'h4', label: 'อีก 4 ชม.', hours: 4 },
  { key: 'h8', label: 'อีก 8 ชม.', hours: 8 },
  { key: 'h12', label: 'อีก 12 ชม.', hours: 12 },
];

/** ปัดขึ้นเป็นทุก 30 นาที */
const roundUp = (date: Date): Date => {
  const d = new Date(date);
  d.setSeconds(0, 0);
  const extra = (STEP_MIN - (d.getMinutes() % STEP_MIN)) % STEP_MIN;
  d.setMinutes(d.getMinutes() + extra);
  return d;
};

/** ที่อยู่จาก reverse geocode → ชื่อจุดขายสั้นๆ (ไม่มีข้อมูล = '') */
const labelFromAddress = (addr: Location.LocationGeocodedAddress | undefined): string => {
  if (!addr) return '';
  const parts = [addr.name, addr.street, addr.district || addr.subregion, addr.city]
    .map((p) => (typeof p === 'string' ? p.trim() : ''))
    .filter(Boolean);
  const unique: string[] = [];
  parts.forEach((p) => {
    if (!unique.some((u) => u.includes(p) || p.includes(u))) unique.push(p);
  });
  return unique.join(' ').slice(0, FM_LIMITS.LABEL_MAX);
};

export const OpenShopSheet: React.FC<OpenShopSheetProps> = ({
  visible,
  presence,
  needsGps,
  mode = 'open',
  onClose,
  onDone,
}) => {
  const { colors } = useTheme();
  const mountedRef = useRef(true);
  const locateIdRef = useRef(0);

  const [coords, setCoords] = useState<Coords | null>(null);
  const [locating, setLocating] = useState(false);
  const [locateError, setLocateError] = useState<string | null>(null);
  const [label, setLabel] = useState('');
  const [suggested, setSuggested] = useState('');
  const labelEditedRef = useRef(false);

  const [closeAt, setCloseAt] = useState<Date>(() => roundUp(new Date(Date.now() + 4 * 3600_000)));
  const [preset, setPreset] = useState<string | null>('h4');
  /** แชร์ตำแหน่งสด — เริ่มปิดไว้ ให้ผู้ขายเลือกเปิดเอง */
  const [liveSharing, setLiveSharing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const locate = useCallback(async () => {
    const id = ++locateIdRef.current;
    setLocating(true);
    setLocateError(null);
    const found = await getCurrentCoords({ accuracy: 'high', timeoutMs: 12_000 });
    if (!mountedRef.current || id !== locateIdRef.current) return;
    setLocating(false);
    if (!found) {
      setLocateError('ยังหาตำแหน่งไม่เจอ เปิด GPS ของเครื่อง แล้วกด "หาตำแหน่งอีกครั้ง"');
      return;
    }
    setCoords(found);
    // เดาชื่อจุดขายจากที่อยู่ (ไม่มีเน็ต/ไม่มีบริการ = ข้าม)
    try {
      const results = await Location.reverseGeocodeAsync({ latitude: found.latitude, longitude: found.longitude });
      if (!mountedRef.current || id !== locateIdRef.current) return;
      const guess = labelFromAddress(results?.[0]);
      setSuggested(guess);
      if (guess && !labelEditedRef.current) setLabel((prev) => (prev.trim() ? prev : guess));
    } catch {
      // เดาไม่ได้ก็ให้พิมพ์เอง
    }
  }, []);

  // เปิดแผ่น → รีเซ็ตค่า + หาตำแหน่ง
  useEffect(() => {
    if (!visible) {
      locateIdRef.current++;
      return;
    }
    const now = new Date();
    const existingClose = presence.is_open && presence.closes_at ? new Date(presence.closes_at) : null;
    if (existingClose && !Number.isNaN(existingClose.getTime()) && existingClose.getTime() > now.getTime() + 10 * 60_000) {
      setCloseAt(existingClose);
      setPreset(null);
    } else {
      setCloseAt(roundUp(new Date(now.getTime() + 4 * 3600_000)));
      setPreset('h4');
    }
    setLabel(presence.location_label || '');
    labelEditedRef.current = false;
    setSuggested('');
    setLiveSharing(mode === 'update' ? presence.live_location_sharing : false);
    setError(null);
    setCoords(null);
    setLocateError(null);
    if (needsGps) locate();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [visible]);

  const closeInfo = useMemo(() => {
    const now = Date.now();
    const diffMin = Math.round((closeAt.getTime() - now) / 60_000);
    if (diffMin < 10) return { ok: false, text: 'เวลาปิดต้องห่างจากตอนนี้อย่างน้อย 10 นาที' };
    if (diffMin > 24 * 60) return { ok: false, text: 'ตั้งเวลาปิดได้ไม่เกิน 24 ชั่วโมง' };
    const h = Math.floor(diffMin / 60);
    const m = diffMin % 60;
    const span = h > 0 ? `${h} ชม.${m > 0 ? ` ${m} นาที` : ''}` : `${m} นาที`;
    return { ok: true, text: `ร้านจะปิดเองตอน ${clockTh(closeAt.toISOString())} (อีก ${span})` };
  }, [closeAt]);

  const stepClose = (deltaMin: number) => {
    setPreset(null);
    setCloseAt((prev) => {
      const next = new Date(prev.getTime() + deltaMin * 60_000);
      const min = Date.now() + 10 * 60_000;
      const max = Date.now() + 24 * 3600_000;
      if (next.getTime() < min) return roundUp(new Date(min));
      if (next.getTime() > max) return prev;
      return next;
    });
  };

  const choosePreset = (key: string, hours: number) => {
    setPreset(key);
    setCloseAt(roundUp(new Date(Date.now() + hours * 3600_000)));
  };

  const canSubmit = closeInfo.ok && (!needsGps || !!coords) && !locating;

  const submit = async () => {
    if (!canSubmit || busy) return;
    setBusy(true);
    setError(null);
    // ส่งเป็น ISO (มีเขตเวลา) — ไม่พึ่งเขตเวลาของเครื่องให้ตรงกับ server แบบ "HH:MM"
    const closesAt = closeAt.toISOString();

    const trimmed = label.trim().slice(0, FM_LIMITS.LABEL_MAX);
    const result = await openFmShop(
      needsGps && coords
        ? {
            latitude: coords.latitude,
            longitude: coords.longitude,
            ...(trimmed ? { location_label: trimmed } : {}),
            closes_at: closesAt,
            live_location_sharing: liveSharing,
          }
        : { closes_at: closesAt }
    );
    if (!mountedRef.current) return;
    setBusy(false);
    if (result.success) {
      resultHaptic('success');
      onDone(result.data, result.message || (result.data.just_opened ? 'เปิดร้านแล้ว' : 'อัปเดตร้านแล้ว'));
      return;
    }
    resultHaptic('error');
    setError(result.message);
  };

  const title = mode === 'update' ? 'ย้ายจุดขาย / เปลี่ยนเวลาปิด' : needsGps ? 'เปิดร้านที่นี่วันนี้' : 'เปิดร้านวันนี้';

  return (
    <FormSheet
      visible={visible}
      icon={mode === 'update' ? '📍' : '🛒'}
      title={title}
      description={
        needsGps
          ? 'ลูกค้าใกล้ๆ จะเห็นร้านคุณบนแผนที่ และผู้ติดตามร้านจะได้รับแจ้งเตือนว่าร้านเปิดแล้ว'
          : 'ร้านจะเปิดที่ที่อยู่ร้านที่ลงทะเบียนไว้ ลูกค้าสั่งได้จนถึงเวลาปิด'
      }
      submitLabel={mode === 'update' ? 'บันทึก' : 'เปิดร้านเลย'}
      submitVariant="success"
      submitDisabled={!canSubmit}
      onSubmit={submit}
      busy={busy}
      cancelLabel="ยกเลิก"
      onClose={onClose}
    >
      {/* ---------- ตำแหน่ง ---------- */}
      {needsGps && (
        <View style={[styles.box, { backgroundColor: colors.inset, borderColor: colors.border }]}>
          <Text style={[typography.caption, { color: colors.textMuted }]}>ตำแหน่งร้านวันนี้</Text>
          {locating ? (
            <View style={styles.row}>
              <ActivityIndicator color={colors.gold} />
              <Text style={[typography.body, { color: colors.text }]}>กำลังหาตำแหน่ง...</Text>
            </View>
          ) : coords ? (
            <View style={styles.row}>
              <Text style={styles.pin}>📍</Text>
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.success }]}>ได้ตำแหน่งแล้ว</Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>
                  {typeof coords.accuracy === 'number' ? `แม่นยำประมาณ ±${Math.round(coords.accuracy)} เมตร` : 'จากตำแหน่งของเครื่องตอนนี้'}
                </Text>
              </View>
              <Button3D title="หาใหม่" size="sm" variant="secondary" onPress={locate} />
            </View>
          ) : (
            <>
              <Text style={[typography.bodySm, styles.gapTop, { color: colors.danger }]}>
                {locateError || 'ยังไม่ได้ตำแหน่ง'}
              </Text>
              <Button3D title="หาตำแหน่งอีกครั้ง" icon="🔄" size="sm" variant="secondary" onPress={locate} style={styles.gapTop} />
            </>
          )}
        </View>
      )}

      {/* ---------- ชื่อจุดขาย (ร้านเคลื่อนที่เท่านั้น — ร้านประจำใช้ที่อยู่ร้าน) ---------- */}
      {needsGps && (
        <Field
          label="ชื่อจุดขาย (ลูกค้าจะเห็น)"
          value={label}
          onChangeText={(text) => {
            labelEditedRef.current = true;
            setLabel(text);
          }}
          placeholder="เช่น หน้าตลาดนัดเช้า ซอย 5"
          maxLength={FM_LIMITS.LABEL_MAX}
          hint={`${label.length}/${FM_LIMITS.LABEL_MAX}`}
          returnKeyType="done"
        />
      )}
      {needsGps && !!suggested && suggested !== label.trim() && (
        <Chip
          label={`ใช้: ${suggested}`}
          icon="📍"
          size="sm"
          onPress={() => {
            labelEditedRef.current = true;
            setLabel(suggested);
          }}
          style={styles.suggest}
        />
      )}

      {/* ---------- เวลาปิด ---------- */}
      <Text style={[typography.caption, styles.sectionLabel, { color: colors.textMuted }]}>ปิดร้านอัตโนมัติ</Text>
      <View style={styles.chips}>
        {PRESETS.map((p) => (
          <Chip key={p.key} label={p.label} size="sm" selected={preset === p.key} onPress={() => choosePreset(p.key, p.hours)} />
        ))}
      </View>
      <View style={[styles.stepper, { backgroundColor: colors.inset, borderColor: colors.border }]}>
        <Button3D title="−30 นาที" size="sm" variant="secondary" onPress={() => stepClose(-STEP_MIN)} />
        <View style={styles.clock}>
          <Text style={[typography.h1, { color: colors.textStrong }]} accessibilityLabel={`ปิดร้านเวลา ${toHHMM(closeAt)} น.`}>
            {toHHMM(closeAt)}
          </Text>
          <Text style={[typography.micro, { color: colors.textMuted }]}>
            {closeAt.toDateString() === new Date().toDateString() ? 'วันนี้' : 'พรุ่งนี้'}
          </Text>
        </View>
        <Button3D title="+30 นาที" size="sm" variant="secondary" onPress={() => stepClose(STEP_MIN)} />
      </View>
      <Text style={[typography.caption, styles.gapTop, { color: closeInfo.ok ? colors.textMuted : colors.danger }]}>
        {closeInfo.text}
      </Text>

      {/* ---------- แชร์ตำแหน่งสด ---------- */}
      {needsGps && (
        <View style={[styles.box, styles.liveBox, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          <View style={styles.row}>
            <View style={styles.flex}>
              <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>📡 แชร์ตำแหน่งสด</Text>
              <Text style={[typography.caption, { color: colors.textMuted }]}>
                เหมาะกับรถเข็นที่ขยับไปเรื่อยๆ แอปส่งตำแหน่งทุก 30 วินาที เฉพาะตอนเปิดแอปไว้
              </Text>
            </View>
            <Switch
              value={liveSharing}
              onValueChange={setLiveSharing}
              trackColor={{ false: colors.border, true: colors.success }}
              thumbColor={colors.card}
              accessibilityLabel="แชร์ตำแหน่งสด"
            />
          </View>
          <Text style={[typography.micro, styles.gapTop, { color: colors.textFaint }]}>
            ปิดแอปแล้วการแชร์จะหยุดชั่วคราว ถ้าไม่ได้ส่งตำแหน่งเกิน {presence.live_stale_minutes} นาที ระบบจะปิดร้านให้
          </Text>
        </View>
      )}

      {!!error && (
        <View style={[styles.errorBox, { backgroundColor: colors.dangerSoft }]} accessibilityRole="alert">
          <Text style={[typography.bodySm, { color: colors.danger }]}>{error}</Text>
        </View>
      )}
    </FormSheet>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  box: {
    marginTop: spacing.md,
    borderRadius: radii.md,
    borderWidth: 1,
    padding: spacing.md,
  },
  liveBox: {
    marginTop: spacing.lg,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.xs,
  },
  pin: {
    fontSize: 22,
  },
  gapTop: {
    marginTop: spacing.sm,
  },
  suggest: {
    marginTop: spacing.sm,
    alignSelf: 'flex-start',
  },
  sectionLabel: {
    marginTop: spacing.lg,
    marginBottom: spacing.xs,
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
  },
  stepper: {
    marginTop: spacing.sm,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderRadius: radii.md,
    borderWidth: 1,
    padding: spacing.sm,
  },
  clock: {
    alignItems: 'center',
  },
  errorBox: {
    marginTop: spacing.md,
    borderRadius: radii.md,
    padding: spacing.md,
  },
});

export default OpenShopSheet;
