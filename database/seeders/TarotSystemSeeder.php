<?php

namespace Database\Seeders;

use App\Models\TarotCard;
use App\Models\TarotSpreadType;
use App\Models\TarotReadingCategory;
use App\Models\TarotSetting;
use App\Models\TarotCardBackImage;
use Illuminate\Database\Seeder;

class TarotSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedTarotCards();
        $this->seedSpreadTypes();
        $this->seedReadingCategories();
        $this->seedSettings();
        $this->seedCardBackImages();
    }

    /**
     * Seed 78 Tarot Cards
     */
    private function seedTarotCards(): void
    {
        // Skip if cards already exist
        if (TarotCard::count() >= 78) {
            return;
        }

        // Major Arcana (22 cards)
        $majorArcana = [
            [
                'number' => 0,
                'name_en' => 'The Fool',
                'name_th' => 'คนบ้า',
                'keywords_en' => ['new beginnings', 'innocence', 'spontaneity', 'free spirit'],
                'keywords_th' => ['การเริ่มต้นใหม่', 'ความบริสุทธิ์', 'ความเป็นธรรมชาติ', 'จิตวิญญาณเสรี'],
                'upright_meaning_th' => 'การเริ่มต้นใหม่ การผจญภัย ความบริสุทธิ์ ความเป็นธรรมชาติ โอกาสใหม่ๆ การก้าวไปข้างหน้าด้วยความมั่นใจ',
                'reversed_meaning_th' => 'ความประมาท ความไร้เหตุผล การขาดการวางแผน ความเสี่ยงที่ไม่จำเป็น',
            ],
            [
                'number' => 1,
                'name_en' => 'The Magician',
                'name_th' => 'นักมายากล',
                'keywords_en' => ['manifestation', 'resourcefulness', 'power', 'inspired action'],
                'keywords_th' => ['การสร้างสรรค์', 'ความสามารถ', 'พลัง', 'การกระทำที่มีแรงบันดาลใจ'],
                'upright_meaning_th' => 'ความสามารถในการทำสิ่งต่างๆให้สำเร็จ การใช้ทักษะและความสามารถอย่างเต็มที่ การสร้างสรรค์สิ่งใหม่ พลังแห่งการกระทำ',
                'reversed_meaning_th' => 'การใช้พลังในทางที่ผิด การหลอกลวง ความสามารถที่ยังไม่พัฒนา การขาดทิศทาง',
            ],
            [
                'number' => 2,
                'name_en' => 'The High Priestess',
                'name_th' => 'มหาปุโรหิตหญิง',
                'keywords_en' => ['intuition', 'sacred knowledge', 'divine feminine', 'subconscious'],
                'keywords_th' => ['สัญชาตญาณ', 'ความรู้ลึกลับ', 'พลังแห่งหญิง', 'จิตใต้สำนึก'],
                'upright_meaning_th' => 'สัญชาตญาณที่แข็งแกร่ง ความรู้ที่ลึกซึ้ง ความลึกลับ การฟังเสียงภายใน ความเข้าใจในระดับจิตวิญญาณ',
                'reversed_meaning_th' => 'การเพิกเฉยต่อสัญชาตญาณ ความลับที่ถูกเปิดเผย การขาดความเชื่อมโยงกับตัวเอง',
            ],
            [
                'number' => 3,
                'name_en' => 'The Empress',
                'name_th' => 'จักรพรรดินี',
                'keywords_en' => ['femininity', 'beauty', 'nature', 'nurturing', 'abundance'],
                'keywords_th' => ['ความเป็นหญิง', 'ความงาม', 'ธรรมชาติ', 'การเลี้ยงดู', 'ความอุดมสมบูรณ์'],
                'upright_meaning_th' => 'ความอุดมสมบูรณ์ การเติบโต ความงาม การเลี้ยงดู ความรัก ความอ่อนโยน ความสำเร็จในครอบครัว',
                'reversed_meaning_th' => 'การขาดความมั่นใจ ความสับสน การขาดการเติบโต ปัญหาเรื่องความอุดมสมบูรณ์',
            ],
            [
                'number' => 4,
                'name_en' => 'The Emperor',
                'name_th' => 'จักรพรรดิ',
                'keywords_en' => ['authority', 'structure', 'control', 'fatherhood'],
                'keywords_th' => ['อำนาจ', 'โครงสร้าง', 'การควบคุม', 'ความเป็นพ่อ'],
                'upright_meaning_th' => 'อำนาจ การควบคุม โครงสร้างที่มั่นคง ความเป็นผู้นำ กฎระเบียบ การตัดสินใจที่มั่นคง',
                'reversed_meaning_th' => 'การใช้อำนาจเกินขอบเขต ความเข้มงวดเกินไป ความเป็นเผด็จการ การขาดระเบียบวินัย',
            ],
            [
                'number' => 5,
                'name_en' => 'The Hierophant',
                'name_th' => 'มหาปุโรหิต',
                'keywords_en' => ['spiritual wisdom', 'religious beliefs', 'conformity', 'tradition'],
                'keywords_th' => ['ภูมิปัญญาทางจิตวิญญาณ', 'ความเชื่อทางศาสนา', 'ประเพณี', 'การยึดมั่น'],
                'upright_meaning_th' => 'ความเชื่อทางจิตวิญญาณ ประเพณี การศึกษา การแนะนำ ความรู้ที่สืบทอด การยึดมั่นในหลักการ',
                'reversed_meaning_th' => 'การท้าทายประเพณี ความเชื่อที่ล้าสมัย การขาดความยืดหยุ่น การหลุดพ้นจากข้อจำกัด',
            ],
            [
                'number' => 6,
                'name_en' => 'The Lovers',
                'name_th' => 'คู่รัก',
                'keywords_en' => ['love', 'harmony', 'relationships', 'choices'],
                'keywords_th' => ['ความรัก', 'ความสามัคคี', 'ความสัมพันธ์', 'การเลือก'],
                'upright_meaning_th' => 'ความรักที่แท้จริง ความสัมพันธ์ที่ลงตัว การเลือกที่สำคัญ ความกลมกลืน การรวมเป็นหนึ่ง',
                'reversed_meaning_th' => 'ความไม่ลงรอยกัน การเลือกที่ผิดพลาด ความสัมพันธ์ที่ไม่สมดุล ความขัดแย้ง',
            ],
            [
                'number' => 7,
                'name_en' => 'The Chariot',
                'name_th' => 'รถม้า',
                'keywords_en' => ['control', 'willpower', 'success', 'determination'],
                'keywords_th' => ['การควบคุม', 'พลังใจ', 'ความสำเร็จ', 'ความมุ่งมั่น'],
                'upright_meaning_th' => 'ความมุ่งมั่น ความสำเร็จ การควบคุมทิศทาง ชัยชนะ การก้าวไปข้างหน้าอย่างมั่นใจ',
                'reversed_meaning_th' => 'การสูญเสียการควบคุม ทิศทางที่สับสน ความล้มเหลว การขาดแรงจูงใจ',
            ],
            [
                'number' => 8,
                'name_en' => 'Strength',
                'name_th' => 'พลัง',
                'keywords_en' => ['strength', 'courage', 'patience', 'control'],
                'keywords_th' => ['ความแข็งแกร่ง', 'ความกล้าหาญ', 'ความอดทน', 'การควบคุม'],
                'upright_meaning_th' => 'ความแข็งแกร่งภายใน ความกล้าหาญ ความอดทน การควบคุมตนเอง ความอ่อนโยนที่มีพลัง',
                'reversed_meaning_th' => 'ความอ่อนแอ การขาดความมั่นใจ ความกลัว การไม่สามารถควบคุมตนเอง',
            ],
            [
                'number' => 9,
                'name_en' => 'The Hermit',
                'name_th' => 'นักบวช',
                'keywords_en' => ['soul searching', 'introspection', 'inner guidance', 'solitude'],
                'keywords_th' => ['การค้นหาตัวเอง', 'การมองเข้าไปข้างใน', 'การชี้นำภายใน', 'ความสันโดษ'],
                'upright_meaning_th' => 'การค้นหาตัวเอง ปัญญาภายใน ความสันโดษ การใคร่ครวญ การเดินทางภายใน',
                'reversed_meaning_th' => 'ความโดดเดี่ยว การแยกตัว ความสับสน การหลีกหนีจากความจริง',
            ],
            [
                'number' => 10,
                'name_en' => 'Wheel of Fortune',
                'name_th' => 'กงล้อแห่งโชค',
                'keywords_en' => ['good luck', 'karma', 'life cycles', 'destiny'],
                'keywords_th' => ['โชคดี', 'กรรม', 'วงจรชีวิต', 'โชคชะตา'],
                'upright_meaning_th' => 'การเปลี่ยนแปลง โชคลาภ วงจรชีวิต โอกาสใหม่ จุดเปลี่ยนที่สำคัญ',
                'reversed_meaning_th' => 'โชคร้าย ความไม่แน่นอน การต่อต้านการเปลี่ยนแปลง วงจรที่ไม่ดี',
            ],
            [
                'number' => 11,
                'name_en' => 'Justice',
                'name_th' => 'ความยุติธรรม',
                'keywords_en' => ['justice', 'fairness', 'truth', 'law'],
                'keywords_th' => ['ความยุติธรรม', 'ความเป็นธรรม', 'ความจริง', 'กฎหมาย'],
                'upright_meaning_th' => 'ความยุติธรรม ความเป็นธรรม ความจริง การตัดสินใจที่ถูกต้อง ความสมดุล',
                'reversed_meaning_th' => 'ความไม่เป็นธรรม ความลำเอียง การตัดสินผิดพลาด ความไม่สมดุล',
            ],
            [
                'number' => 12,
                'name_en' => 'The Hanged Man',
                'name_th' => 'ชายผู้ถูกแขวนคอ',
                'keywords_en' => ['pause', 'surrender', 'letting go', 'new perspectives'],
                'keywords_th' => ['การหยุดพัก', 'การยอมรับ', 'การปล่อยวาง', 'มุมมองใหม่'],
                'upright_meaning_th' => 'การหยุดพัก การเห็นจากมุมมองใหม่ การยอมรับ การเสียสละ การรอคอย',
                'reversed_meaning_th' => 'การต่อต้าน ความไม่พร้อมที่จะปล่อยวาง การเสียเวลา ความหยุดชะงัก',
            ],
            [
                'number' => 13,
                'name_en' => 'Death',
                'name_th' => 'ความตาย',
                'keywords_en' => ['endings', 'change', 'transformation', 'transition'],
                'keywords_th' => ['การสิ้นสุด', 'การเปลี่ยนแปลง', 'การเปลี่ยนแปลงครั้งใหญ่', 'การเปลี่ยนผ่าน'],
                'upright_meaning_th' => 'การสิ้นสุดและการเริ่มต้นใหม่ การเปลี่ยนแปลงครั้งใหญ่ การปล่อยวางอดีต การเปลี่ยนแปลงที่จำเป็น',
                'reversed_meaning_th' => 'การต่อต้านการเปลี่ยนแปลง ความกลัวต่อสิ่งใหม่ การยึดติดกับอดีต',
            ],
            [
                'number' => 14,
                'name_en' => 'Temperance',
                'name_th' => 'ความพอประมาณ',
                'keywords_en' => ['balance', 'moderation', 'patience', 'purpose'],
                'keywords_th' => ['ความสมดุล', 'ความพอดี', 'ความอดทน', 'จุดมุ่งหมาย'],
                'upright_meaning_th' => 'ความสมดุล ความพอดี ความอดทน การประสานกลมกลืน ความสงบ',
                'reversed_meaning_th' => 'ความไม่สมดุล ความเกินพอดี ความใจร้อน การขาดการประสานงาน',
            ],
            [
                'number' => 15,
                'name_en' => 'The Devil',
                'name_th' => 'ปีศาจ',
                'keywords_en' => ['shadow self', 'attachment', 'addiction', 'restriction'],
                'keywords_th' => ['ด้านมืด', 'การยึดติด', 'การเสพติด', 'ข้อจำกัด'],
                'upright_meaning_th' => 'การยึดติด ข้อจำกัด ความมืดมิด สิ่งล่อใจ อำนาจของวัตถุนิยม',
                'reversed_meaning_th' => 'การปลดปล่อย การหลุดพ้น การเอาชนะความกลัว การมองเห็นความจริง',
            ],
            [
                'number' => 16,
                'name_en' => 'The Tower',
                'name_th' => 'หอคอย',
                'keywords_en' => ['sudden change', 'upheaval', 'chaos', 'revelation'],
                'keywords_th' => ['การเปลี่ยนแปลงกระทันหัน', 'ความโกลาหล', 'ความวุ่นวาย', 'การเปิดเผย'],
                'upright_meaning_th' => 'การเปลี่ยนแปลงกะทันหัน การพังทลาย ความจริงที่ถูกเปิดเผย วิกฤตการณ์',
                'reversed_meaning_th' => 'การหลีกเลี่ยงภัยพิบัติ การเปลี่ยนแปลงที่ค่อยเป็นค่อยไป ความกลัวต่อการเปลี่ยนแปลง',
            ],
            [
                'number' => 17,
                'name_en' => 'The Star',
                'name_th' => 'ดวงดาว',
                'keywords_en' => ['hope', 'faith', 'purpose', 'renewal'],
                'keywords_th' => ['ความหวัง', 'ศรัทธา', 'จุดมุ่งหมาย', 'การฟื้นฟู'],
                'upright_meaning_th' => 'ความหวัง การฟื้นฟู แรงบันดาลใจ ความเชื่อมั่น ความสงบในจิตใจ',
                'reversed_meaning_th' => 'การสูญเสียความหวัง ความท้อแท้ การขาดแรงบันดาลใจ ความสงสัยในตนเอง',
            ],
            [
                'number' => 18,
                'name_en' => 'The Moon',
                'name_th' => 'ดวงจันทร์',
                'keywords_en' => ['illusion', 'fear', 'anxiety', 'subconscious'],
                'keywords_th' => ['ภาพลวงตา', 'ความกลัว', 'ความวิตกกังวล', 'จิตใต้สำนึก'],
                'upright_meaning_th' => 'ความไม่แน่นอน ภาพลวงตา ความกลัว สัญชาตญาณ ความลึกลับ',
                'reversed_meaning_th' => 'การปลดปล่อยความกลัว ความชัดเจน การเปิดเผยความจริง',
            ],
            [
                'number' => 19,
                'name_en' => 'The Sun',
                'name_th' => 'ดวงอาทิตย์',
                'keywords_en' => ['positivity', 'fun', 'warmth', 'success'],
                'keywords_th' => ['ความเป็นบวก', 'ความสนุกสนาน', 'ความอบอุ่น', 'ความสำเร็จ'],
                'upright_meaning_th' => 'ความสุข ความสำเร็จ ความมีชีวิตชีวา พลังงานบวก ความสดใส',
                'reversed_meaning_th' => 'ความมืดมน ความไม่มีความสุข ความล้มเหลวชั่วคราว การมองโลกในแง่ร้าย',
            ],
            [
                'number' => 20,
                'name_en' => 'Judgement',
                'name_th' => 'การพิพากษา',
                'keywords_en' => ['judgement', 'rebirth', 'inner calling', 'absolution'],
                'keywords_th' => ['การตัดสิน', 'การเกิดใหม่', 'เสียงเรียกภายใน', 'การให้อภัย'],
                'upright_meaning_th' => 'การเกิดใหม่ การตัดสินที่ดี การให้อภัย การยอมรับในอดีต จุดเปลี่ยนสำคัญ',
                'reversed_meaning_th' => 'ความสงสัยในตนเอง การตัดสินผิดพลาด การไม่ให้อภัย การยึดติดอดีต',
            ],
            [
                'number' => 21,
                'name_en' => 'The World',
                'name_th' => 'โลก',
                'keywords_en' => ['completion', 'accomplishment', 'travel', 'success'],
                'keywords_th' => ['ความสมบูรณ์', 'ความสำเร็จ', 'การเดินทาง', 'ความสำเร็จ'],
                'upright_meaning_th' => 'ความสำเร็จอย่างสมบูรณ์ การบรรลุเป้าหมาย การเดินทาง ความสมบูรณ์',
                'reversed_meaning_th' => 'ความไม่สมบูรณ์ การขาดความสำเร็จ ความรู้สึกติดขัด การไม่เสร็จสิ้น',
            ],
        ];

        foreach ($majorArcana as $card) {
            TarotCard::create(array_merge($card, [
                'type' => 'major_arcana',
                'suit' => null,
                'description_en' => $card['name_en'] . ' represents ' . implode(', ', $card['keywords_en']),
                'description_th' => 'ไพ่' . $card['name_th'] . ' แทน' . implode(', ', $card['keywords_th']),
                'upright_meaning_en' => 'Upright: ' . implode(', ', $card['keywords_en']),
                'reversed_meaning_en' => 'Reversed: Blocked or excessive ' . implode(', ', $card['keywords_en']),
            ]));
        }

        // Minor Arcana - Wands (14 cards)
        $this->seedMinorArcana('wands', 'ไม้เท้า', [
            'en' => ['passion', 'creativity', 'action', 'adventure'],
            'th' => ['ความหลงใหล', 'ความคิดสร้างสรรค์', 'การกระทำ', 'การผจญภัย']
        ]);

        // Minor Arcana - Cups (14 cards)
        $this->seedMinorArcana('cups', 'ถ้วย', [
            'en' => ['emotions', 'relationships', 'feelings', 'intuition'],
            'th' => ['อารมณ์', 'ความสัมพันธ์', 'ความรู้สึก', 'สัญชาตญาณ']
        ]);

        // Minor Arcana - Swords (14 cards)
        $this->seedMinorArcana('swords', 'ดาบ', [
            'en' => ['thoughts', 'intellect', 'conflict', 'communication'],
            'th' => ['ความคิด', 'สติปัญญา', 'ความขัดแย้ง', 'การสื่อสาร']
        ]);

        // Minor Arcana - Pentacles (14 cards)
        $this->seedMinorArcana('pentacles', 'เหรียญ', [
            'en' => ['material', 'money', 'career', 'practical'],
            'th' => ['วัตถุ', 'เงิน', 'การงาน', 'ความเป็นจริง']
        ]);
    }

    /**
     * Seed Minor Arcana for a suit
     */
    private function seedMinorArcana(string $suit, string $suitTh, array $keywords): void
    {
        $ranks = [
            ['number' => 1, 'name' => 'Ace', 'name_th' => 'เอซ'],
            ['number' => 2, 'name' => 'Two', 'name_th' => 'สอง'],
            ['number' => 3, 'name' => 'Three', 'name_th' => 'สาม'],
            ['number' => 4, 'name' => 'Four', 'name_th' => 'สี่'],
            ['number' => 5, 'name' => 'Five', 'name_th' => 'ห้า'],
            ['number' => 6, 'name' => 'Six', 'name_th' => 'หก'],
            ['number' => 7, 'name' => 'Seven', 'name_th' => 'เจ็ด'],
            ['number' => 8, 'name' => 'Eight', 'name_th' => 'แปด'],
            ['number' => 9, 'name' => 'Nine', 'name_th' => 'เก้า'],
            ['number' => 10, 'name' => 'Ten', 'name_th' => 'สิบ'],
            ['number' => 11, 'name' => 'Page', 'name_th' => 'ข้าราชบริพาร'],
            ['number' => 12, 'name' => 'Knight', 'name_th' => 'อัศวิน'],
            ['number' => 13, 'name' => 'Queen', 'name_th' => 'ราชินี'],
            ['number' => 14, 'name' => 'King', 'name_th' => 'กษัตริย์'],
        ];

        foreach ($ranks as $rank) {
            TarotCard::create([
                'type' => 'minor_arcana',
                'suit' => $suit,
                'number' => $rank['number'],
                'name_en' => $rank['name'] . ' of ' . ucfirst($suit),
                'name_th' => $rank['name_th'] . 'แห่ง' . $suitTh,
                'keywords_en' => $keywords['en'],
                'keywords_th' => $keywords['th'],
                'description_en' => $rank['name'] . ' of ' . ucfirst($suit) . ' - ' . implode(', ', $keywords['en']),
                'description_th' => $rank['name_th'] . 'แห่ง' . $suitTh . ' - ' . implode(', ', $keywords['th']),
                'upright_meaning_en' => 'Positive aspects of ' . implode(', ', $keywords['en']),
                'upright_meaning_th' => 'ด้านบวกของ' . implode(', ', $keywords['th']),
                'reversed_meaning_en' => 'Negative aspects of ' . implode(', ', $keywords['en']),
                'reversed_meaning_th' => 'ด้านลบของ' . implode(', ', $keywords['th']),
            ]);
        }
    }

    /**
     * Seed Spread Types
     */
    private function seedSpreadTypes(): void
    {
        $spreadTypes = [
            [
                'name_en' => 'Single Card',
                'name_th' => 'ไพ่ใบเดียว',
                'description_en' => 'A quick one-card reading for immediate insight',
                'description_th' => 'การทำนายแบบไพ่ใบเดียวสำหรับคำตอบรวดเร็ว',
                'card_count' => 1,
                'positions' => [
                    ['name_en' => 'Answer', 'name_th' => 'คำตอบ', 'description_th' => 'คำตอบของคำถามที่คุณถาม']
                ],
                'sort_order' => 1,
            ],
            [
                'name_en' => 'Past, Present, Future',
                'name_th' => 'อดีต ปัจจุบัน อนาคต',
                'description_en' => 'A three-card spread showing timeline',
                'description_th' => 'การเปิดไพ่ 3 ใบแสดงเส้นทางเวลา',
                'card_count' => 3,
                'positions' => [
                    ['name_en' => 'Past', 'name_th' => 'อดีต', 'description_th' => 'สิ่งที่ผ่านมาที่มีผลต่อปัจจุบัน'],
                    ['name_en' => 'Present', 'name_th' => 'ปัจจุบัน', 'description_th' => 'สถานการณ์ปัจจุบันของคุณ'],
                    ['name_en' => 'Future', 'name_th' => 'อนาคต', 'description_th' => 'สิ่งที่กำลังจะเกิดขึ้น']
                ],
                'sort_order' => 2,
            ],
            [
                'name_en' => 'Celtic Cross',
                'name_th' => 'ไม้กางเขนเซลติก',
                'description_en' => 'A comprehensive 10-card spread for deep insight',
                'description_th' => 'การเปิดไพ่ 10 ใบแบบละเอียดลึกซึ้ง',
                'card_count' => 10,
                'positions' => [
                    ['name_en' => 'Present', 'name_th' => 'ปัจจุบัน', 'description_th' => 'สถานการณ์ปัจจุบัน'],
                    ['name_en' => 'Challenge', 'name_th' => 'อุปสรรค', 'description_th' => 'สิ่งที่ขัดขวาง'],
                    ['name_en' => 'Past', 'name_th' => 'อดีต', 'description_th' => 'รากฐานของสถานการณ์'],
                    ['name_en' => 'Future', 'name_th' => 'อนาคต', 'description_th' => 'สิ่งที่กำลังจะมา'],
                    ['name_en' => 'Above', 'name_th' => 'เป้าหมาย', 'description_th' => 'สิ่งที่คุณมุ่งหวัง'],
                    ['name_en' => 'Below', 'name_th' => 'รากฐาน', 'description_th' => 'พื้นฐานของคุณ'],
                    ['name_en' => 'Advice', 'name_th' => 'คำแนะนำ', 'description_th' => 'สิ่งที่คุณควรทำ'],
                    ['name_en' => 'External', 'name_th' => 'ภายนอก', 'description_th' => 'อิทธิพลจากภายนอก'],
                    ['name_en' => 'Hopes', 'name_th' => 'ความหวัง', 'description_th' => 'ความหวังและความกลัว'],
                    ['name_en' => 'Outcome', 'name_th' => 'ผลลัพธ์', 'description_th' => 'ผลลัพธ์สุดท้าย']
                ],
                'sort_order' => 3,
            ],
            [
                'name_en' => 'Relationship Spread',
                'name_th' => 'การทำนายความสัมพันธ์',
                'description_en' => 'A 5-card spread focused on relationships',
                'description_th' => 'การเปิดไพ่ 5 ใบเกี่ยวกับความสัมพันธ์',
                'card_count' => 5,
                'positions' => [
                    ['name_en' => 'You', 'name_th' => 'คุณ', 'description_th' => 'สถานะของคุณ'],
                    ['name_en' => 'Partner', 'name_th' => 'คู่ของคุณ', 'description_th' => 'สถานะของคู่'],
                    ['name_en' => 'Connection', 'name_th' => 'ความเชื่อมโยง', 'description_th' => 'สิ่งที่เชื่อมคุณทั้งสอง'],
                    ['name_en' => 'Challenge', 'name_th' => 'อุปสรรค', 'description_th' => 'สิ่งที่เป็นปัญหา'],
                    ['name_en' => 'Outcome', 'name_th' => 'ผลลัพธ์', 'description_th' => 'อนาคตของความสัมพันธ์']
                ],
                'sort_order' => 4,
            ],
            [
                'name_en' => 'Career Path',
                'name_th' => 'เส้นทางอาชีพ',
                'description_en' => 'A 5-card spread for career guidance',
                'description_th' => 'การเปิดไพ่ 5 ใบเกี่ยวกับอาชีพการงาน',
                'card_count' => 5,
                'positions' => [
                    ['name_en' => 'Current', 'name_th' => 'ปัจจุบัน', 'description_th' => 'สถานการณ์งานปัจจุบัน'],
                    ['name_en' => 'Strengths', 'name_th' => 'จุดแข็ง', 'description_th' => 'จุดแข็งของคุณ'],
                    ['name_en' => 'Challenges', 'name_th' => 'อุปสรรค', 'description_th' => 'สิ่งที่ต้องเอาชนะ'],
                    ['name_en' => 'Advice', 'name_th' => 'คำแนะนำ', 'description_th' => 'สิ่งที่ควรทำ'],
                    ['name_en' => 'Outcome', 'name_th' => 'ผลลัพธ์', 'description_th' => 'อนาคตด้านอาชีพ']
                ],
                'sort_order' => 5,
            ],
        ];

        foreach ($spreadTypes as $spreadType) {
            TarotSpreadType::updateOrCreate(
                ['name_en' => $spreadType['name_en']],
                $spreadType
            );
        }
    }

    /**
     * Seed Reading Categories
     */
    private function seedReadingCategories(): void
    {
        $categories = [
            [
                'name_en' => 'Love & Relationships',
                'name_th' => 'ความรักและความสัมพันธ์',
                'slug' => 'love-relationships',
                'description_en' => 'Get insights about your romantic life and relationships',
                'description_th' => 'ค้นพบคำตอบเกี่ยวกับความรักและความสัมพันธ์ของคุณ',
                'icon' => 'fa-heart',
                'color' => '#FF6B9D',
                'price' => 0.00,
                'is_free_first' => true,
                'sort_order' => 1,
            ],
            [
                'name_en' => 'Career & Finance',
                'name_th' => 'การงานและการเงิน',
                'slug' => 'career-finance',
                'description_en' => 'Explore your career path and financial situation',
                'description_th' => 'สำรวจเส้นทางอาชีพและสถานการณ์การเงินของคุณ',
                'icon' => 'fa-briefcase',
                'color' => '#4CAF50',
                'price' => 99.00,
                'is_free_first' => true,
                'sort_order' => 2,
            ],
            [
                'name_en' => 'Personal Growth',
                'name_th' => 'การพัฒนาตนเอง',
                'slug' => 'personal-growth',
                'description_en' => 'Discover insights for your spiritual and personal development',
                'description_th' => 'ค้นพบข้อมูลเชิงลึกสำหรับการพัฒนาจิตวิญญาณและส่วนบุคคล',
                'icon' => 'fa-seedling',
                'color' => '#9C27B0',
                'price' => 79.00,
                'is_free_first' => true,
                'sort_order' => 3,
            ],
            [
                'name_en' => 'Health & Wellness',
                'name_th' => 'สุขภาพและความเป็นอยู่',
                'slug' => 'health-wellness',
                'description_en' => 'Get guidance on your physical and mental well-being',
                'description_th' => 'รับคำแนะนำเกี่ยวกับสุขภาพกายและใจของคุณ',
                'icon' => 'fa-spa',
                'color' => '#00BCD4',
                'price' => 89.00,
                'is_free_first' => true,
                'sort_order' => 4,
            ],
            [
                'name_en' => 'General Reading',
                'name_th' => 'การทำนายทั่วไป',
                'slug' => 'general',
                'description_en' => 'A general reading about your current life situation',
                'description_th' => 'การทำนายทั่วไปเกี่ยวกับสถานการณ์ชีวิตปัจจุบันของคุณ',
                'icon' => 'fa-star',
                'color' => '#FFC107',
                'price' => 0.00,
                'is_free_first' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $category) {
            TarotReadingCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }

    /**
     * Seed Default Settings
     */
    private function seedSettings(): void
    {
        $settings = [
            ['key' => 'enable_tarot_system', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable/disable tarot reading system'],
            ['key' => 'allow_guest_readings', 'value' => 'true', 'type' => 'boolean', 'description' => 'Allow guest users to get readings'],
            ['key' => 'require_payment_gateway', 'value' => 'promptpay', 'type' => 'string', 'description' => 'Default payment gateway for paid readings'],
            ['key' => 'save_readings_days', 'value' => '90', 'type' => 'integer', 'description' => 'Days to keep saved readings'],
            ['key' => 'enable_ai_interpretation', 'value' => 'false', 'type' => 'boolean', 'description' => 'Enable AI-powered interpretation'],
            ['key' => 'show_reversed_cards', 'value' => 'true', 'type' => 'boolean', 'description' => 'Allow reversed card readings'],
            ['key' => 'animation_speed', 'value' => 'medium', 'type' => 'string', 'description' => 'Card animation speed: slow, medium, fast'],
        ];

        foreach ($settings as $setting) {
            TarotSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Seed Default Card Back Images
     */
    private function seedCardBackImages(): void
    {
        TarotCardBackImage::updateOrCreate(
            ['name' => 'Default Purple'],
            [
                'name' => 'Default Purple',
                'image_url' => '/images/tarot/card-backs/default-purple.png',
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
    }
}
