{{--
    Mind Maps Section - แสดง mind maps ต่างๆ ของระบบ TPIX

    ใช้ Mermaid.js สำหรับ rendering mind maps
    ประกอบด้วย:
    1. Ecosystem Overview Mind Map
    2. MLM Structure Mind Map
    3. Token Flow Mind Map
    4. Commission Distribution Mind Map
    5. Technical Architecture Mind Map
    6. Governance Model Mind Map
--}}

<section id="mindmaps" class="section-padding bg-gradient-to-br from-purple-50 via-blue-50 to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    <div class="container mx-auto px-4">

        {{-- Header --}}
        <div class="text-center mb-16">
            <div class="inline-block px-6 py-2 gradient-primary text-white rounded-full text-sm font-bold mb-4">
                📊 Mind Maps
            </div>
            <h2 class="text-5xl font-black text-gray-900 dark:text-white mb-4">
                แผนที่ความคิด TPIX
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                ภาพรวมระบบนิเวศและโครงสร้างของ TPIX ในรูปแบบ Mind Maps
            </p>
        </div>

        {{-- Tab Navigation --}}
        <div x-data="{ activeTab: 'ecosystem' }" class="space-y-8">

            {{-- Tabs --}}
            <div class="flex flex-wrap justify-center gap-3 mb-12">
                <button @click="activeTab = 'ecosystem'"
                        :class="activeTab === 'ecosystem' ? 'gradient-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                        class="px-6 py-3 rounded-xl font-semibold transition hover:scale-105 shadow-lg">
                    🌐 Ecosystem
                </button>
                <button @click="activeTab = 'mlm'"
                        :class="activeTab === 'mlm' ? 'gradient-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                        class="px-6 py-3 rounded-xl font-semibold transition hover:scale-105 shadow-lg">
                    👥 MLM Structure
                </button>
                <button @click="activeTab = 'tokenflow'"
                        :class="activeTab === 'tokenflow' ? 'gradient-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                        class="px-6 py-3 rounded-xl font-semibold transition hover:scale-105 shadow-lg">
                    💰 Token Flow
                </button>
                <button @click="activeTab = 'commission'"
                        :class="activeTab === 'commission' ? 'gradient-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                        class="px-6 py-3 rounded-xl font-semibold transition hover:scale-105 shadow-lg">
                    💵 Commission
                </button>
                <button @click="activeTab = 'tech'"
                        :class="activeTab === 'tech' ? 'gradient-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                        class="px-6 py-3 rounded-xl font-semibold transition hover:scale-105 shadow-lg">
                    🔧 Technical
                </button>
                <button @click="activeTab = 'governance'"
                        :class="activeTab === 'governance' ? 'gradient-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                        class="px-6 py-3 rounded-xl font-semibold transition hover:scale-105 shadow-lg">
                    ⚖️ Governance
                </button>
            </div>

            {{-- Tab Content --}}

            {{-- 1. Ecosystem Overview Mind Map --}}
            <div x-show="activeTab === 'ecosystem'" x-cloak class="glass rounded-3xl p-8">
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    🌐 TPIX Ecosystem Overview
                </h3>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 overflow-x-auto">
                    <pre class="mermaid text-center">
mindmap
  root((TPIX<br/>Ecosystem))
    Blockchain Layer
      Own Blockchain
        Chain ID: 7000
        Consensus: IBFT
        Block Time: 2s
      EVM Compatible
        Smart Contracts
        DApps Support
        Web3 Integration
      Performance
        1500 TPS
        Low Fees <$0.01
        Fast Finality
    Token Layer
      TPIX Token
        Fixed Supply: 7B
        Native Gas
        Utility Token
      Staking
        APY: 30-120%
        Lock Periods
        Auto-compound
      Distribution
        Ecosystem 30%
        Rewards 25%
        Staking 20%
    Application Layer
      E-Commerce
        TP Affiliate
        Product Sales
        Seller Platform
      AI Services
        AI Bots
        LINE Bots
        Automation
      DeFi
        Swaps
        Liquidity
        Yield Farming
      Supply Chain
        Food Passport
        Traceability
        Verification
    User Layer
      Affiliates
        MLM 5 Levels
        Commission 11.5%
        Referral Rewards
      Customers
        Shopping
        AI Services
        Staking
      Developers
        Build DApps
        APIs
        SDKs
                    </pre>
                </div>
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <strong>💡 คำอธิบาย:</strong> Mind map นี้แสดงภาพรวมระบบนิเวศ TPIX ซึ่งประกอบด้วย 4 layers หลัก:
                        Blockchain Layer (โครงสร้างพื้นฐาน), Token Layer (ระบบ token), Application Layer (แอปพลิเคชัน),
                        และ User Layer (ผู้ใช้งาน)
                    </p>
                </div>
            </div>

            {{-- 2. MLM Structure Mind Map --}}
            <div x-show="activeTab === 'mlm'" x-cloak class="glass rounded-3xl p-8">
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    👥 MLM Structure & Commission
                </h3>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 overflow-x-auto">
                    <pre class="mermaid text-center">
