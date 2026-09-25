/**
 * ถอนเงิน — ใช้ API ถอนเงินชุดเดียวกับเว็บ (E2: WithdrawalService + PIN)
 *
 * ลำดับ: GET /wallet/withdraw/info → (ยังไม่ยืนยันตัวตน: ไป KYC) → (ยังไม่มี PIN: ตั้ง PIN)
 *        → (ยังไม่มีบัญชี: เพิ่มบัญชี) → กรอกยอด (preview จาก server) → ใส่ PIN → POST /wallet/withdraw
 *
 * ค่าธรรมเนียม / ภาษี / ขั้นต่ำ มาจาก server ทั้งหมด — ห้ามฮาร์ดโค้ดในแอป
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { router } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import {
  addPayoutAccount,
  cancelWithdrawal,
  getWithdrawInfo,
  getWithdrawals,
  previewWithdraw,
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
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  formatBaht,
  resultHaptic,
} from '@/components/ui';
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

/** เก็บเฉพาะตัวเลข + ทศนิยม 2 ตำแหน่ง */
const sanitizeAmount = (text: string): string => {
  const cleaned = text.replace(/[^0-9.]/g, '');
  const [intPart, ...rest] = cleaned.split('.');
  if (rest.length === 0) return intPart.slice(0, 9);
  return `${intPart.slice(0, 9)}.${rest.join('').slice(0, 2)}`;
};

const onlyDigits = (text: string, max: number) => text.replace(/\D/g, '').slice(0, max);

// =====================================================
// ช่องกรอกแบบธีม
// =====================================================

const Field = ({
  label,
  hint,
  ...inputProps
}: React.ComponentProps<typeof TextInput> & { label: string; hint?: string }) => {
  const { colors } = useTheme();
  return (
    <View style={styles.field}>
      <Text style={[typography.caption, { color: colors.textMuted }]}>{label}</Text>
      <TextInput
        placeholderTextColor={colors.textFaint}
        {...inputProps}
        style={[
          styles.input,
          { backgroundColor: colors.inset, borderColor: colors.border, color: colors.textStrong },
          inputProps.style,
        ]}
        accessibilityLabel={label}
      />
      {!!hint && <Text style={[typography.micro, { color: colors.textFaint }]}>{hint}</Text>}
    </View>
  );
};

// =====================================================
// ตั้ง PIN
// =====================================================

