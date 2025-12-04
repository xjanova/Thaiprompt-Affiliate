<?php

namespace Database\Seeders;

use App\Models\Slogan;
use Illuminate\Database\Seeder;

/**
 * Seeder สำหรับคำขวัญ/คำคม
 *
 * คำคมเกี่ยวกับ TP-Affiliate, ธุรกิจ, ความสำเร็จ, และชีวิต
 */
class SloganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌟 กำลัง seed คำขวัญ...');

        // ลบข้อมูลเก่า
        Slogan::truncate();

        $slogans = $this->getSlogans();

        foreach ($slogans as $slogan) {
            Slogan::create($slogan);
        }

        $this->command->info('✅ Seed คำขวัญสำเร็จ! (' . count($slogans) . ' คำ)');
    }

    /**
     * รายการคำขวัญทั้งหมด
     */
    private function getSlogans(): array
    {
        return array_merge(
            $this->getThaiSpiritSlogans(),
            $this->getBusinessSlogans(),
            $this->getMotivationSlogans(),
            $this->getSuccessSlogans(),
            $this->getTeamworkSlogans(),
            $this->getAffiliateSlogans(),
            $this->getEcommerceSlogans(),
            $this->getLifeSlogans(),
            $this->getWisdomSlogans(),
            $this->getTPAffiliateSlogans()
        );
    }

    /**
     * คำขวัญจิตวิญญาณไทย
     */
    private function getThaiSpiritSlogans(): array
    {
        $slogans = [
            ['content' => 'เราช่วยคุณ คุณช่วยเรา ไทยช่วยไทย', 'icon' => '🇹🇭', 'priority' => 5],
            ['content' => 'คนไทยไม่ทิ้งกัน ร่วมมือร่วมใจ ก้าวไปด้วยกัน', 'icon' => '🤝', 'priority' => 5],
            ['content' => 'ความสามัคคี คือพลังของคนไทย', 'icon' => '💪', 'priority' => 4],
            ['content' => 'น้ำใจไทย ไม่มีวันเหือดแห้ง', 'icon' => '💝', 'priority' => 4],
            ['content' => 'รักษาความเป็นไทย พัฒนาสู่สากล', 'icon' => '🌏', 'priority' => 3],
            ['content' => 'สินค้าไทย คนไทยต้องช่วยกัน', 'icon' => '🛒', 'priority' => 4],
            ['content' => 'ซื้อของไทย ใช้ของไทย หนุนคนไทย', 'icon' => '🇹🇭', 'priority' => 4],
            ['content' => 'เศรษฐกิจไทย เราสร้างได้ด้วยมือเรา', 'icon' => '🏗️', 'priority' => 3],
            ['content' => 'ความกตัญญู คือรากฐานของความสำเร็จ', 'icon' => '🙏', 'priority' => 3],
            ['content' => 'เอื้อเฟื้อเผื่อแผ่ แบ่งปันกันและกัน', 'icon' => '❤️', 'priority' => 3],
            ['content' => 'พอเพียง พอประมาณ มีเหตุผล', 'icon' => '⚖️', 'priority' => 4],
            ['content' => 'ทำดีได้ดี ทำชั่วได้ชั่ว กฎแห่งกรรม', 'icon' => '☸️', 'priority' => 3],
            ['content' => 'รู้รักสามัคคี สามัคคีคือพลัง', 'icon' => '💪', 'priority' => 4],
            ['content' => 'ไทยเข้มแข็ง เพราะคนไทยร่วมใจ', 'icon' => '🇹🇭', 'priority' => 3],
            ['content' => 'ภูมิใจในความเป็นไทย สร้างสรรค์สิ่งดีๆ', 'icon' => '✨', 'priority' => 3],
            ['content' => 'รอยยิ้มสยาม มนต์เสน่ห์แห่งความสุข', 'icon' => '😊', 'priority' => 3],
            ['content' => 'ประเทศไทย ไม่ใช่ประเทศของใครคนเดียว', 'icon' => '🇹🇭', 'priority' => 3],
            ['content' => 'ร่วมแรงร่วมใจ ไทยไม่แพ้ใคร', 'icon' => '💪', 'priority' => 4],
            ['content' => 'หยิ่งในศักดิ์ศรี มีน้ำใจไทย', 'icon' => '👑', 'priority' => 3],
            ['content' => 'สืบสานวัฒนธรรม นำพาชาติก้าวไกล', 'icon' => '🎭', 'priority' => 3],
        ];

        return array_map(fn($s) => array_merge($s, ['category' => 'thai_spirit']), $slogans);
    }

    /**
     * คำขวัญธุรกิจ
     */
    private function getBusinessSlogans(): array
    {
        $slogans = [
            ['content' => 'ธุรกิจที่ดี คือธุรกิจที่ทุกฝ่ายได้ประโยชน์', 'icon' => '🤝', 'priority' => 4],
            ['content' => 'ลูกค้าคือหัวใจ บริการคือชีวิต', 'icon' => '❤️', 'priority' => 4],
            ['content' => 'คุณภาพสร้างความเชื่อมั่น ความเชื่อมั่นสร้างความสำเร็จ', 'icon' => '⭐', 'priority' => 4],
            ['content' => 'ซื่อสัตย์ต่อลูกค้า คือการลงทุนที่คุ้มค่าที่สุด', 'icon' => '💎', 'priority' => 4],
            ['content' => 'ทำธุรกิจด้วยใจ ไม่ใช่แค่เพื่อกำไร', 'icon' => '💝', 'priority' => 3],
            ['content' => 'เงินทองเป็นของนอกกาย แต่ชื่อเสียงอยู่กับเราตลอดไป', 'icon' => '👑', 'priority' => 3],
            ['content' => 'ขายของดี พูดจริง ทำจริง', 'icon' => '✅', 'priority' => 4],
            ['content' => 'ลูกค้าประจำ มาจากการบริการที่ประทับใจ', 'icon' => '🌟', 'priority' => 3],
            ['content' => 'ทุกปัญหามีทางออก ทุกวิกฤตมีโอกาส', 'icon' => '🔓', 'priority' => 4],
            ['content' => 'เริ่มต้นจากเล็กๆ ค่อยๆ เติบโตอย่างมั่นคง', 'icon' => '🌱', 'priority' => 3],
            ['content' => 'ไม่มีธุรกิจใดสำเร็จได้โดยไม่ต้องเหนื่อย', 'icon' => '💪', 'priority' => 3],
            ['content' => 'ความอดทน คือกุญแจสู่ความสำเร็จ', 'icon' => '🔑', 'priority' => 4],
            ['content' => 'ลงทุนในตัวเอง ก่อนลงทุนในธุรกิจ', 'icon' => '📚', 'priority' => 3],
            ['content' => 'รู้จักลูกค้า รู้จักตลาด รู้จักตัวเอง', 'icon' => '🎯', 'priority' => 3],
            ['content' => 'ขายปัญหาของลูกค้า ไม่ใช่ขายสินค้าของเรา', 'icon' => '💡', 'priority' => 4],
            ['content' => 'ทำให้ลูกค้ารู้สึกพิเศษ เพราะเขาคือคนพิเศษ', 'icon' => '✨', 'priority' => 3],
            ['content' => 'ราคาถูก ไม่ได้แปลว่าคุณภาพต่ำ', 'icon' => '💰', 'priority' => 3],
            ['content' => 'สร้างคุณค่า ไม่ใช่แค่สร้างราคา', 'icon' => '💎', 'priority' => 4],
            ['content' => 'ธุรกิจที่ยั่งยืน คือธุรกิจที่มีจริยธรรม', 'icon' => '🌿', 'priority' => 3],
            ['content' => 'ก้าวทีละก้าว แต่ก้าวให้มั่น', 'icon' => '👣', 'priority' => 3],
            ['content' => 'ความสำเร็จของลูกค้า คือความสำเร็จของเรา', 'icon' => '🏆', 'priority' => 4],
            ['content' => 'ทำธุรกิจเหมือนทำบ้าน สร้างให้แข็งแรงจากฐานราก', 'icon' => '🏠', 'priority' => 3],
            ['content' => 'ขยัน อดทน ซื่อสัตย์ สู่ความสำเร็จ', 'icon' => '🚀', 'priority' => 4],
            ['content' => 'ทุกการขายคือการสร้างมิตรภาพ', 'icon' => '🤝', 'priority' => 3],
            ['content' => 'มองหาโอกาส ไม่ใช่มองหาข้อแก้ตัว', 'icon' => '👀', 'priority' => 4],
        ];

        return array_map(fn($s) => array_merge($s, ['category' => 'business']), $slogans);
    }

    /**
     * คำขวัญแรงบันดาลใจ
     */
    private function getMotivationSlogans(): array
    {
        $slogans = [
            ['content' => 'วันนี้ยากลำบาก พรุ่งนี้จะง่ายขึ้น', 'icon' => '🌅', 'priority' => 4],
            ['content' => 'ล้มได้ แต่ต้องลุกให้ไว', 'icon' => '💪', 'priority' => 5],
            ['content' => 'ความพยายามไม่เคยทรยศใคร', 'icon' => '🔥', 'priority' => 5],
            ['content' => 'ถ้าไม่เริ่มวันนี้ จะสำเร็จวันไหน', 'icon' => '⏰', 'priority' => 4],
            ['content' => 'ความฝันจะเป็นจริงได้ ถ้าลงมือทำ', 'icon' => '✨', 'priority' => 4],
            ['content' => 'อย่าหยุดเดิน แม้จะเหนื่อยแค่ไหน', 'icon' => '🚶', 'priority' => 4],
            ['content' => 'ทุกความสำเร็จ เริ่มจากการตัดสินใจ', 'icon' => '🎯', 'priority' => 4],
            ['content' => 'เชื่อในตัวเอง แม้ไม่มีใครเชื่อ', 'icon' => '💫', 'priority' => 4],
            ['content' => 'ความกลัวคืออุปสรรค ความกล้าคือทางสู่เป้าหมาย', 'icon' => '🦁', 'priority' => 3],
            ['content' => 'ทุกวันคือโอกาสใหม่ในการเริ่มต้น', 'icon' => '🌟', 'priority' => 4],
            ['content' => 'ไม่มีคำว่าสายเกินไป สำหรับคนที่ต้องการเปลี่ยนแปลง', 'icon' => '🔄', 'priority' => 4],
            ['content' => 'ความล้มเหลวคือบทเรียน ไม่ใช่จุดจบ', 'icon' => '📖', 'priority' => 4],
            ['content' => 'ทำวันนี้ให้ดีที่สุด พรุ่งนี้จะดีกว่า', 'icon' => '⭐', 'priority' => 4],
            ['content' => 'อนาคตสร้างได้ ด้วยสิ่งที่ทำวันนี้', 'icon' => '🏗️', 'priority' => 3],
            ['content' => 'ปัญหามีไว้แก้ ไม่ใช่มีไว้กลัว', 'icon' => '🔧', 'priority' => 4],
            ['content' => 'ความสำเร็จไม่ได้มาจากโชค แต่มาจากความพยายาม', 'icon' => '🍀', 'priority' => 4],
            ['content' => 'ยิ่งพยายาม ยิ่งโชคดี', 'icon' => '🎰', 'priority' => 3],
            ['content' => 'อดทนวันนี้ เพื่อพรุ่งนี้ที่ดีกว่า', 'icon' => '💎', 'priority' => 4],
            ['content' => 'ความพ่ายแพ้ คือจุดเริ่มต้นของชัยชนะ', 'icon' => '🏆', 'priority' => 3],
            ['content' => 'ไม่ต้องเก่งที่สุด แค่ดีที่สุดในแบบของเรา', 'icon' => '👍', 'priority' => 4],
            ['content' => 'ทุกก้าวเล็กๆ รวมกันเป็นการเดินทางที่ยิ่งใหญ่', 'icon' => '👣', 'priority' => 3],
            ['content' => 'อย่าเปรียบเทียบตัวเองกับคนอื่น เปรียบเทียบกับเมื่อวาน', 'icon' => '📈', 'priority' => 4],
            ['content' => 'ความสำเร็จคือการไม่ยอมแพ้', 'icon' => '🏅', 'priority' => 4],
            ['content' => 'ลุกขึ้นมา แม้จะล้มกี่ครั้งก็ตาม', 'icon' => '🦋', 'priority' => 4],
            ['content' => 'วันนี้คุณอาจเหนื่อย แต่พรุ่งนี้คุณจะภูมิใจ', 'icon' => '🌈', 'priority' => 4],
            ['content' => 'ทุกความยากลำบาก ล้วนมีบทเรียนซ่อนอยู่', 'icon' => '📚', 'priority' => 3],
            ['content' => 'หากไม่ลอง ก็ไม่มีวันรู้ว่าทำได้', 'icon' => '🎲', 'priority' => 4],
            ['content' => 'ความกลัวจะหายไป เมื่อคุณลงมือทำ', 'icon' => '⚡', 'priority' => 3],
            ['content' => 'ทำทุกวันเหมือนเป็นวันสุดท้าย', 'icon' => '🔥', 'priority' => 3],
            ['content' => 'ชีวิตไม่ได้มีไว้รอ แต่มีไว้ลงมือทำ', 'icon' => '🚀', 'priority' => 4],
        ];

        return array_map(fn($s) => array_merge($s, ['category' => 'motivation']), $slogans);
    }

    /**
     * คำขวัญความสำเร็จ
     */
    private function getSuccessSlogans(): array
    {
        $slogans = [
            ['content' => 'ความสำเร็จไม่ใช่จุดหมาย แต่เป็นการเดินทาง', 'icon' => '🛤️', 'priority' => 4],
            ['content' => 'คนสำเร็จ ไม่หยุดเรียนรู้', 'icon' => '📖', 'priority' => 4],
            ['content' => 'ความสำเร็จ มาจากการทำซ้ำสิ่งที่ถูกต้อง', 'icon' => '🔁', 'priority' => 3],
            ['content' => '1% ที่สำเร็จ คือคนที่ไม่เลิกทำ', 'icon' => '💯', 'priority' => 5],
            ['content' => 'ความสำเร็จคือผลรวมของความพยายามทุกวัน', 'icon' => '📊', 'priority' => 4],
            ['content' => 'คนที่ประสบความสำเร็จ คือคนที่ลงมือทำ', 'icon' => '🏃', 'priority' => 4],
            ['content' => 'ไม่มีทางลัดสู่ความสำเร็จที่ยั่งยืน', 'icon' => '🛣️', 'priority' => 3],
            ['content' => 'ความสำเร็จที่แท้จริง คือการเติบโตทุกวัน', 'icon' => '🌱', 'priority' => 4],
            ['content' => 'สำเร็จคนเดียว ยังไม่เท่าสำเร็จด้วยกัน', 'icon' => '👥', 'priority' => 4],
            ['content' => 'ความสำเร็จไม่ได้วัดที่เงิน แต่วัดที่ความสุข', 'icon' => '😊', 'priority' => 3],
            ['content' => 'ทุกความสำเร็จ เริ่มจากความเชื่อมั่นในตัวเอง', 'icon' => '💪', 'priority' => 4],
            ['content' => 'ความสำเร็จอยู่แค่เอื้อม ถ้าไม่หยุดพยายาม', 'icon' => '🤚', 'priority' => 4],
            ['content' => 'สำเร็จได้ เพราะไม่ยอมแพ้', 'icon' => '🏆', 'priority' => 5],
            ['content' => 'ความสำเร็จของคุณ จะเป็นแรงบันดาลใจให้คนอื่น', 'icon' => '💫', 'priority' => 3],
            ['content' => 'ล้มเหลว 99 ครั้ง สำเร็จครั้งที่ 100', 'icon' => '💯', 'priority' => 4],
            ['content' => 'ความสำเร็จคือรางวัลของความอดทน', 'icon' => '🎁', 'priority' => 3],
            ['content' => 'ยิ่งยาก ยิ่งคุ้มค่าเมื่อสำเร็จ', 'icon' => '💎', 'priority' => 4],
            ['content' => 'ความสำเร็จเล็กๆ สะสมเป็นความยิ่งใหญ่', 'icon' => '🧱', 'priority' => 3],
            ['content' => 'คนสำเร็จ มองปัญหาเป็นโอกาส', 'icon' => '👀', 'priority' => 4],
            ['content' => 'ความสำเร็จที่แท้จริง คือได้ช่วยเหลือผู้อื่น', 'icon' => '🤝', 'priority' => 4],
        ];

        return array_map(fn($s) => array_merge($s, ['category' => 'success']), $slogans);
    }

    /**
     * คำขวัญการทำงานร่วมกัน
     */
    private function getTeamworkSlogans(): array
    {
        $slogans = [
            ['content' => 'คนเดียว หัวหาย สองคน เพื่อนตาย', 'icon' => '👥', 'priority' => 4],
            ['content' => 'ทีมที่แข็งแกร่ง สร้างผลลัพธ์ที่ยิ่งใหญ่', 'icon' => '💪', 'priority' => 4],
            ['content' => 'ร่วมมือกัน ทำให้ความฝันเป็นจริง', 'icon' => '🤝', 'priority' => 4],
            ['content' => 'ไม่มีใครเก่งคนเดียว แต่เก่งด้วยกัน', 'icon' => '👫', 'priority' => 4],
            ['content' => 'พลังของทีม คือพลังที่ไม่มีขีดจำกัด', 'icon' => '⚡', 'priority' => 3],
            ['content' => 'หนึ่งคนทำได้เร็ว แต่ทีมทำได้ไกล', 'icon' => '🏃', 'priority' => 4],
            ['content' => 'ความสำเร็จของทีม คือความสำเร็จของทุกคน', 'icon' => '🏆', 'priority' => 4],
            ['content' => 'เราไม่ได้ทำคนเดียว เรามีกันและกัน', 'icon' => '❤️', 'priority' => 4],
            ['content' => 'ทีมที่ดี ทำให้งานยากกลายเป็นง่าย', 'icon' => '✨', 'priority' => 3],
            ['content' => 'ร่วมแรง ร่วมใจ ไม่มีอะไรที่ทำไม่ได้', 'icon' => '💪', 'priority' => 4],
            ['content' => 'พาร์ทเนอร์ที่ดี มีค่ามากกว่าทองคำ', 'icon' => '💎', 'priority' => 4],
            ['content' => 'สนับสนุนกัน เติบโตไปด้วยกัน', 'icon' => '🌱', 'priority' => 4],
            ['content' => 'เราคือครอบครัวเดียวกัน', 'icon' => '🏠', 'priority' => 3],
            ['content' => 'แบ่งปันความรู้ เพิ่มพูนความสำเร็จ', 'icon' => '📚', 'priority' => 3],
            ['content' => 'ทีมเวิร์ค ทำให้ฝันเป็นจริง', 'icon' => '✨', 'priority' => 4],
            ['content' => 'ช่วยกันคิด ช่วยกันทำ ช่วยกันสำเร็จ', 'icon' => '🧠', 'priority' => 3],
            ['content' => 'พลังของความสามัคคี ไม่มีใครเอาชนะได้', 'icon' => '🔥', 'priority' => 4],
            ['content' => 'เพื่อนร่วมงานที่ดี คือสมบัติล้ำค่า', 'icon' => '💝', 'priority' => 3],
            ['content' => 'ไว้วางใจกัน ก้าวไปด้วยกัน', 'icon' => '🤝', 'priority' => 4],
            ['content' => 'ทีมที่แข็งแกร่ง เกิดจากความเข้าใจกัน', 'icon' => '💕', 'priority' => 3],
        ];

        return array_map(fn($s) => array_merge($s, ['category' => 'teamwork']), $slogans);
    }

    /**
     * คำขวัญ Affiliate
     */
    private function getAffiliateSlogans(): array
    {
        $slogans = [
            ['content' => 'แนะนำของดี ได้ค่าคอม ลูกค้าได้ของถูกใจ Win-Win!', 'icon' => '🎯', 'priority' => 5],
            ['content' => 'ขยายเครือข่าย ขยายรายได้ ไปด้วยกัน', 'icon' => '🕸️', 'priority' => 4],
            ['content' => 'รายได้ Passive Income สร้างได้จริง', 'icon' => '💰', 'priority' => 4],
            ['content' => 'แชร์ของดี ได้เงิน ง่ายๆ แค่นี้เอง', 'icon' => '📲', 'priority' => 4],
            ['content' => 'Affiliate ไม่ใช่แค่ขาย แต่คือการช่วยเหลือ', 'icon' => '🤝', 'priority' => 4],
            ['content' => 'สร้างรายได้จากทุกที่ ทุกเวลา', 'icon' => '🌍', 'priority' => 4],
            ['content' => 'ยิ่งแชร์ ยิ่งได้ ยิ่งเติบโต', 'icon' => '📈', 'priority' => 4],
            ['content' => 'เครือข่ายของคุณ คือทรัพย์สินของคุณ', 'icon' => '💎', 'priority' => 4],
            ['content' => 'ทุกการแนะนำ คือโอกาสสร้างรายได้', 'icon' => '💵', 'priority' => 3],
            ['content' => 'Affiliate มืออาชีพ สร้างได้ทุกคน', 'icon' => '👔', 'priority' => 3],
            ['content' => 'รายได้ไม่จำกัด ขึ้นอยู่กับความพยายาม', 'icon' => '🚀', 'priority' => 4],
            ['content' => 'สร้างทีม สร้างอนาคต สร้างความมั่นคง', 'icon' => '🏗️', 'priority' => 4],
            ['content' => 'ขายของดี มีคนขอบคุณ', 'icon' => '🙏', 'priority' => 3],
            ['content' => 'Affiliate คือธุรกิจที่ทุกคนเป็นเจ้าของได้', 'icon' => '👑', 'priority' => 4],
            ['content' => 'เริ่มต้น 0 บาท แต่สร้างรายได้ได้จริง', 'icon' => '🆓', 'priority' => 4],
            ['content' => 'พาร์ทเนอร์ที่ดี ช่วยให้ธุรกิจเติบโต', 'icon' => '🌱', 'priority' => 3],
            ['content' => 'ยิ่งมีเครือข่าย ยิ่งมีรายได้', 'icon' => '🕸️', 'priority' => 4],
            ['content' => 'ทำงานที่บ้าน สร้างรายได้ทั่วโลก', 'icon' => '🏠', 'priority' => 3],
            ['content' => 'คุณคือตัวแทนของสินค้าดีๆ', 'icon' => '⭐', 'priority' => 3],
            ['content' => 'แนะนำจากใจ ลูกค้าประทับใจ', 'icon' => '❤️', 'priority' => 4],
        ];

        return array_map(fn($s) => array_merge($s, ['category' => 'affiliate']), $slogans);
    }

    /**
     * คำขวัญอีคอมเมิร์ซ
     */
    private function getEcommerceSlogans(): array
    {
        $slogans = [
            ['content' => 'ขายออนไลน์ ไม่มีวันหยุด รายได้ไม่มีเพดาน', 'icon' => '🛒', 'priority' => 4],
            ['content' => 'ร้านค้าออนไลน์ เปิดตลอด 24 ชั่วโมง', 'icon' => '⏰', 'priority' => 4],
            ['content' => 'สินค้าดี ราคาถูก ส่งไว ลูกค้าประทับใจ', 'icon' => '📦', 'priority' => 4],
            ['content' => 'ขายของออนไลน์ ทำได้ทุกคน เริ่มได้เลยวันนี้', 'icon' => '💻', 'priority' => 4],
            ['content' => 'ลูกค้าทั่วประเทศ แค่คลิกเดียว', 'icon' => '🇹🇭', 'priority' => 3],
            ['content' => 'อีคอมเมิร์ซ คืออนาคตของธุรกิจ', 'icon' => '🚀', 'priority' => 4],
            ['content' => 'ต้นทุนต่ำ กำไรดี ขายออนไลน์นี่แหละ', 'icon' => '💰', 'priority' => 4],
            ['content' => 'สินค้าดีมีคนหา สินค้าห่วยมีคนด่า', 'icon' => '⭐', 'priority' => 3],
            ['content' => 'ขายของออนไลน์ ไม่ต้องมีหน้าร้าน', 'icon' => '🏪', 'priority' => 3],
            ['content' => 'รีวิวดี ยอดขายพุ่ง', 'icon' => '📈', 'priority' => 4],
            ['content' => 'ลูกค้าคือพระเจ้า บริการดีได้ลูกค้าประจำ', 'icon' => '👑', 'priority' => 4],
            ['content' => 'ส่งไว ส่งฟรี ลูกค้าแฮปปี้', 'icon' => '🚚', 'priority' => 3],
            ['content' => 'ถ่ายรูปสวย ขายได้ราคา', 'icon' => '📸', 'priority' => 3],
            ['content' => 'ตอบแชทไว ปิดการขายได้', 'icon' => '💬', 'priority' => 4],
            ['content' => 'ขายออนไลน์ ทำเงินได้ทั้งกลางวันกลางคืน', 'icon' => '🌙', 'priority' => 3],
            ['content' => 'ลูกค้าพอใจ แนะนำต่อ ยอดขายเพิ่ม', 'icon' => '📣', 'priority' => 4],
            ['content' => 'สต็อกพอ ส่งทัน ลูกค้าประทับใจ', 'icon' => '📦', 'priority' => 3],
            ['content' => 'ขายของดี มีแต่คนบอกต่อ', 'icon' => '🗣️', 'priority' => 4],
            ['content' => 'อีคอมเมิร์ซ สร้างโอกาสให้ทุกคน', 'icon' => '🌟', 'priority' => 4],
            ['content' => 'เปิดร้านออนไลน์ เปิดประตูสู่ความสำเร็จ', 'icon' => '🚪', 'priority' => 3],
        ];

        return array_map(fn($s) => array_merge($s, ['category' => 'ecommerce']), $slogans);
    }

    /**
     * คำขวัญชีวิต
     */
    private function getLifeSlogans(): array
    {
        $slogans = [
            ['content' => 'ชีวิตสั้น อย่าเสียเวลากับเรื่องไร้สาระ', 'icon' => '⏳', 'priority' => 4],
            ['content' => 'ทำวันนี้ให้ดีที่สุด อย่าห่วงพรุ่งนี้มาก', 'icon' => '🌅', 'priority' => 4],
            ['content' => 'ความสุขอยู่ที่ใจ ไม่ได้อยู่ที่เงิน', 'icon' => '😊', 'priority' => 3],
            ['content' => 'ใช้ชีวิตให้คุ้มค่า ทุกวันคือของขวัญ', 'icon' => '🎁', 'priority' => 4],
            ['content' => 'อย่าเปรียบเทียบชีวิตกับใคร ทุกคนมีเส้นทางของตัวเอง', 'icon' => '🛤️', 'priority' => 4],
            ['content' => 'ความกตัญญู คือคุณธรรมอันสูงส่ง', 'icon' => '🙏', 'priority' => 3],
            ['content' => 'รักตัวเอง ก่อนที่จะรักคนอื่น', 'icon' => '❤️', 'priority' => 3],
            ['content' => 'ยิ้มให้โลก โลกจะยิ้มให้คุณ', 'icon' => '😄', 'priority' => 4],
            ['content' => 'ทำดีไม่ต้องเอาหน้า ทำดีเพราะมันคือสิ่งที่ควรทำ', 'icon' => '✨', 'priority' => 3],
            ['content' => 'ชีวิตมีขึ้นมีลง สำคัญคืออย่ายอมแพ้', 'icon' => '🎢', 'priority' => 4],
            ['content' => 'ความพอเพียง นำมาซึ่งความสุข', 'icon' => '🌿', 'priority' => 4],
            ['content' => 'อย่าปล่อยให้วันนี้ผ่านไปโดยไม่ได้ทำอะไรเลย', 'icon' => '📆', 'priority' => 4],
            ['content' => 'สุขภาพคือทรัพย์สินที่มีค่าที่สุด', 'icon' => '💪', 'priority' => 4],
            ['content' => 'ครอบครัวคือสิ่งสำคัญที่สุดในชีวิต', 'icon' => '👨‍👩‍👧‍👦', 'priority' => 4],
            ['content' => 'ให้อภัย ปล่อยวาง ใจเบาสบาย', 'icon' => '🕊️', 'priority' => 3],
            ['content' => 'เวลาคือสิ่งที่มีค่า ใช้มันอย่างคุ้มค่า', 'icon' => '⏰', 'priority' => 4],
            ['content' => 'มีน้อยใช้น้อย มีมากใช้มาก พอเพียง', 'icon' => '⚖️', 'priority' => 3],
            ['content' => 'ความสุขที่แท้จริง มาจากภายในใจ', 'icon' => '💝', 'priority' => 4],
            ['content' => 'ทุกวันคือโอกาสในการเริ่มต้นใหม่', 'icon' => '🌄', 'priority' => 4],
            ['content' => 'อย่าหมกมุ่นกับอดีต จงมุ่งหน้าสู่อนาคต', 'icon' => '👀', 'priority' => 3],
            ['content' => 'ชีวิตสอนให้เราเรียนรู้ทุกวัน', 'icon' => '📖', 'priority' => 3],
            ['content' => 'ความทุกข์ผ่านไป ความสุขกำลังมา', 'icon' => '🌈', 'priority' => 4],
            ['content' => 'ทำสิ่งที่รัก รักในสิ่งที่ทำ', 'icon' => '❤️', 'priority' => 4],
            ['content' => 'ความสำเร็จไม่ได้สำคัญเท่าการเป็นคนดี', 'icon' => '😇', 'priority' => 3],
            ['content' => 'ชีวิตคือการเดินทาง ไม่ใช่จุดหมายปลายทาง', 'icon' => '🚶', 'priority' => 4],
        ];

        return array_map(fn($s) => array_merge($s, ['category' => 'life']), $slogans);
    }

    /**
     * คำขวัญปรัชญา
     */
    private function getWisdomSlogans(): array
    {
        $slogans = [
            ['content' => 'น้ำขึ้นให้รีบตัก โอกาสมาให้รีบคว้า', 'icon' => '🌊', 'priority' => 4],
            ['content' => 'ช้าๆ ได้พร้าเล่มงาม', 'icon' => '🐢', 'priority' => 3],
            ['content' => 'รู้เขารู้เรา รบร้อยครั้งชนะร้อยครั้ง', 'icon' => '🎯', 'priority' => 4],
            ['content' => 'น้ำนิ่งไหลลึก คนเงียบมักทำได้มาก', 'icon' => '🌊', 'priority' => 3],
            ['content' => 'ต้นไม้ใหญ่มาจากเมล็ดเล็กๆ', 'icon' => '🌳', 'priority' => 4],
            ['content' => 'ความรู้คืออำนาจ แต่การลงมือทำคือพลัง', 'icon' => '⚡', 'priority' => 4],
            ['content' => 'ปากหนักเบา หัวหนักใบ ใจเบาคำ', 'icon' => '🤐', 'priority' => 3],
            ['content' => 'รู้จักพอ รู้จักหยุด รู้จักปล่อยวาง', 'icon' => '☯️', 'priority' => 4],
            ['content' => 'อ่อนน้อมถ่อมตน คนนับถือ', 'icon' => '🙇', 'priority' => 3],
            ['content' => 'ทำตัวเหมือนน้ำ อ่อนไหลแต่แข็งแกร่ง', 'icon' => '💧', 'priority' => 4],
            ['content' => 'ความโกรธคือไฟ ความอดทนคือน้ำ', 'icon' => '🔥', 'priority' => 3],
            ['content' => 'ฟังมากพูดน้อย เรียนรู้ได้มาก', 'icon' => '👂', 'priority' => 4],
            ['content' => 'ความผิดพลาด คือครูที่ดีที่สุด', 'icon' => '📝', 'priority' => 4],
            ['content' => 'คนฉลาดเรียนรู้จากความผิดพลาดของคนอื่น', 'icon' => '🧠', 'priority' => 3],
            ['content' => 'อย่าตัดสินหนังสือจากปก อย่าตัดสินคนจากภายนอก', 'icon' => '📚', 'priority' => 4],
            ['content' => 'ความสำเร็จที่ยั่งยืน มาจากความซื่อสัตย์', 'icon' => '💎', 'priority' => 4],
            ['content' => 'ทำวันละนิด แต่ทำทุกวัน', 'icon' => '📆', 'priority' => 4],
            ['content' => 'ความอดทน เป็นสมบัติของนักปราชญ์', 'icon' => '🏛️', 'priority' => 3],
            ['content' => 'มองโลกในแง่ดี แต่เตรียมพร้อมสำหรับสิ่งที่ไม่ดี', 'icon' => '🌞', 'priority' => 4],
            ['content' => 'ความสงบ นำมาซึ่งปัญญา', 'icon' => '🧘', 'priority' => 3],
        ];

        return array_map(fn($s) => array_merge($s, ['category' => 'wisdom']), $slogans);
    }

    /**
     * คำขวัญเกี่ยวกับ TP-Affiliate โดยเฉพาะ
     */
    private function getTPAffiliateSlogans(): array
    {
        $slogans = [
            ['content' => 'เราช่วยคุณ คุณช่วยเรา ไทยช่วยไทย - TP-Affiliate', 'icon' => '🇹🇭', 'priority' => 5],
            ['content' => 'เราจะพัฒนาสิ่งที่คุณต้องการ อยู่ด้วยกันอย่าลากลูกค้าไปไหนเลย', 'icon' => '💝', 'priority' => 5],
            ['content' => 'เราจะให้ในสิ่งที่อื่นไม่ให้คุณ เพียงบอกเรา', 'icon' => '🎁', 'priority' => 5],
            ['content' => 'TP-Affiliate พาร์ทเนอร์ของคุณ ตลอดไป', 'icon' => '🤝', 'priority' => 5],
            ['content' => 'สร้างธุรกิจ สร้างรายได้ สร้างอนาคต กับ TP-Affiliate', 'icon' => '🚀', 'priority' => 5],
            ['content' => 'ความสำเร็จของคุณ คือความภาคภูมิใจของเรา', 'icon' => '🏆', 'priority' => 5],
            ['content' => 'เติบโตไปด้วยกัน ไม่ทิ้งกัน TP-Affiliate', 'icon' => '🌱', 'priority' => 5],
            ['content' => 'เราไม่ได้แค่ให้ระบบ เราให้การสนับสนุน', 'icon' => '💪', 'priority' => 4],
            ['content' => 'ทุกปัญหาของคุณ คือสิ่งที่เราต้องแก้ไข', 'icon' => '🔧', 'priority' => 4],
            ['content' => 'ความไว้วางใจของคุณ คือแรงผลักดันของเรา', 'icon' => '💎', 'priority' => 4],
            ['content' => 'สร้างเครือข่าย สร้างความมั่นคง สร้างอนาคต', 'icon' => '🕸️', 'priority' => 4],
            ['content' => 'ไม่ว่าคุณจะเริ่มต้นจากศูนย์ เราจะพาคุณไปถึงเป้าหมาย', 'icon' => '🎯', 'priority' => 4],
            ['content' => 'พร้อมช่วยเหลือ 24 ชั่วโมง ทุกวัน ไม่มีวันหยุด', 'icon' => '⏰', 'priority' => 3],
            ['content' => 'คุณไม่ได้อยู่คนเดียว เรามีทีมคอยช่วยเหลือ', 'icon' => '👥', 'priority' => 4],
            ['content' => 'เราเชื่อในศักยภาพของคุณ', 'icon' => '💫', 'priority' => 4],
            ['content' => 'TP-Affiliate ไม่ใช่แค่ระบบ แต่คือครอบครัว', 'icon' => '🏠', 'priority' => 5],
            ['content' => 'รายได้ไม่จำกัด ขึ้นอยู่กับความพยายามของคุณ', 'icon' => '💰', 'priority' => 4],
            ['content' => 'เราพัฒนาไม่หยุด เพื่อให้คุณได้สิ่งที่ดีที่สุด', 'icon' => '📈', 'priority' => 4],
            ['content' => 'ฝันให้ไกล ไปให้ถึง TP-Affiliate พาคุณไปได้', 'icon' => '✈️', 'priority' => 4],
            ['content' => 'วันนี้คุณเลือกเรา พรุ่งนี้เราพาคุณสำเร็จ', 'icon' => '🏆', 'priority' => 5],
            ['content' => 'ร่วมสร้างอนาคตไปด้วยกัน กับ TP-Affiliate', 'icon' => '🌟', 'priority' => 5],
            ['content' => 'เราเข้าใจผู้ประกอบการ เพราะเราก็เป็นผู้ประกอบการ', 'icon' => '🤝', 'priority' => 4],
            ['content' => 'ความสำเร็จของคุณ คือความสำเร็จของ TP-Affiliate', 'icon' => '🎉', 'priority' => 5],
            ['content' => 'ไม่มีอะไรที่ทำไม่ได้ ถ้าเราร่วมมือกัน', 'icon' => '💪', 'priority' => 4],
            ['content' => 'สัญญาของเรา: เราจะไม่ทิ้งคุณ', 'icon' => '🤞', 'priority' => 5],
        ];

        return array_map(fn($s) => array_merge($s, ['category' => 'general', 'priority' => $s['priority'] ?? 3]), $slogans);
    }
}
