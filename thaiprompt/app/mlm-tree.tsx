/**
 * MLM Tree View - หน้าดูแผนผังสายงาน
 * ใช้ StyleSheet แทน NativeWind (แก้ปัญหาจอขาว)
 * แสดงตัวเองเมื่อไม่มี downline
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
  StyleSheet,
  StatusBar,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
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

  const sizeStyles = {
    small: { paddingH: 8, paddingV: 2, fontSize: 11 },
    medium: { paddingH: 12, paddingV: 4, fontSize: 13 },
    large: { paddingH: 16, paddingV: 6, fontSize: 15 },
  };

  const bgColor = rank.color || '#3B82F6';
  const s = sizeStyles[size];

  return (
    <View
      style={[
        styles.rankBadge,
        {
          backgroundColor: bgColor + '30',
          paddingHorizontal: s.paddingH,
          paddingVertical: s.paddingV,
        },
      ]}
    >
      <Text style={[styles.rankBadgeText, { color: bgColor, fontSize: s.fontSize }]}>
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
  animValue,
  isRoot = false,
}: {
  node: TreeNode;
  level: number;
  isExpanded: boolean;
  onToggle: () => void;
  onPress: () => void;
  isLoading: boolean;
  animValue: Animated.Value;
  isRoot?: boolean;
}) => {
  const hasChildren = node.childrenCount > 0 || (node.children && node.children.length > 0);
  const paddingLeft = Math.min(level * 16, 64);

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

  const statusColor = node.isActive ? '#10B981' : '#6B7280';

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
        style={[
          styles.nodeCard,
          { marginLeft: 16 + paddingLeft },
          isRoot && styles.nodeCardRoot,
        ]}
      >
        {/* Gradient top border for root */}
        {level === 0 && (
          <LinearGradient
            colors={['#3B82F6', '#8B5CF6']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.nodeGradientBorder}
          />
        )}

        <View style={styles.nodeContent}>
          {/* Expand/Collapse button */}
          {hasChildren ? (
            <Pressable onPress={onToggle} style={styles.expandBtn}>
              {isLoading ? (
                <ActivityIndicator size="small" color="#3B82F6" />
              ) : (
                <Animated.View style={{ transform: [{ rotate: rotation }] }}>
                  <Text style={{ fontSize: 18, color: '#9CA3AF' }}>▶</Text>
                </Animated.View>
              )}
            </Pressable>
          ) : (
            <View style={styles.expandBtnPlaceholder} />
          )}

          {/* Avatar */}
          {/* ⭐ เพิ่ม null check เพื่อป้องกัน crash */}
          <View style={styles.avatarContainer}>
            <LinearGradient
              colors={isRoot ? ['#F59E0B', '#EF4444'] : ['#3B82F6', '#8B5CF6']}
              style={styles.avatar}
            >
              <Text style={styles.avatarText}>
                {(node?.name || 'U').charAt(0).toUpperCase()}
              </Text>
            </LinearGradient>

            {/* Active indicator */}
            <View style={[styles.activeIndicator, { backgroundColor: statusColor }]} />
          </View>

          {/* Info */}
          <View style={styles.nodeInfo}>
            <View style={styles.nameRow}>
              <Text style={styles.nodeName} numberOfLines={1}>
                {node?.name || 'ไม่ระบุชื่อ'}
                {isRoot && ' (คุณ)'}
              </Text>
              {node.rank && (
                <View style={styles.rankContainer}>
                  <RankBadge rank={node.rank} />
                </View>
              )}
            </View>

            <Text style={styles.nodeSubtitle} numberOfLines={1}>
              ระดับ {node.level} • {node.childrenCount || 0} คนในทีม
            </Text>
          </View>

          {/* Stats */}
          <View style={styles.nodeStats}>
            {node.statistics?.monthlyPV !== undefined && (
              <Text style={styles.pvText}>
                {node.statistics.monthlyPV.toLocaleString()} PV
              </Text>
            )}
            <View style={[styles.statusBadge, { backgroundColor: statusColor + '20' }]}>
              <Text style={[styles.statusText, { color: statusColor }]}>
                {node.isActive ? 'ใช้งาน' : 'ไม่ใช้งาน'}
              </Text>
            </View>
          </View>

          <Text style={{ fontSize: 20, color: '#6B7280', marginLeft: 8 }}>▶</Text>
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
}: {
  visible: boolean;
  member: MemberStats | null;
  onClose: () => void;
  onViewTree: () => void;
}) => {
  if (!member) return null;

  const statusColor = member.isActive ? '#10B981' : '#6B7280';

  return (
    <Modal visible={visible} animationType="slide" transparent onRequestClose={onClose}>
      <View style={styles.modalOverlay}>
        <Pressable onPress={onClose} style={StyleSheet.absoluteFill} />

        <View style={styles.modalContent}>
          {/* Handle */}
          <View style={styles.modalHandle}>
            <View style={styles.modalHandleBar} />
          </View>

          {/* Header */}
          {/* ⭐ เพิ่ม null check เพื่อป้องกัน crash */}
          <View style={styles.modalHeader}>
            <View style={styles.modalAvatarContainer}>
              <LinearGradient
                colors={['#3B82F6', '#8B5CF6', '#06B6D4']}
                style={styles.modalAvatarGradient}
              >
                <View style={styles.modalAvatarInner}>
                  <Text style={styles.modalAvatarText}>
                    {(member?.name || 'U').charAt(0).toUpperCase()}
                  </Text>
                </View>
              </LinearGradient>
              <View style={[styles.modalStatusDot, { backgroundColor: statusColor }]} />
            </View>

            <Text style={styles.modalName}>{member?.name || 'ไม่ระบุชื่อ'}</Text>
            {member?.email && <Text style={styles.modalEmail}>{member.email}</Text>}
            {member.rank && (
              <View style={styles.modalRankContainer}>
                <RankBadge rank={member.rank} size="medium" />
              </View>
            )}
          </View>

          {/* Stats Grid */}
          <View style={styles.modalStatsGrid}>
            {[
              { emoji: '👥', value: member.statistics.directReferrals, label: 'สมาชิกโดยตรง', color: '#3B82F6' },
              { emoji: '🌐', value: member.statistics.totalTeamMembers, label: 'สมาชิกในทีม', color: '#8B5CF6' },
              { emoji: '📈', value: member.statistics.monthlyPV.toLocaleString(), label: 'PV เดือนนี้', color: '#10B981' },
              { emoji: '💰', value: formatCurrency(member.statistics.totalSales), label: 'ยอดขายรวม', color: '#F97316' },
            ].map((stat, i) => (
              <View key={i} style={styles.modalStatItem}>
                <View style={[styles.modalStatIcon, { backgroundColor: stat.color + '20' }]}>
                  <Text style={{ fontSize: 20 }}>{stat.emoji}</Text>
                </View>
                <Text style={styles.modalStatValue}>{stat.value}</Text>
                <Text style={styles.modalStatLabel}>{stat.label}</Text>
              </View>
            ))}
          </View>

          {/* Info */}
          <View style={styles.modalInfoCard}>
            <View style={styles.modalInfoRow}>
              <Text style={{ fontSize: 18 }}>📅</Text>
              <Text style={styles.modalInfoText}>
                สมัครเมื่อ: {new Date(member.joinedAt).toLocaleDateString('th-TH', {
                  year: 'numeric', month: 'long', day: 'numeric',
                })}
              </Text>
            </View>
            <View style={styles.modalInfoRow}>
              <View style={[styles.modalStatusIndicator, { backgroundColor: statusColor }]} />
              <Text style={styles.modalInfoText}>
                สถานะ: {member.isActive ? 'ใช้งานอยู่' : 'ไม่ได้ใช้งาน'}
              </Text>
            </View>
          </View>

          {/* Actions */}
          <View style={styles.modalActions}>
            <Pressable onPress={onViewTree}>
              <LinearGradient
                colors={['#8B5CF6', '#6D28D9']}
                style={styles.modalViewTreeBtn}
              >
                <Text style={{ fontSize: 20, color: 'white' }}>🌐</Text>
                <Text style={styles.modalViewTreeText}>ดูสายงานของสมาชิกนี้</Text>
              </LinearGradient>
            </Pressable>

            <Pressable onPress={onClose} style={styles.modalCloseBtn}>
              <Text style={styles.modalCloseText}>ปิด</Text>
            </Pressable>
          </View>

          <View style={{ height: 24 }} />
        </View>
      </View>
    </Modal>
  );
};

