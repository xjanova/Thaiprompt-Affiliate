/**
 * หน้าเติมเงิน (Top-up) - ตรงกับเว็บ
 *
 * Features:
 * - ปุ่มจำนวนเงินด่วน: 100, 300, 500, 1000, 2000, 5000, 10000
 * - กรอกจำนวนเงินเอง (100-100,000 บาท)
 * - แสดงยอดเงินปัจจุบัน
 * - ไปหน้า Checkout เพื่อชำระเงิน
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
  Linking,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useRouter } from 'expo-router';
import { useColorScheme } from 'react-native';
import { useAuthStore } from '@/stores/authStore';
import { getWallet } from '@/services/api';
import { formatCurrency } from '@/constants';
import { API_BASE_URL } from '@/services/api';

// จำนวนเงินด่วน
const QUICK_AMOUNTS = [100, 300, 500, 1000, 2000, 5000, 10000];

// ขั้นต่ำและขั้นสูง
const MIN_AMOUNT = 100;
const MAX_AMOUNT = 100000;

export default function WalletTopupScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const { isAuthenticated, token } = useAuthStore();

  // State
  const [walletBalance, setWalletBalance] = useState<number>(0);
  const [selectedAmount, setSelectedAmount] = useState<number | null>(null);
  const [customAmount, setCustomAmount] = useState<string>('');
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
    // อนุญาตเฉพาะตัวเลข
    const numericValue = text.replace(/[^0-9]/g, '');
    setCustomAmount(numericValue);
    setSelectedAmount(null);
  };

  // ยืนยันเติมเงิน
  const handleSubmit = async () => {
    const amount = getAmount();

    // ตรวจสอบจำนวนเงิน
    if (amount < MIN_AMOUNT) {
      Alert.alert('จำนวนเงินไม่ถูกต้อง', `เติมเงินขั้นต่ำ ${formatCurrency(MIN_AMOUNT)}`);
      return;
    }

    if (amount > MAX_AMOUNT) {
      Alert.alert('จำนวนเงินไม่ถูกต้อง', `เติมเงินสูงสุด ${formatCurrency(MAX_AMOUNT)}`);
      return;
    }

    setIsSubmitting(true);

    try {
      // เปิดหน้าเติมเงินบนเว็บพร้อมจำนวนเงิน
      const topupUrl = `${API_BASE_URL.replace('/api', '')}/user/wallet/topup?amount=${amount}&from_app=1`;
      await Linking.openURL(topupUrl);
    } catch (error) {
      Alert.alert('เกิดข้อผิดพลาด', 'ไม่สามารถเปิดหน้าชำระเงินได้');
    } finally {
      setIsSubmitting(false);
    }
  };

  const amount = getAmount();
  const isValidAmount = amount >= MIN_AMOUNT && amount <= MAX_AMOUNT;

  return (
    <View style={[styles.container, isDark && styles.containerDark]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />

      {/* Header */}
      <LinearGradient
        colors={['#3B82F6', '#2563EB']}
        style={styles.header}
      >
        <TouchableOpacity style={styles.backButton} onPress={() => router.back()}>
          <Text style={styles.headerIcon}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>เติมเงิน</Text>
        <View style={styles.headerSpacer} />
      </LinearGradient>

      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.keyboardView}
      >
        <ScrollView
          style={styles.content}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
        >
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
                รองรับ บัตรเครดิต/เดบิต, PromptPay, Mobile Banking
              </Text>
            </View>
          </View>

          <View style={styles.bottomPadding} />
        </ScrollView>

        {/* ปุ่มยืนยัน */}
        <View style={[styles.footer, isDark && styles.footerDark]}>
          <TouchableOpacity
            style={[
              styles.submitButton,
              !isValidAmount && styles.submitButtonDisabled,
            ]}
            onPress={handleSubmit}
            disabled={!isValidAmount || isSubmitting}
          >
            {isSubmitting ? (
              <ActivityIndicator color="#FFF" />
            ) : (
              <>
                <Text style={styles.buttonIcon}>➕</Text>
                <Text style={styles.submitButtonText}>
                  เติมเงิน {amount > 0 ? formatCurrency(amount) : ''}
                </Text>
              </>
            )}
          </TouchableOpacity>
        </View>
      </KeyboardAvoidingView>
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
  submitButtonText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFF',
  },
});
