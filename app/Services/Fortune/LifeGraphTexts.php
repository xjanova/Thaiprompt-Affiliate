<?php

namespace App\Services\Fortune;

/**
 * 📜 คำพยากรณ์ตามตำราสำหรับกราฟชีวิต — สำเนาตรงตัวจาก GenLotto (site/app/Astro/LifeGraphTexts.php) แก้ที่ GenLotto ก่อน
 *
 * กติกา: ใส่เฉพาะใจความที่ ≥ 2 แหล่งตรงกัน · เรียบเรียงใหม่ ไม่คัดลอกข้อความต้นฉบับ
 *   · ตัดรายละเอียดที่แหล่งไม่ตรงกัน (ทิศ สีผิวคน ลำดับได้-เสีย) · คำทำนายเรื่องความตายเขียนเป็น "ระวังสุขภาพ…"
 *   · คู่ที่ตำราขัดกัน = null (ไม่มีข้อความ ไม่มีคะแนน) และอยู่ใน LifeGraph::OMITTED
 * src = คีย์ใน SOURCES · tone ดี/ร้าย/ผสม (ผสม = ได้แล้วเสีย หรือร้ายก่อนดีภายหลัง)
 */
final class LifeGraphTexts
{
    public const SOURCES = [
        'phromchat' => ['title' => 'ตำราพรหมชาติ ว่าด้วยเทวดาเสวยอายุ (ร้อยแก้ว) — วิทยาทาน/arnoon266 · ajanton (ฉบับเรียบเรียง)', 'url' => 'https://ajanton.blogspot.com/2015/06/blog-post_1.html'],
        'horasatthai' => ['title' => 'horasatthai — มหาทักษา ๘ องค์ (ตารางคำพยากรณ์ 64 คู่)', 'url' => 'https://horasatthai.com/horasat/maha-taksa'],
        'thasung' => ['title' => 'หนังสือพิธีรับพระเคราะห์เสวยอายุ วัดท่าซุง (ผ่าน blockdit "เจ้าหนูจำไม" 2564)', 'url' => 'https://www.blockdit.com/posts/6022c60d23a0d109a9749bef'],
        'amnat' => ['title' => 'ตำราพรหมชาติ ฉบับอำนาจสาส์น (astroneemo.net)', 'url' => 'https://astroneemo.net/index.php?Itemid=120&catid=131%3A2010-08-25-03-54-03&id=875%3A2010-08-25-04-07-05&option=com_content&view=article'],
        'tewada' => ['title' => 'วิทยาทาน — เทวดาเสวยอายุตามวัน (คนละสำนวนกับพรหมชาติ)', 'url' => 'https://sites.google.com/view/arnoon266/%E0%B8%AB%E0%B8%99%E0%B8%B2%E0%B9%81%E0%B8%A3%E0%B8%81/%E0%B9%80%E0%B8%97%E0%B8%A7%E0%B8%94%E0%B8%B2%E0%B9%80%E0%B8%AA%E0%B8%A7%E0%B8%A2%E0%B8%AD%E0%B8%B2%E0%B8%A2%E0%B8%95%E0%B8%B2%E0%B8%A1%E0%B8%A7%E0%B8%99'],
        'punnapron' => ['title' => 'punnapron2013 — ทายโชคและทายเคราะห์ขณะพระเคราะห์เสวยอายุ', 'url' => 'http://punnapron2013.blogspot.com/2013/11/blog-post_4306.html'],
        'silpa' => ['title' => 'ศิลปวัฒนธรรม — เทพนพเคราะห์ และสำนวน "พระศุกร์เข้า พระเสาร์แทรก"', 'url' => 'https://www.silpa-mag.com/culture/article_136672'],
        'sanook' => ['title' => 'sanook ดูดวง — ดาวเสวยอายุ (เครดิต pookazza; tnews/teenee เป็นสำเนา)', 'url' => 'https://www.sanook.com/horoscope/76865/'],
        'bloggang' => ['title' => 'bloggang (morpim) — ทักษาจร และภพที่ให้โทษ อริ มรณะ วินาศ', 'url' => 'https://www.bloggang.com/m/viewblog.php?id=morpim&group=10'],
        'joop' => ['title' => 'หมอจุ๊บ — กลวิธีการนับทักษา (ทักษาจร)', 'url' => 'http://joop-astro.blogspot.com/2011/07/blog-post_6023.html'],
        'saan' => ['title' => 'สอ้าน นาคเพชรพูล — ดูดวงทางโทรศัพท์ บทที่ ๖ (10luc)', 'url' => 'http://www.10luc.com/%E0%B8%94%E0%B8%B9%E0%B8%94%E0%B8%A7%E0%B8%87%E0%B8%97%E0%B8%B2%E0%B8%87%E0%B9%82%E0%B8%97%E0%B8%A3%E0%B8%A8%E0%B8%B1%E0%B8%9E%E0%B8%97%E0%B9%8C-%E0%B8%9A%E0%B8%97%E0%B8%97%E0%B8%B5%E0%B9%88-%E0%B9%96/'],
        'sarakadee' => ['title' => 'สารคดี — ทักษาพยากรณ์ (ตารางภูมิตามพรหมชาติ)', 'url' => 'https://www.sarakadee.com/2025/04/09/taksapayakorn/'],
        'gotoknow' => ['title' => 'gotoknow — ความหมายภูมิทักษา', 'url' => 'https://www.gotoknow.org/posts/678036'],
        'sunwasa' => ['title' => 'ศรรวสา — ภูมิของดาวเสวยอายุตายตัวตามลำดับ อ่านช่วงวัยจากชื่อภูมิไม่ได้', 'url' => 'https://sunwasa.wordpress.com/2008/02/05/%E0%B8%95%E0%B8%AD%E0%B8%99%E0%B8%97%E0%B8%B5%E0%B9%88/'],
    ];

