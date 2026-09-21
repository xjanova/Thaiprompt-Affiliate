<?php

namespace App\Services\Fortune;

/**
 * 🗓️ ฤกษ์ยามตามตำราไทย — พอร์ตจาก GenLotto `public_html/assets/js/ruek.js` (JS → PHP บรรทัดต่อบรรทัด)
 *
 * ทำไมต้องมี (2026-09-21): แม่หมอไม่เคยมีตัวคำนวณฤกษ์ยามเลย แต่พรอมต์สั่งให้พูดเรื่องนี้หลายจุด
 *   - บทสรุปบิล 99 บังคับย่อหน้า "วัน/ช่วงเวลามงคล" ทุกบิล
 *   - ตำราสายมูสั่ง "เลือกวันธงชัย-อธิบดี เลี่ยงวันอุบาทว์-โลกาวินาศ" โดยไม่บอกว่าปีนี้คือวันไหน
 *   - เมนูขายสัญญา "ฤกษ์ดี"
 *   ⇒ AI ต้องเดาวันเอง = ผิดกฎ "ห้ามมโนโหร"
 *
 * ของในไฟล์นี้คือ "ตำรา" ไม่ใช่ตัวเลือกของเรา — ทุกตารางยืนยันได้ ≥ 2 แหล่งอิสระ และตรึงกับปฏิทินโหรฯ myhora
 *   (GenLotto tests/verify_ruek.mjs · ที่นี่ ThaiRuekYamTest ใช้ fixture ชุดเดียวกัน)
 *   - ฤกษ์บน (นพดลฤกษ์) = ตำแหน่งจันทร์จริง (นิรายนะ ลาหิรี) → เทียบ myhora 1,370 วัน เวลาเปลี่ยนฤกษ์คลาด ≤ 10 นาที
 *   - ฤกษ์ล่าง = ปฏิทินหลวง (ThaiLunarCalendar) + วาร → ดิถีโชค/มหาสูญ/พลาย/พิฆาต/เรียงหมอน ตรง myhora ทุกวัน
 *   - กาลโยค = สูตรจากจุลศักราช → ตรงประกาศสงกรานต์ทุกปี พ.ศ. 2480–2600
 *   - ยามอัฏฐกาล = ลงเลข +5 (กลางวัน) / +4 (กลางคืน) → ตรงตาราง 5 แหล่ง
 *   ข้อที่แหล่งขัดกันหรือหากฎคำนวณไม่ได้ ไม่ใส่ (ดู OMITTED) — ยอมทำน้อยแต่ถูก
 *
 * ⚠️ นี่คือ "ฤกษ์ทั่วไป" ตามตำรา ไม่ได้ผูกกับดวงเกิดของเจ้าของงาน
 *    การเลี่ยงวันกาลกิณีของเจ้าของงานทำที่ AuspiciousTimingDirective (ใช้ทักษาจาก ThaiAstrologyService)
 *
 * เวลาทั้งหมดเป็นเวลาไทย (UTC+7) · จังหวัดมีผลเฉพาะลัคนา (ยาม/ฤกษ์บน/ฤกษ์ล่างใช้เวลามาตรฐานทั้งประเทศ)
 */
final class ThaiRuekYam
{
    /** ความกว้างนักษัตร 1 ช่อง = 13°20′ */
    public const NAK_SPAN = 360.0 / 27.0;

    /** เวลาท้องถิ่นกรุงเทพฯ UTC+06:42 ที่คัมภีร์สุริยยาตร์ใช้ (หน่วยวัน) */
    private const LMT_BKK = 6.7 / 24.0;

    /** พิกัดกรุงเทพฯ — ค่าเริ่มต้นเมื่อไม่รู้สถานที่จัดงาน */
    public const DEFAULT_LAT = 13.75;

    public const DEFAULT_LON = 100.5;

    public const WD = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

    /** 27 นักษัตร เรียงจากอัศวินี — ช่องละ 13°20′ */
    public const NAKSHATRA = [
        'อัศวินี', 'ภรณี', 'กฤติกา', 'โรหิณี', 'มฤคศิระ', 'อารทรา', 'ปุนัพสุ', 'ปุษยะ', 'อาศเลษา',
        'มาฆะ', 'บุรพผลคุนี', 'อุตรผลคุนี', 'หัสตะ', 'จิตรา', 'สวาตี', 'วิศาขา', 'อนุราธา', 'เชษฐา',
        'มูละ', 'บุรพาษาฒ', 'อุตราษาฒ', 'ศรวณะ', 'ธนิษฐา', 'ศตภิษัช', 'บุรพภัทรบท', 'อุตรภัทรบท', 'เรวดี',
    ];

    /**
     * ฤกษ์บน (นพดลฤกษ์) — นักษัตรที่ n (1–27) ตกฤกษ์ที่ (n−1) mod 9
     *   ลำดับ: วิกิพีเดีย "ดาวนักขัตฤกษ์" · astroneemo 1471 · sanook 55645 · myhora
     *   ดี/ไม่ดี: astroneemo 311 — ดี 6 ฤกษ์ · โจโร เทศาตรี เพชฌฆาต = "ฤกษ์คร่อมราศี"
     *   ทลิทโทใช้กับเรื่อง "ขอ" เท่านั้น (sanook 55645)
     */
    public const RUEK9 = [
        ['name' => 'ทลิทโทฤกษ์', 'short' => 'ทลิทโท', 'good' => true, 'ask_only' => true, 'mean' => 'ผู้ขอ', 'fit' => 'การขอ สู่ขอ ขอความช่วยเหลือ สมัครงาน กู้ยืม'],
        ['name' => 'มหัทธโนฤกษ์', 'short' => 'มหัทธโน', 'good' => true, 'ask_only' => false, 'mean' => 'เศรษฐี', 'fit' => 'งานมงคลเพื่อทรัพย์ เปิดร้าน ค้าขาย แต่งงาน ขึ้นบ้านใหม่'],
        ['name' => 'โจโรฤกษ์', 'short' => 'โจโร', 'good' => false, 'ask_only' => false, 'mean' => 'ผู้ช่วงชิง', 'fit' => 'งานแข่งขัน ช่วงชิง ปราบปราม'],
        ['name' => 'ภูมิปาโลฤกษ์', 'short' => 'ภูมิปาโล', 'good' => true, 'ask_only' => false, 'mean' => 'ผู้รักษาแผ่นดิน', 'fit' => 'ที่ดิน ก่อสร้าง ปลูกเรือน ยกศาลพระภูมิ งานมงคลทั่วไป'],
        ['name' => 'เทศาตรีฤกษ์', 'short' => 'เทศาตรี', 'good' => false, 'ask_only' => false, 'mean' => 'คนต่างถิ่น', 'fit' => 'การค้าต่างถิ่น เดินทาง ท่องเที่ยว'],
        ['name' => 'เทวีฤกษ์', 'short' => 'เทวี', 'good' => true, 'ask_only' => false, 'mean' => 'นางพญา', 'fit' => 'ความงาม ชื่อเสียง เข้าหาผู้ใหญ่ งานที่ต้องการความเรียบร้อย'],
        ['name' => 'เพชฌฆาตฤกษ์', 'short' => 'เพชฌฆาต', 'good' => false, 'ask_only' => false, 'mean' => 'ผู้ตัด ผู้แบ่ง', 'fit' => 'งานเด็ดขาด ฟันฝ่าอุปสรรค ปลุกเสก'],
        ['name' => 'ราชาฤกษ์', 'short' => 'ราชา', 'good' => true, 'ask_only' => false, 'mean' => 'พระราชา', 'fit' => 'รับตำแหน่ง งานพิธีใหญ่ งานมงคลที่มีเกียรติ'],
        ['name' => 'สมโณฤกษ์', 'short' => 'สมโณ', 'good' => true, 'ask_only' => false, 'mean' => 'นักบวช ความสงบ', 'fit' => 'บวช งานศาสนา ทำบุญ เข้าศึกษา'],
    ];

    /** ชื่อยามอัฏฐกาล ผูกกับดาวเจ้ายาม (ไม่ใช่ลำดับช่อง) — กลางวัน / กลางคืน */
    public const YAM_DAY = [1 => 'สุริชะ', 2 => 'จันเทา', 3 => 'ภุมมะ', 4 => 'พุธะ', 5 => 'ครู', 6 => 'ศุกระ', 7 => 'เสารี'];

    public const YAM_NIGHT = [1 => 'รวิ', 2 => 'ศศิ', 3 => 'ภุมโม', 4 => 'พุโธ', 5 => 'ชีโว', 6 => 'ศุกโกร', 7 => 'โสโร'];

