{{--
    Flow Diagrams Section - แสดง flow diagrams ของกระบวนการต่างๆ ใน TPIX

    ใช้ Mermaid.js สำหรับ rendering flow diagrams
    ประกอบด้วย:
    1. Transaction Flow Diagram
    2. Staking Process Flow
    3. Commission Distribution Flow
    4. Governance Voting Flow
--}}

<section id="flowdiagrams" class="section-padding bg-gradient-to-br from-cyan-50 via-teal-50 to-emerald-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    <div class="container mx-auto px-4">

        {{-- Header --}}
        <div class="text-center mb-16">
            <div class="inline-block px-6 py-2 gradient-primary text-white rounded-full text-sm font-bold mb-4">
                🔄 Flow Diagrams
            </div>
            <h2 class="text-5xl font-black text-gray-900 dark:text-white mb-4">
                กระบวนการทำงาน TPIX
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                แผนภาพแสดงขั้นตอนการทำงานของระบบต่างๆ ใน TPIX อย่างละเอียด
            </p>
        </div>

        {{-- Tab Navigation --}}
        <div x-data="{ activeFlow: 'transaction' }" class="space-y-8">

            {{-- Tabs --}}
            <div class="flex flex-wrap justify-center gap-3 mb-12">
                <button @click="activeFlow = 'transaction'"
                        :class="activeFlow === 'transaction' ? 'gradient-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                        class="px-6 py-3 rounded-xl font-semibold transition hover:scale-105 shadow-lg">
                    💸 Transaction Flow
                </button>
                <button @click="activeFlow = 'staking'"
                        :class="activeFlow === 'staking' ? 'gradient-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                        class="px-6 py-3 rounded-xl font-semibold transition hover:scale-105 shadow-lg">
                    🔒 Staking Process
                </button>
                <button @click="activeFlow = 'commission'"
                        :class="activeFlow === 'commission' ? 'gradient-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                        class="px-6 py-3 rounded-xl font-semibold transition hover:scale-105 shadow-lg">
                    💵 Commission Flow
                </button>
                <button @click="activeFlow = 'governance'"
                        :class="activeFlow === 'governance' ? 'gradient-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                        class="px-6 py-3 rounded-xl font-semibold transition hover:scale-105 shadow-lg">
                    🗳️ Governance Voting
                </button>
            </div>

            {{-- Tab Content --}}

            {{-- 1. Transaction Flow Diagram --}}
            <div x-show="activeFlow === 'transaction'" x-cloak class="glass rounded-3xl p-8">
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    💸 TPIX Transaction Flow
                </h3>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 overflow-x-auto">
                    <pre class="mermaid text-center">
flowchart TD
    Start([User Initiates Transaction]) --> Check{Has TPIX<br/>for Gas?}
    Check -->|No| BuyTPIX[Buy TPIX from DEX]
    Check -->|Yes| CreateTX[Create Transaction]
    BuyTPIX --> CreateTX

    CreateTX --> Sign[Sign with Private Key]
    Sign --> Broadcast[Broadcast to Network]

    Broadcast --> Mempool[Transaction Mempool]
    Mempool --> Validator[Validator Picks TX]

    Validator --> Validate{Validate<br/>Transaction}
    Validate -->|Invalid| Reject[Reject Transaction]
    Validate -->|Valid| Include[Include in Block]

    Include --> Consensus[IBFT Consensus]
    Consensus --> Finalize[Block Finalized]

    Finalize --> Execute[Execute Smart Contract]
    Execute --> UpdateState[Update Blockchain State]

    UpdateState --> CalcFee[Calculate Gas Fee]
    CalcFee --> Burn[Burn 0.1% TPIX]
    Burn --> Validator2[Pay Validator 99.9%]

    Validator2 --> Confirm[Transaction Confirmed]
    Confirm --> Notify[Notify User]
    Notify --> End([Transaction Complete])

    Reject --> RefundGas[Refund Gas to User]
    RefundGas --> End

    style Start fill:#10b981,color:#fff
    style End fill:#3b82f6,color:#fff
    style Validate fill:#f59e0b,color:#fff
    style Reject fill:#ef4444,color:#fff
    style Confirm fill:#10b981,color:#fff
    style Burn fill:#f59e0b,color:#fff
                    </pre>
                </div>
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                        <strong>💡 คำอธิบาย:</strong> กระบวนการทำธุรกรรมบน TPIX Blockchain ตั้งแต่ user สร้าง transaction
                        ผ่าน validator verification, consensus, execution จนถึงการ confirmed
                    </p>
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li>⏱️ <strong>Block Time:</strong> 2 วินาที (เร็วกว่า Bitcoin 300 เท่า)</li>
                        <li>💰 <strong>Gas Fee:</strong> <$0.01 per transaction</li>
                        <li>🔥 <strong>Burn Rate:</strong> 0.1% ของ gas fee ถูก burn ทุก transaction</li>
                        <li>✅ <strong>Finality:</strong> Transaction ถูก finalized ภายใน 2 วินาที</li>
                    </ul>
                </div>
            </div>

            {{-- 2. Staking Process Flow --}}
            <div x-show="activeFlow === 'staking'" x-cloak class="glass rounded-3xl p-8">
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    🔒 Staking Process Flow
                </h3>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 overflow-x-auto">
                    <pre class="mermaid text-center">
