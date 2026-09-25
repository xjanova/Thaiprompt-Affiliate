/**
 * หน้าเติมเงิน (Top-up) — Native Payment Flow · ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * Features:
 * - ปุ่มจำนวนเงินด่วน: 100, 300, 500, 1000, 2000, 5000, 10000
 * - กรอกจำนวนเงินเอง (100-100,000 บาท) ตัวเลขใหญ่
 * - เลือกวิธีชำระเงิน (PLAY-18: ในแอปเหลือ PromptPay/QR เท่านั้น)
 * - ชำระเงินผ่าน API โดยตรง (ไม่ต้องเปิดเว็บ)
 * - แสดง QR Code (การ์ดขาวกรอบน้ำเงิน-ทอง) / ข้อมูลโอนเงิน ภายในแอพ
 * - แถบขั้นตอน: จำนวนเงิน → วิธีชำระ → ชำระเงิน → เสร็จสิ้น · ปุ่มหลักลอยท้ายจอ
 */

import { SvgXml } from 'react-native-svg';
import React, { useState, useEffect, useCallback, useRef } from 'react';
import {
  View,
  ScrollView,
  Pressable,
  StyleSheet,
  Alert,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Image,
  Linking,
} from 'react-native';
import { Text } from '@/components/ui/Text';
import { LinearGradient } from 'expo-linear-gradient';
import { Image as BrandImage } from 'expo-image';
import { useRouter } from 'expo-router';
import * as Clipboard from 'expo-clipboard';
import { useAuthStore } from '@/stores/authStore';
import { resolveQrSource } from '@/utils/qrSource';
import {
  getWallet,
  getDepositMethods,
  initializeWalletTopup,
  checkPaymentStatus,
  PaymentMethod,
  PaymentTransaction,
} from '@/services/api';
import { formatCurrency } from '@/constants';
import {
  Button3D,
  Card3D,
  Icon,
  Pill,
  Screen,
  SectionHeader,
  usePressGuard,
  type IconName,
} from '@/components/ui';
import {
  ActionBar,
  IconTile,
  InfoRow,
  MoneyInput,
  MoneyText,
  NavyCard,
  StepTrack,
  type TileTone,
} from '@/components/wallet/WalletKit';
import { useTheme, LIGHT_THEME, radii, shadowStyle, spacing, typography, withAlpha } from '@/theme';

// จำนวนเงินด่วน
const QUICK_AMOUNTS = [100, 300, 500, 1000, 2000, 5000, 10000];

// ขั้นต่ำและขั้นสูง
const MIN_AMOUNT = 100;
const MAX_AMOUNT = 100000;

// Payment Steps
type PaymentStep = 'amount' | 'method' | 'processing' | 'result';

/** ลำดับขั้นบนแถบขั้นตอน (ตรงกับ PaymentStep) */
const STEP_ORDER: PaymentStep[] = ['amount', 'method', 'processing', 'result'];
const STEP_LABELS = ['จำนวนเงิน', 'วิธีชำระ', 'ชำระเงิน', 'เสร็จสิ้น'];

// ไอคอนเส้นสำหรับวิธีชำระเงิน (ตาม category ที่ server ส่งมา)
const PAYMENT_ICONS: Record<string, IconName> = {
  promptpay: 'qr-code',
  qr: 'qr-code',
  credit_card: 'credit-card',
  card: 'credit-card',
  bank_transfer: 'bank',
  bank: 'bank',
  truemoney: 'device-mobile',
  wallet: 'wallet',
  default: 'coins',
};

/** จุดเด่นความปลอดภัยใต้ฟอร์มเติมเงิน */
const TRUST_POINTS: Array<{ icon: IconName; tone: TileTone; text: string }> = [
  { icon: 'shield-check', tone: 'success', text: 'ชำระเงินผ่านระบบที่ปลอดภัย' },
  { icon: 'lightning', tone: 'gold', text: 'ยอดเงินเข้าทันทีหลังชำระ' },
  { icon: 'qr-code', tone: 'navy', text: 'ชำระด้วย PromptPay สแกนจ่ายได้ทุกแอปธนาคาร' },
];

const KANOK = require('@/assets/images/brand/kanok-gold.webp');

/**
 * วิธีชำระเงินนี้ใช้ได้หรือไม่ — backend ส่ง `enabled` (บางรุ่นส่ง `is_available`)
 * เดิมเช็คแค่ is_available ทำให้ทุกวิธีขึ้น "ไม่พร้อมใช้งาน" เมื่อ server ไม่ส่งคีย์นี้
 */
const isMethodAvailable = (method: PaymentMethod): boolean => {
  const flags = method as PaymentMethod & { is_available?: boolean };
  return flags.enabled !== false && flags.is_available !== false;
};

