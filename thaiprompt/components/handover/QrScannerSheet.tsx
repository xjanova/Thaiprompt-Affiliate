/**
 * QrScannerSheet — แผ่นสแกน QR เต็มจอ (expo-camera CameraView) + ทางสำรองกรอกรหัส 6 หลัก
 *
 * - ขอสิทธิ์กล้องเมื่อเปิดแผ่นเท่านั้น · ปฏิเสธถาวร → ปุ่มเปิดการตั้งค่า · กล้องใช้ไม่ได้ → กรอกรหัสแทน (ถ้ามี codeFallback)
 * - สิทธิ์กล้องอ่านใหม่ทุกครั้งที่เปิดแผ่น และเมื่อกลับเข้าแอป (ไปเปิดสิทธิ์ในตั้งค่าเครื่องแล้วกลับมา) — U1
 * - กันสแกนซ้ำ: อ่านได้ครั้งแรกแล้วล็อก 2.5 วินาที และไม่ส่งค่าเดิมซ้ำในการเปิดแผ่นรอบเดียวกัน
 *   หน้าแม่ส่งไม่สำเร็จ → เปลี่ยน scanResetKey ให้สแกนค่าเดิมได้อีก (U2) · รหัสที่กรอกส่งซ้ำได้หลังรอบก่อนจบ
 * - busy = หน้าแม่กำลังส่งผลสแกนไป server → ม่านหมุน + หยุดอ่าน QR
 * - ปิดแผ่น = ถอดกล้องทิ้งทันที (ไม่ถือกล้องค้าง)
 * - พื้นกล้องมืดเสมอทั้งสองโหมด (ใช้ชุดสีโหมดมืดของธีม)
 *
 * @example
 * <QrScannerSheet
 *   visible={open}
 *   title="สแกน QR ของไรเดอร์"
 *   onClose={() => setOpen(false)}
 *   onScanned={(data) => submitScan(data)}
 *   codeFallback={{ length: 6, onSubmit: submitCode, label: 'กล้องใช้ไม่ได้? กรอกรหัสของไรเดอร์' }}
 * />
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, AppState, Linking, Modal, Pressable, StyleSheet, View } from 'react-native';
import { CameraView, useCameraPermissions, type BarcodeScanningResult } from 'expo-camera';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text, TextInput } from '@/components/ui/Text';
import { Button3D, GlassIconButton, Icon, resultHaptic, selectionHaptic } from '@/components/ui';
import { DARK_THEME, radii, spacing, typography, withAlpha } from '@/theme';

export interface QrScannerSheetProps {
  visible: boolean;
  title: string;
  onClose: () => void;
  onScanned: (data: string) => void;
  /**
   * ทางสำรองเมื่อกล้องใช้ไม่ได้: กรอกรหัสตัวเลข
   * label = ข้อความปุ่มเปลี่ยนไปกรอกรหัส · hint = คำอธิบายในหน้ากรอกรหัส
   */
  codeFallback?: { length: 6; onSubmit: (code: string) => void; label?: string; hint?: string };
  /** หน้าแม่กำลังส่งผลไป server (หยุดอ่าน + แสดงม่านหมุน) */
  busy?: boolean;
  /** ข้อความใต้กรอบสแกน */
  hint?: string;
  /** เปิดแผ่นมาที่หน้ากรอกรหัสเลย (ผู้ใช้กด "กล้องใช้ไม่ได้?" จากหน้าแม่) */
  initialMode?: 'camera' | 'code';
  /** ข้อความผิดพลาดของรหัสที่กรอก (แสดงใต้ช่องรหัส ไม่ต้องปิดแผ่น) */
  codeError?: string | null;
  /** เปลี่ยนค่านี้ = ล้างตัวกันสแกนซ้ำ (หน้าแม่ส่งไม่สำเร็จ ให้สแกน QR เดิมได้อีก) */
  scanResetKey?: number;
}

const SCAN_LOCK_MS = 2500;
const MAX_DATA_LENGTH = 1024;

