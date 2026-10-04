/**
 * ตรวจข้อมูลบัตร (ขั้นที่ 2 จาก 4) — ตามแบบ IdReview.png
 *
 * - ส่งรูปบัตรเข้ารอบปัจจุบัน (POST /ekyc/sessions/{id}/id-card) ครั้งเดียวต่อรูปต่อรอบ แล้วแสดงสิ่งที่ AI อ่านได้
 * - ผล retake (ภาพไม่ชัด/แสงสะท้อน/ไม่ใช่บัตรจริง ฯลฯ) → บอกเหตุผลเป็นข้อความไทย (ของ server ก่อน) + ปุ่มถ่ายใหม่อย่างเดียว
 * - แก้ชื่อ/วันเกิดได้ถ้า AI อ่านผิด → PATCH ก่อนไปขั้นใบหน้า (แก้เอง = เจ้าหน้าที่ตรวจทานอีกครั้ง บอกผู้ใช้ชัดๆ)
 * - ผลตรวจบัตร 3 สถานะ: ผ่าน (เขียว) / ไม่ผ่าน (แดง) / ไม่ทราบ (เทา "เจ้าหน้าที่จะตรวจให้" — ห้ามแดง)
 * - ตัวอ่านบัตรอัตโนมัติไม่พร้อม (ai_available = false) → ไม่โชว์ "อ่านบัตรสำเร็จ 0%" บอกให้กรอกชื่อ/วันเกิดเอง
 * - ระบบตรวจไม่ว่าง (503 EKYC_AI_BUSY) → รอตามเวลาที่ server บอก แล้วส่งรูปเดิมซ้ำ (ไม่นับเป็นครั้งที่พลาด)
 * - รอบหมดอายุ / ส่งรูปบัตรครบโควตารอบ (EKYC_CARD_LIMIT) → เริ่มรอบใหม่แล้วส่งรูปเดิมให้เองหนึ่งครั้ง
 *   (ระหว่างบันทึกข้อมูลที่แก้ก็เช่นกัน — ข้อมูลที่แก้ไว้ยังอยู่)
 * - "อ่านบัตรอีกครั้ง" → กลับไปถ่ายบัตรใหม่ให้ AI อ่านใหม่
 * - กันกดซ้ำทุกปุ่ม · ไม่ setState หลังออกจากหน้า · กันแคปหน้าจอ (มีรูปบัตรบนจอ)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, Pressable, StyleSheet, View } from 'react-native';
import { Image } from 'expo-image';
import { router, useIsFocused } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { Button3D, Card3D, Icon, Pill, resultHaptic } from '@/components/ui';
import { Field, FormSheet } from '@/components/shop';
import { EkycShell, InfoNote } from '@/components/ekyc/EkycKit';
import { useRetryCountdown } from '@/components/ekyc/cameraKit';
import { useMountedRef } from '@/components/taladsod/hooks';
import { useSensitiveScreen } from '@/hooks/useSensitiveScreen';
import { useEkycStore } from '@/stores/ekycStore';
import {
  cleanThaiName,
  correctEkycIdCard,
  EKYC_APP_OUTDATED_MESSAGE,
  formatThaiDate,
  isSessionUsable,
  parseThaiBirthDate,
  retryAfterSeconds,
  uploadEkycIdCard,
} from '@/services/api/ekycApi';
import { goResultForCode } from '@/services/ekyc/flow';
import { radii, spacing, typography, useTheme } from '@/theme';

/** busy = ระบบตรวจไม่ว่าง รอแล้วส่งรูปเดิมซ้ำ */
type Phase = 'uploading' | 'ready' | 'retake' | 'busy' | 'error';

/** ผลตรวจบัตร 1 ข้อ: ok = ผ่าน · bad = ไม่ผ่าน · unknown = ไม่ทราบ (เจ้าหน้าที่ตรวจให้) */
type CheckNote = { state: 'ok' | 'bad' | 'unknown'; text: string };

const triNote = (value: boolean | null, ok: string, bad: string, unknown: string): CheckNote =>
  value === true ? { state: 'ok', text: ok } : value === false ? { state: 'bad', text: bad } : { state: 'unknown', text: unknown };

