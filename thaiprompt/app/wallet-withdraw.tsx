/**
 * ถอนเงิน — ใช้ API ถอนเงินชุดเดียวกับเว็บ (E2: WithdrawalService + PIN) · ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * ลำดับ: GET /wallet/withdraw/info → (ยังไม่ยืนยันตัวตน: ไป KYC) → (ยังไม่มี PIN: ตั้ง PIN)
 *        → (ยังไม่มีบัญชี: เพิ่มบัญชี) → กรอกยอด (preview จาก server) → ใส่ PIN → POST /wallet/withdraw
 *
 * ค่าธรรมเนียม / ภาษี / ขั้นต่ำ มาจาก server ทั้งหมด — ห้ามฮาร์ดโค้ดในแอป
 *
 * จัดการบัญชีรับเงิน (ปุ่มจุดสามจุดท้ายแถว):
 *   - ตั้งเป็นบัญชีหลัก → ถามยืนยัน → POST /wallet/bank-accounts/{id}/default
 *   - ลบบัญชี → ถามยืนยันพร้อม PIN → POST /wallet/bank-accounts/{id}/delete
 *     (มีคำขอถอนค้าง = 409 IN_USE · PIN ผิด = บอกจำนวนครั้งที่เหลือ · ลบบัญชีหลัก = server ตั้งบัญชีอื่นเป็นหลักให้)
 *
 * หน้าตา: การ์ดยอดเงินน้ำเงินแบบบัตรโลหะ · บัญชีรับเงินเป็นแถวในการ์ดเดียว (เลือกแบบวิทยุ)
 *         ตัวเลขถอนตัวใหญ่ · ฟอร์ม PIN/บัญชีเป็นการ์ดขาวหัวไอคอน · ธนาคารเลือกเป็นแถว
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  StyleSheet,
  View,
} from 'react-native';
import { Text, TextInput } from '@/components/ui/Text';
import { router } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import {
  addPayoutAccount,
  cancelWithdrawal,
  deletePayoutAccount,
  getWithdrawInfo,
  getWithdrawals,
  previewWithdraw,
  setDefaultPayoutAccount,
  setWalletPin,
  withdraw,
  type PayoutAccount,
  type WithdrawInfo,
  type WithdrawPreview,
  type Withdrawal,
} from '@/services/api/accountApi';
import { newIdempotencyKey } from '@/services/api/client';
import {
  Button3D,
  Card3D,
  Chip,
  ConsentSheet,
  EmptyState,
  Icon,
  OnHeaderProvider,
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  formatBaht,
  resultHaptic,
  selectionHaptic,
  type IconName,
} from '@/components/ui';
import { IconTile, InfoRow, MoneyInput, MoneyText, NavyCard, type TileTone } from '@/components/wallet/WalletKit';
import { useTheme, spacing, radii, typography, type Tone } from '@/theme';

const BANKS = ['กสิกรไทย', 'ไทยพาณิชย์', 'กรุงเทพ', 'กรุงไทย', 'กรุงศรีอยุธยา', 'ทหารไทยธนชาต', 'ออมสิน', 'ธ.ก.ส.'];

const WITHDRAWAL_TONE: Record<string, Tone> = {
  pending: 'warning',
  processing: 'info',
  approved: 'info',
  completed: 'success',
  rejected: 'danger',
  cancelled: 'neutral',
};

/** โทนป้าย → โทนกล่องไอคอน (neutral = น้ำเงิน) */
const TILE_TONE: Record<Tone, TileTone> = {
  neutral: 'navy',
  gold: 'gold',
  success: 'success',
  danger: 'danger',
  info: 'info',
  warning: 'warning',
};

/** เก็บเฉพาะตัวเลข + ทศนิยม 2 ตำแหน่ง */
const sanitizeAmount = (text: string): string => {
  const cleaned = text.replace(/[^0-9.]/g, '');
  const [intPart, ...rest] = cleaned.split('.');
  if (rest.length === 0) return intPart.slice(0, 9);
  return `${intPart.slice(0, 9)}.${rest.join('').slice(0, 2)}`;
};

const onlyDigits = (text: string, max: number) => text.replace(/\D/g, '').slice(0, max);

// =====================================================
// ช่องกรอกแบบธีม (พื้นยุบ มุม 14 · โฟกัส = ขอบทอง)
// =====================================================

const Field = ({
  label,
  hint,
  icon,
  onFocus,
  onBlur,
  ...inputProps
}: React.ComponentProps<typeof TextInput> & { label: string; hint?: string; icon?: IconName }) => {
  const { colors } = useTheme();
  const [focused, setFocused] = useState(false);
  return (
    <View style={styles.field}>
      <Text style={[typography.caption, styles.fieldLabel, { color: colors.textMuted }]}>{label}</Text>
      <View
        style={[
          styles.inputBox,
          { backgroundColor: colors.inset, borderColor: focused ? colors.gold : colors.border },
        ]}
      >
        {!!icon && <Icon name={icon} size={19} color={focused ? colors.goldDeep : colors.textFaint} />}
        <TextInput
          placeholderTextColor={colors.textFaint}
          selectionColor={colors.gold}
          {...inputProps}
          onFocus={(e) => {
            setFocused(true);
            onFocus?.(e);
          }}
          onBlur={(e) => {
            setFocused(false);
            onBlur?.(e);
          }}
          style={[styles.input, { color: colors.textStrong }, inputProps.style]}
          accessibilityLabel={label}
        />
      </View>
      {!!hint && <Text style={[typography.micro, { color: colors.textFaint }]}>{hint}</Text>}
    </View>
  );
};

