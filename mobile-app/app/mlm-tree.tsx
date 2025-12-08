/**
 * MLM Tree View - หน้าดูแผนผังสายงานแบบ Premium
 * Interactive tree visualization with touch gestures
 */

import React, { useEffect, useState, useCallback, useRef, useMemo } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  TextInput,
  RefreshControl,
  Animated,
  Easing,
  Dimensions,
  Modal,
  Alert,
  ActivityIndicator,
  Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import {
  getMLMTree,
  getTreeNodeChildren,
  getTeamMemberStats,
  searchTeamMember,
  TreeNode,
} from '@/services/api';
import { formatCurrency } from '@/constants';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

// =====================================================
// Types
// =====================================================

interface MemberStats {
  userId: number;
  name: string;
  email?: string;
  avatar?: string;
  rank?: {
    id: number;
    name: string;
    nameTh: string;
    icon?: string;
    color?: string;
  };
  statistics: {
    directReferrals: number;
    totalTeamMembers: number;
    monthlyPV: number;
    totalSales: number;
  };
  joinedAt: string;
  isActive: boolean;
}

// =====================================================
// Rank Badge Component
// =====================================================

const RankBadge = ({
  rank,
  size = 'small',
}: {
  rank?: TreeNode['rank'];
  size?: 'small' | 'medium' | 'large';
}) => {
  if (!rank) return null;

  const sizeClasses = {
    small: 'px-2 py-0.5 text-xs',
    medium: 'px-3 py-1 text-sm',
    large: 'px-4 py-1.5 text-base',
  };

  const bgColor = rank.color || '#3B82F6';

  return (
    <View
      style={{ backgroundColor: bgColor + '30' }}
      className={`rounded-full ${sizeClasses[size].split(' ').slice(0, 2).join(' ')}`}
    >
      <Text
        style={{ color: bgColor }}
        className={`font-semibold ${sizeClasses[size].split(' ').slice(2).join(' ')}`}
      >
        {rank.icon} {rank.nameTh || rank.name}
      </Text>
    </View>
  );
};

// =====================================================
// Tree Node Component - แสดงแต่ละ node ใน tree
// =====================================================

