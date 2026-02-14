<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

/**
 * ProvinceSeeder - สร้างข้อมูลจังหวัดไทยทั้ง 77 จังหวัด
 *
 * ข้อมูลประกอบด้วย:
 * - ชื่อภาษาไทย
 * - ชื่อภาษาอังกฤษ
 * - รหัสจังหวัด
 * - ภูมิภาค
 * - พิกัด GPS (latitude, longitude)
 */
class ProvinceSeeder extends Seeder
{
    /**
     * สร้างข้อมูลจังหวัดไทยทั้ง 77 จังหวัด
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูลจังหวัดไทย...');

        $provinces = $this->getProvinces();
        $created = 0;
        $skipped = 0;

        foreach ($provinces as $province) {
            // ตรวจสอบว่ามีอยู่แล้วหรือไม่ (idempotent)
            if (Province::where('code', $province['code'])->exists()) {
                $skipped++;

                continue;
            }

            Province::create($province);
            $created++;
        }

        $this->command->info("✅ Seed ข้อมูลจังหวัดเสร็จสิ้น: สร้างใหม่ {$created} จังหวัด, ข้าม {$skipped} จังหวัด");
    }

    /**
     * ดึงข้อมูลจังหวัดไทยทั้ง 77 จังหวัด
     */
    private function getProvinces(): array
    {
        return [
            // ภาคกลาง (Central)
            ['name_th' => 'กรุงเทพมหานคร', 'name_en' => 'Bangkok', 'code' => '10', 'region' => 'central', 'latitude' => 13.7563, 'longitude' => 100.5018, 'sort_order' => 1],
            ['name_th' => 'กำแพงเพชร', 'name_en' => 'Kamphaeng Phet', 'code' => '62', 'region' => 'central', 'latitude' => 16.4827, 'longitude' => 99.5226, 'sort_order' => 2],
            ['name_th' => 'ชัยนาท', 'name_en' => 'Chai Nat', 'code' => '18', 'region' => 'central', 'latitude' => 15.1851, 'longitude' => 100.1251, 'sort_order' => 3],
            ['name_th' => 'นครนายก', 'name_en' => 'Nakhon Nayok', 'code' => '26', 'region' => 'central', 'latitude' => 14.2069, 'longitude' => 101.2131, 'sort_order' => 4],
            ['name_th' => 'นครปฐม', 'name_en' => 'Nakhon Pathom', 'code' => '73', 'region' => 'central', 'latitude' => 13.8196, 'longitude' => 100.0644, 'sort_order' => 5],
            ['name_th' => 'นครสวรรค์', 'name_en' => 'Nakhon Sawan', 'code' => '60', 'region' => 'central', 'latitude' => 15.7030, 'longitude' => 100.1367, 'sort_order' => 6],
            ['name_th' => 'นนทบุรี', 'name_en' => 'Nonthaburi', 'code' => '12', 'region' => 'central', 'latitude' => 13.8622, 'longitude' => 100.5144, 'sort_order' => 7],
            ['name_th' => 'ปทุมธานี', 'name_en' => 'Pathum Thani', 'code' => '13', 'region' => 'central', 'latitude' => 14.0208, 'longitude' => 100.5250, 'sort_order' => 8],
            ['name_th' => 'พระนครศรีอยุธยา', 'name_en' => 'Phra Nakhon Si Ayutthaya', 'code' => '14', 'region' => 'central', 'latitude' => 14.3692, 'longitude' => 100.5877, 'sort_order' => 9],
            ['name_th' => 'พิจิตร', 'name_en' => 'Phichit', 'code' => '66', 'region' => 'central', 'latitude' => 16.4429, 'longitude' => 100.3486, 'sort_order' => 10],
            ['name_th' => 'พิษณุโลก', 'name_en' => 'Phitsanulok', 'code' => '65', 'region' => 'central', 'latitude' => 16.8298, 'longitude' => 100.2654, 'sort_order' => 11],
            ['name_th' => 'เพชรบูรณ์', 'name_en' => 'Phetchabun', 'code' => '67', 'region' => 'central', 'latitude' => 16.4190, 'longitude' => 101.1600, 'sort_order' => 12],
            ['name_th' => 'ลพบุรี', 'name_en' => 'Lop Buri', 'code' => '16', 'region' => 'central', 'latitude' => 14.7995, 'longitude' => 100.6534, 'sort_order' => 13],
            ['name_th' => 'สมุทรปราการ', 'name_en' => 'Samut Prakan', 'code' => '11', 'region' => 'central', 'latitude' => 13.5991, 'longitude' => 100.5998, 'sort_order' => 14],
            ['name_th' => 'สมุทรสงคราม', 'name_en' => 'Samut Songkhram', 'code' => '75', 'region' => 'central', 'latitude' => 13.4098, 'longitude' => 100.0021, 'sort_order' => 15],
            ['name_th' => 'สมุทรสาคร', 'name_en' => 'Samut Sakhon', 'code' => '74', 'region' => 'central', 'latitude' => 13.5475, 'longitude' => 100.2747, 'sort_order' => 16],
            ['name_th' => 'สระบุรี', 'name_en' => 'Saraburi', 'code' => '19', 'region' => 'central', 'latitude' => 14.5289, 'longitude' => 100.9102, 'sort_order' => 17],
            ['name_th' => 'สิงห์บุรี', 'name_en' => 'Sing Buri', 'code' => '17', 'region' => 'central', 'latitude' => 14.8936, 'longitude' => 100.3967, 'sort_order' => 18],
            ['name_th' => 'สุโขทัย', 'name_en' => 'Sukhothai', 'code' => '64', 'region' => 'central', 'latitude' => 17.0069, 'longitude' => 99.8265, 'sort_order' => 19],
            ['name_th' => 'สุพรรณบุรี', 'name_en' => 'Suphan Buri', 'code' => '72', 'region' => 'central', 'latitude' => 14.4745, 'longitude' => 100.1177, 'sort_order' => 20],
            ['name_th' => 'อ่างทอง', 'name_en' => 'Ang Thong', 'code' => '15', 'region' => 'central', 'latitude' => 14.5896, 'longitude' => 100.4549, 'sort_order' => 21],
            ['name_th' => 'อุทัยธานี', 'name_en' => 'Uthai Thani', 'code' => '61', 'region' => 'central', 'latitude' => 15.3798, 'longitude' => 100.0244, 'sort_order' => 22],

            // ภาคเหนือ (North)
            ['name_th' => 'เชียงราย', 'name_en' => 'Chiang Rai', 'code' => '57', 'region' => 'north', 'latitude' => 19.9105, 'longitude' => 99.8406, 'sort_order' => 23],
            ['name_th' => 'เชียงใหม่', 'name_en' => 'Chiang Mai', 'code' => '50', 'region' => 'north', 'latitude' => 18.7883, 'longitude' => 98.9853, 'sort_order' => 24],
            ['name_th' => 'น่าน', 'name_en' => 'Nan', 'code' => '55', 'region' => 'north', 'latitude' => 18.7756, 'longitude' => 100.7730, 'sort_order' => 25],
            ['name_th' => 'พะเยา', 'name_en' => 'Phayao', 'code' => '56', 'region' => 'north', 'latitude' => 19.1664, 'longitude' => 99.9019, 'sort_order' => 26],
            ['name_th' => 'แพร่', 'name_en' => 'Phrae', 'code' => '54', 'region' => 'north', 'latitude' => 18.1445, 'longitude' => 100.1403, 'sort_order' => 27],
            ['name_th' => 'แม่ฮ่องสอน', 'name_en' => 'Mae Hong Son', 'code' => '58', 'region' => 'north', 'latitude' => 19.3020, 'longitude' => 97.9654, 'sort_order' => 28],
            ['name_th' => 'ลำปาง', 'name_en' => 'Lampang', 'code' => '52', 'region' => 'north', 'latitude' => 18.2888, 'longitude' => 99.4903, 'sort_order' => 29],
            ['name_th' => 'ลำพูน', 'name_en' => 'Lamphun', 'code' => '51', 'region' => 'north', 'latitude' => 18.5747, 'longitude' => 99.0087, 'sort_order' => 30],
            ['name_th' => 'อุตรดิตถ์', 'name_en' => 'Uttaradit', 'code' => '53', 'region' => 'north', 'latitude' => 17.6200, 'longitude' => 100.0993, 'sort_order' => 31],

            // ภาคตะวันออกเฉียงเหนือ (Northeast / Isan)
            ['name_th' => 'กาฬสินธุ์', 'name_en' => 'Kalasin', 'code' => '46', 'region' => 'northeast', 'latitude' => 16.4322, 'longitude' => 103.5061, 'sort_order' => 32],
            ['name_th' => 'ขอนแก่น', 'name_en' => 'Khon Kaen', 'code' => '40', 'region' => 'northeast', 'latitude' => 16.4419, 'longitude' => 102.8360, 'sort_order' => 33],
            ['name_th' => 'ชัยภูมิ', 'name_en' => 'Chaiyaphum', 'code' => '36', 'region' => 'northeast', 'latitude' => 15.8068, 'longitude' => 102.0318, 'sort_order' => 34],
            ['name_th' => 'นครพนม', 'name_en' => 'Nakhon Phanom', 'code' => '48', 'region' => 'northeast', 'latitude' => 17.4048, 'longitude' => 104.7859, 'sort_order' => 35],
            ['name_th' => 'นครราชสีมา', 'name_en' => 'Nakhon Ratchasima', 'code' => '30', 'region' => 'northeast', 'latitude' => 14.9799, 'longitude' => 102.0977, 'sort_order' => 36],
            ['name_th' => 'บึงกาฬ', 'name_en' => 'Bueng Kan', 'code' => '38', 'region' => 'northeast', 'latitude' => 18.3609, 'longitude' => 103.6466, 'sort_order' => 37],
            ['name_th' => 'บุรีรัมย์', 'name_en' => 'Buri Ram', 'code' => '31', 'region' => 'northeast', 'latitude' => 14.9930, 'longitude' => 103.1029, 'sort_order' => 38],
            ['name_th' => 'มหาสารคาม', 'name_en' => 'Maha Sarakham', 'code' => '44', 'region' => 'northeast', 'latitude' => 16.1851, 'longitude' => 103.3007, 'sort_order' => 39],
            ['name_th' => 'มุกดาหาร', 'name_en' => 'Mukdahan', 'code' => '49', 'region' => 'northeast', 'latitude' => 16.5453, 'longitude' => 104.7235, 'sort_order' => 40],
            ['name_th' => 'ยโสธร', 'name_en' => 'Yasothon', 'code' => '35', 'region' => 'northeast', 'latitude' => 15.7926, 'longitude' => 104.1451, 'sort_order' => 41],
            ['name_th' => 'ร้อยเอ็ด', 'name_en' => 'Roi Et', 'code' => '45', 'region' => 'northeast', 'latitude' => 16.0538, 'longitude' => 103.6520, 'sort_order' => 42],
            ['name_th' => 'เลย', 'name_en' => 'Loei', 'code' => '42', 'region' => 'northeast', 'latitude' => 17.4904, 'longitude' => 101.7223, 'sort_order' => 43],
            ['name_th' => 'ศรีสะเกษ', 'name_en' => 'Si Sa Ket', 'code' => '33', 'region' => 'northeast', 'latitude' => 15.1186, 'longitude' => 104.3220, 'sort_order' => 44],
            ['name_th' => 'สกลนคร', 'name_en' => 'Sakon Nakhon', 'code' => '47', 'region' => 'northeast', 'latitude' => 17.1545, 'longitude' => 104.1348, 'sort_order' => 45],
            ['name_th' => 'สุรินทร์', 'name_en' => 'Surin', 'code' => '32', 'region' => 'northeast', 'latitude' => 14.8820, 'longitude' => 103.4937, 'sort_order' => 46],
            ['name_th' => 'หนองคาย', 'name_en' => 'Nong Khai', 'code' => '43', 'region' => 'northeast', 'latitude' => 17.8783, 'longitude' => 102.7414, 'sort_order' => 47],
            ['name_th' => 'หนองบัวลำภู', 'name_en' => 'Nong Bua Lam Phu', 'code' => '39', 'region' => 'northeast', 'latitude' => 17.2041, 'longitude' => 102.4260, 'sort_order' => 48],
            ['name_th' => 'อำนาจเจริญ', 'name_en' => 'Amnat Charoen', 'code' => '37', 'region' => 'northeast', 'latitude' => 15.8656, 'longitude' => 104.6258, 'sort_order' => 49],
            ['name_th' => 'อุดรธานี', 'name_en' => 'Udon Thani', 'code' => '41', 'region' => 'northeast', 'latitude' => 17.4138, 'longitude' => 102.7874, 'sort_order' => 50],
            ['name_th' => 'อุบลราชธานี', 'name_en' => 'Ubon Ratchathani', 'code' => '34', 'region' => 'northeast', 'latitude' => 15.2448, 'longitude' => 104.8473, 'sort_order' => 51],

            // ภาคตะวันออก (East)
            ['name_th' => 'จันทบุรี', 'name_en' => 'Chanthaburi', 'code' => '22', 'region' => 'east', 'latitude' => 12.6108, 'longitude' => 102.1037, 'sort_order' => 52],
            ['name_th' => 'ฉะเชิงเทรา', 'name_en' => 'Chachoengsao', 'code' => '24', 'region' => 'east', 'latitude' => 13.6904, 'longitude' => 101.0779, 'sort_order' => 53],
            ['name_th' => 'ชลบุรี', 'name_en' => 'Chon Buri', 'code' => '20', 'region' => 'east', 'latitude' => 13.3611, 'longitude' => 100.9847, 'sort_order' => 54],
            ['name_th' => 'ตราด', 'name_en' => 'Trat', 'code' => '23', 'region' => 'east', 'latitude' => 12.2428, 'longitude' => 102.5177, 'sort_order' => 55],
            ['name_th' => 'ปราจีนบุรี', 'name_en' => 'Prachin Buri', 'code' => '25', 'region' => 'east', 'latitude' => 14.0509, 'longitude' => 101.3687, 'sort_order' => 56],
            ['name_th' => 'ระยอง', 'name_en' => 'Rayong', 'code' => '21', 'region' => 'east', 'latitude' => 12.6814, 'longitude' => 101.2816, 'sort_order' => 57],
            ['name_th' => 'สระแก้ว', 'name_en' => 'Sa Kaeo', 'code' => '27', 'region' => 'east', 'latitude' => 13.8244, 'longitude' => 102.0645, 'sort_order' => 58],

            // ภาคตะวันตก (West)
            ['name_th' => 'กาญจนบุรี', 'name_en' => 'Kanchanaburi', 'code' => '71', 'region' => 'west', 'latitude' => 14.0227, 'longitude' => 99.5328, 'sort_order' => 59],
            ['name_th' => 'ตาก', 'name_en' => 'Tak', 'code' => '63', 'region' => 'west', 'latitude' => 16.8840, 'longitude' => 99.1258, 'sort_order' => 60],
            ['name_th' => 'ประจวบคีรีขันธ์', 'name_en' => 'Prachuap Khiri Khan', 'code' => '77', 'region' => 'west', 'latitude' => 11.8126, 'longitude' => 99.7957, 'sort_order' => 61],
            ['name_th' => 'เพชรบุรี', 'name_en' => 'Phetchaburi', 'code' => '76', 'region' => 'west', 'latitude' => 13.1119, 'longitude' => 99.9391, 'sort_order' => 62],
            ['name_th' => 'ราชบุรี', 'name_en' => 'Ratchaburi', 'code' => '70', 'region' => 'west', 'latitude' => 13.5282, 'longitude' => 99.8134, 'sort_order' => 63],

            // ภาคใต้ (South)
            ['name_th' => 'กระบี่', 'name_en' => 'Krabi', 'code' => '81', 'region' => 'south', 'latitude' => 8.0863, 'longitude' => 98.9063, 'sort_order' => 64],
            ['name_th' => 'ชุมพร', 'name_en' => 'Chumphon', 'code' => '86', 'region' => 'south', 'latitude' => 10.4930, 'longitude' => 99.1800, 'sort_order' => 65],
            ['name_th' => 'ตรัง', 'name_en' => 'Trang', 'code' => '92', 'region' => 'south', 'latitude' => 7.5564, 'longitude' => 99.6114, 'sort_order' => 66],
            ['name_th' => 'นครศรีธรรมราช', 'name_en' => 'Nakhon Si Thammarat', 'code' => '80', 'region' => 'south', 'latitude' => 8.4324, 'longitude' => 99.9631, 'sort_order' => 67],
            ['name_th' => 'นราธิวาส', 'name_en' => 'Narathiwat', 'code' => '96', 'region' => 'south', 'latitude' => 6.4254, 'longitude' => 101.8253, 'sort_order' => 68],
            ['name_th' => 'ปัตตานี', 'name_en' => 'Pattani', 'code' => '94', 'region' => 'south', 'latitude' => 6.8691, 'longitude' => 101.2509, 'sort_order' => 69],
            ['name_th' => 'พังงา', 'name_en' => 'Phang Nga', 'code' => '82', 'region' => 'south', 'latitude' => 8.4500, 'longitude' => 98.5300, 'sort_order' => 70],
            ['name_th' => 'พัทลุง', 'name_en' => 'Phatthalung', 'code' => '93', 'region' => 'south', 'latitude' => 7.6167, 'longitude' => 100.0743, 'sort_order' => 71],
            ['name_th' => 'ภูเก็ต', 'name_en' => 'Phuket', 'code' => '83', 'region' => 'south', 'latitude' => 7.8804, 'longitude' => 98.3923, 'sort_order' => 72],
            ['name_th' => 'ยะลา', 'name_en' => 'Yala', 'code' => '95', 'region' => 'south', 'latitude' => 6.5410, 'longitude' => 101.2808, 'sort_order' => 73],
            ['name_th' => 'ระนอง', 'name_en' => 'Ranong', 'code' => '85', 'region' => 'south', 'latitude' => 9.9528, 'longitude' => 98.6085, 'sort_order' => 74],
            ['name_th' => 'สงขลา', 'name_en' => 'Songkhla', 'code' => '90', 'region' => 'south', 'latitude' => 7.1897, 'longitude' => 100.5955, 'sort_order' => 75],
            ['name_th' => 'สตูล', 'name_en' => 'Satun', 'code' => '91', 'region' => 'south', 'latitude' => 6.6238, 'longitude' => 100.0673, 'sort_order' => 76],
            ['name_th' => 'สุราษฎร์ธานี', 'name_en' => 'Surat Thani', 'code' => '84', 'region' => 'south', 'latitude' => 9.1382, 'longitude' => 99.3217, 'sort_order' => 77],
        ];
    }
}
