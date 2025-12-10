/**
 * หน้าถอนเงิน (Withdraw) - ตรงกับเว็บ
 *
 * Features:
 * - เลือกช่องทางการถอน (ธนาคาร, PromptPay)
 * - กรอกจำนวนเงิน
 * - คำนวณค่าธรรมเนียมและภาษี
 * - ใส่ PIN ยืนยัน
 * - แสดงยอดสุทธิที่จะได้รับ
 */

import React, { useState, useEffect, useCallback } from 'react';
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
  Modal,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useRouter } from 'expo-router';
import { useColorScheme } from 'react-native';
import { useAuthStore } from '@/stores/authStore';
import { getWallet, createWithdrawalRequest } from '@/services/api';
import { formatCurrency } from '@/constants';

// ช่องทางการถอน
interface PaymentMethod {
  id: string;
  name: string;
  icon: string;
  color: string;
  description: string;
}

const PAYMENT_METHODS: PaymentMethod[] = [
  {
    id: 'bank',
    name: 'โอนเข้าบัญชีธนาคาร',
    icon: '🏦',
    color: '#3B82F6',
    description: 'โอนเงินเข้าบัญชีธนาคารที่ผูกไว้',
  },
  {
    id: 'promptpay',
    name: 'PromptPay',
    icon: '📱',
    color: '#10B981',
    description: 'โอนผ่าน PromptPay',
  },
];

// ค่าธรรมเนียม (ตัวอย่าง - ควรดึงจาก API)
const FEE_RATE = 0; // ฟรี
const TAX_RATE = 0.03; // 3%
const MIN_WITHDRAW = 300;

