/**
 * หน้าเติมเงิน (Top-up) - Native Payment Flow
 *
 * Features:
 * - ปุ่มจำนวนเงินด่วน: 100, 300, 500, 1000, 2000, 5000, 10000
 * - กรอกจำนวนเงินเอง (100-100,000 บาท)
 * - เลือกวิธีชำระเงิน (PromptPay, บัตรเครดิต, โอนธนาคาร, TrueMoney)
 * - ชำระเงินผ่าน API โดยตรง (ไม่ต้องเปิดเว็บ)
 * - แสดง QR Code / ข้อมูลโอนเงิน ภายในแอพ
 */

import React, { useState, useEffect, useCallback, useRef } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  TextInput,
  StyleSheet,
  StatusBar,
  Alert,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Image,
  Linking,
  Clipboard,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useRouter } from 'expo-router';
import { useColorScheme } from 'react-native';
import { useAuthStore } from '@/stores/authStore';
import {
  getWallet,
  getDepositMethods,
  initializeWalletTopup,
  checkPaymentStatus,
  PaymentMethod,
  PaymentTransaction,
} from '@/services/api';
import { formatCurrency } from '@/constants';

// จำนวนเงินด่วน
const QUICK_AMOUNTS = [100, 300, 500, 1000, 2000, 5000, 10000];

// ขั้นต่ำและขั้นสูง
const MIN_AMOUNT = 100;
const MAX_AMOUNT = 100000;

// Payment Steps
type PaymentStep = 'amount' | 'method' | 'processing' | 'result';

// Emoji icons สำหรับวิธีชำระเงิน
const PAYMENT_ICONS: Record<string, string> = {
  promptpay: '📲',
  qr: '📲',
  credit_card: '💳',
  card: '💳',
  bank_transfer: '🏦',
  bank: '🏦',
  truemoney: '📱',
  wallet: '👛',
  default: '💰',
};

