/**
 * แชทออเดอร์ร้านค้า — ใช้ร่วมกันทั้งฝั่งผู้ซื้อ (/order/[id]?tab=chat) และฝั่งร้าน (/merchant/order/[id]?tab=chat)
 *
 * สัญญา API (เหมือนกันทั้งสองฝั่ง):
 *   - fetchPage(page) → ข้อความหน้า page เรียงใหม่ → เก่า (หน้า 1 = ล่าสุด) — รายการในจอกลับหัว (inverted)
 *   - send(text, clientId) → ข้อความที่สร้างแล้ว · clientId ใช้กันส่งซ้ำ (server ทั้งสองฝั่งคืนข้อความเดิมเมื่อรหัสซ้ำ)
 *
 * พฤติกรรม:
 *   - ส่งแบบทันใจ: ฟองขึ้นทันที (กำลังส่ง…) → สำเร็จแทนที่ด้วยข้อความจริง · ไม่สำเร็จ = ฟองขอบแดง แตะเพื่อส่งใหม่/ลบ
 *   - กันกดส่งรัว: อ่าน/ล้างข้อความผ่าน ref ทันทีในจังหวะเดียวกับที่กด
 *   - รีเฟรชทุก CHAT_POLL_MS ขณะหน้านี้อยู่บนจอและแอปอยู่เบื้องหน้า · กลับเข้าแอป = โหลดใหม่ทันที
 *   - เลื่อนขึ้นสุด = โหลดข้อความเก่ากว่า
 *   - ทุก setState หลัง await เช็ค mountedRef ก่อน
 *
 * หน้าตา (ธีมรอยัล): ฟองของฉัน = พื้น navyFill ตัวขาว (เข้มทั้งสองโหมด) · อีกฝั่ง = การ์ดขาว/การ์ดมืด ขอบบาง
 *   · ข้อความระบบ = ฟ้าอ่อนกลางจอ · ป้ายวันคั่นเมื่อข้ามวัน · ช่องพิมพ์ลอยท้ายจอ + ปุ่มส่งทอง
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  AppState,
  FlatList,
  Keyboard,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  View,
  type TextInput as RNTextInput,
} from 'react-native';
import { Image } from 'expo-image';
import { useFocusEffect } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text, TextInput } from '@/components/ui/Text';
import { Button3D, Chip, EmptyState, Icon, resultHaptic } from '@/components/ui';
import type { ApiResult } from '@/services/api/client';
import { isTrustedWebUrl } from '@/utils/linking';
import { IconTile } from './ShopKit';
import { formatThaiDateTime, openHttpsLink } from './helpers';
import { useTheme, radii, spacing, typography, withAlpha } from '@/theme';

/** ข้อความแชท (ตรงกับ ShopOrderMessage ฝั่งผู้ซื้อ + is_read ฝั่งร้าน) */
export interface OrderChatMessage {
  id: number;
  sender_type: string;
  sender_name: string | null;
  message: string | null;
  attachment: string | null;
  attachment_type: string | null;
  is_system_message: boolean;
  is_mine: boolean;
  /** ฟองของฉัน: อีกฝั่งอ่านแล้วหรือยัง (API ฝั่งร้านส่งมา ฝั่งผู้ซื้อไม่มี) */
  is_read?: boolean;
  created_at: string;
}

export interface OrderChatPage {
  messages: OrderChatMessage[];
  pagination?: { current_page: number; last_page: number } | null;
}

export interface OrderChatPanelProps {
  /** โหลดข้อความหน้า page (1 = ล่าสุด) */
  fetchPage: (page: number) => Promise<ApiResult<OrderChatPage>>;
  /** ส่งข้อความ (text ตัดช่องว่างหัวท้ายแล้ว ≤ 2000 ตัวอักษร) */
  send: (text: string, clientId: string) => Promise<ApiResult<OrderChatMessage>>;
  /** ส่งได้หรือไม่ (ออเดอร์ยกเลิก/คืนเงิน = ปิดแชท) */
  canSend: boolean;
  placeholder: string;
  inputLabel: string;
  emptyText: string;
  /** ข้อความตอบเร็ว (แสดงเมื่อช่องพิมพ์ว่าง · แตะ = ใส่ลงช่องพิมพ์ให้แก้ก่อนส่ง ไม่ส่งทันที) */
  quickReplies?: string[];
  /** หลังโหลดสำเร็จ (ข้อความอีกฝั่งถูกนับว่าอ่านแล้ว) — ใช้ล้างป้ายยังไม่อ่านของหน้าแม่ */
  onRead?: () => void;
}