// =====================================================
// การ์ดวิธีชำระเงิน
// =====================================================

interface MethodCardProps {
  method: PaymentMethod;
  selected: boolean;
  /** กำลังสร้างรายการด้วยวิธีนี้ */
  busy: boolean;
  /** อีกวิธีกำลังสร้างรายการอยู่ → จางลง */
  dimmed: boolean;
  disabled: boolean;
  onPress: () => unknown;
}

const MethodCard: React.FC<MethodCardProps> = ({ method, selected, busy, dimmed, disabled, onPress }) => {
  const { colors } = useTheme();
  const available = isMethodAvailable(method);
  const { run } = usePressGuard(onPress, { disabled });

  return (
    <Pressable
      onPress={run}
      disabled={disabled}
      accessibilityRole="button"
      accessibilityState={{ disabled, selected, busy }}
      style={({ pressed }) => [
        styles.methodCard,
        {
          backgroundColor: colors.card,
          borderColor: selected ? colors.gold : colors.border,
          borderWidth: selected ? 1.5 : 1,
        },
        shadowStyle('sm', colors.shadowDark),
        !available && styles.methodUnavailable,
        dimmed && styles.methodDimmed,
        pressed && styles.pressed,
      ]}
    >
      <IconTile icon={PAYMENT_ICONS[method.category] || PAYMENT_ICONS.default} tone="gold" size={48} />
      <View style={styles.flex}>
        <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{method.name}</Text>
        {!!method.description && (
          <Text style={[typography.caption, styles.methodDesc, { color: colors.textMuted }]}>{method.description}</Text>
        )}
        {!available && <Pill label="ไม่พร้อมใช้งาน" tone="danger" style={styles.methodPill} />}
      </View>
      {busy ? (
        <ActivityIndicator color={colors.gold} />
      ) : (
        <Icon name="caret-right" size={18} color={selected ? colors.goldDeep : colors.textFaint} weight="bold" />
      )}
    </Pressable>
  );
};

// =====================================================
// แถวข้อมูลบัญชีโอน (มีปุ่มคัดลอก)
// =====================================================

const BankInfoLine: React.FC<{ label: string; value: string; money?: boolean; onCopy?: () => void }> = ({
  label,
  value,
  money = false,
  onCopy,
}) => {
  const { colors } = useTheme();
  return (
    <View style={[styles.bankLine, { borderTopColor: colors.divider }]}>
      <View style={styles.flex}>
        <Text style={[typography.caption, { color: colors.textMuted }]}>{label}</Text>
        <Text
          style={[
            money ? typography.h2 : typography.bodyStrong,
            money && styles.tabular,
            { color: money ? colors.goldDeep : colors.textStrong },
          ]}
        >
          {value}
        </Text>
      </View>
      {!!onCopy && (
        <Pressable
          onPress={onCopy}
          hitSlop={8}
          accessibilityRole="button"
          accessibilityLabel={`คัดลอก${label}`}
          style={({ pressed }) => [styles.copyBtn, { backgroundColor: colors.goldSoft, opacity: pressed ? 0.7 : 1 }]}
        >
          <Icon name="copy" size={14} color={colors.goldDeep} weight="bold" />
          <Text style={[typography.caption, styles.copyText, { color: colors.goldDeep }]}>คัดลอก</Text>
        </Pressable>
      )}
    </View>
  );
};

