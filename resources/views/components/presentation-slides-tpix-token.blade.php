{{--
    TPIX Token Slides - ระบบ Cryptocurrency (8 Slides)
    เนื้อหา: Introduction, Tokenomics, Use Cases, Wallet, Earn, Spend, Roadmap, Get Started
--}}

{{-- Slide 1: TPIX Token Introduction --}}
<div class="slide" data-topic="tpix-token">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="text-center max-w-5xl">
            {{-- TPIX Logo --}}
            <div class="mb-8">
                <div class="w-40 h-40 mx-auto bg-gradient-to-br from-yellow-400 via-orange-500 to-red-500 rounded-full flex items-center justify-center shadow-2xl animate-pulse">
                    <div class="w-32 h-32 bg-gradient-to-br from-yellow-300 to-orange-400 rounded-full flex items-center justify-center">
                        <span class="text-4xl font-black text-white">TPIX</span>
                    </div>
                </div>
            </div>

            <h1 class="text-5xl md:text-6xl font-black text-white mb-6">
                <span class="bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500 bg-clip-text text-transparent">TPIX Token</span>
            </h1>

            <p class="text-2xl text-white/80 mb-8">
                Utility Token แห่งอนาคต<br>
                <span class="text-yellow-400">สร้างมูลค่า • ใช้งานจริง • เติบโตยั่งยืน</span>
            </p>

            {{-- Key Metrics --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-10">
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 border border-yellow-500/30">
                    <div class="text-3xl font-black text-yellow-400">100M</div>
                    <div class="text-white/60 text-sm">Total Supply</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 border border-orange-500/30">
                    <div class="text-3xl font-black text-orange-400">ERC-20</div>
                    <div class="text-white/60 text-sm">Token Standard</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 border border-red-500/30">
                    <div class="text-3xl font-black text-red-400">2%</div>
                    <div class="text-white/60 text-sm">Transaction Fee</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 border border-pink-500/30">
                    <div class="text-3xl font-black text-pink-400">18%</div>
                    <div class="text-white/60 text-sm">APY Staking</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 2: Tokenomics & Burning Mechanism --}}
<div class="slide" data-topic="tpix-token">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="w-full max-w-6xl">
            <div class="text-center mb-8">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    <i class="fas fa-chart-pie text-yellow-400 mr-4"></i>
                    Tokenomics
                </h2>
                <p class="text-xl text-white/70">การกระจาย Token และกลไกการเผา</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                {{-- Distribution --}}
                <div class="space-y-3">
                    <h3 class="text-2xl font-bold text-white mb-4"><i class="fas fa-coins text-yellow-400 mr-2"></i>การกระจาย Token</h3>

                    <div class="flex items-center gap-3 bg-blue-500/20 backdrop-blur-sm rounded-xl p-3 border border-blue-500/30">
                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                        <div class="flex-1">
                            <div class="text-white font-bold text-sm">Community & Rewards</div>
                            <div class="text-white/60 text-xs">30% - สำหรับรางวัลผู้ใช้งาน</div>
                        </div>
                        <div class="text-xl font-black text-blue-400">30M</div>
                    </div>

                    <div class="flex items-center gap-3 bg-emerald-500/20 backdrop-blur-sm rounded-xl p-3 border border-emerald-500/30">
                        <div class="w-3 h-3 bg-emerald-500 rounded-full"></div>
                        <div class="flex-1">
                            <div class="text-white font-bold text-sm">Reserve & Treasury</div>
                            <div class="text-white/60 text-xs">25% - สำรองและคลัง</div>
                        </div>
                        <div class="text-xl font-black text-emerald-400">25M</div>
                    </div>

                    <div class="flex items-center gap-3 bg-purple-500/20 backdrop-blur-sm rounded-xl p-3 border border-purple-500/30">
                        <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                        <div class="flex-1">
                            <div class="text-white font-bold text-sm">Development & Team</div>
                            <div class="text-white/60 text-xs">20% - พัฒนาระบบ (Vested 3 ปี)</div>
                        </div>
                        <div class="text-xl font-black text-purple-400">20M</div>
                    </div>

                    <div class="flex items-center gap-3 bg-pink-500/20 backdrop-blur-sm rounded-xl p-3 border border-pink-500/30">
                        <div class="w-3 h-3 bg-pink-500 rounded-full"></div>
                        <div class="flex-1">
                            <div class="text-white font-bold text-sm">Marketing & Partners</div>
                            <div class="text-white/60 text-xs">15% - การตลาดและพันธมิตร</div>
                        </div>
                        <div class="text-xl font-black text-pink-400">15M</div>
                    </div>

                    <div class="flex items-center gap-3 bg-amber-500/20 backdrop-blur-sm rounded-xl p-3 border border-amber-500/30">
                        <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
                        <div class="flex-1">
                            <div class="text-white font-bold text-sm">Liquidity Pool</div>
                            <div class="text-white/60 text-xs">10% - สภาพคล่องใน DEX</div>
                        </div>
                        <div class="text-xl font-black text-amber-400">10M</div>
                    </div>
                </div>

                {{-- Burning Mechanism --}}
                <div class="space-y-4">
                    <h3 class="text-2xl font-bold text-white mb-4"><i class="fas fa-fire text-orange-400 mr-2"></i>กลไกการเผา Token</h3>

                    <div class="bg-gradient-to-br from-red-500/20 to-orange-500/20 backdrop-blur-sm rounded-2xl p-5 border border-red-500/30">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-orange-500 rounded-xl flex items-center justify-center">
                                <i class="fas fa-fire-alt text-xl text-white"></i>
                            </div>
                            <div>
                                <div class="text-white font-bold">Auto-Burn Mechanism</div>
                                <div class="text-white/60 text-sm">เผาอัตโนมัติทุกธุรกรรม</div>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center justify-between text-white/80">
                                <span>• ค่าธรรมเนียม 2% ต่อธุรกรรม</span>
                                <span class="text-red-400 font-bold">0.5% เผา</span>
                            </div>
                            <div class="flex items-center justify-between text-white/80">
                                <span>• ซื้อสินค้าด้วย TPIX</span>
                                <span class="text-red-400 font-bold">1% เผา</span>
                            </div>
                            <div class="flex items-center justify-between text-white/80">
                                <span>• Unstake ก่อนกำหนด</span>
                                <span class="text-red-400 font-bold">5% เผา</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-yellow-500/20 to-amber-500/20 backdrop-blur-sm rounded-2xl p-5 border border-yellow-500/30">
                        <div class="text-center">
                            <i class="fas fa-chart-line text-3xl text-yellow-400 mb-2"></i>
                            <div class="text-white font-bold mb-1">Deflationary Model</div>
                            <div class="text-white/70 text-sm">ยิ่งใช้งานมาก Token ยิ่งหายาก</div>
                            <div class="mt-3 text-2xl font-black text-yellow-400">↗ เพิ่มมูลค่า</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 3: Use Cases - การใช้งาน TPIX --}}
