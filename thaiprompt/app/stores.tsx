/**
 * รายชื่อร้านค้า — /stores?type=featured|official
 *
 * - featured = ร้านแนะนำที่แอดมินเลือก · official = ร้านที่ยืนยันตัวตนแล้ว
 * - ตัวเลขจริงจาก server (จำนวนสินค้า/เรตติ้ง) ไม่มีค่าสมมติ (SHOP-17)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { router, useLocalSearchParams } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { getFeaturedStores, getOfficialStores, type StoreListItem } from '@/services/api/shopApi';
import { Card3D, Chip, EmptyState, Pill, Screen } from '@/components/ui';
import { CartButton } from '@/components/shop';
import { useTheme, radii, spacing, typography } from '@/theme';

type StoreTab = 'featured' | 'official';

const StoreRow: React.FC<{ store: StoreListItem }> = ({ store }) => {
  const { colors } = useTheme();
  const rating = Number(store.rating) || 0;
  const ratingCount = Number(store.rating_count) || 0;

  return (
    <Card3D
      onPress={() => router.push(`/store/${store.id}` as never)}
      padding={spacing.md}
      radius={radii.lg}
      shadow="sm"
      style={styles.card}
      accessibilityLabel={`ร้าน ${store.name}`}
    >
      <View style={styles.row}>
        {store.logo ? (
          <Image source={{ uri: store.logo }} style={[styles.logo, { backgroundColor: colors.inset }]} contentFit="cover" />
        ) : (
          <View style={[styles.logo, styles.center, { backgroundColor: colors.goldSoft }]}>
            <Text style={styles.logoIcon}>🏪</Text>
          </View>
        )}
        <View style={styles.flex}>
          <Text numberOfLines={1} style={[typography.h3, { color: colors.textStrong }]}>
            {store.name}
          </Text>
          <Text style={[typography.caption, { color: colors.textMuted }]}>
            {Number(store.productCount) || 0} สินค้า
            {ratingCount > 0 ? ` · ⭐ ${rating.toFixed(1)} (${ratingCount})` : ''}
          </Text>
          <View style={styles.pills}>
            {store.isOfficial && <Pill label="ยืนยันแล้ว" tone="success" icon="✔" />}
            {store.rider_delivery && <Pill label="ไรเดอร์ส่ง" tone="gold" icon="🛵" />}
          </View>
        </View>
        <Text style={[typography.h2, { color: colors.textFaint }]}>›</Text>
      </View>
    </Card3D>
  );
};

export default function StoresScreen() {
  const { colors } = useTheme();
  const params = useLocalSearchParams<{ type?: string }>();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [tab, setTab] = useState<StoreTab>(params.type === 'official' ? 'official' : 'featured');
  const [stores, setStores] = useState<StoreListItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(
    async (mode: 'initial' | 'refresh') => {
      if (!isAuthenticated) {
        setLoading(false);
        return;
      }
      const requestId = ++requestIdRef.current;
      if (mode === 'initial') setLoading(true);
      else setRefreshing(true);

      const result = tab === 'official' ? await getOfficialStores() : await getFeaturedStores();
      if (!mountedRef.current || requestId !== requestIdRef.current) return;

      if (result.success) {
        setStores(Array.isArray(result.data) ? result.data : []);
        setError(null);
      } else {
        setError(result.message);
      }
      setLoading(false);
      setRefreshing(false);
    },
    [isAuthenticated, tab]
  );

  useEffect(() => {
    setStores([]);
    load('initial');
  }, [load]);

  const header = (
    <View style={styles.tabs}>
      <Chip label="ร้านแนะนำ" icon="⭐" selected={tab === 'featured'} onPress={() => setTab('featured')} />
      <Chip label="ร้านยืนยันแล้ว" icon="✔" selected={tab === 'official'} onPress={() => setTab('official')} />
    </View>
  );

  const renderBody = () => {
    if (!isAuthenticated) {
      return (
        <EmptyState
          icon="🔐"
          title="เข้าสู่ระบบก่อนนะ"
          message="เข้าสู่ระบบเพื่อดูร้านค้าและสั่งซื้อ"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      );
    }

    return (
      <FlatList
        data={stores}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => <StoreRow store={item} />}
        ListHeaderComponent={header}
        ListEmptyComponent={
          loading ? (
            <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
          ) : error ? (
            <EmptyState compact variant="error" message={error} onAction={() => load('initial')} />
          ) : (
            <EmptyState
              compact
              icon="🏪"
              title="ยังไม่มีร้านในหมวดนี้"
              message="ลองดูสินค้าทั้งหมดในหน้าช้อปก่อนนะ"
              actionLabel="ไปหน้าช้อป"
              onAction={() => router.push('/(tabs)/shop' as never)}
            />
          )
        }
        contentContainerStyle={styles.list}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => load('refresh')}
            tintColor={colors.gold}
            colors={[colors.gold]}
            progressBackgroundColor={colors.card}
          />
        }
        showsVerticalScrollIndicator={false}
      />
    );
  };

  return (
    <Screen title="ร้านค้า" subtitle="เลือกช้อปจากร้านที่ใช่" scroll={false} right={isAuthenticated ? <CartButton /> : undefined}>
      {renderBody()}
    </Screen>
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
  list: {
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.xxxl,
  },
  tabs: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  card: {
    marginBottom: spacing.md,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  logo: {
    width: 56,
    height: 56,
    borderRadius: 16,
  },
  logoIcon: {
    fontSize: 26,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.xs,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
});