    /** ชื่อดาวตามเลข 1–7 (ใช้บอกดาวเจ้ายาม) */
    public const PLANET_TH = [1 => 'อาทิตย์', 2 => 'จันทร์', 3 => 'อังคาร', 4 => 'พุธ', 5 => 'พฤหัสบดี', 6 => 'ศุกร์', 7 => 'เสาร์'];

    /**
     * จุลศักราช: เถลิงศก = 1954167.5 + (จ.ศ.×292207 + 373)/800 วัน (เวลาท้องถิ่นกรุงเทพฯ)
     *   ตรงประกาศสงกรานต์ 121 ปีภายใน 1 นาที
     */
    public const CS_EPOCH = 1954167.5;

    /**
     * กาลโยค — ค่าแต่ละฐาน = ((a·จ.ศ. + b) mod m) + off   [a, b] ตามตาราง · m/off ตาม KY_BASE
     *   ตรงประกาศสงกรานต์ทุกปี พ.ศ. 2480–2600 · อุบาทว์อยู่ก่อนธงชัย 1 วันเสมอ
     *   (พระราชหัตถเลขา ร.5 ในราชกิจจานุเบกษา 2417 · ร.4 ให้เรียกอุบาทว์ว่า "อุบาสน")
     */
    public const KY = [
        'thongchai' => ['th' => 'ธงชัย', 'good' => true, 'wan' => [3, 2], 'yam' => [2, 2], 'rasi' => [10, 3], 'dithi' => [10, 2], 'ruek' => [10, 2]],
        'athibodi' => ['th' => 'อธิบดี', 'good' => true, 'wan' => [1, 4], 'yam' => [1, 3], 'rasi' => [1, 0], 'dithi' => [1, 23], 'ruek' => [1, 2]],
        'ubat' => ['th' => 'อุบาทว์', 'good' => false, 'wan' => [3, 1], 'yam' => [2, 1], 'rasi' => [10, 2], 'dithi' => [10, 1], 'ruek' => [10, 1]],
        'lokawinat' => ['th' => 'โลกาวินาศ', 'good' => false, 'wan' => [1, 6], 'yam' => [1, 7], 'rasi' => [1, 4], 'dithi' => [1, 9], 'ruek' => [1, 12]],
    ];

    /** ฐานของกาลโยค: [m, off, ชื่อ] — วัน 1=อาทิตย์ · ยาม 1–8 · ราศี 0=เมษ · ดิถี 1–15 ขึ้น 16–30 แรม · ฤกษ์ 1–27 */
    public const KY_BASE = ['wan' => [7, 1, 'วัน'], 'yam' => [8, 1, 'ยาม'], 'rasi' => [12, 0, 'ราศี'], 'dithi' => [30, 1, 'ดิถี'], 'ruek' => [27, 1, 'ฤกษ์']];

    /**
     * ฤกษ์ล่าง (ภูมิดลฤกษ์) — ค่ำของวันอาทิตย์…เสาร์ · ค่ำ = ขึ้นหรือแรมก็ได้ (ทุกแหล่งตรงกัน) · null = แหล่งขัดกัน ไม่ใส่
     *   ตรวจกับเครื่องหมายในปฏิทิน myhora ทุกวัน 2567–2570
     */
    public const GOOD_DITHI = [
        ['key' => 'amarit', 'name' => 'ดิถีอำมฤตโชค', 'rank' => 'ดีที่สุด', 'by_wd' => [8, 3, 9, 2, 4, 1, 5]],
        // พลูหลวงให้ อาทิตย์ 13 · เสาร์ 15 — ใช้ค่าที่ astroneemo (มีโคลงกำกับ) + วัดท่าขนุน + myhora ตรงกัน
        ['key' => 'sittho', 'name' => 'ดิถีสิทธิโชค', 'rank' => 'ดี', 'by_wd' => [11, 5, 14, 10, 9, 11, 4]],
        // วันพฤหัส 7 ค่ำ (astroneemo "ครูเจ็ด" · พลูหลวง · วัดท่าขนุน · theluckyname) — ค่า 3 ค่ำพบแหล่งเดียว
        ['key' => 'mahasittho', 'name' => 'ดิถีมหาสิทธิโชค', 'rank' => 'ดี', 'by_wd' => [14, 12, 13, 4, 7, 10, 15]],
        ['key' => 'chaiyachok', 'name' => 'ดิถีชัยโชค', 'rank' => 'ดี (งานแข่งขัน)', 'by_wd' => [8, 3, 11, 10, 4, 1, 11]],
        ['key' => 'rachachok', 'name' => 'ดิถีราชาโชค', 'rank' => 'ดี (ขอความช่วยเหลือผู้ใหญ่)', 'by_wd' => [6, 3, 9, 6, 10, 1, 5]],
    ];

    /** ดิถีมหาสูญ [ข] พรหมชาติ ตามเดือน */
    public const MAHASUN_MONTH = [6 => 4, 3 => 4, 7 => 8, 10 => 8, 8 => 6, 5 => 6, 11 => 12, 2 => 12, 9 => 10, 12 => 10, 1 => 2, 4 => 2];

    /** ดิถีมหาสูญ [ก] อ.ทองเจือ ตามราศีอาทิตย์ (นิรายนะ ลาหิรี) เมษ…มีน */
    public const MAHASUN_SUN = [6, 4, 8, 6, 10, 8, 12, 10, 2, 12, 4, 2];

    /** ดิถีพิฆาต: อาทิตย์–พฤหัสตรงกันทุกแหล่ง · ศุกร์/เสาร์ขัดกัน (8/9 · 9/8 · 12/12) จึงไม่ใส่ */
    public const PHIKHAT = [12, 11, 7, 3, 6, null, null];

    /** ดิถีทรธึก นับตามวาร (หลวงวิศาลดรุณกร · astroneemo 1479) · ศุกร์ขัดกัน (3/7/9) */
    public const THORATHUEK = [4, 6, 1, 3, 8, null, 1];

    /** ดิถีอายกรรมพลาย ตามเดือน: [ปฐม, ทุติยะ, ตติยะ] */
    public const PHLAI = [3 => [4, 5, 6], 7 => [4, 5, 6], 4 => [1, 2, 3], 10 => [1, 2, 3], 5 => [13, 14, 15], 11 => [13, 14, 15], 6 => [10, 11, 12], 8 => [6, 7, 8], 9 => [3, 4, 5], 12 => [2, 3, 4], 1 => [9, 10, 11], 2 => [7, 8, 9]];

    public const PHLAI_LV = ['ปฐม', 'ทุติยะ', 'ตติยะ'];

    /** ดิถีเรียงหมอน (ตำราใช้หาฤกษ์แต่งงาน) */
    public const RIANGMON = ['waxing' => [7, 10, 13], 'waning' => [4, 8, 10, 14]];

    /**
     * มหาฤกษ์ตำราพรหมชาติ รายดิถีข้างขึ้น (astroneemo 923 · magiciannumber) — ข้างแรมยืนยันได้แหล่งเดียว จึงไม่ใส่
     * แสดงเป็นข้อมูลประกอบ ไม่ตัดสินวัน เพราะขัดกับตำราอื่นที่ยืนยันแล้ว
     */
    public const MAHALUEK_WAX = ['good' => [1, 4, 6, 9, 11, 13, 15]];

    /** ทิศห้ามตามวาร (อาทิตย์…เสาร์) — หลาวเหล็ก: astroneemo 928 · ajanton · ผีหลวง: astroneemo 927 · พลูหลวง */
    public const DIR_LAOLEK = ['ตะวันตก', 'ตะวันออก', 'เหนือ', 'เหนือ', 'ใต้', 'ตะวันตก', 'ตะวันออก'];

    public const DIR_PHILUANG = ['พายัพ (ตะวันตกเฉียงเหนือ)', 'บูรพา (ตะวันออก)', 'อีสาน (ตะวันออกเฉียงเหนือ)', 'อุดร (เหนือ)', 'ทักษิณ (ใต้)', 'ปัจจิม (ตะวันตก)', 'อาคเนย์ (ตะวันออกเฉียงใต้)'];

