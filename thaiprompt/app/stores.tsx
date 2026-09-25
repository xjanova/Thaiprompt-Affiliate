/**
 * รายชื่อร้านค้า — /stores?type=featured|official (ธีมรอยัล)
 *
 * หน้าตา: ชิปสลับ "ร้านแนะนำ | ร้านยืนยันแล้ว" · การ์ดขาวต่อร้าน (โลโก้มุมมน ชื่อ ดาว ป้ายสถานะ ลูกศร)
 * - featured = ร้านแนะนำที่แอดมินเลือก · official = ร้านที่ยืนยันตัวตนแล้ว
 * - ตัวเลขจริงจาก server (จำนวนสินค้า/เรตติ้ง) ไม่มีค่าสมมติ (SHOP-17)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { router, useLocalSearchParams } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { getFeaturedStores, getOfficialStores, type StoreListItem } from '@/services/api/shopApi';
import { Card3D, Chip, EmptyState, Icon, Pill, Screen } from '@/components/ui';
import { CartButton, StoreLogo } from '@/components/shop';
import { useTheme, spacing, typography } from '@/theme';

type StoreTab = 'featured' | 'official';

const StoreRow: React.FC<{ store: StoreListItem }> = ({ store }) => {
  const { colors } = useTheme();
  const rating = Number(store.rating) || 0;
  const ratingCount = Number(store.rating_count) || 0;

  return (
    <Card3D
      onPress={() => router.push(`/store/${store.id}` as never)}
      padding={spacing.md}
      radius={20}
      shadow="sm"
      style={styles.card}
      accessibilityLabel={`ร้าน ${store.name}`}
    >
      <View style={styles.row}>
        <StoreLogo uri={store.logo} size={60} radius={18} />
        <View style={styles.flex}>
          <View style={styles.nameRow}>
            <Text numberOfLines={1} style={[typography.serifSm, styles.name, { color: colors.textStrong }]}>
              {store.name}
            </Text>
            {store.isOfficial && <Icon name="seal-check" size={17} color={colors.goldDeep} weight="fill" />}
          </View>
          <View style={styles.metaRow}>
            <Text style={[typography.caption, { color: colors.textMuted }]}>{Number(store.productCount) || 0} สินค้า</Text>
            {ratingCount > 0 && (
              <>
                <Text style={[typography.caption, { color: colors.textFaint }]}>·</Text>
                <Icon name="star" size={13} color={colors.gold} weight="fill" />
                <Text style={[typography.caption, styles.rating, { color: colors.text }]}>{rating.toFixed(1)}</Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>({ratingCount})</Text>
              </>
            )}
          </View>
          {(store.isOfficial || store.rider_delivery) && (
            <View style={styles.pills}>
              {store.isOfficial && <Pill label="ยืนยันแล้ว" tone="success" icon="seal-check" />}
              {store.rider_delivery && <Pill label="ไรเดอร์ส่ง" tone="gold" icon="moped" />}
            </View>
          )}
        </View>
        <View style={[styles.chevron, { backgroundColor: colors.navySoft }]}>
          <Icon name="caret-right" size={16} color={colors.textMuted} weight="bold" />
        </View>
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
      <Chip label="ร้านแนะนำ" icon="star" selected={tab === 'featured'} onPress={() => setTab('featured')} />
      <Chip label="ร้านยืนยันแล้ว" icon="seal-check" selected={tab === 'official'} onPress={() => setTab('official')} />
    </View>
  );

  const renderBody = () => {
    if (!isAuthenticated) {
      return (
        <EmptyState
          icon="lock-key"
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
              art="store"
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
  list: {
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
    paddingBottom: spacing.xxxl,
  },
  tabs: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.lg,
  },
  card: {
    marginBottom: spacing.md,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  nameRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
  },
  name: {
    flexShrink: 1,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 1,
  },
  rating: {
    fontWeight: '700',
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.sm,
  },
  chevron: {
    width: 30,
    height: 30,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  loader: {
    marginTop: spacing.xxxl,
  },
});
