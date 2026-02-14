<?php

namespace App\Services;

use App\Models\TarotCard;
use App\Models\TarotCardInterpretation;
use App\Models\TarotReading;
use App\Models\TarotReadingCard;

/**
 * TarotInterpretationService
 *
 * บริการสำหรับสร้างคำทำนายไพ่ทาโร่ต์แบบละเอียด
 * รวมบริบทของหมวดหมู่, ตำแหน่งไพ่, และสถานะหงาย/กลับหัว
 *
 * @author Claude AI
 *
 * @version 1.0.0
 */
class TarotInterpretationService
{
    /**
     * บริบทของแต่ละหมวดหมู่การทำนาย
     * กำหนดโฟกัสและคำศัพท์ที่ใช้ในแต่ละด้าน
     */
    protected array $categoryContexts = [
        'love-relationships' => [
            'focus' => 'ความรักและความสัมพันธ์',
            'aspects' => ['ความรัก', 'การคบหา', 'ความสัมพันธ์', 'คู่ครอง', 'ความผูกพัน', 'หัวใจ'],
            'questions' => ['คู่ของคุณ', 'ความรู้สึก', 'อนาคตความสัมพันธ์', 'โอกาสพบรัก'],
            'energy' => 'พลังแห่งความรัก',
            'guidance_prefix' => 'ในเรื่องความรัก',
        ],
        'career-finance' => [
            'focus' => 'การงานและการเงิน',
            'aspects' => ['อาชีพ', 'การเงิน', 'ธุรกิจ', 'รายได้', 'ความก้าวหน้า', 'โอกาส'],
            'questions' => ['เส้นทางอาชีพ', 'การลงทุน', 'รายได้', 'ความสำเร็จ'],
            'energy' => 'พลังแห่งความมั่งคั่ง',
            'guidance_prefix' => 'ในเรื่องการงาน',
        ],
        'personal-growth' => [
            'focus' => 'การพัฒนาตนเอง',
            'aspects' => ['จิตวิญญาณ', 'สติปัญญา', 'การเติบโต', 'ศักยภาพ', 'ความรู้', 'ปัญญา'],
            'questions' => ['เส้นทางชีวิต', 'จุดมุ่งหมาย', 'การพัฒนา', 'ศักยภาพภายใน'],
            'energy' => 'พลังแห่งการตื่นรู้',
            'guidance_prefix' => 'ในเรื่องการพัฒนาตนเอง',
        ],
        'health-wellness' => [
            'focus' => 'สุขภาพและความเป็นอยู่',
            'aspects' => ['สุขภาพกาย', 'สุขภาพจิต', 'พลังชีวิต', 'ความสมดุล', 'การดูแลตัวเอง'],
            'questions' => ['สุขภาวะ', 'พลังงาน', 'การฟื้นฟู', 'ความสมดุลชีวิต'],
            'energy' => 'พลังแห่งการเยียวยา',
            'guidance_prefix' => 'ในเรื่องสุขภาพ',
        ],
        'general' => [
            'focus' => 'ภาพรวมชีวิต',
            'aspects' => ['ชีวิตโดยทั่วไป', 'เส้นทาง', 'โชคชะตา', 'พลังงาน', 'โอกาส'],
            'questions' => ['ทิศทางชีวิต', 'คำแนะนำ', 'สิ่งที่ควรรู้', 'พลังงานรอบตัว'],
            'energy' => 'พลังแห่งจักรวาล',
            'guidance_prefix' => 'ในภาพรวม',
        ],
    ];

