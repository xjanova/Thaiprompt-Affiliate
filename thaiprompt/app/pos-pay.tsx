/**
 * จ่ายคำขอจากร้าน (POS → ส่งด้วยไรเดอร์ Thai Prompt) — /pos-pay?token=...
 *
 * เข้าหน้านี้ได้จาก
 *   - สแกน QR `TPPOS1.{token}` บนจอร้าน (ตัวสแกนในหน้านี้เมื่อไม่มี token · ตัวสแกนหน้าโอนเงิน)
 *   - แตะ push `pos_payment_request` {token} · deep link /pos-pay?token=   (token มีหรือไม่มี prefix ก็ได้)
 *
 * ขั้นตอน: ดูรายการ + ราคาจาก server → เลือกที่อยู่ที่ปักหมุด (คิดค่าส่งไรเดอร์ใหม่ทุกครั้งที่เปลี่ยน)
 *          → ใส่ PIN กระเป๋าเงิน → POST /pos-requests/{token}/pay → ไปหน้าติดตามคำสั่งซื้อ (track_path)
 *
 * กติกา
 *   - จ่ายด้วยกระเป๋าเงินเท่านั้น (ไม่มีเก็บเงินปลายทาง) · ยอดทุกตัวมาจาก server — แอปไม่คำนวณเอง
 *   - ปุ่มจ่ายเปิดเมื่อ: ที่อยู่ที่ปักหมุดแล้ว + ไรเดอร์ส่งได้ + ยอดเงินพอ + ใบเสนอราคาเป็นของที่อยู่ที่เลือกอยู่จริง
 *   - Idempotency-Key หนึ่งตัวต่อการเปิดหน้า (ต่อ token) — กดซ้ำ/เน็ตหลุดแล้วลองใหม่ = ไม่ตัดเงินซ้ำ
 *   - เน็ตหลุดตอนจ่าย → ถามสถานะคำขอก่อน ถ้าจ่ายไปแล้วพาไปหน้าคำสั่งซื้อเลย
 *   - ระหว่างรอผลจ่ายเงิน ปิดแผ่น PIN / ออกจากหน้าไม่ได้ · ถูกถอดระหว่างรอ = ไม่ setState
 *   - QR หมดอายุ / ถูกยกเลิก / ไม่พบ / มีคนจ่ายแล้ว → หน้าสถานะพร้อมปุ่มกลับ (จ่ายแล้วโดยเรา = ปุ่มดูคำสั่งซื้อ)
 *
 * หน้าตา (ธีมรอยัล): การ์ดร้านขอบทอง · การ์ดขั้นตอนมีเลขในวงกลม (รายการ → ส่งไปที่ → ชำระด้วย)
 *   · แถบล่างลอยยอดทอง + ปุ่มทอง · แผ่น PIN ใช้ ConsentSheet + Field แบบเดียวกับหน้าถอนเงิน
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, Linking, Modal, StyleSheet, View } from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { router, useFocusEffect, useLocalSearchParams, useNavigation } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text } from '@/components/ui/Text';
import { useAuthStore } from '@/stores/authStore';
import { getWallet } from '@/services/api';
import { getAddresses, type Address } from '@/services/api/shopApi';
import { getPosRequest, payPosRequest, type PosRequestQuote } from '@/services/api/posPayApi';
import { newIdempotencyKey, type ApiFailure } from '@/services/api/client';
import { isAllowedInternalRoute } from '@/utils/linking';
import { isPosQr, parsePosToken } from '@/utils/posQr';
import {
  Button3D,
  Card3D,
  ConsentSheet,
  EmptyState,
  GlassIconButton,
  Icon,
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  formatBaht,
  resultHaptic,
  selectionHaptic,
  type IconName,
} from '@/components/ui';
import {
  Field,
  FormSheet,
  IconTile,
  NoticeBanner,
  RadioMark,
  StepBadge,
  StickyBar,
  StoreLogo,
  ThumbImage,
} from '@/components/shop';
import { useTheme, DARK_THEME, spacing, typography } from '@/theme';

const SCREEN_TITLE = 'ชำระเงินร้านค้า';

// =====================================================
// สถานะจบของคำขอ (ไม่ต้องจ่ายต่อ)
// =====================================================

type TerminalKind = 'not_found' | 'expired' | 'cancelled' | 'paid_other' | 'paid_mine';

interface TerminalState {
  kind: TerminalKind;
  orderId?: number | null;
}

const TERMINAL_COPY: Record<TerminalKind, { icon: IconName; title: string; message: string }> = {
  not_found: {
    icon: 'qr-code',
    title: 'ไม่พบคำขอชำระเงินนี้',
    message: 'QR อาจไม่ถูกต้องหรือถูกยกเลิกไปแล้ว ขอให้ร้านสร้าง QR ใหม่แล้วสแกนอีกครั้งนะ',
  },
  expired: {
    icon: 'hourglass',
    title: 'QR หมดอายุแล้ว',
    message: 'QR ชำระเงินใช้ได้ 15 นาที ยังไม่มีการตัดเงิน ขอให้ร้านสร้าง QR ใหม่แล้วสแกนอีกครั้งนะ',
  },
  cancelled: {
    icon: 'x-circle',
    title: 'ร้านยกเลิกคำขอนี้แล้ว',
    message: 'ยังไม่มีการตัดเงิน ถ้ายังต้องการสั่ง ขอให้ร้านสร้าง QR ใหม่นะ',
  },
  paid_other: {
    icon: 'seal-check',
    title: 'คำขอนี้ชำระเงินไปแล้ว',
    message: 'มีผู้ชำระคำขอนี้แล้ว ถ้าคุณยังไม่ได้จ่าย ติดต่อร้านเพื่อขอ QR ใหม่นะ',
  },
  paid_mine: {
    icon: 'check-circle',
    title: 'คุณชำระเงินคำขอนี้แล้ว',
    message: 'ไรเดอร์จะไปรับของที่ร้านแล้วนำส่งถึงคุณ ติดตามสถานะได้ที่หน้าคำสั่งซื้อ',
  },
};

/** ผลของการขอใบเสนอราคา (stale = มีคำขอใหม่กว่า / หน้าถูกปิดแล้ว) */
type QuoteOutcome =
  | { kind: 'quote'; quote: PosRequestQuote }
  | { kind: 'terminal'; terminal: TerminalState }
  | { kind: 'error' }
  | { kind: 'stale' };

/** สถานะจาก GET ที่ตอบ 200 */
const terminalFromQuote = (quote: PosRequestQuote): TerminalState | null => {
  switch (quote.status) {
    case 'paid':
      return quote.order_id ? { kind: 'paid_mine', orderId: quote.order_id } : { kind: 'paid_other' };
    case 'expired':
      return { kind: 'expired' };
    case 'cancelled':
      return { kind: 'cancelled' };
    default:
      return null;
  }
};

