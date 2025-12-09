/**
 * Wallet Screen - Premium Stable Version
 * ใช้ StyleSheet แทน NativeWind
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
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '@/stores/authStore';
import { getWallet, getWalletTransactions } from '@/services/api';
import { formatCurrency } from '@/constants';

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
}

// Action Button Component
const ActionButton = ({
  icon,
  label,
  color,
  onPress,
}: {
  icon: string;
  label: string;
  color: string;
  onPress: () => void;
}) => (
  <Pressable style={styles.actionButton} onPress={onPress}>
    <View style={[styles.actionIconBox, { backgroundColor: color }]}>
      <Ionicons name={icon as any} size={22} color="#FFF" />
    </View>
    <Text style={styles.actionLabel}>{label}</Text>
  </Pressable>
);

// Transaction Item Component
const TransactionItem = ({
  type,
  title,
  amount,
  date,
  status,
}: {
  type: 'in' | 'out';
  title: string;
  amount: number;
  date: string;
  status: string;
}) => {
  const isIncome = type === 'in';

  return (
    <View style={styles.txItem}>
      <View style={[styles.txIcon, { backgroundColor: isIncome ? '#D1FAE5' : '#FEE2E2' }]}>
        <Ionicons
          name={isIncome ? 'arrow-down-outline' : 'arrow-up-outline'}
          size={18}
          color={isIncome ? '#10B981' : '#EF4444'}
        />
      </View>
      <View style={styles.txInfo}>
        <Text style={styles.txTitle}>{title}</Text>
        <Text style={styles.txDate}>{date}</Text>
      </View>
      <View style={styles.txAmountBox}>
        <Text style={[styles.txAmount, { color: isIncome ? '#10B981' : '#EF4444' }]}>
          {isIncome ? '+' : '-'}{formatCurrency(amount)}
        </Text>
        <View style={[styles.txStatus, { backgroundColor: status === 'completed' ? '#D1FAE5' : '#FEF3C7' }]}>
          <Text style={[styles.txStatusText, { color: status === 'completed' ? '#059669' : '#D97706' }]}>
            {status === 'completed' ? 'สำเร็จ' : 'รอ'}
          </Text>
        </View>
      </View>
    </View>
  );
};

export default function WalletScreen() {
  const { isAuthenticated, user } = useAuthStore();

  const [wallet, setWallet] = useState<WalletData | null>(null);
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [refreshing, setRefreshing] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

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
          status: tx.status === 'completed' ? 'completed' : 'pending',
          date: tx.date,
          dateRelative: tx.dateRelative,
        }));
        setTransactions(txItems);
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

  // ถ้ายังไม่ login
  if (!isAuthenticated) {
    return (
      <View style={styles.container}>
        <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
        <View style={styles.notLoggedIn}>
          <Ionicons name="wallet-outline" size={70} color="#4B5563" />
          <Text style={styles.notLoggedInTitle}>กระเป๋าเงินของคุณ</Text>
          <Text style={styles.notLoggedInText}>เข้าสู่ระบบเพื่อดูยอดเงินและทำธุรกรรม</Text>
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
      <View style={styles.container}>
        <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
        <View style={styles.loadingBox}>
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text style={styles.loadingText}>กำลังโหลด...</Text>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />

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
        {/* Header */}
        <View style={styles.header}>
          <Text style={styles.headerTitle}>กระเป๋าเงิน</Text>
        </View>

        {/* Balance Card */}
        <View style={styles.balanceCardWrapper}>
          <LinearGradient
            colors={['#10B981', '#059669']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={styles.balanceCard}
          >
            <View style={styles.balanceHeader}>
              <Ionicons name="wallet-outline" size={22} color="rgba(255,255,255,0.9)" />
              <Text style={styles.balanceLabel}>ยอดเงินคงเหลือ</Text>
            </View>
            <Text style={styles.balanceAmount}>
              {formatCurrency(wallet?.balance || 0)}
            </Text>

            <View style={styles.balanceRow}>
              <View style={styles.balanceCol}>
                <Text style={styles.balanceSubLabel}>พร้อมถอน</Text>
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
            onPress={() => Alert.alert('เติมเงิน', 'ฟีเจอร์นี้กำลังพัฒนา')}
          />
          <ActionButton
            icon="arrow-up-circle-outline"
            label="ถอนเงิน"
            color="#10B981"
            onPress={() => Alert.alert('ถอนเงิน', 'ฟีเจอร์นี้กำลังพัฒนา')}
          />
          <ActionButton
            icon="swap-horizontal-outline"
            label="โอนเงิน"
            color="#8B5CF6"
            onPress={() => Alert.alert('โอนเงิน', 'ฟีเจอร์นี้กำลังพัฒนา')}
          />
          <ActionButton
            icon="qr-code-outline"
            label="QR Code"
            color="#EC4899"
            onPress={() => Alert.alert('QR Code', 'ฟีเจอร์นี้กำลังพัฒนา')}
          />
        </View>

        {/* Transactions */}
        <View style={styles.txSection}>
          <View style={styles.txHeader}>
            <Text style={styles.txHeaderTitle}>ประวัติธุรกรรม</Text>
            <Pressable onPress={() => Alert.alert('ประวัติทั้งหมด', 'ฟีเจอร์นี้กำลังพัฒนา')}>
              <Text style={styles.txHeaderLink}>ดูทั้งหมด</Text>
            </Pressable>
          </View>

          {transactions.length > 0 ? (
            transactions.map((tx, index) => (
              <TransactionItem
                key={tx.id || index}
                type={tx.type}
                title={tx.title}
                amount={tx.amount}
                date={tx.date}
                status={tx.status}
              />
            ))
          ) : (
            <View style={styles.emptyTx}>
              <Ionicons name="receipt-outline" size={40} color="#4B5563" />
              <Text style={styles.emptyTxText}>ยังไม่มีธุรกรรม</Text>
            </View>
          )}
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    paddingBottom: 100,
  },
  header: {
    paddingHorizontal: 20,
    paddingTop: 56,
    paddingBottom: 16,
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFFFFF',
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
    marginBottom: 16,
  },
  balanceCard: {
    borderRadius: 20,
    padding: 20,
  },
  balanceHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 6,
  },
  balanceLabel: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.9)',
    marginLeft: 8,
  },
  balanceAmount: {
    fontSize: 34,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 16,
  },
  balanceRow: {
    flexDirection: 'row',
  },
  balanceCol: {
    flex: 1,
  },
  balanceSubLabel: {
    fontSize: 12,
    color: 'rgba(255,255,255,0.7)',
  },
  balanceSubValue: {
    fontSize: 16,
    fontWeight: '600',
    color: '#FFFFFF',
    marginTop: 2,
  },
  actionsRow: {
    flexDirection: 'row',
    paddingHorizontal: 16,
    marginBottom: 20,
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
  actionIconBox: {
    width: 40,
    height: 40,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 6,
  },
  actionLabel: {
    fontSize: 11,
    color: '#FFFFFF',
    fontWeight: '500',
  },
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
  txIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
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
  emptyTxText: {
    color: '#9CA3AF',
    marginTop: 8,
  },
});
