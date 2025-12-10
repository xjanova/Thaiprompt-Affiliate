/**
 * SVG Icon Library - TP UltraAPP
 * ไอคอน SVG คุณภาพสูงสำหรับแอพ
 * ไม่พึ่งพา font loading - ทำงานได้ทันที 100%
 */

import React from 'react';
import Svg, { Path, Circle, Rect, G, Defs, LinearGradient, Stop } from 'react-native-svg';

interface IconProps {
  size?: number;
  color?: string;
  strokeWidth?: number;
}

// ===== Navigation Icons =====

export const ArrowBackIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M19 12H5M12 19l-7-7 7-7"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const ArrowForwardIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M5 12h14M12 5l7 7-7 7"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const ChevronRightIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M9 18l6-6-6-6"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const ChevronLeftIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M15 18l-6-6 6-6"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const MenuIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M3 12h18M3 6h18M3 18h18"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const CloseIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M18 6L6 18M6 6l12 12"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

// ===== Theme Icons =====

export const SunIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Circle cx="12" cy="12" r="5" stroke={color} strokeWidth={2} />
    <Path
      d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
    />
  </Svg>
);

export const MoonIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const PhoneIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Rect x="5" y="2" width="14" height="20" rx="2" stroke={color} strokeWidth={2} />
    <Path d="M12 18h.01" stroke={color} strokeWidth={2} strokeLinecap="round" />
  </Svg>
);

// ===== Settings Icons =====

export const SettingsIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Circle cx="12" cy="12" r="3" stroke={color} strokeWidth={2} />
    <Path
      d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const GlobeIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Circle cx="12" cy="12" r="10" stroke={color} strokeWidth={2} />
    <Path
      d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

// ===== Security Icons =====

export const ShieldCheckIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M9 12l2 2 4-4"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const ShieldIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const LockIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Rect x="3" y="11" width="18" height="11" rx="2" stroke={color} strokeWidth={2} />
    <Path
      d="M7 11V7a5 5 0 0110 0v4"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const FingerprintIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M2 12C2 6.5 6.5 2 12 2a10 10 0 018 4"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
    />
    <Path
      d="M5 19.5C5.5 18 6 15 6 12c0-3.3 2.7-6 6-6 1.8 0 3.4.8 4.5 2"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
    />
    <Path
      d="M8.5 22c.4-1.8.5-4 .5-6"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
    />
    <Path
      d="M12 22c.3-2.3.5-4.5.5-7"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
    />
    <Path
      d="M18 13c0 3.4-.7 6.3-1.7 8.4"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
    />
    <Path
      d="M22 16.3c-.6 2.3-1.4 4.3-2.5 6"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
    />
    <Path
      d="M10 12a2 2 0 104 0c0-1.1-.9-2-2-2"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
    />
    <Path
      d="M15 22c.3-1.7.5-3.5.5-6a3.5 3.5 0 00-7 0"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
    />
  </Svg>
);

export const EyeIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Circle cx="12" cy="12" r="3" stroke={color} strokeWidth={2} />
  </Svg>
);

export const EyeOffIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path d="M1 1l22 22" stroke={color} strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" />
  </Svg>
);

// ===== Notification Icons =====

export const BellIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const MailIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M22 6l-10 7L2 6"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

// ===== Location Icons =====

export const LocationIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Circle cx="12" cy="10" r="3" stroke={color} strokeWidth={2} />
  </Svg>
);

export const NavigationIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M3 11l19-9-9 19-2-8-8-2z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const CompassIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Circle cx="12" cy="12" r="10" stroke={color} strokeWidth={2} />
    <Path
      d="M16.24 7.76l-2.12 6.36-6.36 2.12 2.12-6.36 6.36-2.12z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

// ===== Action Icons =====

export const CheckIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M20 6L9 17l-5-5"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const CheckCircleIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M22 11.08V12a10 10 0 11-5.93-9.14"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M22 4L12 14.01l-3-3"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const PlusIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M12 5v14M5 12h14"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const MinusIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path d="M5 12h14" stroke={color} strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" />
  </Svg>
);

export const EditIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const TrashIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path d="M10 11v6M14 11v6" stroke={color} strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" />
  </Svg>
);

export const CopyIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Rect x="9" y="9" width="13" height="13" rx="2" stroke={color} strokeWidth={2} />
    <Path
      d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const RefreshIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M23 4v6h-6M1 20v-6h6"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

// ===== User Icons =====

export const UserIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Circle cx="12" cy="7" r="4" stroke={color} strokeWidth={2} />
  </Svg>
);

export const UsersIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Circle cx="9" cy="7" r="4" stroke={color} strokeWidth={2} />
    <Path
      d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const LogOutIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M16 17l5-5-5-5M21 12H9"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

// ===== Document Icons =====