const CHAT_POLL_MS = 10000;
const MAX_LENGTH = 2000;
const COUNTER_FROM = 1800;

type PendingStatus = 'sending' | 'failed';

interface PendingMessage {
  localId: string;
  text: string;
  status: PendingStatus;
  error?: string;
  /** id ข้อความจาก server ที่มากที่สุดตอนสร้าง — ใช้จับคู่เมื่อ server มีข้อความนี้แล้ว (เน็ตหลุดหลังส่งถึง) */
  afterId: number;
  createdAt: string;
}

type Row = { kind: 'server'; msg: OrderChatMessage } | { kind: 'pending'; msg: PendingMessage };

// ---------- ตัวช่วย ----------

const newClientId = (): string => `m${Date.now().toString(36)}${Math.random().toString(36).slice(2, 10)}`;

const timeOf = (iso: string): number => {
  const t = Date.parse(iso);
  return Number.isNaN(t) ? 0 : t;
};

/** รวมข้อความ (ตัวหลังชนะ) แล้วเรียงใหม่ → เก่า */
const mergeMessages = (current: OrderChatMessage[], incoming: OrderChatMessage[]): OrderChatMessage[] => {
  const byId = new Map<number, OrderChatMessage>();
  current.forEach((m) => byId.set(m.id, m));
  incoming.forEach((m) => {
    if (m && Number.isFinite(m.id)) byId.set(m.id, m);
  });
  return Array.from(byId.values()).sort((a, b) => timeOf(b.created_at) - timeOf(a.created_at) || b.id - a.id);
};

/** ตัดความยาวแบบไม่ผ่ากลางอีโมจิ */
const clampText = (text: string): string => {
  const chars = Array.from(text);
  return chars.length > MAX_LENGTH ? chars.slice(0, MAX_LENGTH).join('') : text;
};

const dayKey = (iso: string): string => {
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? '' : `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`;
};

const dayLabel = (iso: string): string => {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  const today = new Date();
  const yesterday = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 1);
  if (dayKey(iso) === dayKey(today.toISOString())) return 'วันนี้';
  if (dayKey(iso) === dayKey(yesterday.toISOString())) return 'เมื่อวาน';
  return formatThaiDateTime(iso, false);
};

const clockOf = (iso: string): string => {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  try {
    return d.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
  } catch {
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
  }
};

const rowIso = (row: Row): string => (row.kind === 'server' ? row.msg.created_at : row.msg.createdAt);

// =====================================================
// ฟองข้อความ
// =====================================================

