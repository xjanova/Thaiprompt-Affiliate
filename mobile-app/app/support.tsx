/**
 * Support Ticket Screen - หน้าติดต่อแอดมิน
 * ระบบ Ticket สำหรับติดต่อสอบถามและแจ้งปัญหา
 */

import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  TextInput,
  ActivityIndicator,
  Modal,
  FlatList,
  RefreshControl,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import Animated, { FadeInDown, FadeInUp } from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import {
  getTickets,
  createTicket,
  getTicket,
  replyTicket,
  rateTicket,
  Ticket,
  TicketMessage,
} from '@/services/api';

// =====================================================
// Category Options
// =====================================================

const CATEGORIES = [
  { value: 'general', label: 'ทั่วไป', icon: 'chatbubble-ellipses' },
  { value: 'account', label: 'บัญชี', icon: 'person' },
  { value: 'payment', label: 'การชำระเงิน', icon: 'card' },
  { value: 'rider', label: 'ไรเดอร์', icon: 'bicycle' },
  { value: 'technical', label: 'ปัญหาเทคนิค', icon: 'bug' },
  { value: 'complaint', label: 'ร้องเรียน', icon: 'warning' },
  { value: 'suggestion', label: 'ข้อเสนอแนะ', icon: 'bulb' },
  { value: 'other', label: 'อื่นๆ', icon: 'ellipsis-horizontal' },
];

const PRIORITY_COLORS = {
  low: '#9CA3AF',
  medium: '#3B82F6',
  high: '#F59E0B',
  urgent: '#EF4444',
};

const STATUS_COLORS = {
  open: '#3B82F6',
  in_progress: '#F59E0B',
  waiting: '#8B5CF6',
  resolved: '#10B981',
  closed: '#6B7280',
};

// =====================================================
// Create Ticket Modal
// =====================================================

interface CreateTicketModalProps {
  visible: boolean;
  onClose: () => void;
  onSuccess: () => void;
  isDark: boolean;
}