flowchart TD
    Start([User Wants to Stake]) --> CheckBalance{Has TPIX<br/>Balance?}
    CheckBalance -->|No| Buy[Buy TPIX]
    CheckBalance -->|Yes| SelectAmount[Select Stake Amount]
    Buy --> SelectAmount

    SelectAmount --> SelectPeriod[Choose Lock Period]
    SelectPeriod --> ShowAPY[Show APY Rate]

    ShowAPY --> Periods{Lock Period}
    Periods -->|30 Days| APY30[APY: 30%]
    Periods -->|90 Days| APY90[APY: 60%]
    Periods -->|180 Days| APY180[APY: 90%]
    Periods -->|365 Days| APY365[APY: 120%]

    APY30 --> Confirm[Confirm Staking]
    APY90 --> Confirm
    APY180 --> Confirm
    APY365 --> Confirm

    Confirm --> Approve[Approve Smart Contract]
    Approve --> LockTPIX[Lock TPIX in Contract]

    LockTPIX --> CreateStake[Create Stake Record]
    CreateStake --> StartTime[Record Start Time]
    StartTime --> CalcEndTime[Calculate End Time]

    CalcEndTime --> DailyReward[Daily Reward Accrual]
    DailyReward --> Compound{Auto<br/>Compound?}
    Compound -->|Yes| AddToStake[Add to Stake Amount]
    Compound -->|No| PendingReward[Pending Rewards]

    AddToStake --> CheckTime{Time<br/>Elapsed?}
    PendingReward --> CheckTime

    CheckTime -->|No| DailyReward
    CheckTime -->|Yes| Unlock[Unlock Period Reached]

    Unlock --> UserAction{User<br/>Action}
    UserAction -->|Withdraw| CalcTotal[Calculate Total Rewards]
    UserAction -->|Restake| NewStake[Create New Stake]

    CalcTotal --> Transfer[Transfer TPIX + Rewards]
    Transfer --> UpdateBalance[Update User Balance]
    UpdateBalance --> Notify[Notify User]

    NewStake --> SelectAmount

    Notify --> End([Staking Complete])

    style Start fill:#10b981,color:#fff
    style End fill:#3b82f6,color:#fff
    style LockTPIX fill:#f59e0b,color:#fff
    style CalcTotal fill:#10b981,color:#fff
    style APY30 fill:#8b5cf6,color:#fff
    style APY90 fill:#8b5cf6,color:#fff
    style APY180 fill:#8b5cf6,color:#fff
    style APY365 fill:#8b5cf6,color:#fff
                    </pre>
                </div>
                <div class="mt-6 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                        <strong>💡 คำอธิบาย:</strong> ขั้นตอนการ stake TPIX ตั้งแต่การเลือกจำนวนและระยะเวลา lock
                        การคำนวณ APY การ compound อัตโนมัติ จนถึงการ unlock และ withdraw
                    </p>
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li>📊 <strong>APY Range:</strong> 30% - 120% ตามระยะเวลา lock</li>
                        <li>🔄 <strong>Auto-compound:</strong> เพิ่ม APY ได้อีก 10-20%</li>
                        <li>⏰ <strong>Flexible:</strong> สามารถ restake ได้ทันทีหลัง unlock</li>
                        <li>🔒 <strong>Security:</strong> Smart contract audited และ time-locked</li>
                    </ul>
                </div>
            </div>

            {{-- 3. Commission Distribution Flow --}}
            <div x-show="activeFlow === 'commission'" x-cloak class="glass rounded-3xl p-8">
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    💵 Commission Distribution Flow
                </h3>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 overflow-x-auto">
                    <pre class="mermaid text-center">
