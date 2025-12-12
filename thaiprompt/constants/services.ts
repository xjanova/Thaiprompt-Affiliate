/**
 * Service Types & Categories - ประเภทบริการทั้งหมดในระบบ
 * สอดคล้องกับ ServiceCategorySeeder.php ในเว็บ
 */

// =====================================================
// ประเภทหมวดหมู่บริการ
// =====================================================

export type ServiceCategoryType =
  | 'health_beauty'
  | 'maintenance'
  | 'cleaning'
  | 'delivery'
  | 'education'
  | 'care'
  | 'other';

export type ServiceSlug =
  | 'massage-spa'
  | 'hair-salon'
  | 'beauty'
  | 'air-conditioner'
  | 'electrician'
  | 'plumber'
  | 'appliance-repair'
  | 'handyman'
  | 'house-cleaning'
  | 'car-wash'
  | 'laundry'
  | 'food-delivery'
  | 'parcel-delivery'
  | 'moving'
  | 'ride-hailing'
  | 'tutoring'
  | 'fitness-training'
  | 'pet-services'
  | 'nursing-care'
  | 'photography'
  | 'gardening'
  | 'others';

// =====================================================
// Interface
// =====================================================

export interface ServiceCategory {
  id: number;
  slug: ServiceSlug;
  name: string;
  shortName: string;
  description: string;
  icon: string;
  iconFamily: 'ionicons' | 'material' | 'fontawesome';
  color: string;
  gradient: [string, string];
  categoryType: ServiceCategoryType;
  isFeatured: boolean;
  basePrice?: number;
  priceUnit?: string;
}

export interface ServiceGroup {
  type: ServiceCategoryType;
  title: string;
  icon: string;
  services: ServiceCategory[];
}

// =====================================================
// รายการบริการทั้งหมด - 22 ประเภท
// =====================================================

