/**
 * กระเป๋าเงิน (แท็บ) — ธีมรอยัล น้ำเงินกรมท่า-ทอง ตามม็อกที่เจ้าของอนุมัติ
 *
 * - หัวน้ำเงินลายกนก + บัตร TP Wallet แบบบัตรโลหะ: ยอดคงเหลือ (ทอง) · พร้อมใช้ · รอดำเนินการ
 * - ปุ่มลัด: เติมเงิน · ถอนเงิน (ต้องยืนยันตัวตนก่อน) · โอนเงิน (PLAY-18: ปิดใน build สโตร์) · ประวัติ
 * - รายรับ/รายจ่ายเดือนนี้ · เตือนยืนยันตัวตน (KYC) · ธุรกรรมล่าสุด 10 รายการ
 * - QR รับเงินระหว่างผู้ใช้ (P2P) render เฉพาะเมื่อเปิดฟีเจอร์เท่านั้น (PLAY-18)
 * - นโยบาย Google Play: รายได้จากระบบเครือข่ายแสดงเป็น "ค่าแนะนำ" (walletTransactionTitle)
 */

import React, { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  Share,
  StatusBar,
  StyleSheet,
  View,
} from 'react-native';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useIsFocused } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import QRCode from 'react-native-qrcode-svg';
import { Text } from '@/components/ui/Text';
import { useAuthStore } from '@/stores/authStore';
import { getWallet, getWalletTransactions, getKycStatus } from '@/services/api';
import { isFeatureEnabled } from '@/config/appConfig';
import { walletTransactionTitle } from '@/utils/storePolicy';
import {
  Button3D,
  EmptyState,
  Icon,
  OnHeaderProvider,
  Pill,
  RoyalHeader,
  SectionHeader,
  formatBaht,
  tapHaptic,
  type IconName,
} from '@/components/ui';
import { useTheme, spacing, typography, shadowStyle, type Tone } from '@/theme';
import { KANOK_CREST_CLEARANCE } from '@/components/ui/KanokTabBar';

const KANOK = require('@/assets/images/brand/kanok-gold.webp');

/** เงินบาท 2 ตำแหน่ง มีจุลภาค (API บางค่าส่งมาเป็นข้อความ เช่น "2196.00") */
const money = (value: unknown): string => formatBaht(value ?? 0, { decimals: 2 });

// Wallet data type
interface WalletData {
  balance: number;
  availableBalance: number;
  pendingBalance: number;
  totalIncome: number;
  totalExpense: number;
  thisMonthIncome: number;
  thisMonthExpense: number;
  currency: string;
  walletAddress?: string;
}

// Transaction type
interface Transaction {
  id: number;
  type: 'in' | 'out';
  amount: number;
  title: string;
  status: string;
  date: string;
  dateRelative: string;
  referenceType?: string;
}

/** ไอคอนของธุรกรรมตามประเภทอ้างอิง */
const txIcon = (tx: Transaction): IconName => {
  switch (tx.referenceType) {
    case 'commission':
      return 'gift';
    case 'order':
      return 'shopping-bag-open';
    case 'withdrawal':
      return 'bank';
    case 'topup':
      return 'plus';
    case 'transfer':
      return 'paper-plane-tilt';
    default:
      return tx.type === 'in' ? 'arrow-down-left' : 'arrow-up-right';
  }
};

/** ป้ายสถานะ (สำเร็จไม่ต้องแสดง ให้รายการดูสะอาด) */
const STATUS_PILL: Record<string, { label: string; tone: Tone } | undefined> = {
  pending: { label: 'รอดำเนินการ', tone: 'warning' },
  processing: { label: 'กำลังดำเนินการ', tone: 'info' },
  failed: { label: 'ไม่สำเร็จ', tone: 'danger' },
  cancelled: { label: 'ยกเลิก', tone: 'neutral' },
};

// =====================================================
// ชิ้นส่วนย่อย
// =====================================================