    /** ความหมายภูมิทักษา (เรื่องในชีวิตที่ภูมินั้นดูแล) — ใจความที่หลายแหล่งตรงกัน */
    public const BHAVA = [
        'บริวาร' => 'ครอบครัว คนในบ้าน ลูกน้อง เพื่อนพ้อง',
        'อายุ' => 'สุขภาพ ความเป็นอยู่',
        'เดช' => 'อำนาจ ชื่อเสียง ตำแหน่งหน้าที่',
        'ศรี' => 'โชคลาภ ทรัพย์สิน ความเจริญ',
        'มูละ' => 'บ้าน ที่ดิน หลักฐาน ทรัพย์เดิม',
        'อุตสาหะ' => 'ความขยัน การงานอาชีพ',
        'มนตรี' => 'ผู้ใหญ่ ผู้อุปถัมภ์ เจ้านาย',
        'กาลกิณี' => 'ศัตรู อุปสรรค เคราะห์ รายจ่าย',
    ];

    public const BHAVA_SRC = ['phromchat', 'sarakadee', 'gotoknow', 'sanook'];

    /**
     * ทักษาจรรายปี: ดาวที่ภูมิเดิมเป็น X กลายเป็นกาลีจร / ศรีจรในปีนั้น (bloggang · หมอจุ๊บ · สอ้าน ตรงกัน)
     * null = แหล่งขัดกัน (ศรีจรของภูมิกาลกิณีเดิม: ว่าได้ลาภ / เสียเงิน / ไว้ใจไม่ได้)
     *
     * @var array<string, array{kali:?string, kali_src:list<string>, sri:?string, sri_src:list<string>}>
     */
    public const JON = [
        'บริวาร' => ['kali' => 'คนใกล้ชิด ลูกน้อง หรือคู่ครองก่อเรื่องให้เสียทรัพย์', 'kali_src' => ['bloggang', 'joop', 'saan'], 'sri' => 'ได้คนดีมาช่วย ได้บริวารใหม่หรือคู่ครอง', 'sri_src' => ['bloggang', 'joop', 'saan']],
        'อายุ' => ['kali' => 'ระวังเจ็บป่วย สุขภาพทรุด', 'kali_src' => ['bloggang', 'joop', 'saan'], 'sri' => 'สุขภาพดีขึ้น หายจากโรค', 'sri_src' => ['bloggang', 'joop', 'saan']],
        'เดช' => ['kali' => 'ระวังเสียชื่อเสียง ถูกใส่ร้าย', 'kali_src' => ['bloggang', 'joop', 'saan'], 'sri' => 'ได้ตำแหน่งและชื่อเสียง', 'sri_src' => ['bloggang', 'joop', 'saan']],
        'ศรี' => ['kali' => 'การเงินฝืด ระวังเสียเงิน', 'kali_src' => ['bloggang', 'joop', 'saan'], 'sri' => 'โชคดี การเงินรุ่ง', 'sri_src' => ['bloggang', 'joop', 'saan']],
        'มูละ' => ['kali' => 'เสียทรัพย์ อาจต้องย้าย ที่อยู่มีเรื่องเดือดร้อน', 'kali_src' => ['bloggang', 'joop', 'saan'], 'sri' => 'ได้ที่อยู่ ขายทรัพย์ได้ราคา', 'sri_src' => ['bloggang', 'joop', 'saan']],
        'อุตสาหะ' => ['kali' => 'งานสะดุด ขาดทุน ท้อแท้', 'kali_src' => ['bloggang', 'joop', 'saan'], 'sri' => 'งานสำเร็จ ค้าขายดี', 'sri_src' => ['bloggang', 'joop', 'saan']],
        'มนตรี' => ['kali' => 'ผู้ใหญ่มีเรื่องสุขภาพ ขาดที่พึ่ง', 'kali_src' => ['joop', 'saan'], 'sri' => 'ผู้ใหญ่ช่วยเหลือ', 'sri_src' => ['bloggang', 'joop', 'saan']],
        'กาลกิณี' => ['kali' => 'เคราะห์ร้ายหนักขึ้น', 'kali_src' => ['joop', 'saan'], 'sri' => null, 'sri_src' => []],
    ];

