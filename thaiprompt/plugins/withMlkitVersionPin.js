/**
 * withMlkitVersionPin — ล็อกเวอร์ชัน Google ML Kit ที่แพ็กเกจ @infinitered/react-native-mlkit-* ขอแบบลอย
 *
 * ทำไม: android/build.gradle ของแพ็กเกจขอ 'com.google.mlkit:face-detection:16.+' และ 'vision-common:17.+'
 *   → ทุก build ไปถามเวอร์ชันล่าสุดจาก dl.google.com ใหม่ วันไหน Google ออกรุ่นใหม่ แอปก็เปลี่ยนไส้โดยไม่รู้ตัว
 *   ล็อกไว้ = build ซ้ำได้ผลเดิม (อัปเกรดเมื่อเราตั้งใจเท่านั้น)
 *
 * เวอร์ชันที่ล็อก = รุ่นล่าสุดที่ 16.+ / 17.+ ได้จริง (maven-metadata.xml ของ dl.google.com ที่ Gradle ดึงมา 2026-10-04)
 *   face-detection 16.1.7 (latest/release) · vision-common 17.3.0 (latest/release)
 *
 * วิธี: เติม resolutionStrategy.force ท้าย android/build.gradle (groovy) ครั้งเดียว (มีเครื่องหมายกันใส่ซ้ำ)
 * build.gradle ไม่ใช่ groovy (เช่น .kts ในอนาคต) → ข้ามพร้อมคำเตือน ไม่ทำให้ prebuild ล้ม
 */

const MLKIT_PINNED_VERSIONS = {
  'com.google.mlkit:face-detection': '16.1.7',
  'com.google.mlkit:vision-common': '17.3.0',
};

const MLKIT_PIN_MARKER = '// @thaiprompt/mlkit-version-pin';

/**
 * เติมบล็อกล็อกเวอร์ชันท้าย build.gradle ระดับโปรเจกต์ (เรียกซ้ำได้ ไม่ใส่ซ้ำ)
 *
 * @param {string} contents เนื้อหา android/build.gradle
 * @returns {string} เนื้อหาที่มีบล็อก force แล้ว
 */
function pinMlkitVersions(contents) {
  if (contents.includes(MLKIT_PIN_MARKER)) return contents;
  const forces = Object.entries(MLKIT_PINNED_VERSIONS)
    .map(([module, version]) => `      force '${module}:${version}'`)
    .join('\n');
  return [
    contents.replace(/\s*$/, ''),
    '',
    MLKIT_PIN_MARKER,
    'allprojects {',
    '  configurations.all {',
    '    resolutionStrategy {',
    forces,
    '    }',
    '  }',
    '}',
    '',
  ].join('\n');
}

/**
 * config plugin: ล็อกเวอร์ชัน ML Kit ใน android/build.gradle
 *
 * @param {object} config ค่าคอนฟิก Expo
 * @returns {object} ค่าคอนฟิกที่ผ่าน mod ของ build.gradle แล้ว
 */
function withMlkitVersionPin(config) {
  const { withProjectBuildGradle } = require('expo/config-plugins');
  return withProjectBuildGradle(config, (modConfig) => {
    if (modConfig.modResults.language === 'groovy') {
      modConfig.modResults.contents = pinMlkitVersions(modConfig.modResults.contents);
    } else {
      console.warn('[withMlkitVersionPin] android/build.gradle ไม่ใช่ groovy — ข้ามการล็อกเวอร์ชัน ML Kit');
    }
    return modConfig;
  });
}

module.exports = withMlkitVersionPin;
module.exports.pinMlkitVersions = pinMlkitVersions;
module.exports.MLKIT_PINNED_VERSIONS = MLKIT_PINNED_VERSIONS;
