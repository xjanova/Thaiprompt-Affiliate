<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Feature Document Controller
 *
 * จัดการหน้าเอกสารสำหรับแต่ละ Feature ของระบบ
 * รวมถึงหน้า All-in-One, AI, TPIX, Wallet, MLM, Security
 */
class FeatureDocumentController extends Controller
{
    /**
     * หน้าเอกสาร All-in-One Platform
     *
     * แสดงรายละเอียดทุกระบบที่รวมอยู่ในแพลตฟอร์ม
     *
     * @return View
     */
    public function allInOne(): View
    {
        // ข้อมูลระบบทั้งหมดที่รวมอยู่ในแพลตฟอร์ม
        $data = $this->getAllInOneData();

        return view('documents.all-in-one', [
            'data' => $data,
            'pageTitle' => 'All-in-One Platform - ระบบครบวงจรในแพลตฟอร์มเดียว',
        ]);
    }

    /**
     * หน้าเอกสาร AI-Powered Automation
     *
     * แสดงรายละเอียดระบบ AI และ Automation ทั้งหมด
     *
     * @return View
     */
    public function aiAutomation(): View
    {
        // ข้อมูลระบบ AI ทั้งหมด
        $data = $this->getAiAutomationData();

        return view('documents.ai-automation', [
            'data' => $data,
            'pageTitle' => 'AI-Powered Automation - ระบบอัจฉริยะอัตโนมัติ 24/7',
        ]);
    }

    /**
     * หน้าเอกสาร Blockchain & TPIX Token
     *
     * แสดง Whitepaper TPIX ทั้งหมด
     *
     * @return View
     */
    public function blockchainTpix(): View
    {
        // ข้อมูล TPIX Whitepaper ทั้งหมด
        $data = $this->getBlockchainTpixData();

        return view('documents.blockchain-tpix', [
            'data' => $data,
            'pageTitle' => 'Blockchain & TPIX Token - Whitepaper ฉบับสมบูรณ์',
        ]);
    }

    /**
     * หน้าเอกสาร Multi-Currency Wallet
     *
     * แสดงรายละเอียดระบบกระเป๋าเงินหลายสกุล
     *
     * @return View
     */
    public function multiCurrencyWallet(): View
    {
        // ข้อมูลระบบ Wallet ทั้งหมด
        $data = $this->getMultiCurrencyWalletData();

        return view('documents.multi-currency-wallet', [
            'data' => $data,
            'pageTitle' => 'Multi-Currency Wallet - กระเป๋าเงินหลายสกุล',
        ]);
    }

    /**
     * หน้าเอกสาร MLM & Commission System
     *
     * แสดงรายละเอียดระบบ MLM และคอมมิชชั่น
     *
     * @return View
     */
    public function mlmCommission(): View
    {
        // ข้อมูลระบบ MLM ทั้งหมด
        $data = $this->getMlmCommissionData();

        return view('documents.mlm-commission', [
            'data' => $data,
            'pageTitle' => 'MLM & Commission System - ระบบสายงานและคอมมิชชั่น',
        ]);
    }

    /**
     * หน้าเอกสาร Enterprise Security
     *
     * แสดงรายละเอียดระบบความปลอดภัย
     *
     * @return View
     */
    public function enterpriseSecurity(): View
    {
        // ข้อมูลระบบ Security ทั้งหมด
        $data = $this->getEnterpriseSecurityData();

        return view('documents.enterprise-security', [
            'data' => $data,
            'pageTitle' => 'Enterprise Security - ความปลอดภัยระดับองค์กร',
        ]);
    }