    /** อัคนิโรธตกที่ใด ตามค่ำ 1–15 (ขึ้นและแรม) — [ที่ตก, สิ่งที่ห้ามทำในวันนั้น] */
    public const AKKHANI = [
        1 => ['วัวควาย', 'ซื้อขายวัวควาย เปิดคอกสัตว์'], 2 => ['ป่า', 'เข้าป่า ตัดไม้'], 3 => ['น้ำ', 'เดินทางน้ำ ลงเรือเล่นน้ำ ขุดบ่อสระ'], 4 => ['ภูเขา', 'ขึ้นเขา ปีนเขา'],
        5 => ['ที่ทาง', 'แบ่งที่ดิน รังวัด'], 6 => ['บ้านเรือน', 'ยกเสาเอก ปลูกบ้าน ขึ้นบ้านใหม่ งานมงคลในบ้าน'], 7 => ['วัง', 'พิธีราชาภิเษก'], 8 => ['ยวดยาน', 'ซื้อขายรถ หัดขับ ออกรถใหม่'],
        9 => ['แผ่นดิน', 'ขุดหลุม ขุดดิน ถมดิน ลงเสา'], 10 => ['เรือ', 'ลงเรือ ต่อเรือ'], 11 => ['พืชพรรณ', 'ปลูกต้นไม้ เพาะชำ'], 12 => ['ตัวสตรี', 'แต่งงาน ส่งตัวเจ้าสาว'],
        13 => ['ตัวบุรุษ', 'แต่งงาน ส่งตัวเข้าหอ'], 14 => ['พัทธสีมา', 'บวช บรรพชา อุปสมบท'], 15 => ['เทวดา', 'บวงสรวง ไหว้ครู บูชาเทพ'],
    ];

    /** งานมงคลทุกงาน: ตำราไม่ให้ฤกษ์วันอังคารและวันเสาร์ (พลูหลวง "การให้ฤกษ์ฉบับง่าย" · astroneemo 807) */
    public const MONGKOL_WD_BAN = [2, 6];

    /** กฎวันแต่งงาน (ใช้ร่วมกับหมั้น) */
    private const WEDDING_DAY = [
        'wd_ban' => [2, 3, 4, 6], 'wd_ban_text' => 'ตำราห้ามแต่งงานวันอังคาร พุธ พฤหัสบดี เสาร์ (ใช้ได้ อาทิตย์ จันทร์ ศุกร์)',
        'dithi_ban' => [['wd' => 5, 'd' => 7, 'text' => 'วันศุกร์ ขึ้น/แรม 7 ค่ำ ("สมรส 7")']],
        'riangmon' => true,
        'months' => ['good' => [2, 4, 6, 9, 10], 'bad' => [12], 'cond' => [8 => 'เดือน 8 ใช้ได้เฉพาะก่อนเข้าพรรษา (ขึ้น 1–15 ค่ำ)']],
    ];

