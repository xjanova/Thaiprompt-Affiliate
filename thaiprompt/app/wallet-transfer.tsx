/**
 * หน้าโอนเงิน (Transfer) - ตรงกับเว็บ · ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * Features:
 * - สแกน QR Code หรือกรอก Wallet Address
 * - ค้นหาผู้รับและแสดงข้อมูล
 * - กรอกจำนวนเงิน
 * - คำนวณค่าธรรมเนียม (ผู้โอนจ่าย)
 * - แสดงยอดคงเหลือหลังโอน
 * - ใส่ PIN ยืนยัน
 *
 * หมายเหตุ: ปิดอยู่หลัง FEATURES.P2P_TRANSFER_ENABLED (PLAY-18) — ปรับหน้าตาให้เข้าธีมแบบเบาๆ ไว้ก่อน
 */

import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  ScrollView,
  Pressable,
  StyleSheet,
  Alert,
  ActivityIndicator,
  Modal,
  KeyboardAvoidingView,
  Platform,
  Image,
} from 'react-native';
import { Text, TextInput } from '@/components/ui/Text';
import { LinearGradient } from 'expo-linear-gradient';
import { Redirect, useRouter } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { isFeatureEnabled } from '@/config/appConfig';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { useAuthStore } from '@/stores/authStore';
import { getWallet, lookupWalletAddress, transferMoney } from '@/services/api';
import { formatCurrency } from '@/constants';
import { Button3D, Card3D, GlassIconButton, Icon, IconButton, Screen, type IconName } from '@/components/ui';
import { ActionBar, InfoRow, MoneyInput, MoneyText, NavyCard } from '@/components/wallet/WalletKit';
import { useTheme, DARK_THEME, radii, shadowStyle, spacing, typography } from '@/theme';

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

/**
 * PLAY-18: โอนเงินระหว่างผู้ใช้ (P2P) ปิดใน build สโตร์ → deep link เข้ามาก็กลับไปหน้ากระเป๋าเงิน
 */
export default function WalletTransferScreen() {
  if (!isFeatureEnabled('P2P_TRANSFER_ENABLED')) {
    return <Redirect href={'/(tabs)/wallet' as never} />;
  }
  return <WalletTransferContent />;
}

/** แถวสรุปในแผ่น PIN: ไอคอน + ป้าย + ค่า */
const SummaryLine: React.FC<{ icon: IconName; label: string; strong?: boolean; children: React.ReactNode }> = ({
  icon,
  label,
  strong = false,
  children,
}) => {
  const { colors } = useTheme();
  return (
    <View style={styles.summaryLine}>
      <Icon name={icon} size={16} color={strong ? colors.goldDeep : colors.textFaint} />
      <Text
        style={[
          strong ? typography.bodyStrong : typography.bodySm,
          styles.flex,
          { color: strong ? colors.textStrong : colors.textMuted },
        ]}
      >
        {label}
      </Text>
      {children}
    </View>
  );
};