const CreateTicketModal = ({
  visible,
  onClose,
  onSuccess,
  isDark,
}: CreateTicketModalProps) => {
  const [subject, setSubject] = useState('');
  const [category, setCategory] = useState('general');
  const [message, setMessage] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async () => {
    if (!subject.trim()) {
      Alert.alert('ข้อผิดพลาด', 'กรุณากรอกหัวข้อ');
      return;
    }
    if (!message.trim()) {
      Alert.alert('ข้อผิดพลาด', 'กรุณากรอกข้อความ');
      return;
    }

    setIsSubmitting(true);
    try {
      const response = await createTicket({
        subject: subject.trim(),
        category,
        message: message.trim(),
      });

      if (response.success) {
        Alert.alert('สำเร็จ', `สร้าง Ticket #${response.data?.ticketNumber} สำเร็จ`);
        setSubject('');
        setCategory('general');
        setMessage('');
        onClose();
        onSuccess();
      } else {
        Alert.alert('ข้อผิดพลาด', response.message || 'ไม่สามารถสร้าง Ticket ได้');
      }
    } catch (error) {
      Alert.alert('ข้อผิดพลาด', 'เกิดข้อผิดพลาด กรุณาลองใหม่');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Modal visible={visible} transparent animationType="slide">
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        className="flex-1"
      >
        <View className="flex-1 bg-black/50 justify-end">
          <View
            className={`${isDark ? 'bg-gray-800' : 'bg-white'} rounded-t-3xl max-h-[90%]`}
          >
            {/* Header */}
            <View className="flex-row items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
              <Text className={`text-xl font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
                สร้าง Ticket ใหม่
              </Text>
              <Pressable onPress={onClose} className="p-2">
                <Ionicons name="close" size={24} color={isDark ? '#fff' : '#000'} />
              </Pressable>
            </View>

            <ScrollView className="p-4">
              {/* Subject */}
              <View className="mb-4">
                <Text className={`mb-1 font-medium ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                  หัวข้อ *
                </Text>
                <TextInput
                  value={subject}
                  onChangeText={setSubject}
                  placeholder="เช่น มีปัญหาเกี่ยวกับการถอนเงิน"
                  placeholderTextColor="#9CA3AF"
                  className={`rounded-xl px-4 py-3 ${
                    isDark ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900'
                  }`}
                />
              </View>

              {/* Category */}
              <View className="mb-4">
                <Text className={`mb-2 font-medium ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                  หมวดหมู่
                </Text>
                <View className="flex-row flex-wrap">
                  {CATEGORIES.map((cat) => (
                    <Pressable
                      key={cat.value}
                      onPress={() => setCategory(cat.value)}
                      className={`flex-row items-center mr-2 mb-2 px-3 py-2 rounded-xl ${
                        category === cat.value
                          ? 'bg-primary-500'
                          : isDark
                            ? 'bg-gray-700'
                            : 'bg-gray-100'
                      }`}
                    >
                      <Ionicons
                        name={cat.icon as keyof typeof Ionicons.glyphMap}
                        size={16}
                        color={category === cat.value ? '#fff' : '#6B7280'}
                      />
                      <Text
                        className={`ml-1 text-sm ${
                          category === cat.value
                            ? 'text-white'
                            : isDark
                              ? 'text-gray-300'
                              : 'text-gray-700'
                        }`}
                      >
                        {cat.label}
                      </Text>
                    </Pressable>
                  ))}
                </View>
              </View>

              {/* Message */}
              <View className="mb-6">
                <Text className={`mb-1 font-medium ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                  รายละเอียด *
                </Text>
                <TextInput
                  value={message}
                  onChangeText={setMessage}
                  placeholder="อธิบายปัญหาหรือคำถามของคุณ..."
                  placeholderTextColor="#9CA3AF"
                  multiline
                  numberOfLines={5}
                  textAlignVertical="top"
                  className={`rounded-xl px-4 py-3 min-h-[120px] ${
                    isDark ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900'
                  }`}
                />
              </View>

              {/* Submit Button */}
              <Pressable
                onPress={handleSubmit}
                disabled={isSubmitting}
                className={`bg-primary-500 rounded-xl py-4 mb-6 ${isSubmitting ? 'opacity-60' : ''}`}
              >
                {isSubmitting ? (
                  <ActivityIndicator color="white" />
                ) : (
                  <Text className="text-white text-center font-bold text-lg">
                    ส่ง Ticket
                  </Text>
                )}
              </Pressable>
            </ScrollView>
          </View>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
};

// =====================================================
// Ticket Detail Modal
// =====================================================

interface TicketDetailModalProps {
  visible: boolean;
  onClose: () => void;
  ticketId: number | null;
  isDark: boolean;
  onRefresh: () => void;
}

const TicketDetailModal = ({
  visible,
  onClose,
  ticketId,
  isDark,
  onRefresh,
}: TicketDetailModalProps) => {
  const [ticket, setTicket] = useState<{
    ticket: Ticket;
    messages: TicketMessage[];
  } | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [replyText, setReplyText] = useState('');
  const [isSending, setIsSending] = useState(false);

  const loadTicket = useCallback(async () => {
    if (!ticketId) return;

    setIsLoading(true);
    try {
      const response = await getTicket(ticketId);
      if (response?.success && response.data) {
        setTicket({
          ticket: response.data.ticket as unknown as Ticket,
          messages: response.data.messages,
        });
      }
    } catch (error) {
      console.error('Load ticket error:', error);
    } finally {
      setIsLoading(false);
    }
  }, [ticketId]);

  useEffect(() => {
    if (visible && ticketId) {
      loadTicket();
    }
  }, [visible, ticketId, loadTicket]);

  const handleReply = async () => {
    if (!replyText.trim() || !ticketId) return;

    setIsSending(true);
    try {
      const response = await replyTicket(ticketId, replyText.trim());
      if (response.success) {
        setReplyText('');
        await loadTicket();
        onRefresh();
      } else {
        Alert.alert('ข้อผิดพลาด', response.message || 'ไม่สามารถส่งข้อความได้');
      }
    } catch (error) {
      Alert.alert('ข้อผิดพลาด', 'เกิดข้อผิดพลาด กรุณาลองใหม่');
    } finally {
      setIsSending(false);
    }
  };

  return (
    <Modal visible={visible} transparent animationType="slide">
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        className="flex-1"
      >
        <View className="flex-1 bg-black/50">
          <SafeAreaView className="flex-1">
            <View
              className={`flex-1 ${isDark ? 'bg-gray-800' : 'bg-white'} rounded-t-3xl mt-10`}
            >
              {/* Header */}
              <View className="flex-row items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <View className="flex-1">
                  <Text className={`text-lg font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
                    {ticket?.ticket.ticketNumber || 'Loading...'}
                  </Text>
                  <Text className="text-gray-500 text-sm" numberOfLines={1}>
                    {ticket?.ticket.subject || ''}
                  </Text>
                </View>
                <Pressable onPress={onClose} className="p-2">
                  <Ionicons name="close" size={24} color={isDark ? '#fff' : '#000'} />
                </Pressable>
              </View>

              {isLoading ? (
                <View className="flex-1 justify-center items-center">
                  <ActivityIndicator size="large" color="#3B82F6" />
                </View>
              ) : (
                <>
                  {/* Status Badge */}
                  <View className="px-4 py-2 flex-row items-center">
                    <View
                      className="px-3 py-1 rounded-full"
                      style={{
                        backgroundColor:
                          STATUS_COLORS[ticket?.ticket.status as keyof typeof STATUS_COLORS] + '20',
                      }}
                    >
                      <Text
                        style={{
                          color: STATUS_COLORS[ticket?.ticket.status as keyof typeof STATUS_COLORS],
                        }}
                        className="text-sm font-medium"
                      >
                        {ticket?.ticket.statusText}
                      </Text>
                    </View>
                    <Text className="text-gray-500 text-xs ml-2">
                      {ticket?.ticket.categoryText}
                    </Text>
                  </View>

                  {/* Messages */}
                  <FlatList
                    data={ticket?.messages || []}
                    keyExtractor={(item) => item.id.toString()}
                    className="flex-1 px-4"
                    renderItem={({ item }) => (
                      <View
                        className={`mb-3 p-4 rounded-2xl ${
                          item.isFromAdmin
                            ? 'bg-blue-50 dark:bg-blue-900/30 self-start max-w-[85%]'
                            : 'bg-gray-100 dark:bg-gray-700 self-end max-w-[85%]'
                        }`}
                      >
                        <View className="flex-row items-center mb-1">
                          <Ionicons
                            name={item.isFromAdmin ? 'shield-checkmark' : 'person'}
                            size={14}
                            color={item.isFromAdmin ? '#3B82F6' : '#6B7280'}
                          />
                          <Text
                            className={`text-xs ml-1 ${
                              item.isFromAdmin
                                ? 'text-blue-600 dark:text-blue-400'
                                : 'text-gray-500'
                            }`}
                          >
                            {item.isFromAdmin ? 'Admin' : 'คุณ'}
                          </Text>
                        </View>
                        <Text
                          className={`${
                            item.isFromAdmin
                              ? 'text-blue-800 dark:text-blue-200'
                              : isDark
                                ? 'text-white'
                                : 'text-gray-800'
                          }`}
                        >
                          {item.message}
                        </Text>
                        <Text className="text-xs text-gray-400 mt-1">
                          {item.createdAt}
                        </Text>
                      </View>
                    )}
                  />

                  {/* Reply Input */}
                  {ticket?.ticket.status !== 'closed' && (
                    <View className="p-4 border-t border-gray-200 dark:border-gray-700">
                      <View className="flex-row items-end">
                        <TextInput
                          value={replyText}
                          onChangeText={setReplyText}
                          placeholder="พิมพ์ข้อความ..."
                          placeholderTextColor="#9CA3AF"
                          multiline
                          className={`flex-1 rounded-2xl px-4 py-3 mr-2 max-h-24 ${
                            isDark ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900'
                          }`}
                        />
                        <Pressable
                          onPress={handleReply}
                          disabled={isSending || !replyText.trim()}
                          className={`w-12 h-12 rounded-full bg-primary-500 items-center justify-center ${
                            !replyText.trim() ? 'opacity-50' : ''
                          }`}
                        >
                          {isSending ? (
                            <ActivityIndicator color="white" size="small" />
                          ) : (
                            <Ionicons name="send" size={20} color="white" />
                          )}
                        </Pressable>
                      </View>
                    </View>
                  )}
                </>
              )}
            </View>
          </SafeAreaView>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
};

// =====================================================
// Main Support Screen
// =====================================================

export default function SupportScreen() {
  const { isAuthenticated } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const [tickets, setTickets] = useState<Ticket[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [selectedTicketId, setSelectedTicketId] = useState<number | null>(null);

  const loadTickets = useCallback(async () => {
    try {
      const response = await getTickets();
      if (response?.success && response.data) {
        setTickets(response.data.tickets);
      }
    } catch (error) {
      console.error('Load tickets error:', error);
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, []);

  useEffect(() => {
    if (isAuthenticated) {
      loadTickets();
    }
  }, [isAuthenticated, loadTickets]);

  const handleRefresh = () => {
    setIsRefreshing(true);
    loadTickets();
  };

  // Not authenticated
  if (!isAuthenticated) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1 justify-center items-center px-6">
          <Ionicons name="chatbubbles" size={80} color={isDark ? '#4B5563' : '#9CA3AF'} />
          <Text className={`text-xl font-bold mt-4 ${isDark ? 'text-white' : 'text-gray-800'}`}>
            ติดต่อฝ่ายสนับสนุน
          </Text>
          <Text className="text-gray-500 text-center mt-2">
            เข้าสู่ระบบเพื่อติดต่อแอดมิน
          </Text>
          <Pressable
            onPress={() => router.push('/login')}
            className="bg-primary-500 px-8 py-3 rounded-xl mt-6"
          >
            <Text className="text-white font-bold">เข้าสู่ระบบ</Text>
          </Pressable>
        </SafeAreaView>
      </View>
    );
  }

  return (
    <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
      <SafeAreaView className="flex-1">
        {/* Header */}
        <View className="flex-row items-center justify-between px-5 pt-4 pb-2">
          <View className="flex-row items-center">
            <Pressable onPress={() => router.back()} className="mr-4">
              <Ionicons name="arrow-back" size={24} color={isDark ? '#fff' : '#000'} />
            </Pressable>
            <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
              ติดต่อแอดมิน
            </Text>
          </View>
          <Pressable
            onPress={() => setShowCreateModal(true)}
            className="bg-primary-500 px-4 py-2 rounded-xl flex-row items-center"
          >
            <Ionicons name="add" size={20} color="white" />
            <Text className="text-white font-medium ml-1">สร้าง</Text>
          </Pressable>
        </View>

        {/* Content */}
        {isLoading ? (
          <View className="flex-1 justify-center items-center">
            <ActivityIndicator size="large" color="#3B82F6" />
            <Text className="text-gray-500 mt-4">กำลังโหลด...</Text>
          </View>
        ) : tickets.length === 0 ? (
          <View className="flex-1 justify-center items-center px-6">
            <LinearGradient
              colors={['#3B82F6', '#8B5CF6']}
              className="w-24 h-24 rounded-full items-center justify-center mb-6"
            >
              <Ionicons name="chatbubbles-outline" size={48} color="white" />
            </LinearGradient>
            <Text className={`text-xl font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
              ยังไม่มี Ticket
            </Text>
            <Text className="text-gray-500 text-center mt-2 mb-6">
              สร้าง Ticket เพื่อติดต่อสอบถามหรือแจ้งปัญหา
            </Text>
            <Pressable
              onPress={() => setShowCreateModal(true)}
              className="bg-primary-500 px-8 py-4 rounded-xl"
            >
              <Text className="text-white font-bold text-lg">สร้าง Ticket</Text>
            </Pressable>
          </View>
        ) : (
          <FlatList
            data={tickets}
            keyExtractor={(item) => item.id.toString()}
            contentContainerClassName="px-5 py-4"
            refreshControl={
              <RefreshControl
                refreshing={isRefreshing}
                onRefresh={handleRefresh}
                tintColor="#3B82F6"
              />
            }
            renderItem={({ item, index }) => (
              <Animated.View entering={FadeInDown.delay(index * 50).springify()}>
                <Pressable
                  onPress={() => setSelectedTicketId(item.id)}
                  className={`mb-3 p-4 rounded-2xl ${
                    isDark ? 'bg-gray-800' : 'bg-white'
                  } shadow-sm`}
                >
                  {/* Header */}
                  <View className="flex-row items-center justify-between mb-2">
                    <Text className="text-primary-500 font-medium">
                      #{item.ticketNumber}
                    </Text>
                    <View
                      className="px-2 py-1 rounded-full"
                      style={{
                        backgroundColor:
                          STATUS_COLORS[item.status as keyof typeof STATUS_COLORS] + '20',
                      }}
                    >
                      <Text
                        style={{
                          color: STATUS_COLORS[item.status as keyof typeof STATUS_COLORS],
                        }}
                        className="text-xs font-medium"
                      >
                        {item.statusText}
                      </Text>
                    </View>
                  </View>

                  {/* Subject */}
                  <Text
                    className={`font-medium mb-1 ${isDark ? 'text-white' : 'text-gray-800'}`}
                    numberOfLines={2}
                  >
                    {item.subject}
                  </Text>

                  {/* Footer */}
                  <View className="flex-row items-center justify-between mt-2">
                    <View className="flex-row items-center">
                      <View
                        className="px-2 py-1 rounded-md mr-2"
                        style={{
                          backgroundColor:
                            PRIORITY_COLORS[item.priority as keyof typeof PRIORITY_COLORS] + '20',
                        }}
                      >
                        <Text
                          style={{
                            color: PRIORITY_COLORS[item.priority as keyof typeof PRIORITY_COLORS],
                          }}
                          className="text-xs"
                        >
                          {item.categoryText}
                        </Text>
                      </View>
                      <Text className="text-gray-400 text-xs">
                        {item.messageCount} ข้อความ
                      </Text>
                    </View>
                    {item.hasUnreadAdminMessage && (
                      <View className="w-3 h-3 rounded-full bg-red-500" />
                    )}
                  </View>
                </Pressable>
              </Animated.View>
            )}
          />
        )}
      </SafeAreaView>

      {/* Create Ticket Modal */}
      <CreateTicketModal
        visible={showCreateModal}
        onClose={() => setShowCreateModal(false)}
        onSuccess={loadTickets}
        isDark={isDark}
      />

      {/* Ticket Detail Modal */}
      <TicketDetailModal
        visible={selectedTicketId !== null}
        onClose={() => setSelectedTicketId(null)}
        ticketId={selectedTicketId}
        isDark={isDark}
        onRefresh={loadTickets}
      />
    </View>
  );
}