    /**
     * ฤกษ์ตามงาน 17 หมวด — ทุกกฎยืนยันได้ ≥ 2 แหล่ง · กฎที่ต้องใช้วันเกิดเจ้าของงานไม่อยู่ที่นี่
     *   งานมงคล (mongkol) ใช้ข้อห้ามงานมงคลทั่วไปด้วย: มหาสูญ พิฆาต ทรธึก อายกรรมพลาย กระทิงวัน เสาร์ 5
     *   วันโลกาวินาศ วันอุบาทว์ ฤกษ์บนคร่อมราศี และยาม/ลัคนา/ฤกษ์ที่ตกกาลโยคอุบาทว์-โลกาวินาศ
     *   ruek_prefer = index ของ RUEK9 ที่ตำราให้ใช้กับงานนั้น
     */
    public const ACTIVITIES = [
        'general' => ['label' => 'งานมงคลทั่วไป', 'mongkol' => true, 'ruek_prefer' => null],
        'wedding' => self::WEDDING_DAY + [
            'label' => 'แต่งงาน · ส่งตัว', 'mongkol' => true, 'ruek_prefer' => [1, 7],
            'notes' => ['แต่งงานใช้มหัทธโนฤกษ์ · งานใหญ่ใช้ราชาฤกษ์', 'ภูมิปาโลฤกษ์และฤกษ์ส่งตัว ตำราขัดกัน จึงไม่ชี้', 'คืนส่งตัว: อัคนิโรธ 12 ค่ำตกตัวสตรี 13 ค่ำตกตัวบุรุษ'],
        ],
        'engage' => self::WEDDING_DAY + [
            'label' => 'หมั้น · สู่ขอ', 'mongkol' => true, 'ruek_prefer' => [0], 'ruek_ask' => [0],
            'notes' => ['สู่ขอ/ขอหมั้นใช้ทลิทโทฤกษ์ (ฤกษ์ของการขอ)', 'ตำราไม่แยกวัน/เดือนของงานหมั้น จึงใช้กฎเดียวกับแต่งงาน'],
        ],
        'ordain' => [
            'label' => 'บวช · อุปสมบท', 'mongkol' => true,
            'dithi_ban' => [['d' => 14, 'text' => 'ขึ้น/แรม 14 ค่ำ ("สงฆ์ 14" อัคนิโรธตกพัทธสีมา)']],
            'ruek_prefer' => [8],
            'notes' => ['ตำราส่วนใหญ่ใช้สมโณฤกษ์', 'ตำราฤกษ์ไม่ได้กำหนดวาร/เดือนเฉพาะสำหรับบวช'],
        ],
        'lasikkha' => [
            'label' => 'ลาสิกขา · สึก', 'mongkol' => true, 'ruek_prefer' => null,
            'dithi_ban' => [['d' => 14, 'text' => 'ขึ้น/แรม 14 ค่ำ ("สงฆ์ 14" ห้ามการที่เกี่ยวกับพระสงฆ์)']],
            'notes' => ['ตำราให้หาฤกษ์สึกตามดวงของผู้สึก', 'ตำราไม่ได้กำหนดวาร/เดือน/ฤกษ์บนเฉพาะสำหรับสึก จึงใช้เกณฑ์งานมงคลทั่วไป'],
        ],
        'merit' => [
            'label' => 'ทำบุญ · ทำบุญบ้าน', 'mongkol' => true,
            'dithi_ban' => [['d' => 14, 'text' => 'ขึ้น/แรม 14 ค่ำ (ห้ามนิมนต์พระ "สงฆ์ 14")']],
            'ruek_prefer' => [8],
            'notes' => ['งานกุศล/ทำบุญใช้สมโณฤกษ์', 'สะเดาะเคราะห์ต้องดูตามดวงของผู้นั้น'],
        ],
        'funeral' => [
            'label' => 'งานศพ · ฌาปนกิจ', 'mongkol' => false, 'ruek_prefer' => null,
            'wd_ban' => [5], 'wd_ban_text' => 'ตำราห้ามเผาศพวันศุกร์',
            'dithi_ban' => [['d' => 15, 'text' => 'ขึ้น/แรม 15 ค่ำ ("เผาศพ 15" — ถ้าตรงวันศุกร์ห้ามเด็ดขาด)']],
            'notes' => ['งานศพไม่ใช้ข้อห้ามงานมงคล และตำราไม่ได้กำหนดฤกษ์บน'],
        ],
        'worship' => [
            'label' => 'บวงสรวง · ไหว้ครู · บูชาเทพ', 'mongkol' => false, 'ruek_prefer' => null,
            'wd_good' => [4], 'wd_good_text' => 'ไหว้ครูวันพฤหัสบดี (วันครู)',
            'dithi_ban' => [['d' => 15, 'text' => 'ขึ้น/แรม 15 ค่ำ (อัคนิโรธตกศาลเทวดา ห้ามบวงสรวง ไหว้ครู บูชาเทพ)']],
            'notes' => ['ตำราไม่ได้กำหนดฤกษ์บน/เดือนสำหรับบวงสรวง'],
        ],
        'car' => [
            'label' => 'ออกรถใหม่', 'mongkol' => true, 'ruek_prefer' => null,
            'wd_ban' => [2, 6], 'wd_ban_text' => 'ตำราห้ามออกรถวันอังคาร เสาร์',
            'dithi_ban' => [['d' => 8, 'text' => 'ขึ้น/แรม 8 ค่ำ (อัคนิโรธตกยวดยาน ห้ามซื้อรถ/เอารถออก)']],
            'notes' => ['ต้องเลี่ยงวันกาลกิณีของเจ้าของรถด้วย', 'ฤกษ์บนสำหรับออกรถ ตำราขัดกัน (มหัทธโน / ภูมิปาโล) จึงไม่ชี้'],
        ],
        'house' => [
            'label' => 'ขึ้นบ้านใหม่', 'mongkol' => true,
            'wd_ban' => [2, 6], 'wd_ban_text' => 'ตำราห้ามขึ้นบ้านใหม่วันอังคาร เสาร์', 'wd_good' => [5], 'wd_good_text' => 'ตำราขึ้นบ้านใหม่นิยมวันศุกร์',
            'dithi_ban' => [['d' => 6, 'text' => 'ขึ้น/แรม 6 ค่ำ (อัคนิโรธตกบ้านเรือน)']],
            'months' => ['good' => [2, 4, 6, 12]],
            'ruek_prefer' => [1],
            'notes' => ['ถ้านิมนต์พระมาทำบุญ ห้ามขึ้น/แรม 14 ค่ำ', 'ปลูกเรือน/ยกเสาเอกเป็นอีกหมวด (กฎเดือนและวันต่างกัน)'],
        ],
        'pillar' => [
            'label' => 'ยกเสาเอก · ปลูกเรือน · ก่อสร้าง', 'mongkol' => true,
            // posttoday real-estate 36374 · wikibooks พิธีปลูกบ้าน · kapook 44142 · พลูหลวง · ศิลปวัฒนธรรม · astroneemo 924
            'wd_ban' => [0, 2, 5, 6], 'wd_ban_text' => 'ตำราห้ามปลูกเรือน/ยกเสาเอกวันอาทิตย์ อังคาร เสาร์ และไม่แนะนำวันศุกร์',
            'wd_good' => [1, 3, 4], 'wd_good_text' => 'ตำราปลูกเรือนให้วันจันทร์ พุธ พฤหัสบดี เป็นวันดี',
            'dithi_ban' => [['d' => 6, 'text' => 'ขึ้น/แรม 6 ค่ำ (อัคนิโรธตกบ้านเรือน ห้ามยกเสาเอก ปลูกบ้าน)'], ['d' => 9, 'text' => 'ขึ้น/แรม 9 ค่ำ (อัคนิโรธตกแผ่นดิน ห้ามขุดหลุม ฝังเสา)']],
            'months' => ['good' => [2, 4, 6, 9, 12], 'bad' => [3, 5, 7, 8, 10, 11], 'note' => [1 => 'เดือนอ้าย ตำราขัดกัน (2 แหล่งว่าดี 1 แหล่งว่าไม่ดี)']],
            'ruek_prefer' => [3],
            'notes' => ['ใช้ภูมิปาโลฤกษ์ (ผู้รักษาแผ่นดิน)', 'ทิศเสาเอกตามเดือน ยังหาแหล่งยืนยันไม่ได้ จึงไม่ชี้'],
        ],
        'shop' => [
            'label' => 'เปิดร้าน · เปิดกิจการ · ค้าขาย', 'mongkol' => true,
            'wd_ban' => [2, 6], 'wd_ban_text' => 'ตำราห้ามเปิดห้างร้าน/อาคารวันเสาร์ และไม่ให้ฤกษ์มงคลวันอังคาร',
            'lfj_good' => ['ฟู'], 'lfj_text' => 'วันฟู (พรหมชาติ) — เหมาะกับค้าขาย ทำมาหากิน',
            'ruek_prefer' => [1],
            'notes' => ['ร้านทอง ธนาคาร ห้างร้าน บริษัท ใช้มหัทธโนฤกษ์'],
        ],
        'travel' => [
            'label' => 'ออกเดินทาง · เดินทางไกล', 'mongkol' => false, 'ruek_prefer' => null,
            'wd_ban' => [2, 6], 'wd_ban_text' => 'ตำราห้ามออกเดินทางวันอังคาร เสาร์',
            'lfj_good' => ['ลอย'], 'lfj_ban' => ['จม'], 'lfj_text' => 'เดินทางใช้วันลอย ห้ามวันจม — ตำราพรหมชาติ',
            'akkhani_note' => [2, 3, 4, 8, 10],
            'travel_dirs' => true,
            'notes' => ['ฤกษ์บนสำหรับเดินทาง ตำราขัดกัน (โจโร / เทศาตรี) จึงไม่ชี้'],
        ],
        'topknot' => [
            'label' => 'โกนจุก · โกนผมไฟ', 'mongkol' => true, 'ruek_prefer' => null,
            'wd_ban' => [2, 6], 'wd_ban_text' => 'ตำราห้ามโกนจุกวันอังคาร (และไม่ให้ฤกษ์มงคลวันเสาร์)',
            'dithi_note' => [['d' => 11, 'text' => 'ขึ้น/แรม 11 ค่ำ ห้ามโกนจุกเด็กหญิง ("นารี 11")']],
            'wd_note' => [['wd' => 3, 'text' => 'วันพุธ ตำราห้ามตัดผม (ไม่ได้ระบุโกนจุกโดยตรง)']],
            'notes' => ['ฤกษ์ตามดวงของเด็กเป็นดวงเฉพาะบุคคล'],
        ],
        'land' => [
            'label' => 'ซื้อขายที่ดิน · ทำสัญญา', 'mongkol' => false,
            'dithi_ban' => [['d' => 5, 'text' => 'ขึ้น/แรม 5 ค่ำ (อัคนิโรธตกเขตที่ทาง ห้ามแบ่ง/ซื้อที่ดิน รังวัด)']],
            'ruek_prefer' => [3],
            'notes' => ['ที่ดินและการเช่าซื้อตำรานิยมภูมิปาโลฤกษ์'],
        ],
        'plant' => [
            'label' => 'ปลูกต้นไม้ · เพาะปลูก', 'mongkol' => false,
            'dithi_ban' => [['d' => 11, 'text' => 'ขึ้น/แรม 11 ค่ำ (อัคนิโรธตกพืชพรรณ ห้ามปลูก เพาะชำ หว่าน)']],
            'lfj_good' => ['ลอย', 'ฟู'], 'lfj_ban' => ['จม'], 'lfj_text' => 'เพาะปลูกใช้วันลอย/วันฟู ห้ามวันจม — ตำราพรหมชาติ',
            'wd_note' => [['wd' => 3, 'text' => 'วันพุธห้ามตัด (ดายหญ้า ตัดต้นไม้)'], ['wd' => 4, 'text' => 'วันพฤหัสบดีห้ามถอน (ถอนหญ้า ถอนกล้า)']],
            'ruek_prefer' => [3],
            'notes' => ['กสิกรรมตำรานิยมภูมิปาโลฤกษ์'],
        ],
        'shrine' => [
            'label' => 'ตั้งศาลพระภูมิ', 'mongkol' => true,
            'wd_ban_by_month' => [1 => [4, 6], 5 => [4, 6], 9 => [4, 6], 2 => [3, 5], 6 => [3, 5], 10 => [3, 5], 3 => [2], 7 => [2], 11 => [2], 4 => [1], 8 => [1], 12 => [1]],
            'dithi_ban' => [['d' => 9, 'text' => 'ขึ้น/แรม 9 ค่ำ (อัคนิโรธตกแผ่นดิน ห้ามขุดหลุมปลูกศาล)']],
            'ruek_prefer' => [3, 5, 1, 7],
            'notes' => ['ฤกษ์ตั้งศาล: ภูมิปาโล เทวี มหัทธโน ราชา', 'ทิศตั้งศาลยังหาแหล่งยืนยันไม่ได้ จึงไม่ชี้'],
        ],
    ];

    /**
     * ของที่จงใจไม่ใส่ พร้อมเหตุผล — ห้ามเติมเองจากความจำโดยไม่มีแหล่งยืนยัน ≥ 2 แหล่ง
     */
    public const OMITTED = [
        'ยามอุบากอง — ฉบับที่แพร่หลายขัดกันเรื่องความหมายสัญลักษณ์',
        'ฤกษ์เข้า / ฤกษ์ออก / ฤกษ์นคร / ฤกษ์ยายี — พบแค่ชื่อบท ยังไม่พบรายการนักษัตรจากแหล่งอิสระ',
        'ดาวเจ้าฤกษ์ของฤกษ์บน — ตำราใช้ 3 ระบบไม่ตรงกัน',
        'ดิถีพิฆาตวันศุกร์/เสาร์ · ดิถีทรธึกวันศุกร์ — แต่ละแหล่งให้ค่าต่างกัน',
        'วันลอย/ฟู/จม ตำราโหราศาสตร์ไทย — หากฎคำนวณไม่พบ ใช้ฉบับพรหมชาติแทน',
        'ทิศตั้งศาล · สีรถ · ทิศเสาเอกตามเดือน · ทิศเทวดาจร — ยังไม่พบแหล่งยืนยันที่เป็นอิสระต่อกัน',
        'รับตำแหน่ง · สมัครงาน · ย้ายบ้าน · ผ่าตัด · ผ่าคลอด · สะเดาะเคราะห์ — ต้องใช้ดวงบุคคล หรือยืนยันได้แหล่งเดียว',
        'ยามตามเวลาพระอาทิตย์ขึ้น–ตกจริง · แก้ยามตามเวลาท้องถิ่นแต่ละจังหวัด — แหล่งขัดกัน จึงใช้ 06:00/18:00 เวลามาตรฐาน',
    ];

    private PlanetEphemeris $eph;

    private ThaiAstrologyService $astro;

    /** memo ของ day() ต่อ instance — คีย์ = ymd|lat|lon */
    private array $dayMemo = [];