function WalletTransferContent() {
  const router = useRouter();
  const { colors, gradients } = useTheme();
  const insets = useSafeAreaInsets();
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

  // เปิด Modal ใส่ PIN - แสดง confirmation ก่อน
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

    // แสดง confirmation dialog ก่อนเปิด PIN modal
    Alert.alert(
      'ยืนยันการโอนเงิน',
      `ผู้รับ: ${recipient.name}\n\n` +
      `จำนวนเงินที่โอน: ${formatCurrency(amountNum)}\n` +
      `ค่าธรรมเนียม (${(TRANSFER_FEE_RATE * 100).toFixed(0)}%): ${formatCurrency(fee)}\n\n` +
      `รวมหักจากกระเป๋า: ${formatCurrency(totalDeduction)}\n` +
      `ยอดคงเหลือหลังโอน: ${formatCurrency(remainingBalance)}`,
      [
        { text: 'ยกเลิก', style: 'cancel' },
        { text: 'ดำเนินการต่อ', onPress: () => setShowPinModal(true) },
      ]
    );
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
          'โอนเงินสำเร็จ!',
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

  const feeLabel = `ค่าธรรมเนียม (${(TRANSFER_FEE_RATE * 100).toFixed(0)}%)`;

  return (
    <Screen
      title="โอนเงิน"
      onBack={() => router.back()}
      scroll={false}
      right={<IconButton icon="scan" label="สแกน QR Code" onPress={openScanner} />}
    >
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
          {/* ยอดเงินคงเหลือ */}
          <NavyCard contentStyle={styles.balanceHero}>
            <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>ยอดเงินคงเหลือ</Text>
            {isLoading ? (
              <Text style={[typography.moneyLg, { color: colors.goldLight }]}>...</Text>
            ) : (
              <MoneyText text={formatCurrency(walletBalance)} color={colors.goldLight} size={34} />
            )}
          </NavyCard>

          {/* กรอก Wallet Address */}
          <Text style={[typography.h3, styles.sectionTitle, { color: colors.textStrong }]}>Wallet Address ผู้รับ</Text>

          <View style={[styles.inputRow, { backgroundColor: colors.inset, borderColor: colors.border }]}>
            <Icon name="wallet" size={20} color={colors.goldDeep} />
            <TextInput
              style={[styles.addressInput, { color: colors.textStrong }]}
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
              placeholderTextColor={colors.textFaint}
              selectionColor={colors.gold}
              autoCapitalize="none"
            />
            {isLookingUp && <ActivityIndicator size="small" color={colors.gold} />}
          </View>

          {/* แสดงข้อผิดพลาด */}
          {lookupError && (
            <View style={[styles.noteBox, { backgroundColor: colors.dangerSoft }]}>
              <Icon name="warning-circle" size={18} color={colors.danger} />
              <Text style={[typography.bodySm, styles.flex, { color: colors.danger }]}>{lookupError}</Text>
            </View>
          )}

          {/* แสดงข้อมูลผู้รับ (เช็ค null กัน crash) */}
          {recipient && (
            <Card3D
              style={styles.recipientCard}
              padding={spacing.lg}
              contentStyle={[styles.recipientRow, { borderWidth: 1.5, borderColor: colors.success }]}
            >
              <View style={styles.recipientAvatar}>
                {recipient.avatar ? (
                  <Image source={{ uri: recipient.avatar }} style={styles.avatarImage} />
                ) : (
                  <LinearGradient colors={gradients.navy} style={styles.avatarPlaceholder}>
                    <Text style={[styles.avatarText, { color: colors.goldLight }]}>
                      {(recipient.name || 'U').charAt(0).toUpperCase()}
                    </Text>
                  </LinearGradient>
                )}
              </View>
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>
                  {recipient.name || 'ไม่ระบุชื่อ'}
                </Text>
                <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
                  {recipient.walletAddress}
                </Text>
              </View>
              <Icon name="check-circle" size={26} color={colors.success} weight="fill" />
            </Card3D>
          )}

          {/* กรอกจำนวนเงิน */}
          <Text style={[typography.h3, styles.sectionTitle, { color: colors.textStrong }]}>จำนวนเงินที่ต้องการโอน</Text>

          <MoneyInput
            value={amount}
            onChangeText={(text) => setAmount(text.replace(/[^0-9]/g, ''))}
            placeholder="0"
            keyboardType="numeric"
            maxLength={7}
          />

          <Text style={[typography.caption, styles.inputHint, { color: colors.textFaint }]}>
            โอนขั้นต่ำ {formatCurrency(MIN_TRANSFER)}
          </Text>

          {/* หมายเหตุ */}
          <Text style={[typography.h3, styles.sectionTitle, { color: colors.textStrong }]}>หมายเหตุ (ไม่บังคับ)</Text>

          <TextInput
            style={[styles.noteInput, { backgroundColor: colors.inset, borderColor: colors.border, color: colors.textStrong }]}
            value={note}
            onChangeText={setNote}
            placeholder="ระบุหมายเหตุ..."
            placeholderTextColor={colors.textFaint}
            selectionColor={colors.gold}
            maxLength={100}
          />

          {/* สรุปการโอน */}
          {amountNum > 0 && (
            <Card3D padding={spacing.lg} style={styles.summaryCard}>
              <Text style={[typography.h3, styles.summaryTitle, { color: colors.textStrong }]}>สรุปการโอนเงิน</Text>

              <InfoRow label="จำนวนเงินที่โอน" value={formatCurrency(amountNum)} />
              <InfoRow label={feeLabel}>
                <Text style={[typography.bodyStrong, { color: colors.warning }]}>+{formatCurrency(fee)}</Text>
              </InfoRow>

              <View style={[styles.divider, { backgroundColor: colors.divider }]} />

              <InfoRow label="รวมที่หักจากกระเป๋า" strong>
                <Text style={[typography.h2, styles.tabular, { color: colors.goldDeep }]}>{formatCurrency(totalDeduction)}</Text>
              </InfoRow>
              <InfoRow label="ยอดคงเหลือหลังโอน">
                <Text style={[typography.bodyStrong, { color: remainingBalance < 0 ? colors.danger : colors.textStrong }]}>
                  {formatCurrency(remainingBalance)}
                </Text>
              </InfoRow>

              {remainingBalance < 0 && (
                <View style={[styles.noteBox, styles.warningBox, { backgroundColor: colors.warningSoft }]}>
                  <Icon name="warning" size={18} color={colors.warning} />
                  <Text style={[typography.bodySm, styles.warningText, { color: colors.warning }]}>ยอดเงินไม่เพียงพอ</Text>
                </View>
              )}
            </Card3D>
          )}
        </ScrollView>

        {/* ปุ่มดำเนินการ */}
        <ActionBar>
          <Button3D
            title="โอนเงิน"
            icon="paper-plane-tilt"
            size="lg"
            fullWidth
            disabled={!canTransfer}
            onPress={handleContinue}
          />
        </ActionBar>
      </KeyboardAvoidingView>

      {/* QR Scanner Modal — พื้นกล้องมืดเสมอทั้งสองโหมด */}
      <Modal visible={showScanner} animationType="slide" onRequestClose={() => setShowScanner(false)}>
        <View style={[styles.scannerContainer, { backgroundColor: DARK_THEME.colors.background }]}>
          <View style={[styles.scannerHeader, { paddingTop: insets.top + spacing.md }]}>
            <GlassIconButton icon="x" weight="bold" accessibilityLabel="ปิด" onPress={() => setShowScanner(false)} />
            <Text style={[typography.h2, { color: DARK_THEME.colors.textStrong }]}>สแกน QR Code</Text>
            <View style={styles.scannerSpacer} />
          </View>

          <CameraView
            style={styles.camera}
            facing="back"
            barcodeScannerSettings={{ barcodeTypes: ['qr'] }}
            onBarcodeScanned={handleBarCodeScanned}
          >
            <View style={[styles.scannerOverlay, { backgroundColor: colors.overlay }]}>
              <View style={[styles.scannerFrame, { borderColor: colors.gold }]} />
            </View>
          </CameraView>

          <View style={[styles.scannerFooter, { paddingBottom: insets.bottom + spacing.xl }]}>
            <Text style={[typography.body, { color: DARK_THEME.colors.text }]}>วาง QR Code ในกรอบเพื่อสแกน</Text>
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
        <View style={[styles.modalOverlay, { backgroundColor: colors.overlay }]}>
          <View
            style={[
              styles.modalContent,
              { backgroundColor: colors.card, paddingBottom: Math.max(insets.bottom, spacing.lg) + spacing.sm },
              shadowStyle('lg', colors.shadowDark),
            ]}
          >
            <View style={[styles.handle, { backgroundColor: colors.border }]} />
            <View style={styles.modalHeader}>
              <Text style={[typography.h1, { color: colors.textStrong }]}>ยืนยันด้วย PIN</Text>
              <Pressable
                onPress={() => setShowPinModal(false)}
                accessibilityRole="button"
                accessibilityLabel="ปิด"
                hitSlop={10}
                style={({ pressed }) => [styles.closeBtn, { backgroundColor: colors.inset, opacity: pressed ? 0.7 : 1 }]}
              >
                <Icon name="x" size={18} color={colors.textMuted} weight="bold" />
              </Pressable>
            </View>

            <Text style={[typography.bodySm, styles.modalDesc, { color: colors.textMuted }]}>
              กรอก PIN 6 หลักเพื่อยืนยันการโอนเงิน
            </Text>

            <TextInput
              style={[styles.pinInput, { backgroundColor: colors.inset, borderColor: colors.border, color: colors.textStrong }]}
              value={pin}
              onChangeText={(text) => setPin(text.replace(/[^0-9]/g, '').slice(0, 6))}
              placeholder="••••••"
              placeholderTextColor={colors.textFaint}
              selectionColor={colors.gold}
              keyboardType="numeric"
              secureTextEntry
              maxLength={6}
              textAlign="center"
            />

            {/* สรุปใน Modal */}
            <View style={[styles.modalSummary, { backgroundColor: colors.inset, borderColor: colors.border }]}>
              <SummaryLine icon="user" label="ผู้รับ">
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{recipient?.name}</Text>
              </SummaryLine>
              <SummaryLine icon="money" label="จำนวนเงิน">
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{formatCurrency(amountNum)}</Text>
              </SummaryLine>
              <SummaryLine icon="receipt" label={feeLabel}>
                <Text style={[typography.bodyStrong, { color: colors.warning }]}>+{formatCurrency(fee)}</Text>
              </SummaryLine>
              <View style={[styles.divider, { backgroundColor: colors.divider }]} />
              <SummaryLine icon="coins" label="รวมหักจากกระเป๋า" strong>
                <Text style={[typography.h3, { color: colors.goldDeep }]}>{formatCurrency(totalDeduction)}</Text>
              </SummaryLine>
              <View style={[styles.divider, { backgroundColor: colors.divider }]} />
              <SummaryLine icon="wallet" label="ยอดปัจจุบัน">
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{formatCurrency(walletBalance)}</Text>
              </SummaryLine>
              <SummaryLine icon="check-circle" label="ยอดคงเหลือหลังโอน" strong>
                <Text style={[typography.bodyStrong, { color: colors.success }]}>{formatCurrency(remainingBalance)}</Text>
              </SummaryLine>
            </View>

            <Button3D
              title="ยืนยันการโอน"
              icon="paper-plane-tilt"
              size="lg"
              fullWidth
              disabled={pin.length !== 6}
              loading={isSubmitting}
              onPress={handleSubmit}
            />
          </View>
        </View>
      </Modal>
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
  tabular: {
    fontVariant: ['tabular-nums'],
  },
  balanceHero: {
    alignItems: 'center',
    gap: spacing.xs,
  },
  sectionTitle: {
    marginTop: spacing.xl,
    marginBottom: spacing.sm,
  },
  inputRow: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 14,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    minHeight: 52,
    gap: spacing.md,
  },
  addressInput: {
    flex: 1,
    fontSize: 14,
    paddingVertical: spacing.md,
  },
  noteBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.sm,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
  warningBox: {
    marginTop: spacing.md,
  },
  warningText: {
    fontWeight: '600',
  },
  recipientCard: {
    marginTop: spacing.md,
  },
  recipientRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  recipientAvatar: {
    borderRadius: 24,
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
    fontWeight: '700',
  },
  inputHint: {
    marginTop: spacing.sm,
  },
  noteInput: {
    borderRadius: 14,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    fontSize: 15,
    minHeight: 50,
  },
  summaryCard: {
    marginTop: spacing.xl,
  },
  summaryTitle: {
    marginBottom: spacing.sm,
  },
  divider: {
    height: StyleSheet.hairlineWidth,
    marginVertical: spacing.sm,
  },

  // Scanner
  scannerContainer: {
    flex: 1,
  },
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
  camera: {
    flex: 1,
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
  },

  // Modal
  modalOverlay: {
    flex: 1,
    justifyContent: 'flex-end',
  },
  modalContent: {
    borderTopLeftRadius: radii.xxl,
    borderTopRightRadius: radii.xxl,
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.sm,
  },
  handle: {
    alignSelf: 'center',
    width: 44,
    height: 5,
    borderRadius: 3,
    marginBottom: spacing.lg,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  closeBtn: {
    width: 36,
    height: 36,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  modalDesc: {
    marginTop: spacing.xs,
    marginBottom: spacing.lg,
  },
  pinInput: {
    borderRadius: 16,
    borderWidth: 1,
    paddingVertical: spacing.md,
    fontSize: 28,
    fontWeight: '700',
    letterSpacing: 10,
    marginBottom: spacing.lg,
  },
  modalSummary: {
    borderRadius: radii.lg,
    borderWidth: 1,
    padding: spacing.lg,
    gap: spacing.xs,
    marginBottom: spacing.xl,
  },
  summaryLine: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
});