    /**
     * ดึงข้อมูลสำหรับหน้า All-in-One Platform
     *
     * @return array
     */
    private function getAllInOneData(): array
    {
        return [
            'version' => '3.0',
            'updated_at' => '2024-12-03',

            // ระบบหลักทั้งหมด
            'systems' => [
                [
                    'category' => 'ระบบการตลาดและขาย',
                    'color' => 'from-purple-500 to-indigo-600',
                    'icon' => 'fa-sitemap',
                    'items' => [
                        [
                            'name' => 'ระบบ Affiliate/MLM',
                            'description' => 'ระบบสายงานหลายระดับ รองรับ Unilevel และ Binary',
                            'features' => ['ไม่จำกัดชั้น', 'คำนวณคอมมิชชั่นอัตโนมัติ', 'รายงานละเอียด', 'Rank System'],
                        ],
                        [
                            'name' => 'E-Commerce',
                            'description' => 'ระบบขายสินค้าออนไลน์ครบวงจร',
                            'features' => ['จัดการสินค้า', 'ระบบตะกร้า', 'ชำระเงินหลายช่องทาง', 'ส่งของ'],
                        ],
                        [
                            'name' => 'Marketplace',
                            'description' => 'ตลาดกลางสำหรับผู้ขายหลายราย',
                            'features' => ['Multi-vendor', 'Commission System', 'Seller Dashboard', 'Analytics'],
                        ],
                    ],
                ],
                [
                    'category' => 'ระบบ AI และ Automation',
                    'color' => 'from-emerald-500 to-teal-600',
                    'icon' => 'fa-robot',
                    'items' => [
                        [
                            'name' => 'AI Chatbot',
                            'description' => 'ระบบตอบแชทอัจฉริยะด้วย AI',
                            'features' => ['เรียนรู้จากข้อมูล', 'ตอบได้หลายภาษา', 'ตอบได้ 24/7', 'ปรับแต่งได้'],
                        ],
                        [
                            'name' => 'LINE Bot',
                            'description' => 'บอท LINE อัจฉริยะ',
                            'features' => ['เชื่อมต่อ LINE OA', 'Auto Reply', 'Rich Menu', 'Broadcast'],
                        ],
                        [
                            'name' => 'Trading Bot',
                            'description' => 'บอทเทรดอัตโนมัติ',
                            'features' => ['หลาย Strategies', 'Multi-exchange', 'Risk Management', 'Analytics'],
                        ],
                    ],
                ],
                [
                    'category' => 'ระบบการเงิน',
                    'color' => 'from-amber-500 to-orange-600',
                    'icon' => 'fa-wallet',
                    'items' => [
                        [
                            'name' => 'Digital Wallet',
                            'description' => 'กระเป๋าเงินดิจิทัลหลายสกุล',
                            'features' => ['THB, USD, Crypto', 'โอนเงิน', 'ถอนเงิน', 'ประวัติธุรกรรม'],
                        ],
                        [
                            'name' => 'TPIX Token',
                            'description' => 'เหรียญ Cryptocurrency ของระบบ',
                            'features' => ['Blockchain ของตัวเอง', 'Staking', 'DEX', 'Rewards'],
                        ],
                        [
                            'name' => 'POS System',
                            'description' => 'ระบบขายหน้าร้าน',
                            'features' => ['ขายสินค้า', 'จัดการสต็อก', 'รายงาน', 'พิมพ์ใบเสร็จ'],
                        ],
                    ],
                ],
                [
                    'category' => 'ระบบจอง',
                    'color' => 'from-rose-500 to-pink-600',
                    'icon' => 'fa-calendar-check',
                    'items' => [
                        [
                            'name' => 'Hotel Booking',
                            'description' => 'ระบบจองโรงแรม',
                            'features' => ['ค้นหาห้อง', 'เปรียบเทียบราคา', 'จองออนไลน์', 'Review'],
                        ],
                        [
                            'name' => 'Delivery Platform',
                            'description' => 'แพลตฟอร์มส่งอาหารและบริการ',
                            'features' => ['Food Delivery', 'Grocery', 'Courier', 'Rider App'],
                        ],
                    ],
                ],
                [
                    'category' => 'ระบบบริหารจัดการ',
                    'color' => 'from-slate-500 to-gray-600',
                    'icon' => 'fa-cogs',
                    'items' => [
                        [
                            'name' => 'HRM System',
                            'description' => 'ระบบบริหารทรัพยากรบุคคล',
                            'features' => ['ข้อมูลพนักงาน', 'ลางาน', 'เงินเดือน', 'Attendance'],
                        ],
                        [
                            'name' => 'Academy LMS',
                            'description' => 'ระบบ Learning Management',
                            'features' => ['คอร์สออนไลน์', 'Quiz', 'Certificate', 'Progress'],
                        ],
                        [
                            'name' => 'Accounting',
                            'description' => 'ระบบบัญชี',
                            'features' => ['รายรับ-รายจ่าย', 'Invoice', 'Tax Report', 'Financial Report'],
                        ],
                    ],
                ],
                [
                    'category' => 'ระบบเครื่องมือพิเศษ',
                    'color' => 'from-cyan-500 to-blue-600',
                    'icon' => 'fa-magic',
                    'items' => [
                        [
                            'name' => 'Food Passport',
                            'description' => 'ระบบตรวจสอบแหล่งที่มาอาหาร',
                            'features' => ['Traceability', 'QR Code', 'Certificate', 'Blockchain'],
                        ],
                        [
                            'name' => 'QR/Barcode Generator',
                            'description' => 'เครื่องมือสร้าง QR และ Barcode',
                            'features' => ['หลายรูปแบบ', 'Logo', 'ปรับแต่งสี', 'Download'],
                        ],
                        [
                            'name' => 'Tarot Reading',
                            'description' => 'ระบบดูดวงไพ่ทาโร่ AI',
                            'features' => ['หลายหมวดดวง', 'AI ทำนาย', 'บันทึกประวัติ', 'แชร์ได้'],
                        ],
                        [
                            'name' => 'Games',
                            'description' => 'เกมเพื่อความบันเทิง',
                            'features' => ['หลายเกม', 'Leaderboard', 'Rewards', 'Tournament'],
                        ],
                    ],
                ],
            ],

            // สถิติ
            'stats' => [
                ['label' => 'ระบบรองรับ', 'value' => '20+', 'icon' => 'fa-cubes'],
                ['label' => 'ผู้ใช้ทั่วโลก', 'value' => '10,000+', 'icon' => 'fa-users'],
                ['label' => 'ธุรกรรมต่อวัน', 'value' => '50,000+', 'icon' => 'fa-exchange-alt'],
                ['label' => 'Uptime', 'value' => '99.9%', 'icon' => 'fa-server'],
            ],

            // ข้อดีของ All-in-One
            'benefits' => [
                [
                    'title' => 'ประหยัดค่าใช้จ่าย',
                    'description' => 'ไม่ต้องซื้อระบบแยกหลายตัว จ่ายครั้งเดียวได้ทุกระบบ',
                    'icon' => 'fa-piggy-bank',
                ],
                [
                    'title' => 'ทำงานร่วมกันได้ดี',
                    'description' => 'ทุกระบบถูกออกแบบมาให้ทำงานร่วมกัน ไม่ต้อง Integrate เพิ่ม',
                    'icon' => 'fa-link',
                ],
                [
                    'title' => 'จัดการง่าย',
                    'description' => 'Admin Panel เดียว จัดการได้ทุกระบบ',
                    'icon' => 'fa-tachometer-alt',
                ],
                [
                    'title' => 'Support ครบวงจร',
                    'description' => 'ทีมซัพพอร์ตเดียวดูแลทุกระบบ 24/7',
                    'icon' => 'fa-headset',
                ],
            ],
        ];
    }