export default function WalletWithdrawScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const { isAuthenticated } = useAuthStore();

  // State
  const [walletBalance, setWalletBalance] = useState<number>(0);
  const [selectedMethod, setSelectedMethod] = useState<PaymentMethod | null>(null);
  const [amount, setAmount] = useState<string>('');
  const [pin, setPin] = useState<string>('');
  const [note, setNote] = useState<string>('');
  const [showPinModal, setShowPinModal] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

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

  useEffect(() => {
    loadWallet();
  }, [loadWallet]);

  // คำนวณค่าธรรมเนียมและยอดสุทธิ
  const amountNum = parseFloat(amount) || 0;
  const fee = amountNum * FEE_RATE;
  const tax = amountNum * TAX_RATE;
  const netAmount = amountNum - fee - tax;
  const remainingBalance = walletBalance - amountNum;

  const isValidAmount = amountNum >= MIN_WITHDRAW && amountNum <= walletBalance;

  // เปิด Modal ใส่ PIN
  const handleContinue = () => {
    if (!selectedMethod) {
      Alert.alert('กรุณาเลือกช่องทาง', 'เลือกช่องทางการถอนเงิน');
      return;
    }

    if (!isValidAmount) {
      if (amountNum < MIN_WITHDRAW) {
        Alert.alert('จำนวนเงินไม่ถูกต้อง', `ถอนขั้นต่ำ ${formatCurrency(MIN_WITHDRAW)}`);
      } else {
        Alert.alert('ยอดเงินไม่พอ', 'ยอดเงินในกระเป๋าไม่เพียงพอ');
      }
      return;
    }

    setShowPinModal(true);
  };

  // ยืนยันการถอน
  const handleSubmit = async () => {
    if (pin.length !== 6) {
      Alert.alert('PIN ไม่ถูกต้อง', 'กรุณากรอก PIN 6 หลัก');
      return;
    }

    setIsSubmitting(true);

    try {
      const response = await createWithdrawalRequest({
        amount: amountNum,
        payment_method: selectedMethod?.id,
        pin,
        note,
      });

      if (response?.success) {
        setShowPinModal(false);
        Alert.alert(
          'ส่งคำขอสำเร็จ',
          `คำขอถอนเงิน ${formatCurrency(amountNum)} ถูกส่งแล้ว\nรอการอนุมัติภายใน 1-3 วันทำการ`,
          [{ text: 'ตกลง', onPress: () => router.back() }]
        );
      } else {
        Alert.alert('เกิดข้อผิดพลาด', response?.message || 'ไม่สามารถส่งคำขอถอนเงินได้');
      }
    } catch (error: any) {
      Alert.alert('เกิดข้อผิดพลาด', error?.message || 'ไม่สามารถส่งคำขอถอนเงินได้');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <View style={[styles.container, isDark && styles.containerDark]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />

      {/* Header */}
      <LinearGradient colors={['#10B981', '#059669']} style={styles.header}>
        <TouchableOpacity style={styles.backButton} onPress={() => router.back()}>
          <Text style={styles.headerIcon}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>ถอนเงิน</Text>
        <View style={styles.headerSpacer} />
      </LinearGradient>

      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.keyboardView}
      >
        <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
          {/* ยอดเงินคงเหลือ */}
          <View style={[styles.balanceCard, isDark && styles.cardDark]}>
            <Text style={[styles.balanceLabel, isDark && styles.textMuted]}>
              ยอดเงินที่ถอนได้
            </Text>
            <Text style={[styles.balanceAmount, isDark && styles.textLight]}>
              {isLoading ? '...' : formatCurrency(walletBalance)}
            </Text>
          </View>

          {/* เลือกช่องทาง */}
          <Text style={[styles.sectionTitle, isDark && styles.textLight]}>
            เลือกช่องทางการรับเงิน
          </Text>

          {PAYMENT_METHODS.map((method) => (
            <TouchableOpacity
              key={method.id}
              style={[
                styles.methodCard,
                isDark && styles.cardDark,
                selectedMethod?.id === method.id && styles.methodCardSelected,
              ]}
              onPress={() => setSelectedMethod(method)}
            >
              <View style={[styles.methodIcon, { backgroundColor: `${method.color}20` }]}>
                <Text style={styles.methodEmoji}>{method.icon}</Text>
              </View>
              <View style={styles.methodInfo}>
                <Text style={[styles.methodName, isDark && styles.textLight]}>
                  {method.name}
                </Text>
                <Text style={[styles.methodDesc, isDark && styles.textMuted]}>
                  {method.description}
                </Text>
              </View>
              {selectedMethod?.id === method.id && (
                <Text style={styles.checkIcon}>✓</Text>
              )}
            </TouchableOpacity>
          ))}

          {/* กรอกจำนวนเงิน */}
          <Text style={[styles.sectionTitle, isDark && styles.textLight]}>
            จำนวนเงินที่ต้องการถอน
          </Text>

          <View style={[styles.inputContainer, isDark && styles.inputContainerDark]}>
            <Text style={[styles.inputPrefix, isDark && styles.textMuted]}>฿</Text>
            <TextInput
              style={[styles.input, isDark && styles.textLight]}
              value={amount}
              onChangeText={(text) => setAmount(text.replace(/[^0-9]/g, ''))}
              placeholder="0"
              placeholderTextColor="#9CA3AF"
              keyboardType="numeric"
              maxLength={7}
            />
            <TouchableOpacity
              style={styles.maxButton}
              onPress={() => setAmount(String(Math.floor(walletBalance)))}
            >
              <Text style={styles.maxButtonText}>ถอนทั้งหมด</Text>
            </TouchableOpacity>
          </View>

          <Text style={styles.inputHint}>ถอนขั้นต่ำ {formatCurrency(MIN_WITHDRAW)}</Text>

          {/* หมายเหตุ */}
          <Text style={[styles.sectionTitle, isDark && styles.textLight]}>
            หมายเหตุ (ไม่บังคับ)
          </Text>

          <TextInput
            style={[styles.noteInput, isDark && styles.noteInputDark, isDark && styles.textLight]}
            value={note}
            onChangeText={setNote}
            placeholder="ระบุหมายเหตุ..."
            placeholderTextColor="#9CA3AF"
            multiline
            maxLength={200}
          />

          {/* สรุปการถอน */}
          {amountNum > 0 && (
            <View style={[styles.summaryCard, isDark && styles.cardDark]}>
              <Text style={[styles.summaryTitle, isDark && styles.textLight]}>
                สรุปการถอนเงิน
              </Text>

              <View style={styles.summaryRow}>
                <Text style={[styles.summaryLabel, isDark && styles.textMuted]}>
                  จำนวนเงินที่ถอน
                </Text>
                <Text style={[styles.summaryValue, isDark && styles.textLight]}>
                  {formatCurrency(amountNum)}
                </Text>
              </View>

              {fee > 0 && (
                <View style={styles.summaryRow}>
                  <Text style={[styles.summaryLabel, isDark && styles.textMuted]}>
                    ค่าธรรมเนียม
                  </Text>
                  <Text style={styles.feeText}>-{formatCurrency(fee)}</Text>
                </View>
              )}

              {tax > 0 && (
                <View style={styles.summaryRow}>
                  <Text style={[styles.summaryLabel, isDark && styles.textMuted]}>
                    ภาษีหัก ณ ที่จ่าย (3%)
                  </Text>
                  <Text style={styles.feeText}>-{formatCurrency(tax)}</Text>
                </View>
              )}

              <View style={styles.divider} />

              <View style={styles.summaryRow}>
                <Text style={[styles.summaryLabelBold, isDark && styles.textLight]}>
                  ยอดสุทธิที่จะได้รับ
                </Text>
                <Text style={styles.netAmount}>{formatCurrency(netAmount)}</Text>
              </View>

              <View style={styles.summaryRow}>
                <Text style={[styles.summaryLabel, isDark && styles.textMuted]}>
                  ยอดคงเหลือหลังถอน
                </Text>
                <Text
                  style={[
                    styles.summaryValue,
                    remainingBalance < 0 && styles.errorText,
                    isDark && styles.textLight,
                  ]}
                >
                  {formatCurrency(remainingBalance)}
                </Text>
              </View>
            </View>
          )}

          <View style={styles.bottomPadding} />
        </ScrollView>

        {/* ปุ่มดำเนินการ */}
        <View style={[styles.footer, isDark && styles.footerDark]}>
          <TouchableOpacity
            style={[styles.submitButton, !isValidAmount && styles.submitButtonDisabled]}
            onPress={handleContinue}
            disabled={!isValidAmount}
          >
            <Text style={styles.submitButtonText}>ดำเนินการถอนเงิน</Text>
            <Text style={styles.buttonIcon}>→</Text>
          </TouchableOpacity>
        </View>
      </KeyboardAvoidingView>

      {/* PIN Modal */}
      <Modal
        visible={showPinModal}
        transparent
        animationType="slide"
        onRequestClose={() => setShowPinModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={[styles.modalContent, isDark && styles.modalContentDark]}>
            <View style={styles.modalHeader}>
              <Text style={[styles.modalTitle, isDark && styles.textLight]}>
                ยืนยันด้วย PIN
              </Text>
              <TouchableOpacity onPress={() => setShowPinModal(false)}>
                <Text style={[styles.modalIcon, isDark && styles.textLight]}>✕</Text>
              </TouchableOpacity>
            </View>

            <Text style={[styles.modalDesc, isDark && styles.textMuted]}>
              กรอก PIN 6 หลักเพื่อยืนยันการถอนเงิน
            </Text>

            <View style={styles.pinContainer}>
              <TextInput
                style={[styles.pinInput, isDark && styles.pinInputDark]}
                value={pin}
                onChangeText={(text) => setPin(text.replace(/[^0-9]/g, '').slice(0, 6))}
                placeholder="● ● ● ● ● ●"
                placeholderTextColor="#9CA3AF"
                keyboardType="numeric"
                secureTextEntry
                maxLength={6}
                textAlign="center"
              />
            </View>

            <View style={styles.modalSummary}>
              <Text style={[styles.modalSummaryLabel, isDark && styles.textMuted]}>
                จำนวนเงินที่ถอน
              </Text>
              <Text style={styles.modalSummaryAmount}>{formatCurrency(amountNum)}</Text>
              <Text style={[styles.modalSummaryLabel, isDark && styles.textMuted]}>
                ยอดสุทธิที่จะได้รับ
              </Text>
              <Text style={styles.modalNetAmount}>{formatCurrency(netAmount)}</Text>
            </View>

            <TouchableOpacity
              style={[styles.modalButton, pin.length !== 6 && styles.modalButtonDisabled]}
              onPress={handleSubmit}
              disabled={pin.length !== 6 || isSubmitting}
            >
              {isSubmitting ? (
                <ActivityIndicator color="#FFF" />
              ) : (
                <Text style={styles.modalButtonText}>ยืนยันการถอนเงิน</Text>
              )}
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
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
  methodEmoji: {
    fontSize: 24,
  },
  checkIcon: {
    fontSize: 24,
    color: '#10B981',
    fontWeight: 'bold',
  },
  buttonIcon: {
    fontSize: 20,
    color: '#FFF',
    fontWeight: 'bold',
  },
  modalIcon: {
    fontSize: 24,
    fontWeight: 'bold',
  },

  // Balance Card
  balanceCard: {
    backgroundColor: '#FFF',
    borderRadius: 16,
    padding: 20,
    marginBottom: 24,
    alignItems: 'center',
  },
  cardDark: {
    backgroundColor: '#1F2937',
  },
  balanceLabel: {
    fontSize: 14,
    color: '#6B7280',
    marginBottom: 4,
  },
  balanceAmount: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#10B981',
  },

  // Section
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#374151',
    marginBottom: 12,
  },

  // Method Card
  methodCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFF',
    borderRadius: 12,
    padding: 16,
    marginBottom: 10,
    borderWidth: 2,
    borderColor: '#E5E7EB',
  },
  methodCardSelected: {
    borderColor: '#10B981',
    backgroundColor: 'rgba(16, 185, 129, 0.05)',
  },
  methodIcon: {
    width: 48,
    height: 48,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  methodInfo: {
    flex: 1,
  },
  methodName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#374151',
  },
  methodDesc: {
    fontSize: 12,
    color: '#9CA3AF',
    marginTop: 2,
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
    fontSize: 28,
    fontWeight: 'bold',
    color: '#1F2937',
    paddingVertical: 16,
  },
  maxButton: {
    backgroundColor: '#10B981',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
  },
  maxButtonText: {
    color: '#FFF',
    fontSize: 12,
    fontWeight: '600',
  },
  inputHint: {
    fontSize: 12,
    color: '#9CA3AF',
    marginBottom: 16,
  },

  // Note Input
  noteInput: {
    backgroundColor: '#FFF',
    borderRadius: 12,
    borderWidth: 2,
    borderColor: '#E5E7EB',
    padding: 16,
    fontSize: 14,
    color: '#1F2937',
    minHeight: 80,
    textAlignVertical: 'top',
    marginBottom: 16,
  },
  noteInputDark: {
    backgroundColor: '#1F2937',
    borderColor: '#374151',
  },

  // Summary
  summaryCard: {
    backgroundColor: '#FFF',
    borderRadius: 16,
    padding: 20,
  },
  summaryTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#374151',
    marginBottom: 16,
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  summaryLabel: {
    fontSize: 14,
    color: '#6B7280',
  },
  summaryLabelBold: {
    fontSize: 14,
    fontWeight: '600',
    color: '#374151',
  },
  summaryValue: {
    fontSize: 14,
    fontWeight: '600',
    color: '#374151',
  },
  feeText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#EF4444',
  },
  netAmount: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#10B981',
  },
  errorText: {
    color: '#EF4444',
  },
  divider: {
    height: 1,
    backgroundColor: '#E5E7EB',
    marginVertical: 12,
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
    backgroundColor: '#10B981',
    borderRadius: 12,
    padding: 16,
    gap: 8,
  },
  submitButtonDisabled: {
    backgroundColor: '#9CA3AF',
  },
  submitButtonText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFF',
  },

  // Modal
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#FFF',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: 24,
  },
  modalContentDark: {
    backgroundColor: '#1F2937',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#374151',
  },
  modalDesc: {
    fontSize: 14,
    color: '#6B7280',
    marginBottom: 24,
  },
  pinContainer: {
    marginBottom: 24,
  },
  pinInput: {
    backgroundColor: '#F3F4F6',
    borderRadius: 12,
    padding: 16,
    fontSize: 32,
    fontWeight: 'bold',
    color: '#374151',
    letterSpacing: 8,
  },
  pinInputDark: {
    backgroundColor: '#374151',
    color: '#FFF',
  },
  modalSummary: {
    alignItems: 'center',
    marginBottom: 24,
  },
  modalSummaryLabel: {
    fontSize: 12,
    color: '#6B7280',
    marginBottom: 4,
  },
  modalSummaryAmount: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#374151',
    marginBottom: 12,
  },
  modalNetAmount: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#10B981',
  },
  modalButton: {
    backgroundColor: '#10B981',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
  },
  modalButtonDisabled: {
    backgroundColor: '#9CA3AF',
  },
  modalButtonText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFF',
  },
});