/** สถานะจาก error ของ GET/POST (null = ไม่ใช่สถานะจบ) */
const terminalFromFailure = (res: ApiFailure): TerminalState | null => {
  switch (res.code) {
    case 'REQUEST_NOT_FOUND':
      return { kind: 'not_found' };
    case 'REQUEST_EXPIRED':
      return { kind: 'expired' };
    case 'REQUEST_CANCELLED':
      return { kind: 'cancelled' };
    case 'REQUEST_ALREADY_PAID': {
      const orderId = Number(res.data?.order_id);
      return Number.isInteger(orderId) && orderId > 0 ? { kind: 'paid_mine', orderId } : { kind: 'paid_other' };
    }
    default:
      break;
  }
  if (res.status === 410) return { kind: 'expired' };
  if (res.status === 404) return { kind: 'not_found' };
  return null;
};

// =====================================================
// ตัวช่วย
// =====================================================

const onlyDigits = (text: string, max: number) => text.replace(/\D/g, '').slice(0, max);

/** เวลา HH:MM ของเครื่อง (ค่าไม่ถูกต้อง = null) — ไม่พึ่ง Intl */
const timeText = (iso: unknown): string | null => {
  if (typeof iso !== 'string' || !iso) return null;
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return null;
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
};

/** path หน้าคำสั่งซื้อหลังจ่าย (ใช้ track_path จาก server เมื่อผ่าน allowlist) */
const orderPathFor = (orderId: number | null | undefined, trackPath?: string | null): string => {
  if (isAllowedInternalRoute(trackPath)) return trackPath;
  return orderId ? `/order/${orderId}` : '/orders';
};

const goBackOrHome = () => {
  if (router.canGoBack()) router.back();
  else router.replace('/(tabs)' as never);
};

// =====================================================
// หน้าจอ
// =====================================================

export default function PosPayScreen() {
  const params = useLocalSearchParams<{ token?: string | string[] }>();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const [scannedToken, setScannedToken] = useState<string | null>(null);

  const rawParam = Array.isArray(params.token) ? params.token[0] : params.token;
  const token = scannedToken ?? parsePosToken(rawParam);

  if (!isAuthenticated) {
    return (
      <Screen title={SCREEN_TITLE} scroll={false}>
        <EmptyState
          icon="lock-key"
          title="เข้าสู่ระบบก่อนนะ"
          message="เข้าสู่ระบบ Thai Prompt แล้วสแกน QR ของร้านอีกครั้งเพื่อชำระเงิน"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      </Screen>
    );
  }

  if (!token) {
    return <ScanStart invalidParam={!!rawParam} onToken={setScannedToken} />;
  }

  // key = token → สแกนใบใหม่ได้ state ใหม่ทั้งหมด (รวม Idempotency-Key)
  return <PosPayContent key={token} token={token} />;
}

// =====================================================
// ยังไม่มี token → หน้าเริ่มสแกน
// =====================================================

const StepLine: React.FC<{ n: number; text: string }> = ({ n, text }) => {
  const { colors } = useTheme();
  return (
    <View style={styles.stepLine}>
      <StepBadge n={n} />
      <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>{text}</Text>
    </View>
  );
};

const ScanStart: React.FC<{ invalidParam: boolean; onToken: (token: string) => void }> = ({ invalidParam, onToken }) => {
  const { colors } = useTheme();
  const [permission, requestPermission] = useCameraPermissions();
  const [scannerOpen, setScannerOpen] = useState(false);

  const openScanner = async () => {
    if (!permission?.granted) {
      const result = await requestPermission();
      if (!result.granted) {
        Alert.alert(
          'ต้องการสิทธิ์ใช้กล้อง',
          'อนุญาตให้แอปใช้กล้องเพื่อสแกน QR ชำระเงินของร้าน',
          result.canAskAgain
            ? [{ text: 'ตกลง' }]
            : [
                { text: 'ไว้ก่อน', style: 'cancel' },
                { text: 'เปิดการตั้งค่า', onPress: () => Linking.openSettings().catch(() => undefined) },
              ]
        );
        return;
      }
    }
    setScannerOpen(true);
  };

  return (
    <Screen title="สแกนจ่ายร้านค้า" subtitle="ส่งด้วยไรเดอร์ Thai Prompt">
      {invalidParam && (
        <NoticeBanner
          tone="danger"
          text="ลิงก์ชำระเงินไม่ถูกต้อง สแกน QR บนหน้าจอร้านอีกครั้งนะ"
          style={styles.block}
        />
      )}
      <Card3D gradientBorder shadow="lg" padding={spacing.xl} radius={24} style={styles.block}>
        <View style={[styles.scanHero, { backgroundColor: colors.goldSoft }]}>
          <Icon name="qr-code" size={46} color={colors.goldDeep} />
        </View>
        <Text style={[typography.serif, styles.centerText, { color: colors.textStrong }]}>สแกน QR ที่หน้าจอร้าน</Text>
        <Text style={[typography.body, styles.centerText, styles.gapTopSm, { color: colors.textMuted }]}>
          เมื่อร้านกด “ส่งไรเดอร์ Thai Prompt” ที่เครื่องขายของ จะมี QR ขึ้นบนหน้าจอ สแกนเพื่อดูรายการและจ่ายด้วยกระเป๋าเงิน
        </Text>
        <View style={styles.steps}>
          <StepLine n={1} text="สแกน QR แล้วตรวจรายการกับราคา" />
          <StepLine n={2} text="เลือกที่อยู่ที่ปักหมุดไว้ ดูค่าส่งไรเดอร์" />
          <StepLine n={3} text="ใส่ PIN จ่ายจากกระเป๋าเงิน ไรเดอร์รับของที่ร้านไปส่งให้" />
        </View>
        <Button3D title="เปิดกล้องสแกน" icon="scan" size="lg" fullWidth onPress={openScanner} style={styles.gapTop} />
      </Card3D>

      <PosQrScanner
        visible={scannerOpen}
        onClose={() => setScannerOpen(false)}
        onToken={(token) => {
          setScannerOpen(false);
          onToken(token);
        }}
      />
    </Screen>
  );
};