// =====================================================
// Stats Summary Component
// =====================================================

const StatsSummary = ({
  stats,
}: {
  stats: { totalMembers: number; activeMembers: number; totalPV: number; levels: number };
}) => {
  const summaryItems = [
    { emoji: '👥', value: stats.totalMembers.toString(), label: 'สมาชิกทั้งหมด', color: '#3B82F6' },
    { emoji: '✓', value: stats.activeMembers.toString(), label: 'ใช้งานอยู่', color: '#10B981' },
    { emoji: '📈', value: stats.totalPV.toLocaleString(), label: 'PV รวม', color: '#8B5CF6' },
    { emoji: '📊', value: stats.levels.toString(), label: 'ระดับ', color: '#F97316' },
  ];

  return (
    <View style={styles.statsSummary}>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ paddingHorizontal: 12 }}>
        {summaryItems.map((item, index) => (
          <View key={index} style={styles.statsSummaryItem}>
            <View style={[styles.statsSummaryIcon, { backgroundColor: item.color + '20' }]}>
              <Text style={{ fontSize: 20 }}>{item.emoji}</Text>
            </View>
            <Text style={styles.statsSummaryValue}>{item.value}</Text>
            <Text style={styles.statsSummaryLabel}>{item.label}</Text>
          </View>
        ))}
      </ScrollView>
    </View>
  );
};

