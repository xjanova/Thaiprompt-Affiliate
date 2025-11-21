{{--
    Use Cases & Applications Section - แสดงตัวอย่างการใช้งาน TPIX ในชีวิตจริง

    ประกอบด้วย:
    - Real-world Use Cases
    - User Stories
    - Industry Applications
    - Success Metrics
--}}

<section id="usecases" class="section-padding bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    <div class="container mx-auto px-4">

        {{-- Header --}}
        <div class="text-center mb-16">
            <div class="inline-block px-6 py-2 gradient-primary text-white rounded-full text-sm font-bold mb-4">
                💼 Use Cases & Applications
            </div>
            <h2 class="text-5xl font-black text-gray-900 dark:text-white mb-4">
                การใช้งานจริงของ TPIX
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                ตัวอย่างการใช้งาน TPIX ในชีวิตจริงและอุตสาหกรรมต่างๆ
            </p>
        </div>

        {{-- Primary Use Cases --}}
        <div class="grid md:grid-cols-2 gap-8 mb-16">

            {{-- Use Case 1: E-commerce --}}
            <div class="glass rounded-3xl p-8 feature-card">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-3xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">E-commerce</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ซื้อขายสินค้าออนไลน์</p>
                    </div>
                </div>

                <div class="space-y-4">
                    {{-- User Story --}}
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                        <h5 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                            <i class="fas fa-user text-blue-500"></i> User Story
                        </h5>
                        <p class="text-sm text-gray-700 dark:text-gray-300 italic">
                            "สมชายเป็นเจ้าของร้านออนไลน์ เขาใช้ TPIX เป็น payment method ลูกค้าจ่ายด้วย TPIX
                            ได้ส่วนลด 5% และสมชายประหยัด fee จาก payment gateway ได้ 2.5%"
                        </p>
                    </div>

                    {{-- Benefits --}}
                    <div>
                        <h5 class="font-bold text-gray-900 dark:text-white mb-3">Benefits:</h5>
                        <div class="space-y-2">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">ค่าธรรมเนียมต่ำ</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400"><$0.01 per transaction vs. credit card 2.5-3%</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">รับเงินทันที</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">No 30-day waiting period</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">Global Payment</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">ขายได้ทั่วโลกไม่มีข้อจำกัด</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Metrics --}}
                    <div class="grid grid-cols-3 gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-center">
                            <div class="text-2xl font-black text-blue-600 dark:text-blue-400">500+</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Merchants</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-black text-purple-600 dark:text-purple-400">$2.5M</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Monthly Volume</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-black text-green-600 dark:text-green-400">98.5%</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Success Rate</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Use Case 2: MLM & Referral --}}
            <div class="glass rounded-3xl p-8 feature-card">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-users text-3xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">MLM & Referral</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">สร้างรายได้แบบ passive</p>
                    </div>
                </div>

                <div class="space-y-4">
                    {{-- User Story --}}
                    <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                        <h5 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                            <i class="fas fa-user text-purple-500"></i> User Story
                        </h5>
                        <p class="text-sm text-gray-700 dark:text-gray-300 italic">
                            "สมหญิงแนะนำเพื่อน 10 คน เธอได้ commission 5% จากยอดขายทุกคน และได้ 3% จากเพื่อนของเพื่อน
                            รายได้ passive income เฉลี่ยเดือนละ 15,000 บาท"
                        </p>
                    </div>

                    {{-- Benefits --}}
                    <div>
                        <h5 class="font-bold text-gray-900 dark:text-white mb-3">Benefits:</h5>
                        <div class="space-y-2">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">Passive Income</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">รายได้ต่อเนื่องจากเครือข่าย 5 ชั้น</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">Real-time Commission</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">รับ commission ทันทีที่มียอดขาย</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">Rank System</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Bonus เพิ่ม 10-50% ตาม rank</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Metrics --}}
                    <div class="grid grid-cols-3 gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-center">
                            <div class="text-2xl font-black text-purple-600 dark:text-purple-400">15K+</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Active Members</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-black text-pink-600 dark:text-pink-400">$1.2M</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Monthly Payout</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-black text-green-600 dark:text-green-400">11.5%</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Total Commission</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Use Case 3: Staking & Investment --}}
            <div class="glass rounded-3xl p-8 feature-card">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-lock text-3xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Staking & Investment</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ลงทุนรับดอกเบี้ย</p>
                    </div>
                </div>

                <div class="space-y-4">
                    {{-- User Story --}}
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                        <h5 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                            <i class="fas fa-user text-green-500"></i> User Story
                        </h5>
                        <p class="text-sm text-gray-700 dark:text-gray-300 italic">
                            "ดาวstake TPIX 100,000 tokens เป็นเวลา 1 ปี ได้ APY 120%
                            หลัง 1 ปีเธอมี 220,000 tokens (+120K profit) มูลค่ารวม 220,000 บาท"
                        </p>
                    </div>

                    {{-- Benefits --}}
                    <div>
                        <h5 class="font-bold text-gray-900 dark:text-white mb-3">Benefits:</h5>
                        <div class="space-y-2">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">High APY</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">30-120% APY ตามระยะเวลา lock</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">Auto-compound</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">ดอกเบี้ยทบต้นอัตโนมัติ เพิ่มผลตอบแทน</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">Flexible Lock</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">เลือก lock 30-365 วันได้ตามต้องการ</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Metrics --}}
                    <div class="grid grid-cols-3 gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-center">
                            <div class="text-2xl font-black text-green-600 dark:text-green-400">$8.5M</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Total Staked</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">120%</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Max APY</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-black text-teal-600 dark:text-teal-400">3.2K</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Active Stakers</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Use Case 4: AI Services --}}
            <div class="glass rounded-3xl p-8 feature-card">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-robot text-3xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">AI Services</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">AI Bots & Automation</p>
                    </div>
                </div>

                <div class="space-y-4">
                    {{-- User Story --}}
                    <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl">
                        <h5 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                            <i class="fas fa-user text-orange-500"></i> User Story
                        </h5>
                        <p class="text-sm text-gray-700 dark:text-gray-300 italic">
                            "บริษัทของแดงใช้ LINE AI Bot สำหรับ customer service จ่ายด้วย TPIX เดือนละ 5,000 บาท
                            ประหยัดค่าพนักงานได้ 80% และลูกค้าได้รับบริการ 24/7"
                        </p>
                    </div>

                    {{-- Benefits --}}
                    <div>
                        <h5 class="font-bold text-gray-900 dark:text-white mb-3">Benefits:</h5>
                        <div class="space-y-2">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">ประหยัดต้นทุน</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">AI Bot ถูกกว่าพนักงาน 80-90%</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">24/7 Service</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">ให้บริการตลอด 24 ชั่วโมง ไม่มีวันหยุด</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">Scalable</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">รองรับลูกค้าได้ไม่จำกัด พร้อมกัน</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Metrics --}}
                    <div class="grid grid-cols-3 gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-center">
                            <div class="text-2xl font-black text-orange-600 dark:text-orange-400">2.5K</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Active Bots</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-black text-red-600 dark:text-red-400">150K</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Daily Messages</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-black text-green-600 dark:text-green-400">95%</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Satisfaction</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Industry Applications --}}
        <div class="mb-16">
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                🏭 Industry Applications
            </h3>
            <div class="grid md:grid-cols-3 lg:grid-cols-4 gap-4">

                <div class="glass rounded-2xl p-6 text-center feature-card">
                    <div class="text-4xl mb-3">🏨</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Hotel & Tourism</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">จองโรงแรม จ่ายด้วย TPIX</p>
                </div>

                <div class="glass rounded-2xl p-6 text-center feature-card">
                    <div class="text-4xl mb-3">🍔</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Food & Beverage</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ร้านอาหาร cafe รับ TPIX</p>
                </div>

                <div class="glass rounded-2xl p-6 text-center feature-card">
                    <div class="text-4xl mb-3">🎓</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Education</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">คอร์สเรียน ค่าเทอม</p>
                </div>

                <div class="glass rounded-2xl p-6 text-center feature-card">
                    <div class="text-4xl mb-3">🏥</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Healthcare</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ค่ารักษา ประกันสุขภาพ</p>
                </div>

                <div class="glass rounded-2xl p-6 text-center feature-card">
                    <div class="text-4xl mb-3">🏠</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Real Estate</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ซื้อ-ขายที่ดิน บ้าน</p>
                </div>

                <div class="glass rounded-2xl p-6 text-center feature-card">
                    <div class="text-4xl mb-3">🚗</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Automotive</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ซื้อรถ ค่าบำรุงรักษา</p>
                </div>

                <div class="glass rounded-2xl p-6 text-center feature-card">
                    <div class="text-4xl mb-3">💄</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Beauty & Spa</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">บริการสปา เสริมความงาม</p>
                </div>

                <div class="glass rounded-2xl p-6 text-center feature-card">
                    <div class="text-4xl mb-3">🎮</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Gaming</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">In-game items, NFTs</p>
                </div>

            </div>
        </div>

        {{-- Success Metrics --}}
        <div class="glass rounded-3xl p-8 bg-gradient-to-r from-green-500/10 to-blue-500/10 dark:from-green-500/20 dark:to-blue-500/20">
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                📊 Success Metrics (Target 2025)
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-5xl font-black gradient-text mb-2">100K+</div>
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Active Users</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Daily active wallet addresses</div>
                </div>
                <div class="text-center">
                    <div class="text-5xl font-black gradient-text mb-2">$50M</div>
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Transaction Volume</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Monthly transaction value</div>
                </div>
                <div class="text-center">
                    <div class="text-5xl font-black gradient-text mb-2">10K</div>
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Merchants</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Accepting TPIX payment</div>
                </div>
                <div class="text-center">
                    <div class="text-5xl font-black gradient-text mb-2">95%</div>
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">User Satisfaction</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Overall rating score</div>
                </div>
            </div>
        </div>

    </div>
</section>