export default function WalletTopupScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
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
        const filteredMethods = response.methods.filter(
          (method) => method.category !== 'wallet' && method.id !== 'wallet' && method.id !== 'balance'
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
      console.log('💳 Payment response:', response);

      if (response.success && response.transaction) {
        setTransaction(response.transaction);
        setCurrentStep('processing');

        // เริ่ม polling สำหรับ QR / Bank Transfer
        if (method.category === 'qr' || method.category === 'bank') {
          startStatusPolling(response.transaction.transaction_id);
        }

        // เปิด Deep Link สำหรับ TrueMoney หรือ wallet อื่น
        if (response.transaction.deep_link) {
          setTimeout(() => {
            Linking.openURL(response.transaction.deep_link!).catch(() => {
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
          setTransaction(response.data);

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
  const handleCopy = (text: string, label: string) => {
    Clipboard.setString(text);
    Alert.alert('คัดลอกแล้ว', `${label} ถูกคัดลอกแล้ว`);
  };

  // กลับไปขั้นตอนก่อนหน้า
  const handleBack = () => {
    stopPolling();

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
      {/* ยอดเงินปัจจุบัน */}
      <View style={[styles.balanceCard, isDark && styles.cardDark]}>
        <View style={styles.balanceRow}>
          <Text style={styles.emojiIcon}>💰</Text>
          <Text style={[styles.balanceLabel, isDark && styles.textLight]}>
            ยอดเงินปัจจุบัน
          </Text>
        </View>
        <Text style={[styles.balanceAmount, isDark && styles.textLight]}>
          {isLoading ? '...' : formatCurrency(walletBalance)}
        </Text>
      </View>

      {/* เลือกจำนวนเงิน */}
      <Text style={[styles.sectionTitle, isDark && styles.textLight]}>
        เลือกจำนวนเงินที่ต้องการเติม
      </Text>

      {/* ปุ่มจำนวนเงินด่วน */}
      <View style={styles.quickAmountGrid}>
        {QUICK_AMOUNTS.map((amt) => (
          <TouchableOpacity
            key={amt}
            style={[
              styles.quickAmountBtn,
              isDark && styles.quickAmountBtnDark,
              selectedAmount === amt && styles.quickAmountBtnSelected,
            ]}
            onPress={() => handleQuickAmount(amt)}
          >
            <Text
              style={[
                styles.quickAmountText,
                isDark && styles.textMuted,
                selectedAmount === amt && styles.quickAmountTextSelected,
              ]}
            >
              ฿{amt.toLocaleString()}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      {/* กรอกจำนวนเงินเอง */}
      <Text style={[styles.sectionTitle, isDark && styles.textLight]}>
        หรือกรอกจำนวนเงินเอง
      </Text>

      <View style={[styles.inputContainer, isDark && styles.inputContainerDark]}>
        <Text style={[styles.inputPrefix, isDark && styles.textMuted]}>฿</Text>
        <TextInput
          style={[styles.input, isDark && styles.textLight]}
          value={customAmount}
          onChangeText={handleCustomAmount}
          placeholder="0"
          placeholderTextColor="#9CA3AF"
          keyboardType="numeric"
          maxLength={7}
        />
      </View>

      <Text style={styles.inputHint}>
        ขั้นต่ำ {formatCurrency(MIN_AMOUNT)} - สูงสุด {formatCurrency(MAX_AMOUNT)}
      </Text>

      {/* สรุปการเติมเงิน */}
      {amount > 0 && (
        <View style={[styles.summaryCard, isDark && styles.cardDark]}>
          <View style={styles.summaryRow}>
            <Text style={[styles.summaryLabel, isDark && styles.textMuted]}>
              จำนวนเงินที่จะเติม
            </Text>
            <Text style={[styles.summaryValue, isDark && styles.textLight]}>
              {formatCurrency(amount)}
            </Text>
          </View>
          <View style={styles.divider} />
          <View style={styles.summaryRow}>
            <Text style={[styles.summaryLabel, isDark && styles.textMuted]}>
              ยอดเงินหลังเติม
            </Text>
            <Text style={styles.summaryTotal}>
              {formatCurrency(walletBalance + amount)}
            </Text>
          </View>
        </View>
      )}

      {/* ข้อมูลการชำระเงิน */}
      <View style={[styles.infoCard, isDark && styles.cardDark]}>
        <View style={styles.infoRow}>
          <Text style={styles.infoEmoji}>🛡️</Text>
          <Text style={[styles.infoText, isDark && styles.textMuted]}>
            ชำระเงินผ่านระบบที่ปลอดภัย
          </Text>
        </View>
        <View style={styles.infoRow}>
          <Text style={styles.infoEmoji}>⚡</Text>
          <Text style={[styles.infoText, isDark && styles.textMuted]}>
            ยอดเงินเข้าทันทีหลังชำระ
          </Text>
        </View>
        <View style={styles.infoRow}>
          <Text style={styles.infoEmoji}>💳</Text>
          <Text style={[styles.infoText, isDark && styles.textMuted]}>
            รองรับ PromptPay, บัตรเครดิต, โอนธนาคาร
          </Text>
        </View>
      </View>
    </>
  );

  // Render Step 2: เลือกวิธีชำระเงิน
  const renderMethodStep = () => (
    <>
      {/* แสดงจำนวนเงิน */}
      <View style={[styles.amountDisplay, isDark && styles.cardDark]}>
        <Text style={[styles.amountDisplayLabel, isDark && styles.textMuted]}>
          จำนวนเงินที่จะเติม
        </Text>
        <Text style={[styles.amountDisplayValue, isDark && styles.textLight]}>
          {formatCurrency(amount)}
        </Text>
      </View>

      <Text style={[styles.sectionTitle, isDark && styles.textLight]}>
        เลือกวิธีชำระเงิน
      </Text>

      {loadingMethods ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text style={[styles.loadingText, isDark && styles.textMuted]}>
            กำลังโหลดวิธีชำระเงิน...
          </Text>
        </View>
      ) : (
        <View style={styles.methodList}>
          {paymentMethods.map((method) => (
            <TouchableOpacity
              key={method.id}
              style={[
                styles.methodCard,
                isDark && styles.cardDark,
                selectedMethod?.id === method.id && styles.methodCardSelected,
                !method.is_available && styles.methodCardDisabled,
              ]}
              onPress={() => method.is_available && handleSelectPaymentMethod(method)}
              disabled={!method.is_available || isSubmitting}
            >
              <View
                style={[
                  styles.methodIconContainer,
                  { backgroundColor: method.color || '#3B82F6' + '20' },
                ]}
              >
                <Text style={styles.methodIcon}>
                  {PAYMENT_ICONS[method.category] || PAYMENT_ICONS.default}
                </Text>
              </View>
              <View style={styles.methodInfo}>
                <Text style={[styles.methodName, isDark && styles.textLight]}>
                  {method.name}
                </Text>
                {method.description && (
                  <Text style={[styles.methodDesc, isDark && styles.textMuted]}>
                    {method.description}
                  </Text>
                )}
                {!method.is_available && (
                  <Text style={styles.methodUnavailable}>ไม่พร้อมใช้งาน</Text>
                )}
              </View>
              <Text style={styles.methodArrow}>→</Text>
            </TouchableOpacity>
          ))}
        </View>
      )}
    </>
  );

  // Render Step 3: กำลังชำระเงิน
  const renderProcessingStep = () => (
    <View style={styles.processingContainer}>
      {/* QR Code สำหรับ PromptPay */}
      {transaction?.qr_code && (
        <View style={[styles.qrContainer, isDark && styles.cardDark]}>
          <Text style={[styles.qrTitle, isDark && styles.textLight]}>
            สแกน QR Code เพื่อชำระเงิน
          </Text>
          <View style={styles.qrImageContainer}>
            <Image
              source={{ uri: transaction.qr_code_url || `data:image/png;base64,${transaction.qr_code}` }}
              style={styles.qrImage}
              resizeMode="contain"
            />
          </View>
          <Text style={[styles.qrAmount, isDark && styles.textLight]}>
            {formatCurrency(transaction.amount)}
          </Text>
          <Text style={[styles.qrHint, isDark && styles.textMuted]}>
            เปิดแอพธนาคารแล้วสแกน QR Code นี้
          </Text>
        </View>
      )}

      {/* Bank Transfer Info */}
      {transaction?.bank_info && (
        <View style={[styles.bankInfoCard, isDark && styles.cardDark]}>
          <Text style={[styles.bankInfoTitle, isDark && styles.textLight]}>
            โอนเงินไปยังบัญชีนี้
          </Text>

          <View style={styles.bankInfoRow}>
            <Text style={[styles.bankInfoLabel, isDark && styles.textMuted]}>ธนาคาร</Text>
            <Text style={[styles.bankInfoValue, isDark && styles.textLight]}>
              {transaction.bank_info.bank_name}
            </Text>
          </View>

          <View style={styles.bankInfoRow}>
            <Text style={[styles.bankInfoLabel, isDark && styles.textMuted]}>ชื่อบัญชี</Text>
            <Text style={[styles.bankInfoValue, isDark && styles.textLight]}>
              {transaction.bank_info.account_name}
            </Text>
          </View>

          <View style={styles.bankInfoRow}>
            <Text style={[styles.bankInfoLabel, isDark && styles.textMuted]}>เลขบัญชี</Text>
            <View style={styles.copyRow}>
              <Text style={[styles.bankInfoValue, isDark && styles.textLight]}>
                {transaction.bank_info.account_number}
              </Text>
              <TouchableOpacity
                style={styles.copyBtn}
                onPress={() => handleCopy(transaction.bank_info!.account_number, 'เลขบัญชี')}
              >
                <Text style={styles.copyBtnText}>คัดลอก</Text>
              </TouchableOpacity>
            </View>
          </View>

          <View style={styles.bankInfoRow}>
            <Text style={[styles.bankInfoLabel, isDark && styles.textMuted]}>จำนวนเงิน</Text>
            <View style={styles.copyRow}>
              <Text style={styles.bankInfoAmount}>
                {formatCurrency(transaction.amount)}
              </Text>
              <TouchableOpacity
                style={styles.copyBtn}
                onPress={() => handleCopy(transaction.amount.toString(), 'จำนวนเงิน')}
              >
                <Text style={styles.copyBtnText}>คัดลอก</Text>
              </TouchableOpacity>
            </View>
          </View>

          {transaction.bank_info.reference && (
            <View style={styles.bankInfoRow}>
              <Text style={[styles.bankInfoLabel, isDark && styles.textMuted]}>อ้างอิง</Text>
              <View style={styles.copyRow}>
                <Text style={[styles.bankInfoValue, isDark && styles.textLight]}>
                  {transaction.bank_info.reference}
                </Text>
                <TouchableOpacity
                  style={styles.copyBtn}
                  onPress={() => handleCopy(transaction.bank_info!.reference!, 'เลขอ้างอิง')}
                >
                  <Text style={styles.copyBtnText}>คัดลอก</Text>
                </TouchableOpacity>
              </View>
            </View>
          )}
        </View>
      )}

      {/* Status */}
      <View style={[styles.statusCard, isDark && styles.cardDark]}>
        <ActivityIndicator size="small" color="#3B82F6" />
        <Text style={[styles.statusText, isDark && styles.textMuted]}>
          รอการชำระเงิน...
        </Text>
        <Text style={[styles.statusHint, isDark && styles.textMuted]}>
          ระบบจะอัพเดทอัตโนมัติเมื่อชำระสำเร็จ
        </Text>
      </View>

      {/* Transaction ID */}
      <View style={styles.transactionIdContainer}>
        <Text style={[styles.transactionIdLabel, isDark && styles.textMuted]}>
          รหัสรายการ: {transaction?.transaction_id}
        </Text>
      </View>
    </View>
  );

  // Render Step 4: ผลลัพธ์
  const renderResultStep = () => {
    const isSuccess = transaction?.status === 'completed';

    return (
      <View style={styles.resultContainer}>
        <View style={[styles.resultCard, isDark && styles.cardDark]}>
          <View style={[styles.resultIcon, isSuccess ? styles.resultIconSuccess : styles.resultIconFail]}>
            <Text style={styles.resultEmoji}>{isSuccess ? '✅' : '❌'}</Text>
          </View>

          <Text style={[styles.resultTitle, isDark && styles.textLight]}>
            {isSuccess ? 'เติมเงินสำเร็จ!' : 'การชำระเงินไม่สำเร็จ'}
          </Text>

          {isSuccess ? (
            <>
              <Text style={styles.resultAmount}>+{formatCurrency(transaction?.amount || 0)}</Text>
              <Text style={[styles.resultHint, isDark && styles.textMuted]}>
                ยอดเงินได้รับการอัพเดทแล้ว
              </Text>
            </>
          ) : (
            <Text style={[styles.resultHint, isDark && styles.textMuted]}>
              {transaction?.status === 'expired' ? 'รายการหมดเวลา' :
                transaction?.status === 'cancelled' ? 'รายการถูกยกเลิก' :
                  'กรุณาลองใหม่อีกครั้ง'}
            </Text>
          )}

          {/* New Balance */}
          {isSuccess && (
            <View style={styles.newBalanceContainer}>
              <Text style={[styles.newBalanceLabel, isDark && styles.textMuted]}>
                ยอดเงินใหม่
              </Text>
              <Text style={[styles.newBalanceValue, isDark && styles.textLight]}>
                {formatCurrency(walletBalance)}
              </Text>
            </View>
          )}
        </View>

        {/* Actions */}
        <View style={styles.resultActions}>
          <TouchableOpacity
            style={[styles.resultBtn, styles.resultBtnPrimary]}
            onPress={handleNewTransaction}
          >
            <Text style={styles.resultBtnText}>
              {isSuccess ? 'เติมเงินอีก' : 'ลองใหม่'}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.resultBtn, styles.resultBtnSecondary]}
            onPress={() => router.back()}
          >
            <Text style={[styles.resultBtnTextSecondary, isDark && styles.textLight]}>
              กลับหน้าหลัก
            </Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  };

  // Render footer button
  const renderFooter = () => {
    if (currentStep === 'amount') {
      return (
        <View style={[styles.footer, isDark && styles.footerDark]}>
          <TouchableOpacity
            style={[
              styles.submitButton,
              !isValidAmount && styles.submitButtonDisabled,
            ]}
            onPress={handleProceedToMethod}
            disabled={!isValidAmount || isSubmitting}
          >
            <Text style={styles.buttonIcon}>💳</Text>
            <Text style={styles.submitButtonText}>
              เลือกวิธีชำระเงิน {amount > 0 ? formatCurrency(amount) : ''}
            </Text>
          </TouchableOpacity>
        </View>
      );
    }

    if (currentStep === 'method' && isSubmitting) {
      return (
        <View style={[styles.footer, isDark && styles.footerDark]}>
          <View style={styles.submitButton}>
            <ActivityIndicator color="#FFF" />
            <Text style={styles.submitButtonText}>กำลังสร้างรายการ...</Text>
          </View>
        </View>
      );
    }

    if (currentStep === 'processing') {
      return (
        <View style={[styles.footer, isDark && styles.footerDark]}>
          <TouchableOpacity
            style={[styles.submitButton, styles.submitButtonCancel]}
            onPress={handleBack}
          >
            <Text style={styles.submitButtonText}>ยกเลิก</Text>
          </TouchableOpacity>
        </View>
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
    <View style={[styles.container, isDark && styles.containerDark]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />

      {/* Header */}
      <LinearGradient
        colors={['#3B82F6', '#2563EB']}
        style={styles.header}
      >
        <TouchableOpacity style={styles.backButton} onPress={handleBack}>
          <Text style={styles.headerIcon}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>{getHeaderTitle()}</Text>
        <View style={styles.headerSpacer} />
      </LinearGradient>

      {currentStep === 'result' ? (
        // Result step ไม่มี scroll
        renderResultStep()
      ) : (
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={styles.keyboardView}
        >
          <ScrollView
            style={styles.content}
            showsVerticalScrollIndicator={false}
            keyboardShouldPersistTaps="handled"
          >
            {currentStep === 'amount' && renderAmountStep()}
            {currentStep === 'method' && renderMethodStep()}
            {currentStep === 'processing' && renderProcessingStep()}

            <View style={styles.bottomPadding} />
          </ScrollView>

          {renderFooter()}
        </KeyboardAvoidingView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F3F4F6',
  },
  containerDark: {
    backgroundColor: '#111827',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingTop: 50,
    paddingBottom: 16,
    paddingHorizontal: 16,
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.2)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  headerTitle: {
    flex: 1,
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFF',
    textAlign: 'center',
  },
  headerSpacer: {
    width: 40,
  },
  keyboardView: {
    flex: 1,
  },
  content: {
    flex: 1,
    padding: 16,
  },
  textLight: {
    color: '#F3F4F6',
  },
  textMuted: {
    color: '#9CA3AF',
  },
  headerIcon: {
    fontSize: 24,
    color: '#FFF',
    fontWeight: 'bold',
  },
  emojiIcon: {
    fontSize: 24,
  },
  infoEmoji: {
    fontSize: 20,
  },
  buttonIcon: {
    fontSize: 20,
  },

  // Balance Card
  balanceCard: {
    backgroundColor: '#FFF',
    borderRadius: 16,
    padding: 20,
    marginBottom: 24,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
  },
  cardDark: {
    backgroundColor: '#1F2937',
  },
  balanceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 8,
  },
  balanceLabel: {
    fontSize: 14,
    color: '#6B7280',
  },
  balanceAmount: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#1F2937',
  },

  // Section Title
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#374151',
    marginBottom: 12,
  },

  // Quick Amount Grid
  quickAmountGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginBottom: 24,
  },
  quickAmountBtn: {
    width: '31%',
    backgroundColor: '#FFF',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    borderWidth: 2,
    borderColor: '#E5E7EB',
  },
  quickAmountBtnDark: {
    backgroundColor: '#1F2937',
    borderColor: '#374151',
  },
  quickAmountBtnSelected: {
    borderColor: '#3B82F6',
    backgroundColor: 'rgba(59, 130, 246, 0.1)',
  },
  quickAmountText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#374151',
  },
  quickAmountTextSelected: {
    color: '#3B82F6',
  },

  // Input
  inputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFF',
    borderRadius: 12,
    borderWidth: 2,
    borderColor: '#E5E7EB',
    paddingHorizontal: 16,
    marginBottom: 8,
  },
  inputContainerDark: {
    backgroundColor: '#1F2937',
    borderColor: '#374151',
  },
  inputPrefix: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#6B7280',
    marginRight: 8,
  },
  input: {
    flex: 1,
    fontSize: 32,
    fontWeight: 'bold',
    color: '#1F2937',
    paddingVertical: 16,
  },
  inputHint: {
    fontSize: 12,
    color: '#9CA3AF',
    marginBottom: 24,
  },

  // Summary
  summaryCard: {
    backgroundColor: '#FFF',
    borderRadius: 16,
    padding: 20,
    marginBottom: 16,
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  summaryLabel: {
    fontSize: 14,
    color: '#6B7280',
  },
  summaryValue: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1F2937',
  },
  summaryTotal: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#10B981',
  },
  divider: {
    height: 1,
    backgroundColor: '#E5E7EB',
    marginVertical: 12,
  },

  // Info Card
  infoCard: {
    backgroundColor: '#FFF',
    borderRadius: 16,
    padding: 16,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingVertical: 8,
  },
  infoText: {
    flex: 1,
    fontSize: 14,
    color: '#6B7280',
  },

  bottomPadding: {
    height: 100,
  },

  // Footer
  footer: {
    padding: 16,
    backgroundColor: '#FFF',
    borderTopWidth: 1,
    borderTopColor: '#E5E7EB',
  },
  footerDark: {
    backgroundColor: '#1F2937',
    borderTopColor: '#374151',
  },
  submitButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#3B82F6',
    borderRadius: 12,
    padding: 16,
    gap: 8,
  },
  submitButtonDisabled: {
    backgroundColor: '#9CA3AF',
  },
  submitButtonCancel: {
    backgroundColor: '#EF4444',
  },
  submitButtonText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFF',
  },

  // Step 2: Payment Methods
  amountDisplay: {
    backgroundColor: '#FFF',
    borderRadius: 16,
    padding: 20,
    marginBottom: 24,
    alignItems: 'center',
  },
  amountDisplayLabel: {
    fontSize: 14,
    color: '#6B7280',
    marginBottom: 8,
  },
  amountDisplayValue: {
    fontSize: 36,
    fontWeight: 'bold',
    color: '#1F2937',
  },
  loadingContainer: {
    alignItems: 'center',
    paddingVertical: 40,
  },
  loadingText: {
    marginTop: 12,
    fontSize: 14,
    color: '#6B7280',
  },
  methodList: {
    gap: 12,
  },
  methodCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFF',
    borderRadius: 16,
    padding: 16,
    borderWidth: 2,
    borderColor: '#E5E7EB',
  },
  methodCardSelected: {
    borderColor: '#3B82F6',
    backgroundColor: 'rgba(59, 130, 246, 0.1)',
  },
  methodCardDisabled: {
    opacity: 0.5,
  },
  methodIconContainer: {
    width: 48,
    height: 48,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  methodIcon: {
    fontSize: 24,
  },
  methodInfo: {
    flex: 1,
  },
  methodName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1F2937',
  },
  methodDesc: {
    fontSize: 12,
    color: '#6B7280',
    marginTop: 2,
  },
  methodUnavailable: {
    fontSize: 12,
    color: '#EF4444',
    marginTop: 2,
  },
  methodArrow: {
    fontSize: 20,
    color: '#9CA3AF',
  },

  // Step 3: Processing
  processingContainer: {
    flex: 1,
  },
  qrContainer: {
    backgroundColor: '#FFF',
    borderRadius: 16,
    padding: 24,
    alignItems: 'center',
    marginBottom: 16,
  },
  qrTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#1F2937',
    marginBottom: 16,
  },
  qrImageContainer: {
    backgroundColor: '#FFF',
    padding: 16,
    borderRadius: 12,
    marginBottom: 16,
  },
  qrImage: {
    width: 200,
    height: 200,
  },
  qrAmount: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: 8,
  },
  qrHint: {
    fontSize: 14,
    color: '#6B7280',
    textAlign: 'center',
  },
  bankInfoCard: {
    backgroundColor: '#FFF',
    borderRadius: 16,
    padding: 20,
    marginBottom: 16,
  },
  bankInfoTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#1F2937',
    marginBottom: 16,
  },
  bankInfoRow: {
    marginBottom: 12,
  },
  bankInfoLabel: {
    fontSize: 12,
    color: '#6B7280',
    marginBottom: 4,
  },
  bankInfoValue: {
    fontSize: 16,
    fontWeight: '500',
    color: '#1F2937',
  },
  bankInfoAmount: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#10B981',
  },
  copyRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  copyBtn: {
    backgroundColor: '#3B82F6',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
  },
  copyBtnText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#FFF',
  },
  statusCard: {
    backgroundColor: '#FFF',
    borderRadius: 16,
    padding: 20,
    alignItems: 'center',
    marginBottom: 16,
  },
  statusText: {
    fontSize: 16,
    fontWeight: '500',
    color: '#374151',
    marginTop: 12,
  },
  statusHint: {
    fontSize: 12,
    color: '#6B7280',
    marginTop: 4,
  },
  transactionIdContainer: {
    alignItems: 'center',
    paddingVertical: 8,
  },
  transactionIdLabel: {
    fontSize: 12,
    color: '#9CA3AF',
  },

  // Step 4: Result
  resultContainer: {
    flex: 1,
    padding: 16,
    justifyContent: 'center',
  },
  resultCard: {
    backgroundColor: '#FFF',
    borderRadius: 20,
    padding: 32,
    alignItems: 'center',
    marginBottom: 24,
  },
  resultIcon: {
    width: 80,
    height: 80,
    borderRadius: 40,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  resultIconSuccess: {
    backgroundColor: 'rgba(16, 185, 129, 0.1)',
  },
  resultIconFail: {
    backgroundColor: 'rgba(239, 68, 68, 0.1)',
  },
  resultEmoji: {
    fontSize: 40,
  },
  resultTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: 8,
  },
  resultAmount: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#10B981',
    marginBottom: 8,
  },
  resultHint: {
    fontSize: 14,
    color: '#6B7280',
    textAlign: 'center',
  },
  newBalanceContainer: {
    marginTop: 24,
    paddingTop: 24,
    borderTopWidth: 1,
    borderTopColor: '#E5E7EB',
    alignItems: 'center',
    width: '100%',
  },
  newBalanceLabel: {
    fontSize: 14,
    color: '#6B7280',
    marginBottom: 4,
  },
  newBalanceValue: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#1F2937',
  },
  resultActions: {
    gap: 12,
  },
  resultBtn: {
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
  },
  resultBtnPrimary: {
    backgroundColor: '#3B82F6',
  },
  resultBtnSecondary: {
    backgroundColor: 'transparent',
    borderWidth: 2,
    borderColor: '#E5E7EB',
  },
  resultBtnText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFF',
  },
  resultBtnTextSecondary: {
    fontSize: 16,
    fontWeight: '600',
    color: '#374151',
  },
});