/** ปุ่มลัดทองบนหัวน้ำเงิน */
const QuickAction = ({
  icon,
  label,
  primary,
  onPress,
}: {
  icon: IconName;
  label: string;
  primary?: boolean;
  onPress: () => void;
}) => {
  const { colors, gradients } = useTheme();
  return (
    <Pressable
      onPress={() => {
        tapHaptic();
        onPress();
      }}
      accessibilityRole="button"
      accessibilityLabel={label}
      style={({ pressed }) => [styles.quick, { opacity: pressed ? 0.75 : 1 }]}
    >
      {primary ? (
        <LinearGradient colors={gradients.primary} style={[styles.quickIcon, shadowStyle('md', '#8A6420')]}>
          <Icon name={icon} size={24} color={colors.textOnGold} weight="bold" />
        </LinearGradient>
      ) : (
        <View style={[styles.quickIcon, styles.quickIconGlass]}>
          <Icon name={icon} size={23} color={colors.goldLight} />
        </View>
      )}
      <Text style={[styles.quickLabel, { color: colors.onHeader }]}>{label}</Text>
    </Pressable>
  );
};

/** แถวธุรกรรม */
const TransactionRow = ({ transaction, last }: { transaction: Transaction; last: boolean }) => {
  const { colors } = useTheme();
  const isIncome = transaction.type === 'in';
  const pill = STATUS_PILL[transaction.status];
  return (
    <View style={[styles.txRow, !last && { borderBottomWidth: 1, borderBottomColor: colors.divider }]}>
      <View style={[styles.txIcon, { backgroundColor: isIncome ? colors.successSoft : colors.navySoft }]}>
        <Icon name={txIcon(transaction)} size={20} color={isIncome ? colors.success : colors.navy} weight={isIncome ? 'bold' : 'regular'} />
      </View>
      <View style={styles.flex}>
        <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
          {transaction.title}
        </Text>
        <Text style={[typography.caption, { color: colors.textFaint }]}>{transaction.dateRelative || transaction.date}</Text>
      </View>
      <View style={styles.txRight}>
        <Text style={[styles.txAmount, { color: isIncome ? colors.success : colors.textStrong }]}>
          {isIncome ? '+' : '−'}
          {money(transaction.amount)}
        </Text>
        {!!pill && <Pill label={pill.label} tone={pill.tone} style={styles.txPill} />}
      </View>
    </View>
  );
};

// =====================================================
// หน้าจอ
// =====================================================