<div class="slide" data-topic="tpix-token">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="w-full max-w-6xl">
            <div class="text-center mb-10">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    <i class="fas fa-coins text-orange-400 mr-4"></i>
                    ใช้งาน TPIX ได้อย่างไร?
                </h2>
                <p class="text-xl text-white/70">Utility Token ที่มีประโยชน์จริง</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Use Case 1: Payment --}}
                <div class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 backdrop-blur-sm rounded-2xl p-5 border border-green-500/30 hover:scale-105 transition-transform">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mb-3">
                        <i class="fas fa-shopping-bag text-xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">ชำระเงิน</h3>
                    <p class="text-white/70 text-sm">ซื้อสินค้า ส่วนลด 5-10%</p>
                </div>

                {{-- Use Case 2: Commission --}}
                <div class="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 backdrop-blur-sm rounded-2xl p-5 border border-blue-500/30 hover:scale-105 transition-transform">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center mb-3">
                        <i class="fas fa-percent text-xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">รางวัล</h3>
                    <p class="text-white/70 text-sm">คอมมิชชั่น +20%</p>
                </div>

                {{-- Use Case 3: Staking --}}
                <div class="bg-gradient-to-br from-purple-500/20 to-pink-500/20 backdrop-blur-sm rounded-2xl p-5 border border-purple-500/30 hover:scale-105 transition-transform">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mb-3">
                        <i class="fas fa-lock text-xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Staking</h3>
                    <p class="text-white/70 text-sm">ดอกเบี้ย 18% APY</p>
                </div>

                {{-- Use Case 4: Governance --}}
                <div class="bg-gradient-to-br from-yellow-500/20 to-orange-500/20 backdrop-blur-sm rounded-2xl p-5 border border-yellow-500/30 hover:scale-105 transition-transform">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center mb-3">
                        <i class="fas fa-vote-yea text-xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">โหวต</h3>
                    <p class="text-white/70 text-sm">ร่วมตัดสินใจ</p>
                </div>
            </div>

            {{-- Additional Use Cases Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-6">
                {{-- AI Bot --}}
                <div class="bg-gradient-to-br from-cyan-500/10 to-blue-500/10 backdrop-blur-sm rounded-2xl p-5 border border-cyan-500/30">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-robot text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-bold mb-1">ซื้อ AI Bot</h3>
                            <p class="text-white/60 text-sm">AI Bot และ Premium Features</p>
                        </div>
                    </div>
                </div>

                {{-- VIP Access --}}
                <div class="bg-gradient-to-br from-red-500/10 to-rose-500/10 backdrop-blur-sm rounded-2xl p-5 border border-red-500/30">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-rose-500 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-crown text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-bold mb-1">VIP Access</h3>
                            <p class="text-white/60 text-sm">Early Access & Exclusive Events</p>
                        </div>
                    </div>
                </div>

                {{-- NFT Marketplace --}}
                <div class="bg-gradient-to-br from-indigo-500/10 to-purple-500/10 backdrop-blur-sm rounded-2xl p-5 border border-indigo-500/30">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-gem text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-bold mb-1">NFT Trading</h3>
                            <p class="text-white/60 text-sm">ซื้อขาย NFT ในระบบ</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 4: Wallet & Storage - กระเป๋า TPIX --}}