export const SERVICE_CATEGORIES: ServiceCategory[] = [
  // =====================================================
  // Health & Beauty - สุขภาพและความงาม
  // =====================================================
  {
    id: 1,
    slug: 'massage-spa',
    name: 'นวดและสปา',
    shortName: 'นวด',
    description: 'บริการนวดแผนไทย นวดน้ำมัน สปาผ่อนคลาย',
    icon: '💆',
    iconFamily: 'ionicons',
    color: '#EC4899',
    gradient: ['#EC4899', '#DB2777'],
    categoryType: 'health_beauty',
    isFeatured: true,
    basePrice: 300,
    priceUnit: 'ชม.',
  },
  {
    id: 2,
    slug: 'hair-salon',
    name: 'ตัดผมและทำผม',
    shortName: 'ทำผม',
    description: 'ตัดผม ทำสีผม ดัดผม เซ็ตผมทรงต่างๆ',
    icon: '💇',
    iconFamily: 'ionicons',
    color: '#F472B6',
    gradient: ['#F472B6', '#EC4899'],
    categoryType: 'health_beauty',
    isFeatured: true,
    basePrice: 200,
    priceUnit: 'ครั้ง',
  },
  {
    id: 3,
    slug: 'beauty',
    name: 'เสริมสวย',
    shortName: 'สวย',
    description: 'ทำเล็บ แต่งหน้า ต่อขนตา บริการความงาม',
    icon: '💅',
    iconFamily: 'ionicons',
    color: '#F9A8D4',
    gradient: ['#F9A8D4', '#F472B6'],
    categoryType: 'health_beauty',
    isFeatured: true,
    basePrice: 250,
    priceUnit: 'ครั้ง',
  },

  // =====================================================
  // Maintenance - ซ่อมบำรุง
  // =====================================================
  {
    id: 4,
    slug: 'air-conditioner',
    name: 'ซ่อมแอร์',
    shortName: 'แอร์',
    description: 'ซ่อมแอร์ ล้างแอร์ ติดตั้งแอร์ เติมน้ำยา',
    icon: '❄️',
    iconFamily: 'ionicons',
    color: '#06B6D4',
    gradient: ['#06B6D4', '#0891B2'],
    categoryType: 'maintenance',
    isFeatured: true,
    basePrice: 400,
    priceUnit: 'เครื่อง',
  },
  {
    id: 5,
    slug: 'electrician',
    name: 'ช่างไฟฟ้า',
    shortName: 'ช่างไฟ',
    description: 'ซ่อมไฟฟ้า ติดตั้งระบบไฟ เดินสายไฟ',
    icon: '⚡',
    iconFamily: 'ionicons',
    color: '#FBBF24',
    gradient: ['#FBBF24', '#F59E0B'],
    categoryType: 'maintenance',
    isFeatured: false,
    basePrice: 300,
    priceUnit: 'จุด',
  },
  {
    id: 6,
    slug: 'plumber',
    name: 'ช่างประปา',
    shortName: 'ประปา',
    description: 'ซ่อมท่อน้ำ แก้ท่อตัน ติดตั้งสุขภัณฑ์',
    icon: '🔧',
    iconFamily: 'ionicons',
    color: '#3B82F6',
    gradient: ['#3B82F6', '#2563EB'],
    categoryType: 'maintenance',
    isFeatured: false,
    basePrice: 350,
    priceUnit: 'จุด',
  },
  {
    id: 7,
    slug: 'appliance-repair',
    name: 'ซ่อมเครื่องใช้ไฟฟ้า',
    shortName: 'ซ่อมเครื่อง',
    description: 'ซ่อมตู้เย็น เครื่องซักผ้า ไมโครเวฟ',
    icon: '🔌',
    iconFamily: 'ionicons',
    color: '#8B5CF6',
    gradient: ['#8B5CF6', '#7C3AED'],
    categoryType: 'maintenance',
    isFeatured: false,
    basePrice: 400,
    priceUnit: 'เครื่อง',
  },
  {
    id: 8,
    slug: 'handyman',
    name: 'ช่างทั่วไป',
    shortName: 'ช่างทั่วไป',
    description: 'งานช่างทั่วไป ประกอบเฟอร์นิเจอร์ ติดตั้ง',
    icon: '🛠️',
    iconFamily: 'ionicons',
    color: '#F97316',
    gradient: ['#F97316', '#EA580C'],
    categoryType: 'maintenance',
    isFeatured: false,
    basePrice: 250,
    priceUnit: 'ชม.',
  },

  // =====================================================
  // Cleaning - ทำความสะอาด
  // =====================================================
  {
    id: 9,
    slug: 'house-cleaning',
    name: 'ทำความสะอาดบ้าน',
    shortName: 'แม่บ้าน',
    description: 'บริการทำความสะอาดบ้าน คอนโด สำนักงาน',
    icon: '🏠',
    iconFamily: 'ionicons',
    color: '#10B981',
    gradient: ['#10B981', '#059669'],
    categoryType: 'cleaning',
    isFeatured: true,
    basePrice: 500,
    priceUnit: 'ครั้ง',
  },
  {
    id: 10,
    slug: 'car-wash',
    name: 'ล้างรถ',
    shortName: 'ล้างรถ',
    description: 'ล้างรถถึงที่ ขัดสี เคลือบแก้ว ดูแลรถ',
    icon: '🚗',
    iconFamily: 'ionicons',
    color: '#14B8A6',
    gradient: ['#14B8A6', '#0D9488'],
    categoryType: 'cleaning',
    isFeatured: false,
    basePrice: 200,
    priceUnit: 'คัน',
  },
  {
    id: 11,
    slug: 'laundry',
    name: 'ซักรีด',
    shortName: 'ซักรีด',
    description: 'รับซักรีดเสื้อผ้า ผ้าม่าน ผ้าปูที่นอน',
    icon: '👔',
    iconFamily: 'ionicons',
    color: '#22D3EE',
    gradient: ['#22D3EE', '#06B6D4'],
    categoryType: 'cleaning',
    isFeatured: false,
    basePrice: 50,
    priceUnit: 'กก.',
  },

  // =====================================================
  // Delivery - จัดส่งและขนส่ง
  // =====================================================
  {
    id: 12,
    slug: 'food-delivery',
    name: 'ส่งอาหาร',
    shortName: 'ส่งอาหาร',
    description: 'บริการส่งอาหารถึงที่ รวดเร็ว สดใหม่',
    icon: '🍔',
    iconFamily: 'ionicons',
    color: '#EF4444',
    gradient: ['#EF4444', '#DC2626'],
    categoryType: 'delivery',
    isFeatured: true,
    basePrice: 25,
    priceUnit: 'ครั้ง',
  },
  {
    id: 13,
    slug: 'parcel-delivery',
    name: 'ส่งพัสดุ',
    shortName: 'ส่งพัสดุ',
    description: 'รับส่งพัสดุ เอกสาร ของใช้ต่างๆ',
    icon: '📦',
    iconFamily: 'ionicons',
    color: '#F59E0B',
    gradient: ['#F59E0B', '#D97706'],
    categoryType: 'delivery',
    isFeatured: true,
    basePrice: 30,
    priceUnit: 'ครั้ง',
  },
  {
    id: 14,
    slug: 'moving',
    name: 'ขนย้าย',
    shortName: 'ขนย้าย',
    description: 'ขนย้ายบ้าน เฟอร์นิเจอร์ สิ่งของขนาดใหญ่',
    icon: '🚚',
    iconFamily: 'ionicons',
    color: '#78716C',
    gradient: ['#78716C', '#57534E'],
    categoryType: 'delivery',
    isFeatured: false,
    basePrice: 500,
    priceUnit: 'เที่ยว',
  },
  {
    id: 15,
    slug: 'ride-hailing',
    name: 'เรียกรถ',
    shortName: 'เรียกรถ',
    description: 'เรียกรถรับส่ง แท็กซี่ มอเตอร์ไซค์รับจ้าง',
    icon: '🏍️',
    iconFamily: 'ionicons',
    color: '#6366F1',
    gradient: ['#6366F1', '#4F46E5'],
    categoryType: 'delivery',
    isFeatured: false,
    basePrice: 20,
    priceUnit: 'กม.',
  },

  // =====================================================
  // Education - การศึกษา
  // =====================================================
  {
    id: 16,
    slug: 'tutoring',
    name: 'สอนพิเศษ',
    shortName: 'ติวเตอร์',
    description: 'สอนพิเศษทุกวิชา ทุกระดับชั้น ถึงบ้าน',
    icon: '📚',
    iconFamily: 'ionicons',
    color: '#A855F7',
    gradient: ['#A855F7', '#9333EA'],
    categoryType: 'education',
    isFeatured: true,
    basePrice: 400,
    priceUnit: 'ชม.',
  },
  {
    id: 17,
    slug: 'fitness-training',
    name: 'สอนฟิตเนส',
    shortName: 'PT',
    description: 'เทรนเนอร์ส่วนตัว โยคะ ออกกำลังกาย',
    icon: '🏋️',
    iconFamily: 'ionicons',
    color: '#D946EF',
    gradient: ['#D946EF', '#C026D3'],
    categoryType: 'education',
    isFeatured: false,
    basePrice: 500,
    priceUnit: 'ชม.',
  },

  // =====================================================
  // Care - ดูแลเอาใจใส่
  // =====================================================
  {
    id: 18,
    slug: 'pet-services',
    name: 'บริการสัตว์เลี้ยง',
    shortName: 'สัตว์เลี้ยง',
    description: 'รับเลี้ยงสัตว์ อาบน้ำ ตัดขน พาเดิน',
    icon: '🐕',
    iconFamily: 'ionicons',
    color: '#FB923C',
    gradient: ['#FB923C', '#F97316'],
    categoryType: 'care',
    isFeatured: false,
    basePrice: 300,
    priceUnit: 'ครั้ง',
  },
  {
    id: 19,
    slug: 'nursing-care',
    name: 'พยาบาลดูแลผู้ป่วย',
    shortName: 'พยาบาล',
    description: 'ดูแลผู้ป่วย ผู้สูงอายุ ที่บ้าน',
    icon: '👩‍⚕️',
    iconFamily: 'ionicons',
    color: '#F43F5E',
    gradient: ['#F43F5E', '#E11D48'],
    categoryType: 'care',
    isFeatured: false,
    basePrice: 800,
    priceUnit: 'วัน',
  },

  // =====================================================
  // Other - อื่นๆ
  // =====================================================
  {
    id: 20,
    slug: 'photography',
    name: 'ช่างภาพ',
    shortName: 'ช่างภาพ',
    description: 'ถ่ายภาพงานแต่ง งานอีเวนท์ ถ่ายสินค้า',
    icon: '📷',
    iconFamily: 'ionicons',
    color: '#64748B',
    gradient: ['#64748B', '#475569'],
    categoryType: 'other',
    isFeatured: false,
    basePrice: 1500,
    priceUnit: 'ชม.',
  },
  {
    id: 21,
    slug: 'gardening',
    name: 'ดูแลสวน',
    shortName: 'สวน',
    description: 'ตัดหญ้า ตัดแต่งต้นไม้ จัดสวน ดูแลต้นไม้',
    icon: '🌿',
    iconFamily: 'ionicons',
    color: '#84CC16',
    gradient: ['#84CC16', '#65A30D'],
    categoryType: 'other',
    isFeatured: false,
    basePrice: 300,
    priceUnit: 'ชม.',
  },
  {
    id: 22,
    slug: 'others',
    name: 'อื่นๆ',
    shortName: 'อื่นๆ',
    description: 'บริการอื่นๆ ที่ไม่อยู่ในหมวดหมู่',
    icon: '📋',
    iconFamily: 'ionicons',
    color: '#94A3B8',
    gradient: ['#94A3B8', '#64748B'],
    categoryType: 'other',
    isFeatured: false,
    basePrice: 200,
    priceUnit: 'ครั้ง',
  },
];