export default function EkycReviewScreen() {
  useSensitiveScreen('ekyc-review');
  const { colors } = useTheme();
  const focused = useIsFocused();
  const mountedRef = useMountedRef();
  const session = useEkycStore((s) => s.session);
  const card = useEkycStore((s) => s.card);
  const corrections = useEkycStore((s) => s.corrections);

  const initialPhase: Phase = card?.result && card.sessionId === session?.session_id
    ? card.result.status === 'ok' ? 'ready' : 'retake'
    : 'uploading';
  const [phase, setPhase] = useState<Phase>(initialPhase);
  const [errorText, setErrorText] = useState<string | null>(null);
  const [aspect, setAspect] = useState(1.586);
  const [saving, setSaving] = useState(false);
  const { secondsLeft: retryLeft, start: startRetryCountdown } = useRetryCountdown();
  const uploadingRef = useRef(false);
  const retriedExpiredRef = useRef(false);
  const savingRef = useRef(false);

  // แก้ข้อมูล
  const [editOpen, setEditOpen] = useState(false);
  const [nameDraft, setNameDraft] = useState('');
  const [dayDraft, setDayDraft] = useState('');
  const [monthDraft, setMonthDraft] = useState('');
  const [yearDraft, setYearDraft] = useState('');
  const [editErrors, setEditErrors] = useState<{ name?: string; birth?: string }>({});

  // ไม่มีรอบ/ไม่มีรูป → กลับไปเริ่มใหม่
  useEffect(() => {
    if (!focused) return;
    if (!session) router.replace('/ekyc' as never);
    else if (!card) router.replace('/ekyc/capture' as never);
  }, [focused, session, card]);

  /**
   * เริ่มรอบใหม่ (รอบหมดเวลา / ส่งรูปบัตรครบโควตารอบ) — คงข้อมูลที่ผู้ใช้แก้ไว้
   * สำเร็จ = รอบเปลี่ยน → effect ด้านล่างส่งรูปบัตรเดิมเข้ารอบใหม่ให้เอง
   * @returns true = ได้รอบใหม่ที่ใช้ได้
   */
  const startFresh = useCallback(async (): Promise<boolean> => {
    const store = useEkycStore.getState();
    const keep = { ...store.corrections };
    const restarted = await store.startSession();
    if (!mountedRef.current) return false;
    if (!restarted.success) {
      // ยืนยันแล้ว / รอเจ้าหน้าที่ / ยังตรวจคำขอก่อนหน้า / ลองครบวันนี้ → ไปหน้าผล
      if (goResultForCode(restarted.code)) return false;
      resultHaptic('error');
      setErrorText(restarted.message);
      setPhase('error');
      return false;
    }
    if (!isSessionUsable(restarted.data)) {
      resultHaptic('error');
      setErrorText(EKYC_APP_OUTDATED_MESSAGE);
      setPhase('error');
      return false;
    }
    if (keep.name_th || keep.birth_date) useEkycStore.getState().setCorrections(keep);
    return true;
  }, [mountedRef]);

  const upload = useCallback(async () => {
    const state = useEkycStore.getState();
    const sid = state.session?.session_id;
    const uri = state.card?.uri;
    if (!sid || !uri || uploadingRef.current) return;
    // รอบที่มีท่าที่แอปรุ่นนี้ไม่รู้จัก → ไม่ส่งรูปเข้ารอบนี้ (ไปต่อก็ถ่ายใบหน้าไม่ครบ) ให้อัปเดตแอปก่อน
    if (!isSessionUsable(state.session)) {
      setErrorText(EKYC_APP_OUTDATED_MESSAGE);
      setPhase('error');
      return;
    }
    uploadingRef.current = true;
    setPhase('uploading');
    setErrorText(null);
    let res = await uploadEkycIdCard(sid, uri);
    // ต่อไม่ติดเลย (การเชื่อมต่อเก่าถูกปิด/เน็ตมือถือสลับ) → ลองส่งซ้ำเองหนึ่งครั้ง
    // ส่งรูปบัตรซ้ำในรอบเดิมปลอดภัย (server แทนที่รูปเดิม) · หมดเวลา (TIMEOUT) ไม่ลองซ้ำเอง
    if (!res.success && res.code === 'NETWORK_ERROR' && mountedRef.current) {
      await new Promise<void>((resolve) => setTimeout(resolve, 700));
      if (mountedRef.current) res = await uploadEkycIdCard(sid, uri);
    }
    uploadingRef.current = false;
    if (!mountedRef.current) return;

    if (res.success) {
      retriedExpiredRef.current = false;
      useEkycStore.getState().setCardResult(res.data, sid);
      resultHaptic(res.data.status === 'ok' ? 'success' : 'warning');
      setPhase(res.data.status === 'ok' ? 'ready' : 'retake');
      return;
    }

    // ระบบตรวจไม่ว่าง: รอบเดิม รูปเดิม — ไม่ใช่ความผิดของผู้ใช้
    if (res.code === 'EKYC_AI_BUSY') {
      resultHaptic('warning');
      startRetryCountdown(retryAfterSeconds(res.data));
      setPhase('busy');
      return;
    }
    if ((res.code === 'EKYC_SESSION_EXPIRED' || res.code === 'EKYC_CARD_LIMIT') && !retriedExpiredRef.current) {
      retriedExpiredRef.current = true;
      if (await startFresh()) upload();
      return;
    }
    if (goResultForCode(res.code)) {
      resultHaptic('error');
      return;
    }
    resultHaptic('error');
    if (res.code === 'EKYC_BAD_IMAGE') {
      setErrorText(res.message);
      setPhase('retake');
      return;
    }
    setErrorText(res.message);
    setPhase('error');
  }, [mountedRef, startFresh, startRetryCountdown]);

  // ส่งรูปเมื่อรูปนี้ยังไม่เคยส่งเข้ารอบปัจจุบัน
  useEffect(() => {
    if (!focused || !card || !session) return;
    if (card.result && card.sessionId === session.session_id) {
      setPhase(card.result.status === 'ok' ? 'ready' : 'retake');
      return;
    }
    upload();
  }, [focused, card, session, upload]);

  const retake = () => {
    if (savingRef.current) return;
    if (router.canGoBack()) router.back();
    else router.replace('/ekyc/capture' as never);
  };

  const result = card?.result ?? null;
  const fields = result?.fields;
  const nameShown = corrections.name_th ?? fields?.name_th ?? null;
  const birthShown = corrections.birth_date ?? fields?.birth_date ?? null;
  const edited = !!(corrections.name_th || corrections.birth_date);

  const openEdit = () => {
    setNameDraft(nameShown ?? '');
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(birthShown ?? '');
    setDayDraft(m ? String(Number(m[3])) : '');
    setMonthDraft(m ? String(Number(m[2])) : '');
    setYearDraft(m ? String(Number(m[1]) + 543) : '');
    setEditErrors({});
    setEditOpen(true);
  };

  const saveEdit = () => {
    const errs: { name?: string; birth?: string } = {};
    const name = cleanThaiName(nameDraft);
    if (!name) errs.name = 'ใส่ชื่อ-นามสกุลภาษาไทยตามบัตร';
    const birth = parseThaiBirthDate(dayDraft, monthDraft, yearDraft);
    if (!birth) errs.birth = 'วันเกิดไม่ถูกต้อง ใส่ วัน / เดือน (1–12) / ปี พ.ศ.';
    if (errs.name || errs.birth) {
      setEditErrors(errs);
      resultHaptic('warning');
      return;
    }
    const next = { ...corrections };
    if (name && name !== fields?.name_th) next.name_th = name;
    else delete next.name_th;
    if (birth && birth !== fields?.birth_date) next.birth_date = birth;
    else delete next.birth_date;
    useEkycStore.getState().setCorrections(next);
    setEditOpen(false);
  };

  const proceed = async () => {
    if (savingRef.current || phase !== 'ready') return;
    if (!nameShown || !birthShown) {
      resultHaptic('warning');
      // ตัวอ่านบัตรไม่พร้อม → เปิดช่องกรอกเองเลย
      if (result && !result.ai_available) {
        openEdit();
        return;
      }
      Alert.alert('ข้อมูลยังไม่ครบ', 'AI อ่านชื่อหรือวันเกิดไม่ได้ กด "แก้ได้ถ้าผิด" แล้วกรอกให้ตรงกับบัตร หรือถ่ายบัตรใหม่ให้ชัดขึ้น');
      return;
    }
    const sid = useEkycStore.getState().session?.session_id;
    if (!sid) return;
    if (edited) {
      savingRef.current = true;
      setSaving(true);
      const res = await correctEkycIdCard(sid, corrections);
      savingRef.current = false;
      if (!mountedRef.current) return;
      setSaving(false);
      if (!res.success) {
        // รอบหมดเวลาระหว่างตรวจข้อมูล → เริ่มรอบใหม่ (ส่งรูปบัตรเดิมให้เอง ข้อมูลที่แก้ยังอยู่) แล้วให้กดต่ออีกครั้ง
        if (res.code === 'EKYC_SESSION_EXPIRED') {
          resultHaptic('warning');
          if (await startFresh()) {
            Alert.alert(
              'เริ่มรอบใหม่ให้แล้ว',
              'รอบเดิมหมดเวลา ระบบส่งรูปบัตรเข้ารอบใหม่ให้แล้ว ตรวจข้อมูลอีกครั้งแล้วกด "ถูกต้อง ไปขั้นถัดไป" ได้เลย'
            );
          }
          return;
        }
        if (goResultForCode(res.code)) return;
        resultHaptic('error');
        Alert.alert('บันทึกการแก้ไขไม่สำเร็จ', res.message);
        return;
      }
    }
    resultHaptic('success');
    router.push('/ekyc/face' as never);
  };

  // ---------- ส่วนแสดงผล ----------
  const ocrPct = result ? Math.round(result.ocr_confidence * 100) : 0;
  const aiRead = result ? result.ai_available : true;
  const reasons = (result?.reason_items ?? []).filter((r) => r.code !== 'EXPIRY_LIFELONG');
  const lifelong = (result?.reasons ?? []).includes('EXPIRY_LIFELONG');
  const cardReal = result?.checks.card_real ?? null;

  const bottom =
    phase === 'ready' ? (
      <View style={styles.row}>
        <Button3D title="ถ่ายใหม่" variant="secondary" size="lg" onPress={retake} disabled={saving} />
        <Button3D title="ถูกต้อง ไปขั้นถัดไป" size="lg" onPress={proceed} loading={saving} style={styles.flex} />
      </View>
    ) : phase === 'retake' ? (
      <Button3D title="ถ่ายบัตรใหม่" icon="camera" size="lg" fullWidth onPress={retake} />
    ) : phase === 'busy' ? (
      <View style={styles.row}>
        <Button3D title="ถ่ายใหม่" variant="secondary" size="lg" onPress={retake} />
        <Button3D
          title={retryLeft > 0 ? `ลองใหม่ได้ใน ${retryLeft} วินาที` : 'ลองส่งอีกครั้ง'}
          icon="arrows-clockwise"
          size="lg"
          disabled={retryLeft > 0}
          onPress={upload}
          style={styles.flex}
        />
      </View>
    ) : phase === 'error' ? (
      <View style={styles.row}>
        <Button3D title="ถ่ายใหม่" variant="secondary" size="lg" onPress={retake} />
        <Button3D title="ลองส่งอีกครั้ง" icon="arrows-clockwise" size="lg" onPress={upload} style={styles.flex} />
      </View>
    ) : (
      <Button3D title="AI กำลังอ่านบัตร…" size="lg" fullWidth loading disabled />
    );

  const noteColor = (note: CheckNote): string =>
    note.state === 'ok' ? colors.success : note.state === 'bad' ? colors.danger : colors.textMuted;

  const valueRow = (
    label: string,
    value: string | null,
    note: CheckNote | null,
    onEdit: (() => void) | null,
    first: boolean = false
  ) => (
    <View style={[styles.field, !first && { borderTopWidth: 1, borderTopColor: colors.divider }]}>
      <View style={styles.flex}>
        <Text style={[typography.caption, { color: colors.textMuted }]}>{label}</Text>
        <Text style={[typography.h2, { color: value ? colors.textStrong : colors.textFaint }]}>
          {value ?? (onEdit && !aiRead ? 'ยังไม่ได้กรอก' : 'อ่านไม่ได้')}
        </Text>
        {note && (
          <View style={styles.noteRow}>
            <Icon
              name={note.state === 'ok' ? 'check' : note.state === 'bad' ? 'warning-circle' : 'question'}
              size={14}
              color={noteColor(note)}
              weight="bold"
            />
            <Text style={[typography.caption, styles.flex, { color: noteColor(note) }]}>{note.text}</Text>
          </View>
        )}
      </View>
      {onEdit && (
        <Pressable
          onPress={onEdit}
          accessibilityRole="button"
          accessibilityLabel={`แก้${label}`}
          style={({ pressed }) => [styles.editBtn, { borderColor: colors.border, backgroundColor: colors.card, opacity: pressed ? 0.7 : 1 }]}
        >
          <Icon name="pencil-simple" size={18} color={colors.textStrong} />
        </Pressable>
      )}
    </View>
  );

  return (
    <>
      <EkycShell title="ตรวจข้อมูลบัตร" subtitle="ขั้นที่ 2 จาก 4" step={2} onBack={retake} bottom={bottom}>
        <Card3D padding={spacing.lg} radius={22}>
          <View style={[styles.photoBox, { aspectRatio: aspect, backgroundColor: colors.inset }]}>
            {!!card?.uri && (
              <Image
                source={{ uri: card.uri }}
                style={StyleSheet.absoluteFill}
                contentFit="cover"
                cachePolicy="none"
                onLoad={(e) => {
                  const w = e.source?.width ?? 0;
                  const h = e.source?.height ?? 0;
                  // ภาพจากตัวสแกน (ตัดขอบแล้ว) แสดงเต็ม · ภาพจากกล้องแนวตั้ง ครอบกลางภาพเป็นรูปทรงบัตร (กรอบบัตรอยู่กลางภาพ)
                  if (w > 0 && h > 0 && mountedRef.current) setAspect(Math.max(1.3, Math.min(1.75, w / h)));
                }}
                accessibilityLabel="รูปบัตรประชาชนที่ถ่าย"
              />
            )}
            {phase === 'uploading' && (
              <View style={[StyleSheet.absoluteFill, styles.center, { backgroundColor: colors.overlay }]}>
                <ActivityIndicator size="large" color={colors.goldLight} />
                <Text style={[typography.bodyStrong, { color: colors.textOnAccent }]}>AI กำลังอ่านบัตร…</Text>
              </View>
            )}
          </View>
          {phase === 'ready' && result && (
            <View style={styles.pills}>
              {aiRead && <Pill label={`อ่านบัตรสำเร็จ ${ocrPct}%`} icon="check" tone="success" />}
              <Pill
                label={
                  cardReal === true
                    ? 'บัตรของจริง ไม่ใช่ภาพจอ'
                    : cardReal === false
                      ? 'ตรวจความเป็นบัตรจริงไม่ผ่าน'
                      : 'เจ้าหน้าที่จะตรวจความเป็นบัตรจริงให้'
                }
                icon="shield-check"
                tone={cardReal === true ? 'success' : cardReal === false ? 'warning' : 'neutral'}
              />
            </View>
          )}
        </Card3D>

        {phase === 'retake' && (
          <Card3D padding={spacing.lg} radius={22}>
            <View style={styles.titleRow}>
              <View style={[styles.warnIcon, { backgroundColor: colors.warningSoft }]}>
                <Icon name="warning" size={20} color={colors.warning} />
              </View>
              <Text style={[typography.h2, styles.flex, { color: colors.textStrong }]}>ถ่ายบัตรใหม่อีกครั้งนะ</Text>
            </View>
            {(reasons.length > 0
              ? reasons
              : [{ code: null, text: errorText || 'รูปบัตรยังใช้ตรวจไม่ได้', tone: 'fail' as const }]
            ).map((item) => (
              <View key={item.text} style={styles.reasonRow}>
                <Icon
                  name={item.tone === 'fail' ? 'x-circle' : 'warning-circle'}
                  size={16}
                  color={item.tone === 'fail' ? colors.danger : colors.warning}
                />
                <Text style={[typography.body, styles.flex, { color: colors.text }]}>{item.text}</Text>
              </View>
            ))}
            <Text style={[typography.caption, styles.tip, { color: colors.textMuted }]}>
              วางบัตรบนพื้นเรียบสีเข้ม ในที่สว่างแต่ไม่มีแสงสะท้อน ให้เห็นบัตรครบทั้งใบ
            </Text>
          </Card3D>
        )}

        {phase === 'busy' && (
          <Card3D padding={spacing.lg} radius={22}>
            <View style={styles.titleRow}>
              <View style={[styles.warnIcon, { backgroundColor: colors.infoSoft }]}>
                <Icon name="hourglass" size={20} color={colors.info} />
              </View>
              <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>ระบบตรวจมีคนใช้เยอะ ลองใหม่ในอีกสักครู่</Text>
            </View>
            <Text style={[typography.caption, styles.tip, { color: colors.textMuted }]}>
              รูปบัตรยังอยู่ในเครื่อง ไม่ต้องถ่ายใหม่ ครั้งนี้ไม่นับเป็นครั้งที่ยืนยันไม่ผ่าน
            </Text>
          </Card3D>
        )}

        {phase === 'error' && (
          <Card3D padding={spacing.lg} radius={22}>
            <View style={styles.titleRow}>
              <View style={[styles.warnIcon, { backgroundColor: colors.dangerSoft }]}>
                <Icon name="cloud-slash" size={20} color={colors.danger} />
              </View>
              <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>{errorText || 'ส่งรูปบัตรไม่สำเร็จ'}</Text>
            </View>
          </Card3D>
        )}

        {phase === 'ready' && result && !aiRead && (
          <InfoNote icon="info">ระบบอ่านบัตรอัตโนมัติไม่พร้อมชั่วคราว กรอกชื่อและวันเกิดเองได้ เจ้าหน้าที่จะตรวจให้</InfoNote>
        )}

        {phase === 'ready' && result && (
          <Card3D padding={spacing.lg} radius={22}>
            <View style={styles.titleRow}>
              <Text style={[typography.h2, styles.flex, { color: colors.textStrong }]}>
                {aiRead ? 'ข้อมูลที่ AI อ่านได้' : 'ข้อมูลบนบัตร'}
              </Text>
              <Pressable
                onPress={openEdit}
                accessibilityRole="button"
                accessibilityLabel={aiRead ? 'แก้ข้อมูลที่อ่านผิด' : 'กรอกข้อมูลตามบัตรเอง'}
                style={({ pressed }) => [styles.chipBtn, { backgroundColor: colors.navySoft, opacity: pressed ? 0.7 : 1 }]}
              >
                <Icon name="pencil-simple" size={14} color={colors.textStrong} />
                <Text style={[typography.micro, { color: colors.textStrong }]}>{aiRead ? 'แก้ได้ถ้าผิด' : 'กรอกเอง'}</Text>
              </Pressable>
            </View>
            {valueRow(
              'เลขบัตรประชาชน',
              fields?.id_number_masked ?? null,
              triNote(result.checks.checksum, 'เลข 13 หลักถูกต้องตามหลักตรวจสอบ', 'เลขบัตรไม่ผ่านหลักตรวจสอบ', 'ยังไม่ทราบ เจ้าหน้าที่จะตรวจให้'),
              null,
              true
            )}
            {valueRow('ชื่อ-นามสกุล', nameShown, null, openEdit)}
            {valueRow('วันเกิด', formatThaiDate(birthShown), null, openEdit)}
            {valueRow(
              'วันบัตรหมดอายุ',
              lifelong && !fields?.expiry_date ? 'ตลอดชีพ' : formatThaiDate(fields?.expiry_date),
              triNote(result.checks.not_expired, 'บัตรยังไม่หมดอายุ', 'บัตรหมดอายุแล้ว', 'ยังไม่ทราบวันหมดอายุ เจ้าหน้าที่จะตรวจให้'),
              null
            )}
            {edited && (
              <View style={[styles.editedNote, { backgroundColor: colors.goldSoft }]}>
                <Icon name="info" size={16} color={colors.goldDeep} />
                <Text style={[typography.caption, styles.flex, { color: colors.text }]}>
                  คุณแก้ข้อมูลเองแล้ว เจ้าหน้าที่จะตรวจทานอีกครั้ง ผลอาจไม่อนุมัติทันที
                </Text>
              </View>
            )}
            <Pressable
              onPress={retake}
              disabled={saving}
              accessibilityRole="button"
              accessibilityLabel="อ่านบัตรอีกครั้ง"
              accessibilityHint="กลับไปถ่ายบัตรใหม่ให้ AI อ่านอีกครั้ง"
              style={({ pressed }) => [styles.rescan, { borderTopColor: colors.divider, opacity: pressed || saving ? 0.6 : 1 }]}
            >
              <Icon name="scan" size={18} color={colors.goldDeep} />
              <Text style={[typography.bodySm, styles.flex, { color: colors.textMuted }]}>ข้อมูลไม่ตรงกับบัตร หรืออ่านไม่ได้?</Text>
              <Text style={[typography.bodyStrong, { color: colors.goldDeep }]}>อ่านบัตรอีกครั้ง</Text>
            </Pressable>
          </Card3D>
        )}

        <InfoNote icon="info">ชื่อในบัตรต้องตรงกับชื่อบัญชีรับเงิน ระบบจะใช้ชื่อนี้เมื่อคุณถอนเงินหรือรับค่าส่ง</InfoNote>
      </EkycShell>

      <FormSheet
        visible={editOpen}
        title="แก้ข้อมูลให้ตรงกับบัตร"
        description="แก้เฉพาะที่ AI อ่านผิด ข้อมูลที่แก้เองเจ้าหน้าที่จะตรวจทานอีกครั้ง"
        icon="pencil-simple"
        submitLabel="บันทึก"
        onSubmit={saveEdit}
        cancelLabel="ยกเลิก"
        onClose={() => setEditOpen(false)}
      >
        <Field
          label="ชื่อ-นามสกุล (ภาษาไทย)"
          value={nameDraft}
          onChangeText={(v) => {
            setNameDraft(v);
            if (editErrors.name) setEditErrors((e) => ({ ...e, name: undefined }));
          }}
          placeholder="เช่น นาย ณัฐ ใจงาม"
          maxLength={100}
          error={editErrors.name}
        />
        <Text style={[typography.caption, styles.birthLabel, { color: colors.textMuted }]}>วันเกิด (ปี พ.ศ.)</Text>
        <View style={styles.row}>
          <Field label="วัน" value={dayDraft} onChangeText={setDayDraft} keyboardType="number-pad" maxLength={2} placeholder="12" containerStyle={styles.flex} />
          <Field label="เดือน" value={monthDraft} onChangeText={setMonthDraft} keyboardType="number-pad" maxLength={2} placeholder="1" containerStyle={styles.flex} />
          <Field label="ปี พ.ศ." value={yearDraft} onChangeText={setYearDraft} keyboardType="number-pad" maxLength={4} placeholder="2538" containerStyle={styles.flex} />
        </View>
        {!!editErrors.birth && <Text style={[typography.caption, { color: colors.danger }]}>{editErrors.birth}</Text>}
      </FormSheet>
    </>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
  },
  center: {
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
  },
  photoBox: {
    width: '100%',
    borderRadius: 16,
    overflow: 'hidden',
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginBottom: spacing.xs,
  },
  chipBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    borderRadius: radii.pill,
    paddingHorizontal: 10,
    paddingVertical: 5,
  },
  field: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.md,
  },
  noteRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 2,
  },
  editBtn: {
    width: 44,
    height: 44,
    borderRadius: 14,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  warnIcon: {
    width: 40,
    height: 40,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  reasonRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingVertical: 6,
  },
  tip: {
    marginTop: spacing.sm,
  },
  editedNote: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.md,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
  birthLabel: {
    marginTop: spacing.md,
  },
  rescan: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderTopWidth: 1,
    marginTop: spacing.md,
    paddingTop: spacing.md,
    minHeight: 44,
  },
});