<div class="slide" data-topic="tpix-token">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="w-full max-w-6xl">
            <div class="text-center mb-8">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    <i class="fas fa-wallet text-yellow-400 mr-4"></i>
                    เก็บ TPIX อย่างไร?
                </h2>
                <p class="text-xl text-white/70">กระเป๋าและความปลอดภัย</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Built-in Wallet --}}
                <div class="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 backdrop-blur-sm rounded-2xl p-6 border border-blue-500/30">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-mobile-alt text-2xl text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-white">TP Wallet</h3>
                            <p class="text-cyan-300 text-sm">กระเป๋าในระบบ (แนะนำ)</p>
                        </div>
                    </div>
                    <ul class="space-y-2 text-white/80">
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-400"></i>
                            <span>สร้างอัตโนมัติเมื่อสมัคร</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-400"></i>
                            <span>ไม่ต้องจัดการ Private Key</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-400"></i>
                            <span>โอนฟรีภายในระบบ</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-400"></i>
                            <span>รักษาความปลอดภัยสูง</span>
                        </li>
                    </ul>
                    <div class="mt-4 p-3 bg-blue-500/20 rounded-lg">
                        <div class="text-sm text-white/70 mb-1">คุณสมบัติพิเศษ</div>
                        <div class="flex gap-2">
                            <span class="px-2 py-1 bg-blue-500/30 text-blue-300 rounded text-xs">Instant Transfer</span>
                            <span class="px-2 py-1 bg-cyan-500/30 text-cyan-300 rounded text-xs">No Gas Fee</span>
                        </div>
                    </div>
                </div>

                {{-- External Wallets --}}
                <div class="bg-gradient-to-br from-orange-500/20 to-red-500/20 backdrop-blur-sm rounded-2xl p-6 border border-orange-500/30">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-link text-2xl text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-white">External Wallets</h3>
                            <p class="text-orange-300 text-sm">กระเป๋าภายนอก (Advanced)</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center gap-3 p-3 bg-orange-500/10 rounded-lg">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                                <i class="fas fa-fox-face text-orange-500"></i>
                            </div>
                            <div class="flex-1">
                                <div class="text-white font-bold">MetaMask</div>
                                <div class="text-white/60 text-xs">Popular & Easy to use</div>
                            </div>
                            <i class="fas fa-check text-green-400"></i>
                        </div>

                        <div class="flex items-center gap-3 p-3 bg-orange-500/10 rounded-lg">
                            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-shield-alt text-white text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <div class="text-white font-bold">Trust Wallet</div>
                                <div class="text-white/60 text-xs">Mobile-first wallet</div>
                            </div>
                            <i class="fas fa-check text-green-400"></i>
                        </div>

                        <div class="flex items-center gap-3 p-3 bg-orange-500/10 rounded-lg">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-blue-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-wallet text-white text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <div class="text-white font-bold">Hardware Wallet</div>
                                <div class="text-white/60 text-xs">Ledger, Trezor (Most Secure)</div>
                            </div>
                            <i class="fas fa-check text-green-400"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Security Tips --}}
            <div class="mt-6 bg-gradient-to-br from-red-500/20 to-pink-500/20 backdrop-blur-sm rounded-2xl p-6 border border-red-500/30">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-shield-halved text-red-400"></i>
                    เคล็ดลับความปลอดภัย
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-key text-yellow-400 mt-1"></i>
                        <div>
                            <div class="text-white font-bold text-sm">เก็บ Private Key ปลอดภัย</div>
                            <div class="text-white/60 text-xs">อย่าแชร์ให้ใครรู้</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-lock text-green-400 mt-1"></i>
                        <div>
                            <div class="text-white font-bold text-sm">ใช้ 2FA ทุกครั้ง</div>
                            <div class="text-white/60 text-xs">ความปลอดภัยสองชั้น</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-user-shield text-blue-400 mt-1"></i>
                        <div>
                            <div class="text-white font-bold text-sm">ระวังการหลอกลวง</div>
                            <div class="text-white/60 text-xs">ตรวจสอบ URL เสมอ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 5: Earn TPIX - วิธีหา TPIX --}}
