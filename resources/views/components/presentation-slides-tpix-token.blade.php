{{--
    TPIX Token Slides - ระบบ Cryptocurrency
    เนื้อหา: Tokenomics, Staking, DeFi, Use Cases
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

{{-- Slide 2: Tokenomics --}}
<div class="slide" data-topic="tpix-token">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="w-full max-w-6xl">
            <div class="text-center mb-10">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    <i class="fas fa-chart-pie text-yellow-400 mr-4"></i>
                    Tokenomics
                </h2>
                <p class="text-xl text-white/70">การกระจาย Token อย่างโปร่งใส</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                {{-- Pie Chart Visual --}}
                <div class="relative">
                    <div class="w-64 h-64 mx-auto relative">
                        {{-- Circular segments --}}
                        <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90">
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#3B82F6" stroke-width="20" stroke-dasharray="75.4 175.93" stroke-dashoffset="0"/>
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#8B5CF6" stroke-width="20" stroke-dasharray="50.27 175.93" stroke-dashoffset="-75.4"/>
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#EC4899" stroke-width="20" stroke-dasharray="37.7 175.93" stroke-dashoffset="-125.66"/>
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#F59E0B" stroke-width="20" stroke-dasharray="25.13 175.93" stroke-dashoffset="-163.36"/>
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#10B981" stroke-width="20" stroke-dasharray="12.57 175.93" stroke-dashoffset="-188.5"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-2xl font-black text-white">100M</div>
                                <div class="text-white/60 text-sm">TPIX</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Legend --}}
                <div class="space-y-4">
                    <div class="flex items-center gap-4 bg-blue-500/20 backdrop-blur-sm rounded-xl p-4 border border-blue-500/30">
                        <div class="w-4 h-4 bg-blue-500 rounded-full"></div>
                        <div class="flex-1">
                            <div class="text-white font-bold">Community & Rewards</div>
                            <div class="text-white/60 text-sm">30% - สำหรับรางวัลผู้ใช้งาน</div>
                        </div>
                        <div class="text-2xl font-black text-blue-400">30M</div>
                    </div>

                    <div class="flex items-center gap-4 bg-purple-500/20 backdrop-blur-sm rounded-xl p-4 border border-purple-500/30">
                        <div class="w-4 h-4 bg-purple-500 rounded-full"></div>
                        <div class="flex-1">
                            <div class="text-white font-bold">Development & Team</div>
                            <div class="text-white/60 text-sm">20% - พัฒนาระบบ (Vested 3 ปี)</div>
                        </div>
                        <div class="text-2xl font-black text-purple-400">20M</div>
                    </div>

                    <div class="flex items-center gap-4 bg-pink-500/20 backdrop-blur-sm rounded-xl p-4 border border-pink-500/30">
                        <div class="w-4 h-4 bg-pink-500 rounded-full"></div>
                        <div class="flex-1">
                            <div class="text-white font-bold">Marketing & Partners</div>
                            <div class="text-white/60 text-sm">15% - การตลาดและพันธมิตร</div>
                        </div>
                        <div class="text-2xl font-black text-pink-400">15M</div>
                    </div>

                    <div class="flex items-center gap-4 bg-amber-500/20 backdrop-blur-sm rounded-xl p-4 border border-amber-500/30">
                        <div class="w-4 h-4 bg-amber-500 rounded-full"></div>
                        <div class="flex-1">
                            <div class="text-white font-bold">Liquidity Pool</div>
                            <div class="text-white/60 text-sm">10% - สภาพคล่องใน DEX</div>
                        </div>
                        <div class="text-2xl font-black text-amber-400">10M</div>
                    </div>

                    <div class="flex items-center gap-4 bg-emerald-500/20 backdrop-blur-sm rounded-xl p-4 border border-emerald-500/30">
                        <div class="w-4 h-4 bg-emerald-500 rounded-full"></div>
                        <div class="flex-1">
                            <div class="text-white font-bold">Reserve & Treasury</div>
                            <div class="text-white/60 text-sm">25% - สำรองและคลัง</div>
                        </div>
                        <div class="text-2xl font-black text-emerald-400">25M</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 3: Use Cases --}}
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

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Use Case 1: Payment --}}
                <div class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 backdrop-blur-sm rounded-2xl p-6 border border-green-500/30 hover:scale-105 transition-transform">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-shopping-bag text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">ชำระเงินซื้อสินค้า</h3>
                    <p class="text-white/70 text-sm">ใช้ TPIX ซื้อสินค้าใน Marketplace ได้ส่วนลดพิเศษ 5-10%</p>
                </div>

                {{-- Use Case 2: Commission --}}
                <div class="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 backdrop-blur-sm rounded-2xl p-6 border border-blue-500/30 hover:scale-105 transition-transform">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-percent text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">รับคอมมิชชั่น</h3>
                    <p class="text-white/70 text-sm">เลือกรับคอมมิชชั่นเป็น TPIX โบนัสเพิ่ม 20%</p>
                </div>

                {{-- Use Case 3: Staking --}}
                <div class="bg-gradient-to-br from-purple-500/20 to-pink-500/20 backdrop-blur-sm rounded-2xl p-6 border border-purple-500/30 hover:scale-105 transition-transform">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-lock text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Staking ดอกเบี้ย</h3>
                    <p class="text-white/70 text-sm">ล็อค TPIX รับดอกเบี้ยสูงถึง 18% APY</p>
                </div>

                {{-- Use Case 4: Governance --}}
                <div class="bg-gradient-to-br from-yellow-500/20 to-orange-500/20 backdrop-blur-sm rounded-2xl p-6 border border-yellow-500/30 hover:scale-105 transition-transform">
                    <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-vote-yea text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Governance Vote</h3>
                    <p class="text-white/70 text-sm">โหวตตัดสินใจทิศทาง Platform ร่วมกัน</p>
                </div>

                {{-- Use Case 5: AI Bot --}}
                <div class="bg-gradient-to-br from-cyan-500/20 to-blue-500/20 backdrop-blur-sm rounded-2xl p-6 border border-cyan-500/30 hover:scale-105 transition-transform">
                    <div class="w-14 h-14 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-robot text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">ซื้อ AI Bot</h3>
                    <p class="text-white/70 text-sm">ใช้ TPIX ซื้อ AI Bot และ Premium Features</p>
                </div>

                {{-- Use Case 6: VIP Access --}}
                <div class="bg-gradient-to-br from-red-500/20 to-rose-500/20 backdrop-blur-sm rounded-2xl p-6 border border-red-500/30 hover:scale-105 transition-transform">
                    <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-rose-500 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-crown text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">VIP Access</h3>
                    <p class="text-white/70 text-sm">สิทธิพิเศษ Early Access และ Exclusive Events</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 4: How to Get TPIX --}}
