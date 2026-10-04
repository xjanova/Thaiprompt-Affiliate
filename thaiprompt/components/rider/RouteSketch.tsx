/**
 * RouteSketch — ภาพเส้นทางจุดรับ → จุดส่งบนพื้นแผนที่ประกอบ (ไม่ใช้ WebView)
 *
 * ใช้ในการ์ดข้อเสนองาน (รายการงานมีหลายใบ — แผนที่สดใบละ WebView หนักเกินไป)
 * - วาดจาก route_polyline ที่ server ส่งมา (Valhalla/Google ความละเอียด 6) ด้วย react-native-svg
 * - ไม่มี polyline / ถอดไม่ได้ → ไม่แสดงอะไรเลย (หน้าจอใช้หน้าตาเดิม)
 * - เส้นน้ำเงินกรมท่าเข้ม (navyFill — เข้มทั้งสองโหมด) + เส้นประทองด้านบน · จุดรับ = วงร้าน · จุดส่ง = หมุดทอง
 * - ภาพพื้นเป็นภาพประกอบ ไม่ใช่แผนที่จริง → บอกโปรแกรมอ่านหน้าจอว่าเป็น "ภาพเส้นทางโดยประมาณ"
 */

import React, { useMemo, useState } from 'react';
import { StyleSheet, View, type LayoutChangeEvent, type StyleProp, type ViewStyle } from 'react-native';
import { Image } from 'expo-image';
import Svg, { Polyline } from 'react-native-svg';
import { Icon } from '@/components/ui';
import { useTheme, withAlpha, shadowStyle } from '@/theme';
import { decodePolyline, fitPointsToBox, thinPoints } from './routePolyline';

const MAP_IMAGE = require('@/assets/images/brand/map-light.webp');

const MARKER = 34;

export interface RouteSketchProps {
  /** encoded polyline (precision 6) */
  polyline: string | null | undefined;
  height?: number;
  /** มุมโค้งด้านบน (ใช้เป็นหัวการ์ด) */
  radiusTop?: number;
  /** มุมโค้งด้านล่าง */
  radiusBottom?: number;
  /** ของที่วางทับมุมบน (ป้ายประเภทงาน/เวลา) */
  children?: React.ReactNode;
  style?: StyleProp<ViewStyle>;
}

/** มีเส้นทางให้วาดหรือไม่ (ใช้ตัดสินใจก่อนจัดหน้า) */
export const hasDrawableRoute = (polyline: string | null | undefined): boolean => decodePolyline(polyline).length >= 2;

export const RouteSketch: React.FC<RouteSketchProps> = ({
  polyline,
  height = 150,
  radiusTop = 0,
  radiusBottom = 0,
  children,
  style,
}) => {
  const { colors, isDark } = useTheme();
  const [width, setWidth] = useState(0);
  const points = useMemo(() => decodePolyline(polyline), [polyline]);
  const box = useMemo(
    () => (width > 0 ? thinPoints(fitPointsToBox(points, width, height, MARKER)) : []),
    [points, width, height]
  );

  if (points.length < 2) return null;

  const onLayout = (event: LayoutChangeEvent) => {
    const w = Math.round(event.nativeEvent.layout.width);
    if (w !== width) setWidth(w);
  };

  const path = box.map((p) => `${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' ');
  const start = box[0];
  const end = box[box.length - 1];

  return (
    <View
      onLayout={onLayout}
      accessible
      accessibilityLabel="ภาพเส้นทางจากจุดรับของไปจุดส่งโดยประมาณ"
      style={[
        styles.frame,
        {
          height,
          backgroundColor: colors.inset,
          borderTopLeftRadius: radiusTop,
          borderTopRightRadius: radiusTop,
          borderBottomLeftRadius: radiusBottom,
          borderBottomRightRadius: radiusBottom,
        },
        style,
      ]}
    >
      <Image source={MAP_IMAGE} style={StyleSheet.absoluteFill} contentFit="cover" />
      {/* โหมดมืด: ม่านทับภาพแผนที่สว่างให้กลืนกับพื้นมืด */}
      {isDark && <View style={[StyleSheet.absoluteFill, { backgroundColor: withAlpha(colors.background, 0.55) }]} />}

      {box.length >= 2 && (
        <>
          <Svg width={width} height={height} style={StyleSheet.absoluteFill}>
            <Polyline
              points={path}
              fill="none"
              stroke={colors.navyFill}
              strokeWidth={7}
              strokeLinecap="round"
              strokeLinejoin="round"
            />
            <Polyline
              points={path}
              fill="none"
              stroke={colors.goldLight}
              strokeWidth={2.2}
              strokeDasharray="5 6"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          </Svg>

          {/* จุดรับของ (ร้าน) */}
          <View
            style={[
              styles.marker,
              {
                left: start.x - MARKER / 2,
                top: start.y - MARKER / 2,
                backgroundColor: colors.card,
                borderColor: colors.navyFill,
              },
              shadowStyle('sm', colors.shadowDark),
            ]}
          >
            <Icon name="storefront" size={17} color={isDark ? colors.goldLight : colors.navy} />
          </View>

          {/* จุดส่ง (หมุดทอง) */}
          <View style={[styles.pin, { left: end.x - MARKER / 2, top: end.y - MARKER + 4 }]}>
            <Icon name="map-pin" size={MARKER} color={colors.gold} weight="fill" />
          </View>
        </>
      )}

      {!!children && <View style={styles.overlay}>{children}</View>}
    </View>
  );
};

const styles = StyleSheet.create({
  frame: {
    overflow: 'hidden',
    width: '100%',
  },
  marker: {
    position: 'absolute',
    width: MARKER,
    height: MARKER,
    borderRadius: MARKER / 2,
    borderWidth: 2.5,
    alignItems: 'center',
    justifyContent: 'center',
  },
  pin: {
    position: 'absolute',
    width: MARKER,
    height: MARKER,
    alignItems: 'center',
  },
  overlay: {
    position: 'absolute',
    left: 12,
    right: 12,
    top: 12,
  },
});

export default RouteSketch;