    /**
     * ดึงข้อมูลสำหรับหน้า AI-Powered Automation
     *
     * @return array
     */
    private function getAiAutomationData(): array
    {
        return [
            'version' => '3.0',
            'updated_at' => '2024-12-03',

            // ระบบ AI ทั้งหมด
            'ai_systems' => [
                [
                    'name' => 'AI Chatbot',
                    'description' => 'ระบบตอบแชทอัจฉริยะด้วย AI รุ่นล่าสุด',
                    'icon' => 'fa-comments',
                    'color' => 'from-blue-500 to-indigo-600',
                    'features' => [
                        'ตอบแชทอัตโนมัติ 24/7',
                        'เรียนรู้จากข้อมูลร้านค้า',
                        'รองรับหลายภาษา (ไทย, English, 中文)',
                        'ตอบคำถามเกี่ยวกับสินค้าได้',
                        'แนะนำสินค้าตามความสนใจ',
                        'จดจำลูกค้าและประวัติการสนทนา',
                    ],
                    'integrations' => ['LINE', 'Facebook Messenger', 'Website Chat', 'Telegram'],
                ],
                [
                    'name' => 'LINE Bot AI',
                    'description' => 'บอท LINE อัจฉริยะสำหรับธุรกิจ',
                    'icon' => 'fab fa-line',
                    'color' => 'from-green-500 to-emerald-600',
                    'features' => [
                        'เชื่อมต่อกับ LINE Official Account',
                        'ตอบคำถามอัตโนมัติด้วย AI',
                        'ส่ง Broadcast ไปหาลูกค้า',
                        'Rich Menu แบบ Dynamic',
                        'Flex Message สวยงาม',
                        'รับออเดอร์ผ่าน LINE',
                    ],
                    'integrations' => ['LINE Official Account', 'E-Commerce', 'CRM'],
                ],
                [
                    'name' => 'Trading Bot',
                    'description' => 'บอทเทรดอัตโนมัติสำหรับ Cryptocurrency',
                    'icon' => 'fa-chart-line',
                    'color' => 'from-amber-500 to-orange-600',
                    'features' => [
                        'เทรดอัตโนมัติตาม Strategy',
                        'รองรับหลาย Exchange',
                        'Risk Management System',
                        'Backtesting ก่อนใช้งานจริง',
                        'Alert แจ้งเตือนผ่าน LINE/Email',
                        'Analytics และรายงานละเอียด',
                    ],
                    'integrations' => ['Binance', 'Bitkub', 'FTX', 'KuCoin'],
                ],
                [
                    'name' => 'AI Content Generator',
                    'description' => 'AI สร้างเนื้อหาอัตโนมัติ',
                    'icon' => 'fa-pen-fancy',
                    'color' => 'from-purple-500 to-pink-600',
                    'features' => [
                        'เขียนคำบรรยายสินค้า',
                        'สร้างโพสต์ Social Media',
                        'เขียน Blog/Article',
                        'ตอบ Review ลูกค้า',
                        'แปลภาษาอัตโนมัติ',
                        'SEO Optimization',
                    ],
                    'integrations' => ['E-Commerce', 'Blog', 'Social Media'],
                ],
                [
                    'name' => 'AI Analytics',
                    'description' => 'วิเคราะห์ข้อมูลด้วย AI',
                    'icon' => 'fa-brain',
                    'color' => 'from-cyan-500 to-blue-600',
                    'features' => [
                        'พยากรณ์ยอดขาย',
                        'แนะนำสินค้าสำหรับลูกค้า',
                        'Sentiment Analysis',
                        'Customer Segmentation',
                        'Trend Prediction',
                        'Anomaly Detection',
                    ],
                    'integrations' => ['Dashboard', 'Reports', 'Alerts'],
                ],
                [
                    'name' => 'Tarot AI',
                    'description' => 'AI ทำนายดวงไพ่ทาโร่',
                    'icon' => 'fa-star',
                    'color' => 'from-rose-500 to-red-600',
                    'features' => [
                        'ทำนายดวงแม่นยำด้วย AI',
                        'หลายหมวดดวง (ความรัก, การงาน, การเงิน)',
                        'อธิบายความหมายไพ่ละเอียด',
                        'บันทึกประวัติการดูดวง',
                        'แชร์ผลได้',
                        'ดูดวงทุกวันฟรี',
                    ],
                    'integrations' => ['LINE Bot', 'Website', 'Mobile App'],
                ],
            ],

            // เทคโนโลยี AI ที่ใช้
            'technologies' => [
                ['name' => 'GPT-4', 'description' => 'Large Language Model ล่าสุด', 'icon' => 'fa-brain'],
                ['name' => 'Claude AI', 'description' => 'AI Assistant อัจฉริยะ', 'icon' => 'fa-robot'],
                ['name' => 'Computer Vision', 'description' => 'AI วิเคราะห์รูปภาพ', 'icon' => 'fa-eye'],
                ['name' => 'NLP', 'description' => 'ประมวลผลภาษาธรรมชาติ', 'icon' => 'fa-language'],
                ['name' => 'Machine Learning', 'description' => 'เรียนรู้และปรับปรุงตัวเอง', 'icon' => 'fa-cogs'],
                ['name' => 'Deep Learning', 'description' => 'Neural Networks ขั้นสูง', 'icon' => 'fa-network-wired'],
            ],

            // ข้อดี
            'benefits' => [
                ['title' => 'ลดต้นทุน', 'value' => '70%', 'description' => 'ประหยัดค่าจ้างพนักงาน'],
                ['title' => 'เพิ่มประสิทธิภาพ', 'value' => '3x', 'description' => 'ทำงานได้เร็วขึ้น'],
                ['title' => 'ทำงาน 24/7', 'value' => '24/7', 'description' => 'ไม่มีวันหยุด'],
                ['title' => 'ความแม่นยำ', 'value' => '95%', 'description' => 'ตอบถูกต้องแม่นยำ'],
            ],
        ];
    }

