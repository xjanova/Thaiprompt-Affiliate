/**
 * ทดสอบถอดรหัสเส้นทาง (encoded polyline) ที่ใช้วาดเส้นทางตามถนนในหน้าชำระเงิน
 *
 * รัน: npx jest utils/__tests__/polyline.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import { decodePolyline, thinRoute } from '../polyline';

/** เข้ารหัสแบบเดียวกับ server (ใช้สร้างข้อมูลทดสอบ precision 6) */
const encode = (points: [number, number][], precision: number): string => {
  const factor = Math.pow(10, precision);
  let lastLat = 0;
  let lastLng = 0;
  const encodeValue = (value: number): string => {
    let v = value < 0 ? ~(value << 1) : value << 1;
    let out = '';
    while (v >= 0x20) {
      out += String.fromCharCode((0x20 | (v & 0x1f)) + 63);
      v >>= 5;
    }
    return out + String.fromCharCode(v + 63);
  };
  return points
    .map(([lat, lng]) => {
      const la = Math.round(lat * factor);
      const ln = Math.round(lng * factor);
      const chunk = encodeValue(la - lastLat) + encodeValue(ln - lastLng);
      lastLat = la;
      lastLng = ln;
      return chunk;
    })
    .join('');
};

describe('decodePolyline', () => {
  it('ตัวอย่างมาตรฐานของ Google (precision 5)', () => {
    expect(decodePolyline('_p~iF~ps|U_ulLnnqC_mqNvxq`@', 5)).toEqual([
      [38.5, -120.2],
      [40.7, -120.95],
      [43.252, -126.453],
    ]);
  });

  it('เส้นทางในกรุงเทพ precision 6 (Valhalla) ถอดกลับได้ตรง', () => {
    const route: [number, number][] = [
      [13.756331, 100.501765],
      [13.75801, 100.50422],
      [13.761234, 100.509876],
      [13.7299, 100.5675],
    ];
    const decoded = decodePolyline(encode(route, 6), 6);
    expect(decoded).toHaveLength(4);
    decoded.forEach(([la, ln], i) => {
      expect(la).toBeCloseTo(route[i][0], 6);
      expect(ln).toBeCloseTo(route[i][1], 6);
    });
  });

  it('ข้อมูลว่าง/เสีย/ไม่ใช่ string → ไม่ throw', () => {
    expect(decodePolyline(null)).toEqual([]);
    expect(decodePolyline('')).toEqual([]);
    expect(decodePolyline(123 as unknown as string)).toEqual([]);
    expect(() => decodePolyline('~~~~~~~~')).not.toThrow();
  });

  it('ลดจุดเส้นยาว โดยเก็บจุดแรก/สุดท้ายไว้', () => {
    const pts: [number, number][] = Array.from({ length: 1000 }, (_, i) => [13 + i / 1000, 100 + i / 1000]);
    const thin = thinRoute(pts, 100);
    expect(thin).toHaveLength(100);
    expect(thin[0]).toEqual(pts[0]);
    expect(thin[99]).toEqual(pts[999]);
  });
});