export const FileTextIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M14 2v6h6M16 13H8M16 17H8M10 9H8"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

// ===== Media Icons =====

export const CameraIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Circle cx="12" cy="13" r="4" stroke={color} strokeWidth={2} />
  </Svg>
);

export const CameraRotateIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M8 13a4 4 0 018 0"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M16 13a4 4 0 01-8 0"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const FlashIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const FlashOffIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M16.16 7.84L13 2l-1.46 5.85M6.34 6.34L3 14h6M21 14h-4.34l-1 8-5.66-6.83M1 1l22 22"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const QRCodeIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Rect x="3" y="3" width="7" height="7" rx="1" stroke={color} strokeWidth={2} />
    <Rect x="14" y="3" width="7" height="7" rx="1" stroke={color} strokeWidth={2} />
    <Rect x="3" y="14" width="7" height="7" rx="1" stroke={color} strokeWidth={2} />
    <Rect x="14" y="14" width="3" height="3" stroke={color} strokeWidth={2} />
    <Rect x="18" y="14" width="3" height="3" stroke={color} strokeWidth={2} />
    <Rect x="14" y="18" width="3" height="3" stroke={color} strokeWidth={2} />
    <Rect x="18" y="18" width="3" height="3" stroke={color} strokeWidth={2} />
  </Svg>
);

// ===== Misc Icons =====

export const StarIcon = ({ size = 24, color = '#000', fill }: IconProps & { fill?: string }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill={fill || 'none'}>
    <Path
      d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const HeartIcon = ({ size = 24, color = '#000', fill }: IconProps & { fill?: string }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill={fill || 'none'}>
    <Path
      d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const InfoIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Circle cx="12" cy="12" r="10" stroke={color} strokeWidth={2} />
    <Path d="M12 16v-4M12 8h.01" stroke={color} strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" />
  </Svg>
);

export const AlertCircleIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Circle cx="12" cy="12" r="10" stroke={color} strokeWidth={2} />
    <Path d="M12 8v4M12 16h.01" stroke={color} strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" />
  </Svg>
);

export const LinkIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export const ExternalLinkIcon = ({ size = 24, color = '#000' }: IconProps) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

// ===== Export all icons as a map for dynamic usage =====

export const IconMap = {
  'arrow-back': ArrowBackIcon,
  'arrow-forward': ArrowForwardIcon,
  'chevron-right': ChevronRightIcon,
  'chevron-left': ChevronLeftIcon,
  'chevron-forward': ChevronRightIcon,
  'menu': MenuIcon,
  'close': CloseIcon,
  'sunny': SunIcon,
  'moon': MoonIcon,
  'phone-portrait': PhoneIcon,
  'settings': SettingsIcon,
  'globe': GlobeIcon,
  'language': GlobeIcon,
  'shield-checkmark': ShieldCheckIcon,
  'shield': ShieldIcon,
  'lock-closed': LockIcon,
  'finger-print': FingerprintIcon,
  'eye': EyeIcon,
  'eye-off': EyeOffIcon,
  'notifications': BellIcon,
  'bell': BellIcon,
  'mail': MailIcon,
  'location': LocationIcon,
  'navigate': NavigationIcon,
  'compass': CompassIcon,
  'checkmark': CheckIcon,
  'checkmark-circle': CheckCircleIcon,
  'add': PlusIcon,
  'remove': MinusIcon,
  'pencil': EditIcon,
  'create': EditIcon,
  'trash': TrashIcon,
  'copy': CopyIcon,
  'copy-outline': CopyIcon,
  'refresh': RefreshIcon,
  'person': UserIcon,
  'people': UsersIcon,
  'log-out': LogOutIcon,
  'document-text': FileTextIcon,
  'camera': CameraIcon,
  'camera-outline': CameraIcon,
  'camera-reverse': CameraRotateIcon,
  'camera-reverse-outline': CameraRotateIcon,
  'flash': FlashIcon,
  'flash-outline': FlashOffIcon,
  'flash-off': FlashOffIcon,
  'qr-code': QRCodeIcon,
  'scan': QRCodeIcon,
  'scan-outline': QRCodeIcon,
  'star': StarIcon,
  'heart': HeartIcon,
  'information-circle': InfoIcon,
  'alert-circle': AlertCircleIcon,
  'link': LinkIcon,
  'open': ExternalLinkIcon,
  'open-outline': ExternalLinkIcon,
};

// ===== Dynamic Icon Component =====

interface DynamicIconProps extends IconProps {
  name: keyof typeof IconMap;
}

export const Icon = ({ name, size = 24, color = '#000', ...props }: DynamicIconProps) => {
  const IconComponent = IconMap[name];
  if (!IconComponent) {
    console.warn(`Icon "${name}" not found`);
    return null;
  }
  return <IconComponent size={size} color={color} {...props} />;
};

export default Icon;