    public function __construct(?PlanetEphemeris $eph = null, ?ThaiAstrologyService $astro = null)
    {
        $this->eph = $eph ?? new PlanetEphemeris;
        $this->astro = $astro ?? new ThaiAstrologyService;
    }

    // ─────────────────────────────── จุลศักราช + กาลโยค ───────────────────────────────

    /** JD (UT) ของเวลาเถลิงศกปี จ.ศ. $cs */
    public static function thalerngsok(int $cs): float
    {
        return self::CS_EPOCH + ($cs * 292207 + 373) / 800 - self::LMT_BKK;
    }

    /** จ.ศ. ที่ครอบ JD (UT) นี้ (เปลี่ยนปีตอนเถลิงศก) */
    public static function csAt(float $jd): int
    {
        $cs = (int) floor(($jd + self::LMT_BKK - self::CS_EPOCH) * 800 / 292207);
        while (self::thalerngsok($cs + 1) <= $jd) {
            $cs++;
        }
        while (self::thalerngsok($cs) > $jd) {
            $cs--;
        }

        return $cs;
    }

    /**
     * กาลโยคของปี จ.ศ. $cs — ทั้ง 4 ชนิด × 5 ฐาน
     *
     * @return array<string, array{th:string, good:bool, wan:int, yam:int, rasi:int, dithi:int, ruek:int}>
     */
    public static function kalayok(int $cs): array
    {
        $out = [];
        foreach (self::KY as $key => $k) {
            $o = ['th' => $k['th'], 'good' => $k['good']];
            foreach (self::KY_BASE as $base => [$m, $off]) {
                [$a, $b] = $k[$base];
                $o[$base] = ((($a * $cs + $b) % $m) + $m) % $m + $off;
            }
            $out[$key] = $o;
        }

        return $out;
    }

    /**
     * สูตรฐานวันตามที่ตำราเขียน (บ้านคุณย่า · วิกิพีเดีย "กาลโยค") — เศษ 1 = อาทิตย์ … 0 = เสาร์
     * ใช้ตรวจว่าสูตรเชิงเส้นใน kalayok() ให้ค่าเดียวกับตำรา
     *
     * @return array<string, int> วัน 1=อาทิตย์ … 7=เสาร์
     */
    public static function kyTraditional(int $cs): array
    {
        $r = static function (int $x): int {
            $m = (($x % 7) + 7) % 7;

            return $m === 0 ? 7 : $m;
        };

        return [
            'thongchai' => $r($cs * 10 + 3),
            'athibodi' => $r($cs % 498),
            'ubat' => $r($cs * 10 + 2),
            'lokawinat' => $r($cs + 1120),
        ];
    }

    // ─────────────────────────────── ฤกษ์ล่าง ───────────────────────────────

    /**
     * วันลอย/ฟู/จม ตำราพรหมชาติ: เดือน 5 ลอย=อาทิตย์ ฟู=อังคาร จม=พฤหัส แล้วเลื่อน 1 วันทุกเดือน
     * (bloggang abhinop · trueid · dooasia)
     *
     * @param  int  $wd  0=อาทิตย์
     */
    public static function lfj(int $month, int $wd): ?string
    {
        $loi = (($month - 5 + 12) % 12) % 7;
        if ($wd === $loi) {
            return 'ลอย';
        }
        if ($wd === ($loi + 2) % 7) {
            return 'ฟู';
        }
        if ($wd === ($loi + 4) % 7) {
            return 'จม';
        }

        return null;
    }

    /**
     * ฤกษ์ล่างของวัน
     *
     * @param  int  $wd  0=อาทิตย์
     * @param  array|null  $L  ผลจาก ThaiLunarCalendar::of()
     * @param  int  $sun  ราศีอาทิตย์นิรายนะ (0=เมษ)
     * @return array{good:array, bad:array, info:array, day:int, month:?int, weekday_num:int, second_eighth:bool}|null
     */
    public static function lower(int $wd, ?array $L, int $sun): ?array
    {
        if ($L === null) {
            return null;
        }
        $d = (int) $L['day'];
        $m = $L['second_eighth'] ? null : (int) $L['month'];
        $n = $wd + 1;
        $good = [];
        $bad = [];
        $info = [];
        $day = 'วัน'.self::WD[$wd];
        $ka = $d.' ค่ำ';

        foreach (self::GOOD_DITHI as $g) {
            if ($g['by_wd'][$wd] === $d) {
                $good[] = ['key' => $g['key'], 'name' => $g['name'], 'rank' => $g['rank'], 'rule' => "{$day} {$ka}"];
            }
        }
        if ($m !== null && (self::MAHASUN_MONTH[$m] ?? null) === $d) {
            $bad[] = ['key' => 'mahasun', 'name' => 'ดิถีมหาสูญ', 'rule' => 'เดือน'.ThaiLunarCalendar::MONTH_TH[$m]." {$ka} (พรหมชาติ)", 'variant' => 'ข'];
        }
        if (self::MAHASUN_SUN[$sun] === $d) {
            $bad[] = ['key' => 'mahasun', 'name' => 'ดิถีมหาสูญ', 'rule' => 'อาทิตย์ราศี'.PlanetEphemeris::SIGNS[$sun]." {$ka} (อ.ทองเจือ)", 'variant' => 'ก'];
        }
        if (self::PHIKHAT[$wd] === $d) {
            $bad[] = ['key' => 'phikhat', 'name' => 'ดิถีพิฆาต', 'rule' => "{$day} {$ka}"];
        }
        if (self::THORATHUEK[$wd] === $d) {
            $bad[] = ['key' => 'thorathuek', 'name' => 'ดิถีทรธึก', 'rule' => "{$day} {$ka}"];
        }
        $pi = $m !== null ? array_search($d, self::PHLAI[$m] ?? [], true) : false;
        if ($pi !== false) {
            $bad[] = ['key' => 'phlai', 'name' => 'ดิถีอายกรรมพลาย'.self::PHLAI_LV[$pi], 'rule' => 'เดือน'.ThaiLunarCalendar::MONTH_TH[$m]." {$ka}", 'level' => $pi + 1];
        }
        if ($m !== null && $n === $m && $m === $d) {
            $bad[] = ['key' => 'krathing', 'name' => 'กระทิงวัน', 'rule' => "เลขวัน = เลขเดือน = ดิถี = {$d} ({$day} เดือน".ThaiLunarCalendar::MONTH_TH[$m]." {$ka})"];
        }
        // เสาร์ 5: ห้ามงานมงคล ใช้ได้เฉพาะปลุกเสก/พุทธาภิเษก (วัดท่าขนุน · astroneemo 775) — ชนะดิถีโชคของวันเสาร์ 5 ค่ำ
        if ($wd === 6 && $d === 5) {
            $bad[] = ['key' => 'sao5', 'name' => 'เสาร์ 5', 'rule' => 'วันเสาร์ ขึ้น/แรม 5 ค่ำ — ใช้ได้เฉพาะปลุกเสก พุทธาภิเษก'];
        }
        if ($L['waxing']) {
            $isGood = in_array($d, self::MAHALUEK_WAX['good'], true);
            $info[] = ['key' => 'mahaluek', 'name' => 'มหาฤกษ์ (พรหมชาติ): '.($isGood ? 'ดี' : 'ไม่ดี'), 'rule' => "ขึ้น {$ka}", 'value' => $isGood ? 'ดี' : 'ไม่ดี'];
        }
        if (in_array($d, self::RIANGMON[$L['waxing'] ? 'waxing' : 'waning'], true)) {
            $info[] = ['key' => 'riangmon', 'name' => 'ดิถีเรียงหมอน', 'rule' => ($L['waxing'] ? 'ขึ้น' : 'แรม')." {$ka}"];
        }
        if (isset(self::AKKHANI[$d])) {
            [$place, $forbid] = self::AKKHANI[$d];
            $info[] = ['key' => 'akkhani', 'name' => 'อัคนิโรธตกลงใน'.$place, 'rule' => "{$ka} — ห้าม{$forbid}", 'place' => $place, 'forbid' => $forbid, 'd' => $d];
        }
        $w = $m !== null ? self::lfj($m, $wd) : null;
        if ($w !== null) {
            $info[] = ['key' => 'lfj', 'name' => 'วัน'.$w, 'rule' => "{$day} ในเดือน".ThaiLunarCalendar::MONTH_TH[$m].' (พรหมชาติ)', 'value' => $w];
        }
        if ($L['holy']) {
            $info[] = ['key' => 'holy', 'name' => 'วันพระ', 'rule' => $L['label']];
        }

        return ['good' => $good, 'bad' => $bad, 'info' => $info, 'day' => $d, 'month' => $m, 'weekday_num' => $n, 'second_eighth' => (bool) $L['second_eighth']];
    }

