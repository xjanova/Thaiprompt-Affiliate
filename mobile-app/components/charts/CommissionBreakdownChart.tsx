/**
 * CommissionBreakdownChart Component - กราฟแสดงสัดส่วนคอมมิชชัน
 *
 * แสดง Pie Chart และ Bar Chart แบ่งตามประเภทคอมมิชชัน
 */

import React from 'react';
import { View, Text, Dimensions } from 'react-native';
import Svg, { G, Path, Circle, Rect, Text as SvgText } from 'react-native-svg';
import { Ionicons } from '@expo/vector-icons';
import Animated, { FadeInDown, FadeInRight } from 'react-native-reanimated';
import { COMMISSION_TYPES, CHART_CONFIG } from '@/config/appConfig';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

interface CommissionBreakdown {
  type: string;
  typeText?: string;
  amount: number;
  percentage?: number;
}

interface CommissionBreakdownChartProps {
  data: CommissionBreakdown[];
  totalAmount?: number;
  chartType?: 'pie' | 'bar' | 'both';
  showLegend?: boolean;
  showValues?: boolean;
  isDark?: boolean;
}

// Commission type config
const getCommissionColor = (type: string): string => {
  const colors: Record<string, string> = {
    unilevel_direct: '#10B981',
    unilevel_indirect: '#8B5CF6',
    binary_pair: '#3B82F6',
    sponsor_bonus: '#F59E0B',
    rank_bonus: '#EC4899',
    leadership_bonus: '#EF4444',
    direct: '#10B981',
    team: '#8B5CF6',
    referral: '#3B82F6',
    bonus: '#F59E0B',
  };
  return colors[type.toLowerCase()] || CHART_CONFIG.PIE_COLORS[0];
};

const getCommissionIcon = (type: string): keyof typeof Ionicons.glyphMap => {
  const icons: Record<string, keyof typeof Ionicons.glyphMap> = {
    unilevel_direct: 'cart',
    unilevel_indirect: 'git-network',
    binary_pair: 'git-branch',
    sponsor_bonus: 'person-add',
    rank_bonus: 'trophy',
    leadership_bonus: 'star',
    direct: 'cart',
    team: 'people',
    referral: 'share-social',
    bonus: 'gift',
  };
  return icons[type.toLowerCase()] || 'cash';
};

const getCommissionLabel = (type: string): string => {
  const labels: Record<string, string> = {
    unilevel_direct: 'ขายตรง',
    unilevel_indirect: 'สายงาน',
    binary_pair: 'ไบนารี่',
    sponsor_bonus: 'ผู้แนะนำ',
    rank_bonus: 'ตำแหน่ง',
    leadership_bonus: 'ผู้นำ',
    direct: 'ขายตรง',
    team: 'ทีม',
    referral: 'แนะนำ',
    bonus: 'โบนัส',
  };
  return labels[type.toLowerCase()] || type;
};

