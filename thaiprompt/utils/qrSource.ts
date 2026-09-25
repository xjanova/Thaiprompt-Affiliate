/**
 * แปลงค่า QR จาก server เป็นสิ่งที่วาดได้ในแอป
 *
 * server ส่ง qr_code ได้หลายแบบ:
 *   - data:image/svg+xml;base64,...  (BaconQrCode — ส่วนใหญ่) → ต้องวาดด้วย SvgXml (Image ของ RN วาด SVG ไม่ได้)
 *   - data:image/png;base64,...      → ใช้ Image ได้ตรงๆ
 *   - base64 ของ PNG ล้วน (ไม่มี data:) → เติมหัว data:image/png;base64,
 *   - qr_code_url (https) → ใช้ Image
 *
 * ใช้ร่วมกันระหว่างหน้าเติมเงินและจุดอื่นที่แสดง QR PromptPay
 */

export type QrSource = { kind: 'svg'; xml: string } | { kind: 'image'; uri: string } | null;

const B64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

/** ถอด base64 เป็นข้อความ (มี atob ใช้ atob · ไม่มีถอดเอง) */
const decodeBase64 = (input: string): string => {
  const clean = input.replace(/[^A-Za-z0-9+/=]/g, '');
  const g = globalThis as { atob?: (s: string) => string };
  if (typeof g.atob === 'function') {
    return g.atob(clean);
  }
  let out = '';
  let buffer = 0;
  let bits = 0;
  for (const ch of clean) {
    if (ch === '=') break;
    const v = B64.indexOf(ch);
    if (v < 0) continue;
    buffer = (buffer << 6) | v;
    bits += 6;
    if (bits >= 8) {
      bits -= 8;
      out += String.fromCharCode((buffer >> bits) & 0xff);
    }
  }
  return out;
};

/**
 * @param qr ค่า qr_code จาก server
 * @param qrUrl ค่า qr_code_url จาก server (ถ้ามี)
 * @returns svg (ส่งให้ SvgXml) / image (ส่งให้ Image) / null = ไม่มี QR ที่ใช้ได้
 */
export const resolveQrSource = (qr: string | null | undefined, qrUrl?: string | null): QrSource => {
  if (typeof qr === 'string' && qr.startsWith('data:image/svg+xml')) {
    const comma = qr.indexOf(',');
    if (comma > 0) {
      const meta = qr.slice(0, comma);
      const body = qr.slice(comma + 1);
      try {
        const xml = meta.includes(';base64') ? decodeBase64(body) : decodeURIComponent(body);
        if (xml.includes('<svg')) return { kind: 'svg', xml };
      } catch {
        // ถอดไม่ได้ → ลองทางอื่น
      }
    }
  }
  if (typeof qr === 'string' && qr.startsWith('data:image/')) {
    return { kind: 'image', uri: qr };
  }
  if (typeof qrUrl === 'string' && /^https?:\/\//i.test(qrUrl)) {
    return { kind: 'image', uri: qrUrl };
  }
  if (typeof qr === 'string' && /^[A-Za-z0-9+/=\s]{100,}$/.test(qr)) {
    return { kind: 'image', uri: `data:image/png;base64,${qr.replace(/\s/g, '')}` };
  }
  return null;
};