    // ─────────────────────────────── ยามอัฏฐกาล ───────────────────────────────

    /**
     * เจ้ายามช่องที่ $slot (0–7) ของวันที่ดาวเจ้าวันเป็น $dayLord (1–7)
     * ยามแรกทั้งสองฟาก = ดาวเจ้าวัน แล้วลงเลข กลางวัน +5 · กลางคืน +4 (เกิน 7 ลบ 7)
     *   อาทิตย์กลางวัน 1 6 4 2 7 5 3 1 · อาทิตย์กลางคืน 1 5 2 6 3 7 4 1
     *   แหล่งที่ตรงกัน: thai-dd.blogspot · bloggang · horasardthaipatana · 7dara · dooasia/พรหมชาติ
     */
    public static function yamLord(int $dayLord, int $slot, bool $night): int
    {
        $step = $night ? 4 : 5;

        return (($dayLord - 1 + $step * $slot) % 7) + 1;
    }

    /**
     * ยาม ณ ชั่วโมง $hour (0–24 เวลาไทย) ของวันปฏิทิน $calendarWd
     * วันทางโหรเปลี่ยนตอน 06:00 · กลางวัน 06:00–18:00 · กลางคืน 18:00–06:00 · ยามละ 1 ชม. 30 นาที
     * เจ้ายามใช้ดาวเจ้าวันปกติ (พุธกลางคืนเริ่มยามด้วยพุธ ไม่ใช่ราหู)
     *
     * @return array{index:int, lord:int, night:bool, name:string, lord_th:string}
     */
    public static function yamAt(int $calendarWd, float $hour): array
    {
        $dow = $hour < 6.0 ? ($calendarWd + 6) % 7 : $calendarWd;
        $night = $hour >= 18.0 || $hour < 6.0;
        $sinceStart = $night ? ($hour >= 18.0 ? $hour - 18.0 : $hour + 6.0) : $hour - 6.0;
        $slot = min(7, (int) floor($sinceStart / 1.5));
        $lord = self::yamLord($dow + 1, $slot, $night);

        return [
            'index' => $slot + 1,
            'lord' => $lord,
            'night' => $night,
            'name' => ($night ? self::YAM_NIGHT : self::YAM_DAY)[$lord],
            'lord_th' => self::PLANET_TH[$lord],
        ];
    }

    // ─────────────────────────────── ฤกษ์บน ───────────────────────────────

    /** ลองจิจูดนิรายนะของจันทร์ ณ JD (UT) */
    public function moonSidereal(float $jd): float
    {
        return self::norm($this->eph->longitude('Moon', $jd) - $this->eph->ayanamsa($jd));
    }

    /** ลองจิจูดนิรายนะของอาทิตย์ ณ JD (UT) */
    public function sunSidereal(float $jd): float
    {
        return self::norm($this->eph->longitude('Sun', $jd) - $this->eph->ayanamsa($jd));
    }

    /** นักษัตรที่จันทร์สถิต (1–27) */
    public function ruekN(float $jd): int
    {
        return (int) floor($this->moonSidereal($jd) / self::NAK_SPAN) % 27 + 1;
    }

    /** ข้อมูลฤกษ์ของนักษัตรที่ n (1–27) */
    public static function ruekInfo(int $n): array
    {
        $idx = ($n - 1) % 9;

        return ['n' => $n, 'nak' => self::NAKSHATRA[$n - 1], 'idx' => $idx] + self::RUEK9[$idx];
    }

    // ─────────────────────────────── วิเคราะห์รายวัน ───────────────────────────────

    /**
     * วิเคราะห์วันปฏิทิน $ymd (00:00–24:00 เวลาไทย) ณ พิกัดสถานที่จัดงาน
     *
     * @return array ทุกอย่างที่ evaluate()/classify() ต้องใช้ — ดูคีย์ท้ายเมธอด
     */
    public function day(string $ymd, float $lat = self::DEFAULT_LAT, float $lon = self::DEFAULT_LON): array
    {
        $memoKey = $ymd.'|'.$lat.'|'.$lon;
        if (isset($this->dayMemo[$memoKey])) {
            return $this->dayMemo[$memoKey];
        }

        $date = new \DateTimeImmutable($ymd.' 00:00:00', new \DateTimeZone('UTC'));
        $wd = (int) $date->format('w');
        $jd0 = self::jdAt($ymd, 0);
        $jd1 = self::jdAt($ymd, 24);
        $noon = self::jdAt($ymd, 12);
        $L = ThaiLunarCalendar::of($date);
        $sun = (int) floor($this->sunSidereal($noon) / 30) % 12;

        // วันเถลิงศกนับเป็นปีใหม่ทั้งวัน (ตรงปฏิทิน myhora 2568–2569)
        $csDay = self::csAt($jd1 - 1e-7);
        $ky = self::kalayok($csDay);
        $dithiNum = $L !== null ? ($L['waxing'] ? $L['day'] : 15 + $L['day']) : null;

        $kyDay = [];
        foreach ($ky as $key => $v) {
            if ($v['wan'] === $wd + 1) {
                $kyDay[] = ['key' => $key] + $v;
            }
        }

        $ruekWin = [];
        foreach ($this->scan(fn (float $jd): int => $this->ruekN($jd), $jd0, $jd1, 60) as $s) {
            $ruekWin[] = [
                'from' => $s['from'], 'to' => $s['to'],
                'from_hm' => self::hmOf($s['from']), 'to_hm' => $s['to'] >= $jd1 - 1e-9 ? '24:00' : self::hmOf($s['to']),
            ] + self::ruekInfo($s['key']);
        }

        $lagnaWin = [];
        foreach ($this->scan(fn (float $jd): int => (int) floor($this->astro->siderealLagnaAtJd($jd, $lat, $lon, $this->eph) / 30) % 12, $jd0, $jd1, 10) as $s) {
            $lagnaWin[] = [
                'from' => $s['from'], 'to' => $s['to'],
                'from_hm' => self::hmOf($s['from']), 'to_hm' => $s['to'] >= $jd1 - 1e-9 ? '24:00' : self::hmOf($s['to']),
                'sign' => $s['key'], 'name' => PlanetEphemeris::SIGNS[$s['key']],
            ];
        }

        // 16 ช่วงยามของวันปฏิทิน: 00:00–06:00 = ยามกลางคืนที่ 5–8 ของคืนวันก่อน
        $yams = [];
        for ($k = 0; $k < 16; $k++) {
            $a = $jd0 + $k * 1.5 / 24;
            $b = $a + 1.5 / 24;
            $yv = self::yamAt($wd, $k * 1.5 + 0.75);
            $yams[] = [
                'from' => $a, 'to' => $b,
                'from_hm' => self::hmOf($a), 'to_hm' => $k === 15 ? '24:00' : self::hmOf($b),
                'slot' => $yv['index'], 'night' => $yv['night'], 'lord' => $yv['lord'], 'lord_th' => $yv['lord_th'], 'name' => $yv['name'],
                'prev_day' => $k < 4,
            ];
        }

        // ช่วงเวลาย่อย = รอยต่อยาม ∪ ฤกษ์เปลี่ยน ∪ ลัคนาเปลี่ยน
        $cuts = [$jd0, $jd1];
        foreach (array_merge($yams, $ruekWin, $lagnaWin) as $s) {
            $cuts[] = $s['from'];
            $cuts[] = $s['to'];
        }
        sort($cuts);
        $pts = [];
        foreach ($cuts as $v) {
            if ($pts === [] || $v - end($pts) > 20 / 86400) {
                $pts[] = $v;
            }
        }

        $segs = [];
        for ($i = 0; $i < count($pts) - 1; $i++) {
            $a = $pts[$i];
            $b = $pts[$i + 1];
            $mid = ($a + $b) / 2;
            $yv = self::findCovering($yams, $mid);
            $rv = self::findCovering($ruekWin, $mid);
            $lv = self::findCovering($lagnaWin, $mid);
            $csMid = self::csAt($mid);
            $kyi = self::kalayok($csMid);
            $tags = [];
            foreach ($kyi as $key => $v) {
                if ($yv !== null && $v['yam'] === $yv['slot']) {
                    $tags[] = ['base' => 'ยาม', 'key' => $key, 'th' => $v['th'], 'good' => $v['good']];
                }
                if ($rv !== null && $v['ruek'] === $rv['n']) {
                    $tags[] = ['base' => 'ฤกษ์', 'key' => $key, 'th' => $v['th'], 'good' => $v['good']];
                }
                if ($lv !== null && $v['rasi'] === $lv['sign']) {
                    $tags[] = ['base' => 'ลัคนา', 'key' => $key, 'th' => $v['th'], 'good' => $v['good']];
                }
            }
            $segs[] = [
                'from' => $a, 'to' => $b,
                'from_hm' => self::hmOf($a), 'to_hm' => $b >= $jd1 - 1e-9 ? '24:00' : self::hmOf($b),
                'yam' => $yv, 'ruek' => $rv, 'lagna' => $lv, 'ky' => $tags, 'cs' => $csMid,
            ];
        }

        return $this->dayMemo[$memoKey] = [
            'ymd' => $ymd, 'wd' => $wd, 'weekday' => self::WD[$wd], 'lat' => $lat, 'lon' => $lon,
            'lunar' => $L, 'sun' => $sun, 'sun_sign' => PlanetEphemeris::SIGNS[$sun],
            'cs' => $csDay, 'be' => (int) $date->format('Y') + 543, 'kalayok' => $ky, 'ky_day' => $kyDay, 'dithi_num' => $dithiNum,
            'lower' => self::lower($wd, $L, $sun), 'ruek_win' => $ruekWin, 'lagna_win' => $lagnaWin, 'yams' => $yams, 'segs' => $segs,
        ];
    }

