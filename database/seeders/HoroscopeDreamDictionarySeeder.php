<?php

namespace Database\Seeders;

use App\Models\HoroscopeDreamCategory;
use App\Models\HoroscopeDreamDictionary;
use Illuminate\Database\Seeder;

/**
 * สร้างข้อมูลพจนานุกรมทำนายฝัน 100+ รายการ ครอบคลุม 12 หมวดหมู่
 *
 * แต่ละรายการประกอบด้วย:
 * - keyword_th: คำค้นหาภาษาไทย
 * - meaning_th: ความหมายของฝัน
 * - lucky_numbers: เลขเด็ด (JSON array)
 * - is_good_omen: ลางดี/ร้าย
 * - intensity: ระดับความแรง 1-5
 */
class HoroscopeDreamDictionarySeeder extends Seeder
{
    /**
     * สร้างข้อมูลพจนานุกรมทำนายฝัน
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌙 กำลัง seed พจนานุกรมทำนายฝัน...');

        // ตรวจสอบว่ามีข้อมูลเพียงพอแล้วหรือยัง
        if (HoroscopeDreamDictionary::count() >= 100) {
            $this->command->info('พจนานุกรมทำนายฝันมีข้อมูลเพียงพอแล้ว ข้าม...');

            return;
        }

        // ดึง category_id จาก slug เพื่อใช้ในการ seed
        $categories = HoroscopeDreamCategory::pluck('id', 'slug')->toArray();

        // ตรวจสอบว่ามีหมวดหมู่ครบ 12 หมวดก่อน seed
        if (count($categories) < 12) {
            $this->command->warn('หมวดหมู่ฝันยังไม่ครบ 12 หมวด กรุณารัน HoroscopeDreamCategorySeeder ก่อน');

            return;
        }

        $entries = $this->getDreamEntries($categories);

        $count = 0;
        foreach ($entries as $entry) {
            HoroscopeDreamDictionary::updateOrCreate(
                ['keyword_th' => $entry['keyword_th']],
                $entry
            );
            $count++;
        }

        $this->command->info("✅ Seed พจนานุกรมทำนายฝัน {$count} รายการ สำเร็จ!");
    }

    /**
     * คืนค่ารายการทำนายฝันทั้งหมด 12 หมวดหมู่
     *
     * @param array $categories แมป slug => id
     * @return array
     */
    private function getDreamEntries(array $categories): array
    {
        return array_merge(
            $this->getAnimalEntries($categories['animals']),
            $this->getPeopleEntries($categories['people']),
            $this->getNatureEntries($categories['nature']),
            $this->getWaterEntries($categories['water']),
            $this->getDeathSpiritEntries($categories['death-spirit']),
            $this->getMoneyEntries($categories['money']),
            $this->getFoodEntries($categories['food']),
            $this->getTravelEntries($categories['travel']),
            $this->getBuildingEntries($categories['buildings']),
            $this->getReligionEntries($categories['religion']),
            $this->getDisasterEntries($categories['disaster']),
            $this->getLoveFamilyEntries($categories['love-family']),
        );
    }

