/**
 * ทดสอบตัวถอดรหัสเส้นทาง (encoded polyline) ที่ใช้วาดภาพเส้นทางในการ์ดข้อเสนองานไรเดอร์
 *
 * รัน: npx jest components/rider/__tests__/routePolyline.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import { decodePolyline, fitPointsToBox, thinPoints } from '../routePolyline';

/** ตัวเข้ารหัสอ้างอิง (อัลกอริทึมเดียวกับ Valhalla/Google) ใช้สร้างข้อมูลทดสอบ */
const encode = (points: Array<[number, number]>, precision = 6): string => {
  const factor = Math.pow(10, precision);
  let out = '';
  let prevLat = 0;
  let prevLng = 0;
  const encodeValue = (value: number) => {
    let v = value < 0 ? ~(value << 1) : value << 1;
    while (v >= 0x20) {
      out += String.fromCharCode((0x20 | (v & 0x1f)) + 63);
      v >>= 5;
    }
    out += String.fromCharCode(v + 63);
  };
  for (const [lat, lng] of points) {
    const iLat = Math.round(lat * factor);
    const iLng = Math.round(lng * factor);
    encodeValue(iLat - prevLat);
    encodeValue(iLng - prevLng);
    prevLat = iLat;
    prevLng = iLng;
  }
  return out;
};

describe('decodePolyline', () => {
  it('ถอดตัวอย่างมาตรฐานของ Google (ความละเอียด 5) ได้ถูกต้อง', () => {
    const points = decodePolyline('_p~iF~ps|U_ulLnnqC_mqNvxq`@', 5);
    expect(points).toHaveLength(3);
    expect(points[0].latitude).toBeCloseTo(38.5, 5);
    expect(points[0].longitude).toBeCloseTo(-120.2, 5);
    expect(points[2].latitude).toBeCloseTo(43.252, 5);
    expect(points[2].longitude).toBeCloseTo(-126.453, 5);
  });

  it('ถอดเส้นทางในกรุงเทพฯ ความละเอียด 6 กลับมาตรงทุกจุด', () => {
    const route: Array<[number, number]> = [
      [13.7563, 100.5018],
      [13.7381, 100.5604],
      [13.7069, 100.6012],
      [13.6904, 100.7501],
    ];
    const points = decodePolyline(encode(route));
    expect(points).toHaveLength(route.length);
    points.forEach((p, i) => {
      expect(p.latitude).toBeCloseTo(route[i][0], 6);
      expect(p.longitude).toBeCloseTo(route[i][1], 6);
    });
  });

  it('ข้อมูลว่าง/เสีย ไม่ throw และคืนเท่าที่ถอดได้', () => {
    expect(decodePolyline(null)).toEqual([]);
    expect(decodePolyline(undefined)).toEqual([]);
    expect(decodePolyline('')).toEqual([]);
    expect(decodePolyline('  ')).toEqual([]);
    const full = encode([
      [13.75, 100.5],
      [13.76, 100.51],
    ]);
    // ตัดกลางจุดที่ 2 → ได้แค่จุดแรก
    expect(decodePolyline(full.slice(0, full.length - 2))).toHaveLength(1);
  });
});

describe('fitPointsToBox', () => {
  it('ทุกจุดอยู่ในกรอบหลังหักขอบ และจุดแรก/สุดท้ายไม่ซ้ำตำแหน่ง', () => {
    const points = decodePolyline(
      encode([
        [13.7563, 100.5018],
        [13.7381, 100.5604],
        [13.6904, 100.7501],
      ])
    );
    const box = fitPointsToBox(points, 320, 150, 24);
    expect(box).toHaveLength(3);
    box.forEach((p) => {
      expect(p.x).toBeGreaterThanOrEqual(24 - 0.001);
      expect(p.x).toBeLessThanOrEqual(320 - 24 + 0.001);
      expect(p.y).toBeGreaterThanOrEqual(24 - 0.001);
      expect(p.y).toBeLessThanOrEqual(150 - 24 + 0.001);
    });
    // ทิศเหนืออยู่บน: จุดแรก (ละติจูดสูงกว่า) ต้องอยู่สูงกว่าจุดสุดท้าย
    expect(box[0].y).toBeLessThan(box[2].y);
  });

  it('จุดเดียว หรือกรอบเล็กเกินไป = วาดไม่ได้', () => {
    expect(fitPointsToBox([{ latitude: 13.7, longitude: 100.5 }], 300, 150)).toEqual([]);
    expect(
      fitPointsToBox(
        [
          { latitude: 13.7, longitude: 100.5 },
          { latitude: 13.8, longitude: 100.6 },
        ],
        40,
        40,
        24
      )
    ).toEqual([]);
  });
});

describe('thinPoints', () => {
  it('ตัดจุดที่ซ้อนกันแต่เก็บจุดแรกและจุดสุดท้ายเสมอ', () => {
    const thinned = thinPoints(
      [
        { x: 0, y: 0 },
        { x: 0.2, y: 0.1 },
        { x: 0.3, y: 0.2 },
        { x: 10, y: 10 },
        { x: 10.1, y: 10 },
      ],
      1.5
    );
    expect(thinned[0]).toEqual({ x: 0, y: 0 });
    expect(thinned[thinned.length - 1]).toEqual({ x: 10.1, y: 10 });
    expect(thinned.length).toBe(3);
  });
});