const TreeNodeItem = ({
  node,
  level,
  isExpanded,
  onToggle,
  onPress,
  isLoading,
  isDark,
  animValue,
}: {
  node: TreeNode;
  level: number;
  isExpanded: boolean;
  onToggle: () => void;
  onPress: () => void;
  isLoading: boolean;
  isDark: boolean;
  animValue: Animated.Value;
}) => {
  const hasChildren = node.childrenCount > 0 || (node.children && node.children.length > 0);
  const paddingLeft = Math.min(level * 16, 64); // Max indent

  // Animation for expand/collapse
  const rotateAnim = useRef(new Animated.Value(isExpanded ? 1 : 0)).current;

  useEffect(() => {
    Animated.timing(rotateAnim, {
      toValue: isExpanded ? 1 : 0,
      duration: 200,
      useNativeDriver: true,
    }).start();
  }, [isExpanded]);

  const rotation = rotateAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '90deg'],
  });

  // Get status color
  const statusColor = node.isActive ? '#10B981' : '#6B7280';
  const statusBg = node.isActive ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-700';

  return (
    <Animated.View
      style={{
        opacity: animValue,
        transform: [
          {
            translateX: animValue.interpolate({
              inputRange: [0, 1],
              outputRange: [-20, 0],
            }),
          },
        ],
      }}
    >
      <Pressable
        onPress={onPress}
        className={`mb-2 mx-4 rounded-2xl overflow-hidden ${
          isDark ? 'bg-gray-800/80' : 'bg-white'
        }`}
        style={{
          marginLeft: 16 + paddingLeft,
          shadowColor: node.isActive ? '#3B82F6' : '#000',
          shadowOffset: { width: 0, height: 2 },
          shadowOpacity: isDark ? 0.3 : 0.1,
          shadowRadius: 8,
          elevation: 3,
        }}
      >
        {/* Gradient top border */}
        {level === 0 && (
          <LinearGradient
            colors={['#3B82F6', '#8B5CF6']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={{ height: 4 }}
          />
        )}

        <View className="p-4 flex-row items-center">
          {/* Expand/Collapse button */}
          {hasChildren ? (
            <Pressable
              onPress={onToggle}
              className={`w-8 h-8 rounded-full items-center justify-center mr-3 ${
                isDark ? 'bg-gray-700' : 'bg-gray-100'
              }`}
            >
              {isLoading ? (
                <ActivityIndicator size="small" color="#3B82F6" />
              ) : (
                <Animated.View style={{ transform: [{ rotate: rotation }] }}>
                  <Ionicons
                    name="chevron-forward"
                    size={18}
                    color={isDark ? '#9CA3AF' : '#6B7280'}
                  />
                </Animated.View>
              )}
            </Pressable>
          ) : (
            <View className="w-8 h-8 mr-3" />
          )}

          {/* Avatar */}
          <View className="relative">
            {node.avatar ? (
              <View className="w-12 h-12 rounded-full overflow-hidden border-2 border-primary-400">
                {/* Image would go here - using placeholder */}
                <View className="w-full h-full bg-primary-100 items-center justify-center">
                  <Text className="text-primary-600 font-bold text-lg">
                    {node.name.charAt(0).toUpperCase()}
                  </Text>
                </View>
              </View>
            ) : (
              <LinearGradient
                colors={['#3B82F6', '#8B5CF6']}
                style={{
                  width: 48,
                  height: 48,
                  borderRadius: 9999,
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                <Text className="text-white font-bold text-lg">
                  {node.name.charAt(0).toUpperCase()}
                </Text>
              </LinearGradient>
            )}

            {/* Active indicator */}
            <View
              className={`absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full border-2 ${
                isDark ? 'border-gray-800' : 'border-white'
              }`}
              style={{ backgroundColor: statusColor }}
            />
          </View>

          {/* Info */}
          <View className="flex-1 ml-3">
            <View className="flex-row items-center flex-wrap">
              <Text
                className={`font-semibold text-base ${
                  isDark ? 'text-white' : 'text-gray-900'
                }`}
                numberOfLines={1}
              >
                {node.name}
              </Text>
              {node.rank && (
                <View className="ml-2">
                  <RankBadge rank={node.rank} />
                </View>
              )}
            </View>

            <Text
              className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}
              numberOfLines={1}
            >
              ระดับ {node.level} • {node.childrenCount || 0} คนในทีม
            </Text>
          </View>

          {/* Stats */}
          <View className="items-end">
            {node.statistics?.monthlyPV !== undefined && (
              <Text className="text-primary-500 font-bold text-sm">
                {node.statistics.monthlyPV.toLocaleString()} PV
              </Text>
            )}
            <View className={`px-2 py-0.5 rounded-full mt-1 ${statusBg}`}>
              <Text style={{ color: statusColor }} className="text-xs font-medium">
                {node.isActive ? 'ใช้งาน' : 'ไม่ใช้งาน'}
              </Text>
            </View>
          </View>

          {/* Arrow */}
          <Ionicons
            name="chevron-forward"
            size={20}
            color={isDark ? '#4B5563' : '#9CA3AF'}
            style={{ marginLeft: 8 }}
          />
        </View>
      </Pressable>
    </Animated.View>
  );
};

// =====================================================
// Member Detail Modal
// =====================================================

const MemberDetailModal = ({
  visible,
  member,
  onClose,
  onViewTree,
  isDark,
}: {
  visible: boolean;
  member: MemberStats | null;
  onClose: () => void;
  onViewTree: () => void;
  isDark: boolean;
}) => {
  if (!member) return null;

  const statusColor = member.isActive ? '#10B981' : '#6B7280';

  return (
    <Modal
      visible={visible}
      animationType="slide"
      transparent
      onRequestClose={onClose}
    >
      <View className="flex-1 justify-end">
        {/* Backdrop */}
        <Pressable
          onPress={onClose}
          className="absolute inset-0 bg-black/50"
        />

        {/* Content */}
        <View
          className={`rounded-t-3xl ${isDark ? 'bg-gray-900' : 'bg-white'}`}
          style={{
            shadowColor: '#000',
            shadowOffset: { width: 0, height: -4 },
            shadowOpacity: 0.1,
            shadowRadius: 12,
            elevation: 20,
          }}
        >
          {/* Handle */}
          <View className="items-center pt-3 pb-2">
            <View className={`w-10 h-1 rounded-full ${isDark ? 'bg-gray-700' : 'bg-gray-300'}`} />
          </View>

          {/* Header */}
          <View className="items-center px-6 pb-4">
            {/* Avatar */}
            <View className="relative mb-3">
              <LinearGradient
                colors={['#3B82F6', '#8B5CF6', '#06B6D4']}
                style={{
                  width: 96,
                  height: 96,
                  borderRadius: 9999,
                  padding: 4,
                }}
              >
                <View className={`w-full h-full rounded-full items-center justify-center ${isDark ? 'bg-gray-800' : 'bg-white'}`}>
                  <Text className="text-primary-500 font-bold text-3xl">
                    {member.name.charAt(0).toUpperCase()}
                  </Text>
                </View>
              </LinearGradient>

              {/* Status */}
              <View
                className={`absolute bottom-0 right-0 w-6 h-6 rounded-full border-4 ${isDark ? 'border-gray-900' : 'border-white'}`}
                style={{ backgroundColor: statusColor }}
              />
            </View>

            <Text className={`text-xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
              {member.name}
            </Text>

            {member.email && (
              <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                {member.email}
              </Text>
            )}

            {member.rank && (
              <View className="mt-2">
                <RankBadge rank={member.rank} size="medium" />
              </View>
            )}
          </View>

          {/* Stats Grid */}
          <View className="flex-row flex-wrap px-4 pb-4">
            <View className="w-1/2 p-2">
              <View
                className={`rounded-2xl p-4 ${isDark ? 'bg-gray-800' : 'bg-gray-50'}`}
              >
                <View className="w-10 h-10 rounded-xl bg-blue-500/20 items-center justify-center mb-2">
                  <Ionicons name="people" size={20} color="#3B82F6" />
                </View>
                <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                  {member.statistics.directReferrals}
                </Text>
                <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                  สมาชิกโดยตรง
                </Text>
              </View>
            </View>

            <View className="w-1/2 p-2">
              <View
                className={`rounded-2xl p-4 ${isDark ? 'bg-gray-800' : 'bg-gray-50'}`}
              >
                <View className="w-10 h-10 rounded-xl bg-purple-500/20 items-center justify-center mb-2">
                  <Ionicons name="git-network" size={20} color="#8B5CF6" />
                </View>
                <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                  {member.statistics.totalTeamMembers}
                </Text>
                <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                  สมาชิกในทีมทั้งหมด
                </Text>
              </View>
            </View>

            <View className="w-1/2 p-2">
              <View
                className={`rounded-2xl p-4 ${isDark ? 'bg-gray-800' : 'bg-gray-50'}`}
              >
                <View className="w-10 h-10 rounded-xl bg-green-500/20 items-center justify-center mb-2">
                  <Ionicons name="trending-up" size={20} color="#10B981" />
                </View>
                <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                  {member.statistics.monthlyPV.toLocaleString()}
                </Text>
                <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                  PV เดือนนี้
                </Text>
              </View>
            </View>

            <View className="w-1/2 p-2">
              <View
                className={`rounded-2xl p-4 ${isDark ? 'bg-gray-800' : 'bg-gray-50'}`}
              >
                <View className="w-10 h-10 rounded-xl bg-orange-500/20 items-center justify-center mb-2">
                  <Ionicons name="cash" size={20} color="#F97316" />
                </View>
                <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                  {formatCurrency(member.statistics.totalSales)}
                </Text>
                <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                  ยอดขายทั้งหมด
                </Text>
              </View>
            </View>
          </View>

          {/* Info */}
          <View className={`mx-6 mb-4 p-4 rounded-2xl ${isDark ? 'bg-gray-800' : 'bg-gray-50'}`}>
            <View className="flex-row items-center mb-2">
              <Ionicons name="calendar" size={18} color={isDark ? '#9CA3AF' : '#6B7280'} />
              <Text className={`ml-2 ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                สมัครเมื่อ: {new Date(member.joinedAt).toLocaleDateString('th-TH', {
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric',
                })}
              </Text>
            </View>
            <View className="flex-row items-center">
              <View
                className="w-3 h-3 rounded-full mr-2"
                style={{ backgroundColor: statusColor }}
              />
              <Text className={`${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                สถานะ: {member.isActive ? 'ใช้งานอยู่' : 'ไม่ได้ใช้งาน'}
              </Text>
            </View>
          </View>

          {/* Actions */}
          <View className="px-6 pb-8">
            <Pressable onPress={onViewTree}>
              <LinearGradient
                colors={['#8B5CF6', '#6D28D9']}
                style={{
                  borderRadius: 16,
                  paddingVertical: 16,
                  flexDirection: 'row',
                  alignItems: 'center',
                  justifyContent: 'center',
                  shadowColor: '#8B5CF6',
                  shadowOffset: { width: 0, height: 4 },
                  shadowOpacity: 0.3,
                  shadowRadius: 8,
                  elevation: 8,
                }}
              >
                <Ionicons name="git-network" size={20} color="white" />
                <Text className="text-white font-bold text-base ml-2">
                  ดูสายงานของสมาชิกนี้
                </Text>
              </LinearGradient>
            </Pressable>

            <Pressable
              onPress={onClose}
              className={`mt-3 rounded-2xl py-4 items-center ${
                isDark ? 'bg-gray-800' : 'bg-gray-100'
              }`}
            >
              <Text className={`font-semibold ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                ปิด
              </Text>
            </Pressable>
          </View>

          {/* Safe area for bottom */}
          <SafeAreaView edges={['bottom']} />
        </View>
      </View>
    </Modal>
  );
};

// =====================================================
// Search Component
// =====================================================

const SearchBar = ({
  value,
  onChangeText,
  onSearch,
  isSearching,
  isDark,
}: {
  value: string;
  onChangeText: (text: string) => void;
  onSearch: () => void;
  isSearching: boolean;
  isDark: boolean;
}) => {
  return (
    <View
      className={`mx-4 mb-4 rounded-2xl flex-row items-center px-4 ${
        isDark ? 'bg-gray-800' : 'bg-white'
      }`}
      style={{
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.05,
        shadowRadius: 8,
        elevation: 2,
      }}
    >
      <Ionicons
        name="search"
        size={20}
        color={isDark ? '#6B7280' : '#9CA3AF'}
      />
      <TextInput
        value={value}
        onChangeText={onChangeText}
        onSubmitEditing={onSearch}
        placeholder="ค้นหาสมาชิก..."
        placeholderTextColor={isDark ? '#6B7280' : '#9CA3AF'}
        className={`flex-1 py-4 px-3 text-base ${
          isDark ? 'text-white' : 'text-gray-900'
        }`}
        returnKeyType="search"
      />
      {isSearching ? (
        <ActivityIndicator size="small" color="#3B82F6" />
      ) : value.length > 0 ? (
        <Pressable onPress={() => onChangeText('')}>
          <Ionicons
            name="close-circle"
            size={20}
            color={isDark ? '#6B7280' : '#9CA3AF'}
          />
        </Pressable>
      ) : null}
    </View>
  );
};

// =====================================================
// Stats Summary Component
// =====================================================

const StatsSummary = ({
  stats,
  isDark,
}: {
  stats: {
    totalMembers: number;
    activeMembers: number;
    totalPV: number;
    levels: number;
  };
  isDark: boolean;
}) => {
  const summaryItems = [
    {
      icon: 'people',
      value: stats.totalMembers.toString(),
      label: 'สมาชิกทั้งหมด',
      color: '#3B82F6',
    },
    {
      icon: 'checkmark-circle',
      value: stats.activeMembers.toString(),
      label: 'ใช้งานอยู่',
      color: '#10B981',
    },
    {
      icon: 'trending-up',
      value: stats.totalPV.toLocaleString(),
      label: 'PV รวม',
      color: '#8B5CF6',
    },
    {
      icon: 'layers',
      value: stats.levels.toString(),
      label: 'ระดับ',
      color: '#F97316',
    },
  ];

  return (
    <View className="mb-4">
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={{ paddingHorizontal: 12 }}
      >
        {summaryItems.map((item, index) => (
          <View
            key={index}
            className={`rounded-2xl p-4 mr-3 ${
              isDark ? 'bg-gray-800/80' : 'bg-white'
            }`}
            style={{
              minWidth: 130,
              shadowColor: item.color,
              shadowOffset: { width: 0, height: 2 },
              shadowOpacity: 0.1,
              shadowRadius: 8,
              elevation: 3,
            }}
          >
            <View
              className="w-10 h-10 rounded-xl items-center justify-center mb-2"
              style={{ backgroundColor: item.color + '20' }}
            >
              <Ionicons name={item.icon as any} size={20} color={item.color} />
            </View>
            <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
              {item.value}
            </Text>
            <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
              {item.label}
            </Text>
          </View>
        ))}
      </ScrollView>
    </View>
  );
};

// =====================================================
// Main Component
// =====================================================

export default function MLMTreeScreen() {
  const { user, isAuthenticated } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  // State
  const [tree, setTree] = useState<TreeNode | null>(null);
  const [expandedNodes, setExpandedNodes] = useState<Set<number>>(new Set());
  const [loadingNodes, setLoadingNodes] = useState<Set<number>>(new Set());
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [isSearching, setIsSearching] = useState(false);
  const [searchResults, setSearchResults] = useState<TreeNode[]>([]);
  const [selectedMember, setSelectedMember] = useState<MemberStats | null>(null);
  const [showMemberModal, setShowMemberModal] = useState(false);
  const [viewingUserId, setViewingUserId] = useState<number | null>(null);

  // Animation
  const fadeAnim = useRef(new Animated.Value(0)).current;

  // Stats calculation
  const stats = useMemo(() => {
    if (!tree) return { totalMembers: 0, activeMembers: 0, totalPV: 0, levels: 0 };

    let total = 0;
    let active = 0;
    let pv = 0;
    let maxLevel = 0;

    const countNodes = (node: TreeNode) => {
      total++;
      if (node.isActive) active++;
      if (node.statistics?.monthlyPV) pv += node.statistics.monthlyPV;
      if (node.level > maxLevel) maxLevel = node.level;
      node.children?.forEach(countNodes);
    };

    countNodes(tree);

    return {
      totalMembers: total,
      activeMembers: active,
      totalPV: pv,
      levels: maxLevel,
    };
  }, [tree]);

  // Load tree data
  const loadTree = useCallback(async (userId?: number) => {
    if (!isAuthenticated) {
      setIsLoading(false);
      return;
    }

    try {
      setIsLoading(true);
      const response = await getMLMTree({ userId, depth: 2 });

      if (response.success && response.data) {
        setTree(response.data);
        setViewingUserId(userId || null);

        // Animate in
        Animated.timing(fadeAnim, {
          toValue: 1,
          duration: 500,
          easing: Easing.out(Easing.ease),
          useNativeDriver: true,
        }).start();
      }
    } catch (error) {
      console.error('Load MLM tree error:', error);
      Alert.alert('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลสายงานได้');
    } finally {
      setIsLoading(false);
    }
  }, [isAuthenticated]);

  // Initial load
  useEffect(() => {
    loadTree();
  }, [loadTree]);

  // Refresh
  const handleRefresh = useCallback(async () => {
    setRefreshing(true);
    await loadTree(viewingUserId || undefined);
    setRefreshing(false);
  }, [loadTree, viewingUserId]);

  // Toggle node expansion
  const toggleNode = useCallback(async (node: TreeNode) => {
    const nodeId = node.id;

    if (expandedNodes.has(nodeId)) {
      // Collapse
      setExpandedNodes((prev) => {
        const next = new Set(prev);
        next.delete(nodeId);
        return next;
      });
    } else {
      // Expand - load children if needed
      if (node.children?.length === 0 && node.childrenCount > 0) {
        setLoadingNodes((prev) => new Set(prev).add(nodeId));

        try {
          const response = await getTreeNodeChildren(nodeId);
          if (response.success && response.data) {
            // Update tree with new children
            setTree((prevTree) => {
              if (!prevTree) return null;

              const updateNode = (n: TreeNode): TreeNode => {
                if (n.id === nodeId) {
                  return { ...n, children: response.data! };
                }
                return {
                  ...n,
                  children: n.children?.map(updateNode) || [],
                };
              };

              return updateNode(prevTree);
            });
          }
        } catch (error) {
          console.error('Load children error:', error);
        } finally {
          setLoadingNodes((prev) => {
            const next = new Set(prev);
            next.delete(nodeId);
            return next;
          });
        }
      }

      setExpandedNodes((prev) => new Set(prev).add(nodeId));
    }
  }, [expandedNodes]);

  // Press node - show details
  const handleNodePress = useCallback(async (node: TreeNode) => {
    try {
      const response = await getTeamMemberStats(node.id);
      if (response.success && response.data) {
        setSelectedMember(response.data as MemberStats);
        setShowMemberModal(true);
      }
    } catch (error) {
      console.error('Get member stats error:', error);
      // Show basic info if API fails
      setSelectedMember({
        userId: node.id,
        name: node.name,
        email: node.email,
        avatar: node.avatar,
        rank: node.rank,
        statistics: node.statistics || {
          directReferrals: node.childrenCount,
          totalTeamMembers: 0,
          monthlyPV: 0,
          totalSales: 0,
        },
        joinedAt: node.joinedAt,
        isActive: node.isActive,
      });
      setShowMemberModal(true);
    }
  }, []);

  // Search
  const handleSearch = useCallback(async () => {
    if (searchQuery.length < 2) {
      setSearchResults([]);
      return;
    }

    setIsSearching(true);
    try {
      const response = await searchTeamMember(searchQuery);
      if (response.success && response.data) {
        setSearchResults(response.data);
      }
    } catch (error) {
      console.error('Search error:', error);
    } finally {
      setIsSearching(false);
    }
  }, [searchQuery]);

  // Effect for debounced search
  useEffect(() => {
    const timer = setTimeout(() => {
      if (searchQuery.length >= 2) {
        handleSearch();
      } else {
        setSearchResults([]);
      }
    }, 500);

    return () => clearTimeout(timer);
  }, [searchQuery, handleSearch]);

  // View member's tree
  const handleViewMemberTree = useCallback(() => {
    if (selectedMember) {
      setShowMemberModal(false);
      loadTree(selectedMember.userId);
    }
  }, [selectedMember, loadTree]);

  // Render tree recursively
  const renderTree = useCallback((node: TreeNode, level: number = 0): React.ReactNode => {
    const nodeAnim = new Animated.Value(1);
    const isExpanded = expandedNodes.has(node.id);
    const isLoadingNode = loadingNodes.has(node.id);

    return (
      <View key={node.id}>
        <TreeNodeItem
          node={node}
          level={level}
          isExpanded={isExpanded}
          onToggle={() => toggleNode(node)}
          onPress={() => handleNodePress(node)}
          isLoading={isLoadingNode}
          isDark={isDark}
          animValue={nodeAnim}
        />

        {isExpanded &&
          node.children?.map((child) => renderTree(child, level + 1))}
      </View>
    );
  }, [expandedNodes, loadingNodes, toggleNode, handleNodePress, isDark]);

  // Not authenticated
  if (!isAuthenticated) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1 justify-center items-center px-6">
          <Ionicons
            name="git-network"
            size={80}
            color={isDark ? '#4B5563' : '#9CA3AF'}
          />
          <Text
            className={`text-xl font-bold mt-4 ${isDark ? 'text-white' : 'text-gray-800'}`}
          >
            แผนผังสายงาน
          </Text>
          <Text className="text-gray-500 text-center mt-2">
            เข้าสู่ระบบเพื่อดูแผนผังสายงานของคุณ
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
      <SafeAreaView className="flex-1" edges={['top']}>
        {/* Header */}
        <View className="px-5 pt-4 pb-3">
          <View className="flex-row items-center justify-between">
            <Pressable
              onPress={() => router.back()}
              className={`w-10 h-10 rounded-xl items-center justify-center ${
                isDark ? 'bg-gray-800' : 'bg-white'
              }`}
            >
              <Ionicons
                name="arrow-back"
                size={24}
                color={isDark ? '#fff' : '#374151'}
              />
            </Pressable>

            <View className="flex-1 mx-4">
              <Text
                className={`text-xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}
              >
                {viewingUserId ? 'สายงานสมาชิก' : 'แผนผังสายงาน'}
              </Text>
              <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                ดูโครงสร้างทีมของคุณ
              </Text>
            </View>

            {viewingUserId && (
              <Pressable
                onPress={() => loadTree()}
                className={`px-4 py-2 rounded-xl ${
                  isDark ? 'bg-gray-800' : 'bg-white'
                }`}
              >
                <Text className="text-primary-500 font-semibold text-sm">
                  กลับหน้าหลัก
                </Text>
              </Pressable>
            )}
          </View>
        </View>

        {/* Search */}
        <SearchBar
          value={searchQuery}
          onChangeText={setSearchQuery}
          onSearch={handleSearch}
          isSearching={isSearching}
          isDark={isDark}
        />

        {/* Content */}
        {isLoading ? (
          <View className="flex-1 items-center justify-center">
            <ActivityIndicator size="large" color="#3B82F6" />
            <Text className={`mt-4 ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
              กำลังโหลดข้อมูลสายงาน...
            </Text>
          </View>
        ) : searchResults.length > 0 ? (
          // Search Results
          <ScrollView
            className="flex-1"
            contentContainerStyle={{ paddingBottom: 20 }}
            showsVerticalScrollIndicator={false}
          >
            <Text className={`mx-4 mb-3 font-semibold ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
              ผลการค้นหา ({searchResults.length} รายการ)
            </Text>
            {searchResults.map((node) => (
              <TreeNodeItem
                key={node.id}
                node={node}
                level={0}
                isExpanded={false}
                onToggle={() => {}}
                onPress={() => handleNodePress(node)}
                isLoading={false}
                isDark={isDark}
                animValue={fadeAnim}
              />
            ))}
          </ScrollView>
        ) : tree ? (
          <Animated.View className="flex-1" style={{ opacity: fadeAnim }}>
            <ScrollView
              className="flex-1"
              contentContainerStyle={{ paddingBottom: 20 }}
              showsVerticalScrollIndicator={false}
              refreshControl={
                <RefreshControl
                  refreshing={refreshing}
                  onRefresh={handleRefresh}
                  tintColor="#3B82F6"
                />
              }
            >
              {/* Stats Summary */}
              <StatsSummary stats={stats} isDark={isDark} />

              {/* Tree Header */}
              <View className="mx-4 mb-3 flex-row items-center">
                <View className="w-1 h-6 rounded-full bg-primary-500 mr-3" />
                <Text className={`font-bold text-base ${isDark ? 'text-white' : 'text-gray-800'}`}>
                  โครงสร้างทีม
                </Text>
              </View>

              {/* Tree */}
              {renderTree(tree)}
            </ScrollView>
          </Animated.View>
        ) : (
          <View className="flex-1 items-center justify-center px-6">
            <Ionicons
              name="people-outline"
              size={64}
              color={isDark ? '#4B5563' : '#9CA3AF'}
            />
            <Text className={`text-lg font-semibold mt-4 ${isDark ? 'text-white' : 'text-gray-800'}`}>
              ยังไม่มีสมาชิกในทีม
            </Text>
            <Text className="text-gray-500 text-center mt-2">
              เริ่มแนะนำเพื่อนเพื่อสร้างทีมของคุณ
            </Text>
            <Pressable
              onPress={() => router.push('/referral')}
              className="mt-4"
            >
              <LinearGradient
                colors={['#8B5CF6', '#6D28D9']}
                style={{
                  paddingHorizontal: 24,
                  paddingVertical: 12,
                  borderRadius: 12,
                }}
              >
                <Text className="text-white font-bold">แนะนำเพื่อน</Text>
              </LinearGradient>
            </Pressable>
          </View>
        )}
      </SafeAreaView>

      {/* Member Detail Modal */}
      <MemberDetailModal
        visible={showMemberModal}
        member={selectedMember}
        onClose={() => setShowMemberModal(false)}
        onViewTree={handleViewMemberTree}
        isDark={isDark}
      />
    </View>
  );
}