mindmap
  root((MLM<br/>System))
    Commission Structure
      Level 1: 5%
        Direct Referral
        Highest Rate
        Immediate Reward
      Level 2: 3%
        2nd Generation
        Good Passive Income
      Level 3: 2%
        3rd Generation
        Building Network
      Level 4: 1%
        4th Generation
        Long-term Growth
      Level 5: 0.5%
        5th Generation
        Total: 11.5%
    Earning Methods
      Product Sales
        E-commerce
        AI Bots
        Services
      Staking Rewards
        Lock TPIX
        Earn APY
        Compound
      Referral Bonus
        Signup Bonus
        Activity Bonus
        Milestone Reward
    Rank System
      Bronze
        Entry Level
        Basic Commission
      Silver
        10+ Referrals
        +10% Bonus
      Gold
        50+ Referrals
        +20% Bonus
        Team Support
      Platinum
        100+ Referrals
        +30% Bonus
        Leadership Pool
      Diamond
        500+ Referrals
        +50% Bonus
        Profit Share
    Network Growth
      Binary Tree
        Left Team
        Right Team
        Balance Bonus
      Team Building
        Training
        Support
        Mentorship
      Spillover
        Auto-placement
        Team Growth
        Fair Distribution
                    </pre>
                </div>
                <div class="mt-6 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <strong>💡 คำอธิบาย:</strong> โครงสร้าง MLM 5 ระดับพร้อมระบบ rank ที่ช่วยให้สมาชิกสร้างรายได้แบบ passive income
                        ผ่านการแนะนำสมาชิกใหม่และการสร้าง network
                    </p>
                </div>
            </div>

            {{-- 3. Token Flow Mind Map --}}
            <div x-show="activeTab === 'tokenflow'" x-cloak class="glass rounded-3xl p-8">
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    💰 TPIX Token Flow
                </h3>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 overflow-x-auto">
                    <pre class="mermaid text-center">
mindmap
  root((TPIX<br/>Token Flow))
    Token Generation
      Initial Supply
        7B Fixed
        No Minting
        Deflationary
      Distribution Events
        TGE: 10%
        Vesting Schedule
        Fair Launch
    Token Inflow
      Purchases
        Buy from DEX
        Buy from CEX
        P2P Trading
      Earning
        Staking Rewards
        Commission
        Airdrops
      Services Payment
        Gas Fees
        Platform Fees
        Subscriptions
    Token Outflow
      Spending
        Product Purchase
        Service Payment
        Transaction Fee
      Trading
        Sell on DEX
        Sell on CEX
        Swap to other tokens
      Staking
        Lock in Contract
        Long-term Hold
        Earn Rewards
    Token Utility
      Gas Token
        Transaction Fees
        Smart Contract
        Network Security
      Payment Method
        Buy Products
        Pay Services
        Subscriptions
      Governance
        Voting Rights
        Proposals
        DAO Participation
      Staking
        Earn APY
        Network Security
        Passive Income
    Token Burning
      Transaction Burns
        0.1% per TX
        Deflationary
      Buyback & Burn
        Platform Profit
        Market Support
        Supply Reduction
                    </pre>
                </div>
                <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <strong>💡 คำอธิบาย:</strong> แสดงวงจรการไหลของ TPIX token ตั้งแต่การสร้าง การไหลเข้า-ออก
                        การใช้งาน และกระบวนการ burning เพื่อรักษามูลค่า token
                    </p>
                </div>
            </div>

            {{-- 4. Commission Distribution Mind Map --}}
            <div x-show="activeTab === 'commission'" x-cloak class="glass rounded-3xl p-8">
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    💵 Commission Distribution System
                </h3>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 overflow-x-auto">
                    <pre class="mermaid text-center">
