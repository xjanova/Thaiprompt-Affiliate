/**
 * หน้าโอนเงิน (Transfer) - ตรงกับเว็บ
 *
 * Features:
 * - สแกน QR Code หรือกรอก Wallet Address
 * - ค้นหาผู้รับและแสดงข้อมูล
 * - กรอกจำนวนเงิน
 * - คำนวณค่าธรรมเนียม (ผู้โอนจ่าย)
 * - แสดงยอดคงเหลือหลังโอน
 * - ใส่ PIN ยืนยัน
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
  Image,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useRouter } from 'expo-router';
import { useColorScheme } from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { useAuthStore } from '@/stores/authStore';
import { getWallet, lookupWalletAddress, transferMoney } from '@/services/api';
import { formatCurrency } from '@/constants';

// ค่าธรรมเนียมโอน (ตัวอย่าง - ควรดึงจาก API)
const TRANSFER_FEE_RATE = 0.01; // 1%
const TRANSFER_FEE_MIN = 0;
const TRANSFER_FEE_MAX = 100;
const MIN_TRANSFER = 10;

// ข้อมูลผู้รับ
interface RecipientInfo {
  walletAddress: string;
  name: string;
  avatar?: string;
  userId: number;
}

export default function WalletTransferScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const { isAuthenticated, user } = useAuthStore();
  const [permission, requestPermission] = useCameraPermissions();

  // State
  const [walletBalance, setWalletBalance] = useState<number>(0);
  const [walletAddress, setWalletAddress] = useState<string>('');
  const [recipient, setRecipient] = useState<RecipientInfo | null>(null);
  const [amount, setAmount] = useState<string>('');
  const [pin, setPin] = useState<string>('');
  const [note, setNote] = useState<string>('');
  const [showScanner, setShowScanner] = useState(false);
  const [showPinModal, setShowPinModal] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [isLookingUp, setIsLookingUp] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [lookupError, setLookupError] = useState<string | null>(null);

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

  // คำนวณค่าธรรมเนียม
  const amountNum = parseFloat(amount) || 0;
  const calculateFee = (amt: number): number => {
    if (amt <= 0) return 0;
    let fee = amt * TRANSFER_FEE_RATE;
    fee = Math.max(TRANSFER_FEE_MIN, Math.min(TRANSFER_FEE_MAX, fee));
    return Math.round(fee * 100) / 100;
  };

  const fee = calculateFee(amountNum);
  const totalDeduction = amountNum + fee;
  const remainingBalance = walletBalance - totalDeduction;

  const isValidAmount = amountNum >= MIN_TRANSFER && totalDeduction <= walletBalance;
  const canTransfer = recipient && isValidAmount;

  // ค้นหาผู้รับจาก wallet address
  const lookupRecipient = async (address: string) => {
    if (!address || address.length < 10) {
      setRecipient(null);
      setLookupError(null);
      return;
    }

    // ตรวจสอบว่าไม่ใช่ตัวเอง
    if (user?.wallet_address === address) {
      setLookupError('ไม่สามารถโอนเงินให้ตัวเองได้');
      setRecipient(null);
      return;
    }

    setIsLookingUp(true);
    setLookupError(null);

    try {
      const response = await lookupWalletAddress(address);
      if (response?.success && response.data) {
        setRecipient({
          walletAddress: address,
          name: response.data.name,
          avatar: response.data.avatar,
          userId: response.data.user_id,
        });
        setLookupError(null);
      } else {
        setRecipient(null);
        setLookupError('ไม่พบ Wallet นี้ในระบบ');
      }
    } catch (error) {
      setRecipient(null);
      setLookupError('ไม่สามารถค้นหาผู้รับได้');
    } finally {
      setIsLookingUp(false);
    }
  };

  // จัดการ QR scan
  const handleBarCodeScanned = ({ data }: { data: string }) => {
    setShowScanner(false);
    setWalletAddress(data);
    lookupRecipient(data);
  };

  // เปิด Scanner
  const openScanner = async () => {
    if (!permission?.granted) {
      const result = await requestPermission();
      if (!result.granted) {
        Alert.alert('ต้องการสิทธิ์', 'กรุณาอนุญาตการใช้กล้องเพื่อสแกน QR Code');
        return;
      }
    }
    setShowScanner(true);
  };

  // เปิด Modal ใส่ PIN
  const handleContinue = () => {
    if (!recipient) {
      Alert.alert('ไม่พบผู้รับ', 'กรุณากรอก Wallet Address หรือสแกน QR Code');
      return;
    }

    if (!isValidAmount) {
      if (amountNum < MIN_TRANSFER) {
        Alert.alert('จำนวนเงินไม่ถูกต้อง', `โอนขั้นต่ำ ${formatCurrency(MIN_TRANSFER)}`);
      } else {
        Alert.alert(
          'ยอดเงินไม่พอ',
          `ต้องการ ${formatCurrency(totalDeduction)} (รวมค่าธรรมเนียม)\nยอดคงเหลือ ${formatCurrency(walletBalance)}`
        );
      }
      return;
    }

    setShowPinModal(true);
  };

  // ยืนยันการโอน
  const handleSubmit = async () => {
    if (pin.length !== 6) {
      Alert.alert('PIN ไม่ถูกต้อง', 'กรุณากรอก PIN 6 หลัก');
      return;
    }

    setIsSubmitting(true);

    try {
      const response = await transferMoney({
        wallet_address: recipient!.walletAddress,
        amount: amountNum,
        pin,
        note,
      });

      if (response?.success) {
        setShowPinModal(false);
        Alert.alert(
          'โอนเงินสำเร็จ! ✓',
          `โอน ${formatCurrency(amountNum)} ให้ ${recipient!.name}\nค่าธรรมเนียม ${formatCurrency(fee)}\nยอดคงเหลือ ${formatCurrency(remainingBalance)}`,
          [{ text: 'ตกลง', onPress: () => router.back() }]
        );
      } else {
        Alert.alert('โอนเงินไม่สำเร็จ', response?.message || 'กรุณาตรวจสอบข้อมูลและลองใหม่');
      }
    } catch (error: any) {
      Alert.alert('เกิดข้อผิดพลาด', error?.message || 'ไม่สามารถโอนเงินได้');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <View style={[styles.container, isDark && styles.containerDark]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />

      {/* Header */}
      <LinearGradient colors={['#8B5CF6', '#7C3AED']} style={styles.header}>
        <TouchableOpacity style={styles.backButton} onPress={() => router.back()}>
          <Text style={styles.headerIcon}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>โอนเงิน</Text>
        <TouchableOpacity style={styles.scanButton} onPress={openScanner}>
          <Text style={styles.headerIcon}>📷</Text>
        </TouchableOpacity>
      </LinearGradient>

      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.keyboardView}
      >
        <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
          {/* ยอดเงินคงเหลือ */}
          <View style={[styles.balanceCard, isDark && styles.cardDark]}>
            <Text style={[styles.balanceLabel, isDark && styles.textMuted]}>
              ยอดเงินคงเหลือ
            </Text>
            <Text style={[styles.balanceAmount, isDark && styles.textLight]}>
              {isLoading ? '...' : formatCurrency(walletBalance)}
            </Text>
          </View>

          {/* กรอก Wallet Address */}
          <Text style={[styles.sectionTitle, isDark && styles.textLight]}>
            Wallet Address ผู้รับ
          </Text>

          <View style={[styles.inputRow, isDark && styles.inputRowDark]}>
            <Text style={styles.inputEmoji}>💰</Text>
            <TextInput
              style={[styles.addressInput, isDark && styles.textLight]}
              value={walletAddress}
              onChangeText={(text) => {
                setWalletAddress(text);
                if (text.length >= 10) {
                  lookupRecipient(text);
                } else {
                  setRecipient(null);
                  setLookupError(null);
                }
              }}
              placeholder="กรอก Wallet Address หรือสแกน QR"
              placeholderTextColor="#9CA3AF"
              autoCapitalize="none"
            />
            {isLookingUp && <ActivityIndicator size="small" color="#8B5CF6" />}
          </View>

          {/* แสดงข้อผิดพลาด */}
          {lookupError && (
            <View style={styles.errorBox}>
              <Text style={styles.errorEmoji}>⚠️</Text>
              <Text style={styles.errorText}>{lookupError}</Text>
            </View>
          )}

          {/* แสดงข้อมูลผู้รับ */}
          {recipient && (
            <View style={[styles.recipientCard, isDark && styles.cardDark]}>
              <View style={styles.recipientAvatar}>
                {recipient.avatar ? (
                  <Image source={{ uri: recipient.avatar }} style={styles.avatarImage} />
                ) : (
                  <LinearGradient colors={['#8B5CF6', '#EC4899']} style={styles.avatarPlaceholder}>
                    <Text style={styles.avatarText}>
                      {recipient.name.charAt(0).toUpperCase()}
                    </Text>
                  </LinearGradient>
                )}
              </View>
              <View style={styles.recipientInfo}>
                <Text style={[styles.recipientName, isDark && styles.textLight]}>
                  {recipient.name}
                </Text>
                <Text style={[styles.recipientAddress, isDark && styles.textMuted]}>
                  {recipient.walletAddress}
                </Text>
              </View>
              <Text style={styles.checkIcon}>✓</Text>
            </View>
          )}

          {/* กรอกจำนวนเงิน */}
          <Text style={[styles.sectionTitle, isDark && styles.textLight]}>
            จำนวนเงินที่ต้องการโอน
          </Text>

          <View style={[styles.amountContainer, isDark && styles.amountContainerDark]}>
            <Text style={[styles.amountPrefix, isDark && styles.textMuted]}>฿</Text>
            <TextInput
              style={[styles.amountInput, isDark && styles.textLight]}
              value={amount}
              onChangeText={(text) => setAmount(text.replace(/[^0-9]/g, ''))}
              placeholder="0"
              placeholderTextColor="#9CA3AF"
              keyboardType="numeric"
              maxLength={7}
            />
          </View>

          <Text style={styles.inputHint}>โอนขั้นต่ำ {formatCurrency(MIN_TRANSFER)}</Text>

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
            maxLength={100}
          />

          {/* สรุปการโอน */}
          {amountNum > 0 && (
            <View style={[styles.summaryCard, isDark && styles.cardDark]}>
              <Text style={[styles.summaryTitle, isDark && styles.textLight]}>
                สรุปการโอนเงิน
              </Text>

              <View style={styles.summaryRow}>
                <Text style={[styles.summaryLabel, isDark && styles.textMuted]}>
                  จำนวนเงินที่โอน
                </Text>
                <Text style={[styles.summaryValue, isDark && styles.textLight]}>
                  {formatCurrency(amountNum)}
                </Text>
              </View>

              <View style={styles.summaryRow}>
                <Text style={[styles.summaryLabel, isDark && styles.textMuted]}>
                  ค่าธรรมเนียม ({(TRANSFER_FEE_RATE * 100).toFixed(0)}%)
                </Text>
                <Text style={styles.feeText}>+{formatCurrency(fee)}</Text>
              </View>

              <View style={styles.divider} />

              <View style={styles.summaryRow}>
                <Text style={[styles.summaryLabelBold, isDark && styles.textLight]}>
                  รวมที่หักจากกระเป๋า
                </Text>
                <Text style={styles.totalAmount}>{formatCurrency(totalDeduction)}</Text>
              </View>

              <View style={styles.summaryRow}>
                <Text style={[styles.summaryLabel, isDark && styles.textMuted]}>
                  ยอดคงเหลือหลังโอน
                </Text>
                <Text
                  style={[
                    styles.summaryValue,
                    remainingBalance < 0 && styles.errorTextRed,
                    isDark && styles.textLight,
                  ]}
                >
                  {formatCurrency(remainingBalance)}
                </Text>
              </View>

              {remainingBalance < 0 && (
                <View style={styles.warningBox}>
                  <Text style={styles.warningEmoji}>⚠️</Text>
                  <Text style={styles.warningText}>ยอดเงินไม่เพียงพอ</Text>
                </View>
              )}
            </View>
          )}

          <View style={styles.bottomPadding} />
        </ScrollView>

        {/* ปุ่มดำเนินการ */}
        <View style={[styles.footer, isDark && styles.footerDark]}>
          <TouchableOpacity
            style={[styles.submitButton, !canTransfer && styles.submitButtonDisabled]}
            onPress={handleContinue}
            disabled={!canTransfer}
          >
            <Text style={styles.buttonIcon}>📤</Text>
            <Text style={styles.submitButtonText}>โอนเงิน</Text>
          </TouchableOpacity>
        </View>
      </KeyboardAvoidingView>

      {/* QR Scanner Modal */}
      <Modal visible={showScanner} animationType="slide" onRequestClose={() => setShowScanner(false)}>
        <View style={styles.scannerContainer}>
          <View style={styles.scannerHeader}>
            <TouchableOpacity onPress={() => setShowScanner(false)}>
              <Text style={styles.scannerIcon}>✕</Text>
            </TouchableOpacity>
            <Text style={styles.scannerTitle}>สแกน QR Code</Text>
            <View style={{ width: 28 }} />
          </View>

          <CameraView
            style={styles.camera}
            facing="back"
            barcodeScannerSettings={{ barcodeTypes: ['qr'] }}
            onBarcodeScanned={handleBarCodeScanned}
          >
            <View style={styles.scannerOverlay}>
              <View style={styles.scannerFrame} />
            </View>
          </CameraView>

          <View style={styles.scannerFooter}>
            <Text style={styles.scannerHint}>วาง QR Code ในกรอบเพื่อสแกน</Text>
          </View>
        </View>
      </Modal>

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
              กรอก PIN 6 หลักเพื่อยืนยันการโอนเงิน
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

            {/* สรุปใน Modal */}
            <View style={[styles.modalSummary, isDark && styles.modalSummaryDark]}>
              <View style={styles.modalSummaryRow}>
                <Text style={[styles.modalSummaryLabel, isDark && styles.textMuted]}>ผู้รับ</Text>
                <Text style={[styles.modalSummaryValue, isDark && styles.textLight]}>
                  {recipient?.name}
                </Text>
              </View>
              <View style={styles.modalSummaryRow}>
                <Text style={[styles.modalSummaryLabel, isDark && styles.textMuted]}>จำนวนเงิน</Text>
                <Text style={styles.modalAmountText}>{formatCurrency(amountNum)}</Text>
              </View>
              <View style={styles.modalSummaryRow}>
                <Text style={[styles.modalSummaryLabel, isDark && styles.textMuted]}>ค่าธรรมเนียม</Text>
                <Text style={[styles.modalSummaryValue, isDark && styles.textMuted]}>
                  {formatCurrency(fee)}
                </Text>
              </View>
              <View style={styles.modalDivider} />
              <View style={styles.modalSummaryRow}>
                <Text style={[styles.modalSummaryLabelBold, isDark && styles.textLight]}>รวมหักจากกระเป๋า</Text>
                <Text style={styles.modalTotalText}>{formatCurrency(totalDeduction)}</Text>
              </View>
            </View>

            <TouchableOpacity
              style={[styles.modalButton, pin.length !== 6 && styles.modalButtonDisabled]}
              onPress={handleSubmit}
              disabled={pin.length !== 6 || isSubmitting}
            >
              {isSubmitting ? (
                <ActivityIndicator color="#FFF" />
              ) : (
                <>
                  <Text style={styles.buttonIcon}>📤</Text>
                  <Text style={styles.modalButtonText}>ยืนยันการโอน</Text>
                </>
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
  scanButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.2)',
    justifyContent: 'center',
    alignItems: 'center',
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
  inputEmoji: {
    fontSize: 20,
  },
  errorEmoji: {
    fontSize: 16,
  },
  checkIcon: {
    fontSize: 24,
    color: '#10B981',
    fontWeight: 'bold',
  },
  warningEmoji: {
    fontSize: 16,
  },
  buttonIcon: {
    fontSize: 20,
  },
  scannerIcon: {
    fontSize: 28,
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
    color: '#1F2937',
  },

  // Section
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#374151',
    marginBottom: 12,
  },

  // Input Row
  inputRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFF',
    borderRadius: 12,
    borderWidth: 2,
    borderColor: '#E5E7EB',
    paddingHorizontal: 16,
    paddingVertical: 4,
    marginBottom: 8,
    gap: 12,
  },
  inputRowDark: {
    backgroundColor: '#1F2937',
    borderColor: '#374151',
  },
  addressInput: {
    flex: 1,
    fontSize: 14,
    color: '#1F2937',
    paddingVertical: 12,
  },

  // Error Box
  errorBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 16,
  },
  errorText: {
    color: '#EF4444',
    fontSize: 14,
  },
  errorTextRed: {
    color: '#EF4444',
  },

  // Recipient Card
  recipientCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFF',
    borderRadius: 16,
    padding: 16,
    marginBottom: 24,
    borderWidth: 2,
    borderColor: '#10B981',
  },
  recipientAvatar: {
    marginRight: 12,
  },
  avatarImage: {
    width: 48,
    height: 48,
    borderRadius: 24,
  },
  avatarPlaceholder: {
    width: 48,
    height: 48,
    borderRadius: 24,
    justifyContent: 'center',
    alignItems: 'center',
  },
  avatarText: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFF',
  },
  recipientInfo: {
    flex: 1,
  },
  recipientName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#374151',
  },
  recipientAddress: {
    fontSize: 12,
    color: '#9CA3AF',
    marginTop: 2,
  },

  // Amount Input
  amountContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFF',
    borderRadius: 12,
    borderWidth: 2,
    borderColor: '#E5E7EB',
    paddingHorizontal: 16,
    marginBottom: 8,
  },
  amountContainerDark: {
    backgroundColor: '#1F2937',
    borderColor: '#374151',
  },
  amountPrefix: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#6B7280',
    marginRight: 8,
  },
  amountInput: {
    flex: 1,
    fontSize: 32,
    fontWeight: 'bold',
    color: '#1F2937',
    paddingVertical: 16,
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
    color: '#F59E0B',
  },
  totalAmount: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#8B5CF6',
  },
  divider: {
    height: 1,
    backgroundColor: '#E5E7EB',
    marginVertical: 12,
  },
  warningBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: 'rgba(245, 158, 11, 0.1)',
    padding: 12,
    borderRadius: 8,
    marginTop: 8,
  },
  warningText: {
    color: '#F59E0B',
    fontSize: 14,
    fontWeight: '500',
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
    backgroundColor: '#8B5CF6',
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

  // Scanner
  scannerContainer: {
    flex: 1,
    backgroundColor: '#000',
  },
  scannerHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingTop: 50,
    paddingHorizontal: 16,
    paddingBottom: 16,
  },
  scannerTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFF',
  },
  camera: {
    flex: 1,
  },
  scannerOverlay: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.5)',
  },
  scannerFrame: {
    width: 250,
    height: 250,
    borderWidth: 3,
    borderColor: '#8B5CF6',
    borderRadius: 16,
    backgroundColor: 'transparent',
  },
  scannerFooter: {
    padding: 24,
    alignItems: 'center',
  },
  scannerHint: {
    color: '#FFF',
    fontSize: 14,
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
    backgroundColor: '#F9FAFB',
    borderRadius: 12,
    padding: 16,
    marginBottom: 24,
  },
  modalSummaryDark: {
    backgroundColor: '#374151',
  },
  modalSummaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  modalSummaryLabel: {
    fontSize: 14,
    color: '#6B7280',
  },
  modalSummaryLabelBold: {
    fontSize: 14,
    fontWeight: '600',
    color: '#374151',
  },
  modalSummaryValue: {
    fontSize: 14,
    fontWeight: '500',
    color: '#374151',
  },
  modalAmountText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#8B5CF6',
  },
  modalDivider: {
    height: 1,
    backgroundColor: '#E5E7EB',
    marginVertical: 8,
  },
  modalTotalText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#8B5CF6',
  },
  modalButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#8B5CF6',
    borderRadius: 12,
    padding: 16,
    gap: 8,
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