const PinSetupCard = ({ onDone }: { onDone: () => void }) => {
  const { colors } = useTheme();
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
    <Card3D gradientBorder padding={spacing.xl}>
      <Text style={[typography.h2, { color: colors.textStrong }]}>🔐 ตั้งรหัส PIN กระเป๋าเงิน</Text>
      <Text style={[typography.bodySm, styles.lead, { color: colors.textMuted }]}>
        ใช้ PIN 6 หลักยืนยันทุกครั้งที่ถอนเงินหรือแก้ไขบัญชีรับเงิน
      </Text>
      <Field label="PIN 6 หลัก" value={pin} onChangeText={(t) => setPin(onlyDigits(t, 6))} keyboardType="number-pad" secureTextEntry maxLength={6} placeholder="••••••" />
      <Field label="ยืนยัน PIN อีกครั้ง" value={confirm} onChangeText={(t) => setConfirm(onlyDigits(t, 6))} keyboardType="number-pad" secureTextEntry maxLength={6} placeholder="••••••" />
      {!!error && <Text style={[typography.bodySm, styles.error, { color: colors.danger }]}>{error}</Text>}
      <Button3D title="บันทึก PIN" icon="✅" size="lg" fullWidth onPress={submit} style={styles.cta} />
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
    <Card3D gradientBorder padding={spacing.xl}>
      <Text style={[typography.h2, { color: colors.textStrong }]}>🏦 เพิ่มบัญชีรับเงิน</Text>
      <View style={styles.chipRow}>
        <Chip label="บัญชีธนาคาร" icon="🏦" selected={type === 'bank_transfer'} onPress={() => setType('bank_transfer')} />
        <Chip label="PromptPay" icon="📱" selected={type === 'promptpay'} onPress={() => setType('promptpay')} />
      </View>

      {type === 'bank_transfer' && (
        <View style={styles.field}>
          <Text style={[typography.caption, { color: colors.textMuted }]}>ธนาคาร</Text>
          <View style={styles.bankWrap}>
            {BANKS.map((bank) => (
              <Chip key={bank} label={bank} size="sm" selected={bankName === bank} onPress={() => setBankName(bank)} />
            ))}
          </View>
        </View>
      )}

      <Field label="ชื่อบัญชี" value={accountName} onChangeText={setAccountName} placeholder="ชื่อ-นามสกุล ตามบัญชี" maxLength={100} />
      <Field
        label={type === 'promptpay' ? 'เบอร์โทร / เลขบัตรประชาชน (PromptPay)' : 'เลขบัญชี'}
        value={accountNumber}
        onChangeText={(t) => setAccountNumber(t.replace(/[^0-9-]/g, '').slice(0, 30))}
        keyboardType="number-pad"
        placeholder={type === 'promptpay' ? '08x-xxx-xxxx' : 'xxx-x-xxxxx-x'}
      />
      <Field label="PIN กระเป๋าเงิน" value={pin} onChangeText={(t) => setPin(onlyDigits(t, 6))} keyboardType="number-pad" secureTextEntry maxLength={6} placeholder="••••••" />

      {!!error && <Text style={[typography.bodySm, styles.error, { color: colors.danger }]}>{error}</Text>}
      <Button3D title="บันทึกบัญชี" icon="✅" size="lg" fullWidth onPress={submit} style={styles.cta} />
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

  // ---------- render ----------
  if (!isAuthenticated) {
    return (
      <Screen title="ถอนเงิน" scroll={false}>
        <EmptyState icon="🔐" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
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
          icon="🪪"
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
          icon="🔒"
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
        {/* ยอดที่ถอนได้ */}
        <Card3D gradientBorder padding={spacing.lg}>
          <Text style={[typography.caption, { color: colors.textMuted }]}>ยอดในกระเป๋าเงิน</Text>
          <PriceText amount={info.balance} size="xl" tone="strong" decimals={2} />
          {info.pending_withdrawal_amount > 0 && (
            <Text style={[typography.caption, { color: colors.warning }]}>
              รอโอน {formatBaht(info.pending_withdrawal_amount, { decimals: 2 })}
            </Text>
          )}
          <Text style={[typography.micro, styles.limits, { color: colors.textFaint }]}>
            ถอนได้ครั้งละ {formatBaht(info.limits.min_amount)} – {formatBaht(info.limits.max_amount)}
          </Text>
        </Card3D>

        {/* บัญชีรับเงิน */}
        <SectionHeader
          title="โอนเข้าบัญชี"
          actionLabel={info.payment_methods.length < 5 ? 'เพิ่มบัญชี' : undefined}
          onAction={() => setShowAddAccount(true)}
          style={styles.sectionHeader}
        />
        {info.payment_methods.map((m) => {
          const selected = m.id === accountId;
          return (
            <Card3D
              key={m.id}
              onPress={() => setAccountId(m.id)}
              gradientBorder={selected}
              padding={spacing.md}
              radius={radii.lg}
              shadow="sm"
              style={styles.accountCard}
              accessibilityLabel={`${m.bank_name || 'PromptPay'} ${m.account_name} ลงท้าย ${m.account_last4}`}
            >
              <View style={styles.accountRow}>
                <Text style={styles.accountIcon}>{m.type === 'promptpay' ? '📱' : '🏦'}</Text>
                <View style={styles.flex}>
                  <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>
                    {m.type === 'promptpay' ? 'PromptPay' : m.bank_name || 'บัญชีธนาคาร'}
                  </Text>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>
                    {m.account_name} · {m.account_number_masked}
                  </Text>
                </View>
                {m.is_default && <Pill label="หลัก" tone="gold" />}
                <Text style={[styles.radio, { color: selected ? colors.goldDeep : colors.textFaint }]}>
                  {selected ? '◉' : '○'}
                </Text>
              </View>
            </Card3D>
          );
        })}

        {/* จำนวนเงิน */}
        <SectionHeader title="จำนวนเงิน" style={styles.sectionHeader} />
        <Card3D padding={spacing.lg}>
          <View style={[styles.amountBox, { backgroundColor: colors.inset, borderColor: colors.border }]}>
            <Text style={[typography.h1, { color: colors.goldDeep }]}>฿</Text>
            <TextInput
              value={amount}
              onChangeText={(t) => setAmount(sanitizeAmount(t))}
              keyboardType="decimal-pad"
              placeholder="0"
              placeholderTextColor={colors.textFaint}
              style={[styles.amountInput, { color: colors.textStrong }]}
              accessibilityLabel="จำนวนเงินที่ต้องการถอน"
            />
          </View>
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

          <Field label="หมายเหตุ (ไม่บังคับ)" value={note} onChangeText={setNote} maxLength={200} placeholder="เช่น ถอนค่าส่งของสัปดาห์นี้" />

          {amountNum > 0 && (
            <View style={[styles.previewBox, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              {previewLoading || !preview ? (
                <ActivityIndicator color={colors.gold} />
              ) : preview.errors.length > 0 ? (
                preview.errors.map((e) => (
                  <Text key={e} style={[typography.bodySm, { color: colors.danger }]}>• {e}</Text>
                ))
              ) : (
                <>
                  <View style={styles.previewRow}>
                    <Text style={[typography.bodySm, { color: colors.textMuted }]}>ค่าธรรมเนียม</Text>
                    <PriceText amount={preview.fee} size="sm" decimals={2} bold={false} />
                  </View>
                  {preview.tax > 0 && (
                    <View style={styles.previewRow}>
                      <Text style={[typography.bodySm, { color: colors.textMuted }]}>หักภาษี ณ ที่จ่าย</Text>
                      <PriceText amount={preview.tax} size="sm" decimals={2} bold={false} />
                    </View>
                  )}
                  <View style={[styles.previewRow, styles.previewTotal, { borderTopColor: colors.divider }]}>
                    <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>ยอดที่จะได้รับ</Text>
                    <PriceText amount={preview.net_amount} size="lg" tone="success" decimals={2} />
                  </View>
                </>
              )}
            </View>
          )}

          <Button3D
            title="ถอนเงิน"
            icon="🏦"
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
            {history.map((w) => (
              <Card3D key={w.id} variant="flat" padding={spacing.md} radius={radii.md} style={styles.historyCard}>
                <View style={styles.accountRow}>
                  <View style={styles.flex}>
                    <PriceText amount={w.amount} size="md" tone="strong" decimals={2} />
                    <Text style={[typography.micro, { color: colors.textFaint }]}>
                      {new Date(w.created_at).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })}
                      {w.payment_method ? ` · ${w.payment_method.bank_name || 'PromptPay'} ••${w.payment_method.account_last4}` : ''}
                    </Text>
                    {!!w.rejection_reason && (
                      <Text style={[typography.micro, { color: colors.danger }]}>{w.rejection_reason}</Text>
                    )}
                  </View>
                  <Pill label={w.status_label} tone={WITHDRAWAL_TONE[w.status] || 'neutral'} />
                </View>
                {w.can_cancel && (
                  <Pressable
                    onPress={() => handleCancelWithdrawal(w)}
                    accessibilityRole="button"
                    hitSlop={8}
                    style={styles.cancelLink}
                  >
                    <Text style={[typography.caption, { color: colors.danger, fontWeight: '700' }]}>ยกเลิกคำขอ</Text>
                  </Pressable>
                )}
              </Card3D>
            ))}
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
        icon="🔐"
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
        />
        {!!pinError && <Text style={[typography.bodySm, styles.error, { color: colors.danger }]}>{pinError}</Text>}
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
  lead: {
    marginTop: spacing.xs,
  },
  field: {
    marginTop: spacing.md,
    gap: spacing.xs,
  },
  input: {
    borderWidth: 1,
    borderRadius: radii.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
    fontSize: 16,
  },
  error: {
    marginTop: spacing.md,
  },
  cta: {
    marginTop: spacing.lg,
  },
  secondary: {
    marginTop: spacing.sm,
    alignSelf: 'center',
  },
  chipRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  bankWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.xs,
  },
  limits: {
    marginTop: spacing.xs,
  },
  sectionHeader: {
    marginTop: spacing.xl,
  },
  accountCard: {
    marginBottom: spacing.sm,
  },
  accountRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  accountIcon: {
    fontSize: 24,
  },
  radio: {
    fontSize: 20,
  },
  amountBox: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderRadius: radii.lg,
    paddingHorizontal: spacing.lg,
    gap: spacing.sm,
  },
  amountInput: {
    flex: 1,
    fontSize: 30,
    fontWeight: '800',
    paddingVertical: spacing.md,
  },
  previewBox: {
    marginTop: spacing.lg,
    borderWidth: 1,
    borderRadius: radii.md,
    padding: spacing.md,
    gap: spacing.xs,
  },
  previewRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  previewTotal: {
    borderTopWidth: 1,
    paddingTop: spacing.sm,
    marginTop: spacing.xs,
  },
  historyCard: {
    marginBottom: spacing.sm,
  },
  cancelLink: {
    marginTop: spacing.sm,
    alignSelf: 'flex-end',
  },
});