/** ข้อความผิดพลาดใต้ฟอร์ม (กล่องแดงอ่อน + ไอคอน) */
const ErrorNote = ({ text }: { text: string }) => {
  const { colors } = useTheme();
  return (
    <View style={[styles.errorNote, { backgroundColor: colors.dangerSoft }]} accessibilityRole="alert">
      <Icon name="warning-circle" size={18} color={colors.danger} />
      <Text style={[typography.bodySm, styles.flex, { color: colors.danger }]}>{text}</Text>
    </View>
  );
};

/** วงเลือกแบบวิทยุ (เลือก = วงทองติ๊ก) */
const Radio = ({ checked }: { checked: boolean }) => {
  const { colors } = useTheme();
  return checked ? (
    <View style={[styles.radio, { backgroundColor: colors.gold, borderColor: colors.gold }]}>
      <Icon name="check" size={13} color={colors.textOnGold} weight="bold" />
    </View>
  ) : (
    <View style={[styles.radio, { borderColor: colors.border, backgroundColor: colors.card }]} />
  );
};

/** หัวการ์ดฟอร์ม: กล่องไอคอนทอง + หัวข้อ + คำอธิบาย */
const FormHead = ({ icon, title, lead }: { icon: IconName; title: string; lead?: string }) => {
  const { colors } = useTheme();
  return (
    <View style={styles.formHead}>
      <IconTile icon={icon} tone="gold" size={48} weight="fill" />
      <View style={styles.flex}>
        <Text style={[typography.h2, { color: colors.textStrong }]}>{title}</Text>
        {!!lead && <Text style={[typography.bodySm, { color: colors.textMuted }]}>{lead}</Text>}
      </View>
    </View>
  );
};

// =====================================================
// ตั้ง PIN
// =====================================================

const PinSetupCard = ({ onDone }: { onDone: () => void }) => {
  const [pin, setPin] = useState('');
  const [confirm, setConfirm] = useState('');
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    setError(null);
    if (pin.length !== 6) {
      setError('PIN ต้องเป็นตัวเลข 6 หลัก');
      return;
    }
    if (pin !== confirm) {
      setError('PIN ทั้งสองช่องไม่ตรงกัน');
      return;
    }
    const result = await setWalletPin({ pin, pin_confirmation: confirm });
    if (result.success) {
      resultHaptic('success');
      onDone();
    } else {
      resultHaptic('error');
      setError(result.message);
    }
  };

  return (
    <Card3D padding={spacing.xl}>
      <FormHead
        icon="lock-key"
        title="ตั้งรหัส PIN กระเป๋าเงิน"
        lead="ใช้ PIN 6 หลักยืนยันทุกครั้งที่ถอนเงินหรือแก้ไขบัญชีรับเงิน"
      />
      <Field label="PIN 6 หลัก" value={pin} onChangeText={(t) => setPin(onlyDigits(t, 6))} keyboardType="number-pad" secureTextEntry maxLength={6} placeholder="••••••" style={styles.pinInput} />
      <Field label="ยืนยัน PIN อีกครั้ง" value={confirm} onChangeText={(t) => setConfirm(onlyDigits(t, 6))} keyboardType="number-pad" secureTextEntry maxLength={6} placeholder="••••••" style={styles.pinInput} />
      {!!error && <ErrorNote text={error} />}
      <Button3D title="บันทึก PIN" icon="check-circle" size="lg" fullWidth onPress={submit} style={styles.cta} />
    </Card3D>
  );
};

// =====================================================
// เพิ่มบัญชีรับเงิน
// =====================================================