export default function WalletTopupScreen() {
  const router = useRouter();
  const { colors, gradients, isDark } = useTheme();
  const { isAuthenticated } = useAuthStore();

  // State - ข้อมูลพื้นฐาน
  const [walletBalance, setWalletBalance] = useState<number>(0);
  const [selectedAmount, setSelectedAmount] = useState<number | null>(null);
  const [customAmount, setCustomAmount] = useState<string>('');
  const [isLoading, setIsLoading] = useState(false);

  // State - Payment Flow
  const [currentStep, setCurrentStep] = useState<PaymentStep>('amount');
  const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>([]);
  const [selectedMethod, setSelectedMethod] = useState<PaymentMethod | null>(null);
  const [transaction, setTransaction] = useState<PaymentTransaction | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [loadingMethods, setLoadingMethods] = useState(false);

  // Polling interval ref
  const pollingIntervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

  // โหลดข้อมูล wallet
  const loadWallet = useCallback(async () => {
    if (!isAuthenticated) return;

    setIsLoading(true);
    try {
      const response = await getWallet();
      if (response?.success && response.data) {
        setWalletBalance(response.data.balance || 0);
      }
    } catch (error) {
      console.error('Load wallet error:', error);
    } finally {
      setIsLoading(false);
    }
  }, [isAuthenticated]);

  // โหลดวิธีชำระเงิน (กรอง wallet ออกเพื่อป้องกันการ pump เงิน)
  const loadPaymentMethods = useCallback(async () => {
    setLoadingMethods(true);
    try {
      console.log('📋 Loading deposit methods...');
      const response = await getDepositMethods();
      if (response?.success && response.methods) {
        // กรอง wallet/balance ออก - ไม่ให้ใช้ wallet เติม wallet (ป้องกัน pump เงิน)
        // PLAY-18: เติมเงินในแอปใช้ PromptPay (QR) เท่านั้น — กระเป๋าเงินแบบใช้ในระบบสำหรับสินค้า/ค่าส่ง
        const filteredMethods = response.methods.filter(
          (method) =>
            method.category !== 'wallet' &&
            method.id !== 'wallet' &&
            method.id !== 'balance' &&
            (method.category === 'qr' || /promptpay/i.test(String(method.id)))
        );
        console.log('📋 Got methods:', filteredMethods.length, '(filtered wallet out)');
        setPaymentMethods(filteredMethods);
      }
    } catch (error) {
      console.error('Load payment methods error:', error);
      Alert.alert('เกิดข้อผิดพลาด', 'ไม่สามารถโหลดวิธีชำระเงินได้');
    } finally {
      setLoadingMethods(false);
    }
  }, []);

  useEffect(() => {
    loadWallet();
  }, [loadWallet]);

  // Cleanup polling on unmount
  useEffect(() => {
    return () => {
      if (pollingIntervalRef.current) {
        clearInterval(pollingIntervalRef.current);
      }
    };
  }, []);

  // คำนวณจำนวนเงินที่จะเติม
  const getAmount = (): number => {
    if (selectedAmount) return selectedAmount;
    if (customAmount) return parseFloat(customAmount) || 0;
    return 0;
  };

  // เลือกจำนวนเงินด่วน
  const handleQuickAmount = (amount: number) => {
    setSelectedAmount(amount);
    setCustomAmount('');
  };

  // กรอกจำนวนเงินเอง
  const handleCustomAmount = (text: string) => {
    const numericValue = text.replace(/[^0-9]/g, '');
    setCustomAmount(numericValue);
    setSelectedAmount(null);
  };

  // ไปขั้นตอนเลือกวิธีชำระเงิน
  const handleProceedToMethod = async () => {
    const amount = getAmount();

    if (amount < MIN_AMOUNT) {
      Alert.alert('จำนวนเงินไม่ถูกต้อง', `เติมเงินขั้นต่ำ ${formatCurrency(MIN_AMOUNT)}`);
      return;
    }

    if (amount > MAX_AMOUNT) {
      Alert.alert('จำนวนเงินไม่ถูกต้อง', `เติมเงินสูงสุด ${formatCurrency(MAX_AMOUNT)}`);
      return;
    }

    // โหลดวิธีชำระเงินก่อนไปขั้นตอนถัดไป
    await loadPaymentMethods();
    setCurrentStep('method');
  };

  // เลือกวิธีชำระเงินและเริ่มทำรายการ
  const handleSelectPaymentMethod = async (method: PaymentMethod) => {
    setSelectedMethod(method);
    setIsSubmitting(true);

    try {
      console.log('💳 Starting payment with method:', method.id);
      const amount = getAmount();

      const response = await initializeWalletTopup(amount, method.id);

      if (response.success && response.transaction) {
        setTransaction(response.transaction);
        setCurrentStep('processing');

        // เริ่ม polling สำหรับ QR / Bank Transfer
        if (method.category === 'qr' || method.category === 'bank') {
          startStatusPolling(response.transaction.transaction_id);
        }

        // เปิด Deep Link สำหรับ TrueMoney หรือ wallet อื่น
        const deepLink = response.transaction.deep_link;
        if (deepLink) {
          setTimeout(() => {
            Linking.openURL(deepLink).catch(() => {
              console.log('Cannot open deep link');
            });
          }, 500);
        }
      } else {
        Alert.alert('เกิดข้อผิดพลาด', response.message || 'ไม่สามารถสร้างรายการได้');
      }
    } catch (error) {
      console.error('Payment error:', error);
      Alert.alert('เกิดข้อผิดพลาด', 'ไม่สามารถทำรายการได้ กรุณาลองใหม่');
    } finally {
      setIsSubmitting(false);
    }
  };

  // เริ่ม polling เช็คสถานะ
  const startStatusPolling = (transactionId: string) => {
    console.log('🔄 Starting status polling for:', transactionId);

    // Clear existing interval
    if (pollingIntervalRef.current) {
      clearInterval(pollingIntervalRef.current);
    }

    // Poll every 3 seconds
    pollingIntervalRef.current = setInterval(async () => {
      try {
        const response = await checkPaymentStatus(transactionId);
        console.log('🔄 Status check:', response.data?.status);

        if (response.success && response.data) {
          // /payment/{id}/status ไม่ส่ง qr_code/bank_info กลับมา → รวมกับค่าเดิม ไม่ทับทั้งก้อน (QR ไม่หายหลัง 3 วินาที)
          const fresh = Object.fromEntries(
            Object.entries(response.data).filter(([, v]) => v !== null && v !== undefined)
          ) as Partial<PaymentTransaction>;
          const latest = response.data;
          setTransaction((prev) => (prev ? { ...prev, ...fresh } : latest));

          // Stop polling if completed or failed
          if (['completed', 'failed', 'cancelled', 'expired'].includes(response.data.status)) {
            if (pollingIntervalRef.current) {
              clearInterval(pollingIntervalRef.current);
              pollingIntervalRef.current = null;
            }
            setCurrentStep('result');

            // Reload wallet balance if completed
            if (response.data.status === 'completed') {
              loadWallet();
            }
          }
        }
      } catch (error) {
        console.error('Status poll error:', error);
      }
    }, 3000);
  };

  // หยุด polling
  const stopPolling = () => {
    if (pollingIntervalRef.current) {
      clearInterval(pollingIntervalRef.current);
      pollingIntervalRef.current = null;
    }
  };

  // Copy to clipboard
  const handleCopy = async (text: string, label: string) => {
    await Clipboard.setStringAsync(text);
    Alert.alert('คัดลอกแล้ว', `${label} ถูกคัดลอกแล้ว`);
  };

  // กลับไปขั้นตอนก่อนหน้า
  const handleBack = () => {
    // ระหว่างรอชำระ: หยุดเช็คสถานะเฉพาะเมื่อผู้ใช้ยืนยันยกเลิกจริง (กด "ไม่ยกเลิก" = ยังรอผลต่อ)
    if (currentStep !== 'processing') {
      stopPolling();
    }

    if (currentStep === 'method') {
      setCurrentStep('amount');
      setSelectedMethod(null);
    } else if (currentStep === 'processing') {
      Alert.alert(
        'ยกเลิกการชำระเงิน?',
        'หากยกเลิกตอนนี้ รายการจะถูกยกเลิก',
        [
          { text: 'ไม่ยกเลิก', style: 'cancel' },
          {
            text: 'ยกเลิก',
            style: 'destructive',
            onPress: () => {
              stopPolling();
              setCurrentStep('amount');
              setTransaction(null);
              setSelectedMethod(null);
            },
          },
        ]
      );
    } else if (currentStep === 'result') {
      // Reset all
      setCurrentStep('amount');
      setTransaction(null);
      setSelectedMethod(null);
      setSelectedAmount(null);
      setCustomAmount('');
    } else {
      router.back();
    }
  };

  // ทำรายการใหม่
  const handleNewTransaction = () => {
    setCurrentStep('amount');
    setTransaction(null);
    setSelectedMethod(null);
    setSelectedAmount(null);
    setCustomAmount('');
    loadWallet();
  };

  const amount = getAmount();
  const isValidAmount = amount >= MIN_AMOUNT && amount <= MAX_AMOUNT;

  // Render Step 1: เลือกจำนวนเงิน
  const renderAmountStep = () => (
    <>
      {/* ยอดเงินปัจจุบัน — การ์ดน้ำเงินแบบบัตรโลหะ */}
      <NavyCard>
        <View style={styles.cardHeadRow}>
          <Icon name="wallet" size={18} color={colors.goldLight} weight="fill" />
          <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>ยอดเงินปัจจุบัน</Text>
        </View>
        <View style={styles.balanceLine}>
          {isLoading ? (
            <ActivityIndicator color={colors.goldLight} />
          ) : (
            <MoneyText text={formatCurrency(walletBalance)} color={colors.goldLight} size={34} />
          )}
        </View>
        {/* PLAY-18: กระเป๋าเงินแบบใช้ในระบบ — สำหรับสินค้าจริงและค่าจัดส่งเท่านั้น */}
        <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>
          ยอดเติมใช้ชำระค่าสินค้าและค่าจัดส่งในแอป
        </Text>
      </NavyCard>

      {/* เลือกจำนวนเงิน */}
      <Card3D style={styles.block} padding={spacing.lg}>
        <Text style={[typography.h3, { color: colors.textStrong }]}>เลือกจำนวนเงินที่ต้องการเติม</Text>

        {/* ปุ่มจำนวนเงินด่วน */}
        <View style={styles.quickGrid}>
          {QUICK_AMOUNTS.map((amt) => {
            const selected = selectedAmount === amt;
            return (
              <Pressable
                key={amt}
                onPress={() => handleQuickAmount(amt)}
                accessibilityRole="button"
                accessibilityState={{ selected }}
                style={({ pressed }) => [styles.quickItem, pressed && styles.pressed]}
              >
                {selected ? (
                  <LinearGradient
                    colors={gradients.navy}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 0, y: 1 }}
                    style={[
                      styles.quickTile,
                      { borderColor: withAlpha(colors.gold, 0.6) },
                      shadowStyle('sm', colors.shadowDark),
                    ]}
                  >
                    <Text style={[styles.quickText, { color: colors.goldLight }]}>฿{amt.toLocaleString()}</Text>
                  </LinearGradient>
                ) : (
                  <View style={[styles.quickTile, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                    <Text style={[styles.quickText, { color: colors.textStrong }]}>฿{amt.toLocaleString()}</Text>
                  </View>
                )}
              </Pressable>
            );
          })}
        </View>

        {/* กรอกจำนวนเงินเอง */}
        <View style={styles.orRow}>
          <View style={[styles.orLine, { backgroundColor: colors.divider }]} />
          <Text style={[typography.caption, { color: colors.textMuted }]}>หรือกรอกจำนวนเงินเอง</Text>
          <View style={[styles.orLine, { backgroundColor: colors.divider }]} />
        </View>

        <MoneyInput
          value={customAmount}
          onChangeText={handleCustomAmount}
          placeholder="0"
          keyboardType="numeric"
          maxLength={7}
          accessibilityLabel="กรอกจำนวนเงินเอง"
        />

        <View style={styles.hintRow}>
          <Icon name="info" size={14} color={colors.textFaint} />
          <Text style={[typography.caption, styles.flex, { color: colors.textFaint }]}>
            ขั้นต่ำ {formatCurrency(MIN_AMOUNT)} - สูงสุด {formatCurrency(MAX_AMOUNT)}
          </Text>
        </View>
      </Card3D>

      {/* สรุปการเติมเงิน */}
      {amount > 0 && (
        <Card3D variant="inset" style={styles.block} padding={spacing.lg}>
          <InfoRow label="จำนวนเงินที่จะเติม" value={formatCurrency(amount)} />
          <View style={[styles.divider, { backgroundColor: colors.divider }]} />
          <InfoRow label="ยอดเงินหลังเติม" strong>
            <Text style={[typography.h2, styles.tabular, { color: colors.success }]}>
              {formatCurrency(walletBalance + amount)}
            </Text>
          </InfoRow>
        </Card3D>
      )}

      {/* ข้อมูลการชำระเงิน */}
      <Card3D style={styles.block} padding={spacing.md}>
        {TRUST_POINTS.map((point, index) => (
          <View
            key={point.text}
            style={[
              styles.trustRow,
              index > 0 && { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.divider },
            ]}
          >
            <IconTile icon={point.icon} tone={point.tone} size={36} />
            <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>{point.text}</Text>
          </View>
        ))}
      </Card3D>
    </>
  );

  // Render Step 2: เลือกวิธีชำระเงิน
  const renderMethodStep = () => (
    <>
      {/* แสดงจำนวนเงิน */}
      <NavyCard contentStyle={styles.amountHero}>
        <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>จำนวนเงินที่จะเติม</Text>
        <MoneyText text={formatCurrency(amount)} color={colors.goldLight} size={38} />
      </NavyCard>

      <SectionHeader title="เลือกวิธีชำระเงิน" style={styles.sectionHeader} />

      {loadingMethods ? (
        <View style={styles.centerBox}>
          <ActivityIndicator size="large" color={colors.gold} />
          <Text style={[typography.bodySm, styles.centerText, { color: colors.textMuted }]}>
            กำลังโหลดวิธีชำระเงิน...
          </Text>
        </View>
      ) : paymentMethods.length === 0 ? (
        <Card3D variant="flat" padding={spacing.xl} contentStyle={styles.centerBox}>
          <IconTile icon="qr-code" tone="gold" size={56} />
          <Text style={[typography.body, styles.centerText, { color: colors.textMuted }]}>
            ตอนนี้ยังเติมเงินผ่าน PromptPay ไม่ได้ ลองใหม่ภายหลังนะ
          </Text>
        </Card3D>
      ) : (
        <View style={styles.methodList}>
          {paymentMethods.map((method) => {
            const selected = selectedMethod?.id === method.id;
            return (
              <MethodCard
                key={method.id}
                method={method}
                selected={selected}
                busy={isSubmitting && selected}
                dimmed={isSubmitting && !selected}
                disabled={!isMethodAvailable(method) || isSubmitting}
                onPress={() => isMethodAvailable(method) && handleSelectPaymentMethod(method)}
              />
            );
          })}
        </View>
      )}
    </>
  );

  // Render Step 3: กำลังชำระเงิน
  const renderProcessingStep = () => (
    <View>
      {/* QR Code สำหรับ PromptPay — การ์ดขาว แถบน้ำเงิน กรอบทอง */}
      {transaction?.qr_code && (
        <View
          style={[
            styles.qrCard,
            { backgroundColor: colors.card, borderColor: colors.border },
            shadowStyle('md', colors.shadowDark),
          ]}
        >
          <View style={styles.qrClip}>
            <LinearGradient colors={gradients.navy} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.qrBand}>
              <BrandImage
                source={KANOK}
                contentFit="contain"
                accessible={false}
                pointerEvents="none"
                style={[styles.qrKanok, { opacity: isDark ? 0.3 : 0.42 }]}
              />
              <View style={styles.qrTitleRow}>
                <Icon name="qr-code" size={20} color={colors.goldLight} />
                <Text style={[typography.serifSm, { color: colors.onHeader }]}>สแกน QR Code เพื่อชำระเงิน</Text>
              </View>
            </LinearGradient>

            <View style={styles.qrBody}>
              {/* QR ต้องอยู่บนพื้นขาวเสมอ (สแกนติดทั้งโหมดมืด/สว่าง) → ใช้สีการ์ดของธีมสว่าง */}
              <LinearGradient
                colors={gradients.goldBorder}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={[styles.qrFrame, shadowStyle('md', colors.shadowDark)]}
              >
                <View style={[styles.qrPlate, { backgroundColor: LIGHT_THEME.colors.card }]}>
                  {(() => {
                    // qr_code จาก server ส่วนใหญ่เป็น data:image/svg+xml → ต้องวาดด้วย SvgXml (Image วาด SVG ไม่ได้)
                    const qr = resolveQrSource(transaction.qr_code, transaction.qr_code_url);
                    if (qr?.kind === 'svg') {
                      return <SvgXml xml={qr.xml} width={styles.qrImage.width} height={styles.qrImage.height} />;
                    }
                    if (qr?.kind === 'image') {
                      return <Image source={{ uri: qr.uri }} style={styles.qrImage} resizeMode="contain" />;
                    }
                    return (
                      <Text style={[typography.bodySm, styles.centerText, { color: LIGHT_THEME.colors.textMuted }]}>
                        แสดง QR ไม่ได้ กรุณาใช้ข้อมูลบัญชีด้านล่าง หรือลองใหม่อีกครั้ง
                      </Text>
                    );
                  })()}
                </View>
              </LinearGradient>

              <MoneyText text={formatCurrency(transaction.amount)} color={colors.goldDeep} size={32} style={styles.qrAmount} />
              <Text style={[typography.bodySm, styles.centerText, { color: colors.textMuted }]}>
                เปิดแอพธนาคารแล้วสแกน QR Code นี้
              </Text>
            </View>
          </View>
        </View>
      )}

      {/* Bank Transfer Info */}
      {transaction?.bank_info && (
        <Card3D style={styles.block} padding={spacing.lg}>
          <View style={styles.cardTitleRow}>
            <IconTile icon="bank" tone="navy" size={40} />
            <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>โอนเงินไปยังบัญชีนี้</Text>
          </View>

          <BankInfoLine label="ธนาคาร" value={transaction.bank_info.bank_name} />
          <BankInfoLine label="ชื่อบัญชี" value={transaction.bank_info.account_name} />
          <BankInfoLine
            label="เลขบัญชี"
            value={transaction.bank_info.account_number}
            onCopy={() => handleCopy(transaction.bank_info!.account_number, 'เลขบัญชี')}
          />
          <BankInfoLine
            label="จำนวนเงิน"
            value={formatCurrency(transaction.amount)}
            money
            onCopy={() => handleCopy(transaction.amount.toString(), 'จำนวนเงิน')}
          />
          {transaction.bank_info.ref_no && (
            <BankInfoLine
              label="อ้างอิง"
              value={transaction.bank_info.ref_no}
              onCopy={() => handleCopy(transaction.bank_info!.ref_no!, 'เลขอ้างอิง')}
            />
          )}
        </Card3D>
      )}

      {/* Status */}
      <Card3D style={styles.block} padding={spacing.lg}>
        <View style={styles.statusRow}>
          <View style={[styles.statusSpinner, { backgroundColor: colors.goldSoft }]}>
            <ActivityIndicator size="small" color={colors.goldDeep} />
          </View>
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>รอการชำระเงิน...</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              ระบบจะอัพเดทอัตโนมัติเมื่อชำระสำเร็จ
            </Text>
          </View>
        </View>
      </Card3D>

      {/* Transaction ID */}
      <View style={styles.txIdRow}>
        <Icon name="receipt" size={14} color={colors.textFaint} />
        <Text style={[typography.caption, { color: colors.textFaint }]}>
          รหัสรายการ: {transaction?.transaction_id}
        </Text>
      </View>
    </View>
  );

  // Render Step 4: ผลลัพธ์
  const renderResultStep = () => {
    const isSuccess = transaction?.status === 'completed';

    return (
      <ScrollView
        style={styles.flex}
        contentContainerStyle={styles.resultScroll}
        showsVerticalScrollIndicator={false}
      >
        <StepTrack steps={STEP_LABELS} current={STEP_ORDER.indexOf('result')} style={styles.steps} />

        <Card3D padding={spacing.xl} contentStyle={styles.resultCard}>
          <View style={[styles.resultHalo, { backgroundColor: isSuccess ? colors.successSoft : colors.dangerSoft }]}>
            <View style={[styles.resultIcon, { backgroundColor: isSuccess ? colors.success : colors.danger }]}>
              <Icon name={isSuccess ? 'check' : 'x'} size={34} color={colors.textOnAccent} weight="bold" />
            </View>
          </View>

          <Text style={[typography.serif, styles.centerText, { color: colors.textStrong }]}>
            {isSuccess ? 'เติมเงินสำเร็จ!' : 'การชำระเงินไม่สำเร็จ'}
          </Text>

          {isSuccess ? (
            <>
              <MoneyText text={`+${formatCurrency(transaction?.amount || 0)}`} color={colors.success} size={34} />
              <Text style={[typography.bodySm, styles.centerText, { color: colors.textMuted }]}>
                ยอดเงินได้รับการอัพเดทแล้ว
              </Text>
            </>
          ) : (
            <Text style={[typography.body, styles.centerText, { color: colors.textMuted }]}>
              {transaction?.status === 'expired' ? 'รายการหมดเวลา' :
                transaction?.status === 'cancelled' ? 'รายการถูกยกเลิก' :
                  'กรุณาลองใหม่อีกครั้ง'}
            </Text>
          )}

          {/* New Balance */}
          {isSuccess && (
            <View style={[styles.newBalance, { borderTopColor: colors.divider }]}>
              <Text style={[typography.caption, { color: colors.textMuted }]}>ยอดเงินใหม่</Text>
              <MoneyText text={formatCurrency(walletBalance)} color={colors.textStrong} size={28} />
            </View>
          )}
        </Card3D>

        {/* Actions */}
        <View style={styles.resultActions}>
          <Button3D
            title={isSuccess ? 'เติมเงินอีก' : 'ลองใหม่'}
            icon={isSuccess ? 'plus' : 'arrows-clockwise'}
            size="lg"
            fullWidth
            onPress={handleNewTransaction}
          />
          <Button3D title="กลับหน้าหลัก" variant="secondary" size="lg" fullWidth onPress={() => router.back()} />
        </View>
      </ScrollView>
    );
  };

  // Render footer button (แถบลอยท้ายจอ)
  const renderFooter = () => {
    if (currentStep === 'amount') {
      return (
        <ActionBar>
          <Button3D
            title={`เลือกวิธีชำระเงิน ${amount > 0 ? formatCurrency(amount) : ''}`}
            icon="credit-card"
            size="lg"
            fullWidth
            disabled={!isValidAmount || isSubmitting}
            onPress={handleProceedToMethod}
          />
        </ActionBar>
      );
    }

    if (currentStep === 'method' && isSubmitting) {
      return (
        <ActionBar>
          <Button3D title="กำลังสร้างรายการ..." size="lg" fullWidth loading />
        </ActionBar>
      );
    }

    if (currentStep === 'processing') {
      return (
        <ActionBar>
          <Button3D title="ยกเลิก" icon="x" variant="secondary" size="lg" fullWidth onPress={handleBack} />
        </ActionBar>
      );
    }

    return null;
  };

  // Get header title
  const getHeaderTitle = () => {
    switch (currentStep) {
      case 'amount':
        return 'เติมเงิน';
      case 'method':
        return 'เลือกวิธีชำระเงิน';
      case 'processing':
        return 'ชำระเงิน';
      case 'result':
        return 'ผลลัพธ์';
      default:
        return 'เติมเงิน';
    }
  };

  return (
    <Screen title={getHeaderTitle()} onBack={handleBack} scroll={false}>
      {currentStep === 'result' ? (
        // Result step ไม่มีช่องกรอก → ไม่ต้องหลบคีย์บอร์ด
        renderResultStep()
      ) : (
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={styles.flex}
        >
          <ScrollView
            style={styles.flex}
            contentContainerStyle={styles.content}
            showsVerticalScrollIndicator={false}
            keyboardShouldPersistTaps="handled"
          >
            <StepTrack steps={STEP_LABELS} current={STEP_ORDER.indexOf(currentStep)} style={styles.steps} />

            {currentStep === 'amount' && renderAmountStep()}
            {currentStep === 'method' && renderMethodStep()}
            {currentStep === 'processing' && renderProcessingStep()}
          </ScrollView>

          {renderFooter()}
        </KeyboardAvoidingView>
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  content: {
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
    paddingBottom: spacing.xxl,
  },
  steps: {
    marginBottom: spacing.xl,
  },
  block: {
    marginTop: spacing.lg,
  },
  sectionHeader: {
    marginTop: spacing.xxl,
  },
  pressed: {
    opacity: 0.85,
    transform: [{ scale: 0.98 }],
  },
  tabular: {
    fontVariant: ['tabular-nums'],
  },
  centerText: {
    textAlign: 'center',
  },
  divider: {
    height: StyleSheet.hairlineWidth,
    marginVertical: spacing.sm,
  },

  // การ์ดยอดเงิน
  cardHeadRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
  },
  balanceLine: {
    minHeight: 44,
    justifyContent: 'center',
    alignItems: 'flex-start',
    marginTop: spacing.xs,
    marginBottom: spacing.xs,
  },
  amountHero: {
    alignItems: 'center',
    gap: spacing.xs,
    paddingVertical: spacing.xxl,
  },

  // จำนวนเงินด่วน — 4 ช่องแถวแรก 3 ช่องแถวสอง (ยืดเต็มแถว)
  quickGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  quickItem: {
    flexGrow: 1,
    flexBasis: '22%',
  },
  quickTile: {
    height: 50,
    borderRadius: 16,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: spacing.xs,
  },
  quickText: {
    fontSize: 15,
    fontWeight: '700',
    fontVariant: ['tabular-nums'],
  },
  orRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.xl,
    marginBottom: spacing.md,
  },
  orLine: {
    flex: 1,
    height: 1,
  },
  hintRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    marginTop: spacing.sm,
  },
  trustRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.sm,
  },

  // วิธีชำระเงิน
  centerBox: {
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.xxl,
  },
  methodList: {
    gap: spacing.md,
  },
  methodCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    borderRadius: 20,
    padding: spacing.lg,
  },
  methodDesc: {
    marginTop: 2,
  },
  methodPill: {
    marginTop: spacing.xs,
  },
  methodUnavailable: {
    opacity: 0.5,
  },
  methodDimmed: {
    opacity: 0.6,
  },

  // QR
  qrCard: {
    borderRadius: radii.xl,
    borderWidth: 1,
  },
  qrClip: {
    // เส้นขอบ 1px ของการ์ดดันเนื้อหาเข้ามา → มุมด้านในเล็กกว่ามุมนอก 1
    borderRadius: radii.xl - 1,
    overflow: 'hidden',
  },
  qrBand: {
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.lg,
    paddingBottom: 52,
  },
  qrKanok: {
    position: 'absolute',
    top: -12,
    right: -34,
    width: 150,
    height: 132,
  },
  qrTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  qrBody: {
    alignItems: 'center',
    paddingHorizontal: spacing.xl,
    paddingBottom: spacing.xl,
  },
  qrFrame: {
    marginTop: -38,
    borderRadius: 24,
    padding: 2,
  },
  qrPlate: {
    borderRadius: 22,
    padding: spacing.lg,
  },
  qrImage: {
    width: 216,
    height: 216,
  },
  qrAmount: {
    marginTop: spacing.lg,
  },

  // ข้อมูลโอนเงิน
  cardTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginBottom: spacing.sm,
  },
  bankLine: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.md,
    borderTopWidth: StyleSheet.hairlineWidth,
  },
  copyBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
    borderRadius: radii.pill,
  },
  copyText: {
    fontWeight: '700',
  },
  statusRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  statusSpinner: {
    width: 44,
    height: 44,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
  },
  txIdRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.xs,
    paddingVertical: spacing.lg,
  },

  // ผลลัพธ์
  resultScroll: {
    flexGrow: 1,
    justifyContent: 'center',
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
    paddingBottom: spacing.xxxl,
  },
  resultCard: {
    alignItems: 'center',
    gap: spacing.sm,
  },
  resultHalo: {
    width: 96,
    height: 96,
    borderRadius: 48,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.sm,
  },
  resultIcon: {
    width: 64,
    height: 64,
    borderRadius: 32,
    alignItems: 'center',
    justifyContent: 'center',
  },
  newBalance: {
    alignSelf: 'stretch',
    alignItems: 'center',
    marginTop: spacing.md,
    paddingTop: spacing.lg,
    borderTopWidth: StyleSheet.hairlineWidth,
  },
  resultActions: {
    gap: spacing.md,
    marginTop: spacing.xl,
  },
});
