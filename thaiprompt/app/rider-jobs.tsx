/**
 * Rider Jobs Screen - หน้ารายการงานที่รอรับ
 * แสดงรายการงานที่พร้อมรับ พร้อมรายละเอียดและปุ่มรับงาน
 */

import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  RefreshControl,
  ActivityIndicator,
  Modal,
  Linking,
  Platform,
  StatusBar,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import Animated, { FadeInDown, FadeIn, ZoomIn } from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import {
  getAvailableJobs,
  getCurrentJob,
  acceptJob,
} from '@/services/api';
import { formatCurrency } from '@/constants';
import { ErrorState, NetworkErrorBanner } from '@/components/ErrorState';

// =====================================================
// Types
// =====================================================

interface Job {
  id: number;
  jobNumber: string;
  jobType: string;
  jobTypeText: string;
  title: string;
  pickup: {
    address: string;
    latitude: number;
    longitude: number;
  };
  delivery: {
    address: string;
    latitude: number;
    longitude: number;
  };
  distanceKm?: number;
  totalFee: number;
  riderEarnings: number;
  createdAt: string;
}

// =====================================================
// Job Type Badge Component
// =====================================================

const JobTypeBadge = ({ type, text }: { type: string; text: string }) => {
  const colors: Record<string, { bg: string; text: string }> = {
    food: { bg: 'bg-orange-100', text: 'text-orange-600' },
    package: { bg: 'bg-blue-100', text: 'text-blue-600' },
    document: { bg: 'bg-purple-100', text: 'text-purple-600' },
    grocery: { bg: 'bg-green-100', text: 'text-green-600' },
    default: { bg: 'bg-gray-100', text: 'text-gray-600' },
  };

  const style = colors[type] || colors.default;

  return (
    <View className={`px-2 py-1 rounded-full ${style.bg}`}>
      <Text className={`text-xs font-medium ${style.text}`}>{text}</Text>
    </View>
  );
};

// =====================================================
// Job Card Component
// =====================================================

const JobCard = ({
  job,
  index,
  onAccept,
  onViewMap,
  isAccepting,
}: {
  job: Job;
  index: number;
  onAccept: (job: Job) => void;
  onViewMap: (job: Job) => void;
  isAccepting: boolean;
}) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  return (
    <Animated.View
      entering={FadeInDown.delay(100 + index * 50).springify()}
      className="bg-white dark:bg-gray-800 rounded-2xl p-4 mb-4 border border-gray-100 dark:border-gray-700"
      style={{
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.05,
        shadowRadius: 8,
        elevation: 2,
      }}
    >
      {/* Header */}
      <View className="flex-row items-center justify-between mb-3">
        <View className="flex-row items-center">
          <JobTypeBadge type={job.jobType} text={job.jobTypeText} />
          <Text className="text-gray-400 text-xs ml-2">#{job.jobNumber}</Text>
        </View>
        <View className="bg-green-100 dark:bg-green-900/30 px-2 py-1 rounded-lg">
          <Text className="text-green-600 dark:text-green-400 font-bold text-sm">
            {formatCurrency(job.riderEarnings)}
          </Text>
        </View>
      </View>

      {/* Title */}
      <Text className="text-gray-900 dark:text-white font-bold text-lg mb-3">
        {job.title}
      </Text>

      {/* Pickup Location */}
      <View className="flex-row items-start mb-2">
        <View className="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 items-center justify-center mr-3">
          <Text style={{ fontSize: 16, color: '#10B981' }}>📍</Text>
        </View>
        <View className="flex-1">
          <Text className="text-gray-500 dark:text-gray-400 text-xs mb-0.5">
            รับของ
          </Text>
          <Text className="text-gray-800 dark:text-gray-200 text-sm" numberOfLines={2}>
            {job.pickup.address}
          </Text>
        </View>
      </View>

      {/* Arrow */}
      <View className="flex-row items-center pl-4 py-1">
        <View className="w-0.5 h-4 bg-gray-200 dark:bg-gray-600 mr-3.5" />
      </View>

      {/* Delivery Location */}
      <View className="flex-row items-start mb-4">
        <View className="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30 items-center justify-center mr-3">
          <Text style={{ fontSize: 16, color: '#EF4444' }}>🚩</Text>
        </View>
        <View className="flex-1">
          <Text className="text-gray-500 dark:text-gray-400 text-xs mb-0.5">
            ส่งที่
          </Text>
          <Text className="text-gray-800 dark:text-gray-200 text-sm" numberOfLines={2}>
            {job.delivery.address}
          </Text>
        </View>
      </View>

      {/* Distance & Info */}
      {job.distanceKm && (
        <View className="flex-row items-center mb-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
          <Text style={{ fontSize: 16, color: isDark ? '#9CA3AF' : '#6B7280' }}>🧭</Text>
          <Text className="text-gray-600 dark:text-gray-400 text-sm ml-2">
            ระยะทาง: {job.distanceKm.toFixed(1)} กม.
          </Text>
        </View>
      )}

      {/* Action Buttons */}
      <View className="flex-row">
        <Pressable
          onPress={() => onViewMap(job)}
          className="flex-1 mr-2 bg-gray-100 dark:bg-gray-700 rounded-xl py-3 flex-row items-center justify-center"
        >
          <Text style={{ fontSize: 18, color: isDark ? '#9CA3AF' : '#6B7280' }}>🗺️</Text>
          <Text className="text-gray-700 dark:text-gray-300 font-medium ml-2">
            ดูแผนที่
          </Text>
        </Pressable>

        <Pressable
          onPress={() => onAccept(job)}
          disabled={isAccepting}
          className={`flex-1 ml-2 bg-primary-500 rounded-xl py-3 flex-row items-center justify-center ${isAccepting ? 'opacity-50' : ''}`}
        >
          {isAccepting ? (
            <ActivityIndicator color="white" size="small" />
          ) : (
            <>
              <Text style={{ fontSize: 18, color: 'white' }}>✓</Text>
              <Text className="text-white font-bold ml-2">รับงาน</Text>
            </>
          )}
        </Pressable>
      </View>
    </Animated.View>
  );
};