flowchart TD
    Start([Sales Transaction Occurs]) --> Type{Transaction<br/>Type}

    Type -->|Product Sale| ProductFee[Platform Fee: 10%]
    Type -->|Service Payment| ServiceFee[Platform Fee: 15%]
    Type -->|AI Bot Sale| AIFee[Platform Fee: 20%]

    ProductFee --> TotalFee[Calculate Total Commission Pool]
    ServiceFee --> TotalFee
    AIFee --> TotalFee

    TotalFee --> CheckReferrer{Has<br/>Referrer?}
    CheckReferrer -->|No| ToPlatform[100% to Platform]
    CheckReferrer -->|Yes| GetTree[Get Referral Tree]

    GetTree --> Level1{Level 1<br/>Active?}
    Level1 -->|Yes| Pay1[Pay 5% to Level 1]
    Level1 -->|No| Skip1[Skip Level 1]

    Pay1 --> Level2{Level 2<br/>Active?}
    Skip1 --> Level2
    Level2 -->|Yes| Pay2[Pay 3% to Level 2]
    Level2 -->|No| Skip2[Skip Level 2]

    Pay2 --> Level3{Level 3<br/>Active?}
    Skip2 --> Level3
    Level3 -->|Yes| Pay3[Pay 2% to Level 3]
    Level3 -->|No| Skip3[Skip Level 3]

    Pay3 --> Level4{Level 4<br/>Active?}
    Skip3 --> Level4
    Level4 -->|Yes| Pay4[Pay 1% to Level 4]
    Level4 -->|No| Skip4[Skip Level 4]

    Pay4 --> Level5{Level 5<br/>Active?}
    Skip4 --> Level5
    Level5 -->|Yes| Pay5[Pay 0.5% to Level 5]
    Level5 -->|No| Skip5[Skip Level 5]

    Pay5 --> CheckRank[Check User Ranks]
    Skip5 --> CheckRank

    CheckRank --> RankBonus{Has Rank<br/>Bonus?}
    RankBonus -->|Yes| CalcBonus[Calculate Rank Bonus]
    RankBonus -->|No| Remaining[Calculate Remaining]

    CalcBonus --> PayBonus[Pay Rank Bonuses]
    PayBonus --> Remaining

    Remaining --> Platform[Remaining to Platform]
    ToPlatform --> Platform

    Platform --> Record[Record All Transactions]
    Record --> UpdateBalance[Update User Balances]
    UpdateBalance --> NotifyAll[Notify All Recipients]

    NotifyAll --> End([Distribution Complete])

    style Start fill:#10b981,color:#fff
    style End fill:#3b82f6,color:#fff
    style Pay1 fill:#8b5cf6,color:#fff
    style Pay2 fill:#8b5cf6,color:#fff
    style Pay3 fill:#8b5cf6,color:#fff
    style Pay4 fill:#8b5cf6,color:#fff
    style Pay5 fill:#8b5cf6,color:#fff
    style PayBonus fill:#f59e0b,color:#fff
                    </pre>
                </div>
                <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                        <strong>💡 คำอธิบาย:</strong> กระบวนการแจกจ่ายคอมมิชชั่นจากการขายผ่านระบบ MLM 5 ระดับ
                        พร้อม rank bonus และการบันทึกทุก transaction
                    </p>
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li>💰 <strong>Total Commission:</strong> สูงสุด 11.5% (5+3+2+1+0.5)</li>
                        <li>🎯 <strong>Rank Bonus:</strong> +10% ถึง +50% สำหรับ rank สูง</li>
                        <li>⚡ <strong>Real-time:</strong> Commission จ่ายแบบ real-time</li>
                        <li>📊 <strong>Transparent:</strong> ทุก transaction บันทึกบน blockchain</li>
                    </ul>
                </div>
            </div>

            {{-- 4. Governance Voting Flow --}}
            <div x-show="activeFlow === 'governance'" x-cloak class="glass rounded-3xl p-8">
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    🗳️ Governance Voting Flow
                </h3>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 overflow-x-auto">
                    <pre class="mermaid text-center">