<div class="slide" data-topic="tpix-token">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="w-full max-w-5xl">
            <div class="text-center mb-10">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    <i class="fas fa-gift text-pink-400 mr-4"></i>
                    รับ TPIX ได้อย่างไร?
                </h2>
                <p class="text-xl text-white/70">หลายวิธีในการสะสม Token</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Method 1: Affiliate --}}
                <div class="bg-gradient-to-br from-orange-500/10 to-red-500/10 backdrop-blur-sm rounded-2xl p-6 border border-orange-500/30">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-orange-500 to-red-500 rounded-xl flex items-center justify-center">
                            <span class="text-xl font-black text-white">1</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white mb-2">แนะนำเพื่อน (Referral)</h3>
                            <p class="text-white/70 mb-3">ได้รับ TPIX ทุกครั้งที่เพื่อนสมัครและซื้อสินค้า</p>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 bg-orange-500/30 text-orange-300 rounded-full text-sm">
                                    +50 TPIX / สมาชิกใหม่
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Method 2: Purchase --}}
                <div class="bg-gradient-to-br from-green-500/10 to-emerald-500/10 backdrop-blur-sm rounded-2xl p-6 border border-green-500/30">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
                            <span class="text-xl font-black text-white">2</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white mb-2">ซื้อสินค้า (Cashback)</h3>
                            <p class="text-white/70 mb-3">รับ TPIX Cashback ทุกการซื้อสินค้าในระบบ</p>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 bg-green-500/30 text-green-300 rounded-full text-sm">
                                    1-5% Cashback
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Method 3: Staking --}}
                <div class="bg-gradient-to-br from-purple-500/10 to-pink-500/10 backdrop-blur-sm rounded-2xl p-6 border border-purple-500/30">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                            <span class="text-xl font-black text-white">3</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white mb-2">Staking Rewards</h3>
                            <p class="text-white/70 mb-3">ล็อค TPIX รับดอกเบี้ยทุกวันแบบ Compound</p>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 bg-purple-500/30 text-purple-300 rounded-full text-sm">
                                    สูงสุด 18% APY
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Method 4: Buy on DEX --}}
                <div class="bg-gradient-to-br from-blue-500/10 to-cyan-500/10 backdrop-blur-sm rounded-2xl p-6 border border-blue-500/30">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center">
                            <span class="text-xl font-black text-white">4</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white mb-2">ซื้อบน DEX</h3>
                            <p class="text-white/70 mb-3">ซื้อขาย TPIX ได้ตลอด 24/7 บน Decentralized Exchange</p>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 bg-blue-500/30 text-blue-300 rounded-full text-sm">
                                    Pancakeswap • Uniswap
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CTA --}}
            <div class="mt-10 text-center">
                <div class="inline-flex items-center gap-4 bg-gradient-to-r from-yellow-500/20 to-orange-500/20 backdrop-blur-sm rounded-2xl px-8 py-4 border border-yellow-500/30">
                    <i class="fas fa-rocket text-yellow-400 text-2xl"></i>
                    <div class="text-left">
                        <div class="text-white font-bold">เริ่มสะสม TPIX วันนี้!</div>
                        <div class="text-white/60 text-sm">สมัครสมาชิกฟรี รับ 100 TPIX ทันที</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