/** กล้องสแกน QR ชำระเงินของร้าน (พื้นมืดเสมอทั้งสองโหมด เหมือนตัวสแกนหน้าโอนเงิน) */
const PosQrScanner: React.FC<{ visible: boolean; onClose: () => void; onToken: (token: string) => void }> = ({
  visible,
  onClose,
  onToken,
}) => {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  const [hint, setHint] = useState<string | null>(null);
  // กล้องยิง onBarcodeScanned หลายครั้งต่อวินาที → รับครั้งเดียว / แจ้ง QR ผิดครั้งเดียวต่อรหัส
  const handledRef = useRef(false);
  const lastRejectedRef = useRef('');
  const hintTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(
    () => () => {
      if (hintTimerRef.current) clearTimeout(hintTimerRef.current);
    },
    []
  );

  /** ปิดกล้อง + ล้างสถานะ เปิดครั้งหน้าจะสแกนใหม่ได้ทันที */
  const close = () => {
    handledRef.current = false;
    lastRejectedRef.current = '';
    if (hintTimerRef.current) clearTimeout(hintTimerRef.current);
    setHint(null);
    onClose();
  };

  const handleScanned = ({ data }: { data: string }) => {
    if (handledRef.current) return;
    const token = isPosQr(data) ? parsePosToken(data) : null;
    if (token) {
      handledRef.current = true;
      resultHaptic('success');
      onToken(token);
      return;
    }
    if (lastRejectedRef.current === data) return;
    lastRejectedRef.current = data;
    setHint('QR นี้ไม่ใช่ QR ชำระเงินของร้าน ลองสแกน QR บนหน้าจอเครื่องขายของ');
    if (hintTimerRef.current) clearTimeout(hintTimerRef.current);
    hintTimerRef.current = setTimeout(() => {
      lastRejectedRef.current = '';
      setHint(null);
    }, 2500);
  };

  return (
    <Modal visible={visible} animationType="slide" onRequestClose={close}>
      <View style={[styles.flex, { backgroundColor: DARK_THEME.colors.background }]}>
        <View style={[styles.scannerHeader, { paddingTop: insets.top + spacing.md }]}>
          <GlassIconButton icon="x" weight="bold" accessibilityLabel="ปิด" onPress={close} />
          <Text style={[typography.h2, { color: DARK_THEME.colors.textStrong }]}>สแกน QR ของร้าน</Text>
          <View style={styles.scannerSpacer} />
        </View>

        {visible && (
          <CameraView
            style={styles.flex}
            facing="back"
            barcodeScannerSettings={{ barcodeTypes: ['qr'] }}
            onBarcodeScanned={handleScanned}
          >
            <View style={[styles.scannerOverlay, { backgroundColor: colors.overlay }]}>
              <View style={[styles.scannerFrame, { borderColor: colors.gold }]} />
            </View>
          </CameraView>
        )}

        <View style={[styles.scannerFooter, { paddingBottom: insets.bottom + spacing.xl }]}>
          {hint ? (
            <Text style={[typography.bodyStrong, styles.centerText, { color: DARK_THEME.colors.warning }]}>{hint}</Text>
          ) : (
            <Text style={[typography.body, styles.centerText, { color: DARK_THEME.colors.text }]}>
              วาง QR บนหน้าจอร้านให้อยู่ในกรอบ
            </Text>
          )}
        </View>
      </View>
    </Modal>
  );
};

// =====================================================
// ตัวเลือกที่อยู่ (แผ่นเลือกที่อยู่)
// =====================================================

const AddressOption: React.FC<{ address: Address; selected: boolean; onPress: () => void }> = ({
  address,
  selected,
  onPress,
}) => {
  const { colors } = useTheme();
  return (
    <Card3D
      onPress={onPress}
      gradientBorder={selected}
      shadow="sm"
      padding={spacing.md}
      radius={18}
      style={styles.option}
      accessibilityRole="radio"
      accessibilityState={{ checked: selected }}
      accessibilityLabel={`${address.recipient_name} ${address.full_address}${address.has_location ? '' : ' ยังไม่ได้ปักหมุด'}`}
    >
      <View style={styles.optionRow}>
        <RadioMark selected={selected} />
        <IconTile
          icon={address.has_location ? 'map-pin' : 'house'}
          tone={selected ? 'gold' : 'navy'}
          size={40}
          weight={selected ? 'fill' : 'regular'}
        />
        <View style={styles.flex}>
          <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
            {address.recipient_name}
            {address.is_default ? ' (หลัก)' : ''}
          </Text>
          <Text numberOfLines={2} style={[typography.caption, { color: colors.textMuted }]}>
            {address.full_address}
          </Text>
          {!address.has_location && (
            <View style={styles.inlineWarn}>
              <Icon name="map-pin" size={13} color={colors.warning} />
              <Text style={[typography.caption, { color: colors.warning }]}>ยังไม่ได้ปักหมุด</Text>
            </View>
          )}
        </View>
      </View>
    </Card3D>
  );
};

// =====================================================
// มี token → รายการ / ที่อยู่ / ค่าส่ง / PIN / จ่าย
// =====================================================