export default function CommissionBreakdownChart({
  data,
  totalAmount,
  chartType = 'both',
  showLegend = true,
  showValues = true,
  isDark = true,
}: CommissionBreakdownChartProps) {
  // คำนวณ total ถ้าไม่ได้ส่งมา
  const total = totalAmount || data.reduce((sum, item) => sum + item.amount, 0);

  // คำนวณ percentage
  const processedData = data.map(item => ({
    ...item,
    percentage: total > 0 ? (item.amount / total) * 100 : 0,
    color: getCommissionColor(item.type),
    icon: getCommissionIcon(item.type),
    label: item.typeText || getCommissionLabel(item.type),
  })).filter(item => item.amount > 0);

  if (processedData.length === 0) {
    return (
      <View className={`rounded-2xl p-4 ${isDark ? 'bg-slate-800' : 'bg-white'}`}>
        <Text className={`text-lg font-bold mb-4 ${isDark ? 'text-white' : 'text-gray-900'}`}>
          สัดส่วนคอมมิชชัน
        </Text>
        <View className="items-center justify-center py-8">
          <Ionicons name="pie-chart-outline" size={48} color={isDark ? '#4B5563' : '#9CA3AF'} />
          <Text className={`mt-2 ${isDark ? 'text-gray-500' : 'text-gray-400'}`}>
            ยังไม่มีข้อมูลคอมมิชชัน
          </Text>
        </View>
      </View>
    );
  }

  // Pie Chart Parameters
  const pieSize = 140;
  const pieRadius = pieSize / 2 - 10;
  const pieCenter = pieSize / 2;
  const innerRadius = pieRadius * 0.6;

  // สร้าง Pie Chart Arcs
  const createPieArcs = () => {
    const arcs: React.ReactNode[] = [];
    let currentAngle = -90; // Start from top

    processedData.forEach((item, index) => {
      const angle = (item.percentage / 100) * 360;
      const startAngle = (currentAngle * Math.PI) / 180;
      const endAngle = ((currentAngle + angle) * Math.PI) / 180;

      const largeArcFlag = angle > 180 ? 1 : 0;

      // Outer arc
      const outerStartX = pieCenter + pieRadius * Math.cos(startAngle);
      const outerStartY = pieCenter + pieRadius * Math.sin(startAngle);
      const outerEndX = pieCenter + pieRadius * Math.cos(endAngle);
      const outerEndY = pieCenter + pieRadius * Math.sin(endAngle);

      // Inner arc
      const innerStartX = pieCenter + innerRadius * Math.cos(startAngle);
      const innerStartY = pieCenter + innerRadius * Math.sin(startAngle);
      const innerEndX = pieCenter + innerRadius * Math.cos(endAngle);
      const innerEndY = pieCenter + innerRadius * Math.sin(endAngle);

      const d = [
        `M ${outerStartX} ${outerStartY}`,
        `A ${pieRadius} ${pieRadius} 0 ${largeArcFlag} 1 ${outerEndX} ${outerEndY}`,
        `L ${innerEndX} ${innerEndY}`,
        `A ${innerRadius} ${innerRadius} 0 ${largeArcFlag} 0 ${innerStartX} ${innerStartY}`,
        'Z',
      ].join(' ');

      arcs.push(
        <Path
          key={index}
          d={d}
          fill={item.color}
          opacity={0.9}
        />
      );

      currentAngle += angle;
    });

    return arcs;
  };

  return (
    <View className={`rounded-2xl p-4 ${isDark ? 'bg-slate-800' : 'bg-white'}`}>
      {/* Header */}
      <View className="flex-row items-center justify-between mb-4">
        <View>
          <Text className={`text-lg font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
            สัดส่วนคอมมิชชัน
          </Text>
          <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
            แบ่งตามประเภท
          </Text>
        </View>
        <View className="w-10 h-10 rounded-xl bg-purple-500/20 items-center justify-center">
          <Ionicons name="pie-chart" size={20} color="#8B5CF6" />
        </View>
      </View>

      {/* Charts Container */}
      <View className="flex-row">
        {/* Pie Chart */}
        {(chartType === 'pie' || chartType === 'both') && (
          <Animated.View
            entering={FadeInDown.delay(100).springify()}
            className="items-center justify-center mr-4"
          >
            <Svg width={pieSize} height={pieSize}>
              <G>
                {createPieArcs()}
              </G>
            </Svg>

            {/* Center Text */}
            <View
              className="absolute items-center justify-center"
              style={{ width: pieSize, height: pieSize }}
            >
              <Text className={`text-xs ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                รวม
              </Text>
              <Text className={`text-lg font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                ฿{(total / 1000).toFixed(1)}K
              </Text>
            </View>
          </Animated.View>
        )}

        {/* Legend / Bar List */}
        {showLegend && (
          <View className="flex-1">
            {processedData.map((item, index) => (
              <Animated.View
                key={index}
                entering={FadeInRight.delay(150 + index * 50).springify()}
                className="flex-row items-center mb-2.5"
              >
                {/* Color Dot & Icon */}
                <View
                  className="w-8 h-8 rounded-lg items-center justify-center mr-3"
                  style={{ backgroundColor: `${item.color}20` }}
                >
                  <Ionicons name={item.icon} size={16} color={item.color} />
                </View>

                {/* Info */}
                <View className="flex-1">
                  <View className="flex-row items-center justify-between">
                    <Text className={`text-sm font-medium ${isDark ? 'text-white' : 'text-gray-900'}`}>
                      {item.label}
                    </Text>
                    <Text className={`text-sm font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                      {item.percentage.toFixed(1)}%
                    </Text>
                  </View>

                  {/* Progress Bar */}
                  {(chartType === 'bar' || chartType === 'both') && (
                    <View className={`h-1.5 rounded-full mt-1 ${isDark ? 'bg-slate-700' : 'bg-gray-100'}`}>
                      <View
                        className="h-full rounded-full"
                        style={{
                          width: `${Math.min(item.percentage, 100)}%`,
                          backgroundColor: item.color,
                        }}
                      />
                    </View>
                  )}

                  {/* Amount */}
                  {showValues && (
                    <Text className={`text-xs mt-0.5 ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                      ฿{item.amount.toLocaleString()}
                    </Text>
                  )}
                </View>
              </Animated.View>
            ))}
          </View>
        )}
      </View>
    </View>
  );
}