    /**
     * หมวดสัตว์ - ฝันเห็นสัตว์ต่างๆ
     */
    private function getAnimalEntries(int $categoryId): array
    {
        return [
            [
                'category_id' => $categoryId,
                'keyword_th' => 'งู',
                'meaning_th' => 'ฝันเห็นงูหมายถึงมีศัตรูแอบแฝงอยู่รอบตัว ระวังคนใกล้ชิดที่ไม่จริงใจ แต่ถ้างูไม่ทำร้ายจะได้โชคลาภ',
                'lucky_numbers' => [7, 17, 71],
                'is_good_omen' => false,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'เสือ',
                'meaning_th' => 'ฝันเห็นเสือบ่งบอกถึงอำนาจและความกล้าหาญ จะมีผู้ใหญ่ให้การช่วยเหลือในเรื่องการงาน',
                'lucky_numbers' => [9, 19, 91],
                'is_good_omen' => true,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ช้าง',
                'meaning_th' => 'ฝันเห็นช้างเป็นมงคลอย่างยิ่ง หมายถึงโชคลาภใหญ่กำลังจะมาถึง ตำแหน่งหน้าที่จะเลื่อนขึ้น',
                'lucky_numbers' => [12, 21, 56],
                'is_good_omen' => true,
                'intensity' => 5,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'หมา',
                'meaning_th' => 'ฝันเห็นหมาหมายถึงมิตรภาพและความซื่อสัตย์ จะมีเพื่อนแท้คอยช่วยเหลือในยามยากลำบาก',
                'lucky_numbers' => [11, 16, 61],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'แมว',
                'meaning_th' => 'ฝันเห็นแมวบ่งบอกถึงเรื่องลึกลับและสัญชาตญาณ ระวังคนที่มาทำดีเกินไปอาจมีเจตนาแอบแฝง',
                'lucky_numbers' => [4, 14, 41],
                'is_good_omen' => false,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'นก',
                'meaning_th' => 'ฝันเห็นนกบินเป็นลางดี หมายถึงอิสรภาพและข่าวดีกำลังจะมา การงานจะราบรื่น',
                'lucky_numbers' => [15, 51, 55],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ปลา',
                'meaning_th' => 'ฝันเห็นปลาเป็นลางดีด้านการเงิน จะมีรายได้เข้ามาไม่ขาดสาย ยิ่งปลาตัวใหญ่ยิ่งได้โชคมาก',
                'lucky_numbers' => [39, 93, 69],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ม้า',
                'meaning_th' => 'ฝันเห็นม้าหมายถึงพลังและความรวดเร็ว การงานที่รอคอยจะสำเร็จเร็วกว่าที่คิด',
                'lucky_numbers' => [10, 18, 81],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ลิง',
                'meaning_th' => 'ฝันเห็นลิงบ่งบอกถึงความซุกซนและเล่ห์เหลี่ยม ระวังถูกหลอกลวงจากคนที่แสร้งทำเป็นมิตร',
                'lucky_numbers' => [36, 63, 33],
                'is_good_omen' => false,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'กบ',
                'meaning_th' => 'ฝันเห็นกบหมายถึงการเปลี่ยนแปลงครั้งใหญ่ในชีวิต สิ่งที่เคยติดขัดจะคลี่คลาย',
                'lucky_numbers' => [26, 62, 28],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
        ];
    }

    /**
     * หมวดคนและบุคคล - ฝันเห็นบุคคลต่างๆ
     */
    private function getPeopleEntries(int $categoryId): array
    {
        return [
            [
                'category_id' => $categoryId,
                'keyword_th' => 'คนตาย',
                'meaning_th' => 'ฝันเห็นคนที่เสียชีวิตแล้วมาหา หมายถึงท่านมาบอกบุญ ควรทำบุญอุทิศส่วนกุศลให้',
                'lucky_numbers' => [1, 10, 0],
                'is_good_omen' => false,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'พ่อแม่',
                'meaning_th' => 'ฝันเห็นพ่อแม่หมายถึงความคุ้มครองและพรจากผู้ใหญ่ จะมีสิ่งดีๆ เกิดขึ้นในครอบครัว',
                'lucky_numbers' => [29, 92, 22],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'เด็กทารก',
                'meaning_th' => 'ฝันเห็นเด็กทารกเป็นลางดี หมายถึงการเริ่มต้นใหม่ โอกาสดีกำลังจะมาถึง',
                'lucky_numbers' => [32, 23, 35],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'คนแปลกหน้า',
                'meaning_th' => 'ฝันเห็นคนแปลกหน้าบ่งบอกถึงการพบปะคนใหม่ อาจมีโอกาสทางธุรกิจหรือความสัมพันธ์ใหม่',
                'lucky_numbers' => [47, 74, 77],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'เพื่อน',
                'meaning_th' => 'ฝันเห็นเพื่อนเก่าหมายถึงความทรงจำดีๆ จะได้ข่าวดีจากคนรู้จัก หรือมีโอกาสได้กลับมาเจอกัน',
                'lucky_numbers' => [45, 54, 58],
                'is_good_omen' => true,
                'intensity' => 1,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'อดีตแฟน',
                'meaning_th' => 'ฝันเห็นอดีตแฟนบ่งบอกถึงเรื่องค้างคาในใจที่ยังไม่ได้รับการปลดปล่อย ควรปล่อยวางอดีต',
                'lucky_numbers' => [24, 42, 49],
                'is_good_omen' => false,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ตำรวจ',
                'meaning_th' => 'ฝันเห็นตำรวจหมายถึงความยุติธรรมจะมาถึง ปัญหาที่กังวลจะได้รับการแก้ไขอย่างเป็นธรรม',
                'lucky_numbers' => [46, 64, 66],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'หมอ',
                'meaning_th' => 'ฝันเห็นหมอบ่งบอกถึงสุขภาพ ควรดูแลร่างกายให้ดี หรืออาจได้รับความช่วยเหลือจากผู้เชี่ยวชาญ',
                'lucky_numbers' => [48, 84, 88],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ทหาร',
                'meaning_th' => 'ฝันเห็นทหารหมายถึงความเข้มแข็งและระเบียบวินัย จะมีพลังในการจัดการปัญหาต่างๆ ได้อย่างเด็ดขาด',
                'lucky_numbers' => [46, 64, 69],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
        ];
    }

    /**
     * หมวดธรรมชาติ - ฝันเห็นธรรมชาติ ต้นไม้ ท้องฟ้า
     */
    private function getNatureEntries(int $categoryId): array
    {
        return [
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ต้นไม้ใหญ่',
                'meaning_th' => 'ฝันเห็นต้นไม้ใหญ่เป็นลางดี หมายถึงความมั่นคงและร่มเย็น ครอบครัวจะมีความสุข',
                'lucky_numbers' => [34, 43, 30],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ดอกไม้',
                'meaning_th' => 'ฝันเห็นดอกไม้สวยงามหมายถึงความรักและความงดงาม จะมีเรื่องดีๆ ในเรื่องความรัก',
                'lucky_numbers' => [25, 52, 59],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ป่า',
                'meaning_th' => 'ฝันเห็นป่าทึบบ่งบอกถึงความสับสนในชีวิต ต้องตั้งสติและมองหาทางออกอย่างรอบคอบ',
                'lucky_numbers' => [37, 73, 38],
                'is_good_omen' => false,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ภูเขา',
                'meaning_th' => 'ฝันเห็นภูเขาสูงหมายถึงความท้าทายที่ต้องเผชิญ แต่ถ้าปีนขึ้นถึงยอดจะประสบความสำเร็จ',
                'lucky_numbers' => [50, 5, 55],
                'is_good_omen' => true,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'พระอาทิตย์',
                'meaning_th' => 'ฝันเห็นพระอาทิตย์ส่องสว่างเป็นมงคล หมายถึงชีวิตกำลังเข้าสู่ช่วงที่สดใส ปัญหาจะคลี่คลาย',
                'lucky_numbers' => [19, 91, 99],
                'is_good_omen' => true,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'พระจันทร์',
                'meaning_th' => 'ฝันเห็นพระจันทร์เต็มดวงหมายถึงความสงบและปัญญา จะมีสติปัญญาในการตัดสินใจเรื่องสำคัญ',
                'lucky_numbers' => [15, 51, 65],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ดาว',
                'meaning_th' => 'ฝันเห็นดาวเต็มฟ้าเป็นลางดี หมายถึงความหวังและความสำเร็จ สิ่งที่ปรารถนาจะเป็นจริง',
                'lucky_numbers' => [56, 65, 96],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ฝน',
                'meaning_th' => 'ฝันเห็นฝนตกหมายถึงความอุดมสมบูรณ์และความสดชื่น โชคลาภจะหลั่งไหลมาเหมือนสายฝน',
                'lucky_numbers' => [67, 76, 78],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
        ];
    }

    /**
     * หมวดน้ำและทะเล - ฝันเกี่ยวกับน้ำ
     */
    private function getWaterEntries(int $categoryId): array
    {
        return [
            [
                'category_id' => $categoryId,
                'keyword_th' => 'น้ำท่วม',
                'meaning_th' => 'ฝันเห็นน้ำท่วมบ่งบอกถึงปัญหาที่ท่วมท้น แต่ถ้าน้ำใสจะหมายถึงโชคลาภมาก ถ้าน้ำขุ่นระวังเรื่องเงิน',
                'lucky_numbers' => [8, 18, 81],
                'is_good_omen' => false,
                'intensity' => 5,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'น้ำใส',
                'meaning_th' => 'ฝันเห็นน้ำใสสะอาดเป็นลางดี หมายถึงจิตใจผ่องใสและชีวิตกำลังจะดีขึ้น',
                'lucky_numbers' => [68, 86, 80],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ทะเล',
                'meaning_th' => 'ฝันเห็นทะเลกว้างใหญ่หมายถึงโอกาสไม่มีขีดจำกัด ถ้าทะเลสงบชีวิตจะราบรื่น',
                'lucky_numbers' => [89, 98, 82],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'แม่น้ำ',
                'meaning_th' => 'ฝันเห็นแม่น้ำบ่งบอกถึงการไหลเวียนของชีวิต ถ้าน้ำไหลเชี่ยวจะมีเรื่องเร่งด่วนต้องจัดการ',
                'lucky_numbers' => [78, 87, 72],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'สระน้ำ',
                'meaning_th' => 'ฝันเห็นสระน้ำหมายถึงความสงบในจิตใจ ถ้าว่ายน้ำในสระจะมีความสุขกับสิ่งที่มี',
                'lucky_numbers' => [20, 2, 28],
                'is_good_omen' => true,
                'intensity' => 1,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'น้ำตก',
                'meaning_th' => 'ฝันเห็นน้ำตกสวยงามหมายถึงพลังงานใหม่กำลังหลั่งไหลเข้ามา โชคลาภจะมาแบบไม่คาดคิด',
                'lucky_numbers' => [58, 85, 57],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ฝนตก',
                'meaning_th' => 'ฝันเห็นฝนตกหนักบ่งบอกถึงการชำระล้างสิ่งไม่ดีออกไป หลังจากนี้ชีวิตจะสดใสขึ้น',
                'lucky_numbers' => [67, 76, 70],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'จมน้ำ',
                'meaning_th' => 'ฝันว่าจมน้ำบ่งบอกถึงความรู้สึกจมปลักกับปัญหา ต้องขอความช่วยเหลือจากคนรอบข้าง',
                'lucky_numbers' => [40, 4, 48],
                'is_good_omen' => false,
                'intensity' => 5,
            ],
        ];
    }

    /**
     * หมวดความตายและวิญญาณ - ฝันเกี่ยวกับผี ความตาย
     */
    private function getDeathSpiritEntries(int $categoryId): array
    {
        return [
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ผี',
                'meaning_th' => 'ฝันเห็นผีหมายถึงมีวิญญาณมาขอส่วนบุญ ควรทำบุญอุทิศส่วนกุศลให้ จะได้โชคลาภตามมา',
                'lucky_numbers' => [14, 41, 44],
                'is_good_omen' => false,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'โลงศพ',
                'meaning_th' => 'ฝันเห็นโลงศพอาจฟังดูน่ากลัว แต่ในทางทำนายฝันหมายถึงการสิ้นสุดของปัญหาเก่าและเริ่มต้นใหม่',
                'lucky_numbers' => [43, 34, 40],
                'is_good_omen' => false,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'งานศพ',
                'meaning_th' => 'ฝันเห็นงานศพบ่งบอกถึงการเปลี่ยนแปลงครั้งใหญ่ในชีวิต สิ่งเก่าจะจากไปเพื่อเปิดทางให้สิ่งใหม่',
                'lucky_numbers' => [13, 31, 30],
                'is_good_omen' => false,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ตัวเองตาย',
                'meaning_th' => 'ฝันว่าตัวเองตายเป็นลางดี หมายถึงการเกิดใหม่และการเริ่มต้นชีวิตในบทบาทที่ดีกว่า',
                'lucky_numbers' => [1, 10, 19],
                'is_good_omen' => true,
                'intensity' => 5,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'วิญญาณ',
                'meaning_th' => 'ฝันเห็นวิญญาณลอยอยู่หมายถึงมีสิ่งที่ยังค้างคาจากอดีต ควรสะสางให้เรียบร้อย',
                'lucky_numbers' => [41, 14, 47],
                'is_good_omen' => false,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'สุสาน',
                'meaning_th' => 'ฝันเห็นสุสานบ่งบอกถึงการปล่อยวางอดีต ควรให้อภัยตัวเองและคนอื่น แล้วเดินหน้าต่อ',
                'lucky_numbers' => [49, 94, 90],
                'is_good_omen' => false,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ผีเด็ก',
                'meaning_th' => 'ฝันเห็นผีเด็กหมายถึงมีดวงวิญญาณเด็กมาขอส่วนบุญ ควรทำบุญถวายของเล่นหรืออาหาร',
                'lucky_numbers' => [32, 23, 42],
                'is_good_omen' => false,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ผีเปรต',
                'meaning_th' => 'ฝันเห็นเปรตหมายถึงมีเจ้ากรรมนายเวรมาทวงบุญ ควรทำบุญใส่บาตรและอุทิศส่วนกุศลให้',
                'lucky_numbers' => [44, 4, 14],
                'is_good_omen' => false,
                'intensity' => 5,
            ],
        ];
    }

    /**
     * หมวดเงินและทรัพย์สิน - ฝันเกี่ยวกับเงินทอง
     */
    private function getMoneyEntries(int $categoryId): array
    {
        return [
            [
                'category_id' => $categoryId,
                'keyword_th' => 'เงิน',
                'meaning_th' => 'ฝันเห็นเงินหมายถึงโชคลาภทางการเงินกำลังจะมา ถ้าเก็บเงินได้จะมีรายได้พิเศษ',
                'lucky_numbers' => [6, 16, 66],
                'is_good_omen' => true,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ทอง',
                'meaning_th' => 'ฝันเห็นทองคำเป็นมงคลสูงสุด หมายถึงความมั่งคั่งร่ำรวยและเกียรติยศ โชคลาภใหญ่กำลังมา',
                'lucky_numbers' => [96, 69, 99],
                'is_good_omen' => true,
                'intensity' => 5,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'เพชร',
                'meaning_th' => 'ฝันเห็นเพชรหมายถึงสิ่งมีค่าที่จะเข้ามาในชีวิต อาจเป็นโอกาสหรือคนพิเศษที่จะมาเติมเต็ม',
                'lucky_numbers' => [79, 97, 95],
                'is_good_omen' => true,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'กระเป๋าเงิน',
                'meaning_th' => 'ฝันเห็นกระเป๋าเงินบ่งบอกถึงการเงิน ถ้ากระเป๋าเต็มจะมีเงินเข้า ถ้ากระเป๋าว่างต้องระวังรายจ่าย',
                'lucky_numbers' => [60, 6, 62],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ขุดสมบัติ',
                'meaning_th' => 'ฝันว่าขุดพบสมบัติเป็นลางดีอย่างมาก หมายถึงจะค้นพบสิ่งดีๆ ที่ซ่อนอยู่ในชีวิต',
                'lucky_numbers' => [56, 65, 86],
                'is_good_omen' => true,
                'intensity' => 5,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ลอตเตอรี่',
                'meaning_th' => 'ฝันเห็นลอตเตอรี่หมายถึงดวงโชคลาภกำลังมา ลองเสี่ยงดวงดูอาจมีโชค แต่อย่าใช้เงินเกินตัว',
                'lucky_numbers' => [16, 61, 68],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'เงินหาย',
                'meaning_th' => 'ฝันว่าเงินหายหรือถูกขโมยบ่งบอกถึงความกังวลด้านการเงิน ควรวางแผนการใช้จ่ายให้รอบคอบ',
                'lucky_numbers' => [60, 6, 46],
                'is_good_omen' => false,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ธนบัตร',
                'meaning_th' => 'ฝันเห็นธนบัตรหมายถึงจะมีเงินเข้ามาในเร็วๆ นี้ ยิ่งธนบัตรมากยิ่งได้โชคลาภมาก',
                'lucky_numbers' => [16, 61, 36],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
        ];
    }

    /**
     * หมวดอาหาร - ฝันเกี่ยวกับอาหารและเครื่องดื่ม
     */
    private function getFoodEntries(int $categoryId): array
    {
        return [
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ข้าว',
                'meaning_th' => 'ฝันเห็นข้าวหมายถึงความอุดมสมบูรณ์และความมั่นคงในชีวิต จะไม่ขัดสนเรื่องปากท้อง',
                'lucky_numbers' => [59, 95, 52],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ผลไม้',
                'meaning_th' => 'ฝันเห็นผลไม้สุกงอมเป็นลางดี หมายถึงผลงานที่ทำไว้จะออกดอกออกผล ได้รับรางวัลตอบแทน',
                'lucky_numbers' => [53, 35, 50],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'เนื้อสัตว์',
                'meaning_th' => 'ฝันเห็นเนื้อสัตว์บ่งบอกถึงความต้องการพลังงานในชีวิต ร่างกายกำลังต้องการพักผ่อน',
                'lucky_numbers' => [71, 17, 75],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ขนม',
                'meaning_th' => 'ฝันเห็นขนมหวานหมายถึงความสุขและความหวานชื่นในชีวิต จะมีเรื่องดีๆ มาทำให้ยิ้มได้',
                'lucky_numbers' => [25, 52, 55],
                'is_good_omen' => true,
                'intensity' => 1,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ดื่มน้ำ',
                'meaning_th' => 'ฝันว่าดื่มน้ำหมายถึงการรับพลังงานใหม่ ถ้าน้ำใสสะอาดจะมีสุขภาพดี ถ้าน้ำขุ่นควรระวังสุขภาพ',
                'lucky_numbers' => [80, 8, 82],
                'is_good_omen' => true,
                'intensity' => 1,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'กินอาหาร',
                'meaning_th' => 'ฝันว่ากินอาหารอร่อยหมายถึงจะได้รับสิ่งดีๆ ในชีวิต ทั้งเรื่องงาน เงิน และความรัก',
                'lucky_numbers' => [59, 95, 90],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ผัก',
                'meaning_th' => 'ฝันเห็นผักสดเขียวงามหมายถึงสุขภาพแข็งแรงและความเจริญงอกงาม ชีวิตกำลังเติบโต',
                'lucky_numbers' => [53, 35, 33],
                'is_good_omen' => true,
                'intensity' => 1,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'อาหารเน่าเสีย',
                'meaning_th' => 'ฝันเห็นอาหารเน่าเสียบ่งบอกถึงสิ่งที่ไม่ดีในชีวิต ควรกำจัดสิ่งที่เป็นพิษออกจากชีวิต',
                'lucky_numbers' => [40, 4, 49],
                'is_good_omen' => false,
                'intensity' => 3,
            ],
        ];
    }

    /**
     * หมวดยานพาหนะและการเดินทาง - ฝันเกี่ยวกับยานพาหนะ
     */
    private function getTravelEntries(int $categoryId): array
    {
        return [
            [
                'category_id' => $categoryId,
                'keyword_th' => 'รถยนต์',
                'meaning_th' => 'ฝันเห็นรถยนต์หมายถึงเส้นทางชีวิต ถ้าขับรถราบรื่นจะประสบความสำเร็จ ถ้ารถเสียต้องระวัง',
                'lucky_numbers' => [79, 97, 70],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'เครื่องบิน',
                'meaning_th' => 'ฝันเห็นเครื่องบินหมายถึงการก้าวขึ้นสูง ตำแหน่งหน้าที่จะเลื่อนขั้น หรือมีโอกาสดีจากต่างประเทศ',
                'lucky_numbers' => [89, 98, 85],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'เรือ',
                'meaning_th' => 'ฝันเห็นเรือบ่งบอกถึงการเดินทาง ถ้าเรือล่องไปอย่างสงบชีวิตจะราบรื่น ถ้าเรือล่มต้องระวังอุปสรรค',
                'lucky_numbers' => [83, 38, 30],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'รถไฟ',
                'meaning_th' => 'ฝันเห็นรถไฟหมายถึงเส้นทางที่กำหนดไว้แล้ว ชีวิตกำลังเดินไปตามแผนที่วางไว้',
                'lucky_numbers' => [74, 47, 77],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'อุบัติเหตุรถ',
                'meaning_th' => 'ฝันเห็นอุบัติเหตุรถบ่งบอกถึงการเตือนภัย ควรขับรถด้วยความระมัดระวังและไม่ประมาท',
                'lucky_numbers' => [31, 13, 39],
                'is_good_omen' => false,
                'intensity' => 5,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'หลงทาง',
                'meaning_th' => 'ฝันว่าหลงทางบ่งบอกถึงความสับสนในชีวิตจริง ควรหยุดคิดทบทวนเป้าหมายและทิศทาง',
                'lucky_numbers' => [37, 73, 33],
                'is_good_omen' => false,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ถนน',
                'meaning_th' => 'ฝันเห็นถนนยาวไกลหมายถึงเส้นทางชีวิตข้างหน้า ถ้าถนนสวยงามจะมีอนาคตที่สดใส',
                'lucky_numbers' => [70, 7, 72],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'มอเตอร์ไซค์',
                'meaning_th' => 'ฝันเห็นมอเตอร์ไซค์หมายถึงความอิสระและความเร็ว แต่ต้องระวังอันตรายจากความประมาท',
                'lucky_numbers' => [71, 17, 79],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
        ];
    }

    /**
     * หมวดบ้านและสิ่งก่อสร้าง - ฝันเกี่ยวกับอาคารสถานที่
     */
    private function getBuildingEntries(int $categoryId): array
    {
        return [
            [
                'category_id' => $categoryId,
                'keyword_th' => 'บ้าน',
                'meaning_th' => 'ฝันเห็นบ้านหมายถึงครอบครัวและความมั่นคง ถ้าบ้านสวยงามจะมีความสุข ถ้าบ้านพังต้องระวังปัญหา',
                'lucky_numbers' => [2, 20, 22],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'วัด',
                'meaning_th' => 'ฝันเห็นวัดเป็นมงคล หมายถึงจิตใจสงบและจะได้รับการคุ้มครองจากสิ่งศักดิ์สิทธิ์',
                'lucky_numbers' => [5, 15, 50],
                'is_good_omen' => true,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'โรงเรียน',
                'meaning_th' => 'ฝันเห็นโรงเรียนบ่งบอกถึงการเรียนรู้และพัฒนาตนเอง จะมีบทเรียนใหม่ที่ต้องเรียนรู้',
                'lucky_numbers' => [24, 42, 45],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ตลาด',
                'meaning_th' => 'ฝันเห็นตลาดหมายถึงโอกาสทางการค้าและรายได้ จะมีช่องทางหาเงินใหม่ๆ เข้ามา',
                'lucky_numbers' => [26, 62, 29],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'โรงพยาบาล',
                'meaning_th' => 'ฝันเห็นโรงพยาบาลบ่งบอกถึงเรื่องสุขภาพ ควรไปตรวจสุขภาพและดูแลตัวเองให้ดี',
                'lucky_numbers' => [48, 84, 43],
                'is_good_omen' => false,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'บ้านเก่า',
                'meaning_th' => 'ฝันเห็นบ้านเก่าหมายถึงความทรงจำในอดีต อาจมีเรื่องเก่าๆ ที่ต้องกลับไปจัดการ',
                'lucky_numbers' => [20, 2, 27],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ตึกสูง',
                'meaning_th' => 'ฝันเห็นตึกสูงหมายถึงความทะเยอทะยาน ถ้าอยู่บนยอดตึกจะประสบความสำเร็จสูง',
                'lucky_numbers' => [89, 98, 92],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ห้องมืด',
                'meaning_th' => 'ฝันเห็นห้องมืดบ่งบอกถึงความไม่แน่ใจและความกลัว ต้องหาทางออกและกล้าเผชิญหน้ากับปัญหา',
                'lucky_numbers' => [40, 4, 41],
                'is_good_omen' => false,
                'intensity' => 3,
            ],
        ];
    }

    /**
     * หมวดศาสนาและสิ่งศักดิ์สิทธิ์ - ฝันเกี่ยวกับศาสนา
     */
    private function getReligionEntries(int $categoryId): array
    {
        return [
            [
                'category_id' => $categoryId,
                'keyword_th' => 'พระพุทธรูป',
                'meaning_th' => 'ฝันเห็นพระพุทธรูปเป็นมงคลสูงสุด หมายถึงจะได้รับการคุ้มครองและมีสิ่งดีๆ เกิดขึ้น',
                'lucky_numbers' => [5, 15, 50],
                'is_good_omen' => true,
                'intensity' => 5,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'พระสงฆ์',
                'meaning_th' => 'ฝันเห็นพระสงฆ์หมายถึงการได้รับพรและคำแนะนำดีๆ จะมีผู้ใหญ่คอยชี้แนะทางที่ถูกต้อง',
                'lucky_numbers' => [51, 15, 59],
                'is_good_omen' => true,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'เทวดา',
                'meaning_th' => 'ฝันเห็นเทวดาเป็นลางดีอย่างมาก หมายถึงสิ่งศักดิ์สิทธิ์คอยคุ้มครอง โชคลาภจะมาถึง',
                'lucky_numbers' => [19, 91, 95],
                'is_good_omen' => true,
                'intensity' => 5,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'สวรรค์',
                'meaning_th' => 'ฝันเห็นสวรรค์หมายถึงจิตใจสูงส่งและกุศลกรรมที่ทำมา ชีวิตจะมีแต่ความสุขความเจริญ',
                'lucky_numbers' => [55, 5, 59],
                'is_good_omen' => true,
                'intensity' => 5,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'นรก',
                'meaning_th' => 'ฝันเห็นนรกบ่งบอกถึงบาปกรรมที่ทำไว้ ควรทำบุญกุศลและเลิกทำสิ่งไม่ดี',
                'lucky_numbers' => [44, 4, 40],
                'is_good_omen' => false,
                'intensity' => 5,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'สวดมนต์',
                'meaning_th' => 'ฝันว่าสวดมนต์หมายถึงจิตใจสงบและได้รับพลังบุญ จะผ่านพ้นอุปสรรคไปได้ด้วยดี',
                'lucky_numbers' => [50, 5, 56],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ทำบุญ',
                'meaning_th' => 'ฝันว่าทำบุญตักบาตรหมายถึงจะได้รับผลบุญตอบแทน ชีวิตจะมีแต่สิ่งดีๆ เข้ามา',
                'lucky_numbers' => [59, 95, 55],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'พระธาตุ',
                'meaning_th' => 'ฝันเห็นพระธาตุหรือเจดีย์เป็นมงคลสูง หมายถึงจะได้รับบุญบารมีคุ้มครอง โชคดีมาก',
                'lucky_numbers' => [15, 51, 53],
                'is_good_omen' => true,
                'intensity' => 4,
            ],
        ];
    }

    /**
     * หมวดไฟและภัยพิบัติ - ฝันเกี่ยวกับภัยพิบัติ
     */
    private function getDisasterEntries(int $categoryId): array
    {
        return [
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ไฟไหม้',
                'meaning_th' => 'ฝันเห็นไฟไหม้หมายถึงการเปลี่ยนแปลงอย่างรุนแรง อาจมีการสูญเสียแต่จะนำไปสู่สิ่งใหม่',
                'lucky_numbers' => [3, 13, 31],
                'is_good_omen' => false,
                'intensity' => 5,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'แผ่นดินไหว',
                'meaning_th' => 'ฝันเห็นแผ่นดินไหวบ่งบอกถึงความไม่มั่นคงในชีวิต สิ่งที่เคยมั่นคงอาจสั่นคลอน ต้องเตรียมตัวรับมือ',
                'lucky_numbers' => [31, 13, 38],
                'is_good_omen' => false,
                'intensity' => 5,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ฟ้าผ่า',
                'meaning_th' => 'ฝันเห็นฟ้าผ่าหมายถึงเหตุการณ์ที่จะเกิดขึ้นอย่างกะทันหัน อาจเป็นทั้งดีและร้าย ต้องมีสติ',
                'lucky_numbers' => [37, 73, 30],
                'is_good_omen' => false,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'พายุ',
                'meaning_th' => 'ฝันเห็นพายุบ่งบอกถึงช่วงเวลาที่ยากลำบาก แต่หลังพายุผ่านไปทุกอย่างจะกลับมาดีขึ้น',
                'lucky_numbers' => [37, 73, 35],
                'is_good_omen' => false,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'สึนามิ',
                'meaning_th' => 'ฝันเห็นสึนามิหมายถึงคลื่นปัญหาใหญ่ที่กำลังจะมา ต้องเตรียมตัวและหาทางหนีให้ทัน',
                'lucky_numbers' => [81, 18, 88],
                'is_good_omen' => false,
                'intensity' => 5,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ตึกถล่ม',
                'meaning_th' => 'ฝันเห็นตึกถล่มบ่งบอกถึงสิ่งที่เคยมั่นคงกำลังพังทลาย อาจเป็นธุรกิจ ความสัมพันธ์ หรือความเชื่อ',
                'lucky_numbers' => [30, 3, 39],
                'is_good_omen' => false,
                'intensity' => 5,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ระเบิด',
                'meaning_th' => 'ฝันเห็นระเบิดหมายถึงอารมณ์ที่ถูกกดดันจนระเบิดออกมา ควรหาทางระบายความเครียด',
                'lucky_numbers' => [31, 13, 33],
                'is_good_omen' => false,
                'intensity' => 5,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'น้ำป่า',
                'meaning_th' => 'ฝันเห็นน้ำป่าไหลหลากบ่งบอกถึงปัญหาที่มาอย่างรวดเร็วและรุนแรง ต้องรีบหาทางรับมือ',
                'lucky_numbers' => [81, 18, 80],
                'is_good_omen' => false,
                'intensity' => 4,
            ],
        ];
    }

    /**
     * หมวดความรักและครอบครัว - ฝันเกี่ยวกับความรัก
     */
    private function getLoveFamilyEntries(int $categoryId): array
    {
        return [
            [
                'category_id' => $categoryId,
                'keyword_th' => 'แต่งงาน',
                'meaning_th' => 'ฝันเห็นงานแต่งงานเป็นลางดี หมายถึงการเริ่มต้นสิ่งใหม่ที่ดีงาม ความสัมพันธ์จะแน่นแฟ้นขึ้น',
                'lucky_numbers' => [2, 12, 21],
                'is_good_omen' => true,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ตั้งครรภ์',
                'meaning_th' => 'ฝันว่าตั้งครรภ์หมายถึงสิ่งใหม่กำลังจะเกิดขึ้นในชีวิต อาจเป็นโปรเจกต์ใหม่หรือความคิดใหม่',
                'lucky_numbers' => [32, 23, 29],
                'is_good_omen' => true,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'เลิกกัน',
                'meaning_th' => 'ฝันว่าเลิกกับคนรักบ่งบอกถึงความกลัวการสูญเสีย ควรดูแลความสัมพันธ์ให้ดีและสื่อสารกันมากขึ้น',
                'lucky_numbers' => [24, 42, 44],
                'is_good_omen' => false,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'จูบ',
                'meaning_th' => 'ฝันว่าจูบหมายถึงความรักความอบอุ่น จะมีช่วงเวลาแห่งความสุขกับคนที่รัก',
                'lucky_numbers' => [21, 12, 25],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'กอด',
                'meaning_th' => 'ฝันว่ากอดใครสักคนหมายถึงความต้องการความอบอุ่นและความรัก จะได้รับกำลังใจจากคนรอบข้าง',
                'lucky_numbers' => [22, 2, 28],
                'is_good_omen' => true,
                'intensity' => 2,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ลูก',
                'meaning_th' => 'ฝันเห็นลูกหมายถึงความห่วงใยและความรับผิดชอบ จะมีสิ่งที่ต้องดูแลเอาใจใส่อย่างใกล้ชิด',
                'lucky_numbers' => [23, 32, 36],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'ครอบครัว',
                'meaning_th' => 'ฝันเห็นครอบครัวอยู่พร้อมหน้าพร้อมตาเป็นลางดี หมายถึงความสุขและความอบอุ่นในครอบครัว',
                'lucky_numbers' => [29, 92, 22],
                'is_good_omen' => true,
                'intensity' => 3,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'หย่าร้าง',
                'meaning_th' => 'ฝันว่าหย่าร้างบ่งบอกถึงความขัดแย้งในความสัมพันธ์ ควรหันหน้าเข้าหากันและแก้ปัญหาร่วมกัน',
                'lucky_numbers' => [40, 4, 42],
                'is_good_omen' => false,
                'intensity' => 4,
            ],
            [
                'category_id' => $categoryId,
                'keyword_th' => 'คลอดลูก',
                'meaning_th' => 'ฝันว่าคลอดลูกหมายถึงผลสำเร็จของสิ่งที่บ่มเพาะมานาน โปรเจกต์หรือแผนงานจะสำเร็จ',
                'lucky_numbers' => [32, 23, 39],
                'is_good_omen' => true,
                'intensity' => 4,
            ],
        ];
    }
}
