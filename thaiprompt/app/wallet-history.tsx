/**
 * Wallet History Screen - ประวัติธุรกรรมในแอพ
 *
 * Features:
 * - แสดงธุรกรรมทั้งหมดในแอพ (ไม่ต้องเปิดเว็บ)
 * - Pagination (โหลดทีละหน้า)
 * - กรองตามวันเวลา
 * - กรองตามประเภท (รายรับ/รายจ่าย/ทั้งหมด)
 */

import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  Pressable,
  StyleSheet,
  StatusBar,
  ActivityIndicator,
  Modal,
  RefreshControl,
  Platform,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { getWalletTransactions } from '@/services/api';
import { formatCurrency } from '@/constants';

// Transaction type
interface Transaction {
  id: number;
  type: 'in' | 'out';
  amount: number;
  title: string;
  description?: string;
  status: string;
  date: string;
  dateRelative: string;
  referenceType?: string;
  referenceId?: number;
}

// Filter type
type FilterType = 'all' | 'in' | 'out';

// Date Range type
interface DateRange {
  startDate: Date | null;
  endDate: Date | null;
  label: string;
}

// Preset date ranges
const DATE_PRESETS: DateRange[] = [
  { startDate: null, endDate: null, label: 'ทั้งหมด' },
  {
    startDate: new Date(new Date().setHours(0, 0, 0, 0)),
    endDate: new Date(),
    label: 'วันนี้',
  },
  {
    startDate: new Date(new Date().setDate(new Date().getDate() - 7)),
    endDate: new Date(),
    label: '7 วันล่าสุด',
  },
  {
    startDate: new Date(new Date().setDate(new Date().getDate() - 30)),
    endDate: new Date(),
    label: '30 วันล่าสุด',
  },
  {
    startDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
    endDate: new Date(),
    label: 'เดือนนี้',
  },
  {
    startDate: new Date(new Date().getFullYear(), new Date().getMonth() - 1, 1),
    endDate: new Date(new Date().getFullYear(), new Date().getMonth(), 0),
    label: 'เดือนที่แล้ว',
  },
];

// Get emoji for transaction type
const getTransactionEmoji = (type: 'in' | 'out', referenceType?: string): string => {
  if (referenceType === 'commission') return '🎁';
  if (referenceType === 'order') return '🛒';
  if (referenceType === 'withdrawal') return '💰';
  if (referenceType === 'topup') return '➕';
  if (referenceType === 'transfer') return '🔄';
  if (referenceType === 'refund') return '↩️';
  return type === 'in' ? '⬇️' : '⬆️';
};

// Status config
const getStatusConfig = (status: string): { color: string; text: string } => {
  const configs: Record<string, { color: string; text: string }> = {
    completed: { color: '#10B981', text: 'สำเร็จ' },
    pending: { color: '#F59E0B', text: 'รอดำเนินการ' },
    processing: { color: '#3B82F6', text: 'กำลังดำเนินการ' },
    failed: { color: '#EF4444', text: 'ล้มเหลว' },
    cancelled: { color: '#6B7280', text: 'ยกเลิก' },
  };
  return configs[status] || configs.pending;
};

// Transaction Item Component
const TransactionItem = ({
  transaction,
  isDark,
  onPress,
}: {
  transaction: Transaction;
  isDark: boolean;
  onPress: () => void;
}) => {
  const isIncome = transaction.type === 'in';
  const emoji = getTransactionEmoji(transaction.type, transaction.referenceType);
  const statusConfig = getStatusConfig(transaction.status);

  return (
    <Pressable
      style={[styles.txItem, isDark ? styles.txItemDark : styles.txItemLight]}
      onPress={onPress}
    >
      <View style={[styles.txIcon, { backgroundColor: isIncome ? '#D1FAE5' : '#FEE2E2' }]}>
        <Text style={{ fontSize: 20 }}>{emoji}</Text>
      </View>

      <View style={styles.txInfo}>
        <Text style={[styles.txTitle, isDark && styles.textLight]} numberOfLines={1}>
          {transaction.title}
        </Text>
        <Text style={styles.txDate}>{transaction.dateRelative || transaction.date}</Text>
      </View>

      <View style={styles.txAmountBox}>
        <Text style={[styles.txAmount, { color: isIncome ? '#10B981' : '#EF4444' }]}>
          {isIncome ? '+' : '-'}{formatCurrency(transaction.amount)}
        </Text>
        <View style={[styles.txStatus, { backgroundColor: `${statusConfig.color}20` }]}>
          <Text style={[styles.txStatusText, { color: statusConfig.color }]}>
            {statusConfig.text}
          </Text>
        </View>
      </View>
    </Pressable>
  );
};