// =====================================================
// กลุ่มบริการ
// =====================================================

export const SERVICE_GROUPS: ServiceGroup[] = [
  {
    type: 'health_beauty',
    title: 'สุขภาพและความงาม',
    icon: '💗',
    services: SERVICE_CATEGORIES.filter((s) => s.categoryType === 'health_beauty'),
  },
  {
    type: 'maintenance',
    title: 'ซ่อมบำรุง',
    icon: '🔧',
    services: SERVICE_CATEGORIES.filter((s) => s.categoryType === 'maintenance'),
  },
  {
    type: 'cleaning',
    title: 'ทำความสะอาด',
    icon: '✨',
    services: SERVICE_CATEGORIES.filter((s) => s.categoryType === 'cleaning'),
  },
  {
    type: 'delivery',
    title: 'จัดส่งและขนส่ง',
    icon: '🚴',
    services: SERVICE_CATEGORIES.filter((s) => s.categoryType === 'delivery'),
  },
  {
    type: 'education',
    title: 'การศึกษา',
    icon: '🎓',
    services: SERVICE_CATEGORIES.filter((s) => s.categoryType === 'education'),
  },
  {
    type: 'care',
    title: 'ดูแลเอาใจใส่',
    icon: '❤️',
    services: SERVICE_CATEGORIES.filter((s) => s.categoryType === 'care'),
  },
  {
    type: 'other',
    title: 'อื่นๆ',
    icon: '📱',
    services: SERVICE_CATEGORIES.filter((s) => s.categoryType === 'other'),
  },
];

// =====================================================
// บริการยอดนิยม
// =====================================================

export const FEATURED_SERVICES = SERVICE_CATEGORIES.filter((s) => s.isFeatured);

// =====================================================
// Helper Functions
// =====================================================

export const getServiceBySlug = (slug: ServiceSlug): ServiceCategory | undefined => {
  return SERVICE_CATEGORIES.find((s) => s.slug === slug);
};

export const getServiceById = (id: number): ServiceCategory | undefined => {
  return SERVICE_CATEGORIES.find((s) => s.id === id);
};

export const getServicesByType = (type: ServiceCategoryType): ServiceCategory[] => {
  return SERVICE_CATEGORIES.filter((s) => s.categoryType === type);
};

export const formatServicePrice = (service: ServiceCategory): string => {
  if (!service.basePrice) return 'ราคาตามตกลง';
  return `฿${service.basePrice.toLocaleString()}/${service.priceUnit}`;
};