// =====================================================
// Self Card - แสดงตัวเองเมื่อไม่มี downline
// =====================================================

const SelfCard = ({ user }: { user: { id: number; name: string; email?: string } }) => {
  return (
    <View style={styles.selfCardContainer}>
      <LinearGradient
        colors={['rgba(139, 92, 246, 0.15)', 'rgba(59, 130, 246, 0.1)']}
        style={styles.selfCard}
      >
        {/* Gradient border */}
        <LinearGradient
          colors={['#F59E0B', '#EF4444', '#EC4899']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          style={styles.selfCardBorder}
        />

        <View style={styles.selfCardContent}>
          {/* Avatar */}
          {/* ⭐ เพิ่ม null check เพื่อป้องกัน crash */}
          <LinearGradient colors={['#F59E0B', '#EF4444']} style={styles.selfAvatar}>
            <Text style={styles.selfAvatarText}>{(user?.name || 'U').charAt(0).toUpperCase()}</Text>
          </LinearGradient>

          <Text style={styles.selfName}>{user?.name || 'ไม่ระบุชื่อ'}</Text>
          {user?.email && <Text style={styles.selfEmail}>{user.email}</Text>}

          <View style={styles.selfBadge}>
            <Text style={{ fontSize: 16 }}>⭐</Text>
            <Text style={styles.selfBadgeText}>ผู้ก่อตั้งสายงาน</Text>
          </View>

          {/* Stats */}
          <View style={styles.selfStats}>
            <View style={styles.selfStatItem}>
              <Text style={styles.selfStatValue}>0</Text>
              <Text style={styles.selfStatLabel}>สมาชิกโดยตรง</Text>
            </View>
            <View style={styles.selfStatDivider} />
            <View style={styles.selfStatItem}>
              <Text style={styles.selfStatValue}>0</Text>
              <Text style={styles.selfStatLabel}>สมาชิกในทีม</Text>
            </View>
            <View style={styles.selfStatDivider} />
            <View style={styles.selfStatItem}>
              <Text style={styles.selfStatValue}>0</Text>
              <Text style={styles.selfStatLabel}>PV เดือนนี้</Text>
            </View>
          </View>
        </View>

        {/* Message */}
        <View style={styles.selfMessage}>
          <Text style={{ fontSize: 20 }}>ℹ️</Text>
          <Text style={styles.selfMessageText}>
            คุณยังไม่มีสมาชิกในทีม เริ่มแนะนำเพื่อนเพื่อสร้างทีมของคุณ
          </Text>
        </View>

        {/* Action */}
        <Pressable onPress={() => router.push('/referral')}>
          <LinearGradient colors={['#8B5CF6', '#6D28D9']} style={styles.selfActionBtn}>
            <Text style={{ fontSize: 20, color: 'white' }}>📤</Text>
            <Text style={styles.selfActionText}>แนะนำเพื่อน</Text>
          </LinearGradient>
        </Pressable>
      </LinearGradient>
    </View>
  );
};

// =====================================================
// Main Component
// =====================================================

export default function MLMTreeScreen() {
  const { user, isAuthenticated } = useAuthStore();
  const { resolvedTheme } = useAppStore();

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

  const fadeAnim = useRef(new Animated.Value(0)).current;

  // Stats calculation
  const stats = useMemo(() => {
    if (!tree) return { totalMembers: 1, activeMembers: 1, totalPV: 0, levels: 1 };

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
    return { totalMembers: total, activeMembers: active, totalPV: pv, levels: maxLevel };
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

        Animated.timing(fadeAnim, {
          toValue: 1,
          duration: 500,
          easing: Easing.out(Easing.ease),
          useNativeDriver: true,
        }).start();
      } else {
        setTree(null);
      }
    } catch (error) {
      console.error('Load MLM tree error:', error);
      setTree(null);
    } finally {
      setIsLoading(false);
    }
  }, [isAuthenticated]);

  useEffect(() => {
    loadTree();
  }, [loadTree]);

  const handleRefresh = useCallback(async () => {
    setRefreshing(true);
    await loadTree(viewingUserId || undefined);
    setRefreshing(false);
  }, [loadTree, viewingUserId]);

  // Toggle node expansion
  const toggleNode = useCallback(async (node: TreeNode) => {
    const nodeId = node.id;

    if (expandedNodes.has(nodeId)) {
      setExpandedNodes((prev) => {
        const next = new Set(prev);
        next.delete(nodeId);
        return next;
      });
    } else {
      if (node.children?.length === 0 && node.childrenCount > 0) {
        setLoadingNodes((prev) => new Set(prev).add(nodeId));

        try {
          const response = await getTreeNodeChildren(nodeId);
          if (response.success && response.data) {
            setTree((prevTree) => {
              if (!prevTree) return null;

              const updateNode = (n: TreeNode): TreeNode => {
                if (n.id === nodeId) return { ...n, children: response.data! };
                return { ...n, children: n.children?.map(updateNode) || [] };
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
      setSelectedMember({
        userId: node.id,
        name: node.name,
        email: node.email,
        avatar: node.avatar,
        rank: node.rank,
        statistics: node.statistics || { directReferrals: node.childrenCount, totalTeamMembers: 0, monthlyPV: 0, totalSales: 0 },
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

  useEffect(() => {
    const timer = setTimeout(() => {
      if (searchQuery.length >= 2) handleSearch();
      else setSearchResults([]);
    }, 500);
    return () => clearTimeout(timer);
  }, [searchQuery, handleSearch]);

  const handleViewMemberTree = useCallback(() => {
    if (selectedMember) {
      setShowMemberModal(false);
      loadTree(selectedMember.userId);
    }
  }, [selectedMember, loadTree]);

  // Render tree recursively
  const renderTree = useCallback((node: TreeNode, level: number = 0, isRoot = false): React.ReactNode => {
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
          animValue={nodeAnim}
          isRoot={level === 0 && !viewingUserId}
        />
        {isExpanded && node.children?.map((child) => renderTree(child, level + 1, false))}
      </View>
    );
  }, [expandedNodes, loadingNodes, toggleNode, handleNodePress, viewingUserId]);

  // Not authenticated
  if (!isAuthenticated) {
    return (
      <View style={styles.container}>
        <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
        <View style={styles.centerBox}>
          <Text style={{ fontSize: 80 }}>🌐</Text>
          <Text style={styles.centerTitle}>แผนผังสายงาน</Text>
          <Text style={styles.centerText}>เข้าสู่ระบบเพื่อดูแผนผังสายงานของคุณ</Text>
          <Pressable style={styles.primaryBtn} onPress={() => router.push('/login')}>
            <Text style={styles.primaryBtnText}>เข้าสู่ระบบ</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
      <View style={styles.safeArea}>
        {/* Header */}
        <View style={[styles.header, { paddingTop: 50 }]}>
          <Pressable onPress={() => router.back()} style={styles.backBtn}>
            <Text style={{ fontSize: 24, color: '#fff' }}>←</Text>
          </Pressable>

          <View style={styles.headerTitleContainer}>
            <Text style={styles.headerTitle}>
              {viewingUserId ? 'สายงานสมาชิก' : 'แผนผังสายงาน'}
            </Text>
            <Text style={styles.headerSubtitle}>ดูโครงสร้างทีมของคุณ</Text>
          </View>

          {viewingUserId && (
            <Pressable onPress={() => loadTree()} style={styles.homeBtn}>
              <Text style={styles.homeBtnText}>กลับหน้าหลัก</Text>
            </Pressable>
          )}
        </View>

        {/* Search */}
        <View style={styles.searchContainer}>
          <Text style={{ fontSize: 20 }}>🔍</Text>
          <TextInput
            value={searchQuery}
            onChangeText={setSearchQuery}
            onSubmitEditing={handleSearch}
            placeholder="ค้นหาสมาชิก..."
            placeholderTextColor="#6B7280"
            style={styles.searchInput}
            returnKeyType="search"
          />
          {isSearching ? (
            <ActivityIndicator size="small" color="#3B82F6" />
          ) : searchQuery.length > 0 ? (
            <Pressable onPress={() => setSearchQuery('')}>
              <Text style={{ fontSize: 20, color: '#6B7280' }}>✖</Text>
            </Pressable>
          ) : null}
        </View>

        {/* Content */}
        {isLoading ? (
          <View style={styles.loadingBox}>
            <ActivityIndicator size="large" color="#3B82F6" />
            <Text style={styles.loadingText}>กำลังโหลดข้อมูลสายงาน...</Text>
          </View>
        ) : searchResults.length > 0 ? (
          <ScrollView style={styles.scrollView} contentContainerStyle={{ paddingBottom: 20 }}>
            <Text style={styles.searchResultsTitle}>
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
                animValue={fadeAnim}
              />
            ))}
          </ScrollView>
        ) : tree ? (
          <Animated.View style={[styles.scrollView, { opacity: fadeAnim }]}>
            <ScrollView
              contentContainerStyle={{ paddingBottom: 20 }}
              showsVerticalScrollIndicator={false}
              refreshControl={
                <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} tintColor="#3B82F6" />
              }
            >
              <StatsSummary stats={stats} />

              <View style={styles.treeHeader}>
                <View style={styles.treeHeaderIndicator} />
                <Text style={styles.treeHeaderText}>โครงสร้างทีม</Text>
              </View>

              {renderTree(tree)}
            </ScrollView>
          </Animated.View>
        ) : (
          // ไม่มี downline - แสดงตัวเอง
          <ScrollView
            contentContainerStyle={styles.noMembersContainer}
            refreshControl={
              <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} tintColor="#3B82F6" />
            }
          >
            <SelfCard user={{ id: user?.id || 0, name: user?.name || 'คุณ', email: user?.email }} />
          </ScrollView>
        )}
      </View>

      <MemberDetailModal
        visible={showMemberModal}
        member={selectedMember}
        onClose={() => setShowMemberModal(false)}
        onViewTree={handleViewMemberTree}
      />
    </View>
  );
}

// =====================================================
// Styles
// =====================================================

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#0F0F23' },
  safeArea: { flex: 1 },
  centerBox: { flex: 1, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 24 },
  centerTitle: { fontSize: 20, fontWeight: 'bold', color: '#fff', marginTop: 16 },
  centerText: { fontSize: 14, color: '#9CA3AF', textAlign: 'center', marginTop: 8 },
  primaryBtn: { backgroundColor: '#3B82F6', paddingHorizontal: 32, paddingVertical: 12, borderRadius: 12, marginTop: 24 },
  primaryBtnText: { color: '#fff', fontWeight: 'bold', fontSize: 16 },

  header: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 20, paddingTop: 16, paddingBottom: 12 },
  backBtn: { width: 44, height: 44, borderRadius: 12, backgroundColor: 'rgba(255,255,255,0.1)', alignItems: 'center', justifyContent: 'center' },
  headerTitleContainer: { flex: 1, marginLeft: 16 },
  headerTitle: { fontSize: 20, fontWeight: 'bold', color: '#fff' },
  headerSubtitle: { fontSize: 13, color: '#9CA3AF', marginTop: 2 },
  homeBtn: { paddingHorizontal: 12, paddingVertical: 8, borderRadius: 10, backgroundColor: 'rgba(255,255,255,0.1)' },
  homeBtnText: { color: '#3B82F6', fontWeight: '600', fontSize: 13 },

  searchContainer: { marginHorizontal: 16, marginBottom: 12, flexDirection: 'row', alignItems: 'center', backgroundColor: 'rgba(255,255,255,0.08)', borderRadius: 16, paddingHorizontal: 16 },
  searchInput: { flex: 1, paddingVertical: 14, paddingHorizontal: 12, color: '#fff', fontSize: 16 },

  loadingBox: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  loadingText: { color: '#9CA3AF', marginTop: 12 },

  scrollView: { flex: 1 },
  searchResultsTitle: { marginHorizontal: 16, marginBottom: 12, color: '#9CA3AF', fontWeight: '600' },

  statsSummary: { marginBottom: 16 },
  statsSummaryItem: { borderRadius: 16, padding: 16, marginRight: 12, minWidth: 130, backgroundColor: 'rgba(255,255,255,0.08)' },
  statsSummaryIcon: { width: 40, height: 40, borderRadius: 10, alignItems: 'center', justifyContent: 'center', marginBottom: 8 },
  statsSummaryValue: { fontSize: 24, fontWeight: 'bold', color: '#fff' },
  statsSummaryLabel: { fontSize: 13, color: '#9CA3AF', marginTop: 2 },

  treeHeader: { flexDirection: 'row', alignItems: 'center', marginHorizontal: 16, marginBottom: 12 },
  treeHeaderIndicator: { width: 4, height: 24, borderRadius: 2, backgroundColor: '#3B82F6', marginRight: 12 },
  treeHeaderText: { fontSize: 16, fontWeight: 'bold', color: '#fff' },

  nodeCard: { marginBottom: 8, marginRight: 16, borderRadius: 16, backgroundColor: 'rgba(255,255,255,0.08)', overflow: 'hidden' },
  nodeCardRoot: { borderWidth: 1, borderColor: 'rgba(245,158,11,0.3)' },
  nodeGradientBorder: { height: 4 },
  nodeContent: { flexDirection: 'row', alignItems: 'center', padding: 16 },
  expandBtn: { width: 32, height: 32, borderRadius: 16, backgroundColor: 'rgba(255,255,255,0.1)', alignItems: 'center', justifyContent: 'center', marginRight: 12 },
  expandBtnPlaceholder: { width: 32, height: 32, marginRight: 12 },
  avatarContainer: { position: 'relative' },
  avatar: { width: 48, height: 48, borderRadius: 24, alignItems: 'center', justifyContent: 'center' },
  avatarText: { color: '#fff', fontSize: 18, fontWeight: 'bold' },
  activeIndicator: { position: 'absolute', bottom: -2, right: -2, width: 16, height: 16, borderRadius: 8, borderWidth: 3, borderColor: '#1F2937' },
  nodeInfo: { flex: 1, marginLeft: 12 },
  nameRow: { flexDirection: 'row', alignItems: 'center', flexWrap: 'wrap' },
  nodeName: { fontSize: 16, fontWeight: '600', color: '#fff' },
  rankContainer: { marginLeft: 8 },
  nodeSubtitle: { fontSize: 13, color: '#9CA3AF', marginTop: 2 },
  nodeStats: { alignItems: 'flex-end' },
  pvText: { fontSize: 14, fontWeight: 'bold', color: '#3B82F6' },
  statusBadge: { paddingHorizontal: 8, paddingVertical: 2, borderRadius: 10, marginTop: 4 },
  statusText: { fontSize: 11, fontWeight: '600' },

  rankBadge: { borderRadius: 20 },
  rankBadgeText: { fontWeight: '600' },

  // Modal
  modalOverlay: { flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(0,0,0,0.5)' },
  modalContent: { backgroundColor: '#1F2937', borderTopLeftRadius: 24, borderTopRightRadius: 24 },
  modalHandle: { alignItems: 'center', paddingTop: 12, paddingBottom: 8 },
  modalHandleBar: { width: 40, height: 4, borderRadius: 2, backgroundColor: '#4B5563' },
  modalHeader: { alignItems: 'center', paddingHorizontal: 24, paddingBottom: 16 },
  modalAvatarContainer: { position: 'relative', marginBottom: 12 },
  modalAvatarGradient: { width: 96, height: 96, borderRadius: 48, padding: 4 },
  modalAvatarInner: { flex: 1, borderRadius: 44, backgroundColor: '#1F2937', alignItems: 'center', justifyContent: 'center' },
  modalAvatarText: { color: '#3B82F6', fontSize: 32, fontWeight: 'bold' },
  modalStatusDot: { position: 'absolute', bottom: 0, right: 0, width: 24, height: 24, borderRadius: 12, borderWidth: 4, borderColor: '#1F2937' },
  modalName: { fontSize: 20, fontWeight: 'bold', color: '#fff' },
  modalEmail: { fontSize: 14, color: '#9CA3AF', marginTop: 2 },
  modalRankContainer: { marginTop: 8 },
  modalStatsGrid: { flexDirection: 'row', flexWrap: 'wrap', paddingHorizontal: 16, paddingBottom: 16 },
  modalStatItem: { width: '50%', padding: 8 },
  modalStatIcon: { width: 40, height: 40, borderRadius: 10, alignItems: 'center', justifyContent: 'center', marginBottom: 8 },
  modalStatValue: { fontSize: 20, fontWeight: 'bold', color: '#fff' },
  modalStatLabel: { fontSize: 13, color: '#9CA3AF', marginTop: 2 },
  modalInfoCard: { marginHorizontal: 24, marginBottom: 16, padding: 16, borderRadius: 16, backgroundColor: 'rgba(255,255,255,0.05)' },
  modalInfoRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 8 },
  modalInfoText: { marginLeft: 8, color: '#D1D5DB', fontSize: 14 },
  modalStatusIndicator: { width: 12, height: 12, borderRadius: 6 },
  modalActions: { paddingHorizontal: 24, paddingBottom: 16 },
  modalViewTreeBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', borderRadius: 16, paddingVertical: 16 },
  modalViewTreeText: { color: '#fff', fontWeight: 'bold', fontSize: 16, marginLeft: 8 },
  modalCloseBtn: { marginTop: 12, paddingVertical: 16, alignItems: 'center', borderRadius: 16, backgroundColor: 'rgba(255,255,255,0.1)' },
  modalCloseText: { color: '#D1D5DB', fontWeight: '600' },

  // No members / Self card
  noMembersContainer: { flex: 1, paddingHorizontal: 16, paddingTop: 16 },
  selfCardContainer: { flex: 1 },
  selfCard: { borderRadius: 24, overflow: 'hidden', borderWidth: 1, borderColor: 'rgba(139,92,246,0.2)' },
  selfCardBorder: { height: 4 },
  selfCardContent: { alignItems: 'center', padding: 24 },
  selfAvatar: { width: 80, height: 80, borderRadius: 40, alignItems: 'center', justifyContent: 'center', marginBottom: 16 },
  selfAvatarText: { color: '#fff', fontSize: 32, fontWeight: 'bold' },
  selfName: { fontSize: 22, fontWeight: 'bold', color: '#fff' },
  selfEmail: { fontSize: 14, color: '#9CA3AF', marginTop: 4 },
  selfBadge: { flexDirection: 'row', alignItems: 'center', backgroundColor: 'rgba(245,158,11,0.15)', paddingHorizontal: 16, paddingVertical: 8, borderRadius: 20, marginTop: 12 },
  selfBadgeText: { color: '#F59E0B', fontWeight: '600', marginLeft: 8 },
  selfStats: { flexDirection: 'row', marginTop: 24, paddingHorizontal: 16 },
  selfStatItem: { flex: 1, alignItems: 'center' },
  selfStatValue: { fontSize: 24, fontWeight: 'bold', color: '#fff' },
  selfStatLabel: { fontSize: 12, color: '#9CA3AF', marginTop: 4 },
  selfStatDivider: { width: 1, height: 40, backgroundColor: 'rgba(255,255,255,0.1)' },
  selfMessage: { flexDirection: 'row', alignItems: 'center', marginHorizontal: 24, marginTop: 16, padding: 16, borderRadius: 12, backgroundColor: 'rgba(59,130,246,0.1)' },
  selfMessageText: { flex: 1, marginLeft: 12, color: '#93C5FD', fontSize: 14, lineHeight: 20 },
  selfActionBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', marginHorizontal: 24, marginTop: 16, marginBottom: 24, paddingVertical: 16, borderRadius: 16 },
  selfActionText: { color: '#fff', fontWeight: 'bold', fontSize: 16, marginLeft: 8 },
});