    /**
     * บริบทของตำแหน่งไพ่แต่ละตำแหน่ง
     */
    protected array $positionContexts = [
        // สำหรับไพ่ใบเดียว
        'คำตอบ' => [
            'meaning' => 'นี่คือคำตอบที่จักรวาลส่งมาให้คุณ',
            'time' => 'ปัจจุบัน',
            'focus' => 'คำตอบตรงๆ สำหรับคำถามของคุณ',
        ],
        'Answer' => [
            'meaning' => 'นี่คือคำตอบที่จักรวาลส่งมาให้คุณ',
            'time' => 'ปัจจุบัน',
            'focus' => 'คำตอบตรงๆ สำหรับคำถามของคุณ',
        ],

        // สำหรับ Past, Present, Future
        'อดีต' => [
            'meaning' => 'สิ่งที่ผ่านมาที่มีอิทธิพลต่อสถานการณ์ปัจจุบัน',
            'time' => 'อดีต',
            'focus' => 'รากฐาน ประสบการณ์ที่ผ่านมา และบทเรียน',
        ],
        'Past' => [
            'meaning' => 'สิ่งที่ผ่านมาที่มีอิทธิพลต่อสถานการณ์ปัจจุบัน',
            'time' => 'อดีต',
            'focus' => 'รากฐาน ประสบการณ์ที่ผ่านมา และบทเรียน',
        ],
        'ปัจจุบัน' => [
            'meaning' => 'สถานการณ์ที่เกิดขึ้นในขณะนี้',
            'time' => 'ปัจจุบัน',
            'focus' => 'สิ่งที่คุณกำลังเผชิญอยู่ตอนนี้',
        ],
        'Present' => [
            'meaning' => 'สถานการณ์ที่เกิดขึ้นในขณะนี้',
            'time' => 'ปัจจุบัน',
            'focus' => 'สิ่งที่คุณกำลังเผชิญอยู่ตอนนี้',
        ],
        'อนาคต' => [
            'meaning' => 'แนวโน้มและความเป็นไปได้ที่กำลังจะเกิดขึ้น',
            'time' => 'อนาคต',
            'focus' => 'ทิศทางและโอกาสที่รออยู่ข้างหน้า',
        ],
        'Future' => [
            'meaning' => 'แนวโน้มและความเป็นไปได้ที่กำลังจะเกิดขึ้น',
            'time' => 'อนาคต',
            'focus' => 'ทิศทางและโอกาสที่รออยู่ข้างหน้า',
        ],

        // สำหรับ Celtic Cross และอื่นๆ
        'อุปสรรค' => [
            'meaning' => 'สิ่งที่ท้าทายและขัดขวางเส้นทางของคุณ',
            'time' => 'ปัจจุบัน',
            'focus' => 'ปัญหาที่ต้องเผชิญและเอาชนะ',
        ],
        'Challenge' => [
            'meaning' => 'สิ่งที่ท้าทายและขัดขวางเส้นทางของคุณ',
            'time' => 'ปัจจุบัน',
            'focus' => 'ปัญหาที่ต้องเผชิญและเอาชนะ',
        ],
        'เป้าหมาย' => [
            'meaning' => 'สิ่งที่คุณมุ่งหวังและปรารถนา',
            'time' => 'อนาคต',
            'focus' => 'ความฝันและเป้าหมายสูงสุด',
        ],
        'Above' => [
            'meaning' => 'สิ่งที่คุณมุ่งหวังและปรารถนา',
            'time' => 'อนาคต',
            'focus' => 'ความฝันและเป้าหมายสูงสุด',
        ],
        'รากฐาน' => [
            'meaning' => 'พื้นฐานและรากเหง้าของสถานการณ์',
            'time' => 'อดีต',
            'focus' => 'สิ่งที่หล่อหลอมตัวตนและสถานการณ์',
        ],
        'Below' => [
            'meaning' => 'พื้นฐานและรากเหง้าของสถานการณ์',
            'time' => 'อดีต',
            'focus' => 'สิ่งที่หล่อหลอมตัวตนและสถานการณ์',
        ],
        'คำแนะนำ' => [
            'meaning' => 'แนวทางและสิ่งที่คุณควรทำ',
            'time' => 'ปัจจุบัน',
            'focus' => 'การกระทำที่แนะนำและทิศทางที่ควรไป',
        ],
        'Advice' => [
            'meaning' => 'แนวทางและสิ่งที่คุณควรทำ',
            'time' => 'ปัจจุบัน',
            'focus' => 'การกระทำที่แนะนำและทิศทางที่ควรไป',
        ],
        'ภายนอก' => [
            'meaning' => 'อิทธิพลจากสิ่งแวดล้อมและคนรอบข้าง',
            'time' => 'ปัจจุบัน',
            'focus' => 'ปัจจัยภายนอกที่ส่งผลต่อคุณ',
        ],
        'External' => [
            'meaning' => 'อิทธิพลจากสิ่งแวดล้อมและคนรอบข้าง',
            'time' => 'ปัจจุบัน',
            'focus' => 'ปัจจัยภายนอกที่ส่งผลต่อคุณ',
        ],
        'ความหวัง' => [
            'meaning' => 'ความหวังและความกลัวที่ซ่อนอยู่',
            'time' => 'ปัจจุบัน/อนาคต',
            'focus' => 'ความปรารถนาลึกๆ และความกังวล',
        ],
        'Hopes' => [
            'meaning' => 'ความหวังและความกลัวที่ซ่อนอยู่',
            'time' => 'ปัจจุบัน/อนาคต',
            'focus' => 'ความปรารถนาลึกๆ และความกังวล',
        ],
        'ผลลัพธ์' => [
            'meaning' => 'ผลลัพธ์สุดท้ายหากเส้นทางยังคงเป็นไปตามนี้',
            'time' => 'อนาคต',
            'focus' => 'บทสรุปและผลลัพธ์ที่คาดการณ์',
        ],
        'Outcome' => [
            'meaning' => 'ผลลัพธ์สุดท้ายหากเส้นทางยังคงเป็นไปตามนี้',
            'time' => 'อนาคต',
            'focus' => 'บทสรุปและผลลัพธ์ที่คาดการณ์',
        ],

        // สำหรับ Relationship Spread
        'คุณ' => [
            'meaning' => 'สถานะและพลังงานของตัวคุณในความสัมพันธ์',
            'time' => 'ปัจจุบัน',
            'focus' => 'ตัวตนและบทบาทของคุณ',
        ],
        'You' => [
            'meaning' => 'สถานะและพลังงานของตัวคุณในความสัมพันธ์',
            'time' => 'ปัจจุบัน',
            'focus' => 'ตัวตนและบทบาทของคุณ',
        ],
        'คู่ของคุณ' => [
            'meaning' => 'สถานะและพลังงานของคู่หรือคนที่คุณสนใจ',
            'time' => 'ปัจจุบัน',
            'focus' => 'ความรู้สึกและสถานะของอีกฝ่าย',
        ],
        'Partner' => [
            'meaning' => 'สถานะและพลังงานของคู่หรือคนที่คุณสนใจ',
            'time' => 'ปัจจุบัน',
            'focus' => 'ความรู้สึกและสถานะของอีกฝ่าย',
        ],
        'ความเชื่อมโยง' => [
            'meaning' => 'สิ่งที่เชื่อมโยงคุณทั้งสองเข้าด้วยกัน',
            'time' => 'ปัจจุบัน',
            'focus' => 'พันธะ ความผูกพัน และสิ่งที่มีร่วมกัน',
        ],
        'Connection' => [
            'meaning' => 'สิ่งที่เชื่อมโยงคุณทั้งสองเข้าด้วยกัน',
            'time' => 'ปัจจุบัน',
            'focus' => 'พันธะ ความผูกพัน และสิ่งที่มีร่วมกัน',
        ],

        // สำหรับ Career Path
        'จุดแข็ง' => [
            'meaning' => 'ความสามารถและจุดเด่นของคุณ',
            'time' => 'ปัจจุบัน',
            'focus' => 'ทักษะและพรสวรรค์ที่ควรใช้',
        ],
        'Strengths' => [
            'meaning' => 'ความสามารถและจุดเด่นของคุณ',
            'time' => 'ปัจจุบัน',
            'focus' => 'ทักษะและพรสวรรค์ที่ควรใช้',
        ],
        'Challenges' => [
            'meaning' => 'สิ่งท้าทายที่ต้องเอาชนะในเส้นทางอาชีพ',
            'time' => 'ปัจจุบัน/อนาคต',
            'focus' => 'อุปสรรคและโจทย์ที่ต้องแก้',
        ],
        'Current' => [
            'meaning' => 'สถานการณ์งานปัจจุบันของคุณ',
            'time' => 'ปัจจุบัน',
            'focus' => 'สถานะการทำงานในตอนนี้',
        ],
    ];