const AddAccountCard = ({ onDone, onCancel }: { onDone: () => void; onCancel?: () => void }) => {
  const { colors } = useTheme();
  const [type, setType] = useState<'bank_transfer' | 'promptpay'>('bank_transfer');
  const [bankName, setBankName] = useState('');
  const [accountName, setAccountName] = useState('');
  const [accountNumber, setAccountNumber] = useState('');
  const [pin, setPin] = useState('');
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    setError(null);
    if (type === 'bank_transfer' && !bankName) {
      setError('เลือกธนาคารก่อนนะ');
      return;
    }
    if (accountName.trim().length < 2) {
      setError('กรอกชื่อบัญชีให้ตรงกับหน้าสมุดบัญชี');
      return;
    }
    const digits = accountNumber.replace(/\D/g, '');
    if (digits.length < 9 || digits.length > 30) {
      setError(type === 'promptpay' ? 'กรอกเบอร์โทรหรือเลขบัตรประชาชนที่ผูก PromptPay' : 'เลขบัญชีไม่ถูกต้อง');
      return;
    }
    if (pin.length !== 6) {
      setError('กรอก PIN 6 หลักเพื่อยืนยัน');
      return;
    }
    const result = await addPayoutAccount({
      type,
      account_name: accountName.trim(),
      account_number: digits,
      ...(type === 'bank_transfer' ? { bank_name: bankName } : {}),
      pin,
    });
    if (result.success) {
      resultHaptic('success');
      onDone();
    } else {
      resultHaptic('error');
      setError(result.message);
    }
  };

  return (
    <Card3D padding={spacing.xl}>
      <FormHead icon="bank" title="เพิ่มบัญชีรับเงิน" />
      <View style={styles.chipRow}>
        <Chip label="บัญชีธนาคาร" icon="bank" selected={type === 'bank_transfer'} onPress={() => setType('bank_transfer')} />
        <Chip label="PromptPay" icon="device-mobile" selected={type === 'promptpay'} onPress={() => setType('promptpay')} />
      </View>

      {type === 'bank_transfer' && (
        <View style={styles.field}>
          <Text style={[typography.caption, styles.fieldLabel, { color: colors.textMuted }]}>ธนาคาร</Text>
          <View style={[styles.bankList, { backgroundColor: colors.inset, borderColor: colors.border }]}>
            {BANKS.map((bank, index) => {
              const selected = bankName === bank;
              return (
                <Pressable
                  key={bank}
                  onPress={() => {
                    selectionHaptic();
                    setBankName(bank);
                  }}
                  accessibilityRole="radio"
                  accessibilityState={{ checked: selected }}
                  accessibilityLabel={bank}
                  style={({ pressed }) => [
                    styles.bankRow,
                    index > 0 && { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.divider },
                    selected && { backgroundColor: colors.goldSoft },
                    pressed && styles.pressedDim,
                  ]}
                >
                  <Icon name="bank" size={18} color={selected ? colors.goldDeep : colors.textFaint} weight={selected ? 'fill' : 'regular'} />
                  <Text
                    style={[
                      typography.body,
                      styles.flex,
                      { color: colors.textStrong, fontWeight: selected ? '700' : '400' },
                    ]}
                  >
                    {bank}
                  </Text>
                  <Radio checked={selected} />
                </Pressable>
              );
            })}
          </View>
        </View>
      )}

      <Field label="ชื่อบัญชี" icon="user" value={accountName} onChangeText={setAccountName} placeholder="ชื่อ-นามสกุล ตามบัญชี" maxLength={100} />
      <Field
        label={type === 'promptpay' ? 'เบอร์โทร / เลขบัตรประชาชน (PromptPay)' : 'เลขบัญชี'}
        icon={type === 'promptpay' ? 'device-mobile' : 'credit-card'}
        value={accountNumber}
        onChangeText={(t) => setAccountNumber(t.replace(/[^0-9-]/g, '').slice(0, 30))}
        keyboardType="number-pad"
        placeholder={type === 'promptpay' ? '08x-xxx-xxxx' : 'xxx-x-xxxxx-x'}
      />
      <Field label="PIN กระเป๋าเงิน" icon="lock" value={pin} onChangeText={(t) => setPin(onlyDigits(t, 6))} keyboardType="number-pad" secureTextEntry maxLength={6} placeholder="••••••" />

      {!!error && <ErrorNote text={error} />}
      <Button3D title="บันทึกบัญชี" icon="check-circle" size="lg" fullWidth onPress={submit} style={styles.cta} />
      {onCancel && <Button3D title="ยกเลิก" variant="ghost" size="sm" onPress={onCancel} style={styles.secondary} />}
    </Card3D>
  );
};

// =====================================================
// หน้าหลัก
// =====================================================

