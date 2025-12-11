/**
 * Order Detail Screen - หน้ารายละเอียดคำสั่งซื้อ
 *
 * แสดงรายละเอียดคำสั่งซื้อ, ติดตามสถานะ, และแชทกับร้านค้า
 */

import React, { useEffect, useState, useCallback, useRef } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  RefreshControl,
  Image,
  ActivityIndicator,
  TextInput,
  KeyboardAvoidingView,
  Platform,
  Linking,
  Alert,
  FlatList,
  StatusBar,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router, Stack, useLocalSearchParams } from 'expo-router';
import Animated, { FadeInDown, FadeInUp } from 'react-native-reanimated';
import { Ionicons } from '@expo/vector-icons';
import { useAppStore } from '@/stores/appStore';
import { useAuthStore } from '@/stores/authStore';
import {
  getOrderDetail,
  getOrderTracking,
  getOrderMessages,
  sendOrderMessage,
  cancelOrder,
  type OrderDetail,
  type OrderTracking,
  type OrderMessage,
} from '@/services/api';
import { formatCurrency } from '@/constants';

// Tab types
type TabType = 'detail' | 'tracking' | 'chat';

// สีสถานะ
const getStatusColor = (status: string) => {
  switch (status) {
    case 'pending':
      return '#F59E0B';
    case 'confirmed':
      return '#3B82F6';
    case 'processing':
      return '#8B5CF6';
    case 'shipped':
      return '#06B6D4';
    case 'delivered':
    case 'completed':
      return '#10B981';
    case 'cancelled':
    case 'refunded':
      return '#EF4444';
    default:
      return '#6B7280';
  }
};

// Tab Component
const TabButton = ({
  label,
  icon,
  isActive,
  onPress,
  badge,
}: {
  label: string;
  icon: string;
  isActive: boolean;
  onPress: () => void;
  badge?: number;
}) => (
  <Pressable
    onPress={onPress}
    className={`flex-1 py-3 items-center border-b-2 ${
      isActive
        ? 'border-primary-500'
        : 'border-transparent'
    }`}
  >
    <View className="relative">
      <Ionicons
        name={icon as any}
        size={24}
        color={isActive ? '#3B82F6' : '#9CA3AF'}
      />
      {badge && badge > 0 && (
        <View className="absolute -top-1 -right-2 bg-red-500 rounded-full w-4 h-4 items-center justify-center">
          <Text className="text-white text-xs">{badge}</Text>
        </View>
      )}
    </View>
    <Text
      className={`text-sm mt-1 ${
        isActive ? 'text-primary-500 font-medium' : 'text-gray-500'
      }`}
    >
      {label}
    </Text>
  </Pressable>
);