    /**
     * สร้างคำทำนายสำหรับการอ่านไพ่ทั้งชุด
     *
     * @param  TarotReading  $reading  การอ่านไพ่ที่ต้องการสร้างคำทำนาย
     */
    public function generateInterpretations(TarotReading $reading): void
    {
        // โหลดความสัมพันธ์ที่จำเป็น
        $reading->load(['category', 'spreadType', 'cards.card']);

        $categorySlug = $reading->category->slug ?? 'general';
        $categoryId = $reading->category_id;
        $categoryContext = $this->categoryContexts[$categorySlug] ?? $this->categoryContexts['general'];

        // สร้างคำทำนายสำหรับแต่ละไพ่
        foreach ($reading->cards as $readingCard) {
            $interpretation = $this->generateCardInterpretation(
                $readingCard,
                $categoryContext,
                $reading->question,
                $categoryId  // ส่ง categoryId เพื่อใช้ดึงคำทำนายที่กำหนดเอง
            );

            $readingCard->update(['interpretation' => $interpretation]);
        }

        // สร้างคำทำนายรวม
        $overallInterpretation = $this->generateOverallInterpretation(
            $reading,
            $categoryContext
        );

        $reading->update(['interpretation' => $overallInterpretation]);
    }

    /**
     * สร้างคำทำนายสำหรับไพ่แต่ละใบ
     *
     * ลำดับการใช้คำทำนาย:
     * 1. คำทำนายที่กำหนดเองจากฐานข้อมูล (TarotCardInterpretation)
     * 2. ถ้าไม่มี ใช้คำทำนายพื้นฐานจากไพ่ (TarotCard)
     * 3. ถ้าไม่มี ใช้คำทำนายที่สร้างอัตโนมัติ
     *
     * @param  TarotReadingCard  $readingCard  ไพ่ที่เลือก
     * @param  array  $categoryContext  บริบทของหมวดหมู่
     * @param  string|null  $question  คำถามของผู้ใช้
     * @param  int|null  $categoryId  ID ของหมวดหมู่
     * @return string คำทำนายละเอียด
     */
    protected function generateCardInterpretation(
        TarotReadingCard $readingCard,
        array $categoryContext,
        ?string $question,
        ?int $categoryId = null
    ): string {
        $card = $readingCard->card;
        $isReversed = $readingCard->is_reversed;
        $positionName = $readingCard->position_name;

        // ดึงบริบทตำแหน่ง
        $positionContext = $this->positionContexts[$positionName] ?? [
            'meaning' => 'ตำแหน่งนี้แสดงถึงพลังงานที่ส่งผลต่อคุณ',
            'time' => 'ปัจจุบัน',
            'focus' => 'พลังงานที่กำลังทำงานในชีวิตคุณ',
        ];

        // 1. ตรวจสอบคำทำนายที่กำหนดเองจากฐานข้อมูลก่อน
        $customInterpretation = $this->getCustomInterpretation($card->id, $categoryId, $isReversed);
        if (! empty($customInterpretation)) {
            return $this->buildInterpretationWithCustom(
                $customInterpretation,
                $positionName,
                $positionContext,
                $card->name_th ?? $card->name_en,
                $isReversed,
                $categoryId
            );
        }

        // 2. ดึงความหมายของไพ่จากตารางหลัก (TarotCard)
        $cardMeaning = $isReversed
            ? ($card->reversed_meaning_th ?? 'พลังงานที่ถูกปิดกั้นหรือต้องการความสนใจ')
            : ($card->upright_meaning_th ?? 'พลังงานที่ไหลลื่นและส่งเสริม');

        $cardName = $card->name_th ?? $card->name_en;
        $keywords = $card->keywords_th ?? $card->keywords_en ?? [];
        $orientationText = $isReversed ? 'กลับหัว' : 'หัวตั้ง';
        $orientationEmoji = $isReversed ? '🔄' : '✨';

        // 3. สร้างคำทำนายแบบละเอียดอัตโนมัติ
        $interpretation = $this->buildDetailedInterpretation(
            $cardName,
            $orientationText,
            $orientationEmoji,
            $positionName,
            $positionContext,
            $cardMeaning,
            $keywords,
            $categoryContext,
            $isReversed
        );

        return $interpretation;
    }