<div class="slide" data-topic="tpix-token">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="w-full max-w-6xl">
            <div class="text-center mb-8">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    <i class="fas fa-gift text-pink-400 mr-4"></i>
                    หา TPIX ได้อย่างไร?
                </h2>
                <p class="text-xl text-white/70">หลายวิธีในการสะสม Token</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Method 1: Referral --}}
                <div class="bg-gradient-to-br from-orange-500/10 to-red-500/10 backdrop-blur-sm rounded-2xl p-6 border border-orange-500/30 hover:scale-105 transition-transform">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-orange-500 to-red-500 rounded-xl flex items-center justify-center">
                            <i class="fas fa-user-plus text-2xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white mb-2">แนะนำเพื่อน (Referral)</h3>
                            <p class="text-white/70 mb-3 text-sm">ได้รับ TPIX ทุกครั้งที่เพื่อนสมัครและซื้อสินค้า</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-orange-500/30 text-orange-300 rounded-full text-sm font-bold">
                                    +50 TPIX / สมาชิกใหม่
                                </span>
                                <span class="px-3 py-1 bg-red-500/30 text-red-300 rounded-full text-sm">
                                    ไม่จำกัด
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Method 2: Shopping Cashback --}}
                <div class="bg-gradient-to-br from-green-500/10 to-emerald-500/10 backdrop-blur-sm rounded-2xl p-6 border border-green-500/30 hover:scale-105 transition-transform">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
                            <i class="fas fa-shopping-cart text-2xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white mb-2">ช้อปปิ้ง Cashback</h3>
                            <p class="text-white/70 mb-3 text-sm">รับ TPIX Cashback ทุกการซื้อสินค้าในระบบ</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-green-500/30 text-green-300 rounded-full text-sm font-bold">
                                    1-5% Cashback
                                </span>
                                <span class="px-3 py-1 bg-emerald-500/30 text-emerald-300 rounded-full text-sm">
                                    ทันทีหลังซื้อ
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Method 3: Staking Rewards --}}
                <div class="bg-gradient-to-br from-purple-500/10 to-pink-500/10 backdrop-blur-sm rounded-2xl p-6 border border-purple-500/30 hover:scale-105 transition-transform">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                            <i class="fas fa-lock text-2xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white mb-2">Staking Rewards</h3>
                            <p class="text-white/70 mb-3 text-sm">ล็อค TPIX รับดอกเบี้ยทุกวันแบบ Compound</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-purple-500/30 text-purple-300 rounded-full text-sm font-bold">
                                    8-18% APY
                                </span>
                                <span class="px-3 py-1 bg-pink-500/30 text-pink-300 rounded-full text-sm">
                                    จ่ายทุกวัน
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Method 4: Sell Products --}}
                <div class="bg-gradient-to-br from-blue-500/10 to-cyan-500/10 backdrop-blur-sm rounded-2xl p-6 border border-blue-500/30 hover:scale-105 transition-transform">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center">
                            <i class="fas fa-store text-2xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white mb-2">ขายสินค้า</h3>
                            <p class="text-white/70 mb-3 text-sm">ขายสินค้าของคุณและรับ TPIX เป็นยอดขาย</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-blue-500/30 text-blue-300 rounded-full text-sm font-bold">
                                    รับเต็มราคา
                                </span>
                                <span class="px-3 py-1 bg-cyan-500/30 text-cyan-300 rounded-full text-sm">
                                    โอนฟรี
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Method 5: Promotions --}}
                <div class="bg-gradient-to-br from-yellow-500/10 to-amber-500/10 backdrop-blur-sm rounded-2xl p-6 border border-yellow-500/30 hover:scale-105 transition-transform">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-yellow-500 to-amber-500 rounded-xl flex items-center justify-center">
                            <i class="fas fa-bullhorn text-2xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white mb-2">โปรโมชั่น & Event</h3>
                            <p class="text-white/70 mb-3 text-sm">ร่วม Event พิเศษและรับ TPIX ฟรี</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-yellow-500/30 text-yellow-300 rounded-full text-sm font-bold">
                                    Airdrop
                                </span>
                                <span class="px-3 py-1 bg-amber-500/30 text-amber-300 rounded-full text-sm">
                                    Giveaway
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Method 6: Buy on DEX --}}
                <div class="bg-gradient-to-br from-indigo-500/10 to-purple-500/10 backdrop-blur-sm rounded-2xl p-6 border border-indigo-500/30 hover:scale-105 transition-transform">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-xl flex items-center justify-center">
                            <i class="fas fa-exchange-alt text-2xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white mb-2">ซื้อบน DEX</h3>
                            <p class="text-white/70 mb-3 text-sm">ซื้อขาย TPIX ได้ตลอด 24/7 บน DEX</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-indigo-500/30 text-indigo-300 rounded-full text-xs">
                                    PancakeSwap
                                </span>
                                <span class="px-3 py-1 bg-purple-500/30 text-purple-300 rounded-full text-xs">
                                    Uniswap
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CTA --}}
            <div class="mt-6 text-center">
                <div class="inline-flex items-center gap-4 bg-gradient-to-r from-yellow-500/20 to-orange-500/20 backdrop-blur-sm rounded-2xl px-8 py-4 border border-yellow-500/30">
                    <i class="fas fa-rocket text-yellow-400 text-2xl"></i>
                    <div class="text-left">
                        <div class="text-white font-bold text-lg">เริ่มสะสม TPIX วันนี้!</div>
                        <div class="text-white/60 text-sm">สมัครสมาชิกฟรี รับ 100 TPIX ทันที</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 6: Spend TPIX - ใช้ TPIX ที่ไหน --}}
