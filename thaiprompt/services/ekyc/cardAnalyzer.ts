/**
 * ตัวต่อ native ของตัวตรวจภาพบัตร (cardQuality.ts) — ทำงานในเครื่องทั้งหมด ไม่ส่งรูปออกนอกเครื่องในขั้นนี้
 *
 *   ภาพถ่าย (file://) → expo-image-manipulator ถอดรหัสครั้งเดียว → ครอป/ย่อเฉพาะส่วนที่ต้องดู → JPEG base64
 *   → jpeg-js ถอดเป็นพิกเซล RGB → cardQuality ตัดสิน
 *
 * ⚠️ ห้าม import expo-image-manipulator ตรงๆ: JS ของแพ็กเกจเรียก requireNativeModule ตอนโหลด
 *    แอปรุ่นที่ binary ยังไม่มี native module (dev client เก่า) จะล้มทันที → เช็ค requireOptionalNativeModule ก่อน
 *    ไม่มี = ตัดสินจากรูปหน้าบนบัตรอย่างเดียว (ทำงานแบบเดิม)
 * ⚠️ ห้ามพิมพ์ uri/พิกเซล/ผลตรวจลง log (ข้อมูลบัตร)
 */

import { requireOptionalNativeModule } from 'expo';
import { decode as decodeJpeg } from 'jpeg-js';
import {
  analyzeCardShot,
  base64ToBytes,
  clampRect,
  type CardCropFn,
  type CardShotInput,
  type CardVerdict,
  type RgbImage,
} from './cardQuality';
import { dropTempFile } from './tempFiles';

type ManipulatorPackage = typeof import('expo-image-manipulator');
type NativeImage = Awaited<ReturnType<ReturnType<ManipulatorPackage['ImageManipulator']['manipulate']>['renderAsync']>>;

/** undefined = ยังไม่เคยโหลด · null = build นี้ไม่มี */
let manipulator: ManipulatorPackage | null | undefined;

const loadManipulator = (): ManipulatorPackage | null => {
  if (manipulator !== undefined) return manipulator;
  try {
    manipulator = requireOptionalNativeModule('ExpoImageManipulator')
      ? // eslint-disable-next-line @typescript-eslint/no-require-imports
        (require('expo-image-manipulator') as ManipulatorPackage)
      : null;
  } catch {
    manipulator = null;
  }
  return manipulator;
};

/** build นี้อ่านพิกเซลภาพบัตรในเครื่องได้หรือไม่ (ไม่ได้ = ตรวจได้แค่ตำแหน่งจากรูปหน้า) */
export const isCardAnalyzerAvailable = (): boolean => loadManipulator() !== null;

/** ปล่อยหน่วยความจำ native ทันที (บิตแมปภาพเต็ม ~20 MB ต่อภาพ ไม่รอ GC) */
const release = (obj: { release?: () => void } | null | undefined): void => {
  try {
    obj?.release?.();
  } catch {
    // ปล่อยแล้ว/ปล่อยไม่ได้ — ไม่เป็นไร
  }
};

/**
 * ตรวจภาพบัตร 1 ภาพจากไฟล์ภาพถ่าย (ไม่ลบไฟล์ต้นฉบับ — ผู้เรียกเป็นเจ้าของ)
 * ล้มเหลวกลางทาง = ตัดสินจากรูปหน้าอย่างเดียว (light/sharp = null → หน้าจอไม่ถ่ายอัตโนมัติจากภาพนี้)
 */
export const analyzeCardPhoto = async (uri: string, input: CardShotInput): Promise<CardVerdict> => {
  const pkg = loadManipulator();
  if (!pkg) return analyzeCardShot(input, null);

  const temps: string[] = [];
  let base: NativeImage | null = null;
  try {
    const ctx = pkg.ImageManipulator.manipulate(uri);
    try {
      base = await ctx.renderAsync();
    } finally {
      release(ctx);
    }
    const src = base;
    // ภาพที่ถอดได้ต้องหมุนตรงกับภาพที่ตรวจใบหน้า (expo-camera หมุนพิกเซลให้แล้ว) — ไม่ตรง = ไม่ครอป
    const kx = src.width / input.width;
    const ky = src.height / input.height;
    if (!(kx > 0) || !(ky > 0) || Math.abs(kx - ky) > 0.02) return analyzeCardShot(input, null);

    const crop: CardCropFn = async (rect, outWidth): Promise<RgbImage | null> => {
      const r = clampRect({ x: rect.x * kx, y: rect.y * ky, w: rect.w * kx, h: rect.h * ky }, { w: src.width, h: src.height });
      if (!r) return null;
      const ctxCrop = pkg.ImageManipulator.manipulate(src).crop({ originX: r.x, originY: r.y, width: r.w, height: r.h });
      const outW = Math.max(8, Math.min(r.w, Math.round(outWidth)));
      if (outW < r.w) ctxCrop.resize({ width: outW });
      let img: NativeImage | null = null;
      try {
        img = await ctxCrop.renderAsync();
        const saved = await img.saveAsync({ format: pkg.SaveFormat.JPEG, compress: 0.92, base64: true });
        if (saved.uri) temps.push(saved.uri);
        if (!saved.base64) return null;
        const decoded = decodeJpeg(base64ToBytes(saved.base64), {
          useTArray: true,
          formatAsRGBA: false,
          maxResolutionInMP: 2,
          maxMemoryUsageInMB: 64,
        });
        return { width: decoded.width, height: decoded.height, data: decoded.data };
      } catch {
        return null;
      } finally {
        release(img);
        release(ctxCrop);
      }
    };

    return await analyzeCardShot(input, crop);
  } catch {
    return analyzeCardShot(input, null);
  } finally {
    release(base);
    temps.forEach(dropTempFile);
  }
};