flowchart TD
    Start([Community Member]) --> CheckBalance{Has ≥10K<br/>TPIX?}
    CheckBalance -->|No| CanVote[Can Vote Only]
    CheckBalance -->|Yes| CanPropose[Can Create Proposal]

    CanPropose --> CreateProp[Create Proposal]
    CreateProp --> SelectType{Proposal<br/>Type}

    SelectType -->|Network| Network[Network Upgrade]
    SelectType -->|Economic| Economic[Economic Policy]
    SelectType -->|Feature| Feature[Feature Request]
    SelectType -->|Treasury| Treasury[Treasury Management]

    Network --> Draft[Draft Proposal Details]
    Economic --> Draft
    Feature --> Draft
    Treasury --> Draft

    Draft --> Submit[Submit Proposal]
    Submit --> Lock[Lock 10K TPIX as Deposit]

    Lock --> Discussion[7 Days Discussion Period]
    Discussion --> Community[Community Feedback]
    Community --> Refine[Refine Proposal]

    Refine --> StartVoting[Start Voting Period]
    StartVoting --> Snapshot[Take Snapshot of Holdings]

    Snapshot --> VoteOpen[3 Days Voting Period]

    CanVote --> VoteOpen

    VoteOpen --> UserVote{User<br/>Decision}
    UserVote -->|For| VoteYes[Vote YES]
    UserVote -->|Against| VoteNo[Vote NO]
    UserVote -->|Abstain| VoteAbstain[Vote ABSTAIN]

    VoteYes --> CountVote[Count Votes]
    VoteNo --> CountVote
    VoteAbstain --> CountVote

    CountVote --> VoteEnd[Voting Period Ends]
    VoteEnd --> Calculate[Calculate Results]

    Calculate --> CheckQuorum{Quorum<br/>≥20%?}
    CheckQuorum -->|No| Failed[Proposal Failed]
    CheckQuorum -->|Yes| CheckMajority{YES Votes<br/>>50%?}

    CheckMajority -->|No| Failed
    CheckMajority -->|Yes| Passed[Proposal Passed]

    Passed --> Timelock[24h Timelock]
    Timelock --> Execute[Execute Proposal]

    Execute --> UpdateProtocol[Update Protocol]
    UpdateProtocol --> RefundDeposit[Refund 10K TPIX]
    RefundDeposit --> Reward[Pay Proposal Reward]

    Failed --> RefundDeposit2[Refund 10K TPIX]
    RefundDeposit2 --> Archive[Archive Proposal]

    Reward --> NotifyAll[Notify Community]
    Archive --> NotifyAll

    NotifyAll --> End([Voting Complete])

    style Start fill:#10b981,color:#fff
    style End fill:#3b82f6,color:#fff
    style Passed fill:#10b981,color:#fff
    style Failed fill:#ef4444,color:#fff
    style Execute fill:#f59e0b,color:#fff
    style VoteYes fill:#10b981,color:#fff
    style VoteNo fill:#ef4444,color:#fff
                    </pre>
                </div>
                <div class="mt-6 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                        <strong>💡 คำอธิบาย:</strong> กระบวนการ governance แบบ DAO ตั้งแต่การสร้าง proposal
                        การ discussion, voting, จนถึงการ execute proposal ที่ผ่าน
                    </p>
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li>🎯 <strong>Min Proposal:</strong> ต้องมี TPIX ≥10,000 และ stake 10K เป็น deposit</li>
                        <li>📅 <strong>Timeline:</strong> 7 วัน discussion + 3 วัน voting</li>
                        <li>✅ <strong>Pass Criteria:</strong> Quorum ≥20% และ YES votes >50%</li>
                        <li>⏰ <strong>Timelock:</strong> 24 ชั่วโมงก่อน execute เพื่อความปลอดภัย</li>
                        <li>🎁 <strong>Reward:</strong> Proposer ได้รับ reward หาก proposal ผ่าน</li>
                    </ul>
                </div>
            </div>

        </div>

        {{-- Note --}}
        <div class="mt-12 p-6 bg-gradient-to-r from-cyan-500/10 to-teal-500/10 dark:from-cyan-500/20 dark:to-teal-500/20 rounded-2xl border border-cyan-200 dark:border-cyan-800">
            <div class="flex items-start gap-4">
                <div class="text-3xl">🔄</div>
                <div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                        เกี่ยวกับ Flow Diagrams
                    </h4>
                    <p class="text-gray-700 dark:text-gray-300 mb-3">
                        Flow Diagrams เหล่านี้แสดงขั้นตอนการทำงานของระบบต่างๆ ใน TPIX อย่างละเอียด
                        เพื่อให้เข้าใจกระบวนการและตัดสินใจได้อย่างมั่นใจ
                    </p>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li>✅ แต่ละ step แสดง decision point และ alternative path ชัดเจน</li>
                        <li>✅ สีสันช่วยแยกแยะประเภทของ action (success, error, process)</li>
                        <li>✅ Timeline และเงื่อนไขแสดงครบถ้วนในแต่ละ diagram</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</section>