// Order Detail Tab Component
const OrderDetailTab = ({
  order,
  onCancel,
}: {
  order: OrderDetail;
  onCancel: () => void;
}) => {
  const statusColor = getStatusColor(order.status);
  const canCancel = ['pending', 'confirmed'].includes(order.status) && order.payment_status !== 'paid';

  return (
    <ScrollView className="flex-1 p-4">
      {/* Status Badge */}
      <Animated.View
        entering={FadeInDown.delay(100)}
        className="bg-white dark:bg-dark-50 rounded-2xl p-4 mb-4"
      >
        <View className="flex-row justify-between items-center">
          <View>
            <Text className="text-sm text-gray-500 dark:text-gray-400">สถานะ</Text>
            <View className="flex-row items-center mt-1">
              <View
                className="w-3 h-3 rounded-full mr-2"
                style={{ backgroundColor: statusColor }}
              />
              <Text
                className="text-lg font-semibold"
                style={{ color: statusColor }}
              >
                {order.status_label}
              </Text>
            </View>
          </View>
          <View className="items-end">
            <Text className="text-sm text-gray-500 dark:text-gray-400">การชำระเงิน</Text>
            <Text className="text-base font-medium text-gray-900 dark:text-white mt-1">
              {order.payment_status_label}
            </Text>
          </View>
        </View>
      </Animated.View>

      {/* Order Items */}
      <Animated.View
        entering={FadeInDown.delay(200)}
        className="bg-white dark:bg-dark-50 rounded-2xl p-4 mb-4"
      >
        <Text className="text-lg font-semibold text-gray-900 dark:text-white mb-3">
          รายการสินค้า
        </Text>
        {order.items.map((item, index) => (
          <View
            key={item.id}
            className={`flex-row items-center py-3 ${
              index < order.items.length - 1
                ? 'border-b border-gray-100 dark:border-gray-700'
                : ''
            }`}
          >
            {item.product_image ? (
              <Image
                source={{ uri: item.product_image }}
                className="w-16 h-16 rounded-xl"
                resizeMode="cover"
              />
            ) : (
              <View className="w-16 h-16 rounded-xl bg-gray-100 dark:bg-gray-800 items-center justify-center">
                <Ionicons name="cube-outline" size={24} color="#9CA3AF" />
              </View>
            )}
            <View className="flex-1 ml-3">
              <Text
                className="text-base font-medium text-gray-900 dark:text-white"
                numberOfLines={2}
              >
                {item.product_name}
              </Text>
              <Text className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {formatCurrency(item.price)} x {item.quantity}
              </Text>
            </View>
            <Text className="text-base font-semibold text-primary-500">
              {formatCurrency(item.total)}
            </Text>
          </View>
        ))}
      </Animated.View>

      {/* Order Summary */}
      <Animated.View
        entering={FadeInDown.delay(300)}
        className="bg-white dark:bg-dark-50 rounded-2xl p-4 mb-4"
      >
        <Text className="text-lg font-semibold text-gray-900 dark:text-white mb-3">
          สรุปยอด
        </Text>
        <View className="flex-row justify-between mb-2">
          <Text className="text-gray-500 dark:text-gray-400">ราคาสินค้า</Text>
          <Text className="text-gray-900 dark:text-white">{formatCurrency(order.subtotal)}</Text>
        </View>
        <View className="flex-row justify-between mb-2">
          <Text className="text-gray-500 dark:text-gray-400">ค่าจัดส่ง</Text>
          <Text className="text-gray-900 dark:text-white">
            {order.shipping_fee > 0 ? formatCurrency(order.shipping_fee) : 'ฟรี'}
          </Text>
        </View>
        {order.discount > 0 && (
          <View className="flex-row justify-between mb-2">
            <Text className="text-green-500">ส่วนลด</Text>
            <Text className="text-green-500">-{formatCurrency(order.discount)}</Text>
          </View>
        )}
        <View className="flex-row justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
          <Text className="text-lg font-bold text-gray-900 dark:text-white">รวมทั้งหมด</Text>
          <Text className="text-xl font-bold text-primary-500">
            {formatCurrency(order.total_amount)}
          </Text>
        </View>
      </Animated.View>

      {/* Shipping Address */}
      <Animated.View
        entering={FadeInDown.delay(400)}
        className="bg-white dark:bg-dark-50 rounded-2xl p-4 mb-4"
      >
        <Text className="text-lg font-semibold text-gray-900 dark:text-white mb-3">
          ที่อยู่จัดส่ง
        </Text>
        <View className="flex-row items-start">
          <Ionicons name="location" size={20} color="#3B82F6" />
          <View className="flex-1 ml-2">
            <Text className="text-base font-medium text-gray-900 dark:text-white">
              {order.shipping.name}
            </Text>
            <Text className="text-gray-500 dark:text-gray-400 mt-1">
              {order.shipping.phone}
            </Text>
            <Text className="text-gray-600 dark:text-gray-300 mt-2">
              {order.shipping.address}, {order.shipping.subdistrict}, {order.shipping.district},{' '}
              {order.shipping.province} {order.shipping.postal_code}
            </Text>
          </View>
        </View>
      </Animated.View>

      {/* Cancel Button */}
      {canCancel && (
        <Animated.View entering={FadeInDown.delay(500)}>
          <Pressable
            onPress={onCancel}
            className="bg-red-500 rounded-xl py-4 items-center mb-8"
          >
            <Text className="text-white font-semibold">ยกเลิกคำสั่งซื้อ</Text>
          </Pressable>
        </Animated.View>
      )}
    </ScrollView>
  );
};

