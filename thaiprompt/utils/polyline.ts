/**
 * Encoded polyline (อัลกอริทึมของ Google) — ใช้กับเส้นทางตามถนนจาก server (Valhalla / Google Routes = ความละเอียด 6)
 * ฟังก์ชันล้วน ไม่พึ่ง native module (ทดสอบด้วย jest ได้)
 */

/**
 * ถอดรหัส encoded polyline (อัลกอริทึมของ Google) → [[lat, lng], ...]
 * precision 6 = Valhalla / Google Routes (polyline6) · 5 = polyline มาตรฐาน
 * ข้อมูลเสีย/ยาวเกิน → คืนเท่าที่ถอดได้ (ไม่ throw)
 */
export const decodePolyline = (encoded: string | null | undefined, precision: number = 6): [number, number][] => {
  if (typeof encoded !== 'string' || encoded.length === 0 || encoded.length > 200_000) return [];
  const factor = Math.pow(10, precision);
  const points: [number, number][] = [];
  let index = 0;
  let lat = 0;
  let lng = 0;
  try {
    while (index < encoded.length) {
      let result = 0;
      let shift = 0;
      let byte: number;
      do {
        if (index >= encoded.length) return points;
        byte = encoded.charCodeAt(index++) - 63;
        result |= (byte & 0x1f) << shift;
        shift += 5;
      } while (byte >= 0x20 && shift < 35);
      lat += result & 1 ? ~(result >> 1) : result >> 1;

      result = 0;
      shift = 0;
      do {
        if (index >= encoded.length) return points;
        byte = encoded.charCodeAt(index++) - 63;
        result |= (byte & 0x1f) << shift;
        shift += 5;
      } while (byte >= 0x20 && shift < 35);
      lng += result & 1 ? ~(result >> 1) : result >> 1;

      const la = lat / factor;
      const ln = lng / factor;
      if (Math.abs(la) > 90 || Math.abs(ln) > 180) return points;
      points.push([la, ln]);
    }
  } catch {
    return points;
  }
  return points;
};

/** ลดจุดของเส้นทางให้ไม่เกิน max (เก็บจุดแรก/สุดท้ายไว้เสมอ) */
export const thinRoute = (pts: [number, number][], max: number): [number, number][] => {
  if (pts.length <= max) return pts;
  const step = (pts.length - 1) / (max - 1);
  const out: [number, number][] = [];
  for (let i = 0; i < max; i++) out.push(pts[Math.round(i * step)]);
  return out;
};

