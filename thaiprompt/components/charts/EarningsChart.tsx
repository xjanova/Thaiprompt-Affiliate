/**
 * EarningsChart Component - กราฟแสดงรายได้
 *
 * แสดงกราฟรายได้แบบ Line Chart พร้อมไอคอนและเลย์เอาต์สวยงาม
 */

import React from 'react';
import {
  View,
  Text,
  Dimensions,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import Svg, {
  Path,
  Defs,
  LinearGradient as SvgLinearGradient,
  Stop,
  Circle,
  G,
  Line,
  Text as SvgText,
} from 'react-native-svg';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

interface ChartDataPoint {
  date: string;
  day: string;
  amount: number;
}

interface EarningsChartProps {
  data: ChartDataPoint[];
  title?: string;
  subtitle?: string;
  totalAmount?: number;
  growthPercent?: number;
  height?: number;
  showDots?: boolean;
  showGrid?: boolean;
  gradientColors?: [string, string];
  lineColor?: string;
  isDark?: boolean;
}

export default function EarningsChart({
  data,
  title = 'รายได้',
  subtitle = '7 วันที่ผ่านมา',
  totalAmount,
  growthPercent,
  height = 200,
  showDots = true,
  showGrid = true,
  gradientColors = ['#3B82F6', '#1D4ED8'],
  lineColor = '#3B82F6',
  isDark = true,
}: EarningsChartProps) {
  const chartWidth = SCREEN_WIDTH - 64; // Padding on both sides
  const chartHeight = height - 60; // Space for labels

  // ถ้าไม่มีข้อมูล แสดง empty state
  if (!data || data.length === 0) {
    return (
      <View className={`rounded-2xl p-4 ${isDark ? 'bg-slate-800' : 'bg-white'}`}>
        <View className="flex-row items-center justify-between mb-4">
          <View>
            <Text className={`text-lg font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
              {title}
            </Text>
            <Text className={isDark ? 'text-gray-400' : 'text-gray-500'}>{subtitle}</Text>
          </View>
          <View className="w-10 h-10 rounded-xl bg-blue-500/20 items-center justify-center">
            <Text style={{ fontSize: 20 }}>📈</Text>
          </View>
        </View>
        <View className="items-center justify-center py-8">
          <Text style={{ fontSize: 48, opacity: 0.5 }}>📊</Text>
          <Text className={`mt-2 ${isDark ? 'text-gray-500' : 'text-gray-400'}`}>
            ยังไม่มีข้อมูลรายได้
          </Text>
        </View>
      </View>
    );
  }

  // คำนวณค่าสูงสุดและต่ำสุด
  const amounts = data.map(d => d.amount);
  const maxAmount = Math.max(...amounts) || 1;
  const minAmount = Math.min(...amounts);
  const range = maxAmount - minAmount || 1;

  // คำนวณตำแหน่ง X และ Y
  const getX = (index: number) => {
    const padding = 20;
    const availableWidth = chartWidth - padding * 2;
    return padding + (index / (data.length - 1 || 1)) * availableWidth;
  };

  const getY = (amount: number) => {
    const padding = 20;
    const availableHeight = chartHeight - padding * 2;
    const normalizedValue = (amount - minAmount) / range;
    return chartHeight - padding - normalizedValue * availableHeight;
  };

  // สร้าง path สำหรับ line
  const createLinePath = () => {
    if (data.length === 0) return '';

    let path = `M ${getX(0)} ${getY(data[0].amount)}`;
    for (let i = 1; i < data.length; i++) {
      path += ` L ${getX(i)} ${getY(data[i].amount)}`;
    }
    return path;
  };

  // สร้าง path สำหรับ area fill
  const createAreaPath = () => {
    if (data.length === 0) return '';

    let path = `M ${getX(0)} ${chartHeight}`;
    path += ` L ${getX(0)} ${getY(data[0].amount)}`;

    for (let i = 1; i < data.length; i++) {
      path += ` L ${getX(i)} ${getY(data[i].amount)}`;
    }

    path += ` L ${getX(data.length - 1)} ${chartHeight}`;
    path += ' Z';
    return path;
  };

  // Format currency
  const formatAmount = (amount: number) => {
    if (amount >= 1000000) {
      return `${(amount / 1000000).toFixed(1)}M`;
    }
    if (amount >= 1000) {
      return `${(amount / 1000).toFixed(1)}K`;
    }
    return amount.toFixed(0);
  };

  return (
    <View className={`rounded-2xl overflow-hidden ${isDark ? 'bg-slate-800' : 'bg-white'}`}>
      {/* Header */}
      <View className="p-4 pb-2">
        <View className="flex-row items-center justify-between">
          <View className="flex-1">
            <Text className={`text-lg font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
              {title}
            </Text>
            <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
              {subtitle}
            </Text>
          </View>

          {/* Total & Growth */}
          <View className="items-end">
            {totalAmount !== undefined && (
              <Text className={`text-xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                ฿{totalAmount.toLocaleString()}
              </Text>
            )}
            {growthPercent !== undefined && (
              <View className={`flex-row items-center px-2 py-0.5 rounded-full ${
                growthPercent >= 0 ? 'bg-green-500/20' : 'bg-red-500/20'
              }`}>
                <Text style={{ fontSize: 14 }}>
                  {growthPercent >= 0 ? '📈' : '📉'}
                </Text>
                <Text className={`ml-1 text-xs font-medium ${
                  growthPercent >= 0 ? 'text-green-500' : 'text-red-500'
                }`}>
                  {growthPercent >= 0 ? '+' : ''}{growthPercent.toFixed(1)}%
                </Text>
              </View>
            )}
          </View>
        </View>
      </View>

      {/* Chart */}
      <View className="px-4">
        <Svg width={chartWidth} height={chartHeight}>
          <Defs>
            <SvgLinearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
              <Stop offset="0%" stopColor={lineColor} stopOpacity={0.3} />
              <Stop offset="100%" stopColor={lineColor} stopOpacity={0.05} />
            </SvgLinearGradient>
          </Defs>

          {/* Grid Lines */}
          {showGrid && (
            <G opacity={0.1}>
              {[0.25, 0.5, 0.75].map((ratio, i) => (
                <Line
                  key={i}
                  x1={20}
                  y1={20 + (chartHeight - 40) * ratio}
                  x2={chartWidth - 20}
                  y2={20 + (chartHeight - 40) * ratio}
                  stroke={isDark ? '#FFFFFF' : '#000000'}
                  strokeWidth={1}
                  strokeDasharray="4,4"
                />
              ))}
            </G>
          )}

          {/* Area Fill */}
          <Path
            d={createAreaPath()}
            fill="url(#areaGradient)"
          />

          {/* Line */}
          <Path
            d={createLinePath()}
            stroke={lineColor}
            strokeWidth={3}
            fill="none"
            strokeLinecap="round"
            strokeLinejoin="round"
          />

          {/* Dots */}
          {showDots && data.map((point, index) => (
            <G key={index}>
              <Circle
                cx={getX(index)}
                cy={getY(point.amount)}
                r={6}
                fill={isDark ? '#1E293B' : '#FFFFFF'}
                stroke={lineColor}
                strokeWidth={3}
              />
            </G>
          ))}
        </Svg>
      </View>

      {/* X-axis Labels */}
      <View className="flex-row justify-between px-6 pb-4">
        {data.map((point, index) => (
          <View key={index} className="items-center">
            <Text className={`text-xs ${isDark ? 'text-gray-500' : 'text-gray-400'}`}>
              {point.day}
            </Text>
          </View>
        ))}
      </View>
    </View>
  );
}
