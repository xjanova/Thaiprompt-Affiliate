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
 *
 * 4. usesCleartextTraffic — เปิดเฉพาะตอน build ทดสอบกับ backend ในเครื่อง
 *    (ตั้ง EXPO_PUBLIC_API_URL=http://10.0.2.2:8765/api/v1) เพราะ release build บล็อก HTTP ธรรมดา
 *    build ปกติ (ไม่ตั้ง EXPO_PUBLIC_API_URL หรือเป็น https) = ไม่แตะ manifest เลย
 */

const fs = require('fs');
const path = require('path');

// ต้องเปิด cleartext หรือไม่ — จริงเฉพาะเมื่อชี้ API ไปที่ http:// (QA ในเครื่องเท่านั้น)
const needsCleartextForLocalQa = /^http:\/\//i.test((process.env.EXPO_PUBLIC_API_URL || '').trim());

/**
 * config plugin แบบ inline: ใส่ android:usesCleartextTraffic="true" ให้ <application>
 * ใช้เฉพาะ build QA ที่ชี้ backend แบบ http:// — ห้ามใช้กับ build ที่ขึ้น Store
 *
 * @param {object} config ค่าคอนฟิก Expo
 * @returns {object} ค่าคอนฟิกที่ผ่าน mod ของ AndroidManifest แล้ว
 */
function withLocalQaCleartext(config) {
  const { withAndroidManifest } = require('expo/config-plugins');
  return withAndroidManifest(config, (modConfig) => {
    const application = modConfig.modResults.manifest.application?.[0];
    if (application) {
      application.$['android:usesCleartextTraffic'] = 'true';
    }
    return modConfig;
  });
}

/** แพ็กเกจแอปจันทรา (ดูดวงเชิงลึก) — ต้องตรงกับ JUNTRA_ANDROID_PACKAGE ใน services/juntraLauncher.ts */
const JUNTRA_ANDROID_PACKAGE = 'com.xjanova.juntra';

/**
 * config plugin แบบ inline: ประกาศ <queries><package android:name="com.xjanova.juntra"/></queries>
 * ทำไม: Android 11+ ซ่อนแอปอื่นจาก getLaunchIntentForPackage ถ้าไม่ประกาศ → ปุ่ม "เปิดแอปจันทรา" จะเด้งไป Play Store ตลอด
 * ประกาศทีละแพ็กเกจแบบนี้ไม่ต้องขอสิทธิ์ QUERY_ALL_PACKAGES (ผ่านนโยบาย Google Play)
 *
 * @param {object} config ค่าคอนฟิก Expo
 * @returns {object} ค่าคอนฟิกที่ผ่าน mod ของ AndroidManifest แล้ว
 */
function withJuntraPackageQuery(config) {
  const { withAndroidManifest } = require('expo/config-plugins');
  return withAndroidManifest(config, (modConfig) => {
    const manifest = modConfig.modResults.manifest;
    if (!manifest.queries || manifest.queries.length === 0) {
      manifest.queries = [{}];
    }
    const query = manifest.queries[0];
    query.package = query.package || [];
    const exists = query.package.some((item) => item.$ && item.$['android:name'] === JUNTRA_ANDROID_PACKAGE);
    if (!exists) {
      query.package.push({ $: { 'android:name': JUNTRA_ANDROID_PACKAGE } });
    }
    return modConfig;
  });
}

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

  const finalConfig = {
    ...config,
    plugins,
    android: {
      ...config.android,
      blockedPermissions,
      ...(googleServicesFile ? { googleServicesFile } : {}),
    },
  };

  // ทุก build: ให้มองเห็นแอปจันทรา (ปุ่มเปิดแอปจันทราในหน้าดูดวง)
  const withJuntra = withJuntraPackageQuery(finalConfig);

  // build ปกติ: ไม่แตะ cleartext — เปิดเฉพาะ QA ที่ชี้ http:// เท่านั้น
  if (!needsCleartextForLocalQa) {
    return withJuntra;
  }
  console.warn('[app.config] EXPO_PUBLIC_API_URL เป็น http:// — เปิด usesCleartextTraffic สำหรับ build ทดสอบในเครื่องเท่านั้น');
  return withLocalQaCleartext(withJuntra);
};
