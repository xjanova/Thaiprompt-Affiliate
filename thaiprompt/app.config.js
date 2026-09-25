/**
 * app.config.js — ค่าคอนฟิกแบบไดนามิก (ต่อยอดจาก app.json)
 *
 * app.json ยังเป็นแหล่งค่าหลัก ไฟล์นี้เติมเฉพาะส่วนที่ต้องตัดสินใจตอน build:
 *
 * 1. android.googleServicesFile — ต้องมีเพื่อให้ FCM (push notification) ทำงานใน release build
 *    - บน EAS: สร้าง file env var ชื่อ GOOGLE_SERVICES_JSON (ค่า = ไฟล์ google-services.json)
 *      EAS จะส่ง path ของไฟล์มาใน process.env.GOOGLE_SERVICES_JSON
 *    - เครื่อง dev: วางไฟล์ ./google-services.json (อยู่ใน .gitignore ห้าม commit — repo เป็น PUBLIC)
 *    - ไม่มีทั้งสองอย่าง = build ได้ตามปกติ แต่ push บน Android จะไม่ทำงาน (มีคำเตือนตอน build)
 *
 * 2. expo-dev-client — prebuild ใส่ plugin นี้ให้อัตโนมัติเสมอเมื่อแพ็กเกจติดตั้งอยู่ (legacy auto plugin)
 *    จึงตั้งค่าเองแทน: development = ค่าปกติ / โปรไฟล์อื่น = addGeneratedScheme: false
 *    (ไม่ให้ scheme exp+thaiprompt ของ dev launcher ติดไปกับแอปที่ขึ้น Play Store
 *     ส่วนโค้ด dev launcher/dev menu ถูกปิดใน release build อยู่แล้วจาก source set disableInRelease)
 *    build dev ในเครื่องเอง: ตั้ง APP_VARIANT=development ก่อน `npx expo run:android`
 *
 * 3. SYSTEM_ALERT_WINDOW — บล็อกใน production แต่ปล่อยไว้ใน development
 *    (React Native ใช้ใน debug build สำหรับ perf overlay)
 */

const fs = require('fs');
const path = require('path');

// โปรไฟล์ EAS ที่กำลัง build (EAS ตั้งให้อัตโนมัติ) หรือ APP_VARIANT ที่ตั้งใน eas.json
const buildProfile = process.env.EAS_BUILD_PROFILE || '';
const isDevelopmentVariant =
  process.env.APP_VARIANT === 'development' || buildProfile === 'development';

/**
 * หา path ของ google-services.json
 * ลำดับ: env GOOGLE_SERVICES_JSON (EAS file env var) → ./google-services.json → ไม่มี
 *
 * @returns {string | undefined} path ที่ใช้ได้ หรือ undefined ถ้าไม่มีไฟล์
 */
function resolveGoogleServicesFile() {
  const fromEnv = process.env.GOOGLE_SERVICES_JSON;
  if (fromEnv && fs.existsSync(fromEnv)) {
    return fromEnv;
  }

  const localFile = path.join(__dirname, 'google-services.json');
  if (fs.existsSync(localFile)) {
    return './google-services.json';
  }

  return undefined;
}

module.exports = ({ config }) => {
  const googleServicesFile = resolveGoogleServicesFile();

  // ถ้า build release บน EAS แล้วไม่มีไฟล์ ให้เตือน (ไม่ทำให้ build ล้ม)
  if (!googleServicesFile && buildProfile && buildProfile !== 'development') {
    console.warn(
      '[app.config] ไม่พบ google-services.json (ตั้ง EAS file env var GOOGLE_SERVICES_JSON) — push notification บน Android จะใช้ไม่ได้'
    );
  }

  // ตัด expo-dev-client ที่อาจมีใน app.json ออกก่อน แล้วใส่ใหม่ตาม variant
  const plugins = (config.plugins || []).filter(
    (plugin) => (Array.isArray(plugin) ? plugin[0] : plugin) !== 'expo-dev-client'
  );
  plugins.push(
    isDevelopmentVariant ? 'expo-dev-client' : ['expo-dev-client', { addGeneratedScheme: false }]
  );

  const blockedPermissions = (config.android?.blockedPermissions || []).filter(
    (permission) =>
      !(isDevelopmentVariant && permission === 'android.permission.SYSTEM_ALERT_WINDOW')
  );

  return {
    ...config,
    plugins,
    android: {
      ...config.android,
      blockedPermissions,
      ...(googleServicesFile ? { googleServicesFile } : {}),
    },
  };
};