// =====================================================
// Accept Job Modal
// =====================================================

const AcceptJobModal = ({
  visible,
  job,
  onClose,
  onConfirm,
  isLoading,
}: {
  visible: boolean;
  job: Job | null;
  onClose: () => void;
  onConfirm: () => void;
  isLoading: boolean;
}) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  if (!job) return null;

  return (
    <Modal visible={visible} transparent animationType="fade">
      <View className="flex-1 bg-black/60 justify-center items-center px-6">
        <Animated.View
          entering={ZoomIn.springify()}
          className="bg-white dark:bg-gray-800 rounded-3xl w-full max-w-md overflow-hidden"
        >
          {/* Header */}
          <LinearGradient
            colors={['#3B82F6', '#1D4ED8']}
            style={{
              padding: 24,
              alignItems: 'center',
            }}
          >
            <View className="w-20 h-20 bg-white/20 rounded-full items-center justify-center mb-4">
              <Text style={{ fontSize: 40, color: 'white' }}>🚴</Text>
            </View>
            <Text className="text-white text-xl font-bold text-center">
              ยืนยันรับงาน?
            </Text>
          </LinearGradient>

          {/* Content */}
          <View className="p-6">
            <View className="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 mb-4">
              <Text className="text-gray-800 dark:text-white font-bold text-lg mb-2">
                {job.title}
              </Text>
              <Text className="text-gray-500 dark:text-gray-400 text-sm">
                #{job.jobNumber} • {job.jobTypeText}
              </Text>
            </View>

            <View className="flex-row items-center justify-between mb-4 p-4 bg-green-50 dark:bg-green-900/30 rounded-xl">
              <Text className="text-green-800 dark:text-green-200">รายได้ที่จะได้รับ</Text>
              <Text className="text-green-600 dark:text-green-400 font-bold text-xl">
                {formatCurrency(job.riderEarnings)}
              </Text>
            </View>

            <View className="bg-yellow-50 dark:bg-yellow-900/30 rounded-xl p-4">
              <Text className="text-yellow-800 dark:text-yellow-200 text-sm">
                ⚠️ เมื่อรับงานแล้ว ตำแหน่งของคุณจะถูกแชร์กับลูกค้าจนกว่าจะส่งสำเร็จ
              </Text>
            </View>
          </View>

          {/* Actions */}
          <View className="flex-row p-4 border-t border-gray-200 dark:border-gray-700">
            <Pressable
              onPress={onClose}
              disabled={isLoading}
              className="flex-1 py-3 mr-2 bg-gray-100 dark:bg-gray-700 rounded-xl"
            >
              <Text className="text-gray-700 dark:text-gray-300 text-center font-medium">
                ยกเลิก
              </Text>
            </Pressable>
            <Pressable
              onPress={onConfirm}
              disabled={isLoading}
              className={`flex-1 py-3 ml-2 bg-primary-500 rounded-xl ${isLoading ? 'opacity-50' : ''}`}
            >
              {isLoading ? (
                <ActivityIndicator color="white" />
              ) : (
                <Text className="text-white text-center font-bold">รับงาน</Text>
              )}
            </Pressable>
          </View>
        </Animated.View>
      </View>
    </Modal>
  );
};