const Bubble: React.FC<{
  row: Row;
  showName: boolean;
  onPressFailed: (p: PendingMessage) => void;
}> = ({ row, showName, onPressFailed }) => {
  const { colors, isDark } = useTheme();
  const server = row.kind === 'server' ? row.msg : null;
  const pending = row.kind === 'pending' ? row.msg : null;
  const system = !!server?.is_system_message;
  const mine = pending ? true : !!server?.is_mine && !system;
  const failed = pending?.status === 'failed';
  const text = pending ? pending.text : server?.message || '';
  const iso = rowIso(row);

  if (system) {
    return (
      <View style={styles.systemRow}>
        <View style={[styles.systemBubble, { backgroundColor: colors.infoSoft }]}>
          <Icon name="info" size={13} color={colors.info} />
          <Text style={[typography.caption, styles.flexShrink, { color: colors.text }]}>{text}</Text>
        </View>
      </View>
    );
  }

  // ฟองของฉัน = พื้น navyFill ตัวขาว (ห้ามใช้ colors.navy เป็นพื้น — โหมดมืดเป็นฟ้าอ่อน)
  const bubbleBg = mine ? colors.navyFill : colors.card;
  const textColor = mine ? colors.textOnAccent : colors.textStrong;
  const metaColor = mine ? colors.onHeaderMuted : colors.textFaint;
  const borderColor = failed ? colors.danger : mine ? (isDark ? colors.border : 'transparent') : colors.border;

  const image =
    server && server.attachment && server.attachment_type === 'image' && isTrustedWebUrl(server.attachment) ? server.attachment : null;

  const body = (
    <View
      style={[
        styles.bubble,
        mine ? styles.bubbleMine : styles.bubbleTheirs,
        { backgroundColor: bubbleBg, borderColor, opacity: pending?.status === 'sending' ? 0.72 : 1 },
      ]}
    >
      {!mine && showName && !!server?.sender_name && (
        <Text numberOfLines={1} style={[typography.micro, { color: colors.goldDeep }]}>
          {server.sender_name}
        </Text>
      )}
      {!!image && (
        <Pressable
          onPress={() => openHttpsLink(image, 'ดูรูป')}
          accessibilityRole="imagebutton"
          accessibilityLabel="เปิดดูรูปที่แนบมา"
        >
          <Image source={{ uri: image }} style={[styles.chatImage, { backgroundColor: colors.inset }]} contentFit="cover" />
        </Pressable>
      )}
      {!image && !!server?.attachment && !text && (
        <Text style={[typography.bodySm, { color: textColor }]}>ส่งไฟล์แนบ</Text>
      )}
      {!!text && (
        <Text selectable={!pending} style={[typography.body, { color: textColor }]}>
          {text}
        </Text>
      )}
      <View style={styles.metaRow}>
        {pending?.status === 'sending' ? (
          <>
            <Icon name="clock" size={11} color={metaColor} />
            <Text style={[typography.micro, { color: metaColor }]}>กำลังส่ง…</Text>
          </>
        ) : failed ? null : (
          <>
            <Text style={[typography.micro, { color: metaColor }]}>{clockOf(iso)}</Text>
            {mine && server?.is_read === true && (
              <>
                <Icon name="check-circle" size={11} color={colors.goldLight} weight="fill" />
                <Text style={[typography.micro, { color: colors.goldLight }]}>อ่านแล้ว</Text>
              </>
            )}
            {mine && server?.is_read === false && <Icon name="check" size={11} color={metaColor} />}
          </>
        )}
      </View>
    </View>
  );

  return (
    <View style={[styles.bubbleRow, mine ? styles.bubbleRight : styles.bubbleLeft]}>
      {failed && pending ? (
        <Pressable
          onPress={() => onPressFailed(pending)}
          accessibilityRole="button"
          accessibilityLabel="ส่งข้อความไม่สำเร็จ แตะเพื่อส่งใหม่หรือลบ"
          style={styles.failedWrap}
        >
          {body}
          <View style={styles.failedHint}>
            <Icon name="warning-circle" size={13} color={colors.danger} />
            <Text style={[typography.micro, { color: colors.danger }]}>ส่งไม่สำเร็จ · แตะเพื่อส่งใหม่</Text>
          </View>
        </Pressable>
      ) : (
        body
      )}
    </View>
  );
};

// =====================================================
// แผงแชท
// =====================================================