mindmap
  root((Commission<br/>Distribution))
    Revenue Sources
      Product Sales
        E-commerce: 10%
        Digital Products: 15%
        AI Services: 20%
      Service Fees
        Transaction Fees
        Platform Fees
        Subscription Fees
      Blockchain Fees
        Gas Fees
        Smart Contract Fees
        Network Fees
    Distribution Rules
      MLM Commission
        Level 1: 5%
        Level 2: 3%
        Level 3: 2%
        Level 4: 1%
        Level 5: 0.5%
      Rank Bonus
        Bronze: Base
        Silver: +10%
        Gold: +20%
        Platinum: +30%
        Diamond: +50%
      Performance Bonus
        Monthly Target
        Team Performance
        Leadership Pool
    Payment Schedule
      Real-time
        Instant Commission
        Direct Referral
        Auto-deposit
      Daily Settlement
        Accumulated
        Batch Process
        Gas Optimization
      Monthly Bonus
        Rank Bonus
        Performance
        Team Building
    Withdrawal Methods
      To Wallet
        TPIX Token
        Instant
        No Fee
      To Bank
        THB Fiat
        1-3 Days
        2% Fee
      Reinvest
        Auto Staking
        Compound
        Max Growth
    Commission Tracking
      Dashboard
        Real-time Balance
        Transaction History
        Referral Tree
      Reports
        Daily Summary
        Monthly Report
        Tax Documents
      Notifications
        Instant Alert
        Email Report
        LINE Notify
                    </pre>
                </div>
                <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <strong>💡 คำอธิบาย:</strong> ระบบการแจกจ่ายคอมมิชชั่นแบบโปร่งใส ตั้งแต่แหล่งรายได้ กฎการแจกจ่าย
                        ตารางจ่าย วิธีการถอน และการติดตามผล
                    </p>
                </div>
            </div>

            {{-- 5. Technical Architecture Mind Map --}}
            <div x-show="activeTab === 'tech'" x-cloak class="glass rounded-3xl p-8">
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    🔧 Technical Architecture
                </h3>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 overflow-x-auto">
                    <pre class="mermaid text-center">
mindmap
  root((Technical<br/>Architecture))
    Blockchain Core
      Consensus
        IBFT 2.0
        Validator Nodes
        Byzantine Fault Tolerant
      Network
        Chain ID: 7000
        Block Time: 2s
        TPS: 1500
      EVM
        Solidity
        Web3 Compatible
        Ethereum Tools
    Smart Contracts
      Core Contracts
        TPIX Token
        Staking Contract
        Governance
      Application Contracts
        MLM Logic
        Commission
        Referral System
      Security
        Audited
        Multi-sig
        Time-locks
    Backend Services
      API Layer
        RESTful API
        GraphQL
        WebSocket
      Database
        PostgreSQL
        Redis Cache
        MongoDB
      Queue System
        Laravel Queue
        Job Processing
        Event Driven
    Frontend Apps
      Web App
        Laravel + Vite
        Tailwind CSS
        Alpine.js
      Mobile App
        .NET MAUI
        iOS + Android
        Native Performance
      DApp
        Web3.js
        MetaMask
        WalletConnect
    Infrastructure
      Hosting
        Cloud Servers
        Load Balancers
        Auto-scaling
      Security
        SSL/TLS
        DDoS Protection
        Firewall
      Monitoring
        Server Monitoring
        Blockchain Explorer
        Alert System
    Integration
      Payment Gateway
        Bank Transfer
        Credit Card
        QR Payment
      External APIs
        LINE API
        Google Cloud
        Email Service
      Blockchain Bridge
        Ethereum
        BSC
        Polygon
                    </pre>
                </div>
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <strong>💡 คำอธิบาย:</strong> สถาปัตยกรรมทางเทคนิคของ TPIX ตั้งแต่ blockchain core, smart contracts,
                        backend services, frontend apps, infrastructure และ integrations
                    </p>
                </div>
            </div>

            {{-- 6. Governance Model Mind Map --}}
            <div x-show="activeTab === 'governance'" x-cloak class="glass rounded-3xl p-8">
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    ⚖️ Governance Model
                </h3>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 overflow-x-auto">
                    <pre class="mermaid text-center">
