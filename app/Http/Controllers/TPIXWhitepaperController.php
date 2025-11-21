<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * TPIX Whitepaper Controller
 *
 * จัดการการแสดงและ export ไวท์เปเปอร์ของ TPIX
 */
class TPIXWhitepaperController extends Controller
{
    /**
     * แสดงหน้าไวท์เปเปอร์
     *
     * @return View
     */
    public function index(): View
    {
        // ข้อมูลสำหรับไวท์เปเปอร์
        $data = $this->getWhitepaperData();

        return view('tpix.whitepaper.index', [
            'data' => $data,
            'pageTitle' => 'TPIX Whitepaper - Native Cryptocurrency for Thaiprompt Affiliate',
        ]);
    }

    /**
     * แสดงหน้า PDF (สำหรับ Print to PDF)
     *
     * @return \Illuminate\View\View
     */
    public function exportPdf(): View
    {
        // ข้อมูลสำหรับไวท์เปเปอร์
        $data = $this->getWhitepaperData();

        // แสดง PDF view (ผู้ใช้สามารถ Print to PDF ได้จาก browser)
        return view('tpix.whitepaper.pdf', [
            'data' => $data,
        ]);
    }

    /**
     * ดึงข้อมูลสำหรับไวท์เปเปอร์
     *
     * @return array
     */
    private function getWhitepaperData(): array
    {
        return [
            // ข้อมูลพื้นฐาน
            'version' => '1.0',
            'date' => '2024-11-21',
            'language' => 'th',

            // ข้อมูล TPIX Blockchain
            'blockchain' => [
                'name' => 'TPIX Network',
                'native_coin' => 'TPIX',
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
                    ['name' => 'Ecosystem Development', 'percentage' => 30, 'amount' => 2100000000],
                    ['name' => 'Affiliate Rewards', 'percentage' => 25, 'amount' => 1750000000],
                    ['name' => 'Staking Rewards', 'percentage' => 20, 'amount' => 1400000000],
                    ['name' => 'Team & Advisors', 'percentage' => 15, 'amount' => 1050000000],
                    ['name' => 'Marketing & Partnerships', 'percentage' => 10, 'amount' => 700000000],
                ],
            ],

            // Use Cases
            'use_cases' => [
                [
                    'title' => 'ระบบรางวัล Affiliate',
                    'description' => 'ผู้ใช้สามารถรับ TPIX จากการทำกิจกรรมต่างๆ เช่น แนะนำสมาชิกใหม่ ซื้อสินค้า ทำ Quest',
                    'icon' => 'gift',
                    'features' => [
                        'รับ 100 TPIX เมื่อสมัครสมาชิกใหม่',
                        'รับ 50 TPIX เมื่อแนะนำเพื่อน',
                        'Cashback 5% เป็น TPIX จากการซื้อสินค้า',
                        'รับ 25 TPIX จากการทำ Quest สำเร็จ',
                    ],
                ],
                [
                    'title' => 'ระบบชำระเงินภายใน',
                    'description' => 'ใช้ TPIX ชำระค่าสินค้าและบริการต่างๆ ภายในระบบ Thaiprompt Affiliate',
                    'icon' => 'credit-card',
                    'features' => [
                        'ซื้อ AI Bot Packages',
                        'ซื้อ Trading Bot Subscriptions',
                        'ชำระค่า Hotel Booking',
                        'ซื้อ Digital Products',
                    ],
                ],
                [
                    'title' => 'Token Economy',
                    'description' => 'สร้าง custom tokens บน TPIX blockchain สำหรับธุรกิจของคุณ',
                    'icon' => 'coins',
                    'features' => [
                        'Point Token (สะสมแต้ม)',
                        'Voucher Token (บัตรกำนัล)',
                        'Reward Token (รางวัล)',
                        'Membership Token (สมาชิก VIP)',
                    ],
                ],
                [
                    'title' => 'Decentralized Exchange (DEX)',
                    'description' => 'ซื้อขาย tokens และเพิ่ม liquidity เพื่อรับผลตอบแทน',
                    'icon' => 'exchange',
                    'features' => [
                        'Swap tokens ด้วย AMM',
                        'เพิ่ม Liquidity รับค่าธรรมเนียม 0.3%',
                        'Farming & Yield Optimization',
                        'ติดตาม Impermanent Loss',
                    ],
                ],
                [
                    'title' => 'Staking Pools',
                    'description' => 'Stake TPIX เพื่อรับผลตอบแทน (APY)',
                    'icon' => 'piggy-bank',
                    'features' => [
                        'APY สูงสุด 120% ต่อปี',
                        'Lock periods หลากหลาย (7-365 วัน)',
                        'Auto-compound rewards',
                        'Unstake ได้ทันที (flexible staking)',
                    ],
                ],
                [
                    'title' => 'Smart Contract DApps',
                    'description' => 'พัฒนา DApps บน TPIX blockchain ด้วย Solidity',
                    'icon' => 'code',
                    'features' => [
                        'Loyalty Program Contracts',
                        'Multi-signature Wallets',
                        'Escrow Services',
                        'NFT Marketplaces',
                    ],
                ],
            ],

            // Features
            'features' => [
                'token_management' => [
                    'title' => 'Token Management',
                    'items' => [
                        'สร้าง custom ERC20 tokens',
                        'Deploy ไปยัง TPIX blockchain',
                        'Fixed หรือ Mintable supply',
                        'Burnable tokens',
                        'Pausable contracts',
                        'Address freezing',
                    ],
                ],
                'dex' => [
                    'title' => 'Decentralized Exchange',
                    'items' => [
                        'Automated Market Maker (AMM)',
                        'Token swaps (constant product formula)',
                        'Liquidity pools management',
                        'Add/Remove liquidity',
                        'LP token minting',
                        'Price impact calculation',
                        'Slippage protection',
                        'Fee distribution (0.3% to LPs)',
                    ],
                ],
                'security' => [
                    'title' => 'Security & Performance',
                    'items' => [
                        'IBFT Consensus (Byzantine Fault Tolerant)',
                        'Form request validation',
                        'Policy-based authorization',
                        'Rate limiting (5-50 req/hr)',
                        'Redis caching layer',
                        'Query optimization',
                    ],
                ],
            ],

            // Roadmap
            'roadmap' => [
                [
                    'phase' => 'Phase 1: Foundation',
                    'status' => 'completed',
                    'quarter' => 'Q4 2023',
                    'milestones' => [
                        'Blockchain core implementation ✅',
                        'TPIX native coin (7B fixed supply) ✅',
                        'IBFT consensus mechanism ✅',
                        'EVM integration ✅',
                        'Genesis configuration ✅',
                    ],
                ],
                [
                    'phase' => 'Phase 2: Integration',
                    'status' => 'completed',
                    'quarter' => 'Q1 2024',
                    'milestones' => [
                        'Laravel service integration ✅',
                        'REST API endpoints ✅',
                        'PHP service layer ✅',
                        'Block explorer ✅',
                        'Docker deployment ✅',
                        'Monitoring (Prometheus + Grafana) ✅',
                    ],
                ],
                [
                    'phase' => 'Phase 3: Enhancement',
                    'status' => 'in_progress',
                    'quarter' => 'Q2-Q3 2024',
                    'milestones' => [
                        'Multi-node testnet 🚧',
                        'Faucet service (แจก TPIX ฟรี) 🚧',
                        'Advanced monitoring dashboards 🚧',
                        'Performance optimization 🚧',
                        'SDK development (PHP, JS, Python) 🚧',
                    ],
                ],
                [
                    'phase' => 'Phase 4: Production',
                    'status' => 'planned',
                    'quarter' => 'Q4 2024',
                    'milestones' => [
                        'Mainnet launch 📅',
                        'Mobile wallet app 📅',
                        'Bridge to other blockchains 📅',
                        'Governance system 📅',
                        'Lightning Network (Layer 2) 📅',
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
                    'Chart.js' => 'Data visualization',
                    'ethers.js' => 'Web3 library',
                ],
                'infrastructure' => [
                    'Docker' => 'Containerization',
                    'Blockscout' => 'Block explorer',
                    'Prometheus' => 'Metrics collection',
                    'Grafana' => 'Monitoring dashboards',
                ],
            ],

            // Integration Points
            'integrations' => [
                'thaiprompt_affiliate' => [
                    'title' => 'Thaiprompt Affiliate System',
                    'description' => 'ระบบ MLM และ Affiliate Marketing',
                    'connections' => [
                        'Wallet Integration' => 'ผู้ใช้มี TPIX wallet อัตโนมัติ',
                        'Reward System' => 'รับ TPIX จากการทำกิจกรรม',
                        'Commission Payout' => 'จ่ายคอมมิชชั่นเป็น TPIX',
                        'Rank Bonuses' => 'โบนัสจากการเลื่อนระดับ',
                    ],
                ],
                'ecommerce' => [
                    'title' => 'E-Commerce Platform',
                    'description' => 'ระบบขายสินค้าออนไลน์',
                    'connections' => [
                        'Payment Gateway' => 'รับชำระเงินด้วย TPIX',
                        'Cashback' => 'คืนเงิน 5% เป็น TPIX',
                        'Loyalty Points' => 'แต้มสะสมเป็น TPIX',
                        'Merchant Settlement' => 'โอนเงินให้ผู้ขาย',
                    ],
                ],
                'ai_bots' => [
                    'title' => 'AI Bot Marketplace',
                    'description' => 'ตลาดซื้อขาย AI Bots',
                    'connections' => [
                        'Bot Purchase' => 'ซื้อ bots ด้วย TPIX',
                        'Subscription' => 'จ่ายค่าใช้งานรายเดือน',
                        'Revenue Share' => 'แบ่งรายได้เป็น TPIX',
                        'Creator Rewards' => 'รางวัลสำหรับผู้สร้าง',
                    ],
                ],
                'hotels' => [
                    'title' => 'Hotel Booking System',
                    'description' => 'ระบบจองโรงแรม',
                    'connections' => [
                        'Booking Payment' => 'จ่ายค่าห้องด้วย TPIX',
                        'Cashback Rewards' => 'คืนเงิน 3% เป็น TPIX',
                        'Loyalty Program' => 'สะสมแต้มเป็น TPIX',
                        'Hotel Settlement' => 'โอนเงินให้โรงแรม',
                    ],
                ],
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
}
