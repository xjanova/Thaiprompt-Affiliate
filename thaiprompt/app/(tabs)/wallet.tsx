/**
 * Wallet Screen - Premium Full Featured Version
 * ใช้ StyleSheet แทน NativeWind
 *
 * Features:
 * - แสดงยอดเงินในกระเป๋า
 * - ประวัติธุรกรรม
 * - ปุ่มเติมเงิน/ถอนเงิน/โอนเงิน
 * - รองรับโหมดมืด/สว่าง
 * - KYC Warning
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  RefreshControl,
  Alert,
  StyleSheet,
  StatusBar,
  ActivityIndicator,
  Linking,
  Modal,
  TouchableOpacity,
  Share,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { getWallet, getWalletTransactions, getKycStatus } from '@/services/api';
import { formatCurrency } from '@/constants';
import QRCode from 'react-native-qrcode-svg';

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

// Action Button Component
const ActionButton = ({
  icon,
  label,
  color,
  onPress,
  disabled = false,
}: {
  icon: string;
  label: string;
  color: string;
  onPress: () => void;
  disabled?: boolean;
}) => (
  <Pressable
    style={[styles.actionButton, disabled && styles.actionButtonDisabled]}
    onPress={onPress}
    disabled={disabled}
  >
    <View style={[styles.actionIconBox, { backgroundColor: color }]}>
      <Ionicons name={icon as any} size={22} color="#FFF" />
    </View>
    <Text style={styles.actionLabel}>{label}</Text>
  </Pressable>
);

// Transaction Item Component
const TransactionItem = ({
  transaction,
  isDark,
}: {
  transaction: Transaction;
  isDark: boolean;
}) => {
  const isIncome = transaction.type === 'in';

  // Choose icon based on referenceType
  let iconName = isIncome ? 'arrow-down-outline' : 'arrow-up-outline';
  if (transaction.referenceType === 'commission') iconName = 'gift-outline';
  if (transaction.referenceType === 'order') iconName = 'cart-outline';
  if (transaction.referenceType === 'withdrawal') iconName = 'wallet-outline';
  if (transaction.referenceType === 'topup') iconName = 'add-circle-outline';
  if (transaction.referenceType === 'transfer') iconName = 'swap-horizontal-outline';

  // Status color & text
  const statusConfig: Record<string, { color: string; text: string }> = {
    completed: { color: '#10B981', text: 'สำเร็จ' },
    pending: { color: '#F59E0B', text: 'รอดำเนินการ' },
    failed: { color: '#EF4444', text: 'ล้มเหลว' },
    cancelled: { color: '#6B7280', text: 'ยกเลิก' },
  };

  const config = statusConfig[transaction.status] || statusConfig.pending;

  return (
    <View style={[styles.txItem, !isDark && styles.txItemLight]}>
      <View style={[styles.txIcon, { backgroundColor: isIncome ? '#D1FAE5' : '#FEE2E2' }]}>
        <Ionicons
          name={iconName as any}
          size={18}
          color={isIncome ? '#10B981' : '#EF4444'}
        />
      </View>
      <View style={styles.txInfo}>
        <Text style={[styles.txTitle, !isDark && styles.txTitleLight]}>{transaction.title}</Text>
        <Text style={styles.txDate}>{transaction.dateRelative || transaction.date}</Text>
      </View>
      <View style={styles.txAmountBox}>
        <Text style={[styles.txAmount, { color: isIncome ? '#10B981' : '#EF4444' }]}>
          {isIncome ? '+' : '-'}{formatCurrency(transaction.amount)}
        </Text>
        <View style={[styles.txStatus, { backgroundColor: `${config.color}20` }]}>
          <Text style={[styles.txStatusText, { color: config.color }]}>
            {config.text}
          </Text>
        </View>
      </View>
    </View>
  );
};

// Stat Card Component
const StatCard = ({
  label,
  value,
  icon,
  color,
  isDark,
}: {
  label: string;
  value: string;
  icon: string;
  color: string;
  isDark: boolean;
}) => (
  <View style={[styles.statCard, !isDark && styles.statCardLight]}>
    <View style={[styles.statIcon, { backgroundColor: `${color}20` }]}>
      <Ionicons name={icon as any} size={18} color={color} />
    </View>
    <Text style={[styles.statValue, !isDark && styles.statValueLight]}>{value}</Text>
    <Text style={styles.statLabel}>{label}</Text>
  </View>
);

export default function WalletScreen() {
  const { isAuthenticated, user } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const [wallet, setWallet] = useState<WalletData | null>(null);
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [refreshing, setRefreshing] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [kycStatus, setKycStatus] = useState<string | null>(null);
  const [showQrModal, setShowQrModal] = useState(false);

  // โหลดข้อมูล
  const loadData = useCallback(async () => {
    if (!isAuthenticated) {
      setIsLoading(false);
      return;
    }

    try {
      // Load wallet data
      const walletResponse = await getWallet();
      if (walletResponse?.success && walletResponse.data) {
        setWallet(walletResponse.data);
      }

      // Load transactions
      const txResponse = await getWalletTransactions(1, 'all', 10);
      if (txResponse?.success && txResponse.data) {
        const txItems = txResponse.data.items.map((tx: any) => ({
          id: tx.id,
          type: tx.type,
          amount: tx.amount,
          title: tx.title,
          status: tx.status || 'completed',
          date: tx.date,
          dateRelative: tx.dateRelative,
          referenceType: tx.referenceType,
        }));
        setTransactions(txItems);
      }

      // Load KYC status
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

  // Handle actions
  const handleTopUp = () => {
    router.push('/wallet-topup');
  };

  const handleWithdraw = () => {
    if (kycStatus !== 'approved') {
      Alert.alert(
        'ต้องยืนยันตัวตน',
        'กรุณายืนยันตัวตน (KYC) ก่อนทำการถอนเงิน',
        [
          { text: 'ยกเลิก', style: 'cancel' },
          { text: 'ยืนยันตัวตน', onPress: () => router.push('/kyc') },
        ]
      );
      return;
    }
    router.push('/wallet-withdraw');
  };

  const handleTransfer = () => {
    router.push('/wallet-transfer');
  };

  const handleHistory = () => {
    // เปิดเว็บสำหรับดูประวัติทั้งหมด (หน้าละเอียดมากกว่า)
    Linking.openURL('https://main.thaiprompt.online/user/wallet/history');
  };

  const handleShowQr = () => {
    setShowQrModal(true);
  };

  const handleShareWalletAddress = async () => {
    const address = wallet?.walletAddress || user?.wallet_address;
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

  // ถ้ายังไม่ login
  if (!isAuthenticated) {
    return (
      <View style={[styles.container, !isDark && styles.containerLight]}>
        <StatusBar
          barStyle={isDark ? 'light-content' : 'dark-content'}
          backgroundColor={isDark ? '#0F0F23' : '#FFFFFF'}
        />
        <View style={styles.notLoggedIn}>
          <Ionicons name="wallet-outline" size={70} color="#4B5563" />
          <Text style={[styles.notLoggedInTitle, !isDark && styles.textDark]}>
            กระเป๋าเงินของคุณ
          </Text>
          <Text style={styles.notLoggedInText}>
            เข้าสู่ระบบเพื่อดูยอดเงินและทำธุรกรรม
          </Text>
          <Pressable style={styles.loginButton} onPress={() => router.push('/login')}>
            <Text style={styles.loginButtonText}>เข้าสู่ระบบ</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  // Loading
  if (isLoading && !wallet) {
    return (
      <View style={[styles.container, !isDark && styles.containerLight]}>
        <StatusBar
          barStyle={isDark ? 'light-content' : 'dark-content'}
          backgroundColor={isDark ? '#0F0F23' : '#FFFFFF'}
        />
        <View style={styles.loadingBox}>
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text style={[styles.loadingText, !isDark && styles.textDark]}>
            กำลังโหลด...
          </Text>
        </View>
      </View>
    );
  }

  return (
    <View style={[styles.container, !isDark && styles.containerLight]}>
      <StatusBar
        barStyle="light-content"
        backgroundColor="#10B981"
      />

      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor="#3B82F6"
            colors={['#3B82F6']}
          />
        }
      >
        {/* Balance Card */}
        <View style={styles.balanceCardWrapper}>
          <LinearGradient
            colors={['#10B981', '#059669']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={styles.balanceCard}
          >
            <View style={styles.balanceHeaderRow}>
              <View style={styles.balanceHeader}>
                <Ionicons name="wallet-outline" size={22} color="rgba(255,255,255,0.9)" />
                <Text style={styles.balanceLabel}>ยอดเงินคงเหลือ</Text>
              </View>
              {/* QR Code Button */}
              <Pressable style={styles.qrButton} onPress={handleShowQr}>
                <Ionicons name="qr-code" size={24} color="#FFF" />
              </Pressable>
            </View>
            <Text style={styles.balanceAmount}>
              {formatCurrency(wallet?.balance || 0)}
            </Text>

            <View style={styles.balanceRow}>
              <View style={styles.balanceCol}>
                <Text style={styles.balanceSubLabel}>พร้อมใช้</Text>
                <Text style={styles.balanceSubValue}>
                  {formatCurrency(wallet?.availableBalance || 0)}
                </Text>
              </View>
              <View style={styles.balanceCol}>
                <Text style={styles.balanceSubLabel}>รอดำเนินการ</Text>
                <Text style={styles.balanceSubValue}>
                  {formatCurrency(wallet?.pendingBalance || 0)}
                </Text>
              </View>
            </View>
          </LinearGradient>
        </View>

        {/* Action Buttons */}
        <View style={styles.actionsRow}>
          <ActionButton
            icon="add-circle-outline"
            label="เติมเงิน"
            color="#3B82F6"
            onPress={handleTopUp}
          />
          <ActionButton
            icon="arrow-up-circle-outline"
            label="ถอนเงิน"
            color="#10B981"
            onPress={handleWithdraw}
          />
          <ActionButton
            icon="swap-horizontal-outline"
            label="โอนเงิน"
            color="#8B5CF6"
            onPress={handleTransfer}
          />
          <ActionButton
            icon="time-outline"
            label="ประวัติ"
            color="#F59E0B"
            onPress={handleHistory}
          />
        </View>

        {/* Stats Cards */}
        <View style={styles.statsRow}>
          <StatCard
            label="รายรับเดือนนี้"
            value={formatCurrency(wallet?.thisMonthIncome || 0)}
            icon="trending-up"
            color="#10B981"
            isDark={isDark}
          />
          <StatCard
            label="รายจ่ายเดือนนี้"
            value={formatCurrency(wallet?.thisMonthExpense || 0)}
            icon="trending-down"
            color="#EF4444"
            isDark={isDark}
          />
        </View>

        {/* KYC Warning */}
        {kycStatus !== 'approved' && (
          <Pressable
            style={styles.kycWarning}
            onPress={() => router.push('/kyc')}
          >
            <Ionicons name="warning" size={24} color="#F59E0B" />
            <View style={styles.kycWarningContent}>
              <Text style={styles.kycWarningTitle}>ยืนยันตัวตน</Text>
              <Text style={styles.kycWarningText}>
                {kycStatus === 'pending'
                  ? 'รอการตรวจสอบเอกสาร'
                  : kycStatus === 'rejected'
                    ? 'เอกสารถูกปฏิเสธ กรุณาส่งใหม่'
                    : 'กรุณายืนยันตัวตนเพื่อปลดล็อคการถอนเงิน'}
              </Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#F59E0B" />
          </Pressable>
        )}

        {/* Transactions */}
        <View style={styles.txSection}>
          <View style={styles.txHeader}>
            <Text style={[styles.txHeaderTitle, !isDark && styles.txHeaderTitleLight]}>
              ธุรกรรมล่าสุด
            </Text>
            <Pressable onPress={handleHistory}>
              <Text style={styles.txHeaderLink}>ดูทั้งหมด</Text>
            </Pressable>
          </View>

          {transactions.length > 0 ? (
            transactions.map((tx) => (
              <TransactionItem
                key={tx.id}
                transaction={tx}
                isDark={isDark}
              />
            ))
          ) : (
            <View style={[styles.emptyTx, !isDark && styles.emptyTxLight]}>
              <Ionicons name="receipt-outline" size={40} color="#4B5563" />
              <Text style={styles.emptyTxText}>ยังไม่มีธุรกรรม</Text>
            </View>
          )}
        </View>
      </ScrollView>

      {/* QR Code Modal */}
      <Modal
        visible={showQrModal}
        transparent
        animationType="fade"
        onRequestClose={() => setShowQrModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={[styles.qrModalContent, !isDark && styles.qrModalContentLight]}>
            {/* Header */}
            <View style={styles.qrModalHeader}>
              <Text style={[styles.qrModalTitle, !isDark && styles.textDark]}>
                QR Code รับเงิน
              </Text>
              <Pressable
                style={styles.qrModalClose}
                onPress={() => setShowQrModal(false)}
              >
                <Ionicons name="close" size={24} color={isDark ? '#FFF' : '#1F2937'} />
              </Pressable>
            </View>

            {/* QR Code */}
            <View style={styles.qrCodeBox}>
              {(wallet?.walletAddress || user?.wallet_address) ? (
                <QRCode
                  value={wallet?.walletAddress || user?.wallet_address || ''}
                  size={200}
                  backgroundColor="white"
                  color="#1F2937"
                />
              ) : (
                <View style={styles.qrPlaceholder}>
                  <Ionicons name="qr-code-outline" size={60} color="#9CA3AF" />
                  <Text style={styles.qrPlaceholderText}>ไม่พบ Wallet Address</Text>
                </View>
              )}
            </View>

            {/* User Info */}
            <View style={styles.qrUserInfo}>
              <Ionicons name="person-circle-outline" size={24} color="#3B82F6" />
              <Text style={[styles.qrUserName, !isDark && styles.textDark]}>
                {user?.name || 'ผู้ใช้'}
              </Text>
            </View>

            {/* Wallet Address */}
            <View style={[styles.walletAddressBox, !isDark && styles.walletAddressBoxLight]}>
              <Text style={styles.walletAddressLabel}>Wallet Address</Text>
              <Text style={[styles.walletAddressValue, !isDark && styles.textDark]}>
                {wallet?.walletAddress || user?.wallet_address || '-'}
              </Text>
            </View>

            {/* Actions */}
            <View style={styles.qrModalActions}>
              <Pressable
                style={[styles.qrActionBtn, styles.qrShareBtn]}
                onPress={handleShareWalletAddress}
              >
                <Ionicons name="share-outline" size={20} color="#FFF" />
                <Text style={styles.qrActionBtnText}>แชร์</Text>
              </Pressable>
            </View>

            {/* Info */}
            <Text style={styles.qrInfoText}>
              ให้ผู้โอนสแกน QR Code นี้ หรือกรอก Wallet Address เพื่อโอนเงินให้คุณ
            </Text>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
  },
  containerLight: {
    backgroundColor: '#F9FAFB',
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    paddingBottom: 100,
  },
  textDark: {
    color: '#1F2937',
  },
  notLoggedIn: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 24,
  },
  notLoggedInTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 16,
  },
  notLoggedInText: {
    fontSize: 14,
    color: '#9CA3AF',
    textAlign: 'center',
    marginTop: 8,
  },
  loginButton: {
    backgroundColor: '#3B82F6',
    paddingHorizontal: 32,
    paddingVertical: 14,
    borderRadius: 12,
    marginTop: 24,
  },
  loginButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  loadingBox: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    color: '#9CA3AF',
    marginTop: 12,
  },
  balanceCardWrapper: {
    paddingHorizontal: 20,
    paddingTop: 56,
    marginBottom: 16,
  },
  balanceCard: {
    borderRadius: 24,
    padding: 24,
  },
  balanceHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  balanceHeader: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  qrButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  balanceLabel: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.9)',
    marginLeft: 8,
  },
  balanceAmount: {
    fontSize: 36,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 20,
  },
  balanceRow: {
    flexDirection: 'row',
    backgroundColor: 'rgba(255,255,255,0.15)',
    borderRadius: 16,
    padding: 16,
  },
  balanceCol: {
    flex: 1,
    alignItems: 'center',
  },
  balanceSubLabel: {
    fontSize: 12,
    color: 'rgba(255,255,255,0.7)',
  },
  balanceSubValue: {
    fontSize: 16,
    fontWeight: '600',
    color: '#FFFFFF',
    marginTop: 4,
  },
  actionsRow: {
    flexDirection: 'row',
    paddingHorizontal: 16,
    marginBottom: 16,
  },
  actionButton: {
    flex: 1,
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    marginHorizontal: 4,
    paddingVertical: 14,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
  },
  actionButtonDisabled: {
    opacity: 0.5,
  },
  actionIconBox: {
    width: 44,
    height: 44,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  actionLabel: {
    fontSize: 12,
    color: '#FFFFFF',
    fontWeight: '500',
  },

  // Stats Row
  statsRow: {
    flexDirection: 'row',
    paddingHorizontal: 16,
    marginBottom: 16,
    gap: 12,
  },
  statCard: {
    flex: 1,
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  statCardLight: {
    backgroundColor: '#FFFFFF',
    borderColor: '#E5E7EB',
  },
  statIcon: {
    width: 36,
    height: 36,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  statValue: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  statValueLight: {
    color: '#1F2937',
  },
  statLabel: {
    fontSize: 12,
    color: '#9CA3AF',
  },

  // KYC Warning
  kycWarning: {
    flexDirection: 'row',
    alignItems: 'center',
    marginHorizontal: 16,
    marginBottom: 16,
    backgroundColor: 'rgba(245,158,11,0.15)',
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: 'rgba(245,158,11,0.3)',
  },
  kycWarningContent: {
    flex: 1,
    marginLeft: 12,
  },
  kycWarningTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#F59E0B',
  },
  kycWarningText: {
    fontSize: 12,
    color: '#9CA3AF',
    marginTop: 2,
  },

  // Transactions
  txSection: {
    paddingHorizontal: 20,
  },
  txHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  txHeaderTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  txHeaderTitleLight: {
    color: '#1F2937',
  },
  txHeaderLink: {
    fontSize: 14,
    color: '#3B82F6',
    fontWeight: '500',
  },
  txItem: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 14,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
  },
  txItemLight: {
    backgroundColor: '#FFFFFF',
    borderColor: '#E5E7EB',
  },
  txIcon: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  txInfo: {
    flex: 1,
  },
  txTitle: {
    fontSize: 14,
    fontWeight: '500',
    color: '#FFFFFF',
  },
  txTitleLight: {
    color: '#1F2937',
  },
  txDate: {
    fontSize: 12,
    color: '#9CA3AF',
    marginTop: 2,
  },
  txAmountBox: {
    alignItems: 'flex-end',
  },
  txAmount: {
    fontSize: 14,
    fontWeight: 'bold',
  },
  txStatus: {
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 8,
    marginTop: 4,
  },
  txStatusText: {
    fontSize: 10,
    fontWeight: '600',
  },
  emptyTx: {
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 14,
    padding: 24,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
  },
  emptyTxLight: {
    backgroundColor: '#FFFFFF',
    borderColor: '#E5E7EB',
  },
  emptyTxText: {
    color: '#9CA3AF',
    marginTop: 8,
  },

  // QR Modal Styles
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.7)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  qrModalContent: {
    backgroundColor: '#1F2937',
    borderRadius: 24,
    padding: 24,
    width: '100%',
    maxWidth: 360,
    alignItems: 'center',
  },
  qrModalContentLight: {
    backgroundColor: '#FFFFFF',
  },
  qrModalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    width: '100%',
    marginBottom: 24,
  },
  qrModalTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  qrModalClose: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.1)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  qrCodeBox: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 20,
    marginBottom: 20,
  },
  qrPlaceholder: {
    width: 200,
    height: 200,
    alignItems: 'center',
    justifyContent: 'center',
  },
  qrPlaceholderText: {
    color: '#9CA3AF',
    marginTop: 12,
    fontSize: 14,
  },
  qrUserInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  qrUserName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#FFFFFF',
    marginLeft: 8,
  },
  walletAddressBox: {
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 12,
    padding: 16,
    width: '100%',
    marginBottom: 20,
  },
  walletAddressBoxLight: {
    backgroundColor: '#F3F4F6',
  },
  walletAddressLabel: {
    fontSize: 12,
    color: '#9CA3AF',
    marginBottom: 4,
  },
  walletAddressValue: {
    fontSize: 14,
    color: '#FFFFFF',
    fontWeight: '500',
  },
  qrModalActions: {
    flexDirection: 'row',
    marginBottom: 16,
  },
  qrActionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 12,
  },
  qrShareBtn: {
    backgroundColor: '#3B82F6',
  },
  qrActionBtnText: {
    color: '#FFFFFF',
    fontWeight: '600',
    marginLeft: 8,
  },
  qrInfoText: {
    fontSize: 12,
    color: '#9CA3AF',
    textAlign: 'center',
    lineHeight: 18,
  },
});