    /**
     * ดึงคำทำนายที่กำหนดเองจากฐานข้อมูล
     *
     * @param  int  $cardId  ID ของไพ่
     * @param  int|null  $categoryId  ID ของหมวดหมู่
     * @param  bool  $isReversed  ไพ่กลับหัวหรือไม่
     * @return string|null คำทำนายที่กำหนดเอง หรือ null ถ้าไม่มี
     */
    protected function getCustomInterpretation(int $cardId, ?int $categoryId, bool $isReversed): ?string
    {
        if (! $categoryId) {
            return null;
        }

        $customInterpretation = TarotCardInterpretation::findFor($cardId, $categoryId);

        if (! $customInterpretation) {
            return null;
        }

        // ดึงคำทำนายตามสถานะหงาย/กลับหัว
        $interpretation = $isReversed
            ? $customInterpretation->getReversedInterpretation('th')
            : $customInterpretation->getUprightInterpretation('th');

        return ! empty($interpretation) ? $interpretation : null;
    }

    /**
     * สร้างคำทำนายจากข้อมูลที่กำหนดเอง
     *
     * @param  string  $customInterpretation  คำทำนายที่กำหนดเอง
     * @param  string  $positionName  ชื่อตำแหน่ง
     * @param  array  $positionContext  บริบทตำแหน่ง
     * @param  string  $cardName  ชื่อไพ่
     * @param  bool  $isReversed  ไพ่กลับหัวหรือไม่
     * @param  int|null  $categoryId  ID ของหมวดหมู่
     */
    protected function buildInterpretationWithCustom(
        string $customInterpretation,
        string $positionName,
        array $positionContext,
        string $cardName,
        bool $isReversed,
        ?int $categoryId
    ): string {
        $lines = [];

        // ส่วนความหมาย
        $lines[] = "ความหมาย: {$customInterpretation}";

        // ส่วนตำแหน่ง
        $lines[] = '';
        $lines[] = "ในตำแหน่ง \"{$positionName}\" - {$positionContext['meaning']}";

        // คำแนะนำที่กำหนดเอง (ถ้ามี)
        if ($categoryId) {
            $customInterpretationModel = TarotCardInterpretation::where('card_id', function ($query) use ($cardName) {
                $query->select('id')
                    ->from('tarot_cards')
                    ->where('name_th', $cardName)
                    ->orWhere('name_en', $cardName)
                    ->limit(1);
            })->where('category_id', $categoryId)->first();

            if ($customInterpretationModel) {
                $advice = $customInterpretationModel->getAdvice('th');
                if (! empty($advice)) {
                    $lines[] = '';
                    $lines[] = "คำแนะนำ: {$advice}";
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * สร้างคำทำนายละเอียดสำหรับไพ่
     *
     * @param  string  $cardName  ชื่อไพ่
     * @param  string  $orientationText  ข้อความหงาย/กลับหัว
     * @param  string  $orientationEmoji  อีโมจิ
     * @param  string  $positionName  ชื่อตำแหน่ง
     * @param  array  $positionContext  บริบทตำแหน่ง
     * @param  string  $cardMeaning  ความหมายไพ่
     * @param  array  $keywords  คีย์เวิร์ด
     * @param  array  $categoryContext  บริบทหมวดหมู่
     * @param  bool  $isReversed  ไพ่กลับหัวหรือไม่
     */
    protected function buildDetailedInterpretation(
        string $cardName,
        string $orientationText,
        string $orientationEmoji,
        string $positionName,
        array $positionContext,
        string $cardMeaning,
        array $keywords,
        array $categoryContext,
        bool $isReversed
    ): string {
        $keywordString = ! empty($keywords) ? implode(', ', array_slice($keywords, 0, 3)) : '';

        // สร้างคำทำนายแบบอ่านง่าย (ไม่ใช้ markdown)
        $lines = [];

        // ส่วนความหมายของไพ่
        $lines[] = "ความหมาย: {$cardMeaning}";

        // ส่วนตำแหน่ง
        $lines[] = '';
        $lines[] = "ในตำแหน่ง \"{$positionName}\" - {$positionContext['meaning']}";

        // ส่วนการตีความตามบริบท
        $lines[] = '';
        if ($isReversed) {
            $lines[] = $this->getReversedContextualInterpretation(
                $cardName,
                $positionContext,
                $categoryContext,
                $keywordString
            );
        } else {
            $lines[] = $this->getUprightContextualInterpretation(
                $cardName,
                $positionContext,
                $categoryContext,
                $keywordString
            );
        }

        // ส่วนคำแนะนำ
        $lines[] = '';
        $lines[] = 'คำแนะนำ: '.$this->getAdvice($isReversed, $positionContext, $categoryContext);

        return implode("\n", $lines);
    }

    /**
     * สร้างคำทำนายตามบริบทสำหรับไพ่หัวตั้ง
     *
     * @param  string  $cardName  ชื่อไพ่
     * @param  array  $positionContext  บริบทตำแหน่ง
     * @param  array  $categoryContext  บริบทหมวดหมู่
     * @param  string  $keywords  คีย์เวิร์ด
     */
    protected function getUprightContextualInterpretation(
        string $cardName,
        array $positionContext,
        array $categoryContext,
        string $keywords
    ): string {
        $time = $positionContext['time'];
        $focus = $categoryContext['focus'];
        $energy = $categoryContext['energy'];

        $interpretations = [
            'อดีต' => "ไพ่ {$cardName} ในตำแหน่งอดีตบ่งบอกว่าคุณมีรากฐานที่ดีใน{$focus} ประสบการณ์ที่ผ่านมาได้หล่อหลอมให้คุณมีพลังของ{$keywords} พื้นฐานนี้เป็นจุดเริ่มต้นที่ดีสำหรับเส้นทางข้างหน้า",

            'ปัจจุบัน' => "ตอนนี้พลังของไพ่ {$cardName} กำลังทำงานอยู่ในชีวิตคุณ คุณอยู่ในช่วงที่{$energy}กำลังส่งเสริม พลังของ{$keywords}กำลังช่วยเหลือคุณในเรื่อง{$focus} นี่เป็นเวลาที่ดีในการใช้พลังงานนี้ให้เป็นประโยชน์",

            'อนาคต' => "ไพ่ {$cardName} บ่งบอกว่าอนาคตของคุณใน{$focus}มีแนวโน้มที่ดี พลังของ{$keywords}กำลังจะมาถึง เตรียมตัวรับโอกาสและความเปลี่ยนแปลงที่ดี",

            'ปัจจุบัน/อนาคต' => "ไพ่ {$cardName} แสดงให้เห็นว่าพลังของ{$keywords}กำลังส่งผลต่อทั้งปัจจุบันและอนาคตของคุณใน{$focus} นี่เป็นพลังที่ต่อเนื่องและยั่งยืน",
        ];

        return $interpretations[$time] ?? "ไพ่ {$cardName} นำพลังของ{$keywords}มาสู่{$focus}ของคุณ พลังงานนี้ส่งเสริมและช่วยเหลือให้คุณก้าวไปข้างหน้า";
    }

    /**
     * สร้างคำทำนายตามบริบทสำหรับไพ่กลับหัว
     *
     * @param  string  $cardName  ชื่อไพ่
     * @param  array  $positionContext  บริบทตำแหน่ง
     * @param  array  $categoryContext  บริบทหมวดหมู่
     * @param  string  $keywords  คีย์เวิร์ด
     */
    protected function getReversedContextualInterpretation(
        string $cardName,
        array $positionContext,
        array $categoryContext,
        string $keywords
    ): string {
        $time = $positionContext['time'];
        $focus = $categoryContext['focus'];
        $energy = $categoryContext['energy'];

        $interpretations = [
            'อดีต' => "ไพ่ {$cardName} กลับหัวในตำแหน่งอดีตชี้ให้เห็นว่ามีบางสิ่งจากอดีตที่ยังไม่ได้รับการแก้ไขใน{$focus} พลังของ{$keywords}อาจถูกปิดกั้นหรือใช้ไปในทางที่ไม่เหมาะสม สิ่งนี้อาจยังส่งผลต่อปัจจุบัน",

            'ปัจจุบัน' => "ตอนนี้พลังของไพ่ {$cardName} กำลังถูกท้าทายหรือปิดกั้น คุณอาจรู้สึกว่า{$energy}ไม่ไหลลื่นในเรื่อง{$focus} พลังของ{$keywords}ต้องการความสนใจและการปรับสมดุล นี่เป็นเวลาที่ต้องหยุดคิดทบทวน",

            'อนาคต' => "ไพ่ {$cardName} กลับหัวเตือนว่าอาจมีอุปสรรคหรือความล่าช้าใน{$focus} พลังของ{$keywords}อาจต้องการการปลดปล่อยหรือปรับทิศทาง อย่างไรก็ตาม อุปสรรคนี้สามารถเอาชนะได้ด้วยความเข้าใจและการเตรียมพร้อม",

            'ปัจจุบัน/อนาคต' => "ไพ่ {$cardName} กลับหัวบ่งบอกว่าพลังของ{$keywords}กำลังถูกปิดกั้นทั้งในปัจจุบันและส่งผลต่ออนาคตใน{$focus} ต้องการการปลดปล่อยพลังงานที่ติดขัด",
        ];

        return $interpretations[$time] ?? "ไพ่ {$cardName} กลับหัวชี้ให้เห็นว่าพลังของ{$keywords}กำลังถูกปิดกั้นหรือต้องการความสนใจใน{$focus} นี่เป็นโอกาสในการทบทวนและปรับสมดุล";
    }

    /**
     * สร้างคำแนะนำตามไพ่
     *
     * @param  bool  $isReversed  ไพ่กลับหัวหรือไม่
     * @param  array  $positionContext  บริบทตำแหน่ง
     * @param  array  $categoryContext  บริบทหมวดหมู่
     */
    protected function getAdvice(bool $isReversed, array $positionContext, array $categoryContext): string
    {
        $focus = $categoryContext['focus'];
        $time = $positionContext['time'];

        if ($isReversed) {
            $advice = [
                'อดีต' => "หันกลับไปพิจารณาประสบการณ์ใน{$focus}ที่ผ่านมา อาจมีบทเรียนที่ยังไม่ได้เรียนรู้ การให้อภัยตัวเองและผู้อื่นจะช่วยปลดปล่อยพลังงานที่ติดขัด",
                'ปัจจุบัน' => "หยุดพักและทบทวนสถานการณ์{$focus}ในตอนนี้ อย่าเร่งรีบตัดสินใจ ค่อยๆ คลี่คลายปัญหาทีละขั้น รับฟังเสียงภายในของตัวเอง",
                'อนาคต' => "เตรียมใจรับมือกับความท้าทายที่อาจเกิดขึ้นใน{$focus} สร้างแผนสำรอง อย่าหมดกำลังใจ อุปสรรคคือโอกาสในการเติบโต",
            ];
        } else {
            $advice = [
                'อดีต' => "ชื่นชมและนำบทเรียนจากอดีตใน{$focus}มาใช้ รากฐานที่ดีจะช่วยให้คุณก้าวไปข้างหน้าได้อย่างมั่นคง",
                'ปัจจุบัน' => "ใช้พลังงานดีๆ ที่กำลังมาถึงใน{$focus}ให้เป็นประโยชน์ นี่คือเวลาที่ดีในการลงมือทำและไขว่คว้าโอกาส",
                'อนาคต' => "มองไปข้างหน้าด้วยความหวังใน{$focus} โอกาสดีๆ กำลังจะมาถึง เตรียมตัวให้พร้อมเพื่อรับสิ่งดีๆ",
            ];
        }

        return $advice[$time] ?? ($isReversed
            ? "ใช้เวลานี้ในการทบทวนและปรับสมดุลใน{$focus} ความท้าทายนำมาซึ่งโอกาสในการเติบโต"
            : "เปิดใจรับพลังงานดีๆ ที่กำลังมาถึงใน{$focus} และใช้มันให้เป็นประโยชน์สูงสุด");
    }

    /**
     * สร้างคำทำนายรวมสำหรับการอ่านทั้งชุด
     *
     * @param  TarotReading  $reading  การอ่านไพ่
     * @param  array  $categoryContext  บริบทหมวดหมู่
     */
    protected function generateOverallInterpretation(TarotReading $reading, array $categoryContext): string
    {
        $cards = $reading->cards;
        $categoryName = $reading->category->name_th ?? 'การทำนายทั่วไป';
        $spreadTypeName = $reading->spreadType->name_th ?? 'การเปิดไพ่';
        $focus = $categoryContext['focus'];

        // นับไพ่หัวตั้งและกลับหัว
        $uprightCount = $cards->where('is_reversed', false)->count();
        $reversedCount = $cards->where('is_reversed', true)->count();
        $totalCards = $cards->count();

        // วิเคราะห์พลังงานโดยรวม
        $energyAnalysis = $this->analyzeOverallEnergy($uprightCount, $reversedCount, $totalCards);

        // รวบรวมคีย์เวิร์ดจากทุกไพ่
        $allKeywords = [];
        foreach ($cards as $readingCard) {
            $keywords = $readingCard->card->keywords_th ?? [];
            $allKeywords = array_merge($allKeywords, $keywords);
        }
        $topKeywords = array_slice(array_unique($allKeywords), 0, 5);
        $keywordString = implode(', ', $topKeywords);

        // สร้างบทสรุปแบบอ่านง่าย (ไม่ใช้ markdown)
        $lines = [];

        $lines[] = "พลังงานโดยรวม: {$energyAnalysis['description_clean']}";
        $lines[] = '';
        $lines[] = 'การวิเคราะห์:';
        $lines[] = $this->buildOverallAnalysis($cards, $categoryContext, $energyAnalysis);
        $lines[] = '';
        $lines[] = 'คำแนะนำ: '.$this->getOverallAdvice($energyAnalysis, $categoryContext);
        $lines[] = '';
        $lines[] = 'ข้อคิด: '.$this->getInspirationMessage($energyAnalysis, $focus);

        // คำถามของผู้ใช้ (ถ้ามี)
        if (! empty($reading->question)) {
            $lines[] = '';
            $lines[] = "คำถามของคุณ: \"{$reading->question}\"";
            $lines[] = 'คำตอบ: '.$this->answerQuestion($reading->question, $cards, $categoryContext, $energyAnalysis);
        }

        return implode("\n", $lines);
    }

    /**
     * วิเคราะห์พลังงานโดยรวมจากไพ่ทั้งหมด
     *
     * @param  int  $uprightCount  จำนวนไพ่หัวตั้ง
     * @param  int  $reversedCount  จำนวนไพ่กลับหัว
     * @param  int  $totalCards  จำนวนไพ่ทั้งหมด
     */
    protected function analyzeOverallEnergy(int $uprightCount, int $reversedCount, int $totalCards): array
    {
        $uprightRatio = $totalCards > 0 ? $uprightCount / $totalCards : 0;

        if ($uprightRatio >= 0.8) {
            return [
                'type' => 'very_positive',
                'description' => 'พลังงานบวกสูงมาก - เส้นทางกำลังเปิดกว้าง',
                'description_clean' => 'พลังงานบวกสูงมาก เส้นทางกำลังเปิดกว้าง',
                'level' => 5,
                'advice_tone' => 'optimistic',
            ];
        } elseif ($uprightRatio >= 0.6) {
            return [
                'type' => 'positive',
                'description' => 'พลังงานบวก - สถานการณ์เอื้ออำนวย',
                'description_clean' => 'พลังงานบวก สถานการณ์เอื้ออำนวย',
                'level' => 4,
                'advice_tone' => 'encouraging',
            ];
        } elseif ($uprightRatio >= 0.4) {
            return [
                'type' => 'balanced',
                'description' => 'พลังงานสมดุล - มีทั้งโอกาสและความท้าทาย',
                'description_clean' => 'พลังงานสมดุล มีทั้งโอกาสและความท้าทาย',
                'level' => 3,
                'advice_tone' => 'balanced',
            ];
        } elseif ($uprightRatio >= 0.2) {
            return [
                'type' => 'challenging',
                'description' => 'พลังงานท้าทาย - ต้องการความพยายามเพิ่มเติม',
                'description_clean' => 'พลังงานท้าทาย ต้องการความพยายามเพิ่มเติม',
                'level' => 2,
                'advice_tone' => 'supportive',
            ];
        } else {
            return [
                'type' => 'transformative',
                'description' => 'พลังงานแห่งการเปลี่ยนแปลง - โอกาสในการเติบโตครั้งใหญ่',
                'description_clean' => 'พลังงานแห่งการเปลี่ยนแปลง โอกาสในการเติบโตครั้งใหญ่',
                'level' => 1,
                'advice_tone' => 'transformative',
            ];
        }
    }

    /**
     * สร้างการวิเคราะห์โดยรวมจากไพ่ทั้งหมด
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $cards  ไพ่ทั้งหมด
     * @param  array  $categoryContext  บริบทหมวดหมู่
     * @param  array  $energyAnalysis  การวิเคราะห์พลังงาน
     */
    protected function buildOverallAnalysis($cards, array $categoryContext, array $energyAnalysis): string
    {
        $focus = $categoryContext['focus'];
        $level = $energyAnalysis['level'];
        $type = $energyAnalysis['type'];

        // ดึงไพ่สำคัญ
        $firstCard = $cards->first();
        $lastCard = $cards->last();

        $firstCardName = $firstCard?->card?->name_th ?? 'ไพ่แรก';
        $lastCardName = $lastCard?->card?->name_th ?? 'ไพ่สุดท้าย';
        $firstReversed = $firstCard?->is_reversed ? ' (กลับหัว)' : '';
        $lastReversed = $lastCard?->is_reversed ? ' (กลับหัว)' : '';

        $analysis = '';

        switch ($type) {
            case 'very_positive':
                $analysis = "การทำนายครั้งนี้แสดงพลังงานที่ดีเยี่ยมสำหรับ{$focus}ของคุณ ไพ่{$firstCardName}{$firstReversed}ในตำแหน่งแรกเป็นสัญญาณที่ดีมาก บ่งบอกว่าพื้นฐานและจุดเริ่มต้นของคุณมั่นคง\n\n";
                $analysis .= "พลังงานโดยรวมไหลลื่นและสนับสนุน นี่เป็นช่วงเวลาที่ดีในการไขว่คว้าโอกาส โดยเฉพาะในด้าน{$focus} ไพ่{$lastCardName}{$lastReversed}ในตำแหน่งสุดท้ายชี้ว่าผลลัพธ์มีแนวโน้มที่ดี";
                break;

            case 'positive':
                $analysis = "การทำนายครั้งนี้แสดงพลังงานบวกโดยรวมสำหรับ{$focus} แม้อาจมีความท้าทายเล็กน้อย แต่โดยรวมเส้นทางกำลังเปิดกว้าง\n\n";
                $analysis .= "ไพ่{$firstCardName}{$firstReversed}บอกเล่าถึงจุดเริ่มต้นที่ดี และไพ่{$lastCardName}{$lastReversed}ชี้ให้เห็นทิศทางที่จะไป พลังงานโดยรวมสนับสนุนความสำเร็จ";
                break;

            case 'balanced':
                $analysis = "การทำนายครั้งนี้แสดงภาพที่สมดุลสำหรับ{$focus} มีทั้งโอกาสและความท้าทายในสัดส่วนที่ใกล้เคียงกัน\n\n";
                $analysis .= "ไพ่{$firstCardName}{$firstReversed}บ่งบอกถึงสถานการณ์เริ่มต้น ขณะที่ไพ่{$lastCardName}{$lastReversed}ชี้ให้เห็นว่าผลลัพธ์ขึ้นอยู่กับการตัดสินใจและการกระทำของคุณ นี่เป็นเวลาที่ต้องใช้วิจารณญาณและความสมดุล";
                break;

            case 'challenging':
                $analysis = "การทำนายครั้งนี้บ่งบอกว่า{$focus}กำลังเผชิญกับความท้าทายบางประการ แต่นี่ไม่ใช่สิ่งที่เลวร้าย - ความท้าทายคือโอกาสในการเติบโต\n\n";
                $analysis .= "ไพ่{$firstCardName}{$firstReversed}ชี้ให้เห็นจุดที่ต้องการความสนใจ และไพ่{$lastCardName}{$lastReversed}บอกว่าการเอาชนะอุปสรรคเหล่านี้จะนำมาซึ่งการเติบโตครั้งสำคัญ";
                break;

            case 'transformative':
                $analysis = "การทำนายครั้งนี้บ่งบอกถึงช่วงเวลาแห่งการเปลี่ยนแปลงครั้งใหญ่ใน{$focus} ไพ่หลายใบปรากฏในทิศทางกลับหัว ซึ่งหมายความว่าพลังงานกำลังถูกปรับเปลี่ยน\n\n";
                $analysis .= "ไพ่{$firstCardName}{$firstReversed}บอกว่าการเปลี่ยนแปลงเริ่มต้นแล้ว และไพ่{$lastCardName}{$lastReversed}ชี้ว่าการยอมรับการเปลี่ยนแปลงจะนำไปสู่การเกิดใหม่ที่ดีกว่า ปล่อยวางสิ่งเก่าเพื่อต้อนรับสิ่งใหม่";
                break;
        }

        return $analysis;
    }

    /**
     * สร้างคำแนะนำโดยรวม
     *
     * @param  array  $energyAnalysis  การวิเคราะห์พลังงาน
     * @param  array  $categoryContext  บริบทหมวดหมู่
     */
    protected function getOverallAdvice(array $energyAnalysis, array $categoryContext): string
    {
        $focus = $categoryContext['focus'];
        $tone = $energyAnalysis['advice_tone'];

        $advices = [
            'optimistic' => "นี่เป็นเวลาที่ดีเยี่ยมสำหรับ{$focus}! ไขว่คว้าโอกาสที่มาถึง ความมั่นใจและการกระทำเชิงบวกจะนำคุณไปสู่ความสำเร็จ เชื่อมั่นในตัวเองและก้าวไปข้างหน้า",

            'encouraging' => "สถานการณ์{$focus}เอื้ออำนวยให้คุณก้าวต่อไป อย่าลังเลที่จะลงมือทำ แม้จะมีความท้าทายเล็กน้อย แต่พลังงานโดยรวมสนับสนุนคุณ",

            'balanced' => "ใช้วิจารณญาณและความสมดุลในการตัดสินใจเรื่อง{$focus} พิจารณาทั้งโอกาสและความเสี่ยง อย่ารีบร้อน แต่อย่ารอนานเกินไป หาจุดสมดุลที่เหมาะกับตัวคุณ",

            'supportive' => "แม้{$focus}จะมีความท้าทาย แต่อย่าหมดกำลังใจ ทุกอุปสรรคล้วนมีบทเรียน ค่อยๆ แก้ปัญหาทีละขั้น ขอความช่วยเหลือเมื่อต้องการ และเชื่อมั่นว่าคุณผ่านมันไปได้",

            'transformative' => "นี่คือช่วงเวลาแห่งการเปลี่ยนแปลงครั้งใหญ่ใน{$focus} อย่าต่อต้านการเปลี่ยนแปลง ปล่อยวางสิ่งที่ไม่รับใช้คุณอีกต่อไป การสิ้นสุดนำมาซึ่งการเริ่มต้นใหม่ที่ดีกว่า",
        ];

        return $advices[$tone] ?? $advices['balanced'];
    }

    /**
     * สร้างข้อความสร้างแรงบันดาลใจ
     *
     * @param  array  $energyAnalysis  การวิเคราะห์พลังงาน
     * @param  string  $focus  โฟกัสของการทำนาย
     */
    protected function getInspirationMessage(array $energyAnalysis, string $focus): string
    {
        $level = $energyAnalysis['level'];

        $inspirations = [
            5 => "จงเป็นแสงสว่างที่คุณต้องการเห็นในโลก พลังงานรอบตัวคุณสว่างไสว {$focus}ของคุณกำลังเบ่งบาน",
            4 => "ทุกก้าวเล็กๆ นำไปสู่การเดินทางที่ยิ่งใหญ่ คุณกำลังก้าวไปในทิศทางที่ถูกต้องใน{$focus}",
            3 => "ความสมดุลคือกุญแจสู่ความสุข รักษาสมดุลใน{$focus}และชีวิตของคุณจะราบรื่น",
            2 => "ดอกบัวงดงามที่สุดเบ่งบานจากโคลนตม ความท้าทายใน{$focus}คือโอกาสให้คุณเติบโต",
            1 => "การเปลี่ยนแปลงคือธรรมชาติของชีวิต ปล่อยวางและต้อนรับ{$focus}ในรูปแบบใหม่ที่ดีกว่า",
        ];

        return $inspirations[$level] ?? $inspirations[3];
    }

    /**
     * ตอบคำถามของผู้ใช้ตามไพ่ที่ได้
     *
     * @param  string  $question  คำถาม
     * @param  \Illuminate\Database\Eloquent\Collection  $cards  ไพ่
     * @param  array  $categoryContext  บริบทหมวดหมู่
     * @param  array  $energyAnalysis  การวิเคราะห์พลังงาน
     */
    protected function answerQuestion(string $question, $cards, array $categoryContext, array $energyAnalysis): string
    {
        $focus = $categoryContext['focus'];
        $type = $energyAnalysis['type'];

        // ดึงคีย์เวิร์ดจากไพ่สำคัญ
        $mainCard = $cards->first();
        $mainCardName = $mainCard?->card?->name_th ?? 'ไพ่';
        $isMainReversed = $mainCard?->is_reversed ?? false;

        $answerTemplates = [
            'very_positive' => "จากไพ่ที่ปรากฏ คำตอบสำหรับคำถามของคุณค่อนข้างชัดเจนในทางบวก ไพ่{$mainCardName}บ่งบอกว่าสิ่งที่คุณถามเกี่ยวกับ{$focus}มีแนวโน้มที่ดีมาก จักรวาลกำลังสนับสนุนคุณ",

            'positive' => "ไพ่ให้คำตอบในเชิงบวกสำหรับคำถามของคุณ ไพ่{$mainCardName}ชี้ว่าเส้นทางใน{$focus}กำลังเปิดกว้าง แม้อาจต้องใช้ความพยายาม แต่ผลลัพธ์จะคุ้มค่า",

            'balanced' => "ไพ่ให้คำตอบที่ต้องพิจารณาหลายด้าน ไพ่{$mainCardName}บอกว่า{$focus}ขึ้นอยู่กับการตัดสินใจและการกระทำของคุณ มีทั้งโอกาสและความท้าทาย เลือกอย่างรอบคอบ",

            'challenging' => "ไพ่บอกว่าคำถามของคุณเกี่ยวกับ{$focus}อาจเผชิญกับอุปสรรคบางอย่าง ไพ่{$mainCardName}เตือนให้ระวังและเตรียมพร้อม แต่ไม่ได้หมายความว่าเป็นไปไม่ได้ - เพียงต้องใช้ความพยายามมากขึ้น",

            'transformative' => "ไพ่บอกว่าคำถามของคุณเกี่ยวกับ{$focus}กำลังอยู่ในช่วงเปลี่ยนผ่าน ไพ่{$mainCardName}ชี้ว่าคำตอบที่แท้จริงอาจไม่ใช่สิ่งที่คุณคาดหวัง แต่จะเป็นสิ่งที่ดีกว่า เปิดใจรับความเป็นไปได้ใหม่ๆ",
        ];

        return $answerTemplates[$type] ?? $answerTemplates['balanced'];
    }
}
