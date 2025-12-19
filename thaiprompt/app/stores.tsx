/**
 * Stores Screen - แสดงรายการร้านค้า
 *
 * รองรับ query params:
 * - /stores?type=official - แสดงร้านค้าทางการ
 * - /stores?type=featured - แสดงร้านแนะนำติดดาว
 * - /stores - แสดงร้านค้าทั้งหมด
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  Pressable,
  RefreshControl,
  Image,
  ActivityIndicator,
  StyleSheet,
  StatusBar,
} from 'react-native';
import { router, useLocalSearchParams, Stack } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { useAppStore } from '@/stores/appStore';
import { getOfficialStores, getFeaturedStores } from '@/services/api';

// Store Type
interface Store {
  id: string;
  name: string;
  logo?: string;
  rating: number;
  isOfficial: boolean;
  isFeatured: boolean;
  productCount: number;
}

// Store Card Component
const StoreCard = ({
  store,
  onPress,
}: {
  store: Store;
  onPress: () => void;
}) => (
  <Pressable onPress={onPress} style={styles.storeCard}>
    {/* Store Image */}
    <View style={styles.storeImageContainer}>
      {store.logo ? (
        <Image source={{ uri: store.logo }} style={styles.storeImage} resizeMode="cover" />
      ) : (
        <Text style={styles.storeEmoji}>🏪</Text>
      )}

      {/* Badge */}
      {(store.isOfficial || store.isFeatured) && (
        <View style={[styles.storeBadge, store.isOfficial ? styles.badgeBlue : styles.badgeOrange]}>
          <Text style={styles.badgeEmoji}>{store.isOfficial ? '✅' : '⭐'}</Text>
          <Text style={styles.storeBadgeText}>{store.isOfficial ? 'ทางการ' : 'แนะนำ'}</Text>
        </View>
      )}
    </View>

    {/* Store Info - ⭐ เพิ่ม null checks เพื่อป้องกัน crash */}
    <View style={styles.storeInfo}>
      <Text style={styles.storeName} numberOfLines={1}>{store?.name || 'ร้านค้า'}</Text>
      <View style={styles.storeRating}>
        <Text style={styles.ratingEmoji}>⭐</Text>
        <Text style={styles.storeRatingText}>
          {(store?.rating ?? 0).toFixed(1)} • {store?.productCount ?? 0} สินค้า
        </Text>
      </View>
    </View>

    {/* Arrow */}
    <View style={styles.arrowContainer}>
      <Text style={styles.arrowText}>→</Text>
    </View>
  </Pressable>
);