export default function WalletWithdrawScreen() {
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);

  const [info, setInfo] = useState<WithdrawInfo | null>(null);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const [accountId, setAccountId] = useState<number | null>(null);
  const [amount, setAmount] = useState('');
  const [note, setNote] = useState('');
  const [preview, setPreview] = useState<WithdrawPreview | null>(null);
  const [previewLoading, setPreviewLoading] = useState(false);
  const [showAddAccount, setShowAddAccount] = useState(false);

  const [pinSheet, setPinSheet] = useState(false);
  const [pin, setPin] = useState('');
  const [pinError, setPinError] = useState<string | null>(null);

  const [history, setHistory] = useState<Withdrawal[]>([]);

  // ลบบัญชีรับเงิน (ยืนยันด้วย PIN)
  const [removeTarget, setRemoveTarget] = useState<PayoutAccount | null>(null);
  const [removePin, setRemovePin] = useState('');
  const [removeError, setRemoveError] = useState<string | null>(null);
  /** กันกดตั้งบัญชีหลัก/ลบซ้อนกัน */
  const accountBusyRef = useRef(false);

  const mountedRef = useRef(true);
  const previewReq = useRef(0);
  /** Idempotency-Key ต่อ (ยอด, บัญชี, หมายเหตุ) — กดซ้ำด้วยข้อมูลเดิมหลังเน็ตหลุดจะใช้คีย์เดิม */
  const idempotencyRef = useRef<{ signature: string; key: string } | null>(null);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // ---------- โหลดข้อมูล ----------
  /** โหลดคำขอถอนล่าสุด — คืนรายการหน้าแรก (null = โหลดไม่สำเร็จ/ออกจากหน้าแล้ว) */
  const loadHistory = useCallback(async (): Promise<Withdrawal[] | null> => {
    const result = await getWithdrawals(1);
    if (!mountedRef.current || !result.success) return null;
    const items = Array.isArray(result.data?.items) ? result.data.items : [];
    setHistory(items.slice(0, 5));
    return items;
  }, []);

  const loadInfo = useCallback(async (mode: 'initial' | 'refresh' = 'initial') => {
    if (!isAuthenticated) {
      setLoading(false);
      return;
    }
    if (mode === 'refresh') setRefreshing(true);
    const result = await getWithdrawInfo();
    if (!mountedRef.current) return;
    if (result.success) {
      // กันคีย์หายจาก server ไม่ให้หน้าจอพัง
      const normalized: WithdrawInfo = {
        ...result.data,
        balance: Number(result.data?.balance) || 0,
        pending_withdrawal_amount: Number(result.data?.pending_withdrawal_amount) || 0,
        limits: {
          min_amount: Number(result.data?.limits?.min_amount) || 0,
          max_amount: Number(result.data?.limits?.max_amount) || 0,
        },
        payment_methods: Array.isArray(result.data?.payment_methods) ? result.data.payment_methods : [],
      };
      setInfo(normalized);
      setLoadError(null);
      const methods = normalized.payment_methods;
      setAccountId((current) => {
        if (current && methods.some((m) => m.id === current)) return current;
        return (methods.find((m) => m.is_default) || methods[0])?.id ?? null;
      });
    } else if (!info) {
      setLoadError(result.message);
    }
    setLoading(false);
    setRefreshing(false);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isAuthenticated]);

  useEffect(() => {
    loadInfo('initial');
    loadHistory();
  }, [loadInfo, loadHistory]);

  // ---------- preview จาก server (debounce 500ms) ----------
  const amountNum = Number(amount) || 0;
  useEffect(() => {
    if (!info || amountNum <= 0) {
      setPreview(null);
      setPreviewLoading(false);
      return;
    }
    const req = ++previewReq.current;
    setPreviewLoading(true);
    const timer = setTimeout(async () => {
      const result = await previewWithdraw(amountNum);
      if (!mountedRef.current || req !== previewReq.current) return;
      setPreview(
        result.success
          ? { ...result.data, errors: Array.isArray(result.data?.errors) ? result.data.errors : [] }
          : { amount: amountNum, fee: 0, tax: 0, net_amount: 0, errors: [result.message] }
      );
      setPreviewLoading(false);
    }, 500);
    return () => clearTimeout(timer);
  }, [amountNum, info]);

  const selectedAccount: PayoutAccount | null = useMemo(
    () => info?.payment_methods.find((m) => m.id === accountId) || null,
    [info, accountId]
  );

  const canContinue =
    !!info &&
    !!selectedAccount &&
    amountNum > 0 &&
    !previewLoading &&
    !!preview &&
    preview.errors.length === 0;

  // ---------- ยืนยันด้วย PIN ----------
  const submitWithdraw = async () => {
    if (!selectedAccount || pin.length !== 6) {
      setPinError('กรอก PIN 6 หลัก');
      return;
    }
    setPinError(null);
    const noteText = note.trim().slice(0, 200);
    const signature = `${amountNum.toFixed(2)}|${selectedAccount.id}|${noteText}`;
    if (!idempotencyRef.current || idempotencyRef.current.signature !== signature) {
      idempotencyRef.current = { signature, key: newIdempotencyKey() };
    }
    // คำขอที่มีอยู่แล้วก่อนกด — ใช้แยกคำขอใหม่ตอนเน็ตหลุด/ตอบช้า
    const knownIds = new Set(history.map((w) => w.id));

    const result = await withdraw(
      {
        amount: amountNum,
        pin,
        payment_method_id: selectedAccount.id,
        ...(noteText ? { note: noteText } : {}),
      },
      idempotencyRef.current.key
    );
    if (!mountedRef.current) return;

    if (result.success) {
      idempotencyRef.current = null;
      resultHaptic('success');
      setPinSheet(false);
      setPin('');
      setAmount('');
      setNote('');
      Alert.alert(
        'ส่งคำขอถอนเงินแล้ว',
        `ยอดที่จะได้รับ ${formatBaht(result.data.net_amount, { decimals: 2 })}\nสถานะ: ${result.data.status_label}`,
        [{ text: 'ตกลง' }]
      );
      loadInfo('refresh');
      loadHistory();
      return;
    }

    resultHaptic('error');
    switch (result.code) {
      case 'INVALID_PIN': {
        const left = Number(result.data?.attempts_remaining);
        setPinError(Number.isFinite(left) ? `${result.message} (ลองได้อีก ${left} ครั้ง)` : result.message);
        setPin('');
        return;
      }
      case 'PIN_NOT_SET':
      case 'PAYMENT_METHOD_REQUIRED':
      case 'WALLET_LOCKED':
      case 'KYC_REQUIRED':
        setPinSheet(false);
        setPin('');
        Alert.alert('ถอนเงิน', result.message);
        loadInfo('refresh');
        return;
      case 'TIMEOUT':
      case 'NETWORK_ERROR':
      case 'REQUEST_IN_PROGRESS': {
        // ไม่รู้ว่า server สร้างคำขอไปแล้วหรือยัง → ห้ามชวน "ลองใหม่" ทันที ตรวจรายการล่าสุดก่อน
        setPinSheet(false);
        setPin('');
        loadInfo('refresh');
        const latest = await loadHistory();
        if (!mountedRef.current) return;
        const created = latest?.find(
          (w) =>
            !knownIds.has(w.id) &&
            Math.abs((Number(w.amount) || 0) - amountNum) < 0.005 &&
            (w.status === 'pending' || w.status === 'processing' || w.status === 'approved')
        );
        if (created) {
          idempotencyRef.current = null;
          setAmount('');
          setNote('');
          Alert.alert(
            'ส่งคำขอถอนเงินแล้ว',
            `พบคำขอถอน ${formatBaht(created.amount, { decimals: 2 })} ในรายการล่าสุดแล้ว ไม่ต้องกดซ้ำนะ`,
            [{ text: 'ตกลง' }]
          );
        } else {
          Alert.alert(
            'ยังไม่แน่ใจว่าถอนเงินสำเร็จไหม',
            'ตรวจสอบรายการล่าสุดก่อนลองใหม่นะ ถ้ายังไม่มีคำขอนี้ ค่อยกดถอนเงินอีกครั้ง',
            [{ text: 'ตกลง' }]
          );
        }
        return;
      }
      default:
        setPinSheet(false);
        setPin('');
        Alert.alert('ถอนเงินไม่สำเร็จ', result.message);
    }
  };

  const handleCancelWithdrawal = (item: Withdrawal) => {
    Alert.alert('ยกเลิกคำขอถอนเงิน', `ยกเลิกคำขอ ${formatBaht(item.amount, { decimals: 2 })} ใช่ไหม?`, [
      { text: 'ไม่ยกเลิก', style: 'cancel' },
      {
        text: 'ยกเลิกคำขอ',
        style: 'destructive',
        onPress: async () => {
          const result = await cancelWithdrawal(item.id);
          if (!mountedRef.current) return;
          if (result.success) {
            loadInfo('refresh');
            loadHistory();
          } else {
            Alert.alert('ยกเลิกไม่สำเร็จ', result.message);
          }
        },
      },
    ]);
  };

  // ---------- จัดการบัญชีรับเงิน ----------
  const accountLabel = (m: PayoutAccount) =>
    `${m.type === 'promptpay' ? 'PromptPay' : m.bank_name || 'บัญชีธนาคาร'} ••${m.account_last4}`;

  const doSetDefault = async (m: PayoutAccount) => {
    if (accountBusyRef.current) return;
    accountBusyRef.current = true;
    const result = await setDefaultPayoutAccount(m.id);
    accountBusyRef.current = false;
    if (!mountedRef.current) return;
    if (result.success) {
      resultHaptic('success');
      // แสดงผลทันที แล้วโหลดใหม่ให้ตรง server
      setInfo((prev) =>
        prev ? { ...prev, payment_methods: prev.payment_methods.map((x) => ({ ...x, is_default: x.id === m.id })) } : prev
      );
      loadInfo('refresh');
    } else {
      resultHaptic('error');
      Alert.alert('ตั้งบัญชีหลักไม่สำเร็จ', result.message);
      if (result.code === 'NOT_FOUND') loadInfo('refresh');
    }
  };

  const openRemove = (m: PayoutAccount) => {
    setRemovePin('');
    setRemoveError(null);
    setRemoveTarget(m);
  };

  const submitRemove = async () => {
    const target = removeTarget;
    if (!target || accountBusyRef.current) return;
    if (removePin.length !== 6) {
      setRemoveError('กรอก PIN 6 หลัก');
      return;
    }
    accountBusyRef.current = true;
    setRemoveError(null);
    const result = await deletePayoutAccount(target.id, removePin);
    accountBusyRef.current = false;
    if (!mountedRef.current) return;

    if (result.success) {
      resultHaptic('success');
      setRemoveTarget(null);
      setRemovePin('');
      Alert.alert('ลบบัญชีแล้ว', `ลบ ${accountLabel(target)} ออกจากบัญชีรับเงินแล้ว`);
      loadInfo('refresh');
      return;
    }

    resultHaptic('error');
    if (result.code === 'INVALID_PIN') {
      const left = Number(result.data?.attempts_remaining);
      setRemoveError(Number.isFinite(left) ? `${result.message} (ลองได้อีก ${left} ครั้ง)` : result.message);
      setRemovePin('');
      return;
    }
    setRemoveTarget(null);
    setRemovePin('');
    Alert.alert('ลบบัญชีไม่สำเร็จ', result.message);
    if (result.code === 'WALLET_LOCKED' || result.code === 'NOT_FOUND' || result.code === 'PIN_NOT_SET') loadInfo('refresh');
  };

  const manageAccount = (m: PayoutAccount) => {
    const buttons: Array<{ text: string; style?: 'cancel' | 'destructive'; onPress?: () => void }> = [];
    if (!m.is_default) {
      buttons.push({
        text: 'ตั้งเป็นบัญชีหลัก',
        onPress: () =>
          Alert.alert('ตั้งเป็นบัญชีหลัก?', `${accountLabel(m)} จะถูกเลือกไว้ก่อนทุกครั้งที่ถอนเงิน`, [
            { text: 'ยกเลิก', style: 'cancel' },
            { text: 'ตั้งเป็นบัญชีหลัก', onPress: () => doSetDefault(m) },
          ]),
      });
    }
    buttons.push({ text: 'ลบบัญชีนี้', style: 'destructive', onPress: () => openRemove(m) });
    buttons.push({ text: 'ปิด', style: 'cancel' });
    Alert.alert(accountLabel(m), `${m.account_name}${m.is_default ? ' · บัญชีหลัก' : ''}`, buttons);
  };

  // ---------- render ----------
  if (!isAuthenticated) {
    return (
      <Screen title="ถอนเงิน" scroll={false}>
        <EmptyState art="wallet" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  const renderContent = () => {
    if (loading) {
      return <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />;
    }
    if (!info) {
      return <EmptyState compact variant="error" message={loadError || undefined} onAction={() => loadInfo('initial')} />;
    }

    if (!info.kyc_verified) {
      return (
        <EmptyState
          compact
          icon="identification-card"
          title="ยืนยันตัวตนก่อนถอนเงิน"
          message="เพื่อความปลอดภัย ต้องยืนยันตัวตน (KYC) ก่อนถอนเงินครั้งแรก"
          actionLabel="ไปยืนยันตัวตน"
          onAction={() => router.push('/kyc')}
        />
      );
    }

    if (info.wallet_locked) {
      return (
        <EmptyState
          compact
          variant="error"
          icon="lock"
          title="กระเป๋าเงินถูกล็อกชั่วคราว"
          message="ใส่ PIN ผิดหลายครั้ง ลองใหม่อีกครั้งภายหลังนะ"
          actionLabel="ลองใหม่"
          onAction={() => loadInfo('refresh')}
        />
      );
    }

    if (!info.has_pin) {
      return <PinSetupCard onDone={() => loadInfo('refresh')} />;
    }

    if (info.payment_methods.length === 0 || showAddAccount) {
      return (
        <AddAccountCard
          onDone={() => {
            setShowAddAccount(false);
            loadInfo('refresh');
          }}
          onCancel={info.payment_methods.length > 0 ? () => setShowAddAccount(false) : undefined}
        />
      );
    }

    const quickAmounts = [info.limits.min_amount, 500, 1000, info.balance]
      .map((v) => Math.floor(Number(v) || 0))
      .filter((v, i, arr) => v > 0 && v <= info.balance && arr.indexOf(v) === i);

    return (
      <>
        {/* ยอดที่ถอนได้ — การ์ดน้ำเงินแบบบัตรโลหะ */}
        <NavyCard>
          <View style={styles.cardHeadRow}>
            <Icon name="wallet" size={18} color={colors.goldLight} weight="fill" />
            <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>ยอดในกระเป๋าเงิน</Text>
          </View>
          <MoneyText text={formatBaht(info.balance, { decimals: 2 })} color={colors.goldLight} size={36} style={styles.balance} />
          {info.pending_withdrawal_amount > 0 && (
            <OnHeaderProvider value>
              <Pill
                label={`รอโอน ${formatBaht(info.pending_withdrawal_amount, { decimals: 2 })}`}
                tone="warning"
                icon="hourglass"
                style={styles.pendingPill}
              />
            </OnHeaderProvider>
          )}
          <View style={[styles.limitsRow, { borderTopColor: colors.headerGlassBorder }]}>
            <Icon name="info" size={14} color={colors.onHeaderMuted} />
            <Text style={[typography.micro, styles.flex, { color: colors.onHeaderMuted }]}>
              ถอนได้ครั้งละ {formatBaht(info.limits.min_amount)} – {formatBaht(info.limits.max_amount)}
            </Text>
          </View>
        </NavyCard>

        {/* บัญชีรับเงิน */}
        <SectionHeader
          title="โอนเข้าบัญชี"
          actionLabel={info.payment_methods.length < 5 ? 'เพิ่มบัญชี' : undefined}
          onAction={() => setShowAddAccount(true)}
          style={styles.sectionHeader}
        />
        <Card3D padding={0} contentStyle={styles.listCard}>
          {info.payment_methods.map((m, index) => {
            const selected = m.id === accountId;
            // แถว = พื้นที่เลือกบัญชี (วิทยุ) + ปุ่มจัดการแยกกัน — ถ้าซ้อนปุ่มในปุ่ม VoiceOver จะกดปุ่มข้างในไม่ได้
            return (
              <View
                key={m.id}
                style={[
                  styles.accountRowWrap,
                  index > 0 && { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.divider },
                  selected && { backgroundColor: colors.goldSoft },
                ]}
              >
                <Pressable
                  onPress={() => {
                    selectionHaptic();
                    setAccountId(m.id);
                  }}
                  accessibilityRole="radio"
                  accessibilityState={{ checked: selected }}
                  accessibilityLabel={`${m.bank_name || 'PromptPay'} ${m.account_name} ลงท้าย ${m.account_last4}${m.is_default ? ' บัญชีหลัก' : ''}`}
                  style={({ pressed }) => [styles.accountRow, pressed && styles.pressedDim]}
                >
                  <IconTile icon={m.type === 'promptpay' ? 'device-mobile' : 'bank'} tone={selected ? 'gold' : 'navy'} />
                  <View style={styles.flex}>
                    <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
                      {m.type === 'promptpay' ? 'PromptPay' : m.bank_name || 'บัญชีธนาคาร'}
                    </Text>
                    <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
                      {m.account_name} · {m.account_number_masked}
                    </Text>
                  </View>
                  {m.is_default && <Pill label="หลัก" tone="gold" />}
                  <Radio checked={selected} />
                </Pressable>
                <Pressable
                  onPress={() => manageAccount(m)}
                  accessibilityRole="button"
                  accessibilityLabel={`จัดการบัญชี ${accountLabel(m)} ตั้งเป็นบัญชีหลักหรือลบ`}
                  hitSlop={6}
                  style={({ pressed }) => [styles.manageButton, pressed && styles.pressedDim]}
                >
                  <Icon name="dots-three-vertical" size={20} color={colors.textMuted} weight="bold" />
                </Pressable>
              </View>
            );
          })}
        </Card3D>

        {/* จำนวนเงิน */}
        <SectionHeader title="จำนวนเงิน" style={styles.sectionHeader} />
        <Card3D padding={spacing.lg}>
          <MoneyInput
            value={amount}
            onChangeText={(t) => setAmount(sanitizeAmount(t))}
            keyboardType="decimal-pad"
            placeholder="0"
            accessibilityLabel="จำนวนเงินที่ต้องการถอน"
          />
          {quickAmounts.length > 0 && (
            <View style={styles.chipRow}>
              {quickAmounts.map((v) => (
                <Chip
                  key={v}
                  size="sm"
                  label={v === Math.floor(info.balance) ? 'ถอนทั้งหมด' : formatBaht(v)}
                  selected={amountNum === v}
                  onPress={() => setAmount(String(v))}
                />
              ))}
            </View>
          )}

          <Field label="หมายเหตุ (ไม่บังคับ)" icon="note-pencil" value={note} onChangeText={setNote} maxLength={200} placeholder="เช่น ถอนค่าส่งของสัปดาห์นี้" />

          {amountNum > 0 && (
            <View style={[styles.previewBox, { backgroundColor: colors.inset, borderColor: colors.border }]}>
              {previewLoading || !preview ? (
                <ActivityIndicator color={colors.gold} />
              ) : preview.errors.length > 0 ? (
                preview.errors.map((e) => (
                  <View key={e} style={styles.previewError}>
                    <Icon name="warning-circle" size={16} color={colors.danger} />
                    <Text style={[typography.bodySm, styles.flex, { color: colors.danger }]}>{e}</Text>
                  </View>
                ))
              ) : (
                <>
                  <InfoRow label="ค่าธรรมเนียม">
                    <PriceText amount={preview.fee} size="sm" decimals={2} bold={false} />
                  </InfoRow>
                  {preview.tax > 0 && (
                    <InfoRow label="หักภาษี ณ ที่จ่าย">
                      <PriceText amount={preview.tax} size="sm" decimals={2} bold={false} />
                    </InfoRow>
                  )}
                  <InfoRow label="ยอดที่จะได้รับ" strong style={[styles.previewTotal, { borderTopColor: colors.divider }]}>
                    <PriceText amount={preview.net_amount} size="lg" tone="success" decimals={2} />
                  </InfoRow>
                </>
              )}
            </View>
          )}

          <Button3D
            title="ถอนเงิน"
            icon="bank"
            size="lg"
            fullWidth
            disabled={!canContinue}
            onPress={() => {
              setPin('');
              setPinError(null);
              setPinSheet(true);
            }}
            style={styles.cta}
          />
        </Card3D>

        {/* ประวัติล่าสุด */}
        {history.length > 0 && (
          <>
            <SectionHeader title="คำขอล่าสุด" style={styles.sectionHeader} />
            <Card3D padding={0} contentStyle={styles.listCard}>
              {history.map((w, index) => {
                const tone = WITHDRAWAL_TONE[w.status] || 'neutral';
                return (
                  <View
                    key={w.id}
                    style={[
                      styles.historyRow,
                      index > 0 && { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.divider },
                    ]}
                  >
                    <View style={styles.historyTop}>
                      <IconTile icon="bank" tone={TILE_TONE[tone]} />
                      <View style={styles.flex}>
                        <PriceText amount={w.amount} size="md" tone="strong" decimals={2} />
                        <Text numberOfLines={1} style={[typography.micro, { color: colors.textFaint }]}>
                          {new Date(w.created_at).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })}
                          {w.payment_method ? ` · ${w.payment_method.bank_name || 'PromptPay'} ••${w.payment_method.account_last4}` : ''}
                        </Text>
                        {!!w.rejection_reason && (
                          <Text style={[typography.micro, { color: colors.danger }]}>{w.rejection_reason}</Text>
                        )}
                      </View>
                      <Pill label={w.status_label} tone={tone} />
                    </View>
                    {w.can_cancel && (
                      <Pressable
                        onPress={() => handleCancelWithdrawal(w)}
                        accessibilityRole="button"
                        hitSlop={8}
                        style={({ pressed }) => [styles.cancelLink, pressed && styles.pressedDim]}
                      >
                        <Icon name="x-circle" size={15} color={colors.danger} />
                        <Text style={[typography.caption, styles.cancelText, { color: colors.danger }]}>ยกเลิกคำขอ</Text>
                      </Pressable>
                    )}
                  </View>
                );
              })}
            </Card3D>
          </>
        )}
      </>
    );
  };

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <Screen
        title="ถอนเงิน"
        subtitle="โอนเข้าบัญชีธนาคารหรือ PromptPay"
        refreshing={refreshing}
        onRefresh={() => {
          loadInfo('refresh');
          loadHistory();
        }}
      >
        {renderContent()}
      </Screen>

      <ConsentSheet
        visible={pinSheet}
        icon="lock-key"
        title="ยืนยันด้วย PIN"
        description={
          selectedAccount && preview
            ? `ถอน ${formatBaht(amountNum, { decimals: 2 })} เข้า ${selectedAccount.type === 'promptpay' ? 'PromptPay' : selectedAccount.bank_name || 'บัญชีธนาคาร'} ••${selectedAccount.account_last4} ได้รับจริง ${formatBaht(preview.net_amount, { decimals: 2 })}`
            : undefined
        }
        acceptLabel="ยืนยันถอนเงิน"
        declineLabel="ยกเลิก"
        acceptDisabled={pin.length !== 6}
        onAccept={submitWithdraw}
        onDecline={() => {
          setPinSheet(false);
          setPin('');
        }}
      >
        <Field
          label="PIN 6 หลัก"
          value={pin}
          onChangeText={(t) => setPin(onlyDigits(t, 6))}
          keyboardType="number-pad"
          secureTextEntry
          maxLength={6}
          placeholder="••••••"
          autoFocus
          style={styles.pinInput}
        />
        {!!pinError && <ErrorNote text={pinError} />}
      </ConsentSheet>

      <ConsentSheet
        visible={!!removeTarget}
        icon="trash"
        title="ลบบัญชีรับเงิน"
        description={
          removeTarget
            ? `ลบ ${accountLabel(removeTarget)} (${removeTarget.account_name}) ออกจากบัญชีรับเงิน${removeTarget.is_default ? ' — บัญชีนี้เป็นบัญชีหลัก ระบบจะตั้งบัญชีอื่นเป็นหลักแทน' : ''}`
            : undefined
        }
        acceptLabel="ลบบัญชี"
        acceptVariant="danger"
        declineLabel="ไม่ลบ"
        acceptDisabled={removePin.length !== 6}
        onAccept={submitRemove}
        onDecline={() => {
          setRemoveTarget(null);
          setRemovePin('');
          setRemoveError(null);
        }}
        footnote="บัญชีที่มีคำขอถอนเงินค้างอยู่ ลบได้หลังโอนเงินเสร็จ"
      >
        <Field
          label="PIN กระเป๋าเงิน 6 หลัก"
          value={removePin}
          onChangeText={(t) => {
            setRemovePin(onlyDigits(t, 6));
            setRemoveError(null);
          }}
          keyboardType="number-pad"
          secureTextEntry
          maxLength={6}
          placeholder="••••••"
          autoFocus
          style={styles.pinInput}
        />
        {!!removeError && <ErrorNote text={removeError} />}
      </ConsentSheet>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  field: {
    marginTop: spacing.md,
    gap: spacing.xs,
  },
  fieldLabel: {
    fontWeight: '600',
  },
  inputBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderWidth: 1,
    borderRadius: 14,
    paddingHorizontal: spacing.md,
    minHeight: 50,
  },
  input: {
    flex: 1,
    paddingVertical: spacing.md,
    fontSize: 16,
  },
  pinInput: {
    fontSize: 22,
    fontWeight: '700',
    letterSpacing: 8,
    textAlign: 'center',
  },
  errorNote: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    borderRadius: radii.sm,
    padding: spacing.md,
    marginTop: spacing.md,
  },
  cta: {
    marginTop: spacing.lg,
  },
  secondary: {
    marginTop: spacing.sm,
    alignSelf: 'center',
  },
  formHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginBottom: spacing.xs,
  },
  chipRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  bankList: {
    borderWidth: 1,
    borderRadius: radii.md,
    overflow: 'hidden',
  },
  bankRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    minHeight: 50,
    paddingHorizontal: spacing.md,
  },
  radio: {
    width: 22,
    height: 22,
    borderRadius: 11,
    borderWidth: 1.5,
    alignItems: 'center',
    justifyContent: 'center',
  },
  pressedDim: {
    opacity: 0.7,
  },

  // การ์ดยอดเงิน
  cardHeadRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
  },
  balance: {
    marginTop: spacing.xs,
  },
  pendingPill: {
    marginTop: spacing.xs,
  },
  limitsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: StyleSheet.hairlineWidth,
  },
  sectionHeader: {
    marginTop: spacing.xxl,
  },

  // แถวในการ์ด
  listCard: {
    overflow: 'hidden',
  },
  accountRowWrap: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  accountRow: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: 14,
    paddingLeft: 14,
    paddingRight: spacing.xs,
  },
  manageButton: {
    width: 44,
    height: 44,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: spacing.xs,
  },
  previewBox: {
    marginTop: spacing.lg,
    borderWidth: 1,
    borderRadius: radii.md,
    padding: spacing.md,
    gap: spacing.xxs,
  },
  previewError: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.xs,
  },
  previewTotal: {
    borderTopWidth: StyleSheet.hairlineWidth,
    paddingTop: spacing.sm,
    marginTop: spacing.xs,
  },
  historyRow: {
    padding: 14,
  },
  historyTop: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  cancelLink: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: spacing.sm,
    alignSelf: 'flex-end',
  },
  cancelText: {
    fontWeight: '700',
  },
});