<div class="slide" data-topic="tpix-token">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="w-full max-w-6xl">
            <div class="text-center mb-8">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    <i class="fas fa-store text-green-400 mr-4"></i>
                    ใช้ TPIX ที่ไหนได้บ้าง?
                </h2>
                <p class="text-xl text-white/70">Ecosystem ครบครันสำหรับใช้จ่าย Token</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                {{-- 1. Marketplace --}}
                <div class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 backdrop-blur-sm rounded-2xl p-5 border border-green-500/30 hover:scale-105 transition-transform">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mb-3 mx-auto">
                        <i class="fas fa-shopping-bag text-2xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white text-center mb-2">Marketplace</h3>
                    <p class="text-white/70 text-sm text-center mb-3">ช้อปปิ้งสินค้าหลากหลายหมวดหมู่</p>
                    <div class="text-center">
                        <span class="px-3 py-1 bg-green-500/30 text-green-300 rounded-full text-xs font-bold">
                            ส่วนลด 5-10%
                        </span>
                    </div>
                </div>

                {{-- 2. AI Bot Store --}}
                <div class="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 backdrop-blur-sm rounded-2xl p-5 border border-blue-500/30 hover:scale-105 transition-transform">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center mb-3 mx-auto">
                        <i class="fas fa-robot text-2xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white text-center mb-2">AI Bot Store</h3>
                    <p class="text-white/70 text-sm text-center mb-3">ซื้อ AI Bot และฟีเจอร์พรีเมียม</p>
                    <div class="text-center">
                        <span class="px-3 py-1 bg-blue-500/30 text-blue-300 rounded-full text-xs font-bold">
                            Exclusive
                        </span>
                    </div>
                </div>

                {{-- 3. Hotel Booking --}}
                <div class="bg-gradient-to-br from-purple-500/20 to-pink-500/20 backdrop-blur-sm rounded-2xl p-5 border border-purple-500/30 hover:scale-105 transition-transform">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mb-3 mx-auto">
                        <i class="fas fa-hotel text-2xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white text-center mb-2">Hotel Booking</h3>
                    <p class="text-white/70 text-sm text-center mb-3">จองโรงแรมราคาพิเศษ</p>
                    <div class="text-center">
                        <span class="px-3 py-1 bg-purple-500/30 text-purple-300 rounded-full text-xs font-bold">
                            ส่วนลด 15%
                        </span>
                    </div>
                </div>

                {{-- 4. Food & Restaurant --}}
                <div class="bg-gradient-to-br from-orange-500/20 to-red-500/20 backdrop-blur-sm rounded-2xl p-5 border border-orange-500/30 hover:scale-105 transition-transform">
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-red-500 rounded-xl flex items-center justify-center mb-3 mx-auto">
                        <i class="fas fa-utensils text-2xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white text-center mb-2">Food & Restaurant</h3>
                    <p class="text-white/70 text-sm text-center mb-3">สั่งอาหารและชำระด้วย TPIX</p>
                    <div class="text-center">
                        <span class="px-3 py-1 bg-orange-500/30 text-orange-300 rounded-full text-xs font-bold">
                            ส่วนลด 8%
                        </span>
                    </div>
                </div>

                {{-- 5. Software & License --}}
                <div class="bg-gradient-to-br from-indigo-500/20 to-blue-500/20 backdrop-blur-sm rounded-2xl p-5 border border-indigo-500/30 hover:scale-105 transition-transform">
                    <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-blue-500 rounded-xl flex items-center justify-center mb-3 mx-auto">
                        <i class="fas fa-laptop-code text-2xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white text-center mb-2">Software & License</h3>
                    <p class="text-white/70 text-sm text-center mb-3">ซื้อ Software และ License</p>
                    <div class="text-center">
                        <span class="px-3 py-1 bg-indigo-500/30 text-indigo-300 rounded-full text-xs font-bold">
                            ถูกกว่า 20%
                        </span>
                    </div>
                </div>

                {{-- 6. NFT Marketplace --}}
                <div class="bg-gradient-to-br from-pink-500/20 to-rose-500/20 backdrop-blur-sm rounded-2xl p-5 border border-pink-500/30 hover:scale-105 transition-transform">
                    <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-rose-500 rounded-xl flex items-center justify-center mb-3 mx-auto">
                        <i class="fas fa-gem text-2xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white text-center mb-2">NFT Marketplace</h3>
                    <p class="text-white/70 text-sm text-center mb-3">ซื้อขาย NFT คอลเลคชั่น</p>
                    <div class="text-center">
                        <span class="px-3 py-1 bg-pink-500/30 text-pink-300 rounded-full text-xs font-bold">
                            Trending
                        </span>
                    </div>
                </div>

                {{-- 7. VIP Membership --}}
                <div class="bg-gradient-to-br from-yellow-500/20 to-amber-500/20 backdrop-blur-sm rounded-2xl p-5 border border-yellow-500/30 hover:scale-105 transition-transform">
                    <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-amber-500 rounded-xl flex items-center justify-center mb-3 mx-auto">
                        <i class="fas fa-crown text-2xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white text-center mb-2">VIP Membership</h3>
                    <p class="text-white/70 text-sm text-center mb-3">อัพเกรดเป็น VIP Member</p>
                    <div class="text-center">
                        <span class="px-3 py-1 bg-yellow-500/30 text-yellow-300 rounded-full text-xs font-bold">
                            Premium
                        </span>
                    </div>
                </div>

                {{-- 8. Consulting Services --}}
                <div class="bg-gradient-to-br from-teal-500/20 to-cyan-500/20 backdrop-blur-sm rounded-2xl p-5 border border-teal-500/30 hover:scale-105 transition-transform">
                    <div class="w-14 h-14 bg-gradient-to-br from-teal-500 to-cyan-500 rounded-xl flex items-center justify-center mb-3 mx-auto">
                        <i class="fas fa-chalkboard-teacher text-2xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white text-center mb-2">Consulting</h3>
                    <p class="text-white/70 text-sm text-center mb-3">ที่ปรึกษาธุรกิจและการตลาด</p>
                    <div class="text-center">
                        <span class="px-3 py-1 bg-teal-500/30 text-teal-300 rounded-full text-xs font-bold">
                            Expert
                        </span>
                    </div>
                </div>

                {{-- 9. Event Tickets --}}
                <div class="bg-gradient-to-br from-red-500/20 to-pink-500/20 backdrop-blur-sm rounded-2xl p-5 border border-red-500/30 hover:scale-105 transition-transform">
                    <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-pink-500 rounded-xl flex items-center justify-center mb-3 mx-auto">
                        <i class="fas fa-ticket-alt text-2xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white text-center mb-2">Event Tickets</h3>
                    <p class="text-white/70 text-sm text-center mb-3">บัตรเข้าอีเวนท์และสัมมนา</p>
                    <div class="text-center">
                        <span class="px-3 py-1 bg-red-500/30 text-red-300 rounded-full text-xs font-bold">
                            Early Bird
                        </span>
                    </div>
                </div>
            </div>

            {{-- Summary --}}
            <div class="mt-6 bg-gradient-to-r from-yellow-500/20 to-orange-500/20 backdrop-blur-sm rounded-2xl px-6 py-4 border border-yellow-500/30 text-center">
                <div class="text-white font-bold text-lg mb-1">🎉 ใช้ TPIX ได้มากกว่า 50+ บริการในระบบ</div>
                <div class="text-white/70 text-sm">เพิ่มเติมทุกเดือน • ยิ่งใช้ยิ่งคุ้ม</div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 7: TPIX Roadmap - แผนงานอนาคต --}}
