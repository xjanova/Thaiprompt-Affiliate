/**
 * โลโก้แบรนด์สำหรับปุ่มเข้าสู่ระบบด้วยบัญชีภายนอก
 *
 * - GoogleGlyph: ตัว G สี่สีตามแนวทางแบรนด์ Google (ห้ามเปลี่ยนสี — วางบนพื้นขาว/สว่างเท่านั้น)
 * - FacebookGlyph: โลโก้ f วงกลม ใช้สีเดียว (ค่าเริ่มต้นขาว สำหรับปุ่มน้ำเงิน Facebook)
 *
 * ส่งเข้า Button3D ผ่าน prop icon ได้เลย (IconSlot รับ element)
 */

import React from 'react';
import Svg, { Path } from 'react-native-svg';

interface GlyphProps {
  size?: number;
}

export const GoogleGlyph = ({ size = 21 }: GlyphProps) => (
  <Svg width={size} height={size} viewBox="0 0 18 18" importantForAccessibility="no-hide-descendants">
    <Path
      fill="#4285F4"
      d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844a4.14 4.14 0 0 1-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"
    />
    <Path
      fill="#34A853"
      d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z"
    />
    <Path
      fill="#FBBC05"
      d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z"
    />
    <Path
      fill="#EA4335"
      d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"
    />
  </Svg>
);

export const FacebookGlyph = ({ size = 21, color = '#FFFFFF' }: GlyphProps & { color?: string }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" importantForAccessibility="no-hide-descendants">
    <Path
      fill={color}
      d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
    />
  </Svg>
);
