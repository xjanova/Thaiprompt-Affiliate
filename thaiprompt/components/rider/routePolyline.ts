/**
 * ถอดรหัสเส้นทาง (encoded polyline) ที่ server ส่งมากับงานไรเดอร์ → พิกัด + จัดลงกรอบภาพ
 *
 * - server (RouteService: Valhalla / Google) เข้ารหัสด้วยความละเอียด 6 ตำแหน่ง (precision 1e6)
 * - ข้อมูลเสีย/ตัดกลาง → คืนเท่าที่ถอดได้ (ไม่ throw) · จำกัดจำนวนจุดกันเส้นยาวผิดปกติทำแอปช้า
 * - ไม่มีการคำนวณเงินหรือระยะที่ใช้ตัดสินใจในไฟล์นี้ — ใช้วาดภาพประกอบเท่านั้น
 */

export interface LatLng {
  latitude: number;
  longitude: number;
}

/** จำนวนจุดสูงสุดที่ถอด (เส้นทางในเมืองปกติไม่กี่ร้อยจุด) */
const MAX_POINTS = 4000;

/**
 * ถอด polyline (Google encoded polyline algorithm)
 * @param encoded ข้อความที่เข้ารหัส
 * @param precision จำนวนทศนิยม (server ส่ง 6)
 */
export const decodePolyline = (encoded: string | null | undefined, precision: number = 6): LatLng[] => {
  if (typeof encoded !== 'string' || encoded.length < 2) return [];
  const factor = Math.pow(10, precision);
  const points: LatLng[] = [];
  let index = 0;
  let lat = 0;
  let lng = 0;

  // อ่านตัวเลขหนึ่งค่า (คืน null เมื่อข้อมูลขาดกลางคัน)
  const readValue = (): number | null => {
    let result = 0;
    let shift = 0;
    let byte = 0;
    do {
      if (index >= encoded.length) return null;
      byte = encoded.charCodeAt(index++) - 63;
      if (byte < 0 || byte > 63) return null;
      result |= (byte & 0x1f) << shift;
      shift += 5;
      // กันข้อมูลแปลกจนเลื่อนบิตเกิน 32 บิต
      if (shift > 30) return null;
    } while (byte >= 0x20);
    return result & 1 ? ~(result >> 1) : result >> 1;
  };

  while (index < encoded.length && points.length < MAX_POINTS) {
    const dLat = readValue();
    if (dLat === null) break;
    const dLng = readValue();
    if (dLng === null) break;
    lat += dLat;
    lng += dLng;
    const latitude = lat / factor;
    const longitude = lng / factor;
    if (Math.abs(latitude) > 90 || Math.abs(longitude) > 180) break;
    points.push({ latitude, longitude });
  }
  return points;
};

/**
 * จัดพิกัดลงกรอบภาพ (x,y เป็นพิกเซล) — รักษาสัดส่วนจริง (ลองจิจูดคูณ cos ละติจูดกลาง)
 * @returns จุดในกรอบ (ว่าง = วาดไม่ได้)
 */
export const fitPointsToBox = (
  points: LatLng[],
  width: number,
  height: number,
  padding: number = 24
): Array<{ x: number; y: number }> => {
  if (points.length < 2 || width <= padding * 2 || height <= padding * 2) return [];
  const meanLat = points.reduce((sum, p) => sum + p.latitude, 0) / points.length;
  const k = Math.cos((meanLat * Math.PI) / 180) || 1;
  const xs = points.map((p) => p.longitude * k);
  const ys = points.map((p) => -p.latitude);
  const minX = Math.min(...xs);
  const maxX = Math.max(...xs);
  const minY = Math.min(...ys);
  const maxY = Math.max(...ys);
  const spanX = Math.max(maxX - minX, 1e-6);
  const spanY = Math.max(maxY - minY, 1e-6);
  const scale = Math.min((width - padding * 2) / spanX, (height - padding * 2) / spanY);
  // จัดกึ่งกลาง
  const offsetX = (width - spanX * scale) / 2;
  const offsetY = (height - spanY * scale) / 2;
  return xs.map((x, i) => ({ x: offsetX + (x - minX) * scale, y: offsetY + (ys[i] - minY) * scale }));
};

/** ลดจุดที่ซ้อนกันเกินไป (ห่างกันน้อยกว่า minGap พิกเซล) ให้ SVG วาดเบาลง */
export const thinPoints = (points: Array<{ x: number; y: number }>, minGap: number = 1.5): Array<{ x: number; y: number }> => {
  if (points.length <= 2) return points;
  const out = [points[0]];
  for (let i = 1; i < points.length - 1; i += 1) {
    const last = out[out.length - 1];
    if (Math.hypot(points[i].x - last.x, points[i].y - last.y) >= minGap) out.push(points[i]);
  }
  out.push(points[points.length - 1]);
  return out;
};
