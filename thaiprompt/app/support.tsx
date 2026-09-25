/**
 * ช่วยเหลือ / ติดต่อทีมงาน — ระบบ Ticket (ธีมนวลทองคำ)
 *
 * - รายการเรื่องที่แจ้ง + สถานะ + ป้าย "มีข้อความใหม่จากทีมงาน"
 * - แจ้งเรื่องใหม่ในแผ่นล่าง (หัวข้อ · หมวด · รายละเอียด) · ตรวจช่องว่างก่อนส่ง
 * - เปิดเรื่อง = ดูบทสนทนา + ตอบกลับ (กันกดส่งซ้ำ) · เรื่องที่แก้ไขแล้ว = ให้คะแนนได้
 * - ข้อความผิดพลาดเป็นภาษาไทยเสมอ (ข้อความจาก server ที่ไม่ใช่ภาษาไทยจะไม่แสดง)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  KeyboardAvoidingView,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import Animated, { FadeInDown } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import {
  createTicket,
  getTicket,
  getTickets,
  rateTicket,
  replyTicket,
  type Ticket,
  type TicketMessage,
} from '@/services/api';
import { isThaiText } from '@/services/api/client';
import { Button3D, Card3D, Chip, EmptyState, Pill, Screen, resultHaptic } from '@/components/ui';
import { FormSheet, Field } from '@/components/shop';
import { useTheme, radii, spacing, typography, type Tone } from '@/theme';

const CATEGORIES = [
  { value: 'general', label: 'ทั่วไป', icon: '💬' },
  { value: 'account', label: 'บัญชี', icon: '👤' },
  { value: 'payment', label: 'การชำระเงิน', icon: '💳' },
  { value: 'order', label: 'คำสั่งซื้อ', icon: '🧾' },
  { value: 'rider', label: 'ไรเดอร์', icon: '🛵' },
  { value: 'technical', label: 'แอปใช้งานไม่ได้', icon: '🐛' },
  { value: 'complaint', label: 'ร้องเรียน', icon: '⚠️' },
  { value: 'suggestion', label: 'ข้อเสนอแนะ', icon: '💡' },
];

const STATUS_TONE: Record<string, Tone> = {
  open: 'info',
  in_progress: 'warning',
  waiting: 'gold',
  resolved: 'success',
  closed: 'neutral',
};

const thaiOr = (message: unknown, fallback: string): string => (isThaiText(message) ? (message as string) : fallback);

// =====================================================
// รายละเอียดเรื่อง (บทสนทนา)
// =====================================================

interface TicketDetail {
  ticket: {
    id: number;
    ticketNumber: string;
    subject: string;
    categoryText: string;
    status: string;
    statusText: string;
    satisfactionRating?: number;
  };
  messages: TicketMessage[];
}

const TicketDetailModal: React.FC<{
  ticketId: number | null;
  onClose: () => void;
  onChanged: () => void;
}> = ({ ticketId, onClose, onChanged }) => {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  const [detail, setDetail] = useState<TicketDetail | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [reply, setReply] = useState('');
  const [sending, setSending] = useState(false);
  const [rating, setRating] = useState(0);
  const mountedRef = useRef(true);
  const sendingRef = useRef(false);
  const listRef = useRef<FlatList<TicketMessage>>(null);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(async (id: number, silent = false) => {
    if (!silent) setLoading(true);
    const response = await getTicket(id);
    if (!mountedRef.current) return;
    setLoading(false);
    if (response?.success && response.data) {
      setDetail({ ticket: response.data.ticket, messages: response.data.messages || [] });
      setError(null);
    } else if (!silent) {
      setError(thaiOr(response?.message, 'เปิดเรื่องนี้ไม่สำเร็จ ลองใหม่อีกครั้งนะ'));
    }
  }, []);

  useEffect(() => {
    if (ticketId) {
      setDetail(null);
      setReply('');
      setRating(0);
      load(ticketId);
    }
  }, [ticketId, load]);

  const send = async () => {
    const text = reply.trim();
    if (!ticketId || !text || sendingRef.current) return;
    sendingRef.current = true;
    setSending(true);
    const response = await replyTicket(ticketId, text);
    sendingRef.current = false;
    if (!mountedRef.current) return;
    setSending(false);
    if (response.success) {
      resultHaptic('success');
      setReply('');
      await load(ticketId, true);
      onChanged();
      setTimeout(() => listRef.current?.scrollToEnd({ animated: true }), 150);
    } else {
      resultHaptic('error');
      Alert.alert('ส่งข้อความไม่สำเร็จ', thaiOr(response.message, 'ลองใหม่อีกครั้งนะ'));
    }
  };

  const submitRating = async () => {
    if (!ticketId || rating < 1) return;
    const response = await rateTicket(ticketId, rating);
    if (!mountedRef.current) return;
    if (response.success) {
      resultHaptic('success');
      await load(ticketId, true);
      onChanged();
    } else {
      Alert.alert('ให้คะแนนไม่สำเร็จ', thaiOr(response.message, 'ลองใหม่อีกครั้งนะ'));
    }
  };

  const t = detail?.ticket;
  const closed = t?.status === 'closed';
  const canRate = !!t && (t.status === 'resolved' || t.status === 'closed') && !t.satisfactionRating;

  return (
    <Modal visible={ticketId !== null} animationType="slide" onRequestClose={onClose} statusBarTranslucent>
      <KeyboardAvoidingView style={[styles.flex, { backgroundColor: colors.background }]} behavior="padding">
        <View style={[styles.modalHeader, { paddingTop: insets.top + spacing.sm, borderBottomColor: colors.divider }]}>
          <View style={styles.flex}>
            <Text style={[typography.h2, { color: colors.textStrong }]} numberOfLines={1}>
              {t ? `#${t.ticketNumber}` : 'กำลังเปิด...'}
            </Text>
            {!!t && (
              <Text style={[typography.bodySm, { color: colors.textMuted }]} numberOfLines={1}>
                {t.subject}
              </Text>
            )}
          </View>
          <Button3D title="ปิด" size="sm" variant="secondary" onPress={onClose} />
        </View>

        {loading && !detail ? (
          <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
        ) : error && !detail ? (
          <EmptyState compact variant="error" message={error} onAction={() => ticketId && load(ticketId)} />
        ) : (
          <>
            {!!t && (
              <View style={styles.metaRow}>
                <Pill label={t.statusText} tone={STATUS_TONE[t.status] || 'neutral'} size="md" />
                {!!t.categoryText && <Pill label={t.categoryText} tone="neutral" />}
              </View>
            )}
            <FlatList
              ref={listRef}
              data={detail?.messages || []}
              keyExtractor={(item) => String(item.id)}
              contentContainerStyle={styles.messages}
              onContentSizeChange={() => listRef.current?.scrollToEnd({ animated: false })}
              renderItem={({ item }) => {
                const mine = !item.isFromAdmin;
                return (
                  <View
                    style={[
                      styles.bubble,
                      mine
                        ? [styles.bubbleMine, { backgroundColor: colors.goldSoft, borderColor: colors.border }]
                        : [styles.bubbleTeam, { backgroundColor: colors.card, borderColor: colors.border }],
                    ]}
                  >
                    <Text style={[typography.micro, { color: mine ? colors.goldDeep : colors.info }]}>
                      {mine ? 'คุณ' : '🛡️ ทีมงาน'}
                    </Text>
                    <Text style={[typography.body, { color: colors.textStrong }]}>{item.message}</Text>
                    <Text style={[typography.micro, styles.bubbleTime, { color: colors.textFaint }]}>{item.createdAt}</Text>
                  </View>
                );
              }}
              ListFooterComponent={
                canRate ? (
                  <Card3D padding={spacing.lg} style={styles.rateCard} contentStyle={styles.rateContent}>
                    <Text style={[typography.h3, { color: colors.textStrong }]}>ทีมงานช่วยได้ดีแค่ไหน?</Text>
                    <View style={styles.stars}>
                      {[1, 2, 3, 4, 5].map((n) => (
                        <Pressable
                          key={n}
                          onPress={() => setRating(n)}
                          hitSlop={6}
                          accessibilityRole="button"
                          accessibilityLabel={`ให้ ${n} ดาว`}
                          accessibilityState={{ selected: rating >= n }}
                        >
                          <Text style={[styles.star, { opacity: rating >= n ? 1 : 0.3 }]}>⭐</Text>
                        </Pressable>
                      ))}
                    </View>
                    <Button3D title="ส่งคะแนน" size="sm" disabled={rating < 1} onPress={submitRating} />
                  </Card3D>
                ) : null
              }
            />
            {!closed && (
              <View
                style={[
                  styles.replyBar,
                  { borderTopColor: colors.divider, backgroundColor: colors.surface, paddingBottom: Math.max(insets.bottom, spacing.sm) },
                ]}
              >
                <TextInput
                  value={reply}
                  onChangeText={setReply}
                  placeholder="พิมพ์ข้อความถึงทีมงาน..."
                  placeholderTextColor={colors.textFaint}
                  multiline
                  maxLength={2000}
                  accessibilityLabel="ข้อความตอบกลับ"
                  style={[typography.body, styles.replyInput, { backgroundColor: colors.inset, color: colors.textStrong, borderColor: colors.border }]}
                />
                <Button3D title="ส่ง" icon="📤" size="md" disabled={!reply.trim()} loading={sending} onPress={send} />
              </View>
            )}
          </>
        )}
      </KeyboardAvoidingView>
    </Modal>
  );
};

// =====================================================
// หน้าหลัก
// =====================================================

export default function SupportScreen() {
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [tickets, setTickets] = useState<Ticket[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [selectedId, setSelectedId] = useState<number | null>(null);

  const [createOpen, setCreateOpen] = useState(false);
  const [subject, setSubject] = useState('');
  const [category, setCategory] = useState('general');
  const [message, setMessage] = useState('');
  const [formError, setFormError] = useState<string | null>(null);
  const [creating, setCreating] = useState(false);

  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);
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
      const requestId = ++requestIdRef.current;
      if (mode === 'refresh') setRefreshing(true);
      const response = await getTickets();
      if (!mountedRef.current || requestId !== requestIdRef.current) return;
      if (response?.success && response.data) {
        setTickets(Array.isArray(response.data.tickets) ? response.data.tickets : []);
        setError(null);
      } else if (mode !== 'silent') {
        setError('โหลดรายการไม่สำเร็จ ตรวจสอบอินเทอร์เน็ตแล้วลองใหม่นะ');
      }
      setLoading(false);
      setRefreshing(false);
    },
    [isAuthenticated]
  );

  useFocusEffect(
    useCallback(() => {
      load(loadedRef.current ? 'silent' : 'initial');
      loadedRef.current = true;
    }, [load])
  );

  const openCreate = () => {
    setFormError(null);
    setCreateOpen(true);
  };

  const submitCreate = async () => {
    if (creating) return;
    if (subject.trim().length < 3) {
      setFormError('ใส่หัวข้อสั้นๆ อย่างน้อย 3 ตัวอักษร');
      return;
    }
    if (message.trim().length < 5) {
      setFormError('เล่ารายละเอียดเพิ่มอีกนิด ทีมงานจะช่วยได้เร็วขึ้น');
      return;
    }
    setCreating(true);
    setFormError(null);
    const response = await createTicket({ subject: subject.trim(), category, message: message.trim() });
    if (!mountedRef.current) return;
    setCreating(false);
    if (response.success) {
      resultHaptic('success');
      setCreateOpen(false);
      setSubject('');
      setMessage('');
      setCategory('general');
      Alert.alert('ส่งเรื่องแล้ว', `เลขที่เรื่อง #${response.data?.ticketNumber || '-'} ทีมงานจะตอบกลับโดยเร็ว`);
      load('silent');
    } else {
      resultHaptic('error');
      setFormError(thaiOr(response.message, 'ส่งเรื่องไม่สำเร็จ ลองใหม่อีกครั้งนะ'));
    }
  };

  if (!isAuthenticated) {
    return (
      <Screen title="ช่วยเหลือ" scroll={false}>
        <EmptyState
          icon="💬"
          title="เข้าสู่ระบบก่อนนะ"
          message="เข้าสู่ระบบเพื่อแจ้งปัญหาหรือสอบถามทีมงาน"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      </Screen>
    );
  }

  const renderTicket = ({ item, index }: { item: Ticket; index: number }) => (
    <Animated.View entering={index < 10 ? FadeInDown.delay(index * 30).duration(220) : undefined}>
      <Card3D
        onPress={() => setSelectedId(item.id)}
        padding={spacing.md}
        radius={radii.lg}
        shadow="sm"
        gradientBorder={item.hasUnreadAdminMessage}
        style={styles.card}
        accessibilityLabel={`เรื่อง ${item.subject} สถานะ ${item.statusText}${item.hasUnreadAdminMessage ? ' มีข้อความใหม่จากทีมงาน' : ''}`}
      >
        <View style={styles.rowBetween}>
          <Text style={[typography.caption, { color: colors.goldDeep }]}>#{item.ticketNumber}</Text>
          <Pill label={item.statusText} tone={STATUS_TONE[item.status] || 'neutral'} />
        </View>
        <Text style={[typography.bodyStrong, styles.subject, { color: colors.textStrong }]} numberOfLines={2}>
          {item.subject}
        </Text>
        <View style={styles.rowBetween}>
          <Text style={[typography.caption, { color: colors.textMuted }]}>
            {item.categoryText} · {item.messageCount.toLocaleString('th-TH')} ข้อความ
          </Text>
          {item.hasUnreadAdminMessage && <Pill label="ข้อความใหม่" tone="danger" icon="🔴" />}
        </View>
      </Card3D>
    </Animated.View>
  );

  return (
    <Screen
      title="ช่วยเหลือ"
      subtitle="แจ้งปัญหาหรือสอบถามทีมงาน"
      scroll={false}
      right={<Button3D title="แจ้งเรื่อง" icon="➕" size="sm" onPress={openCreate} />}
    >
      <FlatList
        data={tickets}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderTicket}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        ListHeaderComponent={
          <Card3D variant="flat" padding={spacing.md} style={styles.intro}>
            <Text style={[typography.bodySm, { color: colors.textMuted }]}>
              🕘 ทีมงานตอบกลับในเวลาทำการ เรื่องด่วนเกี่ยวกับออเดอร์หรือการเงินจะได้รับการดูแลก่อน
            </Text>
          </Card3D>
        }
        ListEmptyComponent={
          loading ? (
            <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
          ) : error ? (
            <EmptyState compact variant="error" message={error} onAction={() => load('initial')} />
          ) : (
            <EmptyState
              compact
              icon="💬"
              title="ยังไม่เคยแจ้งเรื่อง"
              message="มีปัญหาหรืออยากถามอะไร แจ้งทีมงานได้เลย"
              actionLabel="แจ้งเรื่องใหม่"
              onAction={openCreate}
            />
          )
        }
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => load('refresh')}
            tintColor={colors.gold}
            colors={[colors.gold]}
            progressBackgroundColor={colors.card}
          />
        }
      />

      <FormSheet
        visible={createOpen}
        icon="💬"
        title="แจ้งเรื่องใหม่"
        description="เล่าให้ละเอียดหน่อย เช่น เลขออเดอร์ เวลาที่เกิดปัญหา"
        submitLabel="ส่งเรื่อง"
        onSubmit={submitCreate}
        busy={creating}
        cancelLabel="ยกเลิก"
        onClose={() => setCreateOpen(false)}
      >
        <Field
          label="หัวข้อ"
          required
          value={subject}
          onChangeText={(v) => {
            setSubject(v);
            setFormError(null);
          }}
          placeholder="เช่น ถอนเงินแล้วยังไม่เข้าบัญชี"
          maxLength={150}
        />
        <Text style={[typography.caption, styles.catLabel, { color: colors.textMuted }]}>หมวด</Text>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.cats}>
          {CATEGORIES.map((c) => (
            <Chip key={c.value} label={c.label} icon={c.icon} size="sm" selected={category === c.value} onPress={() => setCategory(c.value)} />
          ))}
        </ScrollView>
        <Field
          label="รายละเอียด"
          required
          value={message}
          onChangeText={(v) => {
            setMessage(v);
            setFormError(null);
          }}
          placeholder="อธิบายปัญหาหรือคำถามของคุณ"
          multiline
          maxLength={2000}
          error={formError}
        />
      </FormSheet>

      <TicketDetailModal ticketId={selectedId} onClose={() => setSelectedId(null)} onChanged={() => load('silent')} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  list: {
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.xxxl * 2,
  },
  intro: {
    marginBottom: spacing.md,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  card: {
    marginBottom: spacing.sm,
  },
  rowBetween: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  subject: {
    marginVertical: spacing.xs,
  },
  catLabel: {
    marginTop: spacing.md,
    marginBottom: spacing.xs,
  },
  cats: {
    gap: spacing.xs,
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.md,
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
  metaRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
  },
  messages: {
    padding: spacing.screen,
    gap: spacing.sm,
  },
  bubble: {
    maxWidth: '86%',
    borderRadius: radii.lg,
    borderWidth: 1,
    padding: spacing.md,
  },
  bubbleMine: {
    alignSelf: 'flex-end',
    borderBottomRightRadius: radii.xs,
  },
  bubbleTeam: {
    alignSelf: 'flex-start',
    borderBottomLeftRadius: radii.xs,
  },
  bubbleTime: {
    marginTop: spacing.xs,
  },
  rateCard: {
    marginTop: spacing.md,
  },
  rateContent: {
    alignItems: 'center',
    gap: spacing.sm,
  },
  stars: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  star: {
    fontSize: 30,
  },
  replyBar: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: spacing.sm,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.sm,
    borderTopWidth: StyleSheet.hairlineWidth,
  },
  replyInput: {
    flex: 1,
    minHeight: 48,
    maxHeight: 120,
    borderRadius: radii.md,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
});