    /**
     * ดึงข้อมูลสำหรับหน้า Blockchain & TPIX Token
     *
     * @return array
     */
    private function getBlockchainTpixData(): array
    {
        // ใช้ข้อมูลจาก TPIXWhitepaperController และเพิ่มเติม
        return [
            // ข้อมูลพื้นฐาน
            'version' => '2.0',
            'date' => '2024-12-03',
            'language' => 'th',

            // ชื่อเต็ม
            'full_name' => 'Thaiprompt Index',
            'short_name' => 'TPIX',

            // Executive Summary
            'executive_summary' => [
                'title' => 'TPIX - Native Cryptocurrency สำหรับ Thaiprompt Ecosystem',
                'vision' => 'สร้าง Blockchain Network ที่รองรับการทำธุรกรรมเร็ว ปลอดภัย และราคาถูก สำหรับระบบ Affiliate Marketing ระดับ Enterprise',
                'mission' => [
                    'สร้างเหรียญ Cryptocurrency ที่มี Utility จริงในระบบ',
                    'รองรับการ Stake เพื่อรับผลตอบแทน',
                    'เชื่อมต่อกับระบบ E-Commerce, Hotel, Delivery',
                    'เป็นรางวัลสำหรับ Affiliate และ MLM',
                ],
                'highlights' => [
                    ['label' => 'Total Supply', 'value' => '7,000,000,000 TPIX'],
                    ['label' => 'Blockchain', 'value' => 'TPIX Network (EVM Compatible)'],
                    ['label' => 'Block Time', 'value' => '2 วินาที'],
                    ['label' => 'TPS', 'value' => '~1,500 transactions/second'],
                ],
            ],

            // ข้อมูล TPIX Blockchain
            'blockchain' => [
                'name' => 'TPIX Network',
                'native_coin' => 'Thaiprompt Index (TPIX)',
                'chain_id' => 7000,
                'total_supply' => '7,000,000,000 TPIX',
                'decimals' => 18,
                'block_time' => '2 seconds',
                'consensus' => 'IBFT (Istanbul Byzantine Fault Tolerant)',
                'vm' => 'EVM (Ethereum Virtual Machine)',
                'finality' => '~10 seconds (5 blocks)',
                'tps' => '~1,500 transactions/second',
            ],

            // Tokenomics
            'tokenomics' => [
                'total_supply' => 7000000000,
                'distribution' => [
                    ['name' => 'Ecosystem Development', 'percentage' => 30, 'amount' => 2100000000, 'color' => '#6366f1', 'description' => 'พัฒนาระบบ Platform และ Features ใหม่'],
                    ['name' => 'Affiliate Rewards', 'percentage' => 25, 'amount' => 1750000000, 'color' => '#22c55e', 'description' => 'รางวัลสำหรับ Affiliate และสมาชิก'],
                    ['name' => 'Staking Rewards', 'percentage' => 20, 'amount' => 1400000000, 'color' => '#f59e0b', 'description' => 'ผลตอบแทนจากการ Stake'],
                    ['name' => 'Team & Advisors', 'percentage' => 15, 'amount' => 1050000000, 'color' => '#ec4899', 'description' => 'ทีมผู้พัฒนาและที่ปรึกษา (Lock 2 ปี)'],
                    ['name' => 'Marketing & Partnerships', 'percentage' => 10, 'amount' => 700000000, 'color' => '#06b6d4', 'description' => 'การตลาดและพันธมิตร'],
                ],
            ],

            // Use Cases
            'use_cases' => [
                [
                    'title' => 'ระบบรางวัล Affiliate',
                    'description' => 'ผู้ใช้สามารถรับ TPIX จากการทำกิจกรรมต่างๆ เช่น แนะนำสมาชิกใหม่ ซื้อสินค้า ทำ Quest',
                    'icon' => 'fa-gift',
                    'color' => 'from-purple-500 to-indigo-600',
                    'features' => [
                        'รับ 100 TPIX เมื่อสมัครสมาชิกใหม่',
                        'รับ 50 TPIX เมื่อแนะนำเพื่อน',
                        'Cashback 5% เป็น TPIX จากการซื้อสินค้า',
                        'รับ 25 TPIX จากการทำ Quest สำเร็จ',
                    ],
                ],
                [
                    'title' => 'FoodPassport - ระบบตรวจสอบอาหารแบบ Blockchain',
                    'description' => 'ใช้ TPIX ในการตรวจสอบแหล่งที่มา คุณภาพ และความปลอดภัยของอาหารตลอด Supply Chain',
                    'icon' => 'fa-shield-alt',
                    'color' => 'from-green-500 to-emerald-600',
                    'features' => [
                        'Traceability - ตรวจสอบย้อนกลับแหล่งที่มาจากฟาร์มถึงผู้บริโภค',
                        'Quality Verification - ยืนยันคุณภาพและมาตรฐานอาหาร',
                        'Certificate Management - จัดการใบรับรองมาตรฐานแบบ NFT',
                        'Smart Contracts - ชำระเงินอัตโนมัติเมื่อผ่านการตรวจสอบ',
                        'Reward System - รับ TPIX เมื่อรายงานข้อมูลคุณภาพ',
                    ],
                ],
                [
                    'title' => 'Multi-Service Delivery Platform',
                    'description' => 'แพลตฟอร์มส่งอาหารและบริการครบวงจร ใช้ TPIX เป็นสื่อกลางการชำระเงิน',
                    'icon' => 'fa-truck',
                    'color' => 'from-amber-500 to-orange-600',
                    'features' => [
                        'Food Delivery - ส่งอาหารจากร้านอาหารและฟาร์ม',
                        'Grocery Delivery - ส่งของชำและสินค้าสด',
                        'Cashback 3% - รับคืน TPIX จากทุกออเดอร์',
                        'Rider Rewards - ผู้ส่งรับ TPIX จากการส่งสำเร็จ',
                    ],
                ],
                [
                    'title' => 'Staking Pools',
                    'description' => 'Stake TPIX เพื่อรับผลตอบแทน (APY)',
                    'icon' => 'fa-piggy-bank',
                    'color' => 'from-pink-500 to-rose-600',
                    'features' => [
                        'APY สูงสุด 120% ต่อปี',
                        'Lock periods หลากหลาย (7-365 วัน)',
                        'Auto-compound rewards',
                        'Unstake ได้ทันที (flexible staking)',
                    ],
                ],
                [
                    'title' => 'Decentralized Exchange (DEX)',
                    'description' => 'ซื้อขาย tokens และเพิ่ม liquidity เพื่อรับผลตอบแทน',
                    'icon' => 'fa-exchange-alt',
                    'color' => 'from-cyan-500 to-blue-600',
                    'features' => [
                        'Swap tokens ด้วย AMM',
                        'เพิ่ม Liquidity รับค่าธรรมเนียม 0.3%',
                        'Farming & Yield Optimization',
                        'ติดตาม Impermanent Loss',
                    ],
                ],
                [
                    'title' => 'ระบบชำระเงินภายใน',
                    'description' => 'ใช้ TPIX ชำระค่าสินค้าและบริการต่างๆ ภายในระบบ',
                    'icon' => 'fa-credit-card',
                    'color' => 'from-slate-500 to-gray-600',
                    'features' => [
                        'ซื้อ AI Bot Packages',
                        'ซื้อ Trading Bot Subscriptions',
                        'ชำระค่า Hotel Booking',
                        'ซื้อ Digital Products',
                    ],
                ],
            ],

            // Roadmap (2023-2026)
            'roadmap' => [
                [
                    'phase' => 'Phase 1: Concept & Foundation',
                    'status' => 'completed',
                    'quarter' => 'Q1-Q2 2023',
                    'milestones' => [
                        'Whitepaper & Tokenomics Design ✅',
                        'Technical Architecture Planning ✅',
                        'Team Formation ✅',
                        'Initial Funding & Partnerships ✅',
                        'Community Building ✅',
                    ],
                ],
                [
                    'phase' => 'Phase 2: Blockchain Development',
                    'status' => 'completed',
                    'quarter' => 'Q3-Q4 2023',
                    'milestones' => [
                        'Blockchain core implementation ✅',
                        'TPIX native coin (7B fixed supply) ✅',
                        'IBFT consensus mechanism ✅',
                        'EVM integration ✅',
                        'Testnet deployment ✅',
                    ],
                ],
                [
                    'phase' => 'Phase 3: Platform Integration',
                    'status' => 'completed',
                    'quarter' => 'Q1-Q2 2024',
                    'milestones' => [
                        'Laravel service integration ✅',
                        'REST API endpoints ✅',
                        'Block explorer ✅',
                        'Docker deployment ✅',
                        'Monitoring (Prometheus + Grafana) ✅',
                    ],
                ],
                [
                    'phase' => 'Phase 4: Ecosystem Expansion',
                    'status' => 'in_progress',
                    'quarter' => 'Q3-Q4 2024',
                    'milestones' => [
                        'DEX (Decentralized Exchange) 🚧',
                        'Staking Pools ✅',
                        'Multi-node testnet 🚧',
                        'Faucet service (แจก TPIX ฟรี) ✅',
                        'SDK development (PHP, JS, Python) 🚧',
                    ],
                ],
                [
                    'phase' => 'Phase 5: Real-World Applications',
                    'status' => 'in_progress',
                    'quarter' => 'Q1-Q2 2025',
                    'milestones' => [
                        'FoodPassport Integration 🚧',
                        'Multi-Service Delivery Platform 📅',
                        'IoT Smart Farm System 📅',
                        'Hotel & Travel Booking 🚧',
                        'AI Bot Marketplace ✅',
                    ],
                ],
                [
                    'phase' => 'Phase 6: Mainnet & Scaling',
                    'status' => 'planned',
                    'quarter' => 'Q3-Q4 2025',
                    'milestones' => [
                        'Mainnet launch 📅',
                        'Mobile wallet app 📅',
                        'Bridge to other blockchains 📅',
                        'Governance system (DAO) 📅',
                        'NFT Marketplace 📅',
                    ],
                ],
                [
                    'phase' => 'Phase 7: Global Expansion',
                    'status' => 'planned',
                    'quarter' => 'Q1-Q2 2026',
                    'milestones' => [
                        'International Exchange Listings 📅',
                        'Multi-language Support 📅',
                        'Global Partnership Network 📅',
                        'Enterprise Solutions 📅',
                    ],
                ],
                [
                    'phase' => 'Phase 8: Advanced Features',
                    'status' => 'planned',
                    'quarter' => 'Q3-Q4 2026',
                    'milestones' => [
                        'Lightning Network (Layer 2) 📅',
                        'Cross-chain Interoperability 📅',
                        'AI-Powered Trading Tools 📅',
                        'Carbon Credit Trading 📅',
                    ],
                ],
            ],

            // Technology Stack
            'tech_stack' => [
                'blockchain' => [
                    'Polygon Edge' => 'Blockchain framework (Go)',
                    'IBFT Consensus' => 'Proof of Stake',
                    'EVM' => 'Smart contract execution',
                    'LevelDB' => 'Storage layer',
                ],
                'backend' => [
                    'Laravel 11' => 'PHP framework',
                    'PHP 8.2+' => 'Programming language',
                    'MySQL 8.0+' => 'Database',
                    'Redis' => 'Caching & queues',
                ],
                'smart_contracts' => [
                    'Solidity ^0.8.20' => 'Contract language',
                    'Hardhat' => 'Development framework',
                    'OpenZeppelin' => 'Security libraries',
                ],
                'frontend' => [
                    'Tailwind CSS' => 'Styling framework',
                    'Alpine.js' => 'JavaScript framework',
                    'ethers.js' => 'Web3 library',
                ],
            ],

            // Team
            'team' => [
                [
                    'name' => 'Thaiprompt Development Team',
                    'role' => 'Core Development',
                    'description' => 'ทีมพัฒนาหลักที่มีประสบการณ์ด้าน Blockchain และ Web Development',
                ],
                [
                    'name' => 'Security Auditors',
                    'role' => 'Security & Audit',
                    'description' => 'ทีมตรวจสอบความปลอดภัยของ Smart Contracts และระบบ',
                ],
                [
                    'name' => 'Business Development',
                    'role' => 'Partnerships & Growth',
                    'description' => 'ทีมพัฒนาธุรกิจและหาพันธมิตรใหม่',
                ],
            ],

            // Legal
            'legal' => [
                'disclaimer' => 'TPIX เป็น Utility Token สำหรับใช้งานภายในระบบ Thaiprompt Affiliate เท่านั้น ไม่ใช่ Security Token หรือ Investment Product',
                'risk_warning' => 'การลงทุนใน Cryptocurrency มีความเสี่ยง ผู้ลงทุนควรศึกษาข้อมูลให้ดีก่อนตัดสินใจ',
            ],

            // Statistics
            'stats' => [
                'total_supply' => 7000000000,
                'circulating_supply' => 7000000000,
                'block_time' => 2,
                'tps' => 1500,
                'finality_time' => 10,
                'validators' => 4,
                'avg_gas_price' => '1 Gwei',
                'avg_tx_cost' => '0.000021 TPIX',
            ],
        ];
    }