// Tracking Tab Component
const TrackingTab = ({ tracking }: { tracking: OrderTracking | null }) => {
  if (!tracking) {
    return (
      <View className="flex-1 items-center justify-center p-4">
        <Ionicons name="cube-outline" size={64} color="#9CA3AF" />
        <Text className="text-gray-500 dark:text-gray-400 text-lg mt-4">
          ยังไม่มีข้อมูลการจัดส่ง
        </Text>
      </View>
    );
  }

  return (
    <ScrollView className="flex-1 p-4">
      {/* Tracking Info */}
      {tracking.tracking_number && (
        <Animated.View
          entering={FadeInDown.delay(100)}
          className="bg-white dark:bg-dark-50 rounded-2xl p-4 mb-4"
        >
          <View className="flex-row justify-between items-start">
            <View className="flex-1">
              <Text className="text-sm text-gray-500 dark:text-gray-400">หมายเลขพัสดุ</Text>
              <Text className="text-lg font-bold text-gray-900 dark:text-white mt-1">
                {tracking.tracking_number}
              </Text>
              {tracking.shipping_provider && (
                <View className="flex-row items-center mt-2">
                  <Ionicons name="business" size={16} color="#6B7280" />
                  <Text className="text-gray-600 dark:text-gray-300 ml-1">
                    {tracking.shipping_provider.name}
                  </Text>
                </View>
              )}
            </View>
            {tracking.tracking_url && (
              <Pressable
                onPress={() => Linking.openURL(tracking.tracking_url!)}
                className="bg-primary-500 px-4 py-2 rounded-lg"
              >
                <Text className="text-white font-medium">ติดตาม</Text>
              </Pressable>
            )}
          </View>

          {/* Shipping Provider Hotline */}
          {tracking.shipping_provider?.hotline && (
            <Pressable
              onPress={() =>
                Linking.openURL(`tel:${tracking.shipping_provider!.hotline}`)
              }
              className="flex-row items-center mt-4 pt-4 border-t border-gray-100 dark:border-gray-700"
            >
              <Ionicons name="call" size={20} color="#3B82F6" />
              <Text className="text-primary-500 ml-2">
                โทร {tracking.shipping_provider.hotline}
              </Text>
            </Pressable>
          )}
        </Animated.View>
      )}

      {/* Estimated Delivery */}
      {tracking.estimated_delivery_at && (
        <Animated.View
          entering={FadeInDown.delay(200)}
          className="bg-green-50 dark:bg-green-900/20 rounded-2xl p-4 mb-4"
        >
          <View className="flex-row items-center">
            <Ionicons name="time" size={24} color="#10B981" />
            <View className="ml-3">
              <Text className="text-sm text-green-600 dark:text-green-400">คาดว่าจะได้รับ</Text>
              <Text className="text-lg font-semibold text-green-700 dark:text-green-300">
                {new Date(tracking.estimated_delivery_at).toLocaleDateString('th-TH', {
                  weekday: 'long',
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric',
                })}
              </Text>
            </View>
          </View>
        </Animated.View>
      )}

      {/* Tracking History */}
      <Animated.View
        entering={FadeInDown.delay(300)}
        className="bg-white dark:bg-dark-50 rounded-2xl p-4 mb-4"
      >
        <Text className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          ประวัติการจัดส่ง
        </Text>
        {tracking.history.length === 0 ? (
          <Text className="text-gray-500 dark:text-gray-400 text-center py-4">
            ยังไม่มีประวัติ
          </Text>
        ) : (
          tracking.history.map((item, index) => {
            const statusColor = getStatusColor(item.status);
            const isFirst = index === 0;
            const isLast = index === tracking.history.length - 1;

            return (
              <View key={item.id} className="flex-row">
                {/* Timeline */}
                <View className="items-center mr-4">
                  <View
                    className="w-4 h-4 rounded-full"
                    style={{ backgroundColor: isFirst ? statusColor : '#D1D5DB' }}
                  />
                  {!isLast && (
                    <View className="w-0.5 flex-1 bg-gray-200 dark:bg-gray-700" />
                  )}
                </View>

                {/* Content */}
                <View className="flex-1 pb-6">
                  <Text
                    className={`text-base font-medium ${
                      isFirst ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'
                    }`}
                  >
                    {item.status_label}
                  </Text>
                  {item.description && (
                    <Text className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                      {item.description}
                    </Text>
                  )}
                  {item.location && (
                    <View className="flex-row items-center mt-1">
                      <Ionicons name="location" size={14} color="#9CA3AF" />
                      <Text className="text-sm text-gray-400 ml-1">{item.location}</Text>
                    </View>
                  )}
                  <Text className="text-xs text-gray-400 mt-1">
                    {new Date(item.created_at).toLocaleDateString('th-TH', {
                      year: 'numeric',
                      month: 'short',
                      day: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit',
                    })}
                  </Text>
                </View>
              </View>
            );
          })
        )}
      </Animated.View>
    </ScrollView>
  );
};