export const QrScannerSheet: React.FC<QrScannerSheetProps> = ({
  visible,
  title,
  onClose,
  onScanned,
  codeFallback,
  busy = false,
  hint = 'วาง QR ให้อยู่ในกรอบ ระบบจะอ่านให้อัตโนมัติ',
  initialMode = 'camera',
  codeError = null,
  scanResetKey,
}) => {
  // หน้าจอกล้องมืดเสมอ — ใช้สีชุดโหมดมืดตรงๆ
  const c = DARK_THEME.colors;
  const insets = useSafeAreaInsets();
  const [permission, requestPermission, getPermission] = useCameraPermissions();
  const [mode, setMode] = useState<'camera' | 'code'>('camera');
  const [torch, setTorch] = useState(false);
  const [code, setCode] = useState('');
  const [cameraError, setCameraError] = useState(false);
  const lockedUntilRef = useRef(0);
  const seenRef = useRef<Set<string>>(new Set());
  const codeSubmittedRef = useRef<string | null>(null);
  const askedRef = useRef(false);
  const mountedRef = useRef(true);
  const initialModeRef = useRef(initialMode);
  initialModeRef.current = initialMode;
  const hasCodeFallback = !!codeFallback;

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // เปิดแผ่นใหม่ทุกครั้ง = เริ่มสะอาด
  useEffect(() => {
    if (!visible) return;
    setMode(initialModeRef.current === 'code' && hasCodeFallback ? 'code' : 'camera');
    setTorch(false);
    setCode('');
    setCameraError(false);
    lockedUntilRef.current = 0;
    seenRef.current = new Set();
    codeSubmittedRef.current = null;
    askedRef.current = false;
  }, [visible, hasCodeFallback]);

  // อ่านสิทธิ์กล้องใหม่: ทุกครั้งที่เปิดแผ่น + กลับเข้าแอประหว่างเปิดแผ่น (เช่น ไปเปิดสิทธิ์ในตั้งค่าเครื่องมา) — U1
  useEffect(() => {
    if (!visible) return undefined;
    getPermission().catch(() => {});
    const sub = AppState.addEventListener('change', (state) => {
      if (state === 'active' && mountedRef.current) {
        getPermission().catch(() => {});
        // กล้องเปิดไม่ขึ้นรอบก่อน → ลองใหม่เมื่อกลับเข้าแอป
        setCameraError(false);
      }
    });
    return () => sub.remove();
  }, [visible, getPermission]);

  // หน้าแม่ส่งไม่สำเร็จ → ให้สแกน QR เดิม/ส่งรหัสเดิมได้อีก (U2) — หน่วงสั้นๆ กันยิงซ้ำทันทีที่กล้องยังเห็น QR เดิม
  useEffect(() => {
    if (scanResetKey === undefined) return;
    seenRef.current = new Set();
    codeSubmittedRef.current = null;
    lockedUntilRef.current = Date.now() + 1200;
  }, [scanResetKey]);

  // ส่งรหัสรอบก่อนจบแล้ว (สำเร็จหรือไม่ก็ตาม) → กดยืนยันรหัสเดิมซ้ำได้ (เช่น เน็ตหลุด)
  const prevBusyRef = useRef(busy);
  useEffect(() => {
    if (prevBusyRef.current && !busy) codeSubmittedRef.current = null;
    prevBusyRef.current = busy;
  }, [busy]);

  // ขอสิทธิ์กล้องอัตโนมัติครั้งแรกที่เปิด (ระบบยังถามได้)
  useEffect(() => {
    if (!visible || !permission || permission.granted || askedRef.current) return;
    if (permission.canAskAgain) {
      askedRef.current = true;
      requestPermission().catch(() => {});
    }
  }, [visible, permission, requestPermission]);

  const handleBarcode = useCallback(
    (result: BarcodeScanningResult) => {
      if (busy || mode !== 'camera') return;
      const now = Date.now();
      if (now < lockedUntilRef.current) return;
      const data = typeof result?.data === 'string' ? result.data.trim() : '';
      if (!data || data.length > MAX_DATA_LENGTH) return;
      if (seenRef.current.has(data)) return;
      seenRef.current.add(data);
      lockedUntilRef.current = now + SCAN_LOCK_MS;
      resultHaptic('success');
      onScanned(data);
    },
    [busy, mode, onScanned]
  );

  // ข้อความผิดพลาดของรหัส: แสดงเมื่อหน้าแม่ส่งมาใหม่ · ซ่อนเมื่อผู้ใช้เริ่มแก้รหัส
  const [codeErrorHidden, setCodeErrorHidden] = useState(false);
  useEffect(() => {
    setCodeErrorHidden(false);
  }, [codeError]);

  const onCodeChange = (value: string) => {
    const digits = value.replace(/\D/g, '').slice(0, codeFallback?.length ?? 6);
    if (digits !== code) setCodeErrorHidden(true);
    setCode(digits);
    if (codeSubmittedRef.current && digits !== codeSubmittedRef.current) codeSubmittedRef.current = null;
  };

  const submitCode = () => {
    if (!codeFallback || busy) return;
    if (code.length !== codeFallback.length) {
      resultHaptic('warning');
      return;
    }
    // กันกดส่งรหัสเดิมซ้ำระหว่างรอ
    if (codeSubmittedRef.current === code) return;
    codeSubmittedRef.current = code;
    selectionHaptic();
    codeFallback.onSubmit(code);
  };

  const granted = !!permission?.granted;
  const blocked = !!permission && !permission.granted && !permission.canAskAgain;
  const codeLength = codeFallback?.length ?? 6;

  const renderPermission = () => (
    <View style={styles.permission}>
      <View style={[styles.permIcon, { backgroundColor: c.goldSoft }]}>
        <Icon name="camera" size={34} color={c.gold} weight="fill" />
      </View>
      <Text style={[typography.h2, styles.center, { color: c.textStrong }]}>ขอใช้กล้องเพื่อสแกน QR</Text>
      <Text style={[typography.bodySm, styles.center, { color: c.textMuted }]}>
        ใช้กล้องเฉพาะตอนเปิดหน้านี้ เพื่ออ่าน QR ยืนยันการรับ-ส่งของ ไม่บันทึกภาพ
      </Text>
      {blocked ? (
        <Button3D title="เปิดการตั้งค่า" icon="gear-six" size="lg" fullWidth onPress={() => Linking.openSettings().catch(() => {})} />
      ) : (
        <Button3D title="อนุญาตใช้กล้อง" icon="camera" size="lg" fullWidth onPress={() => requestPermission().catch(() => {})} />
      )}
      {!!codeFallback && (
        <Button3D title={`กรอกรหัส ${codeLength} หลักแทน`} icon="key" variant="ghost" size="md" fullWidth onPress={() => setMode('code')} />
      )}
    </View>
  );

  const renderCode = () => (
    <View style={styles.codeWrap}>
      <Text style={[typography.h2, styles.center, { color: c.textStrong }]}>กรอกรหัส {codeLength} หลัก</Text>
      <Text style={[typography.bodySm, styles.center, { color: c.textMuted }]}>
        {codeFallback?.hint || 'ขอรหัสจากหน้าจอของอีกฝ่าย แล้วพิมพ์ให้ครบ'}
      </Text>
      <Pressable accessibilityRole="none" style={styles.codeBoxes}>
        {Array.from({ length: codeLength }).map((_, i) => {
          const ch = code[i] ?? '';
          const active = i === Math.min(code.length, codeLength - 1);
          return (
            <View
              key={i}
              style={[
                styles.codeBox,
                { backgroundColor: c.inset, borderColor: active ? c.gold : c.border },
                i === 2 && styles.codeGap,
              ]}
            >
              <Text style={[styles.codeDigit, { color: c.textStrong }]}>{ch}</Text>
            </View>
          );
        })}
        {/* ช่องพิมพ์จริงซ้อนทับกล่องตัวเลข (โปร่งใส) */}
        <TextInput
          value={code}
          onChangeText={onCodeChange}
          keyboardType="number-pad"
          maxLength={codeLength}
          autoFocus
          textContentType="oneTimeCode"
          autoComplete="off"
          importantForAutofill="no"
          caretHidden
          onSubmitEditing={submitCode}
          accessibilityLabel={`รหัส ${codeLength} หลัก`}
          style={[StyleSheet.absoluteFill, styles.hiddenInput]}
        />
      </Pressable>
      {!!codeError && !codeErrorHidden && (
        <View style={styles.codeErrorRow} accessibilityLiveRegion="polite">
          <Icon name="warning-circle" size={16} color={c.danger} />
          <Text style={[typography.bodySm, styles.flexShrink, { color: c.danger }]}>{codeError}</Text>
        </View>
      )}
      <Button3D
        title="ยืนยันรหัส"
        icon="check-circle"
        size="lg"
        fullWidth
        disabled={code.length !== codeLength}
        loading={busy}
        onPress={submitCode}
      />
      <Button3D title="กลับไปสแกน QR" icon="scan" variant="ghost" size="md" fullWidth disabled={busy} onPress={() => setMode('camera')} />
    </View>
  );

  return (
    <Modal visible={visible} animationType="slide" onRequestClose={onClose} statusBarTranslucent>
      <View style={[styles.root, { backgroundColor: c.background }]}>
        <View style={[styles.header, { paddingTop: insets.top + spacing.md }]}>
          <GlassIconButton icon="x" weight="bold" accessibilityLabel="ปิด" onPress={onClose} />
          <Text numberOfLines={1} style={[typography.h2, styles.flex, { color: c.textStrong }]}>
            {title}
          </Text>
          {mode === 'camera' && granted && !cameraError ? (
            <GlassIconButton
              icon="lightning"
              weight={torch ? 'fill' : 'regular'}
              accessibilityLabel={torch ? 'ปิดไฟฉาย' : 'เปิดไฟฉาย'}
              onPress={() => setTorch((t) => !t)}
            />
          ) : (
            <View style={styles.headerSpacer} />
          )}
        </View>

        {mode === 'code' && codeFallback ? (
          renderCode()
        ) : !permission ? (
          <View style={styles.loading}>
            <ActivityIndicator color={c.gold} />
          </View>
        ) : !granted || cameraError ? (
          cameraError ? (
            <View style={styles.permission}>
              <Icon name="warning-circle" size={40} color={c.warning} />
              <Text style={[typography.h2, styles.center, { color: c.textStrong }]}>เปิดกล้องไม่ได้</Text>
              <Text style={[typography.bodySm, styles.center, { color: c.textMuted }]}>
                กล้องของเครื่องนี้ใช้งานไม่ได้ตอนนี้{codeFallback ? ' กรอกรหัสแทนได้เลย' : ''}
              </Text>
              {!!codeFallback && (
                <Button3D title={`กรอกรหัส ${codeLength} หลัก`} icon="key" size="lg" fullWidth onPress={() => setMode('code')} />
              )}
            </View>
          ) : (
            renderPermission()
          )
        ) : (
          <>
            {visible && (
              <CameraView
                style={styles.camera}
                facing="back"
                enableTorch={torch}
                barcodeScannerSettings={{ barcodeTypes: ['qr'] }}
                onBarcodeScanned={busy ? undefined : handleBarcode}
                onMountError={() => setCameraError(true)}
              />
            )}
            <View pointerEvents="none" style={[StyleSheet.absoluteFill, styles.overlayCenter]}>
              <View style={[styles.scanFrame, { borderColor: c.gold }]} />
            </View>
            {busy && (
              <View style={[StyleSheet.absoluteFill, styles.overlayCenter, { backgroundColor: withAlpha(c.background, 0.7) }]}>
                <ActivityIndicator size="large" color={c.gold} />
                <Text style={[typography.bodyStrong, { color: c.textStrong }]}>กำลังตรวจสอบ…</Text>
              </View>
            )}
            <View style={[styles.footer, { paddingBottom: insets.bottom + spacing.xl }]}>
              <Text style={[typography.body, styles.center, { color: c.text }]}>{hint}</Text>
              {!!codeFallback && (
                <Button3D
                  title={codeFallback.label || `กล้องใช้ไม่ได้? กรอกรหัส ${codeLength} หลัก`}
                  icon="key"
                  variant="secondary"
                  size="md"
                  disabled={busy}
                  onPress={() => setMode('code')}
                />
              )}
            </View>
          </>
        )}
      </View>
    </Modal>
  );
};

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
  flex: {
    flex: 1,
  },
  flexShrink: {
    flexShrink: 1,
  },
  center: {
    textAlign: 'center',
  },
  codeErrorRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.xs,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.md,
    zIndex: 2,
  },
  headerSpacer: {
    width: 44,
  },
  loading: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  camera: {
    flex: 1,
  },
  overlayCenter: {
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.md,
  },
  scanFrame: {
    width: 250,
    height: 250,
    borderWidth: 3,
    borderRadius: radii.xl,
  },
  footer: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.lg,
  },
  permission: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.xxl,
  },
  permIcon: {
    width: 76,
    height: 76,
    borderRadius: 38,
    alignItems: 'center',
    justifyContent: 'center',
  },
  codeWrap: {
    flex: 1,
    paddingHorizontal: spacing.xxl,
    paddingTop: spacing.xxl,
    gap: spacing.lg,
  },
  codeBoxes: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: spacing.sm,
    marginVertical: spacing.md,
  },
  codeBox: {
    width: 44,
    height: 56,
    borderRadius: radii.md,
    borderWidth: 1.5,
    alignItems: 'center',
    justifyContent: 'center',
  },
  codeGap: {
    marginRight: spacing.md,
  },
  codeDigit: {
    fontSize: 26,
    lineHeight: 34,
    fontWeight: '700',
    fontVariant: ['tabular-nums'],
  },
  hiddenInput: {
    opacity: 0.02,
    color: 'transparent',
  },
});

export default QrScannerSheet;
