/**
 * เลขเวอร์ชัน / เลข build ของแอป — ตัวช่วยล้วน (ไม่ import expo) เพื่อให้ jest ทดสอบได้
 *
 * ค่าจริงอ่านจาก native (expo-application) ใน config/appConfig.ts แล้วส่งเข้ามาที่นี่
 *   - ชื่อเวอร์ชัน (versionName)  เช่น '3.389.0'
 *   - เลข build (versionCode)    เช่น 46
 * อ่านไม่ได้ (เว็บ / เทสต์ / ค่าผิดรูป) = ใช้ค่าสำรองที่ตรงกับ app.json ตอนออก build ล่าสุด
 */

/** เลข build จาก native ('46' | 46) → จำนวนเต็มบวก · ค่าผิดรูป/ว่าง = null */
export const parseNativeBuild = (raw: unknown): number | null => {
  const text = typeof raw === 'number' ? String(raw) : typeof raw === 'string' ? raw.trim() : '';
  if (!/^\d{1,9}$/.test(text)) return null;
  const n = Number(text);
  return Number.isSafeInteger(n) && n > 0 ? n : null;
};

/**
 * เลข build ที่ติดตั้งอยู่ — native ใช้ได้ = ใช้ native · ไม่งั้นค่าสำรอง
 *
 * @example resolveBuildNumber('47', 46) // 47
 * @example resolveBuildNumber(null, 46) // 46
 */
export const resolveBuildNumber = (raw: unknown, fallback: number): number => parseNativeBuild(raw) ?? fallback;

/**
 * ชื่อเวอร์ชัน — รับเฉพาะรูปแบบตัวเลขจุด (3.389.0) · ไม่งั้นค่าสำรอง
 *
 * @example resolveVersionName('3.390.0', '3.389.0') // '3.390.0'
 * @example resolveVersionName('', '3.389.0')        // '3.389.0'
 */
export const resolveVersionName = (raw: unknown, fallback: string): string => {
  const text = typeof raw === 'string' ? raw.trim() : '';
  return /^\d{1,4}(\.\d{1,6}){1,3}$/.test(text) ? text : fallback;
};

/**
 * เลขที่ส่งใน header X-App-Build = เลข build จริงของเครื่อง แต่ไม่ต่ำกว่าชุดฟีเจอร์ที่โค้ดรองรับ (featureFloor)
 *
 * server ใช้ตัดสินด่าน (ไรเดอร์/eKYC ≥ 44 · ส่งมอบด้วย QR ≥ 43 · ตัวอัปเดตในแอป)
 * build จริงตั้งแต่ 46 ขึ้นไป → ส่งเลขจริง · ถ้า native ให้เลขต่ำผิดปกติ (เช่น build ทดสอบ) ยังไม่ตกด่านที่โค้ดรองรับแล้ว
 *
 * @example resolveRequestBuild(46, 44) // 46
 * @example resolveRequestBuild(12, 44) // 44
 */
export const resolveRequestBuild = (installedBuild: number | null | undefined, featureFloor: number): number => {
  const installed = typeof installedBuild === 'number' && Number.isSafeInteger(installedBuild) && installedBuild > 0 ? installedBuild : 0;
  return Math.max(installed, featureFloor);
};