    /**
     * ดึงข้อมูลสำหรับหน้า Multi-Currency Wallet
     *
     * @return array
     */
    private function getMultiCurrencyWalletData(): array
    {
        return [
            'version' => '3.0',
            'updated_at' => '2024-12-03',

            // ประเภทกระเป๋าเงิน
            'wallet_types' => [
                [
                    'name' => 'กระเป๋าเงินบาท (THB)',
                    'description' => 'กระเป๋าเงินสกุลบาทสำหรับธุรกรรมภายในประเทศ',
                    'icon' => 'fa-baht-sign',
                    'color' => 'from-blue-500 to-indigo-600',
                    'features' => [
                        'เติมเงินผ่านธนาคาร',
                        'ถอนเงินเข้าบัญชีธนาคาร',
                        'โอนเงินให้สมาชิก',
                        'รับคอมมิชชั่น',
                        'ชำระค่าสินค้า',
                    ],
                ],
                [
                    'name' => 'กระเป๋าเงินดอลลาร์ (USD)',
                    'description' => 'กระเป๋าเงินสกุลดอลลาร์สำหรับธุรกรรมระหว่างประเทศ',
                    'icon' => 'fa-dollar-sign',
                    'color' => 'from-green-500 to-emerald-600',
                    'features' => [
                        'รองรับการโอนระหว่างประเทศ',
                        'แลกเปลี่ยนเงินตราอัตโนมัติ',
                        'รับเงินจากลูกค้าต่างประเทศ',
                        'จ่ายค่าบริการต่างประเทศ',
                    ],
                ],
                [
                    'name' => 'กระเป๋า TPIX',
                    'description' => 'กระเป๋าเงิน Cryptocurrency TPIX',
                    'icon' => 'fa-coins',
                    'color' => 'from-amber-500 to-orange-600',
                    'features' => [
                        'รับ/ส่ง TPIX',
                        'Staking เพื่อรับผลตอบแทน',
                        'แลกเปลี่ยนกับสกุลอื่น',
                        'ชำระค่าบริการภายในระบบ',
                        'รับรางวัล Affiliate',
                    ],
                ],
                [
                    'name' => 'กระเป๋าคะแนน (Points)',
                    'description' => 'คะแนนสะสมสำหรับแลกสิทธิพิเศษ',
                    'icon' => 'fa-star',
                    'color' => 'from-purple-500 to-pink-600',
                    'features' => [
                        'สะสมคะแนนจากการซื้อ',
                        'แลกส่วนลดสินค้า',
                        'แลกของรางวัล',
                        'แลกเป็น TPIX',
                    ],
                ],
            ],

            // ช่องทางการเติมเงิน
            'deposit_methods' => [
                [
                    'name' => 'PromptPay',
                    'description' => 'เติมเงินผ่าน QR Code PromptPay',
                    'icon' => 'fa-qrcode',
                    'fee' => 'ฟรี',
                    'time' => 'ทันที',
                ],
                [
                    'name' => 'โอนธนาคาร',
                    'description' => 'โอนเงินจากธนาคารโดยตรง',
                    'icon' => 'fa-building-columns',
                    'fee' => 'ฟรี',
                    'time' => '1-24 ชั่วโมง',
                ],
                [
                    'name' => 'บัตรเครดิต/เดบิต',
                    'description' => 'ชำระผ่านบัตร Visa/Mastercard',
                    'icon' => 'fa-credit-card',
                    'fee' => '2.5%',
                    'time' => 'ทันที',
                ],
                [
                    'name' => 'Cryptocurrency',
                    'description' => 'เติมด้วย BTC, ETH, USDT',
                    'icon' => 'fab fa-bitcoin',
                    'fee' => 'Network fee',
                    'time' => '10-60 นาที',
                ],
            ],

            // ช่องทางการถอนเงิน
            'withdrawal_methods' => [
                [
                    'name' => 'บัญชีธนาคาร',
                    'description' => 'ถอนเข้าบัญชีธนาคารไทย',
                    'icon' => 'fa-building-columns',
                    'fee' => '10-30 บาท',
                    'time' => '1-3 วันทำการ',
                    'min' => '100 บาท',
                    'max' => '500,000 บาท/วัน',
                ],
                [
                    'name' => 'PromptPay',
                    'description' => 'ถอนเข้า PromptPay',
                    'icon' => 'fa-mobile-alt',
                    'fee' => '5-15 บาท',
                    'time' => 'ทันที',
                    'min' => '100 บาท',
                    'max' => '100,000 บาท/วัน',
                ],
                [
                    'name' => 'Cryptocurrency',
                    'description' => 'ถอนเป็น TPIX หรือ Stablecoin',
                    'icon' => 'fa-coins',
                    'fee' => 'Network fee',
                    'time' => '10-60 นาที',
                    'min' => '100 TPIX',
                    'max' => 'ไม่จำกัด',
                ],
            ],

            // ฟีเจอร์ความปลอดภัย
            'security_features' => [
                [
                    'name' => '2FA Authentication',
                    'description' => 'ยืนยันตัวตน 2 ขั้นตอนด้วย Google Authenticator หรือ SMS',
                    'icon' => 'fa-shield-alt',
                ],
                [
                    'name' => 'PIN Code',
                    'description' => 'รหัส PIN 6 หลักสำหรับการทำธุรกรรม',
                    'icon' => 'fa-lock',
                ],
                [
                    'name' => 'Withdrawal Whitelist',
                    'description' => 'จำกัดบัญชีปลายทางที่สามารถถอนได้',
                    'icon' => 'fa-list-check',
                ],
                [
                    'name' => 'Transaction Alerts',
                    'description' => 'แจ้งเตือนทุกธุรกรรมผ่าน LINE/Email',
                    'icon' => 'fa-bell',
                ],
                [
                    'name' => 'IP Monitoring',
                    'description' => 'ตรวจจับการเข้าถึงจาก IP ผิดปกติ',
                    'icon' => 'fa-map-marker-alt',
                ],
            ],

            // สถิติ
            'stats' => [
                ['label' => 'ผู้ใช้กระเป๋าเงิน', 'value' => '10,000+', 'icon' => 'fa-users'],
                ['label' => 'ธุรกรรมต่อวัน', 'value' => '50,000+', 'icon' => 'fa-exchange-alt'],
                ['label' => 'ยอดเงินรวม', 'value' => '฿100M+', 'icon' => 'fa-coins'],
                ['label' => 'Uptime', 'value' => '99.99%', 'icon' => 'fa-server'],
            ],
        ];
    }