// =====================================================
// Empty State Component
// =====================================================

const EmptyState = ({ isDark }: { isDark: boolean }) => (
  <Animated.View
    entering={FadeIn.delay(200)}
    className="flex-1 justify-center items-center py-20"
  >
    <View className="w-24 h-24 rounded-full bg-gray-100 dark:bg-gray-700 items-center justify-center mb-4">
      <Text style={{ fontSize: 48, color: isDark ? '#4B5563' : '#9CA3AF' }}>📦</Text>
    </View>
    <Text className={`text-lg font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
      ยังไม่มีงาน
    </Text>
    <Text className="text-gray-500 text-center mt-2 px-8">
      งานใหม่จะแสดงที่นี่เมื่อมีลูกค้าสั่ง
    </Text>
  </Animated.View>
);

// =====================================================
// Main Component
// =====================================================

export default function RiderJobsScreen() {
  const { isAuthenticated } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const [jobs, setJobs] = useState<Job[]>([]);
  const [currentJob, setCurrentJob] = useState<{ hasJob: boolean; job?: Job } | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [isAccepting, setIsAccepting] = useState(false);
  const [selectedJob, setSelectedJob] = useState<Job | null>(null);
  const [showAcceptModal, setShowAcceptModal] = useState(false);
  const [hasError, setHasError] = useState(false);
  const [isRetrying, setIsRetrying] = useState(false);

  // Load Jobs
  const loadJobs = useCallback(async () => {
    try {
      setHasError(false);

      // Check for current job first
      const currentJobRes = await getCurrentJob();
      if (currentJobRes?.success && currentJobRes.data) {
        setCurrentJob(currentJobRes.data);
      }

      // Get available jobs
      const response = await getAvailableJobs();
      if (response?.success && response.data) {
        setJobs(response.data.jobs || []);
      } else if (!response?.success) {
        // API returned but with error
        setHasError(true);
      }
    } catch (error) {
      console.error('Load jobs error:', error);
      // Network error or API connection failed
      setHasError(true);
    } finally {
      setIsLoading(false);
      setIsRetrying(false);
    }
  }, []);

  useEffect(() => {
    if (isAuthenticated) {
      loadJobs();
    }
  }, [isAuthenticated, loadJobs]);

  // Pull to refresh
  const onRefresh = async () => {
    setRefreshing(true);
    await loadJobs();
    setRefreshing(false);
  };

  // Retry on error
  const handleRetry = async () => {
    setIsRetrying(true);
    setIsLoading(true);
    await loadJobs();
  };

  // Open map
  const handleViewMap = (job: Job) => {
    const { latitude, longitude } = job.pickup;
    const url = Platform.select({
      ios: `maps://?daddr=${latitude},${longitude}`,
      android: `google.navigation:q=${latitude},${longitude}`,
    });

    if (url) {
      Linking.openURL(url).catch(() => {
        // Fallback to Google Maps web
        Linking.openURL(
          `https://www.google.com/maps/dir/?api=1&destination=${latitude},${longitude}`
        );
      });
    }
  };

  // Accept job
  const handleAcceptPress = (job: Job) => {
    setSelectedJob(job);
    setShowAcceptModal(true);
  };

  const handleConfirmAccept = async () => {
    if (!selectedJob) return;

    setIsAccepting(true);
    try {
      const response = await acceptJob(selectedJob.id);
      if (response.success) {
        Alert.alert(
          '🎉 รับงานสำเร็จ!',
          response.data?.message || 'เริ่มนำทางไปรับของได้เลย',
          [
            {
              text: 'นำทางไปรับของ',
              onPress: () => {
                setShowAcceptModal(false);
                router.replace('/rider-job-detail');
              },
            },
          ]
        );
      } else {
        Alert.alert('ไม่สำเร็จ', response.message || 'ไม่สามารถรับงานได้');
      }
    } catch (error) {
      console.error('Accept job error:', error);
      Alert.alert('ข้อผิดพลาด', 'เกิดข้อผิดพลาด กรุณาลองใหม่');
    } finally {
      setIsAccepting(false);
    }
  };

  // Not authenticated
  if (!isAuthenticated) {
    return (
      <View style={{ flex: 1, backgroundColor: isDark ? '#0F172A' : '#F9FAFB' }}>
        <StatusBar barStyle="light-content" backgroundColor={isDark ? '#0F172A' : '#3B82F6'} />
        <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 24 }}>
          <Text style={{ fontSize: 80, color: isDark ? '#4B5563' : '#9CA3AF' }}>🔒</Text>
          <Text className={`text-xl font-bold mt-4 ${isDark ? 'text-white' : 'text-gray-800'}`}>
            กรุณาเข้าสู่ระบบ
          </Text>
          <Text className="text-gray-500 text-center mt-2">
            เข้าสู่ระบบเพื่อดูรายการงาน
          </Text>
          <Pressable
            onPress={() => router.push('/login')}
            className="bg-primary-500 px-8 py-3 rounded-xl mt-6"
          >
            <Text className="text-white font-bold">เข้าสู่ระบบ</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  return (
    <View style={{ flex: 1, backgroundColor: isDark ? '#0F172A' : '#F9FAFB' }}>
      <StatusBar barStyle="light-content" backgroundColor={isDark ? '#0F172A' : '#3B82F6'} />
      <View style={{ flex: 1 }}>
        {/* Header */}
        <View style={{ flexDirection: 'row', alignItems: 'center', paddingHorizontal: 20, paddingTop: 50, paddingBottom: 8 }}>
          <Pressable onPress={() => router.back()} className="mr-4">
            <Text style={{ fontSize: 24, color: isDark ? '#fff' : '#000' }}>←</Text>
          </Pressable>
          <View className="flex-1">
            <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
              งานที่รอรับ
            </Text>
            <Text className="text-gray-500 dark:text-gray-400 text-sm">
              {jobs.length} งาน
            </Text>
          </View>
          <Pressable
            onPress={onRefresh}
            className="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 items-center justify-center"
          >
            <Text style={{ fontSize: 20, color: isDark ? '#9CA3AF' : '#6B7280' }}>🔄</Text>
          </Pressable>
        </View>

        {/* Current Job Banner */}
        {currentJob?.hasJob && currentJob.job && (
          <Animated.View entering={FadeInDown.springify()}>
            <Pressable
              onPress={() => router.push('/rider-job-detail')}
              className="mx-5 mb-4"
            >
              <LinearGradient
                colors={['#10B981', '#059669']}
                style={{
                  borderRadius: 16,
                  padding: 16,
                  flexDirection: 'row',
                  alignItems: 'center',
                }}
              >
                <View className="w-12 h-12 bg-white/20 rounded-xl items-center justify-center mr-3">
                  <Text style={{ fontSize: 24, color: 'white' }}>🚴</Text>
                </View>
                <View className="flex-1">
                  <Text className="text-white font-bold">คุณมีงานอยู่!</Text>
                  <Text className="text-green-100 text-sm">
                    {currentJob.job.title}
                  </Text>
                </View>
                <Text style={{ fontSize: 24, color: 'white' }}>›</Text>
              </LinearGradient>
            </Pressable>
          </Animated.View>
        )}

        {/* Content */}
        {isLoading ? (
          <View className="flex-1 justify-center items-center">
            <ActivityIndicator size="large" color="#3B82F6" />
            <Text className="text-gray-500 mt-4">กำลังโหลดงาน...</Text>
          </View>
        ) : hasError ? (
          <ErrorState
            title="ไม่สามารถโหลดงานได้"
            message="ขณะนี้ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้\nโปรดลองใหม่ภายหลัง"
            onRetry={handleRetry}
            retryText={isRetrying ? 'กำลังลอง...' : 'ลองใหม่'}
            showRetry={!isRetrying}
          />
        ) : (
          <ScrollView
            className="flex-1 px-5"
            contentContainerClassName="pb-6"
            showsVerticalScrollIndicator={false}
            refreshControl={
              <RefreshControl
                refreshing={refreshing}
                onRefresh={onRefresh}
                tintColor="#3B82F6"
              />
            }
          >
            {jobs.length > 0 ? (
              jobs.map((job, index) => (
                <JobCard
                  key={job.id}
                  job={job}
                  index={index}
                  onAccept={handleAcceptPress}
                  onViewMap={handleViewMap}
                  isAccepting={isAccepting && selectedJob?.id === job.id}
                />
              ))
            ) : (
              <EmptyState isDark={isDark} />
            )}
          </ScrollView>
        )}
      </View>

      {/* Accept Job Modal */}
      <AcceptJobModal
        visible={showAcceptModal}
        job={selectedJob}
        onClose={() => setShowAcceptModal(false)}
        onConfirm={handleConfirmAccept}
        isLoading={isAccepting}
      />
    </View>
  );
}