// Chat Tab Component
const ChatTab = ({
  orderId,
  messages,
  isLoading,
  onSend,
  onRefresh,
}: {
  orderId: number;
  messages: OrderMessage[];
  isLoading: boolean;
  onSend: (message: string) => void;
  onRefresh: () => void;
}) => {
  const [inputText, setInputText] = useState('');
  const [isSending, setIsSending] = useState(false);
  const flatListRef = useRef<FlatList>(null);

  const handleSend = async () => {
    if (!inputText.trim() || isSending) return;

    setIsSending(true);
    await onSend(inputText.trim());
    setInputText('');
    setIsSending(false);
  };

  const renderMessage = ({ item }: { item: OrderMessage }) => {
    const isMe = item.is_mine;

    return (
      <View
        className={`mb-3 ${isMe ? 'items-end' : 'items-start'}`}
      >
        {!isMe && (
          <Text className="text-xs text-gray-500 dark:text-gray-400 mb-1 ml-2">
            {item.sender_name}
          </Text>
        )}
        <View
          className={`max-w-[80%] rounded-2xl px-4 py-3 ${
            isMe
              ? 'bg-primary-500'
              : item.is_system_message
              ? 'bg-gray-100 dark:bg-gray-800'
              : 'bg-white dark:bg-dark-50'
          }`}
        >
          {item.is_system_message && (
            <View className="flex-row items-center mb-1">
              <Ionicons name="information-circle" size={14} color="#6B7280" />
              <Text className="text-xs text-gray-500 ml-1">ข้อความจากระบบ</Text>
            </View>
          )}
          <Text
            className={`text-base ${
              isMe ? 'text-white' : 'text-gray-900 dark:text-white'
            }`}
          >
            {item.message}
          </Text>
          {item.attachment && (
            <Pressable
              onPress={() => Linking.openURL(item.attachment!)}
              className="mt-2"
            >
              {item.attachment_type === 'image' ? (
                <Image
                  source={{ uri: item.attachment }}
                  className="w-48 h-48 rounded-xl"
                  resizeMode="cover"
                />
              ) : (
                <View className="flex-row items-center bg-gray-100 dark:bg-gray-800 rounded-lg px-3 py-2">
                  <Ionicons name="document" size={20} color="#3B82F6" />
                  <Text className="text-primary-500 ml-2">ดูไฟล์แนบ</Text>
                </View>
              )}
            </Pressable>
          )}
        </View>
        <Text className="text-xs text-gray-400 mt-1 mx-2">
          {new Date(item.created_at).toLocaleTimeString('th-TH', {
            hour: '2-digit',
            minute: '2-digit',
          })}
        </Text>
      </View>
    );
  };

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      className="flex-1"
      keyboardVerticalOffset={100}
    >
      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator size="large" color="#3B82F6" />
        </View>
      ) : (
        <FlatList
          ref={flatListRef}
          data={[...messages].reverse()}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderMessage}
          contentContainerStyle={{ padding: 16, flexGrow: 1 }}
          inverted={false}
          refreshControl={
            <RefreshControl
              refreshing={false}
              onRefresh={onRefresh}
              colors={['#3B82F6']}
            />
          }
          ListEmptyComponent={
            <View className="flex-1 items-center justify-center py-20">
              <Ionicons name="chatbubbles-outline" size={64} color="#9CA3AF" />
              <Text className="text-gray-500 dark:text-gray-400 text-lg mt-4">
                ยังไม่มีข้อความ
              </Text>
              <Text className="text-gray-400 text-sm mt-2">
                เริ่มพูดคุยกับร้านค้าได้เลย
              </Text>
            </View>
          }
        />
      )}

      {/* Input */}
      <View className="flex-row items-end p-4 bg-white dark:bg-dark-50 border-t border-gray-100 dark:border-gray-700">
        <TextInput
          className="flex-1 bg-gray-100 dark:bg-gray-800 rounded-2xl px-4 py-3 text-gray-900 dark:text-white mr-2 max-h-24"
          placeholder="พิมพ์ข้อความ..."
          placeholderTextColor="#9CA3AF"
          value={inputText}
          onChangeText={setInputText}
          multiline
          editable={!isSending}
        />
        <Pressable
          onPress={handleSend}
          disabled={!inputText.trim() || isSending}
          className={`w-12 h-12 rounded-full items-center justify-center ${
            inputText.trim() && !isSending ? 'bg-primary-500' : 'bg-gray-200 dark:bg-gray-700'
          }`}
        >
          {isSending ? (
            <ActivityIndicator size="small" color="white" />
          ) : (
            <Ionicons
              name="send"
              size={20}
              color={inputText.trim() ? 'white' : '#9CA3AF'}
            />
          )}
        </Pressable>
      </View>
    </KeyboardAvoidingView>
  );
};

