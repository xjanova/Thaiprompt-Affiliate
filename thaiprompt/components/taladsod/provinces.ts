/**
 * จังหวัดทั่วประเทศ + พิกัดตัวเมืองโดยประมาณ
 *
 * ใช้เป็นทางเลือกเมื่อผู้ใช้ไม่อนุญาตตำแหน่ง: เลือกจังหวัด → ค้นร้านที่เปิดอยู่ในรัศมี 50 กม. จากตัวเมือง
 * (พิกัดคลาดเคลื่อนได้หลายกิโลเมตร ใช้ค้นหาคร่าวๆ เท่านั้น ไม่ใช้เป็นจุดส่งของ)
 */

export interface ProvinceCenter {
  name: string;
  latitude: number;
  longitude: number;
  region: 'กลาง' | 'ตะวันออก' | 'ตะวันตก' | 'เหนือ' | 'อีสาน' | 'ใต้';
}

export const PROVINCE_SEARCH_RADIUS_KM = 50;

export const PROVINCES: ProvinceCenter[] = [
  { name: 'กรุงเทพมหานคร', latitude: 13.7563, longitude: 100.5018, region: 'กลาง' },
  { name: 'นนทบุรี', latitude: 13.8621, longitude: 100.5144, region: 'กลาง' },
  { name: 'ปทุมธานี', latitude: 14.0208, longitude: 100.525, region: 'กลาง' },
  { name: 'สมุทรปราการ', latitude: 13.5991, longitude: 100.5998, region: 'กลาง' },
  { name: 'สมุทรสาคร', latitude: 13.5475, longitude: 100.2744, region: 'กลาง' },
  { name: 'นครปฐม', latitude: 13.8199, longitude: 100.0621, region: 'กลาง' },
  { name: 'พระนครศรีอยุธยา', latitude: 14.3532, longitude: 100.5689, region: 'กลาง' },
  { name: 'อ่างทอง', latitude: 14.5896, longitude: 100.455, region: 'กลาง' },
  { name: 'ลพบุรี', latitude: 14.7995, longitude: 100.6534, region: 'กลาง' },
  { name: 'สิงห์บุรี', latitude: 14.8936, longitude: 100.3967, region: 'กลาง' },
  { name: 'ชัยนาท', latitude: 15.1851, longitude: 100.1251, region: 'กลาง' },
  { name: 'สระบุรี', latitude: 14.5289, longitude: 100.9101, region: 'กลาง' },
  { name: 'นครนายก', latitude: 14.2069, longitude: 101.213, region: 'กลาง' },
  { name: 'ปราจีนบุรี', latitude: 14.0509, longitude: 101.3717, region: 'ตะวันออก' },
  { name: 'สระแก้ว', latitude: 13.824, longitude: 102.0646, region: 'ตะวันออก' },
  { name: 'ฉะเชิงเทรา', latitude: 13.6904, longitude: 101.0779, region: 'ตะวันออก' },
  { name: 'ชลบุรี', latitude: 13.3611, longitude: 100.9847, region: 'ตะวันออก' },
  { name: 'ระยอง', latitude: 12.6814, longitude: 101.2816, region: 'ตะวันออก' },
  { name: 'จันทบุรี', latitude: 12.6113, longitude: 102.1039, region: 'ตะวันออก' },
  { name: 'ตราด', latitude: 12.2428, longitude: 102.5175, region: 'ตะวันออก' },
  { name: 'ราชบุรี', latitude: 13.5283, longitude: 99.8134, region: 'ตะวันตก' },
  { name: 'กาญจนบุรี', latitude: 14.0228, longitude: 99.5328, region: 'ตะวันตก' },
  { name: 'สุพรรณบุรี', latitude: 14.4745, longitude: 100.1177, region: 'ตะวันตก' },
  { name: 'สมุทรสงคราม', latitude: 13.4098, longitude: 100.0023, region: 'ตะวันตก' },
  { name: 'เพชรบุรี', latitude: 13.1119, longitude: 99.9398, region: 'ตะวันตก' },
  { name: 'ประจวบคีรีขันธ์', latitude: 11.8124, longitude: 99.7973, region: 'ตะวันตก' },
  { name: 'เชียงใหม่', latitude: 18.7883, longitude: 98.9853, region: 'เหนือ' },
  { name: 'เชียงราย', latitude: 19.9105, longitude: 99.8406, region: 'เหนือ' },
  { name: 'ลำพูน', latitude: 18.5745, longitude: 99.0087, region: 'เหนือ' },
  { name: 'ลำปาง', latitude: 18.2888, longitude: 99.4909, region: 'เหนือ' },
  { name: 'พะเยา', latitude: 19.1665, longitude: 99.9019, region: 'เหนือ' },
  { name: 'แพร่', latitude: 18.1446, longitude: 100.1403, region: 'เหนือ' },
  { name: 'น่าน', latitude: 18.7756, longitude: 100.773, region: 'เหนือ' },
  { name: 'แม่ฮ่องสอน', latitude: 19.302, longitude: 97.9654, region: 'เหนือ' },
  { name: 'อุตรดิตถ์', latitude: 17.62, longitude: 100.0993, region: 'เหนือ' },
  { name: 'ตาก', latitude: 16.884, longitude: 99.1259, region: 'เหนือ' },
  { name: 'สุโขทัย', latitude: 17.0078, longitude: 99.823, region: 'เหนือ' },
  { name: 'พิษณุโลก', latitude: 16.8211, longitude: 100.2659, region: 'เหนือ' },
  { name: 'พิจิตร', latitude: 16.4429, longitude: 100.3487, region: 'เหนือ' },
  { name: 'กำแพงเพชร', latitude: 16.4828, longitude: 99.5227, region: 'เหนือ' },
  { name: 'เพชรบูรณ์', latitude: 16.419, longitude: 101.1606, region: 'เหนือ' },
  { name: 'นครสวรรค์', latitude: 15.7047, longitude: 100.1372, region: 'เหนือ' },
  { name: 'อุทัยธานี', latitude: 15.3835, longitude: 100.0246, region: 'เหนือ' },
  { name: 'นครราชสีมา', latitude: 14.9799, longitude: 102.0978, region: 'อีสาน' },
  { name: 'บุรีรัมย์', latitude: 14.993, longitude: 103.1029, region: 'อีสาน' },
  { name: 'สุรินทร์', latitude: 14.8818, longitude: 103.4936, region: 'อีสาน' },
  { name: 'ศรีสะเกษ', latitude: 15.1186, longitude: 104.322, region: 'อีสาน' },
  { name: 'อุบลราชธานี', latitude: 15.2448, longitude: 104.8473, region: 'อีสาน' },
  { name: 'ยโสธร', latitude: 15.7926, longitude: 104.1453, region: 'อีสาน' },
  { name: 'อำนาจเจริญ', latitude: 15.8657, longitude: 104.6258, region: 'อีสาน' },
  { name: 'มุกดาหาร', latitude: 16.5425, longitude: 104.7235, region: 'อีสาน' },
  { name: 'นครพนม', latitude: 17.392, longitude: 104.7695, region: 'อีสาน' },
  { name: 'สกลนคร', latitude: 17.1664, longitude: 104.1486, region: 'อีสาน' },
  { name: 'หนองคาย', latitude: 17.8783, longitude: 102.742, region: 'อีสาน' },
  { name: 'บึงกาฬ', latitude: 18.3609, longitude: 103.6464, region: 'อีสาน' },
  { name: 'อุดรธานี', latitude: 17.4138, longitude: 102.7872, region: 'อีสาน' },
  { name: 'หนองบัวลำภู', latitude: 17.2046, longitude: 102.4407, region: 'อีสาน' },
  { name: 'เลย', latitude: 17.486, longitude: 101.7223, region: 'อีสาน' },
  { name: 'ขอนแก่น', latitude: 16.4322, longitude: 102.8236, region: 'อีสาน' },
  { name: 'ชัยภูมิ', latitude: 15.8068, longitude: 102.0316, region: 'อีสาน' },
  { name: 'มหาสารคาม', latitude: 16.1851, longitude: 103.3029, region: 'อีสาน' },
  { name: 'กาฬสินธุ์', latitude: 16.4315, longitude: 103.5059, region: 'อีสาน' },
  { name: 'ร้อยเอ็ด', latitude: 16.0538, longitude: 103.652, region: 'อีสาน' },
  { name: 'ชุมพร', latitude: 10.493, longitude: 99.18, region: 'ใต้' },
  { name: 'ระนอง', latitude: 9.9528, longitude: 98.6085, region: 'ใต้' },
  { name: 'สุราษฎร์ธานี', latitude: 9.1382, longitude: 99.3217, region: 'ใต้' },
  { name: 'พังงา', latitude: 8.4509, longitude: 98.5256, region: 'ใต้' },
  { name: 'ภูเก็ต', latitude: 7.8804, longitude: 98.3923, region: 'ใต้' },
  { name: 'กระบี่', latitude: 8.0863, longitude: 98.9063, region: 'ใต้' },
  { name: 'นครศรีธรรมราช', latitude: 8.4304, longitude: 99.9631, region: 'ใต้' },
  { name: 'ตรัง', latitude: 7.5563, longitude: 99.6114, region: 'ใต้' },
  { name: 'พัทลุง', latitude: 7.6167, longitude: 100.074, region: 'ใต้' },
  { name: 'สงขลา', latitude: 7.1898, longitude: 100.5954, region: 'ใต้' },
  { name: 'สตูล', latitude: 6.6238, longitude: 100.0674, region: 'ใต้' },
  { name: 'ปัตตานี', latitude: 6.8697, longitude: 101.2501, region: 'ใต้' },
  { name: 'ยะลา', latitude: 6.5411, longitude: 101.2804, region: 'ใต้' },
  { name: 'นราธิวาส', latitude: 6.4255, longitude: 101.8253, region: 'ใต้' },
];

/** ค้นจังหวัดจากคำที่พิมพ์ (ตัดช่องว่าง ไม่สนตัวพิมพ์) */
export const searchProvinces = (query: string): ProvinceCenter[] => {
  const q = query.replace(/\s+/g, '').trim();
  if (!q) return PROVINCES;
  return PROVINCES.filter((p) => p.name.includes(q) || (q.length >= 2 && p.region.includes(q)));
};