    /**
     * ดึงข้อมูลสำหรับหน้า MLM & Commission System
     *
     * @return array
     */
    private function getMlmCommissionData(): array
    {
        return [
            'version' => '3.0',
            'updated_at' => '2024-12-03',

            // แผนการตลาด
            'plans' => [
                [
                    'name' => 'Unilevel Plan',
                    'description' => 'ระบบสายงานแบบไม่จำกัดความกว้าง จ่ายคอมมิชชั่นตามชั้น',
                    'icon' => 'fa-sitemap',
                    'color' => 'from-blue-500 to-indigo-600',
                    'features' => [
                        'ไม่จำกัดจำนวนทีมในชั้นเดียว',
                        'คอมมิชชั่นลดหลั่นตามชั้น',
                        'จ่ายได้หลายชั้น (ไม่จำกัด)',
                        'เหมาะกับทุกธุรกิจ',
                    ],
                    'commission_structure' => [
                        ['level' => 1, 'percentage' => '10%'],
                        ['level' => 2, 'percentage' => '5%'],
                        ['level' => 3, 'percentage' => '3%'],
                        ['level' => 4, 'percentage' => '2%'],
                        ['level' => 5, 'percentage' => '1%'],
                    ],
                ],
                [
                    'name' => 'Binary Plan',
                    'description' => 'ระบบสายงานแบบ 2 ขา (ซ้าย-ขวา) เน้นการทำงานเป็นทีม',
                    'icon' => 'fa-code-branch',
                    'color' => 'from-purple-500 to-pink-600',
                    'features' => [
                        'สายงาน 2 ขา (ซ้าย-ขวา)',
                        'Matching Bonus จากขาอ่อน',
                        'Spill Over สร้างสายงานให้ทีม',
                        'เหมาะกับธุรกิจที่เน้น Team Building',
                    ],
                    'commission_structure' => [
                        ['type' => 'Sponsor Bonus', 'percentage' => '10%'],
                        ['type' => 'Matching Bonus', 'percentage' => '10% ของขาอ่อน'],
                        ['type' => 'Daily Cap', 'percentage' => 'สูงสุด 50,000 บาท/วัน'],
                    ],
                ],
                [
                    'name' => 'Hybrid Plan',
                    'description' => 'ผสมผสาน Unilevel + Binary เพื่อประโยชน์สูงสุด',
                    'icon' => 'fa-diagram-project',
                    'color' => 'from-amber-500 to-orange-600',
                    'features' => [
                        'รวมข้อดีของทั้งสองแผน',
                        'ปรับแต่งได้ตามธุรกิจ',
                        'หลายช่องทางรายได้',
                        'เหมาะกับธุรกิจขนาดใหญ่',
                    ],
                ],
            ],

            // ประเภทคอมมิชชั่น
            'commission_types' => [
                [
                    'name' => 'Direct Commission',
                    'description' => 'คอมมิชชั่นจากการขายโดยตรง',
                    'icon' => 'fa-hand-holding-dollar',
                    'percentage' => '10-30%',
                ],
                [
                    'name' => 'Referral Commission',
                    'description' => 'คอมมิชชั่นจากการแนะนำสมาชิก',
                    'icon' => 'fa-user-plus',
                    'percentage' => '50-500 บาท/คน',
                ],
                [
                    'name' => 'Override Commission',
                    'description' => 'คอมมิชชั่นจากยอดขายของทีม',
                    'icon' => 'fa-users',
                    'percentage' => '1-10%',
                ],
                [
                    'name' => 'Matching Bonus',
                    'description' => 'โบนัสจากผลงานของ Downline',
                    'icon' => 'fa-handshake',
                    'percentage' => '5-20%',
                ],
                [
                    'name' => 'Rank Bonus',
                    'description' => 'โบนัสจากการเลื่อน Rank',
                    'icon' => 'fa-medal',
                    'percentage' => '1,000-100,000 บาท',
                ],
                [
                    'name' => 'Pool Bonus',
                    'description' => 'แบ่งปันจาก Global Pool',
                    'icon' => 'fa-pool',
                    'percentage' => '2% ของยอดรวม',
                ],
            ],

            // ระบบ Rank
            'ranks' => [
                ['name' => 'Member', 'requirement' => 'สมัครสมาชิก', 'benefits' => 'Commission 10%', 'color' => 'from-gray-400 to-gray-500'],
                ['name' => 'Bronze', 'requirement' => 'ยอดขาย 10,000 บาท', 'benefits' => 'Commission 12%', 'color' => 'from-amber-600 to-amber-700'],
                ['name' => 'Silver', 'requirement' => 'ยอดขาย 50,000 บาท', 'benefits' => 'Commission 15%', 'color' => 'from-gray-300 to-gray-400'],
                ['name' => 'Gold', 'requirement' => 'ยอดขาย 200,000 บาท', 'benefits' => 'Commission 18% + Pool', 'color' => 'from-yellow-400 to-yellow-500'],
                ['name' => 'Platinum', 'requirement' => 'ยอดขาย 500,000 บาท', 'benefits' => 'Commission 20% + Pool', 'color' => 'from-slate-300 to-slate-400'],
                ['name' => 'Diamond', 'requirement' => 'ยอดขาย 1,000,000 บาท', 'benefits' => 'Commission 25% + Pool + Travel', 'color' => 'from-cyan-400 to-blue-500'],
                ['name' => 'Crown', 'requirement' => 'ยอดขาย 5,000,000 บาท', 'benefits' => 'Commission 30% + All Bonuses', 'color' => 'from-purple-500 to-pink-500'],
            ],

            // ฟีเจอร์ระบบ
            'features' => [
                [
                    'name' => 'Real-time Dashboard',
                    'description' => 'ดูยอดขายและคอมมิชชั่นแบบ Real-time',
                    'icon' => 'fa-chart-line',
                ],
                [
                    'name' => 'Genealogy Tree',
                    'description' => 'ดูโครงสร้างสายงานแบบ Visual',
                    'icon' => 'fa-sitemap',
                ],
                [
                    'name' => 'Auto Commission',
                    'description' => 'คำนวณและจ่ายคอมมิชชั่นอัตโนมัติ',
                    'icon' => 'fa-calculator',
                ],
                [
                    'name' => 'Reports',
                    'description' => 'รายงานละเอียดหลายมิติ',
                    'icon' => 'fa-file-alt',
                ],
                [
                    'name' => 'E-Wallet',
                    'description' => 'กระเป๋าเงินสำหรับรับคอมมิชชั่น',
                    'icon' => 'fa-wallet',
                ],
                [
                    'name' => 'Instant Withdrawal',
                    'description' => 'ถอนเงินได้ทันที',
                    'icon' => 'fa-money-bill-wave',
                ],
            ],

            // สถิติ
            'stats' => [
                ['label' => 'สมาชิก Affiliate', 'value' => '5,000+', 'icon' => 'fa-users'],
                ['label' => 'ยอดคอมมิชชั่นจ่าย', 'value' => '฿50M+', 'icon' => 'fa-money-bill'],
                ['label' => 'ธุรกรรมต่อเดือน', 'value' => '100,000+', 'icon' => 'fa-exchange-alt'],
                ['label' => 'อัตราการคงอยู่', 'value' => '85%', 'icon' => 'fa-heart'],
            ],
        ];
    }