export default function OrderDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const orderId = parseInt(id, 10);
  const { isDarkMode } = useAppStore();
  const { isAuthenticated } = useAuthStore();

  const [activeTab, setActiveTab] = useState<TabType>('detail');
  const [order, setOrder] = useState<OrderDetail | null>(null);
  const [tracking, setTracking] = useState<OrderTracking | null>(null);
  const [messages, setMessages] = useState<OrderMessage[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isLoadingChat, setIsLoadingChat] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);

  // โหลดรายละเอียดคำสั่งซื้อ
  const loadOrderDetail = useCallback(async () => {
    if (!isAuthenticated || !orderId) {
      router.replace('/login');
      return;
    }

    try {
      setIsLoading(true);
      const result = await getOrderDetail(orderId);
      if (result) {
        setOrder(result);
      } else {
        Alert.alert('ผิดพลาด', 'ไม่พบคำสั่งซื้อ');
        router.back();
      }
    } catch (error) {
      console.error('Load order detail error:', error);
    } finally {
      setIsLoading(false);
    }
  }, [isAuthenticated, orderId]);

  // โหลด Tracking
  const loadTracking = useCallback(async () => {
    if (!orderId) return;
    try {
      const result = await getOrderTracking(orderId);
      setTracking(result);
    } catch (error) {
      console.error('Load tracking error:', error);
    }
  }, [orderId]);

  // โหลดข้อความ (showLoading = false สำหรับ polling แบบ silent)
  const loadMessages = useCallback(async (showLoading: boolean = true) => {
    if (!orderId) return;
    try {
      if (showLoading) {
        setIsLoadingChat(true);
      }
      const result = await getOrderMessages(orderId);
      if (result) {
        setMessages(result.messages);
        setUnreadCount(0); // Reset after loading
      }
    } catch (error) {
      console.error('Load messages error:', error);
    } finally {
      if (showLoading) {
        setIsLoadingChat(false);
      }
    }
  }, [orderId]);

  // ส่งข้อความ
  const handleSendMessage = async (message: string) => {
    const result = await sendOrderMessage(orderId, message);
    if (result.success && result.data) {
      setMessages((prev) => [result.data!, ...prev]);
    } else {
      Alert.alert('ผิดพลาด', result.message || 'ไม่สามารถส่งข้อความได้');
    }
  };

  // ยกเลิกคำสั่งซื้อ
  const handleCancelOrder = () => {
    Alert.alert(
      'ยืนยันการยกเลิก',
      'คุณต้องการยกเลิกคำสั่งซื้อนี้หรือไม่?',
      [
        { text: 'ไม่ใช่', style: 'cancel' },
        {
          text: 'ยกเลิก',
          style: 'destructive',
          onPress: async () => {
            const result = await cancelOrder(orderId);
            if (result.success) {
              Alert.alert('สำเร็จ', 'ยกเลิกคำสั่งซื้อแล้ว');
              loadOrderDetail();
            } else {
              Alert.alert('ผิดพลาด', result.message || 'ไม่สามารถยกเลิกได้');
            }
          },
        },
      ]
    );
  };

  // Initial load
  useEffect(() => {
    loadOrderDetail();
    loadTracking();
  }, []);

  // Load messages when chat tab is active + Auto-poll every 10 seconds
  useEffect(() => {
    if (activeTab === 'chat') {
      loadMessages(true); // โหลดครั้งแรกแสดง loading

      // ตั้ง interval เพื่อ poll ข้อความใหม่ทุก 10 วินาที (silent)
      const pollInterval = setInterval(() => {
        loadMessages(false); // poll แบบไม่แสดง loading
      }, 10000); // 10 วินาที

      return () => clearInterval(pollInterval);
    }
  }, [activeTab, loadMessages]);

  if (isLoading) {
    return (
      <View style={{ flex: 1, backgroundColor: isDarkMode ? '#0F172A' : '#F9FAFB' }}>
        <StatusBar barStyle="light-content" backgroundColor={isDarkMode ? '#1F2937' : '#3B82F6'} />
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text className="text-gray-500 dark:text-gray-400 mt-3">
            กำลังโหลด...
          </Text>
        </View>
      </View>
    );
  }

  if (!order) {
    return null;
  }

  return (
    <View style={{ flex: 1, backgroundColor: isDarkMode ? '#0F172A' : '#F9FAFB' }}>
      <StatusBar barStyle="light-content" backgroundColor={isDarkMode ? '#1F2937' : '#3B82F6'} />
      <Stack.Screen
        options={{
          headerShown: false,
        }}
      />

      {/* Header */}
      <LinearGradient
        colors={isDarkMode ? ['#1F2937', '#111827'] : ['#3B82F6', '#1D4ED8']}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={{ paddingHorizontal: 16, paddingTop: 50, paddingBottom: 16 }}
      >
        <View className="flex-row items-center">
          <Pressable
            onPress={() => router.back()}
            className="w-10 h-10 rounded-full bg-white/20 items-center justify-center mr-3"
          >
            <Ionicons name="arrow-back" size={24} color="white" />
          </Pressable>
          <View className="flex-1">
            <Text className="text-white text-lg font-bold">
              {order.order_number}
            </Text>
            <Text className="text-white/70 text-sm">
              {new Date(order.created_at).toLocaleDateString('th-TH')}
            </Text>
          </View>
        </View>
      </LinearGradient>

      {/* Tabs */}
      <View className="flex-row bg-white dark:bg-dark-50 border-b border-gray-100 dark:border-gray-700">
        <TabButton
          label="รายละเอียด"
          icon="receipt-outline"
          isActive={activeTab === 'detail'}
          onPress={() => setActiveTab('detail')}
        />
        <TabButton
          label="ติดตาม"
          icon="location-outline"
          isActive={activeTab === 'tracking'}
          onPress={() => setActiveTab('tracking')}
        />
        <TabButton
          label="แชท"
          icon="chatbubbles-outline"
          isActive={activeTab === 'chat'}
          onPress={() => setActiveTab('chat')}
          badge={unreadCount}
        />
      </View>

      {/* Tab Content */}
      {activeTab === 'detail' && (
        <OrderDetailTab order={order} onCancel={handleCancelOrder} />
      )}
      {activeTab === 'tracking' && <TrackingTab tracking={tracking} />}
      {activeTab === 'chat' && (
        <ChatTab
          orderId={orderId}
          messages={messages}
          isLoading={isLoadingChat}
          onSend={handleSendMessage}
          onRefresh={() => loadMessages(true)}
        />
      )}
    </View>
  );
}