export default function WalletScreen() {
  const { colors, isDark } = useTheme();
  const insets = useSafeAreaInsets();
  // แท็บค้าง mount อยู่หลังสลับแท็บ → StatusBar ของหน้านี้ต้องมีเฉพาะตอนเห็นอยู่ ไม่งั้นทับสีแถบบนของหน้าแรก
  const isFocused = useIsFocused();
  const { isAuthenticated, user } = useAuthStore();
  // PLAY-18: โอนเงินระหว่างผู้ใช้ (P2P) ปิดไว้จนกว่าจะยื่น Financial features declaration
  const p2pEnabled = isFeatureEnabled('P2P_TRANSFER_ENABLED');

  const [wallet, setWallet] = useState<WalletData | null>(null);
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [refreshing, setRefreshing] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [kycStatus, setKycStatus] = useState<string | null>(null);
  const [showQrModal, setShowQrModal] = useState(false);
  const [balanceHidden, setBalanceHidden] = useState(false);

  // โหลดข้อมูล
  const loadData = useCallback(async () => {
    if (!isAuthenticated) {
      setIsLoading(false);
      return;
    }

    try {
      const walletResponse = await getWallet();
      if (walletResponse?.success && walletResponse.data) {
        setWallet(walletResponse.data);
      }

      const txResponse = await getWalletTransactions(1, 'all', 10);
      if (txResponse?.success && txResponse.data) {
        const txItems = txResponse.data.items.map((tx: any) => ({
          id: tx.id,
          type: tx.type,
          amount: Number(tx.amount) || 0,
          // นโยบาย Google Play: รายได้จากระบบเครือข่ายแสดงเป็น "ค่าแนะนำ" (ไม่มีคำว่าคอมมิชชั่น/ชั้น)
          title: walletTransactionTitle(tx.title, tx.referenceType, tx.type === 'in'),
          status: tx.status || 'completed',
          date: tx.date,
          dateRelative: tx.dateRelative,
          referenceType: tx.referenceType,
        }));
        setTransactions(txItems);
      }

      const kycResponse = await getKycStatus();
      if (kycResponse?.success && kycResponse.data) {
        setKycStatus(kycResponse.data.status);
      }
    } catch (error) {
      console.error('Load wallet data error:', error);
    } finally {
      setIsLoading(false);
    }
  }, [isAuthenticated]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadData();
    setRefreshing(false);
  };

  // ---------- การกระทำ ----------
  const handleTopUp = () => {
    router.push('/wallet-topup');
  };

  const handleWithdraw = () => {
    if (kycStatus !== 'approved') {
      Alert.alert('ต้องยืนยันตัวตน', 'กรุณายืนยันตัวตน (KYC) ก่อนทำการถอนเงิน', [
        { text: 'ยกเลิก', style: 'cancel' },
        { text: 'ยืนยันตัวตน', onPress: () => router.push('/kyc') },
      ]);
      return;
    }
    router.push('/wallet-withdraw');
  };

  const handleTransfer = () => {
    router.push('/wallet-transfer');
  };

  const handleHistory = () => {
    router.push('/wallet-history');
  };

  const handleShowQr = () => {
    // PLAY-18: รับ/โอนเงินระหว่างผู้ใช้ปิดใน build สโตร์
    if (!p2pEnabled) return;
    setShowQrModal(true);
  };

  // ดึงเลขที่กระเป๋าเงินจริง - ไม่ใช้ fallback เพื่อความถูกต้อง
  const getWalletAddress = (): string => {
    if (wallet?.walletAddress) return wallet.walletAddress;
    if (user?.wallet_address) return user.wallet_address;
    return '';
  };

  const handleShareWalletAddress = async () => {
    const address = getWalletAddress();
    if (!address) {
      Alert.alert('ไม่พบ Wallet Address', 'กรุณาลองใหม่อีกครั้ง');
      return;
    }
    try {
      await Share.share({
        message: `Wallet Address ของฉัน: ${address}\n\nสแกน QR Code หรือกรอก Address นี้เพื่อโอนเงินให้ฉัน`,
        title: 'Wallet Address',
      });
    } catch (error) {
      console.error('Share error:', error);
    }
  };

  // ---------- ยังไม่ login ----------
  if (!isAuthenticated) {
    return (
      <View style={[styles.flex, { backgroundColor: colors.background, paddingTop: insets.top }]}>
        {isFocused && (
          <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={colors.background} />
        )}
        <EmptyState
          art="wallet"
          title="กระเป๋าเงินของคุณ"
          message="เข้าสู่ระบบเพื่อดูยอดเงินและทำธุรกรรม"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      </View>
    );
  }

  // ---------- โหลดครั้งแรก ----------
  if (isLoading && !wallet) {
    return (
      <View style={[styles.flex, styles.center, { backgroundColor: colors.background }]}>
        {isFocused && (
          <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={colors.background} />
        )}
        <ActivityIndicator size="large" color={colors.gold} />
        <Text style={[typography.bodySm, { color: colors.textMuted, marginTop: spacing.md }]}>กำลังโหลด...</Text>
      </View>
    );
  }

  const ownerName = (user?.name || '').trim();
  const walletNo = getWalletAddress();

  return (
    <View style={[styles.flex, { backgroundColor: colors.background }]}>
      {isFocused && <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />}

      <ScrollView
        showsVerticalScrollIndicator={false}
        // ยอดซุ้มกนกของแถบล่างยื่นขึ้นมาทับท้ายรายการ — เผื่อที่ให้เลื่อนพ้นซุ้ม
        contentContainerStyle={{ paddingBottom: spacing.xxxl + KANOK_CREST_CLEARANCE }}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={colors.gold}
            colors={[colors.gold]}
            progressBackgroundColor={colors.card}
            progressViewOffset={insets.top}
          />
        }
      >
        {/* ---------- หัวน้ำเงิน + บัตร ---------- */}
        <RoyalHeader ornament={false} style={{ paddingTop: insets.top + spacing.sm, paddingBottom: 60 }}>
          <View style={styles.headRow}>
            <Text accessibilityRole="header" style={[typography.serifLg, styles.flex, { color: colors.onHeader }]}>
              กระเป๋าเงิน
            </Text>
            {p2pEnabled && (
              <Pressable
                onPress={handleShowQr}
                accessibilityRole="button"
                accessibilityLabel="QR รับเงิน"
                style={({ pressed }) => [
                  styles.headBtn,
                  { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder, opacity: pressed ? 0.7 : 1 },
                ]}
              >
                <Icon name="qr-code" size={20} color={colors.onHeader} />
              </Pressable>
            )}
          </View>

          {/* บัตรโลหะน้ำเงินลายกนก */}
          <View style={[styles.card, shadowStyle('lg', '#000000')]}>
            <LinearGradient
              colors={['#1A2F57', '#0E1C38', '#070F20']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={StyleSheet.absoluteFill}
            />
            <Image source={KANOK} style={styles.cardKanok} contentFit="contain" accessible={false} />
            <LinearGradient
              colors={['rgba(255,255,255,0)', 'rgba(255,255,255,0.08)', 'rgba(255,255,255,0)']}
              start={{ x: 0, y: 0.2 }}
              end={{ x: 1, y: 0.8 }}
              style={StyleSheet.absoluteFill}
              pointerEvents="none"
            />
            <View style={styles.cardTop}>
              <View style={styles.cardBrand}>
                <Icon name="crown-simple" size={16} color="#F3DC9B" weight="fill" />
                <Text style={[typography.serifSm, { color: '#F3DC9B' }]}>TP Wallet</Text>
              </View>
              <LinearGradient colors={['#F6E3A6', '#C9973A', '#F2D98E']} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.chip} />
            </View>

            <Pressable
              onPress={() => {
                tapHaptic();
                setBalanceHidden((v) => !v);
              }}
              hitSlop={8}
              accessibilityRole="button"
              accessibilityLabel={balanceHidden ? 'แสดงยอดเงิน' : 'ซ่อนยอดเงิน'}
              style={styles.cardLabelRow}
            >
              <Icon name={balanceHidden ? 'eye-slash' : 'eye'} size={15} color="rgba(214,222,238,0.75)" />
              <Text style={[typography.caption, { color: 'rgba(214,222,238,0.75)' }]}>ยอดเงินคงเหลือ</Text>
            </Pressable>
            <Text
              style={[typography.moneyLg, styles.cardAmount]}
              accessibilityLabel={balanceHidden ? 'ยอดเงินถูกซ่อนอยู่' : `ยอดเงินคงเหลือ ${money(wallet?.balance || 0)}`}
            >
              {balanceHidden ? '฿ • • • • •' : money(wallet?.balance || 0)}
            </Text>

            <View style={styles.cardBottom}>
              <View>
                <Text style={styles.cardSubLabel}>พร้อมใช้</Text>
                <Text style={styles.cardSubValue}>{balanceHidden ? '•••' : money(wallet?.availableBalance || 0)}</Text>
              </View>
              <View>
                <Text style={styles.cardSubLabel}>รอดำเนินการ</Text>
                <Text style={styles.cardSubValue}>{balanceHidden ? '•••' : money(wallet?.pendingBalance || 0)}</Text>
              </View>
              <View style={styles.flex} />
              <Text numberOfLines={1} style={styles.cardOwner}>
                {ownerName ? ownerName.toUpperCase() : ''}
              </Text>
            </View>
          </View>

          {/* ปุ่มลัด */}
          <OnHeaderProvider value>
            <View style={styles.quickRow}>
              <QuickAction icon="plus" label="เติมเงิน" primary onPress={handleTopUp} />
              <QuickAction icon="bank" label="ถอนเงิน" onPress={handleWithdraw} />
              {p2pEnabled && <QuickAction icon="paper-plane-tilt" label="โอนเงิน" onPress={handleTransfer} />}
              <QuickAction icon="clock-counter-clockwise" label="ประวัติ" onPress={handleHistory} />
            </View>
          </OnHeaderProvider>
        </RoyalHeader>

        {/* ---------- แผ่นเนื้อหา ---------- */}
        <View style={[styles.sheet, { backgroundColor: colors.background }]}>
          <Text style={[typography.caption, styles.purpose, { color: colors.textMuted }]}>
            ยอดในกระเป๋าใช้ชำระค่าสินค้าและค่าจัดส่งในแอป และรับค่าส่งของ/ยอดขายจากร้าน
          </Text>

          {/* รายรับ/รายจ่ายเดือนนี้ */}
          <View style={styles.statsRow}>
            <View style={[styles.stat, { backgroundColor: colors.card, borderColor: colors.border }, shadowStyle('sm', colors.shadowDark)]}>
              <View style={[styles.statIcon, { backgroundColor: colors.successSoft }]}>
                <Icon name="trend-up" size={18} color={colors.success} weight="bold" />
              </View>
              <Text style={[typography.caption, { color: colors.textMuted }]}>รายรับเดือนนี้</Text>
              <Text style={[typography.h2, { color: colors.textStrong }]}>{money(wallet?.thisMonthIncome || 0)}</Text>
            </View>
            <View style={[styles.stat, { backgroundColor: colors.card, borderColor: colors.border }, shadowStyle('sm', colors.shadowDark)]}>
              <View style={[styles.statIcon, { backgroundColor: colors.dangerSoft }]}>
                <Icon name="arrow-up-right" size={18} color={colors.danger} weight="bold" />
              </View>
              <Text style={[typography.caption, { color: colors.textMuted }]}>รายจ่ายเดือนนี้</Text>
              <Text style={[typography.h2, { color: colors.textStrong }]}>{money(wallet?.thisMonthExpense || 0)}</Text>
            </View>
          </View>

          {/* เตือนยืนยันตัวตน */}
          {kycStatus !== 'approved' && (
            <Pressable
              onPress={() => router.push('/kyc')}
              accessibilityRole="button"
              accessibilityLabel="ยืนยันตัวตนเพื่อปลดล็อคการถอนเงิน"
              style={({ pressed }) => [
                styles.kyc,
                { backgroundColor: colors.warningSoft, borderColor: colors.warning, opacity: pressed ? 0.8 : 1 },
              ]}
            >
              <View style={[styles.kycIcon, { backgroundColor: colors.card }]}>
                <Icon name="identification-card" size={22} color={colors.warning} />
              </View>
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>ยืนยันตัวตน</Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>
                  {kycStatus === 'pending'
                    ? 'รอการตรวจสอบเอกสาร'
                    : kycStatus === 'rejected'
                      ? 'เอกสารถูกปฏิเสธ กรุณาส่งใหม่'
                      : 'กรุณายืนยันตัวตนเพื่อปลดล็อคการถอนเงิน'}
                </Text>
              </View>
              <Icon name="caret-right" size={18} color={colors.warning} weight="bold" />
            </Pressable>
          )}

          {/* ธุรกรรมล่าสุด */}
          <SectionHeader title="ธุรกรรมล่าสุด" actionLabel="ดูทั้งหมด" onAction={handleHistory} style={styles.section} />
          {transactions.length > 0 ? (
            <View style={[styles.txCard, { backgroundColor: colors.card, borderColor: colors.border }, shadowStyle('md', colors.shadowDark)]}>
              {transactions.map((tx, i) => (
                <TransactionRow key={tx.id} transaction={tx} last={i === transactions.length - 1} />
              ))}
            </View>
          ) : (
            <EmptyState compact art="wallet" title="ยังไม่มีธุรกรรม" message="เติมเงินครั้งแรก แล้วรายการจะแสดงที่นี่" />
          )}
        </View>
      </ScrollView>

      {/* ---------- QR รับเงิน (PLAY-18: ไม่ render เลยเมื่อปิดโอนเงินระหว่างผู้ใช้) ---------- */}
      {p2pEnabled && (
        <Modal visible={showQrModal} transparent animationType="fade" onRequestClose={() => setShowQrModal(false)}>
          <View style={[styles.modalOverlay, { backgroundColor: colors.overlay }]}>
            <View style={[styles.modal, { backgroundColor: colors.card }]}>
              <View style={styles.modalHead}>
                <Text style={[typography.serif, styles.flex, { color: colors.textStrong }]}>QR Code รับเงิน</Text>
                <Pressable
                  onPress={() => setShowQrModal(false)}
                  accessibilityRole="button"
                  accessibilityLabel="ปิด"
                  hitSlop={10}
                  style={[styles.modalClose, { backgroundColor: colors.inset }]}
                >
                  <Icon name="x" size={18} color={colors.textStrong} weight="bold" />
                </Pressable>
              </View>

              <View style={styles.qrBox}>
                {walletNo ? (
                  <QRCode value={walletNo} size={200} backgroundColor="white" color="#0C1A33" />
                ) : (
                  <View style={styles.center}>
                    <Icon name="qr-code" size={56} color={colors.textFaint} />
                    <Text style={[typography.caption, { color: colors.textMuted }]}>ไม่พบ Wallet Address</Text>
                  </View>
                )}
              </View>

              <View style={styles.qrUser}>
                <Icon name="user-circle" size={22} color={colors.navy} />
                <Text style={[typography.h3, { color: colors.textStrong }]}>{user?.name || 'ผู้ใช้'}</Text>
              </View>

              <View style={[styles.addressBox, { backgroundColor: colors.inset }]}>
                <Text style={[typography.micro, { color: colors.textFaint }]}>Wallet Address</Text>
                <Text selectable style={[typography.bodySm, { color: colors.textStrong }]}>
                  {walletNo || '-'}
                </Text>
              </View>

              <Button3D title="แชร์" icon="share-network" variant="navy" fullWidth onPress={handleShareWalletAddress} />
              <Text style={[typography.caption, styles.qrInfo, { color: colors.textMuted }]}>
                ให้ผู้โอนสแกน QR Code นี้ หรือกรอก Wallet Address เพื่อโอนเงินให้คุณ
              </Text>
            </View>
          </View>
        </Modal>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  center: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  headRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.sm,
  },
  headBtn: {
    width: 40,
    height: 40,
    borderRadius: 14,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  card: {
    marginHorizontal: spacing.screen,
    marginTop: spacing.lg,
    height: 210,
    borderRadius: 26,
    overflow: 'hidden',
    padding: spacing.xl,
    borderWidth: 1,
    borderColor: 'rgba(228,192,107,0.35)',
  },
  cardKanok: {
    position: 'absolute',
    right: -60,
    top: -20,
    width: 290,
    height: 255,
    opacity: 0.5,
  },
  cardTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  cardBrand: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 7,
  },
  chip: {
    width: 40,
    height: 30,
    borderRadius: 7,
  },
  cardLabelRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: spacing.xl,
  },
  cardAmount: {
    color: '#F3DC9B',
    marginTop: 2,
  },
  cardBottom: {
    position: 'absolute',
    left: spacing.xl,
    right: spacing.xl,
    bottom: spacing.lg,
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: spacing.xl,
  },
  cardSubLabel: {
    fontSize: 11,
    color: 'rgba(214,222,238,0.6)',
  },
  cardSubValue: {
    fontSize: 14,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  cardOwner: {
    maxWidth: 140,
    fontSize: 11.5,
    letterSpacing: 1.2,
    color: 'rgba(214,222,238,0.6)',
  },
  quickRow: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    paddingHorizontal: spacing.md,
    marginTop: spacing.xl,
  },
  quick: {
    alignItems: 'center',
    gap: spacing.sm,
    minWidth: 72,
  },
  quickIcon: {
    width: 58,
    height: 58,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  quickIconGlass: {
    backgroundColor: 'rgba(228,192,107,0.14)',
    borderWidth: 1,
    borderColor: 'rgba(228,192,107,0.3)',
  },
  quickLabel: {
    fontSize: 12.5,
    fontWeight: '500',
  },
  sheet: {
    marginTop: -30,
    borderTopLeftRadius: 30,
    borderTopRightRadius: 30,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.xl,
  },
  purpose: {
    textAlign: 'center',
    marginBottom: spacing.md,
  },
  statsRow: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  stat: {
    flex: 1,
    borderRadius: 20,
    borderWidth: 1,
    padding: spacing.md + 2,
    gap: 4,
  },
  statIcon: {
    width: 36,
    height: 36,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 4,
  },
  kyc: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginTop: spacing.lg,
    padding: spacing.md + 2,
    borderRadius: 20,
    borderWidth: 1,
  },
  kycIcon: {
    width: 44,
    height: 44,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
  },
  section: {
    marginTop: spacing.xl,
  },
  txCard: {
    borderRadius: 24,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
  },
  txRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.md + 2,
  },
  txIcon: {
    width: 44,
    height: 44,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
  },
  txRight: {
    alignItems: 'flex-end',
    gap: 4,
  },
  txAmount: {
    fontSize: 15,
    fontWeight: '700',
    fontVariant: ['tabular-nums'],
  },
  txPill: {
    alignSelf: 'flex-end',
  },
  modalOverlay: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: spacing.xl,
  },
  modal: {
    width: '100%',
    maxWidth: 380,
    borderRadius: 28,
    padding: spacing.xl,
    gap: spacing.md,
  },
  modalHead: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  modalClose: {
    width: 36,
    height: 36,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  qrBox: {
    alignSelf: 'center',
    padding: spacing.lg,
    borderRadius: 22,
    backgroundColor: '#FFFFFF',
  },
  qrUser: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
  },
  addressBox: {
    borderRadius: 14,
    padding: spacing.md,
    gap: 2,
  },
  qrInfo: {
    textAlign: 'center',
  },
});