mindmap
  root((TPIX<br/>Governance))
    DAO Structure
      Token Holders
        1 TPIX = 1 Vote
        Stakers Priority
        Min 1000 TPIX
      Validators
        Network Security
        Block Production
        Governance Vote
      Core Team
        Development
        Operations
        Strategic Planning
    Proposal Types
      Network Upgrade
        Protocol Changes
        Hard Fork
        Security Patch
      Economic Policy
        Fee Structure
        Staking APY
        Burning Rate
      Feature Request
        New Features
        DApp Integration
        Partnerships
      Treasury
        Budget Allocation
        Marketing Spend
        Development Fund
    Voting Process
      Proposal Creation
        Min 10K TPIX
        Detailed Plan
        Community Discussion
      Voting Period
        7 Days Discussion
        3 Days Voting
        Snapshot Height
      Execution
        Pass: >50%
        Quorum: 20%
        Time-lock: 24h
    Governance Powers
      Parameter Changes
        Block Reward
        Gas Price
        Validator Count
      Treasury Management
        Fund Allocation
        Grant Programs
        Partnerships
      Emergency Actions
        Security Patches
        Network Halt
        Recovery Plan
    Incentives
      Voter Rewards
        Participation Bonus
        Governance Token
        NFT Badges
      Proposal Rewards
        Successful Proposal
        Implementation Bonus
        Recognition
      Validator Rewards
        Block Rewards
        Transaction Fees
        Governance Bonus
                    </pre>
                </div>
                <div class="mt-6 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <strong>💡 คำอธิบาย:</strong> โมเดลการกำกับดูแลแบบ DAO ที่ให้ token holders มีส่วนร่วมในการตัดสินใจ
                        ผ่านระบบ proposal และ voting ที่โปร่งใส
                    </p>
                </div>
            </div>

        </div>

        {{-- Note --}}
        <div class="mt-12 p-6 bg-gradient-to-r from-blue-500/10 to-purple-500/10 dark:from-blue-500/20 dark:to-purple-500/20 rounded-2xl border border-blue-200 dark:border-blue-800">
            <div class="flex items-start gap-4">
                <div class="text-3xl">💡</div>
                <div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                        เกี่ยวกับ Mind Maps
                    </h4>
                    <p class="text-gray-700 dark:text-gray-300 mb-3">
                        Mind Maps เหล่านี้แสดงภาพรวมของระบบ TPIX ในมุมมองต่างๆ เพื่อให้เข้าใจโครงสร้างและการทำงานของระบบได้อย่างรวดเร็วและชัดเจน
                    </p>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li>✅ Mind Maps สามารถ zoom และ pan ได้บนอุปกรณ์ที่รองรับ</li>
                        <li>✅ แต่ละ node แสดงข้อมูลที่สำคัญของส่วนนั้นๆ</li>
                        <li>✅ สีสันและโครงสร้างช่วยให้จำและเข้าใจได้ง่าย</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</section>

{{-- Mermaid.js Initialization Script --}}
<script>
    // Initialize Mermaid for mind maps rendering
    if (typeof mermaid !== 'undefined') {
        mermaid.initialize({
            startOnLoad: true,
            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
            mindmap: {
                padding: 20,
                maxWidth: 1200
            }
        });
    }

    // Re-render mermaid diagrams when tab changes
    document.addEventListener('alpine:initialized', () => {
        // Listen for tab changes and re-render mermaid
        setTimeout(() => {
            if (typeof mermaid !== 'undefined') {
                mermaid.contentLoaded();
            }
        }, 100);
    });
</script>