export default function StoresScreen() {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';
  const { type } = useLocalSearchParams<{ type?: string }>();

  // State
  const [stores, setStores] = useState<Store[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  // กำหนด title และ subtitle ตาม type
  const getTitle = () => {
    switch (type) {
      case 'official':
        return 'ร้านค้าทางการ';
      case 'featured':
        return 'ร้านแนะนำติดดาว';
      default:
        return 'ร้านค้าทั้งหมด';
    }
  };

  const getSubtitle = () => {
    switch (type) {
      case 'official':
        return 'ร้านค้าที่ได้รับการรับรองจาก Thaiprompt';
      case 'featured':
        return 'คัดสรรโดยทีมงานของเรา';
      default:
        return 'ค้นหาร้านค้าที่คุณต้องการ';
    }
  };

  const getEmoji = () => {
    switch (type) {
      case 'official':
        return '🏪';
      case 'featured':
        return '⭐';
      default:
        return '🛒';
    }
  };

  // โหลดข้อมูลร้านค้า
  const loadStores = useCallback(async () => {
    try {
      setIsLoading(true);

      let storeData: Store[] = [];

      if (type === 'official') {
        // โหลดร้านค้าทางการ
        const response = await getOfficialStores();
        if (response) {
          storeData = response;
        }
      } else if (type === 'featured') {
        // โหลดร้านแนะนำ
        const response = await getFeaturedStores();
        if (response) {
          storeData = response;
        }
      } else {
        // โหลดทั้งหมด
        const [official, featured] = await Promise.all([
          getOfficialStores(),
          getFeaturedStores(),
        ]);
        storeData = [...(official || []), ...(featured || [])];
      }

      // ถ้าไม่มีข้อมูลจาก API ใช้ mock data
      if (storeData.length === 0) {
        if (type === 'official') {
          storeData = [
            { id: '1', name: 'Thaiprompt Official', rating: 4.9, isOfficial: true, isFeatured: false, productCount: 150 },
            { id: '2', name: 'TP Electronics', rating: 4.8, isOfficial: true, isFeatured: false, productCount: 89 },
            { id: '3', name: 'TP Fashion', rating: 4.7, isOfficial: true, isFeatured: false, productCount: 234 },
            { id: '4', name: 'TP Home & Living', rating: 4.6, isOfficial: true, isFeatured: false, productCount: 56 },
            { id: '5', name: 'TP Beauty', rating: 4.8, isOfficial: true, isFeatured: false, productCount: 123 },
          ];
        } else if (type === 'featured') {
          storeData = [
            { id: '10', name: 'TopSeller Shop', rating: 4.9, isOfficial: false, isFeatured: true, productCount: 67 },
            { id: '11', name: 'BestDeal Store', rating: 4.8, isOfficial: false, isFeatured: true, productCount: 123 },
            { id: '12', name: 'Premium Goods', rating: 4.7, isOfficial: false, isFeatured: true, productCount: 45 },
            { id: '13', name: 'Quality First', rating: 4.6, isOfficial: false, isFeatured: true, productCount: 78 },
            { id: '14', name: 'Smart Shop', rating: 4.5, isOfficial: false, isFeatured: true, productCount: 92 },
            { id: '15', name: 'Value Plus', rating: 4.7, isOfficial: false, isFeatured: true, productCount: 156 },
          ];
        } else {
          storeData = [
            { id: '1', name: 'Thaiprompt Official', rating: 4.9, isOfficial: true, isFeatured: false, productCount: 150 },
            { id: '2', name: 'TP Electronics', rating: 4.8, isOfficial: true, isFeatured: false, productCount: 89 },
            { id: '10', name: 'TopSeller Shop', rating: 4.9, isOfficial: false, isFeatured: true, productCount: 67 },
            { id: '11', name: 'BestDeal Store', rating: 4.8, isOfficial: false, isFeatured: true, productCount: 123 },
            { id: '12', name: 'Premium Goods', rating: 4.7, isOfficial: false, isFeatured: true, productCount: 45 },
          ];
        }
      }

      setStores(storeData);
    } catch (error) {
      console.error('Load stores error:', error);
      // ใช้ mock data เมื่อเกิดข้อผิดพลาด
      setStores([
        { id: '1', name: 'Thaiprompt Official', rating: 4.9, isOfficial: true, isFeatured: false, productCount: 150 },
        { id: '2', name: 'TopSeller Shop', rating: 4.9, isOfficial: false, isFeatured: true, productCount: 67 },
      ]);
    } finally {
      setIsLoading(false);
    }
  }, [type]);

  useEffect(() => {
    loadStores();
  }, [loadStores]);

  // Refresh handler
  const onRefresh = async () => {
    setRefreshing(true);
    await loadStores();
    setRefreshing(false);
  };

  // Render Store Item
  const renderStore = ({ item }: { item: Store }) => (
    <StoreCard
      store={item}
      onPress={() => router.push(`/store/${item.id}`)}
    />
  );

  // Header Component
  const ListHeaderComponent = () => (
    <View style={styles.headerInfo}>
      <View style={styles.headerIconContainer}>
        <Text style={styles.headerEmoji}>{getEmoji()}</Text>
      </View>
      <Text style={styles.headerTitle}>{getTitle()}</Text>
      <Text style={styles.headerSubtitle}>{getSubtitle()}</Text>
      <View style={styles.countBadge}>
        <Text style={styles.countText}>{stores.length} ร้านค้า</Text>
      </View>
    </View>
  );

  // Empty Component
  const ListEmptyComponent = () => {
    if (isLoading) {
      return (
        <View style={styles.emptyContainer}>
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text style={styles.emptyText}>กำลังโหลดร้านค้า...</Text>
        </View>
      );
    }

    return (
      <View style={styles.emptyContainer}>
        <Text style={styles.emptyEmoji}>🏪</Text>
        <Text style={styles.emptyText}>ไม่พบร้านค้า</Text>
      </View>
    );
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />

      <LinearGradient
        colors={['#0F0F23', '#1A1A2E', '#16213E']}
        style={StyleSheet.absoluteFill}
      />

      <Stack.Screen
        options={{
          headerShown: true,
          title: getTitle(),
          headerStyle: { backgroundColor: '#0F0F23' },
          headerTintColor: '#FFFFFF',
          headerLeft: () => (
            <Pressable onPress={() => router.back()} style={styles.backButton}>
              <Text style={styles.backText}>⬅️</Text>
            </Pressable>
          ),
        }}
      />

      <FlatList
        data={stores}
        renderItem={renderStore}
        keyExtractor={(item) => item.id}
        contentContainerStyle={styles.listContent}
        showsVerticalScrollIndicator={false}
        ListHeaderComponent={ListHeaderComponent}
        ListEmptyComponent={ListEmptyComponent}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor="#3B82F6"
          />
        }
        ItemSeparatorComponent={() => <View style={styles.separator} />}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
  },
  backButton: {
    padding: 8,
  },
  backText: {
    fontSize: 24,
    color: '#FFFFFF',
  },
  listContent: {
    paddingHorizontal: 16,
    paddingBottom: 24,
  },

  // Header Info
  headerInfo: {
    alignItems: 'center',
    paddingVertical: 24,
  },
  headerIconContainer: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: 'rgba(59, 130, 246, 0.2)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  headerEmoji: {
    fontSize: 40,
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 8,
  },
  headerSubtitle: {
    fontSize: 14,
    color: '#9CA3AF',
    textAlign: 'center',
    marginBottom: 16,
  },
  countBadge: {
    backgroundColor: 'rgba(59, 130, 246, 0.2)',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: 'rgba(59, 130, 246, 0.3)',
  },
  countText: {
    color: '#3B82F6',
    fontSize: 14,
    fontWeight: '600',
  },

  // Store Card
  storeCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.05)',
    borderRadius: 16,
    padding: 12,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.1)',
  },
  storeImageContainer: {
    width: 64,
    height: 64,
    borderRadius: 12,
    backgroundColor: 'rgba(255, 255, 255, 0.08)',
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
    marginRight: 12,
  },
  storeImage: {
    width: '100%',
    height: '100%',
  },
  storeEmoji: {
    fontSize: 32,
    color: '#9CA3AF',
  },
  storeBadge: {
    position: 'absolute',
    top: 4,
    right: 4,
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 4,
    paddingVertical: 2,
    borderRadius: 8,
  },
  badgeBlue: {
    backgroundColor: '#3B82F6',
  },
  badgeOrange: {
    backgroundColor: '#F59E0B',
  },
  badgeEmoji: {
    fontSize: 8,
    color: 'white',
  },
  storeBadgeText: {
    color: '#FFFFFF',
    fontSize: 7,
    fontWeight: 'bold',
    marginLeft: 2,
  },
  storeInfo: {
    flex: 1,
  },
  storeName: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 4,
  },
  storeRating: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  ratingEmoji: {
    fontSize: 12,
  },
  storeRatingText: {
    color: '#9CA3AF',
    fontSize: 14,
    marginLeft: 4,
  },
  arrowContainer: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: 'rgba(59, 130, 246, 0.2)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  arrowText: {
    color: '#3B82F6',
    fontSize: 16,
  },

  // Separator
  separator: {
    height: 12,
  },

  // Empty State
  emptyContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 64,
  },
  emptyEmoji: {
    fontSize: 64,
    color: '#6B7280',
  },
  emptyText: {
    color: '#9CA3AF',
    marginTop: 16,
    textAlign: 'center',
    fontSize: 16,
  },
});