// Filter Button Component
const FilterButton = ({
  label,
  isActive,
  onPress,
  isDark,
}: {
  label: string;
  isActive: boolean;
  onPress: () => void;
  isDark: boolean;
}) => (
  <Pressable
    style={[
      styles.filterButton,
      isActive && styles.filterButtonActive,
      isDark && !isActive && styles.filterButtonDark,
    ]}
    onPress={onPress}
  >
    <Text
      style={[
        styles.filterButtonText,
        isActive && styles.filterButtonTextActive,
        isDark && !isActive && styles.filterButtonTextDark,
      ]}
    >
      {label}
    </Text>
  </Pressable>
);

// Transaction Detail Modal
const TransactionDetailModal = ({
  visible,
  transaction,
  onClose,
  isDark,
}: {
  visible: boolean;
  transaction: Transaction | null;
  onClose: () => void;
  isDark: boolean;
}) => {
  if (!transaction) return null;

  const isIncome = transaction.type === 'in';
  const emoji = getTransactionEmoji(transaction.type, transaction.referenceType);
  const statusConfig = getStatusConfig(transaction.status);

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <Pressable style={styles.modalOverlay} onPress={onClose}>
        <Pressable style={[styles.modalContent, isDark && styles.modalContentDark]}>
          {/* Header */}
          <View style={styles.modalHeader}>
            <View style={[styles.modalIcon, { backgroundColor: isIncome ? '#D1FAE5' : '#FEE2E2' }]}>
              <Text style={{ fontSize: 32 }}>{emoji}</Text>
            </View>
            <Text
              style={[
                styles.modalAmount,
                { color: isIncome ? '#10B981' : '#EF4444' },
              ]}
            >
              {isIncome ? '+' : '-'}{formatCurrency(transaction.amount)}
            </Text>
            <View style={[styles.modalStatus, { backgroundColor: `${statusConfig.color}20` }]}>
              <Text style={[styles.modalStatusText, { color: statusConfig.color }]}>
                {statusConfig.text}
              </Text>
            </View>
          </View>

          {/* Details */}
          <View style={[styles.modalDetails, isDark && styles.modalDetailsDark]}>
            <View style={styles.detailRow}>
              <Text style={styles.detailLabel}>รายการ</Text>
              <Text style={[styles.detailValue, isDark && styles.textLight]}>
                {transaction.title}
              </Text>
            </View>

            {transaction.description && (
              <View style={styles.detailRow}>
                <Text style={styles.detailLabel}>รายละเอียด</Text>
                <Text style={[styles.detailValue, isDark && styles.textLight]}>
                  {transaction.description}
                </Text>
              </View>
            )}

            <View style={styles.detailRow}>
              <Text style={styles.detailLabel}>วันที่</Text>
              <Text style={[styles.detailValue, isDark && styles.textLight]}>
                {transaction.date}
              </Text>
            </View>

            <View style={styles.detailRow}>
              <Text style={styles.detailLabel}>ประเภท</Text>
              <Text style={[styles.detailValue, isDark && styles.textLight]}>
                {transaction.referenceType || (isIncome ? 'รายรับ' : 'รายจ่าย')}
              </Text>
            </View>

            <View style={styles.detailRow}>
              <Text style={styles.detailLabel}>รหัสอ้างอิง</Text>
              <Text style={[styles.detailValue, isDark && styles.textLight]}>
                #{transaction.id}
              </Text>
            </View>
          </View>

          {/* Close Button */}
          <Pressable style={styles.modalCloseButton} onPress={onClose}>
            <Text style={styles.modalCloseButtonText}>ปิด</Text>
          </Pressable>
        </Pressable>
      </Pressable>
    </Modal>
  );
};