    /**
     * ดึงข้อมูลสำหรับหน้า Enterprise Security
     *
     * @return array
     */
    private function getEnterpriseSecurityData(): array
    {
        return [
            'version' => '3.0',
            'updated_at' => '2024-12-03',

            // ระบบความปลอดภัย
            'security_systems' => [
                [
                    'name' => 'Two-Factor Authentication (2FA)',
                    'description' => 'ยืนยันตัวตน 2 ขั้นตอนสำหรับการเข้าสู่ระบบและธุรกรรม',
                    'icon' => 'fa-shield-halved',
                    'color' => 'from-blue-500 to-indigo-600',
                    'features' => [
                        'Google Authenticator / Authy',
                        'SMS OTP',
                        'Email OTP',
                        'Hardware Key (YubiKey)',
                        'Biometric (Face ID, Fingerprint)',
                    ],
                ],
                [
                    'name' => 'SSL/TLS Encryption',
                    'description' => 'เข้ารหัสข้อมูลทุกการเชื่อมต่อด้วย SSL Certificate',
                    'icon' => 'fa-lock',
                    'color' => 'from-green-500 to-emerald-600',
                    'features' => [
                        'TLS 1.3 Protocol',
                        'AES-256 Encryption',
                        'Wildcard SSL Certificate',
                        'HSTS Preloading',
                        'Certificate Pinning',
                    ],
                ],
                [
                    'name' => 'IP Whitelist / Blacklist',
                    'description' => 'จำกัดการเข้าถึงตาม IP Address',
                    'icon' => 'fa-network-wired',
                    'color' => 'from-amber-500 to-orange-600',
                    'features' => [
                        'Whitelist IP สำหรับ Admin',
                        'Auto Blacklist IP ผิดปกติ',
                        'Country Blocking',
                        'VPN Detection',
                        'Rate Limiting per IP',
                    ],
                ],
                [
                    'name' => 'License System',
                    'description' => 'ระบบ License สำหรับควบคุมการใช้งาน',
                    'icon' => 'fa-key',
                    'color' => 'from-purple-500 to-pink-600',
                    'features' => [
                        'License Key Validation',
                        'Domain Binding',
                        'Feature Restriction',
                        'Expiry Management',
                        'Anti-Piracy Protection',
                    ],
                ],
                [
                    'name' => 'Activity Logging',
                    'description' => 'บันทึกทุกกิจกรรมในระบบ',
                    'icon' => 'fa-clipboard-list',
                    'color' => 'from-cyan-500 to-blue-600',
                    'features' => [
                        'Login/Logout Logs',
                        'Transaction Logs',
                        'Admin Action Logs',
                        'API Request Logs',
                        'Error Logs',
                    ],
                ],
                [
                    'name' => 'DDoS Protection',
                    'description' => 'ป้องกันการโจมตี DDoS',
                    'icon' => 'fa-shield-virus',
                    'color' => 'from-rose-500 to-red-600',
                    'features' => [
                        'Cloudflare Integration',
                        'Rate Limiting',
                        'Bot Detection',
                        'WAF (Web Application Firewall)',
                        'Auto Scaling',
                    ],
                ],
            ],

            // มาตรฐานความปลอดภัย
            'standards' => [
                [
                    'name' => 'OWASP Top 10',
                    'description' => 'ป้องกันช่องโหว่ความปลอดภัยตามมาตรฐาน OWASP',
                    'icon' => 'fa-shield-alt',
                ],
                [
                    'name' => 'GDPR Compliance',
                    'description' => 'รองรับกฎหมายคุ้มครองข้อมูลส่วนบุคคลของ EU',
                    'icon' => 'fa-user-shield',
                ],
                [
                    'name' => 'PDPA Compliance',
                    'description' => 'รองรับ พ.ร.บ.คุ้มครองข้อมูลส่วนบุคคล ของประเทศไทย',
                    'icon' => 'fa-file-shield',
                ],
                [
                    'name' => 'PCI DSS',
                    'description' => 'มาตรฐานความปลอดภัยสำหรับข้อมูลบัตรชำระเงิน',
                    'icon' => 'fa-credit-card',
                ],
            ],

            // Backup & Recovery
            'backup_recovery' => [
                [
                    'name' => 'Automated Backups',
                    'description' => 'สำรองข้อมูลอัตโนมัติทุกวัน',
                    'frequency' => 'ทุก 6 ชั่วโมง',
                    'retention' => '90 วัน',
                ],
                [
                    'name' => 'Point-in-Time Recovery',
                    'description' => 'กู้คืนข้อมูลย้อนหลังได้ทุกเวลา',
                    'frequency' => 'Continuous',
                    'retention' => '7 วัน',
                ],
                [
                    'name' => 'Geo-Redundant Storage',
                    'description' => 'เก็บข้อมูลหลายที่ตั้งทางภูมิศาสตร์',
                    'locations' => 'Singapore, Tokyo, Sydney',
                ],
                [
                    'name' => 'Disaster Recovery Plan',
                    'description' => 'แผนกู้คืนระบบเมื่อเกิดภัยพิบัติ',
                    'rto' => '< 4 ชั่วโมง',
                    'rpo' => '< 1 ชั่วโมง',
                ],
            ],

            // การตรวจสอบและแจ้งเตือน
            'monitoring_alerts' => [
                [
                    'name' => 'Real-time Monitoring',
                    'description' => 'ตรวจสอบระบบแบบ Real-time',
                    'tools' => ['Prometheus', 'Grafana', 'New Relic'],
                ],
                [
                    'name' => 'Intrusion Detection',
                    'description' => 'ตรวจจับการบุกรุก',
                    'tools' => ['Fail2Ban', 'OSSEC', 'Snort'],
                ],
                [
                    'name' => 'Alert System',
                    'description' => 'แจ้งเตือนทันทีเมื่อพบปัญหา',
                    'channels' => ['LINE', 'Email', 'SMS', 'Slack'],
                ],
                [
                    'name' => 'Security Audits',
                    'description' => 'ตรวจสอบความปลอดภัยเป็นระยะ',
                    'frequency' => 'ทุก 3 เดือน',
                ],
            ],

            // สถิติ
            'stats' => [
                ['label' => 'Uptime', 'value' => '99.99%', 'icon' => 'fa-server'],
                ['label' => 'การโจมตีที่ป้องกัน', 'value' => '10,000+/วัน', 'icon' => 'fa-shield-alt'],
                ['label' => 'SSL Rating', 'value' => 'A+', 'icon' => 'fa-lock'],
                ['label' => 'Security Incidents', 'value' => '0', 'icon' => 'fa-check-circle'],
            ],
        ];
    }
}