    /**
     * ดาวเสวยอายุ 8 ดวง — ช่วงดี/ร้ายตรงกันทุกแหล่ง (จันทร์ พุธ พฤหัส ศุกร์ ดี · อาทิตย์ อังคาร เสาร์ ราหู ร้าย)
     * text = ใจความที่ ≥ 2 แหล่งตรงกัน (ตำราเก่าหลายฉบับอาจสืบสายเดียวกัน — punnapron/sanook เป็นผู้เขียนสมัยใหม่ที่แยกจริง)
     *
     * @var array<int, array{tone:string, text:string, src:list<string>}>
     */
    public const PERIOD = [
        1 => ['tone' => 'ร้าย', 'text' => 'ร้อนใจ มีเกณฑ์โยกย้ายที่อยู่ ระวังเรื่องกับผู้ใหญ่และคดีความ ดูแลสุขภาพและการเงินให้ดี', 'src' => ['phromchat', 'amnat', 'thasung', 'tewada', 'punnapron']],
        2 => ['tone' => 'ดี', 'text' => 'มีลาภ ได้ทรัพย์และของมีค่า ความรักดี ที่อยู่อาศัยดีขึ้น', 'src' => ['phromchat', 'amnat', 'thasung', 'tewada', 'punnapron']],
        3 => ['tone' => 'ร้าย', 'text' => 'ระวังภัยและอุบัติเหตุ มีคนปองร้ายหรือใส่ความ เสียทรัพย์ เจ็บไข้ ญาติห่างเหิน', 'src' => ['phromchat', 'amnat', 'thasung', 'tewada', 'punnapron']],
        4 => ['tone' => 'ดี', 'text' => 'มีลาภและทรัพย์ ได้คู่ครอง ได้เป็นใหญ่ มียศ ชื่อเสียง และบริวาร', 'src' => ['phromchat', 'amnat', 'thasung', 'tewada', 'punnapron']],
        7 => ['tone' => 'ร้าย', 'text' => 'เสียทรัพย์ ระวังเจ็บป่วย อุบัติเหตุ ศัตรูเก่า และการสูญเสียของรัก มีเรื่องกับคนใกล้ตัว', 'src' => ['phromchat', 'amnat', 'thasung', 'tewada', 'silpa', 'punnapron']],
        5 => ['tone' => 'ดี', 'text' => 'ผู้ใหญ่หรือครูบาอาจารย์อุปถัมภ์ มีชื่อเสียงเกียรติยศ มีโชคลาภ ครอบครัวและบริวารดี', 'src' => ['phromchat', 'amnat', 'tewada', 'punnapron']],
        8 => ['tone' => 'ร้าย', 'text' => 'ระวังเจ็บป่วย สุขภาพของพ่อแม่ การพลัดพรากจากญาติ คดีความ และการถูกใส่ร้าย ช่วงท้ายจึงมีลาภ', 'src' => ['phromchat', 'amnat', 'tewada', 'punnapron', 'sanook']],
        6 => ['tone' => 'ดี', 'text' => 'มีเงินทอง อยู่เย็นเป็นสุข คู่ครองดี — ช่วงที่ดีที่สุดในมหาทักษา', 'src' => ['phromchat', 'amnat', 'silpa', 'tewada', 'sanook', 'punnapron']],
    ];