<div class="slide" data-topic="tpix-token">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="w-full max-w-6xl">
            <div class="text-center mb-8">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    <i class="fas fa-map-marked-alt text-blue-400 mr-4"></i>
                    TPIX Roadmap
                </h2>
                <p class="text-xl text-white/70">แผนงานพัฒนาในอนาคต</p>
            </div>

            <div class="space-y-4">
                {{-- Phase 1 - COMPLETED --}}
                <div class="bg-gradient-to-r from-green-500/20 to-emerald-500/20 backdrop-blur-sm rounded-2xl p-5 border-l-4 border-green-500">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-xl text-white"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl font-bold text-white">Phase 1: Foundation</h3>
                                <span class="px-3 py-1 bg-green-500/30 text-green-300 rounded-full text-xs font-bold">COMPLETED ✓</span>
                            </div>
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-white/80">
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-400"></i> สร้าง Smart Contract</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-400"></i> TP Wallet System</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-400"></i> Staking Platform</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-400"></i> Basic Payment Integration</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Phase 2 - IN PROGRESS --}}
                <div class="bg-gradient-to-r from-blue-500/20 to-cyan-500/20 backdrop-blur-sm rounded-2xl p-5 border-l-4 border-blue-500">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-full flex items-center justify-center animate-pulse">
                                <i class="fas fa-sync text-xl text-white"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl font-bold text-white">Phase 2: Ecosystem Expansion</h3>
                                <span class="px-3 py-1 bg-blue-500/30 text-blue-300 rounded-full text-xs font-bold">IN PROGRESS 🚀</span>
                            </div>
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-white/80">
                                <li class="flex items-center gap-2"><i class="fas fa-spinner fa-spin text-blue-400"></i> DEX Listing (PancakeSwap)</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-400"></i> NFT Marketplace</li>
                                <li class="flex items-center gap-2"><i class="fas fa-spinner fa-spin text-blue-400"></i> Mobile Wallet App</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-400"></i> Governance System</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Phase 3 - Q1 2026 --}}
                <div class="bg-gradient-to-r from-purple-500/20 to-pink-500/20 backdrop-blur-sm rounded-2xl p-5 border-l-4 border-purple-500">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center">
                                <span class="text-lg font-black text-white">Q1</span>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl font-bold text-white">Phase 3: DeFi & Cross-Chain</h3>
                                <span class="px-3 py-1 bg-purple-500/30 text-purple-300 rounded-full text-xs font-bold">Q1 2026</span>
                            </div>
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-white/80">
                                <li class="flex items-center gap-2"><i class="fas fa-circle text-purple-400 text-xs"></i> Yield Farming Pools</li>
                                <li class="flex items-center gap-2"><i class="fas fa-circle text-purple-400 text-xs"></i> Cross-Chain Bridge (BSC-ETH)</li>
                                <li class="flex items-center gap-2"><i class="fas fa-circle text-purple-400 text-xs"></i> Liquidity Mining Program</li>
                                <li class="flex items-center gap-2"><i class="fas fa-circle text-purple-400 text-xs"></i> CEX Listing (Top 20)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Phase 4 - Q3 2026 --}}
                <div class="bg-gradient-to-r from-orange-500/20 to-red-500/20 backdrop-blur-sm rounded-2xl p-5 border-l-4 border-orange-500">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-500 rounded-full flex items-center justify-center">
                                <span class="text-lg font-black text-white">Q3</span>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl font-bold text-white">Phase 4: Global Expansion</h3>
                                <span class="px-3 py-1 bg-orange-500/30 text-orange-300 rounded-full text-xs font-bold">Q3 2026</span>
                            </div>
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-white/80">
                                <li class="flex items-center gap-2"><i class="fas fa-circle text-orange-400 text-xs"></i> Multi-Currency Support</li>
                                <li class="flex items-center gap-2"><i class="fas fa-circle text-orange-400 text-xs"></i> Partnership 100+ Merchants</li>
                                <li class="flex items-center gap-2"><i class="fas fa-circle text-orange-400 text-xs"></i> TPIX Debit Card</li>
                                <li class="flex items-center gap-2"><i class="fas fa-circle text-orange-400 text-xs"></i> Global Payment Gateway</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Vision --}}
            <div class="mt-6 bg-gradient-to-r from-yellow-500/20 to-amber-500/20 backdrop-blur-sm rounded-2xl px-6 py-4 border border-yellow-500/30 text-center">
                <div class="text-yellow-400 font-black text-xl mb-1">🎯 Vision 2027</div>
                <div class="text-white/80 text-sm">TPIX เป็น Top 100 Utility Token พร้อมใช้งานจริงในชีวิตประจำวัน</div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 8: How to Get Started - เริ่มต้นใช้งาน --}}