    /**
     * ประเมินวันหนึ่งสำหรับงาน $actKey — คืนรายการเหตุผลรายวัน + ช่วงเวลาที่ผ่านเกณฑ์
     *
     * @param  array  $D  ผลจาก day()
     * @return array{act:string, label:string, mongkol:bool, items:array, day_ok:bool, segs:array}
     */
    public static function evaluate(string $actKey, array $D): array
    {
        $A = self::ACTIVITIES[$actKey] ?? self::ACTIVITIES['general'];
        $items = [];
        $low = $D['lower'];
        $L = $D['lunar'];
        $wd = $D['wd'];
        $add = static function (string $kind, string $text, string $tag, ?string $short = null) use (&$items): void {
            $items[] = ['kind' => $kind, 'text' => $text, 'tag' => $tag, 'short' => $short ?? $text];
        };
        $wdName = 'วัน'.self::WD[$wd];
        $monthName = static fn (int $m): string => 'เดือน'.ThaiLunarCalendar::MONTH_TH[$m];
        $mongkol = (bool) ($A['mongkol'] ?? false);

        if (in_array($wd, $A['wd_ban'] ?? [], true)) {
            $add('ban', $A['wd_ban_text'], 'วาร', $wdName);
        } elseif ($mongkol && in_array($wd, self::MONGKOL_WD_BAN, true)) {
            $add('ban', "{$wdName} — ตำราไม่ให้ฤกษ์งานมงคลวันอังคารและวันเสาร์ (พลูหลวง)", 'วาร', $wdName);
        }
        foreach ($A['wd_note'] ?? [] as $r) {
            if ($r['wd'] === $wd) {
                $add('note', $r['text'], 'วาร', $wdName);
            }
        }
        if (! empty($A['travel_dirs'])) {
            $add('info', "ทิศหลาวเหล็ก{$wdName}: ห้ามเดินทางไปทิศ".self::DIR_LAOLEK[$wd], 'ทิศ');
            $add('info', "ทิศผีหลวง{$wdName}: อย่าไปหรือหันหน้าไปทิศ".self::DIR_PHILUANG[$wd], 'ทิศ');
        }
        if (! empty($A['wd_ban_by_month']) && $low !== null && $low['month'] !== null
            && in_array($wd, $A['wd_ban_by_month'][$low['month']], true)) {
            $banDays = implode(' ', array_map(static fn (int $i): string => self::WD[$i], $A['wd_ban_by_month'][$low['month']]));
            $add('ban', $monthName($low['month'])." ห้ามตั้งศาลวัน{$banDays}", 'วาร', "{$wdName}ใน".$monthName($low['month']));
        }
        if (in_array($wd, $A['wd_good'] ?? [], true)) {
            $add('good', $A['wd_good_text'], 'วาร', $wdName);
        }

        if ($low !== null) {
            foreach ($A['dithi_ban'] ?? [] as $r) {
                if ($low['day'] === $r['d'] && (! isset($r['wd']) || $r['wd'] === $wd)) {
                    $add('ban', $r['text'], 'ดิถี', $r['d'].' ค่ำ'.(isset($r['wd']) ? ' '.$wdName : ''));
                }
            }
            foreach ($A['dithi_note'] ?? [] as $r) {
                if ($low['day'] === $r['d']) {
                    $add('note', $r['text'], 'ดิถี', $r['d'].' ค่ำ');
                }
            }
            if (in_array($low['day'], $A['akkhani_note'] ?? [], true)) {
                [$place, $forbid] = self::AKKHANI[$low['day']];
                $add('note', "{$low['day']} ค่ำ อัคนิโรธตกลงใน{$place} — ห้าม{$forbid}", 'ดิถี', 'อัคนิโรธ'.$place);
            }
            $lv = $low['month'] !== null ? self::lfj($low['month'], $wd) : null;
            if ($lv !== null && in_array($lv, $A['lfj_ban'] ?? [], true)) {
                $add('ban', "วัน{$lv} — ".$A['lfj_text'], 'ฤกษ์ล่าง', 'วัน'.$lv);
            } elseif ($lv !== null && in_array($lv, $A['lfj_good'] ?? [], true)) {
                $add('good', "วัน{$lv} — ".$A['lfj_text'], 'ฤกษ์ล่าง', 'วัน'.$lv);
            }
            if (! empty($A['riangmon'])) {
                foreach ($low['info'] as $x) {
                    if ($x['key'] === 'riangmon') {
                        $add('good', $x['name'].' — '.$x['rule'], 'ดิถี', 'เรียงหมอน');
                    }
                }
            }
            if (! empty($A['months'])) {
                $m = $low['month'];
                $months = $A['months'];
                if ($m === null) {
                    $add('note', 'เดือน 8 หลัง (ปีอธิกมาส) — ตารางเดือนไม่ได้ระบุ', 'เดือน', 'เดือน 8 หลัง');
                } elseif (isset($months['note'][$m])) {
                    $add('note', $months['note'][$m], 'เดือน', $monthName($m));
                } elseif (in_array($m, $months['good'], true)) {
                    $add('good', $monthName($m).' เป็นเดือนที่ตำรานิยม', 'เดือน', $monthName($m));
                } elseif (in_array($m, $months['bad'] ?? [], true)) {
                    $add('ban', $monthName($m).' ตำราให้เลี่ยง', 'เดือน', $monthName($m));
                } elseif (isset($months['cond'][$m])) {
                    $waxing = $L !== null && $L['waxing'];
                    $add($waxing ? 'good' : 'ban', $months['cond'][$m], 'เดือน', $monthName($m).($waxing ? '' : ' (เข้าพรรษาแล้ว)'));
                } else {
                    $add('note', $monthName($m).' ไม่ใช่เดือนที่ตำรานิยม', 'เดือน', $monthName($m));
                }
            }
        }

        if ($mongkol) {
            if ($low !== null) {
                foreach ($low['bad'] as $b) {
                    $add('ban', "{$b['name']}: {$b['rule']} — ห้ามงานมงคล", 'ฤกษ์ล่าง', str_replace('ดิถี', '', $b['name']));
                }
                foreach ($low['good'] as $g) {
                    $add('good', "{$g['name']}: {$g['rule']}", 'ฤกษ์ล่าง', str_replace('ดิถี', '', $g['name']));
                }
                if (empty($A['lfj_good']) && empty($A['lfj_ban'])) {
                    foreach ($low['info'] as $x) {
                        if ($x['key'] === 'lfj' && $x['value'] === 'จม') {
                            $add('note', 'วันจม (พรหมชาติ) — ตำราว่าไม่ควรเริ่มงานมงคลใหม่', 'ฤกษ์ล่าง', 'วันจม');
                        }
                        if ($x['key'] === 'lfj' && $x['value'] === 'ลอย') {
                            $add('good', 'วันลอย (พรหมชาติ) — ดีสำหรับเริ่มต้น', 'ฤกษ์ล่าง', 'วันลอย');
                        }
                    }
                }
                foreach ($low['info'] as $x) {
                    if ($x['key'] === 'mahaluek') {
                        $add($x['value'] === 'ดี' ? 'good' : 'note', "{$x['name']} — {$x['rule']} (ข้อมูลประกอบ ตำราอื่นอาจให้ต่าง)", 'ฤกษ์ล่าง', $x['value'] === 'ดี' ? 'มหาฤกษ์' : 'มหาฤกษ์ไม่ดี');
                    }
                }
            } else {
                $add('note', 'ไม่มีข้อมูลปฏิทินหลวงของวันนี้ จึงตรวจฤกษ์ล่างไม่ได้', 'ฤกษ์ล่าง');
            }
            foreach ($D['ky_day'] as $k) {
                $add($k['good'] ? 'good' : 'ban', "วัน{$k['th']}ประจำปี (กาลโยค จ.ศ. {$D['cs']})", 'กาลโยค', "วัน{$k['th']}");
            }
        }

        foreach ($A['notes'] ?? [] as $note) {
            $add('info', $note, 'หมายเหตุ');
        }

        // รายช่วงเวลา
        $prefer = $A['ruek_prefer'] ?? null;
        $ask = $A['ruek_ask'] ?? [];
        $segs = [];
        foreach ($D['segs'] as $s) {
            $why = [];
            $ok = true;
            $star = false;
            $r = $s['ruek'];
            if ($mongkol) {
                if (! $r['good']) {
                    $ok = false;
                    $why[] = $r['short'].' (ฤกษ์คร่อมราศี)';
                }
                if ($prefer !== null && in_array($r['idx'], $prefer, true)) {
                    $star = true;
                    $why[] = $r['short'].' ตรงงาน';
                } elseif ($prefer !== null && ! in_array($r['idx'], $ask, true) && $r['good']) {
                    $why[] = $r['short'];
                }
                if ($r['ask_only'] && ! in_array($r['idx'], $ask, true) && $prefer === null) {
                    $why[] = 'ทลิทโทใช้กับการขอเท่านั้น';
                }
                foreach ($s['ky'] as $t) {
                    if (! $t['good']) {
                        $ok = false;
                    } else {
                        $star = true;
                    }
                    $why[] = $t['base'].$t['th'];
                }
            } elseif ($prefer !== null && in_array($r['idx'], $prefer, true)) {
                $star = true;
                $why[] = $r['short'].' ตรงงาน';
            }
            $segs[] = $s + ['ok' => $ok, 'star' => $star, 'why' => $why];
        }

        $dayOk = true;
        foreach ($items as $x) {
            if ($x['kind'] === 'ban') {
                $dayOk = false;
                break;
            }
        }

        return ['act' => $actKey, 'label' => $A['label'], 'mongkol' => $mongkol, 'items' => $items, 'day_ok' => $dayOk, 'segs' => $segs];
    }

