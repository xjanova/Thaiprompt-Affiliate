/**
 * ที่อยู่จัดส่งของฉัน — GET/DELETE /addresses, POST /addresses/{id}/default (SHOP-08)
 *
 * หน้าตา (ธีมรอยัล): การ์ดขาวต่อที่อยู่ (ที่อยู่หลัก = ขอบทอง) · กล่องไอคอนหมุด · ป้ายสถานะปักหมุด
 *   · แถวปุ่มแก้ไข/ตั้งเป็นหลัก/ลบ คั่นเส้นด้านล่าง · ปุ่มทอง "เพิ่มที่อยู่ใหม่"
 * - เพิ่ม/แก้ไขที่หน้า /addresses/edit (ปักหมุดด้วย GPS ได้)
 * - ลบต้องยืนยันก่อน · ลบที่อยู่หลัก → server ตั้งที่อยู่อื่นเป็นหลักให้เอง
 * - ส่งด้วยไรเดอร์ได้เฉพาะที่อยู่ที่ปักหมุดแล้ว (has_location)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { deleteAddress, getAddresses, setDefaultAddress, type Address } from '@/services/api/shopApi';
import { Button3D, Card3D, EmptyState, Pill, Screen, resultHaptic } from '@/components/ui';
import { IconTile, MetaItem } from '@/components/shop';
import { useTheme, spacing, typography } from '@/theme';

const MAX_ADDRESSES = 20;

export default function AddressesScreen() {
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [addresses, setAddresses] = useState<Address[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);
  const mountedRef = useRef(true);
  const loadedRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(
    async (mode: 'initial' | 'refresh' | 'silent') => {
      if (!isAuthenticated) {
        setLoading(false);
        return;
      }
      if (mode === 'initial') setLoading(true);
      if (mode === 'refresh') setRefreshing(true);
      const result = await getAddresses();
      if (!mountedRef.current) return;
      if (result.success) {
        setAddresses(Array.isArray(result.data) ? result.data : []);
        setError(null);
        loadedRef.current = true;
      } else if (mode !== 'silent' || !loadedRef.current) {
        setError(result.message);
      }
      setLoading(false);
      setRefreshing(false);
    },
    [isAuthenticated]
  );

  // กลับมาจากหน้าแก้ไข → โหลดใหม่เงียบๆ
  useFocusEffect(
    useCallback(() => {
      load(loadedRef.current ? 'silent' : 'initial');
    }, [load])
  );

  const makeDefault = async (address: Address) => {
    if (busyId !== null) return;
    setBusyId(address.id);
    const result = await setDefaultAddress(address.id);
    if (!mountedRef.current) return;
    setBusyId(null);
    if (result.success) {
      resultHaptic('success');
      load('silent');
    } else {
      Alert.alert('ตั้งเป็นที่อยู่หลักไม่สำเร็จ', result.message);
    }
  };

  const remove = (address: Address) => {
    if (busyId !== null) return;
    Alert.alert('ลบที่อยู่นี้?', `${address.recipient_name}\n${address.full_address}`, [
      { text: 'ไม่ลบ', style: 'cancel' },
      {
        text: 'ลบที่อยู่',
        style: 'destructive',
        onPress: async () => {
          setBusyId(address.id);
          const result = await deleteAddress(address.id);
          if (!mountedRef.current) return;
          setBusyId(null);
          if (result.success) {
            setAddresses((prev) => prev.filter((a) => a.id !== address.id));
            load('silent');
          } else {
            Alert.alert('ลบไม่สำเร็จ', result.message);
          }
        },
      },
    ]);
  };

  const addNew = () => {
    if (addresses.length >= MAX_ADDRESSES) {
      Alert.alert('ที่อยู่เต็มแล้ว', `บันทึกได้สูงสุด ${MAX_ADDRESSES} ที่อยู่ ลบที่อยู่ที่ไม่ใช้ก่อนนะ`);
      return;
    }
    router.push('/addresses/edit' as never);
  };

  if (!isAuthenticated) {
    return (
      <Screen title="ที่อยู่จัดส่ง" scroll={false}>
        <EmptyState
          icon="lock-key"
          title="เข้าสู่ระบบก่อนนะ"
          message="เข้าสู่ระบบเพื่อจัดการที่อยู่จัดส่ง"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      </Screen>
    );
  }

  return (
    <Screen
      title="ที่อยู่จัดส่ง"
      subtitle={addresses.length > 0 ? `${addresses.length} ที่อยู่` : undefined}
      refreshing={refreshing}
      onRefresh={() => load('refresh')}
    >
      {loading && addresses.length === 0 ? (
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      ) : error && addresses.length === 0 ? (
        <EmptyState compact variant="error" message={error} onAction={() => load('initial')} />
      ) : addresses.length === 0 ? (
        <EmptyState
          compact
          art="scooter"
          title="ยังไม่มีที่อยู่จัดส่ง"
          message="เพิ่มที่อยู่ไว้ครั้งเดียว สั่งของครั้งต่อไปเร็วขึ้น"
          actionLabel="เพิ่มที่อยู่"
          onAction={addNew}
        />
      ) : (
        <>
          {addresses.map((address) => (
            <Card3D
              key={address.id}
              padding={spacing.lg}
              radius={20}
              gradientBorder={address.is_default}
              style={styles.card}
              accessibilityLabel={`ที่อยู่ ${address.recipient_name}`}
            >
              <View style={styles.headRow}>
                <IconTile icon={address.has_location ? 'map-pin' : 'house'} tone={address.is_default ? 'gold' : 'navy'} weight="fill" />
                <View style={styles.flex}>
                  <View style={styles.nameRow}>
                    <Text numberOfLines={1} style={[typography.h3, styles.name, { color: colors.textStrong }]}>
                      {address.recipient_name}
                    </Text>
                    {address.is_default && <Pill label="ที่อยู่หลัก" tone="gold" icon="star" />}
                  </View>
                  <MetaItem icon="phone" text={address.phone_number} style={styles.phone} />
                </View>
              </View>
              <Text style={[typography.body, styles.address, { color: colors.text }]}>{address.full_address}</Text>
              {!!address.notes && (
                <View style={[styles.noteBox, { backgroundColor: colors.inset, borderLeftColor: colors.gold }]}>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>หมายเหตุ: {address.notes}</Text>
                </View>
              )}
              <View style={styles.pills}>
                {address.has_location ? (
                  <Pill label="ปักหมุดแล้ว ส่งด้วยไรเดอร์ได้" tone="success" icon="map-pin" />
                ) : (
                  <Pill label="ยังไม่ปักหมุด" tone="warning" icon="map-pin" />
                )}
              </View>
              <View style={[styles.actions, { borderTopColor: colors.divider }]}>
                <Button3D
                  title="แก้ไข"
                  icon="pencil-simple"
                  variant="secondary"
                  size="sm"
                  disabled={busyId !== null}
                  onPress={() => router.push(`/addresses/edit?id=${address.id}` as never)}
                  style={styles.flex}
                />
                {!address.is_default && (
                  <Button3D
                    title="ตั้งเป็นหลัก"
                    variant="secondary"
                    size="sm"
                    loading={busyId === address.id}
                    disabled={busyId !== null && busyId !== address.id}
                    onPress={() => makeDefault(address)}
                    style={styles.flex}
                  />
                )}
                <Button3D
                  title="ลบ"
                  variant="ghost"
                  size="sm"
                  disabled={busyId !== null}
                  onPress={() => remove(address)}
                  accessibilityLabel={`ลบที่อยู่ของ ${address.recipient_name}`}
                />
              </View>
            </Card3D>
          ))}
          <Button3D title="เพิ่มที่อยู่ใหม่" icon="plus" size="lg" fullWidth onPress={addNew} style={styles.addButton} />
        </>
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  card: {
    marginBottom: spacing.md,
  },
  headRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  nameRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  name: {
    flexShrink: 1,
  },
  phone: {
    marginTop: 2,
  },
  address: {
    marginTop: spacing.md,
  },
  noteBox: {
    marginTop: spacing.sm,
    borderRadius: 12,
    borderLeftWidth: 3,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.md,
  },
  actions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
  addButton: {
    marginTop: spacing.sm,
  },
});
