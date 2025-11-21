{{-- Problem & Solution Section --}}
<section id="problem-solution" class="section-padding bg-white dark:bg-gray-900">
    <div class="container mx-auto px-4">

        {{-- Problem Statement --}}
        <div class="mb-20">
            <div class="text-center mb-12">
                <div class="inline-block px-6 py-2 gradient-red text-white rounded-full text-sm font-bold mb-4 shadow-lg">
                    ⚠️ ปัญหา
                </div>
                <h2 class="text-5xl md:text-6xl font-black text-gray-900 dark:text-white mb-4">
                    ปัญหาที่เราพบในปัจจุบัน
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                    Blockchain ปัจจุบันมีปัญหาหลายอย่างที่ทำให้ไม่สามารถใช้งานได้จริงในชีวิตประจำวัน
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-8 max-w-6xl mx-auto">

                {{-- Problem 1: High Fees --}}
                <div class="glass rounded-3xl p-8 border-l-4 border-red-500 feature-card">
                    <div class="flex items-start space-x-4">
                        <div class="w-16 h-16 gradient-red rounded-2xl flex items-center justify-center flex-shrink-0 shadow-xl">
                            <i class="fas fa-exclamation-triangle text-3xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                💸 ค่าธรรมเนียมสูงเกินไป
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">
                                Ethereum Gas Fee สูงถึง <strong>$5-50 ต่อธุรกรรม</strong>
                                ทำให้ไม่คุ้มสำหรับธุรกรรมขนาดเล็ก เช่น ซื้อกาแฟ ส่งเงินให้เพื่อน
                            </p>
                            <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4">
                                <div class="text-red-600 dark:text-red-400 font-mono text-sm space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span>Ethereum:</span>
                                        <span class="font-bold text-lg">$5-50/tx</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span>BSC:</span>
                                        <span class="font-bold text-lg">$0.1-0.5/tx</span>
                                    </div>
                                    <div class="border-t border-red-300 dark:border-red-700 pt-2 flex justify-between items-center text-green-600 dark:text-green-400">
                                        <span class="font-bold">TPIX:</span>
                                        <span class="font-bold text-xl">&lt;$0.01/tx ✅</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400 italic">
                                ⚡ TPIX ถูกกว่า Ethereum <strong class="text-green-600 dark:text-green-400">500-5,000 เท่า</strong>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Problem 2: Slow --}}
                <div class="glass rounded-3xl p-8 border-l-4 border-orange-500 feature-card">
                    <div class="flex items-start space-x-4">
                        <div class="w-16 h-16 gradient-orange rounded-2xl flex items-center justify-center flex-shrink-0 shadow-xl">
                            <i class="fas fa-turtle text-3xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                🐢 ช้า ไม่เหมาะกับการใช้งานจริง
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">
                                Bitcoin ใช้เวลา <strong>10 นาที</strong> ต่อ Block,
                                Ethereum <strong>15 วินาที</strong> ยังช้าเกินไปสำหรับ Real-time Applications
                            </p>
                            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4">
                                <div class="text-orange-600 dark:text-orange-400 font-mono text-sm space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span>Bitcoin:</span>
                                        <span class="font-bold text-lg">~600s (10 min)</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span>Ethereum:</span>
                                        <span class="font-bold text-lg">~15s</span>
                                    </div>
                                    <div class="border-t border-orange-300 dark:border-orange-700 pt-2 flex justify-between items-center text-green-600 dark:text-green-400">
                                        <span class="font-bold">TPIX:</span>
                                        <span class="font-bold text-xl">2s ⚡</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400 italic">
                                🚀 TPIX เร็วกว่า Bitcoin <strong class="text-green-600 dark:text-green-400">300 เท่า</strong>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Problem 3: No Real Use Cases --}}
                <div class="glass rounded-3xl p-8 border-l-4 border-purple-500 feature-card">
                    <div class="flex items-start space-x-4">
                        <div class="w-16 h-16 gradient-purple rounded-2xl flex items-center justify-center flex-shrink-0 shadow-xl">
                            <i class="fas fa-link-slash text-3xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                🔗 ไม่มี Use Cases ในชีวิตจริง
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">
                                Blockchain ส่วนใหญ่เน้น <strong>Trading</strong> อย่างเดียว
                                ไม่สามารถใช้จ่ายในชีวิตประจำวันได้
                            </p>
                            <ul class="space-y-3">
                                <li class="flex items-start text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-times-circle text-red-500 mr-3 mt-1 flex-shrink-0"></i>
                                    <span>ไม่สามารถซื้อของได้</span>
                                </li>
                                <li class="flex items-start text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-times-circle text-red-500 mr-3 mt-1 flex-shrink-0"></i>
                                    <span>ไม่มีระบบ Reward ที่ใช้งานได้จริง</span>
                                </li>
                                <li class="flex items-start text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-times-circle text-red-500 mr-3 mt-1 flex-shrink-0"></i>
                                    <span>ไม่เชื่อมต่อกับธุรกิจจริง</span>
                                </li>
                                <li class="flex items-start text-green-600 dark:text-green-400 font-bold">
                                    <i class="fas fa-check-circle mr-3 mt-1 flex-shrink-0"></i>
                                    <span>TPIX: มี 11+ Use Cases ที่ใช้งานได้จริง ✅</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Problem 4: No Transparency --}}
                <div class="glass rounded-3xl p-8 border-l-4 border-pink-500 feature-card">
                    <div class="flex items-start space-x-4">
                        <div class="w-16 h-16 gradient-pink rounded-2xl flex items-center justify-center flex-shrink-0 shadow-xl">
                            <i class="fas fa-shield-alt text-3xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                🚫 Supply Chain ไม่โปร่งใส
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">
                                ไม่สามารถตรวจสอบที่มาของอาหาร/สินค้าได้
                                เสี่ยงต่อ<strong>สินค้าปลอม อาหารไม่ปลอดภัย</strong>
                            </p>
                            <ul class="space-y-3">
                                <li class="flex items-start text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-times-circle text-red-500 mr-3 mt-1 flex-shrink-0"></i>
                                    <span>ไม่รู้ที่มาของวัตถุดิบ</span>
                                </li>
                                <li class="flex items-start text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-times-circle text-red-500 mr-3 mt-1 flex-shrink-0"></i>
                                    <span>ไม่รู้กระบวนการผลิต</span>
                                </li>
                                <li class="flex items-start text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-times-circle text-red-500 mr-3 mt-1 flex-shrink-0"></i>
                                    <span>ไม่สามารถยืนยันคุณภาพได้</span>
                                </li>
                                <li class="flex items-start text-green-600 dark:text-green-400 font-bold">
                                    <i class="fas fa-check-circle mr-3 mt-1 flex-shrink-0"></i>
                                    <span>TPIX FoodPassport: ตรวจสอบได้ 100% ✅</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Solution Statement --}}
        <div class="mt-20">
            <div class="text-center mb-12">
                <div class="inline-block px-6 py-2 gradient-green text-white rounded-full text-sm font-bold mb-4 shadow-lg">
                    ✅ วิธีแก้ปัญหา
                </div>
                <h2 class="text-5xl md:text-6xl font-black text-gray-900 dark:text-white mb-4">
                    TPIX แก้ปัญหาอย่างไร?
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                    เราสร้าง Blockchain ของเราเอง เพื่อแก้ปัญหาทั้งหมดข้างต้น
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto mb-12">

                {{-- Solution 1: Fast + Cheap --}}
                <div class="glass rounded-3xl p-8 text-center feature-card">
                    <div class="w-20 h-20 gradient-green rounded-full flex items-center justify-center mx-auto mb-6 shadow-2xl floating">
                        <i class="fas fa-bolt text-4xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        ⚡ เร็ว + ถูก
                    </h3>
                    <div class="text-6xl font-black text-gradient mb-4">2s</div>
                    <p class="text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                        Block Time เพียง <strong>2 วินาที</strong><br>
                        ค่าธรรมเนียมต่ำกว่า <strong>$0.01</strong>
                    </p>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4">
                        <div class="text-sm text-green-600 dark:text-green-400 font-bold space-y-1">
                            <div>✅ เร็วกว่า Bitcoin 300x</div>
                            <div>✅ ถูกกว่า Ethereum 500-5,000x</div>
                            <div>✅ รองรับธุรกรรม Real-time</div>
                        </div>
                    </div>
                </div>

                {{-- Solution 2: Real Use Cases --}}
                <div class="glass rounded-3xl p-8 text-center feature-card">
                    <div class="w-20 h-20 gradient-blue rounded-full flex items-center justify-center mx-auto mb-6 shadow-2xl floating" style="animation-delay: 0.2s;">
                        <i class="fas fa-shopping-cart text-4xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        🛍️ ใช้งานได้จริง
                    </h3>
                    <div class="text-6xl font-black text-gradient mb-4">11+</div>
                    <p class="text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                        Use Cases ที่ใช้งานได้จริง<br>
                        ในชีวิตประจำวัน
                    </p>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 text-left">
                        <div class="text-sm text-gray-700 dark:text-gray-300 space-y-2">
                            <div class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>ซื้อของออนไลน์</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>จองโรงแรม</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>สั่งอาหาร Delivery</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>ตรวจสอบที่มาอาหาร</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Solution 3: Transparent --}}
                <div class="glass rounded-3xl p-8 text-center feature-card">
                    <div class="w-20 h-20 gradient-purple rounded-full flex items-center justify-center mx-auto mb-6 shadow-2xl floating" style="animation-delay: 0.4s;">
                        <i class="fas fa-search text-4xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        🔍 โปร่งใส ตรวจสอบได้
                    </h3>
                    <div class="text-6xl font-black text-gradient mb-4">100%</div>
                    <p class="text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                        Blockchain ทำให้ทุกอย่าง<br>
                        โปร่งใส ตรวจสอบได้
                    </p>
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 text-left">
                        <div class="text-sm text-gray-700 dark:text-gray-300 space-y-2">
                            <div class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>ตรวจสอบ TX ทุกรายการ</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>ตรวจสอบที่มาสินค้า</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>ไม่มีการโกง Affiliate</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>ตรวจสอบคุณภาพอาหาร</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Competitive Advantage --}}
            <div class="mt-16 glass rounded-3xl p-12 max-w-6xl mx-auto">
                <h3 class="text-4xl font-black text-gray-900 dark:text-white text-center mb-8">
                    🎯 ข้อได้เปรียบเหนือคู่แข่ง
                </h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">

                    <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-2xl feature-card">
                        <div class="text-5xl mb-4">🏗️</div>
                        <div class="font-bold text-gray-900 dark:text-white mb-2 text-lg">Own Blockchain</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            ไม่ต้องพึ่งพา Ethereum/BSC<br>
                            ควบคุมได้เต็มที่
                        </div>
                    </div>

                    <div class="text-center p-6 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl feature-card">
                        <div class="text-5xl mb-4">⚡</div>
                        <div class="font-bold text-gray-900 dark:text-white mb-2 text-lg">Ultra Fast</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            1,500 TPS<br>
                            2s Block Time
                        </div>
                    </div>

                    <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl feature-card">
                        <div class="text-5xl mb-4">💰</div>
                        <div class="font-bold text-gray-900 dark:text-white mb-2 text-lg">Low Fees</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            &lt;$0.01 ต่อ Transaction<br>
                            ถูกกว่า Ethereum 500x+
                        </div>
                    </div>

                    <div class="text-center p-6 bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-2xl feature-card">
                        <div class="text-5xl mb-4">🛠️</div>
                        <div class="font-bold text-gray-900 dark:text-white mb-2 text-lg">EVM Compatible</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            ใช้ Solidity ได้เลย<br>
                            Migrate จาก Ethereum ง่าย
                        </div>
                    </div>

                </div>

                {{-- Why TPIX is Better --}}
                <div class="mt-8 p-6 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 rounded-2xl text-white text-center">
                    <div class="text-2xl font-black mb-2">
                        🚀 ทำไมต้อง TPIX?
                    </div>
                    <div class="text-lg">
                        เร็วกว่า • ถูกกว่า • ใช้งานได้จริง • โปร่งใส
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>