export const OrderChatPanel: React.FC<OrderChatPanelProps> = ({
  fetchPage,
  send,
  canSend,
  placeholder,
  inputLabel,
  emptyText,
  quickReplies,
  onRead,
}) => {
  const { colors, isDark } = useTheme();
  const insets = useSafeAreaInsets();

  const [messages, setMessages] = useState<OrderChatMessage[]>([]);
  const [pending, setPending] = useState<PendingMessage[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [text, setText] = useState('');
  const [closed, setClosed] = useState(false);
  const [hasOlder, setHasOlder] = useState(false);
  const [loadingOlder, setLoadingOlder] = useState(false);
  const [keyboardOpen, setKeyboardOpen] = useState(false);

  const mountedRef = useRef(true);
  const busyRef = useRef(false);
  const olderBusyRef = useRef(false);
  const pageRef = useRef(1);
  const textRef = useRef('');
  const inputRef = useRef<RNTextInput>(null);
  const messagesRef = useRef<OrderChatMessage[]>([]);
  /** id ที่ได้จากการส่งสำเร็จแล้ว (ไม่ใช้จับคู่กับฟองที่ค้างอีก) */
  const ackedIdsRef = useRef<Set<number>>(new Set());
  const onReadRef = useRef(onRead);
  onReadRef.current = onRead;

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // คีย์บอร์ดเปิด = ไม่ต้องเผื่อขอบล่างของเครื่อง (แถบ home indicator)
  useEffect(() => {
    const show = Keyboard.addListener(Platform.OS === 'ios' ? 'keyboardWillShow' : 'keyboardDidShow', () => setKeyboardOpen(true));
    const hide = Keyboard.addListener(Platform.OS === 'ios' ? 'keyboardWillHide' : 'keyboardDidHide', () => setKeyboardOpen(false));
    return () => {
      show.remove();
      hide.remove();
    };
  }, []);

  const applyServer = useCallback((incoming: OrderChatMessage[]) => {
    const merged = mergeMessages(messagesRef.current, incoming);
    messagesRef.current = merged;
    setMessages(merged);
    // ฟองที่ค้าง (กำลังส่ง/ส่งไม่สำเร็จ) แต่ server มีข้อความนี้แล้ว → เอาฟองค้างออก
    setPending((prev) => {
      if (prev.length === 0) return prev;
      const claimed = new Set<number>(ackedIdsRef.current);
      const next = prev.filter((p) => {
        const match = merged.find(
          (m) => m.is_mine && m.id > p.afterId && !claimed.has(m.id) && (m.message || '').trim() === p.text
        );
        if (match) {
          claimed.add(match.id);
          return false;
        }
        return true;
      });
      return next.length === prev.length ? prev : next;
    });
  }, []);

  const load = useCallback(
    async (silent: boolean) => {
      if (busyRef.current) return;
      busyRef.current = true;
      if (!silent) setLoading(true);
      const res = await fetchPage(1);
      busyRef.current = false;
      if (!mountedRef.current) return;
      if (res.success) {
        const list = Array.isArray(res.data?.messages) ? res.data.messages : [];
        applyServer(list);
        const pg = res.data?.pagination;
        if (pageRef.current === 1) setHasOlder(!!pg && pg.last_page > 1);
        setError(null);
        onReadRef.current?.();
      } else if (!silent || messagesRef.current.length === 0) {
        setError(res.message);
      }
      setLoading(false);
    },
    [fetchPage, applyServer]
  );

  const loadOlder = useCallback(async () => {
    if (!hasOlder || olderBusyRef.current || loading) return;
    olderBusyRef.current = true;
    setLoadingOlder(true);
    const nextPage = pageRef.current + 1;
    const res = await fetchPage(nextPage);
    olderBusyRef.current = false;
    if (!mountedRef.current) return;
    setLoadingOlder(false);
    if (res.success) {
      const list = Array.isArray(res.data?.messages) ? res.data.messages : [];
      pageRef.current = nextPage;
      applyServer(list);
      const pg = res.data?.pagination;
      setHasOlder(!!pg && pg.last_page > nextPage && list.length > 0);
    }
  }, [hasOlder, loading, fetchPage, applyServer]);

  // โหลดเมื่อหน้าอยู่บนจอ + รีเฟรชเป็นระยะเฉพาะตอนแอปอยู่เบื้องหน้า
  useFocusEffect(
    useCallback(() => {
      load(messagesRef.current.length > 0);
      const timer = setInterval(() => {
        if (AppState.currentState === 'active') load(true);
      }, CHAT_POLL_MS);
      const sub = AppState.addEventListener('change', (state) => {
        if (state === 'active') load(true);
      });
      return () => {
        clearInterval(timer);
        sub.remove();
      };
    }, [load])
  );

  // ---------- ส่ง ----------

  const deliver = useCallback(
    async (item: PendingMessage) => {
      const res = await send(item.text, item.localId);
      if (!mountedRef.current) return;
      if (res.success && res.data && Number.isFinite(res.data.id)) {
        ackedIdsRef.current.add(res.data.id);
        const merged = mergeMessages(messagesRef.current, [res.data]);
        messagesRef.current = merged;
        setMessages(merged);
        setPending((prev) => prev.filter((p) => p.localId !== item.localId));
        return;
      }
      resultHaptic('error');
      const message = res.success ? 'ส่งข้อความไม่สำเร็จ ลองใหม่อีกครั้งนะ' : res.message;
      if (!res.success && res.code === 'CHAT_CLOSED') setClosed(true);
      setPending((prev) => prev.map((p) => (p.localId === item.localId ? { ...p, status: 'failed', error: message } : p)));
    },
    [send]
  );

  const submit = useCallback(() => {
    // อ่านจาก ref → กดส่งรัวสองครั้งในเฟรมเดียวกันได้ข้อความเดียว
    const value = clampText(textRef.current.trim());
    if (!value || !canSend || closed) return;
    textRef.current = '';
    setText('');
    const top = messagesRef.current.reduce((max, m) => (m.id > max ? m.id : max), 0);
    const item: PendingMessage = {
      localId: newClientId(),
      text: value,
      status: 'sending',
      afterId: top,
      createdAt: new Date().toISOString(),
    };
    setPending((prev) => [item, ...prev]);
    deliver(item);
  }, [canSend, closed, deliver]);

  const retry = useCallback(
    (item: PendingMessage) => {
      if (item.status !== 'failed') return;
      const again = { ...item, status: 'sending' as const, error: undefined };
      setPending((prev) => prev.map((p) => (p.localId === item.localId ? again : p)));
      deliver(again);
    },
    [deliver]
  );

  const onPressFailed = useCallback(
    (item: PendingMessage) => {
      const buttons: Parameters<typeof Alert.alert>[2] = [
        { text: 'ปิด', style: 'cancel' },
        {
          text: 'ลบข้อความนี้',
          style: 'destructive',
          onPress: () => setPending((prev) => prev.filter((p) => p.localId !== item.localId)),
        },
      ];
      if (canSend && !closed) buttons.push({ text: 'ส่งใหม่', onPress: () => retry(item) });
      Alert.alert('ส่งข้อความไม่สำเร็จ', item.error || 'ลองส่งใหม่อีกครั้งนะ', buttons);
    },
    [canSend, closed, retry]
  );

  const onChangeText = useCallback((value: string) => {
    textRef.current = value;
    setText(value);
  }, []);

  // ข้อความตอบเร็ว → ใส่ลงช่องพิมพ์ให้แก้ได้ก่อน (กันแตะพลาดแล้วส่งทันที)
  const applyQuickReply = useCallback(
    (value: string) => {
      onChangeText(value);
      inputRef.current?.focus();
    },
    [onChangeText]
  );

  // ---------- แสดงผล ----------

  const rows: Row[] = useMemo(
    () => [
      ...pending.map((msg): Row => ({ kind: 'pending', msg })),
      ...messages.map((msg): Row => ({ kind: 'server', msg })),
    ],
    [pending, messages]
  );

  const renderItem = useCallback(
    ({ item, index }: { item: Row; index: number }) => {
      const older = rows[index + 1];
      const iso = rowIso(item);
      const newDay = !older || dayKey(rowIso(older)) !== dayKey(iso);
      const senderKey = (r: Row) => (r.kind === 'pending' ? 'me' : `${r.msg.sender_type}:${r.msg.is_mine ? 'me' : r.msg.sender_name}`);
      const showName = newDay || !older || senderKey(older) !== senderKey(item);
      return (
        <View>
          {newDay && !!dayLabel(iso) && (
            <View style={styles.dayRow}>
              <View style={[styles.dayPill, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <Text style={[typography.micro, { color: colors.textMuted }]}>{dayLabel(iso)}</Text>
              </View>
            </View>
          )}
          <Bubble row={item} showName={showName} onPressFailed={onPressFailed} />
        </View>
      );
    },
    [rows, colors, onPressFailed]
  );

  if (loading && messages.length === 0 && pending.length === 0) {
    return <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />;
  }

  const sendOpen = canSend && !closed;
  const trimmed = text.trim();
  const length = Array.from(text).length;
  const showQuick = sendOpen && !trimmed && !!quickReplies?.length && !error;

  return (
    <KeyboardAvoidingView style={styles.flex} behavior="padding">
      <FlatList
        data={rows}
        inverted
        keyExtractor={(r) => (r.kind === 'server' ? `m-${r.msg.id}` : `p-${r.msg.localId}`)}
        renderItem={renderItem}
        contentContainerStyle={styles.list}
        keyboardShouldPersistTaps="handled"
        keyboardDismissMode={Platform.OS === 'ios' ? 'interactive' : 'on-drag'}
        onEndReached={loadOlder}
        onEndReachedThreshold={0.3}
        ListFooterComponent={
          // รายการกลับหัว → footer อยู่บนสุด (ข้อความเก่ากว่า)
          loadingOlder ? <ActivityIndicator size="small" color={colors.gold} style={styles.olderLoader} /> : null
        }
        ListEmptyComponent={
          // รายการกลับหัว (inverted) → กลับหัวช่องว่างอีกครั้งให้อ่านได้ปกติ
          <View style={styles.flipped}>
            {error ? (
              <EmptyState compact variant="error" message={error} onAction={() => load(false)} />
            ) : (
              <View style={styles.empty}>
                <IconTile icon="chat-circle-dots" tone="gold" size={60} weight="fill" />
                <Text style={[typography.body, styles.emptyText, { color: colors.textMuted }]}>{emptyText}</Text>
              </View>
            )}
          </View>
        }
      />

      {sendOpen ? (
        <View
          style={[
            styles.inputBar,
            {
              backgroundColor: colors.card,
              borderTopColor: colors.divider,
              paddingBottom: keyboardOpen ? spacing.sm : Math.max(insets.bottom, spacing.sm),
              boxShadow: `0px -14px 30px -22px ${withAlpha(colors.shadowDark, isDark ? 0.9 : 0.45)}`,
            },
          ]}
        >
          {showQuick && (
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              keyboardShouldPersistTaps="handled"
              contentContainerStyle={styles.quickRow}
            >
              {quickReplies!.map((q) => (
                <Chip key={q} label={q} size="sm" onPress={() => applyQuickReply(q)} />
              ))}
            </ScrollView>
          )}
          <View style={styles.inputRow}>
            <TextInput
              ref={inputRef}
              value={text}
              onChangeText={onChangeText}
              placeholder={placeholder}
              placeholderTextColor={colors.textFaint}
              multiline
              maxLength={MAX_LENGTH}
              style={[
                typography.body,
                styles.input,
                { backgroundColor: colors.inset, borderColor: colors.border, color: colors.textStrong },
              ]}
              accessibilityLabel={inputLabel}
            />
            <Button3D
              title="ส่ง"
              icon="paper-plane-tilt"
              size="sm"
              onPress={() => submit()}
              disabled={!trimmed}
              style={styles.sendButton}
              accessibilityLabel="ส่งข้อความ"
            />
          </View>
          {length >= COUNTER_FROM && (
            <Text style={[typography.micro, styles.counter, { color: length >= MAX_LENGTH ? colors.danger : colors.textFaint }]}>
              {length}/{MAX_LENGTH}
            </Text>
          )}
        </View>
      ) : (
        <View
          style={[
            styles.closed,
            { borderTopColor: colors.divider, backgroundColor: colors.card, paddingBottom: Math.max(insets.bottom, spacing.md) },
          ]}
        >
          <Icon name="lock" size={14} color={colors.textMuted} />
          <Text style={[typography.caption, { color: colors.textMuted }]}>คำสั่งซื้อนี้ปิดแล้ว ส่งข้อความเพิ่มไม่ได้</Text>
        </View>
      )}
    </KeyboardAvoidingView>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  flexShrink: {
    flexShrink: 1,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  olderLoader: {
    marginVertical: spacing.md,
  },
  list: {
    paddingHorizontal: spacing.screen,
    paddingVertical: spacing.md,
    flexGrow: 1,
  },
  flipped: {
    transform: [{ scaleY: -1 }],
  },
  empty: {
    alignItems: 'center',
    paddingVertical: spacing.xxxl,
    paddingHorizontal: spacing.lg,
  },
  emptyText: {
    marginTop: spacing.md,
    textAlign: 'center',
  },
  dayRow: {
    alignItems: 'center',
    marginVertical: spacing.sm,
  },
  dayPill: {
    borderRadius: radii.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: 3,
  },
  systemRow: {
    alignItems: 'center',
    marginVertical: spacing.xs,
  },
  systemBubble: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    maxWidth: '88%',
    borderRadius: radii.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
  },
  bubbleRow: {
    flexDirection: 'row',
    marginVertical: 3,
  },
  bubbleLeft: {
    justifyContent: 'flex-start',
  },
  bubbleRight: {
    justifyContent: 'flex-end',
  },
  bubble: {
    maxWidth: '80%',
    borderRadius: 20,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    gap: spacing.xxs,
  },
  bubbleMine: {
    borderBottomRightRadius: 6,
  },
  bubbleTheirs: {
    borderBottomLeftRadius: 6,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-end',
    gap: 4,
  },
  failedWrap: {
    maxWidth: '80%',
    alignItems: 'flex-end',
  },
  failedHint: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 4,
  },
  chatImage: {
    width: 200,
    height: 200,
    borderRadius: radii.md,
  },
  inputBar: {
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.sm,
    borderTopWidth: 1,
    gap: spacing.sm,
  },
  quickRow: {
    gap: spacing.sm,
    paddingRight: spacing.screen,
  },
  inputRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: spacing.sm,
  },
  input: {
    flex: 1,
    maxHeight: 120,
    minHeight: 44,
    borderRadius: 22,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  sendButton: {
    marginBottom: 2,
  },
  counter: {
    alignSelf: 'flex-end',
    marginTop: -4,
  },
  closed: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
});