// Main Component
export default function WalletHistoryScreen() {
  const { isAuthenticated } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  // State
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [filterType, setFilterType] = useState<FilterType>('all');
  const [dateRange, setDateRange] = useState<DateRange>(DATE_PRESETS[0]);
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [selectedTransaction, setSelectedTransaction] = useState<Transaction | null>(null);
  const [showDetailModal, setShowDetailModal] = useState(false);

  // Summary
  const [totalIncome, setTotalIncome] = useState(0);
  const [totalExpense, setTotalExpense] = useState(0);

  // Load transactions
  const loadTransactions = useCallback(async (pageNum: number = 1, refresh: boolean = false) => {
    if (!isAuthenticated) return;

    if (pageNum === 1) {
      setIsLoading(true);
    } else {
      setIsLoadingMore(true);
    }

    try {
      const response = await getWalletTransactions(
        pageNum,
        filterType,
        20,
        dateRange.startDate?.toISOString(),
        dateRange.endDate?.toISOString()
      );

      if (response?.success && response.data) {
        const newItems = response.data.items.map((tx: any) => ({
          id: tx.id,
          type: tx.type,
          amount: tx.amount,
          title: tx.title,
          description: tx.description,
          status: tx.status || 'completed',
          date: tx.date,
          dateRelative: tx.dateRelative,
          referenceType: tx.referenceType,
          referenceId: tx.referenceId,
        }));

        if (refresh || pageNum === 1) {
          setTransactions(newItems);
        } else {
          setTransactions(prev => [...prev, ...newItems]);
        }

        setHasMore(response.data.hasMore ?? newItems.length >= 20);
        setTotalIncome(response.data.summary?.totalIncome || 0);
        setTotalExpense(response.data.summary?.totalExpense || 0);
      }
    } catch (error) {
      console.error('Load transactions error:', error);
    } finally {
      setIsLoading(false);
      setIsLoadingMore(false);
      setRefreshing(false);
    }
  }, [isAuthenticated, filterType, dateRange]);

  // Initial load
  useEffect(() => {
    loadTransactions(1, true);
    setPage(1);
  }, [filterType, dateRange]);

  // Handle refresh
  const handleRefresh = () => {
    setRefreshing(true);
    setPage(1);
    loadTransactions(1, true);
  };

  // Handle load more
  const handleLoadMore = () => {
    if (!isLoadingMore && hasMore) {
      const nextPage = page + 1;
      setPage(nextPage);
      loadTransactions(nextPage);
    }
  };

  // Handle filter change
  const handleFilterChange = (type: FilterType) => {
    setFilterType(type);
    setPage(1);
  };

  // Handle date range change
  const handleDateRangeChange = (range: DateRange) => {
    setDateRange(range);
    setShowDatePicker(false);
    setPage(1);
  };

  // Handle transaction press
  const handleTransactionPress = (transaction: Transaction) => {
    setSelectedTransaction(transaction);
    setShowDetailModal(true);
  };

  // Not logged in
  if (!isAuthenticated) {
    return (
      <View style={[styles.container, isDark && styles.containerDark]}>
        <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />
        <View style={styles.notLoggedIn}>
          <Text style={{ fontSize: 60 }}>📋</Text>
          <Text style={[styles.notLoggedInTitle, isDark && styles.textLight]}>
            ประวัติธุรกรรม
          </Text>
          <Text style={styles.notLoggedInText}>เข้าสู่ระบบเพื่อดูประวัติธุรกรรม</Text>
          <Pressable style={styles.loginButton} onPress={() => router.push('/login')}>
            <Text style={styles.loginButtonText}>เข้าสู่ระบบ</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  // Render item
  const renderItem = ({ item }: { item: Transaction }) => (
    <TransactionItem
      transaction={item}
      isDark={isDark}
      onPress={() => handleTransactionPress(item)}
    />
  );

  // Render footer (loading more indicator)
  const renderFooter = () => {
    if (!isLoadingMore) return null;
    return (
      <View style={styles.loadingMore}>
        <ActivityIndicator size="small" color="#3B82F6" />
        <Text style={styles.loadingMoreText}>กำลังโหลดเพิ่ม...</Text>
      </View>
    );
  };

  // Render empty
  const renderEmpty = () => {
    if (isLoading) return null;
    return (
      <View style={styles.emptyContainer}>
        <Text style={{ fontSize: 60 }}>📭</Text>
        <Text style={[styles.emptyTitle, isDark && styles.textLight]}>ไม่มีธุรกรรม</Text>
        <Text style={styles.emptyText}>ยังไม่มีรายการธุรกรรมในช่วงเวลานี้</Text>
      </View>
    );
  };

  return (
    <View style={[styles.container, isDark && styles.containerDark]}>
      <StatusBar barStyle="light-content" backgroundColor="#3B82F6" />

      {/* Header */}
      <LinearGradient
        colors={['#3B82F6', '#2563EB']}
        style={styles.header}
      >
        <Pressable style={styles.backButton} onPress={() => router.back()}>
          <Text style={styles.backIcon}>←</Text>
        </Pressable>
        <View style={{ flex: 1 }}>
          <Text style={styles.headerTitle}>ประวัติธุรกรรม</Text>
          <Text style={styles.headerSubtitle}>ดูรายการย้อนหลังทั้งหมด</Text>
        </View>
      </LinearGradient>

      {/* Summary */}
      <View style={[styles.summaryCard, isDark && styles.summaryCardDark]}>
        <View style={styles.summaryItem}>
          <Text style={{ fontSize: 20 }}>📈</Text>
          <View>
            <Text style={styles.summaryLabel}>รายรับ</Text>
            <Text style={[styles.summaryValue, { color: '#10B981' }]}>
              +{formatCurrency(totalIncome)}
            </Text>
          </View>
        </View>
        <View style={styles.summaryDivider} />
        <View style={styles.summaryItem}>
          <Text style={{ fontSize: 20 }}>📉</Text>
          <View>
            <Text style={styles.summaryLabel}>รายจ่าย</Text>
            <Text style={[styles.summaryValue, { color: '#EF4444' }]}>
              -{formatCurrency(totalExpense)}
            </Text>
          </View>
        </View>
      </View>

      {/* Filters */}
      <View style={styles.filtersContainer}>
        {/* Type Filter */}
        <View style={styles.typeFilters}>
          <FilterButton
            label="ทั้งหมด"
            isActive={filterType === 'all'}
            onPress={() => handleFilterChange('all')}
            isDark={isDark}
          />
          <FilterButton
            label="รายรับ"
            isActive={filterType === 'in'}
            onPress={() => handleFilterChange('in')}
            isDark={isDark}
          />
          <FilterButton
            label="รายจ่าย"
            isActive={filterType === 'out'}
            onPress={() => handleFilterChange('out')}
            isDark={isDark}
          />
        </View>

        {/* Date Filter */}
        <Pressable
          style={[styles.dateFilterButton, isDark && styles.dateFilterButtonDark]}
          onPress={() => setShowDatePicker(true)}
        >
          <Text style={{ fontSize: 16 }}>📅</Text>
          <Text style={[styles.dateFilterText, isDark && styles.textLight]}>
            {dateRange.label}
          </Text>
          <Text style={{ fontSize: 12, color: '#9CA3AF' }}>▼</Text>
        </Pressable>
      </View>

      {/* Transaction List */}
      {isLoading ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text style={[styles.loadingText, isDark && styles.textLight]}>กำลังโหลด...</Text>
        </View>
      ) : (
        <FlatList
          data={transactions}
          renderItem={renderItem}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={styles.listContent}
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={handleRefresh}
              tintColor="#3B82F6"
              colors={['#3B82F6']}
            />
          }
          onEndReached={handleLoadMore}
          onEndReachedThreshold={0.3}
          ListFooterComponent={renderFooter}
          ListEmptyComponent={renderEmpty}
        />
      )}

      {/* Date Picker Modal */}
      <Modal
        visible={showDatePicker}
        transparent
        animationType="fade"
        onRequestClose={() => setShowDatePicker(false)}
      >
        <Pressable style={styles.modalOverlay} onPress={() => setShowDatePicker(false)}>
          <View style={[styles.datePickerContent, isDark && styles.datePickerContentDark]}>
            <Text style={[styles.datePickerTitle, isDark && styles.textLight]}>
              เลือกช่วงเวลา
            </Text>
            {DATE_PRESETS.map((preset, index) => (
              <Pressable
                key={index}
                style={[
                  styles.datePresetItem,
                  dateRange.label === preset.label && styles.datePresetItemActive,
                ]}
                onPress={() => handleDateRangeChange(preset)}
              >
                <Text
                  style={[
                    styles.datePresetText,
                    dateRange.label === preset.label && styles.datePresetTextActive,
                    isDark && dateRange.label !== preset.label && styles.textLight,
                  ]}
                >
                  {preset.label}
                </Text>
                {dateRange.label === preset.label && (
                  <Text style={{ fontSize: 16 }}>✓</Text>
                )}
              </Pressable>
            ))}
          </View>
        </Pressable>
      </Modal>

      {/* Transaction Detail Modal */}
      <TransactionDetailModal
        visible={showDetailModal}
        transaction={selectedTransaction}
        onClose={() => {
          setShowDetailModal(false);
          setSelectedTransaction(null);
        }}
        isDark={isDark}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F9FAFB',
  },
  containerDark: {
    backgroundColor: '#111827',
  },
  textLight: {
    color: '#FFF',
  },

  // Header
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingTop: 50,
    paddingHorizontal: 16,
    paddingBottom: 20,
  },
  backButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  backIcon: {
    fontSize: 24,
    color: '#FFF',
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFF',
  },
  headerSubtitle: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
    marginTop: 2,
  },

  // Summary
  summaryCard: {
    flexDirection: 'row',
    backgroundColor: '#FFF',
    marginHorizontal: 16,
    marginTop: -10,
    borderRadius: 16,
    padding: 16,
    ...Platform.select({
      ios: {
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 8,
      },
      android: {
        elevation: 4,
      },
    }),
  },
  summaryCardDark: {
    backgroundColor: '#1F2937',
  },
  summaryItem: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  summaryDivider: {
    width: 1,
    backgroundColor: '#E5E7EB',
    marginHorizontal: 16,
  },
  summaryLabel: {
    fontSize: 12,
    color: '#9CA3AF',
  },
  summaryValue: {
    fontSize: 16,
    fontWeight: 'bold',
    marginTop: 2,
  },

  // Filters
  filtersContainer: {
    paddingHorizontal: 16,
    paddingTop: 16,
    paddingBottom: 8,
  },
  typeFilters: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 12,
  },
  filterButton: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: '#E5E7EB',
  },
  filterButtonDark: {
    backgroundColor: '#374151',
  },
  filterButtonActive: {
    backgroundColor: '#3B82F6',
  },
  filterButtonText: {
    fontSize: 14,
    color: '#6B7280',
    fontWeight: '500',
  },
  filterButtonTextDark: {
    color: '#9CA3AF',
  },
  filterButtonTextActive: {
    color: '#FFF',
  },
  dateFilterButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: '#FFF',
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  dateFilterButtonDark: {
    backgroundColor: '#1F2937',
    borderColor: '#374151',
  },
  dateFilterText: {
    flex: 1,
    fontSize: 14,
    color: '#374151',
  },

  // List
  listContent: {
    padding: 16,
    paddingTop: 8,
    paddingBottom: 100,
  },
  txItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 14,
    borderRadius: 14,
    marginBottom: 10,
  },
  txItemLight: {
    backgroundColor: '#FFF',
  },
  txItemDark: {
    backgroundColor: '#1F2937',
  },
  txIcon: {
    width: 44,
    height: 44,
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
    color: '#1F2937',
    marginBottom: 2,
  },
  txDate: {
    fontSize: 12,
    color: '#9CA3AF',
  },
  txAmountBox: {
    alignItems: 'flex-end',
  },
  txAmount: {
    fontSize: 15,
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

  // Loading
  loadingContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  loadingText: {
    color: '#6B7280',
    marginTop: 12,
  },
  loadingMore: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 16,
    gap: 8,
  },
  loadingMoreText: {
    color: '#9CA3AF',
    fontSize: 14,
  },

  // Empty
  emptyContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 60,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#1F2937',
    marginTop: 16,
  },
  emptyText: {
    fontSize: 14,
    color: '#9CA3AF',
    marginTop: 4,
  },

  // Not logged in
  notLoggedIn: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
  },
  notLoggedInTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1F2937',
    marginTop: 16,
  },
  notLoggedInText: {
    fontSize: 14,
    color: '#9CA3AF',
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
    color: '#FFF',
    fontSize: 16,
    fontWeight: 'bold',
  },

  // Modal
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  modalContent: {
    backgroundColor: '#FFF',
    borderRadius: 24,
    padding: 24,
    width: '100%',
    maxWidth: 360,
  },
  modalContentDark: {
    backgroundColor: '#1F2937',
  },
  modalHeader: {
    alignItems: 'center',
    marginBottom: 20,
  },
  modalIcon: {
    width: 64,
    height: 64,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  modalAmount: {
    fontSize: 28,
    fontWeight: 'bold',
    marginBottom: 8,
  },
  modalStatus: {
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 12,
  },
  modalStatusText: {
    fontSize: 13,
    fontWeight: '600',
  },
  modalDetails: {
    backgroundColor: '#F9FAFB',
    borderRadius: 16,
    padding: 16,
    marginBottom: 20,
  },
  modalDetailsDark: {
    backgroundColor: '#374151',
  },
  detailRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(0,0,0,0.05)',
  },
  detailLabel: {
    fontSize: 13,
    color: '#9CA3AF',
  },
  detailValue: {
    fontSize: 14,
    fontWeight: '500',
    color: '#1F2937',
    textAlign: 'right',
    flex: 1,
    marginLeft: 16,
  },
  modalCloseButton: {
    backgroundColor: '#3B82F6',
    paddingVertical: 14,
    borderRadius: 12,
    alignItems: 'center',
  },
  modalCloseButtonText: {
    color: '#FFF',
    fontSize: 16,
    fontWeight: '600',
  },

  // Date Picker
  datePickerContent: {
    backgroundColor: '#FFF',
    borderRadius: 20,
    padding: 20,
    width: '100%',
    maxWidth: 320,
  },
  datePickerContentDark: {
    backgroundColor: '#1F2937',
  },
  datePickerTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: 16,
    textAlign: 'center',
  },
  datePresetItem: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 14,
    paddingHorizontal: 16,
    borderRadius: 12,
    marginBottom: 8,
  },
  datePresetItemActive: {
    backgroundColor: '#EBF5FF',
  },
  datePresetText: {
    fontSize: 15,
    color: '#374151',
  },
  datePresetTextActive: {
    color: '#3B82F6',
    fontWeight: '600',
  },
});