    /**
     * จัดวัน: good = ไม่ติดข้อห้าม (งานมงคลต้องมีช่วงผ่านเกณฑ์ระหว่าง 06:00–18:00 ยาว ≥ 15 นาทีด้วย)
     *          bad = ติดข้อห้าม หรือไม่มีช่วงเวลาผ่าน · na = ไม่มีข้อมูลปฏิทินหลวง
     *
     * @return array{cls:string, reasons:array, goods:array, wins:array, stars?:int}
     */
    public static function classify(array $ev, array $D): array
    {
        $goods = [];
        $bans = [];
        foreach ($ev['items'] as $x) {
            if ($x['kind'] === 'good') {
                $goods[] = $x['short'];
            } elseif ($x['kind'] === 'ban') {
                $bans[] = $x['short'];
            }
        }
        if ($D['lunar'] === null) {
            return ['cls' => 'na', 'reasons' => ['ไม่มีข้อมูลปฏิทินหลวง'], 'goods' => $goods, 'wins' => []];
        }

        $d6 = self::jdAt($D['ymd'], 6);
        $d18 = self::jdAt($D['ymd'], 18);
        $wins = [];
        foreach ($ev['segs'] as $s) {
            if (! $s['ok'] || $s['to'] <= $d6 || $s['from'] >= $d18) {
                continue;
            }
            $f = max($s['from'], $d6);
            $t = min($s['to'], $d18);
            $last = count($wins) - 1;
            if ($last >= 0 && abs($wins[$last]['to'] - $f) < 1e-6) {
                $wins[$last]['to'] = $t;
                $wins[$last]['star'] = $wins[$last]['star'] || $s['star'];
            } else {
                $wins[] = ['from' => $f, 'to' => $t, 'star' => $s['star']];
            }
        }
        foreach ($wins as &$w) {
            $w['from_hm'] = self::hmOf($w['from']);
            $w['to_hm'] = $w['to'] >= $d18 - 1e-9 ? '18:00' : self::hmOf($w['to']);
        }
        unset($w);
        // ช่วงสั้นกว่า 15 นาทีใช้ทำพิธีจริงไม่ได้
        $wins = array_values(array_filter($wins, static fn (array $w): bool => ($w['to'] - $w['from']) * 1440 >= 15));

        if ($bans !== []) {
            return ['cls' => 'bad', 'reasons' => array_values(array_unique($bans)), 'goods' => $goods, 'wins' => $wins];
        }
        if ($ev['mongkol'] && $wins === []) {
            return ['cls' => 'bad', 'reasons' => ['ไม่มีช่วงเวลาผ่านเกณฑ์ 06:00–18:00'], 'goods' => $goods, 'wins' => $wins];
        }

        return [
            'cls' => 'good', 'reasons' => [], 'goods' => array_values(array_unique($goods)), 'wins' => $wins,
            'stars' => count(array_filter($wins, static fn (array $w): bool => $w['star'])),
        ];
    }

    /**
     * หาวันดีของงาน $actKey ใน $n วันเริ่มจาก $ymd (รวมวันนั้น) — เกณฑ์เดียวกับ classify()
     *
     * @return array<int, array{ymd:string, D:array, ev:array, goods:array, stars:int, wins:array}>
     */
    public function findDays(string $actKey, string $ymd, int $n, float $lat = self::DEFAULT_LAT, float $lon = self::DEFAULT_LON): array
    {
        $out = [];
        $start = new \DateTimeImmutable($ymd.' 00:00:00', new \DateTimeZone('UTC'));
        for ($i = 0; $i < $n; $i++) {
            $d = $start->modify("+{$i} day")->format('Y-m-d');
            $D = $this->day($d, $lat, $lon);
            $ev = self::evaluate($actKey, $D);
            $c = self::classify($ev, $D);
            if ($c['cls'] !== 'good') {
                continue;
            }
            $out[] = ['ymd' => $d, 'D' => $D, 'ev' => $ev, 'goods' => $c['goods'], 'stars' => $c['stars'] ?? 0, 'wins' => $c['wins']];
        }

        return $out;
    }

    // ─────────────────────────────── เวลา ───────────────────────────────

    /** JD (UT) ของวันที่ $ymd เวลาไทย $hours นาฬิกา */
    public static function jdAt(string $ymd, float $hours): float
    {
        $ts = (new \DateTimeImmutable($ymd.' 00:00:00', new \DateTimeZone('UTC')))->getTimestamp();

        return $ts / 86400 + 2440587.5 + ($hours - 7) / 24;
    }

    /** เวลาไทย HH:MM ของ JD (UT) — ปัดเป็นนาที */
    public static function hmOf(float $jd): string
    {
        $min = (int) round(($jd - 2440587.5) * 1440) + 7 * 60;
        $min = (($min % 1440) + 1440) % 1440;

        return sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
    }

    /**
     * แบ่งช่วง [jd0, jd1) ตามค่าที่ $fn คืน (หาจุดเปลี่ยนแบบแบ่งครึ่งจนละเอียด 1 วินาที)
     *
     * @return array<int, array{from:float, to:float, key:int}>
     */
    private function scan(callable $fn, float $jd0, float $jd1, float $stepMin): array
    {
        $out = [];
        $step = $stepMin / 1440;
        $t = $jd0;
        $k = $fn($t);
        $start = $jd0;
        while ($t < $jd1) {
            $t2 = min($jd1, $t + $step);
            $k2 = $fn($t2);
            if ($k2 !== $k) {
                $lo = $t;
                $hi = $t2;
                while (($hi - $lo) * 86400 > 1) {
                    $mid = ($lo + $hi) / 2;
                    if ($fn($mid) === $k) {
                        $lo = $mid;
                    } else {
                        $hi = $mid;
                    }
                }
                $out[] = ['from' => $start, 'to' => $hi, 'key' => $k];
                $start = $hi;
                $t = $hi;
                $k = $fn($hi);
            } else {
                $t = $t2;
            }
        }
        $out[] = ['from' => $start, 'to' => $jd1, 'key' => $k];

        return $out;
    }

    /** แถวแรกที่ครอบ $jd (from ≤ jd < to) */
    private static function findCovering(array $rows, float $jd): ?array
    {
        foreach ($rows as $r) {
            if ($jd >= $r['from'] && $jd < $r['to']) {
                return $r;
            }
        }

        return null;
    }

    private static function norm(float $deg): float
    {
        $d = fmod($deg, 360.0);

        return $d < 0 ? $d + 360.0 : $d;
    }
}