    /** ผลของดาวเสวยขึ้นกับดาวดวงนั้นในดวงกำเนิด: อริ มรณะ วินาศ หรือนิจ/ประ = หนักขึ้น · อุจ/เกษตร = ดีขึ้น */
    public const NATAL_RULE = ['text' => 'ดาวเสวยที่อยู่ภพอริ มรณะ วินาศน์ หรือเป็นนิจ/ประในดวงเดิม ผลร้ายหนักขึ้น · ดาวที่อุจ/เกษตรให้ผลดีขึ้น', 'src' => ['sanook', 'bloggang']];

    /** วิธีเสริมดวงที่แทบทุกแหล่งตรงกัน (ใช้ในบล็อกให้ AI — ไม่ขึ้นการ์ด) */
    public const REMEDY = ['text' => 'บูชาดาวเสวยอายุคู่กับดาวแทรก ตามจำนวนกำลังดาว (อาทิตย์ 6 จันทร์ 15 อังคาร 8 พุธ 17 เสาร์ 10 พฤหัส 19 ราหู 12 ศุกร์ 21)', 'src' => ['amnat', 'thasung', 'phromchat']];

    /**
     * ดาวเสวย-ดาวแทรก (เลขดาว "เสวย-แทรก") — ตรงกัน 3 ชุด 57 คู่ · 2 ชุด 2 คู่ (1-3, 4-2) · ขัดกัน 5 คู่ (null)
     * ทั้ง 3 ชุดน่าจะสืบจากตำราต้นเดียวกัน แต่ตีพิมพ์แยกกัน (ร้อยแก้ว · กลอน · หนังสือวัด)
     *
     * @var array<string, ?array{tone:string, text:string, src:list<string>}>
     */
    public const SUB = [
        // อาทิตย์เสวย
        '1-1' => ['tone' => 'ร้าย', 'text' => 'เจ็บป่วย ปวดศีรษะ ร้อนใจเรื่องคนใกล้ตัว', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '1-2' => ['tone' => 'ผสม', 'text' => 'ช่วงแรกทุกข์ใจเรื่องครอบครัวหรือผู้หญิง สุขภาพไม่ดี แล้วค่อยคลี่คลาย', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '1-3' => ['tone' => 'ร้าย', 'text' => 'ระวังอัคคีภัย และถูกผู้ใหญ่หรือเจ้านายตำหนิลงโทษ', 'src' => ['phromchat', 'horasatthai']],
        '1-4' => ['tone' => 'ดี', 'text' => 'มีลาภ ได้ยศหรือความก้าวหน้า', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '1-7' => ['tone' => 'ร้าย', 'text' => 'ระวังคดีความ ของมีคม และอุบัติเหตุ', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '1-5' => ['tone' => 'ดี', 'text' => 'ได้ลาภจากผู้ใหญ่หรือผู้ทรงศีล', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '1-8' => ['tone' => 'ร้าย', 'text' => 'เสียทรัพย์ เจ็บป่วย มีเรื่องคดีความ', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '1-6' => ['tone' => 'ดี', 'text' => 'เงินทองเข้า มีบริวาร ได้ลาภจากมิตรหรือผู้มียศ', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        // จันทร์เสวย
        '2-2' => ['tone' => 'ดี', 'text' => 'ได้ทรัพย์ มีลาภเข้าหลายทาง', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '2-3' => ['tone' => 'ร้าย', 'text' => 'เสียทรัพย์ ระวังสุขภาพของผู้ใหญ่ในบ้าน', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '2-4' => ['tone' => 'ดี', 'text' => 'มีลาภ มีชื่อเสียง มิตรสหายช่วยเหลือ', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '2-7' => ['tone' => 'ร้าย', 'text' => 'เสียทรัพย์ มีศัตรู ระวังการทะเลาะวิวาท', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '2-5' => ['tone' => 'ผสม', 'text' => 'ช่วงแรกครอบครัวผิดใจ ร้อนใจ ข้าวของเสียหาย ภายหลังมีลาภ', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '2-8' => ['tone' => 'ร้าย', 'text' => 'ถูกใส่ร้าย เสียทรัพย์ มีเรื่องเกี่ยวกับผู้หญิงหรือผู้ใหญ่', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '2-6' => ['tone' => 'ดี', 'text' => 'มีลาภเงินทอง อยู่เย็นเป็นสุข', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '2-1' => ['tone' => 'ร้าย', 'text' => 'ระวังถูกใส่ความ เสียทรัพย์', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        // อังคารเสวย
        '3-3' => ['tone' => 'ร้าย', 'text' => 'มีภัย เสียทรัพย์ ระวังคนคิดร้าย', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '3-4' => ['tone' => 'ดี', 'text' => 'ได้ลาภผ่านมิตรหรือผู้มีตำแหน่ง', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '3-7' => ['tone' => 'ร้าย', 'text' => 'ระวังบาดเจ็บหรือเจ็บป่วย อาจต้องย้ายที่อยู่', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '3-5' => ['tone' => 'ผสม', 'text' => 'ได้ลาภ แต่มีเรื่องร้อนใจตามมา', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '3-8' => ['tone' => 'ร้าย', 'text' => 'ระวังอัคคีภัยในบ้าน ศัตรู และโจรภัย', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '3-6' => ['tone' => 'ดี', 'text' => 'ได้ทรัพย์ ได้ที่อยู่ มีบริวาร ผู้ใหญ่เมตตา', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '3-1' => ['tone' => 'ร้าย', 'text' => 'ระวังคนใส่ความหรือกลั่นแกล้ง', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '3-2' => ['tone' => 'ดี', 'text' => 'มีลาภเงินทอง สตรีนำโชค', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        // พุธเสวย
        '4-4' => ['tone' => 'ดี', 'text' => 'เงินทองและของมีค่าเพิ่มพูน ร่ำรวยขึ้น', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '4-7' => ['tone' => 'ร้าย', 'text' => 'ระวังพลัดพรากหรือสูญเสียของรัก', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '4-5' => ['tone' => 'ผสม', 'text' => 'ได้ลาภจากผู้ใหญ่ แต่ไม่ควรเดินทางไกล ระวังภัยและโจร', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '4-8' => ['tone' => 'ร้าย', 'text' => 'มีศัตรูผู้มีกำลัง ระวังอัคคีภัยและเจ็บไข้', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '4-6' => ['tone' => 'ดี', 'text' => 'มีลาภมาก', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '4-1' => ['tone' => 'ร้าย', 'text' => 'ระวังต้องโทษ มีอุปสรรค เสียทรัพย์', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '4-2' => ['tone' => 'ดี', 'text' => 'ได้ลาภผ่านมิตรหรือคนช่วยงาน', 'src' => ['horasatthai', 'thasung']],
        '4-3' => ['tone' => 'ร้าย', 'text' => 'มีคดีความ ร้อนใจเรื่องบ้านและคนในบ้าน ระวังศัตรูและโจร', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        // เสาร์เสวย
        '7-7' => ['tone' => 'ร้าย', 'text' => 'มีภัย เสียทรัพย์ ระวังโรคทางท้อง', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '7-5' => ['tone' => 'ผสม', 'text' => 'ได้ลาภจากผู้ใหญ่หรือครูอาจารย์ แต่เดินทางไกลแล้วจะเสีย', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '7-8' => null, // ขัดกัน: พรหมชาติ/วัดท่าซุงว่าเสียก่อนแล้วได้ลาภ · horasatthai ว่าร้ายล้วน
        '7-6' => ['tone' => 'ผสม', 'text' => 'ช่วงแรกเสียของ มีเรื่องเพราะผู้หญิง ภายหลังมิตรให้ลาภ', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '7-1' => ['tone' => 'ร้าย', 'text' => 'เสียทรัพย์ ร้อนใจ มีเรื่องในครอบครัว', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '7-2' => ['tone' => 'ดี', 'text' => 'มีลาภ ครอบครัวและบริวารเกื้อหนุน', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '7-3' => ['tone' => 'ร้าย', 'text' => 'มีศัตรูทำให้เสียทรัพย์', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '7-4' => null, // ขัดกัน: horasatthai ร้ายล้วน · พรหมชาติ/วัดท่าซุงมีลาภด้วยและศัตรูคนละเพศ
        // พฤหัสบดีเสวย
        '5-5' => ['tone' => 'ดี', 'text' => 'ผู้ใหญ่ให้ลาภ ได้ยศและชื่อเสียง', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '5-8' => ['tone' => 'ผสม', 'text' => 'ได้แล้วเสีย เสียทรัพย์ ร้อนใจเรื่องคนรัก', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '5-6' => ['tone' => 'ดี', 'text' => 'มีลาภมาก สุขสบาย', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '5-1' => ['tone' => 'ผสม', 'text' => 'ได้ลาภ แต่ระวังปวดศีรษะหรือเจ็บที่ใบหน้า', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '5-2' => null, // ขัดกัน: พรหมชาติ/horasatthai ว่าระวังผู้หญิง สูญทรัพย์ · วัดท่าซุงว่าได้ลาภ
        '5-3' => ['tone' => 'ร้าย', 'text' => 'ระวังคดีความ เสียทรัพย์', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '5-4' => ['tone' => 'ดี', 'text' => 'มีลาภ ได้ที่ดินไร่นา มีชื่อเสียง', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '5-7' => ['tone' => 'ร้าย', 'text' => 'ระวังผู้ใหญ่หรือเจ้านายให้โทษ', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        // ราหูเสวย
        '8-8' => ['tone' => 'ร้าย', 'text' => 'เจ็บป่วย ระวังโรคทางท้อง และการสูญเสียในครอบครัว', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '8-6' => ['tone' => 'ดี', 'text' => 'มีลาภเข้า', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '8-1' => null, // ขัดกัน: ลำดับได้-เสียกลับกันระหว่างพรหมชาติ/horasatthai กับวัดท่าซุง
        '8-2' => ['tone' => 'ร้าย', 'text' => 'ผู้ใหญ่ไม่พอใจ ถูกใส่ความ ระวังแพ้คดี', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '8-3' => ['tone' => 'ร้าย', 'text' => 'มีภัย ระวังเลือดตกยางออก เสียทรัพย์', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '8-4' => ['tone' => 'ผสม', 'text' => 'ได้ลาภก่อน แล้วเสียทรัพย์ภายหลัง', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '8-7' => ['tone' => 'ดี', 'text' => 'ได้ลาภ โดยเฉพาะจากมิตรสหาย', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '8-5' => null, // ขัดกัน: พรหมชาติ/horasatthai มีด้านดี (อยู่บ้านได้ยศลาภ) · วัดท่าซุงว่าร้อนใจล้วน
        // ศุกร์เสวย
        '6-6' => ['tone' => 'ดี', 'text' => 'มีลาภ มีความสุข เงินทองบริบูรณ์', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '6-1' => ['tone' => 'ร้าย', 'text' => 'ปวดศีรษะ ผิดใจกับพวกพ้อง', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '6-2' => ['tone' => 'ดี', 'text' => 'ได้ทรัพย์ มีลาภ ได้คู่ครอง', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '6-3' => ['tone' => 'ดี', 'text' => 'ได้ทรัพย์ ได้มิตรดี', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '6-4' => ['tone' => 'ดี', 'text' => 'มีลาภ ผู้ใหญ่ให้', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '6-7' => ['tone' => 'ร้าย', 'text' => 'เสียทรัพย์ บริวารหนีหาย เจ็บป่วย', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '6-5' => ['tone' => 'ดี', 'text' => 'ผู้มีอำนาจให้ลาภหรือรางวัล ของหายได้คืน', 'src' => ['phromchat', 'horasatthai', 'thasung']],
        '6-8' => ['tone' => 'ร้าย', 'text' => 'เสียทรัพย์ เจ็บป่วย ห่างเหินคนในครอบครัว', 'src' => ['phromchat', 'horasatthai', 'thasung']],
    ];
}