<div class="slide" data-topic="tpix-token">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="w-full max-w-5xl">
            <div class="text-center mb-8">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    <i class="fas fa-play-circle text-green-400 mr-4"></i>
                    เริ่มต้นใช้งาน TPIX
                </h2>
                <p class="text-xl text-white/70">3 ขั้นตอนง่ายๆ สู่โลก TPIX</p>
            </div>

            {{-- 3 Steps --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                {{-- Step 1: Buy/Earn --}}
                <div class="relative">
                    <div class="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 backdrop-blur-sm rounded-2xl p-6 border border-blue-500/30 hover:scale-105 transition-transform">
                        <div class="absolute -top-4 -left-4 w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-full flex items-center justify-center shadow-lg">
                            <span class="text-2xl font-black text-white">1</span>
                        </div>
                        <div class="text-center pt-4">
                            <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center mb-4 mx-auto">
                                <i class="fas fa-wallet text-3xl text-white"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-3">หา TPIX</h3>
                            <ul class="text-sm text-white/80 space-y-2 text-left">
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-blue-400"></i>
                                    <span>สมัครสมาชิก รับฟรี 100 TPIX</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-blue-400"></i>
                                    <span>แนะนำเพื่อน +50 TPIX/คน</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-blue-400"></i>
                                    <span>ซื้อบน DEX (PancakeSwap)</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Step 2: Store --}}
                <div class="relative">
                    <div class="bg-gradient-to-br from-purple-500/20 to-pink-500/20 backdrop-blur-sm rounded-2xl p-6 border border-purple-500/30 hover:scale-105 transition-transform">
                        <div class="absolute -top-4 -left-4 w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center shadow-lg">
                            <span class="text-2xl font-black text-white">2</span>
                        </div>
                        <div class="text-center pt-4">
                            <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center mb-4 mx-auto">
                                <i class="fas fa-shield-alt text-3xl text-white"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-3">เก็บปลอดภัย</h3>
                            <ul class="text-sm text-white/80 space-y-2 text-left">
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-purple-400"></i>
                                    <span>ใช้ TP Wallet (แนะนำ)</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-purple-400"></i>
                                    <span>MetaMask / Trust Wallet</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-purple-400"></i>
                                    <span>เปิด 2FA Security</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Step 3: Use --}}
                <div class="relative">
                    <div class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 backdrop-blur-sm rounded-2xl p-6 border border-green-500/30 hover:scale-105 transition-transform">
                        <div class="absolute -top-4 -left-4 w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-full flex items-center justify-center shadow-lg">
                            <span class="text-2xl font-black text-white">3</span>
                        </div>
                        <div class="text-center pt-4">
                            <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center mb-4 mx-auto">
                                <i class="fas fa-shopping-cart text-3xl text-white"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-3">ใช้จ่าย</h3>
                            <ul class="text-sm text-white/80 space-y-2 text-left">
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-green-400"></i>
                                    <span>ช้อปปิ้ง ส่วนลด 5-10%</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-green-400"></i>
                                    <span>Stake รับดอกเบี้ย 18%</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-green-400"></i>
                                    <span>ใช้ 50+ บริการ</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="bg-gradient-to-r from-yellow-500/10 to-orange-500/10 backdrop-blur-sm rounded-2xl p-6 border border-yellow-500/30">
                <h3 class="text-xl font-bold text-white text-center mb-4">
                    <i class="fas fa-link text-yellow-400 mr-2"></i>
                    Quick Links
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <button class="bg-gradient-to-br from-blue-500/30 to-cyan-500/30 hover:from-blue-500/40 hover:to-cyan-500/40 backdrop-blur-sm rounded-xl p-3 border border-blue-500/30 transition-all">
                        <i class="fas fa-user-plus text-blue-400 text-xl mb-1"></i>
                        <div class="text-white text-sm font-bold">สมัครสมาชิก</div>
                    </button>
                    <button class="bg-gradient-to-br from-green-500/30 to-emerald-500/30 hover:from-green-500/40 hover:to-emerald-500/40 backdrop-blur-sm rounded-xl p-3 border border-green-500/30 transition-all">
                        <i class="fas fa-shopping-bag text-green-400 text-xl mb-1"></i>
                        <div class="text-white text-sm font-bold">Marketplace</div>
                    </button>
                    <button class="bg-gradient-to-br from-purple-500/30 to-pink-500/30 hover:from-purple-500/40 hover:to-pink-500/40 backdrop-blur-sm rounded-xl p-3 border border-purple-500/30 transition-all">
                        <i class="fas fa-lock text-purple-400 text-xl mb-1"></i>
                        <div class="text-white text-sm font-bold">Staking</div>
                    </button>
                    <button class="bg-gradient-to-br from-orange-500/30 to-red-500/30 hover:from-orange-500/40 hover:to-red-500/40 backdrop-blur-sm rounded-xl p-3 border border-orange-500/30 transition-all">
                        <i class="fas fa-book text-orange-400 text-xl mb-1"></i>
                        <div class="text-white text-sm font-bold">คู่มือ</div>
                    </button>
                </div>
            </div>

            {{-- Final CTA --}}
            <div class="mt-6 text-center">
                <div class="inline-block bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500 rounded-2xl p-1">
                    <div class="bg-gradient-to-br from-gray-900/90 to-gray-800/90 backdrop-blur-sm rounded-xl px-8 py-5">
                        <div class="text-3xl font-black bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500 bg-clip-text text-transparent mb-2">
                            🚀 พร้อมเริ่มต้นแล้วหรือยัง?
                        </div>
                        <div class="text-white/80 mb-4">สมัครวันนี้ รับ 100 TPIX ฟรี + โบนัสพิเศษ!</div>
                        <button class="bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-400 hover:to-orange-400 text-white font-bold px-8 py-3 rounded-xl transition-all transform hover:scale-105 shadow-lg">
                            <i class="fas fa-rocket mr-2"></i>
                            เริ่มต้นทันที
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