const PosPayContent: React.FC<{ token: string }> = ({ token }) => {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  const navigation = useNavigation();

  // ---------- ใบเสนอราคา ----------
  const [quote, setQuote] = useState<PosRequestQuote | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<ApiFailure | null>(null);
  const [quoteLoading, setQuoteLoading] = useState(false);
  const [quoteError, setQuoteError] = useState<string | null>(null);
  const [terminal, setTerminal] = useState<TerminalState | null>(null);
  const [refreshing, setRefreshing] = useState(false);

  // ---------- ที่อยู่ / กระเป๋าเงิน ----------
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [addressesLoaded, setAddressesLoaded] = useState(false);
  /** ที่อยู่ที่ผู้ใช้เลือก (null = ให้ server ใช้ที่อยู่หลัก) */
  const [requestedAddressId, setRequestedAddressId] = useState<number | null>(null);
  const [addressSheet, setAddressSheet] = useState(false);
  const [walletBalance, setWalletBalance] = useState<number | null>(null);

  // ---------- PIN / จ่าย ----------
  const [pinSheet, setPinSheet] = useState(false);
  const [pin, setPin] = useState('');
  const [pinError, setPinError] = useState<string | null>(null);
  const [pinSummary, setPinSummary] = useState<string | undefined>(undefined);
  const [paying, setPaying] = useState(false);

  // Idempotency-Key หนึ่งตัวต่อการเปิดหน้า (ต่อ token) — ลองใหม่ใช้ตัวเดิม = ไม่ตัดเงินซ้ำ
  const [idempotencyKey] = useState(newIdempotencyKey);

  const mountedRef = useRef(true);
  const quoteReqRef = useRef(0);
  const hasQuoteRef = useRef(false);
  const knownAddressIdsRef = useRef<Set<number> | null>(null);
  const focusCountRef = useRef(0);
  const payingRef = useRef(false);
  const leavingRef = useRef(false);
  // ค่าล่าสุดสำหรับ callback ที่ไม่อยากผูก dependency (focus / ดึงลง)
  const requestedRef = useRef<number | null>(null);
  const quoteRef = useRef<PosRequestQuote | null>(null);

  useEffect(() => {
    requestedRef.current = requestedAddressId;
    quoteRef.current = quote;
  }, [requestedAddressId, quote]);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // =====================================================
  // โหลดข้อมูล
  // =====================================================

  /** ใบเสนอราคาของที่อยู่หนึ่ง (ผลเก่าที่ตอบช้ากว่าคำขอใหม่ถูกทิ้ง) */
  const fetchQuote = useCallback(
    async (addressId: number | null): Promise<QuoteOutcome> => {
      const requestId = ++quoteReqRef.current;
      if (hasQuoteRef.current) setQuoteLoading(true);
      else setLoading(true);

      const res = await getPosRequest(token, addressId);
      if (!mountedRef.current || requestId !== quoteReqRef.current) return { kind: 'stale' };
      setQuoteLoading(false);
      setLoading(false);

      if (res.success) {
        hasQuoteRef.current = true;
        setQuote(res.data);
        setQuoteError(null);
        setLoadError(null);
        const end = terminalFromQuote(res.data);
        setTerminal(end);
        return end ? { kind: 'terminal', terminal: end } : { kind: 'quote', quote: res.data };
      }

      const end = terminalFromFailure(res);
      if (end) {
        setTerminal(end);
        setQuoteError(null);
        setLoadError(null);
        return { kind: 'terminal', terminal: end };
      }
      if (hasQuoteRef.current) setQuoteError(res.message);
      else setLoadError(res);
      return { kind: 'error' };
    },
    [token]
  );

  /** ที่อยู่ทั้งหมด + ที่อยู่ที่เพิ่งเพิ่ม (เทียบกับรอบก่อน) */
  const loadAddresses = useCallback(async (): Promise<{ list: Address[]; added: Address[] } | null> => {
    const res = await getAddresses();
    if (!mountedRef.current) return null;
    setAddressesLoaded(true);
    if (!res.success) return null;
    const list = Array.isArray(res.data) ? res.data : [];
    const known = knownAddressIdsRef.current;
    const added = known ? list.filter((a) => !known.has(a.id)) : [];
    knownAddressIdsRef.current = new Set(list.map((a) => a.id));
    setAddresses(list);
    return { list, added };
  }, []);

  /** ยอดคงเหลือไว้แสดงอย่างเดียว (ตัดสินว่าพอไหมใช้ wallet.balance_ok จาก server) */
  const loadWallet = useCallback(async () => {
    const res = await getWallet();
    if (!mountedRef.current) return;
    const balance = Number(res?.data?.balance);
    if (res?.success && Number.isFinite(balance)) setWalletBalance(balance);
  }, []);

  useEffect(() => {
    loadAddresses();
    loadWallet();
  }, [loadAddresses, loadWallet]);

  // คำนวณใหม่ทุกครั้งที่เปลี่ยนที่อยู่ (ครั้งแรก = ที่อยู่หลักจาก server)
  useEffect(() => {
    fetchQuote(requestedAddressId);
  }, [fetchQuote, requestedAddressId]);

  /** โหลดใหม่ทั้งหมด — กลับมาจากหน้าเพิ่ม/แก้ที่อยู่ เติมเงิน หรือดึงลง */
  const refreshAll = useCallback(async () => {
    const [addressResult] = await Promise.all([loadAddresses(), loadWallet()]);
    if (!mountedRef.current) return;
    let next = requestedRef.current ?? quoteRef.current?.address?.id ?? null;
    if (addressResult) {
      if (addressResult.added.length > 0) {
        // เพิ่งเพิ่มที่อยู่ใหม่ → เลือกให้เลย
        next = addressResult.added.reduce((max, a) => (a.id > max.id ? a : max)).id;
      } else if (next !== null && !addressResult.list.some((a) => a.id === next)) {
        // ที่อยู่ที่เลือกถูกลบไปแล้ว → ให้ server ใช้ที่อยู่หลัก
        next = null;
      }
    }
    if (next !== requestedRef.current) setRequestedAddressId(next);
    else await fetchQuote(next);
  }, [loadAddresses, loadWallet, fetchQuote]);

  useFocusEffect(
    useCallback(() => {
      focusCountRef.current += 1;
      if (focusCountRef.current > 1 && !payingRef.current && !leavingRef.current) {
        refreshAll();
      }
    }, [refreshAll])
  );

  const onRefresh = async () => {
    if (payingRef.current) return;
    setRefreshing(true);
    await refreshAll();
    if (mountedRef.current) setRefreshing(false);
  };

  // ระหว่างรอผลจ่ายเงิน ห้ามออกจากหน้า (ไม่งั้นไม่รู้ผล)
  useEffect(() => {
    const unsubscribe = navigation.addListener('beforeRemove', (event: any) => {
      if (leavingRef.current || !payingRef.current) return;
      event.preventDefault();
      Alert.alert('กำลังชำระเงิน', 'รอสักครู่ให้ระบบยืนยันการชำระเงินก่อนนะ');
    });
    return unsubscribe;
  }, [navigation]);

  // =====================================================
  // ที่อยู่ / สถานะปุ่มจ่าย
  // =====================================================

  /** ที่อยู่ที่ server ใช้คิดค่าส่งในใบเสนอราคาล่าสุด — ใช้ทั้งแสดงผลและส่งตอนจ่าย */
  const pricedAddressId = quote?.address?.id ?? null;
  /** ใบเสนอราคาล่าสุดใช้ได้ (ไม่มีคำขอใหม่ค้าง/ไม่ error) → ตรงกับที่อยู่ที่แสดงอยู่ */
  const priced = !!quote?.address && !quoteLoading && !quoteError;
  // ระหว่างคำนวณใหม่/คำนวณไม่สำเร็จ แสดงที่อยู่ที่เพิ่งเลือก · นอกนั้นแสดงที่อยู่ที่ server คิดราคาให้จริง
  const selectedAddressId = quoteLoading || quoteError ? requestedAddressId ?? pricedAddressId : pricedAddressId;
  const selectedAddress = addresses.find((a) => a.id === selectedAddressId) ?? null;
  const riderReady = !!quote && priced && !!quote.address?.has_location && quote.rider.available && quote.total !== null;

  const blockReason: string | null = (() => {
    if (!quote) return 'กำลังโหลด...';
    if (quoteLoading) return 'กำลังคำนวณค่าส่ง...';
    if (quoteError) return 'คำนวณยอดไม่สำเร็จ ลองใหม่อีกครั้ง';
    if (quote.items.length === 0) return 'ไม่มีรายการสินค้า';
    if (!quote.address) return addressesLoaded && addresses.length === 0 ? 'เพิ่มที่อยู่จัดส่งก่อน' : 'เลือกที่อยู่ที่ปักหมุดไว้';
    if (!quote.address.has_location) return 'ปักหมุดที่อยู่ก่อน';
    if (!quote.rider.available) return quote.rider.message || 'ยังส่งด้วยไรเดอร์ไปที่อยู่นี้ไม่ได้';
    if (quote.total === null) return 'ยังคำนวณยอดไม่ได้';
    if (!quote.wallet.balance_ok) return 'ยอดเงินในกระเป๋าไม่พอ';
    return null;
  })();
  const canPay = !blockReason && !paying;

  const chooseAddress = (address: Address) => {
    selectionHaptic();
    setAddressSheet(false);
    if (address.id === pricedAddressId && priced) return;
    // เลือกที่อยู่เดิมซ้ำ (state ไม่เปลี่ยน = effect ไม่ยิง) → ขอใหม่เอง
    if (address.id === requestedAddressId) fetchQuote(address.id);
    else setRequestedAddressId(address.id);
  };

  // =====================================================
  // จ่ายเงิน
  // =====================================================

  const openPin = () => {
    if (blockReason || payingRef.current || !quote) return;
    // สรุปยอดตอนเปิดแผ่น (ไม่กระพริบหายระหว่างถามสถานะซ้ำตอนเน็ตหลุด)
    const feeText = quote.rider.fee ? `ค่าส่งไรเดอร์ ${formatBaht(quote.rider.fee, { decimals: 2 })}` : 'ส่งฟรี';
    setPinSummary(
      `จ่าย ${formatBaht(quote.total, { decimals: 2 })} ให้ ${quote.store?.name || 'ร้านค้า'} ` +
        `(ค่าสินค้า ${formatBaht(quote.subtotal, { decimals: 2 })} + ${feeText}) จากกระเป๋าเงิน`
    );
    setPin('');
    setPinError(null);
    setPinSheet(true);
  };

  /** ปิดแผ่น PIN (ระหว่างรอผลจ่ายปิดไม่ได้) */
  const closePin = () => {
    if (payingRef.current) return;
    setPinSheet(false);
    setPin('');
    setPinError(null);
  };

  const forceClosePin = () => {
    setPinSheet(false);
    setPin('');
    setPinError(null);
  };

  const leaveTo = (path: string) => {
    leavingRef.current = true;
    forceClosePin();
    router.replace(path as never);
  };

  const handlePayFailure = async (res: ApiFailure, addressId: number) => {
    resultHaptic('error');
    switch (res.code) {
      case 'INVALID_PIN': {
        const left = Number(res.data?.attempts_remaining);
        setPinError(Number.isFinite(left) && left >= 0 ? `${res.message} (ลองได้อีก ${left} ครั้ง)` : res.message);
        setPin('');
        return;
      }
      case 'VALIDATION_ERROR':
      case 'VALIDATION':
        setPinError(res.message);
        return;
      case 'CHECKOUT_IN_PROGRESS':
      case 'DUPLICATE_REQUEST':
      case 'REQUEST_IN_PROGRESS':
        setPinError('ระบบกำลังทำรายการก่อนหน้า รอสักครู่แล้วกดยืนยันอีกครั้ง ระบบจะไม่ตัดเงินซ้ำ');
        return;
      case 'TIMEOUT':
      case 'NETWORK_ERROR':
      case 'SERVER_ERROR': {
        // ไม่รู้ว่า server ตัดเงินไปแล้วหรือยัง → ถามสถานะคำขอก่อนชวนลองใหม่
        const outcome = await fetchQuote(addressId);
        if (!mountedRef.current) return;
        if (outcome.kind === 'terminal') {
          if (outcome.terminal.kind === 'paid_mine') {
            resultHaptic('success');
            leaveTo(orderPathFor(outcome.terminal.orderId));
          } else {
            forceClosePin();
          }
          return;
        }
        setPinError(`${res.message}\nกดยืนยันอีกครั้งได้เลย ระบบจะไม่ตัดเงินซ้ำ`);
        return;
      }
      case 'WALLET_LOCKED': {
        forceClosePin();
        const until = timeText(res.data?.locked_until);
        Alert.alert(
          'กระเป๋าเงินถูกล็อกชั่วคราว',
          until ? `ใส่ PIN ผิดหลายครั้ง ลองใหม่ได้หลัง ${until} น.` : res.message
        );
        return;
      }
      case 'PROFILE_PHOTO_REQUIRED':
        // ไรเดอร์รอบ 2: ด่าน profile.photo ของ server (แบบเดียวกับหน้าชำระเงินตะกร้า) — ยังไม่ได้ตัดเงิน/ไม่นับ PIN
        forceClosePin();
        Alert.alert(
          'ถ่ายรูปโปรไฟล์ก่อนนะ',
          'ก่อนสั่งซื้อครั้งแรก ทุกบัญชีต้องมีรูปโปรไฟล์ถ่ายสดจากกล้อง ไรเดอร์จะได้รู้ว่าส่งของถึงมือใคร ถ่ายเสร็จแล้วกลับมาจ่ายต่อได้เลย (ก่อน QR หมดเวลา)',
          [
            { text: 'ไว้ก่อน', style: 'cancel' },
            { text: 'ถ่ายรูปเลย', onPress: () => router.push('/profile-photo?from=checkout' as never) },
          ]
        );
        return;
      case 'PIN_NOT_SET':
        forceClosePin();
        Alert.alert('ยังไม่ได้ตั้ง PIN กระเป๋าเงิน', 'ตั้ง PIN 6 หลักก่อน แล้วกลับมาจ่ายได้เลย (ก่อน QR หมดเวลา)', [
          { text: 'ไว้ก่อน', style: 'cancel' },
          { text: 'ตั้ง PIN', onPress: () => router.push('/wallet-withdraw' as never) },
        ]);
        return;
      case 'INSUFFICIENT_BALANCE': {
        forceClosePin();
        const shortfall = Number(res.data?.shortfall);
        Alert.alert(
          'ยอดเงินในกระเป๋าไม่พอ',
          Number.isFinite(shortfall) && shortfall > 0
            ? `ขาดอีก ${formatBaht(shortfall, { decimals: 2 })} เติมเงินแล้วกลับมาจ่ายได้เลย`
            : res.message,
          [
            { text: 'ไว้ก่อน', style: 'cancel' },
            { text: 'เติมเงิน', onPress: () => router.push('/wallet-topup' as never) },
          ]
        );
        loadWallet();
        fetchQuote(addressId);
        return;
      }
      case 'ADDRESS_LOCATION_REQUIRED':
        forceClosePin();
        Alert.alert('ปักหมุดที่อยู่ก่อนนะ', 'ส่งด้วยไรเดอร์ต้องปักหมุดตำแหน่ง ไรเดอร์จะได้ไปส่งถูกที่', [
          { text: 'ไว้ก่อน', style: 'cancel' },
          { text: 'ปักหมุด', onPress: () => router.push(`/addresses/edit?id=${addressId}` as never) },
        ]);
        fetchQuote(addressId);
        return;
      default: {
        forceClosePin();
        const end = terminalFromFailure(res);
        if (end) {
          // แสดงสถานะจบทันที แล้วถาม server อีกรอบ (จ่ายแล้วโดยเรา = มีปุ่มดูคำสั่งซื้อ)
          setTerminal(end);
          fetchQuote(addressId);
          return;
        }
        Alert.alert('ยังชำระเงินไม่สำเร็จ', res.message);
        fetchQuote(addressId);
      }
    }
  };

  const submitPay = async () => {
    if (payingRef.current) return;
    const addressId = quote?.address?.id;
    if (blockReason || !addressId) {
      setPinError(blockReason || 'เลือกที่อยู่ที่ปักหมุดไว้ก่อนนะ');
      return;
    }
    if (pin.length !== 6) {
      setPinError('กรอก PIN 6 หลัก');
      return;
    }
    payingRef.current = true;
    setPaying(true);
    setPinError(null);

    try {
      const res = await payPosRequest(token, { address_id: addressId, pin }, idempotencyKey);
      if (!mountedRef.current) return;

      if (res.success) {
        resultHaptic('success');
        leaveTo(orderPathFor(res.data.order_id, res.data.track_path));
        return;
      }
      // ยังล็อกปุ่มไว้ระหว่างตรวจผล (เช่น เน็ตหลุดแล้วถามสถานะคำขอซ้ำ)
      await handlePayFailure(res, addressId);
    } finally {
      payingRef.current = false;
      if (mountedRef.current) setPaying(false);
    }
  };

  // =====================================================
  // render
  // =====================================================

  // ---------- สถานะจบ ----------
  if (terminal) {
    const copy = TERMINAL_COPY[terminal.kind];
    const paidMine = terminal.kind === 'paid_mine';
    return (
      <Screen title={SCREEN_TITLE} scroll={false}>
        <EmptyState
          icon={copy.icon}
          title={copy.title}
          message={copy.message}
          actionLabel={paidMine ? 'ดูคำสั่งซื้อ' : 'สแกน QR ใหม่'}
          onAction={() =>
            paidMine ? leaveTo(orderPathFor(terminal.orderId)) : router.replace('/pos-pay' as never)
          }
          secondaryActionLabel="กลับ"
          onSecondaryAction={goBackOrHome}
        />
      </Screen>
    );
  }

  // ---------- โหลดครั้งแรก ----------
  if (!quote) {
    return (
      <Screen title={SCREEN_TITLE} scroll={false}>
        {loadError && !loading ? (
          <EmptyState
            variant={loadError.code === 'NETWORK_ERROR' || loadError.code === 'TIMEOUT' ? 'offline' : 'error'}
            message={loadError.message}
            onAction={() => fetchQuote(requestedRef.current)}
            secondaryActionLabel="กลับ"
            onSecondaryAction={goBackOrHome}
          />
        ) : (
          <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
        )}
      </Screen>
    );
  }

  const itemCount = quote.items.reduce((sum, item) => sum + item.qty, 0);
  const expiresAt = timeText(quote.expires_at);
  const storeName = quote.store?.name || 'ร้านค้า';
  const fee = quote.rider.fee;

  // ---------- การ์ดที่อยู่ ----------
  const renderAddress = () => {
    // ที่อยู่ไม่อยู่ในรายการ (เช่น รายการยังโหลดไม่เสร็จ) → ใช้ข้อมูลย่อจากใบเสนอราคาถ้าเป็นที่อยู่เดียวกัน
    const fallback = selectedAddressId !== null && selectedAddressId === pricedAddressId ? quote.address : null;
    if (selectedAddressId !== null && (selectedAddress || fallback)) {
      const hasLocation = selectedAddress ? selectedAddress.has_location : !!fallback?.has_location;
      return (
        <Card3D
          onPress={() => setAddressSheet(true)}
          padding={spacing.lg}
          radius={20}
          style={styles.block}
          accessibilityLabel="เปลี่ยนที่อยู่จัดส่ง"
        >
          <View style={styles.addressRow}>
            <IconTile icon="map-pin" tone="gold" weight="fill" />
            <View style={styles.flex}>
              <View style={styles.rowBetween}>
                <Text numberOfLines={1} style={[typography.bodyStrong, styles.flex, { color: colors.textStrong }]}>
                  {selectedAddress
                    ? `${selectedAddress.recipient_name} · ${selectedAddress.phone_number}`
                    : fallback?.label || 'ที่อยู่จัดส่ง'}
                </Text>
                {selectedAddress?.is_default && <Pill label="หลัก" tone="gold" />}
              </View>
              <Text style={[typography.bodySm, styles.gapTopSm, { color: colors.text }]}>
                {selectedAddress ? selectedAddress.full_address : fallback?.line || ''}
              </Text>
            </View>
          </View>
          {!hasLocation && (
            <NoticeBanner
              tone="warning"
              icon="map-pin"
              text="ยังไม่ได้ปักหมุด — ไรเดอร์ต้องรู้ตำแหน่งที่จะไปส่ง"
              action={
                <Button3D
                  title="ปักหมุด"
                  size="sm"
                  variant="secondary"
                  onPress={() => router.push(`/addresses/edit?id=${selectedAddressId}` as never)}
                />
              }
              style={styles.innerTop}
            />
          )}
        </Card3D>
      );
    }

    const noAddress = addressesLoaded && addresses.length === 0;
    return (
      <Card3D gradientBorder padding={spacing.lg} radius={20} style={styles.block}>
        <View style={styles.addressRow}>
          <IconTile icon="map-pin" tone="gold" weight="fill" />
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>
              {noAddress ? 'ยังไม่มีที่อยู่จัดส่ง' : 'เลือกที่อยู่จัดส่ง'}
            </Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              {noAddress ? 'เพิ่มที่อยู่และปักหมุด ไรเดอร์จะได้ไปส่งถูกที่' : 'ส่งด้วยไรเดอร์ต้องเป็นที่อยู่ที่ปักหมุดไว้'}
            </Text>
          </View>
        </View>
        <Button3D
          title={noAddress ? 'เพิ่มที่อยู่' : 'เลือกที่อยู่'}
          icon={noAddress ? 'plus' : 'map-pin'}
          size="md"
          fullWidth
          onPress={() => (noAddress ? router.push('/addresses/edit' as never) : setAddressSheet(true))}
          style={styles.gapTop}
        />
      </Card3D>
    );
  };

  // ---------- ค่าส่งไรเดอร์ ----------
  const renderRider = () => {
    // ยังไม่มีที่อยู่ / ยังไม่ปักหมุด → การ์ดที่อยู่บอกวิธีแก้แล้ว
    if (!quote.address || !quote.address.has_location) return null;
    if (quoteError) return null; // แถบ error ด้านบนมีปุ่มลองใหม่แล้ว
    if (quoteLoading) {
      return (
        <View style={[styles.waitingRow, { backgroundColor: colors.card, borderColor: colors.border }]}>
          <ActivityIndicator color={colors.gold} />
          <Text style={[typography.caption, { color: colors.textMuted }]}>กำลังคำนวณค่าส่งไรเดอร์...</Text>
        </View>
      );
    }
    if (!quote.rider.available) {
      return (
        <NoticeBanner
          tone="warning"
          icon="moped"
          text={quote.rider.message || 'ยังส่งด้วยไรเดอร์ไปที่อยู่นี้ไม่ได้'}
          actionBelow={addresses.length > 1}
          action={
            addresses.length > 1 ? (
              <Button3D title="เลือกที่อยู่อื่น" icon="map-pin" size="sm" variant="secondary" onPress={() => setAddressSheet(true)} />
            ) : undefined
          }
          style={styles.block}
        />
      );
    }
    return (
      <Card3D padding={spacing.lg} radius={20} style={styles.block}>
        <View style={styles.rowCenter}>
          <IconTile icon="moped" tone="navy" />
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>ส่งด้วยไรเดอร์ Thai Prompt</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              {quote.rider.distance_km !== null
                ? `ระยะทางประมาณ ${quote.rider.distance_km.toFixed(1)} กม. · ไรเดอร์รับของที่ร้าน`
                : 'ไรเดอร์รับของที่ร้านแล้วนำส่งถึงคุณ'}
            </Text>
          </View>
          {fee === 0 ? <Pill label="ส่งฟรี" tone="success" /> : <PriceText amount={fee} size="md" tone="strong" empty="—" />}
        </View>
      </Card3D>
    );
  };

  return (
    <View style={[styles.flex, { backgroundColor: colors.background }]}>
      <Screen
        title={SCREEN_TITLE}
        subtitle={storeName}
        refreshing={refreshing}
        onRefresh={onRefresh}
        contentStyle={{ paddingBottom: 140 + Math.max(insets.bottom, spacing.md) }}
      >
        {/* ---------- ร้าน ---------- */}
        <Card3D gradientBorder shadow="md" padding={spacing.lg} radius={22} style={styles.block}>
          <View style={styles.rowCenter}>
            <StoreLogo uri={quote.store?.logo_url} size={56} />
            <View style={styles.flex}>
              <Text numberOfLines={2} style={[typography.serifSm, { color: colors.textStrong }]}>
                {storeName}
              </Text>
              <Text style={[typography.caption, { color: colors.textMuted }]}>ขอให้ชำระค่าสินค้า · ส่งด้วยไรเดอร์ Thai Prompt</Text>
            </View>
          </View>
          {!!expiresAt && (
            <Pill label={`ชำระภายใน ${expiresAt} น.`} tone="warning" icon="timer" style={styles.expiryPill} />
          )}
        </Card3D>

        {!!quoteError && (
          <NoticeBanner
            tone="danger"
            text={quoteError}
            actionBelow
            action={
              <Button3D
                title="ลองใหม่"
                icon="arrows-clockwise"
                size="sm"
                variant="secondary"
                onPress={() => fetchQuote(requestedRef.current)}
              />
            }
            style={styles.block}
          />
        )}

        {/* ---------- 1 รายการ ---------- */}
        <SectionHeader title="รายการจากร้าน" subtitle={`${itemCount} ชิ้น`} icon={<StepBadge n={1} />} />
        <Card3D padding={spacing.lg} radius={20} style={styles.block}>
          {quote.items.length === 0 ? (
            <Text style={[typography.bodySm, { color: colors.textMuted }]}>ไม่มีรายการสินค้า</Text>
          ) : (
            quote.items.map((item, index) => (
              <View
                key={`${item.product_id}-${index}`}
                style={[
                  styles.itemRow,
                  index > 0 && { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.divider },
                ]}
              >
                <ThumbImage uri={item.image_url} size={48} />
                <View style={styles.flex}>
                  <Text numberOfLines={2} style={[typography.bodyStrong, { color: colors.textStrong }]}>
                    {item.name}
                  </Text>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>
                    {item.qty} × {formatBaht(item.price)}
                  </Text>
                </View>
                <PriceText amount={item.line_total} size="sm" tone="strong" />
              </View>
            ))
          )}
          {!!quote.note && (
            <View style={[styles.noteBox, { backgroundColor: colors.inset, borderColor: colors.border }]}>
              <Icon name="note-pencil" size={16} color={colors.textMuted} />
              <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>หมายเหตุ: {quote.note}</Text>
            </View>
          )}
        </Card3D>

        {/* ---------- 2 ที่อยู่ + ค่าส่ง ---------- */}
        <SectionHeader
          title="ส่งไปที่"
          icon={<StepBadge n={2} />}
          actionLabel={addresses.length > 0 ? 'เปลี่ยน' : undefined}
          onAction={() => setAddressSheet(true)}
          style={styles.section}
        />
        {renderAddress()}
        {renderRider()}

        {/* ---------- 3 ชำระด้วย ---------- */}
        <SectionHeader title="ชำระด้วย" icon={<StepBadge n={3} />} style={styles.section} />
        <Card3D padding={spacing.lg} radius={20} style={styles.block}>
          <View style={styles.rowCenter}>
            <IconTile icon="wallet" tone="gold" weight="fill" />
            <View style={styles.flex}>
              <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>กระเป๋าเงิน Thai Prompt</Text>
              <Text style={[typography.caption, { color: colors.textMuted }]}>
                {walletBalance !== null
                  ? `ยอดคงเหลือ ${formatBaht(walletBalance, { decimals: 2 })}`
                  : 'ตัดเงินจากกระเป๋าเมื่อยืนยันด้วย PIN'}
              </Text>
            </View>
            {riderReady &&
              (quote.wallet.balance_ok ? <Pill label="ยอดพอ" tone="success" /> : <Pill label="ไม่พอ" tone="danger" />)}
          </View>
          {riderReady && !quote.wallet.balance_ok && (
            <NoticeBanner
              tone="danger"
              icon="wallet"
              text="ยอดเงินในกระเป๋าไม่พอสำหรับคำสั่งซื้อนี้ เติมเงินแล้วกลับมาจ่ายได้เลย"
              actionBelow
              action={
                <Button3D title="เติมเงิน" icon="plus" size="sm" onPress={() => router.push('/wallet-topup' as never)} />
              }
              style={styles.innerTop}
            />
          )}
          <Text style={[typography.caption, styles.innerTop, { color: colors.textFaint }]}>
            รับชำระด้วยกระเป๋าเงินเท่านั้น ไม่มีเก็บเงินปลายทาง
          </Text>
        </Card3D>

        {/* ---------- ยอดรวม ---------- */}
        <Card3D variant="inset" padding={spacing.lg} radius={20} style={styles.block}>
          <View style={styles.rowBetween}>
            <Text style={[typography.body, { color: colors.text }]}>ค่าสินค้า</Text>
            <PriceText amount={quote.subtotal} decimals={2} size="sm" tone="strong" />
          </View>
          <View style={[styles.rowBetween, styles.gapTopSm]}>
            <Text style={[typography.body, { color: colors.text }]}>ค่าส่งไรเดอร์</Text>
            {riderReady && fee === 0 ? (
              <Pill label="ส่งฟรี" tone="success" icon="moped" />
            ) : (
              <PriceText amount={riderReady ? fee : null} decimals={2} size="sm" tone="strong" empty="—" />
            )}
          </View>
          <View style={[styles.rowBetween, styles.grand, { borderTopColor: colors.divider }]}>
            <Text style={[typography.h3, { color: colors.textStrong }]}>ยอดชำระ</Text>
            <PriceText amount={riderReady ? quote.total : null} decimals={2} size="lg" tone="gold" empty="—" />
          </View>
        </Card3D>

        {/* ---------- เงินพัก ---------- */}
        <View style={[styles.escrow, { backgroundColor: colors.infoSoft, borderColor: colors.border }]} accessible>
          <IconTile icon="lock" tone="gold" size={42} />
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>เงินของคุณถูกพักไว้อย่างปลอดภัย</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              ร้านและไรเดอร์จะได้เงินเมื่อคุณกับไรเดอร์สแกน QR ใส่กันตอนรับของแล้วเท่านั้น
            </Text>
          </View>
        </View>
      </Screen>

      {/* ---------- แถบจ่าย ---------- */}
      <StickyBar style={styles.bottomBar}>
        <View style={styles.flex}>
          <Text numberOfLines={2} style={[typography.caption, { color: blockReason ? colors.warning : colors.textMuted }]}>
            {blockReason ?? 'จ่ายด้วยกระเป๋าเงิน'}
          </Text>
          {quoteLoading ? (
            <ActivityIndicator color={colors.gold} style={styles.totalLoader} />
          ) : (
            <PriceText amount={riderReady ? quote.total : null} decimals={2} size="lg" tone="gold" empty="—" />
          )}
        </View>
        <Button3D
          title="ชำระเงิน"
          icon="lock-key"
          size="lg"
          loading={paying}
          loadingText="กำลังชำระ..."
          disabled={!canPay}
          onPress={openPin}
          style={styles.payButton}
        />
      </StickyBar>

      {/* ---------- เลือกที่อยู่ ---------- */}
      <FormSheet
        visible={addressSheet}
        icon="map-pin"
        title="เลือกที่อยู่จัดส่ง"
        description="ส่งด้วยไรเดอร์ต้องเป็นที่อยู่ที่ปักหมุดไว้"
        onClose={() => setAddressSheet(false)}
        cancelLabel="ปิด"
      >
        <View style={styles.sheetList}>
          {addresses.map((a) => (
            <AddressOption key={a.id} address={a} selected={a.id === selectedAddressId} onPress={() => chooseAddress(a)} />
          ))}
        </View>
        <View style={styles.sheetButtons}>
          <Button3D
            title="เพิ่มที่อยู่ใหม่"
            icon="plus"
            size="md"
            onPress={() => {
              setAddressSheet(false);
              router.push('/addresses/edit' as never);
            }}
            style={styles.flex}
          />
          <Button3D
            title="จัดการที่อยู่"
            variant="secondary"
            size="md"
            onPress={() => {
              setAddressSheet(false);
              router.push('/addresses' as never);
            }}
            style={styles.flex}
          />
        </View>
      </FormSheet>

      {/* ---------- ยืนยันด้วย PIN ---------- */}
      <ConsentSheet
        visible={pinSheet}
        icon="lock-key"
        title="ยืนยันด้วย PIN"
        description={pinSummary}
        acceptLabel="ยืนยันชำระเงิน"
        declineLabel="ยกเลิก"
        acceptDisabled={pin.length !== 6}
        onAccept={submitPay}
        onDecline={closePin}
        dismissible={!paying}
        footnote="ระบบตัดเงินครั้งเดียวต่อคำขอ กดซ้ำหรือเน็ตหลุดแล้วลองใหม่จะไม่ตัดเงินซ้ำ"
      >
        <Field
          label="PIN กระเป๋าเงิน 6 หลัก"
          value={pin}
          onChangeText={(t) => {
            setPin(onlyDigits(t, 6));
            if (pinError) setPinError(null);
          }}
          keyboardType="number-pad"
          secureTextEntry
          maxLength={6}
          placeholder="••••••"
          autoFocus
          editable={!paying}
          error={pinError}
          containerStyle={styles.pinField}
          style={styles.pinInput}
        />
      </ConsentSheet>
    </View>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  block: {
    marginBottom: spacing.md,
  },
  section: {
    marginTop: spacing.lg,
  },
  innerTop: {
    marginTop: spacing.md,
  },
  gapTop: {
    marginTop: spacing.lg,
  },
  gapTopSm: {
    marginTop: spacing.xs,
  },
  centerText: {
    textAlign: 'center',
  },
  rowBetween: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  rowCenter: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  // หน้าเริ่มสแกน
  scanHero: {
    alignSelf: 'center',
    width: 88,
    height: 88,
    borderRadius: 44,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.md,
  },
  steps: {
    marginTop: spacing.lg,
    gap: spacing.sm,
  },
  stepLine: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  // ตัวสแกน
  scannerHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.lg,
  },
  scannerSpacer: {
    width: 40,
  },
  scannerOverlay: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  scannerFrame: {
    width: 250,
    height: 250,
    borderWidth: 3,
    borderRadius: 24,
    backgroundColor: 'transparent',
  },
  scannerFooter: {
    paddingTop: spacing.xl,
    paddingHorizontal: spacing.xl,
    alignItems: 'center',
    minHeight: 96,
  },
  // ร้าน / รายการ
  expiryPill: {
    alignSelf: 'flex-start',
    marginTop: spacing.md,
  },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.sm,
  },
  noteBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    marginTop: spacing.md,
    padding: spacing.md,
    borderRadius: 14,
    borderWidth: 1,
  },
  // ที่อยู่
  addressRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
  },
  option: {
    marginBottom: spacing.sm,
  },
  optionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  inlineWarn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 2,
  },
  sheetList: {
    marginTop: spacing.md,
  },
  sheetButtons: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  waitingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    alignSelf: 'center',
    gap: spacing.sm,
    marginBottom: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
    borderRadius: 999,
    borderWidth: 1,
  },
  // ยอดรวม / เงินพัก
  grand: {
    borderTopWidth: 1,
    marginTop: spacing.md,
    paddingTop: spacing.md,
  },
  escrow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: spacing.lg,
    borderRadius: 20,
    borderWidth: 1,
    marginBottom: spacing.md,
  },
  // แถบล่าง
  bottomBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  totalLoader: {
    alignSelf: 'flex-start',
    marginTop: spacing.xs,
  },
  payButton: {
    minWidth: 150,
    flexShrink: 0,
  },
  // PIN
  pinField: {
    marginTop: spacing.lg,
  },
  pinInput: {
    fontSize: 22,
    fontWeight: '700',
    letterSpacing: 8,
    textAlign: 'center',
  },
});
