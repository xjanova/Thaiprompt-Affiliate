@php
    $logo = \App\Models\Setting::get('logo');
    $appName = \App\Models\Setting::get('app_name', 'ไทยพร๊อม');
@endphp

<!-- Slide 1: Title / Cover -->
<div class="slide active">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 p-12">
        <div class="text-center text-white max-w-5xl">
            <div class="mb-8 animate-bounce">
                @if($logo)
                    <img src="{{ asset('storage/' . $logo) }}" alt="{{ $appName }}" class="w-32 h-32 mx-auto filter drop-shadow-2xl object-contain">
                @else
                    <img src="{{ asset('images/logo.svg') }}" alt="{{ $appName }}" class="w-32 h-32 mx-auto filter drop-shadow-2xl">
                @endif
            </div>
            <h1 class="text-7xl md:text-8xl font-black mb-6 leading-tight">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-pink-300 to-purple-300">
                    {{ $appName }}
                </span>
            </h1>
            <h2 class="text-4xl md:text-5xl font-bold mb-8">
                แพลตฟอร์มธุรกิจออนไลน์ครบวงจร
            </h2>
            <p class="text-2xl text-indigo-200 mb-12 max-w-3xl mx-auto leading-relaxed">
                รวมระบบ MLM, E-Commerce, AI Chatbot และอื่นๆ อีกมากมาย
                <br>
                ในแพลตฟอร์มเดียว พร้อมใช้งานทันที
            </p>
            <div class="flex items-center justify-center gap-8 text-lg">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-emerald-400 rounded-full animate-pulse"></div>
                    <span>Production Ready</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-emerald-400 rounded-full animate-pulse"></div>
                    <span>Enterprise Level</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-emerald-400 rounded-full animate-pulse"></div>
                    <span>Scalable Architecture</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 2: Overview / ภาพรวมระบบ -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🎯 ภาพรวมระบบ ไทยพร๊อม
            </h2>
            <div class="grid grid-cols-2 gap-8">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8 transform hover:scale-105 transition-all">
                    <div class="text-6xl mb-4">📊</div>
                    <h3 class="text-3xl font-bold text-white mb-4">ข้อมูลทางเทคนิค</h3>
                    <ul class="space-y-3 text-white text-lg">
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span><strong>113+</strong> Database Models</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span><strong>105</strong> Database Tables</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span><strong>91</strong> HTTP Controllers</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span><strong>136</strong> Database Migrations</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8 transform hover:scale-105 transition-all">
                    <div class="text-6xl mb-4">🚀</div>
                    <h3 class="text-3xl font-bold text-white mb-4">เทคโนโลยีชั้นนำ</h3>
                    <ul class="space-y-3 text-white text-lg">
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>Laravel 11 Framework</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>MySQL Database</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>Tailwind CSS + Alpine.js</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>RESTful API Architecture</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-8 bg-gradient-to-r from-emerald-600 to-teal-600 rounded-3xl p-6 text-center">
                <p class="text-2xl text-white font-bold">
                    💡 ระบบที่สมบูรณ์แบบที่สุดในประเทศไทย
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 2.5: ความเป็นมา / Our Story (NEW) -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-slate-900 via-gray-900 to-slate-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                📖 เรื่องราวของเรา
            </h2>

            <div class="grid grid-cols-1 gap-8 mb-8">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <h3 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
                        <span class="text-5xl">💭</span>
                        <span>ปัญหาที่เราพบ</span>
                    </h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-4 text-white">
                            <div class="flex items-start gap-3">
                                <span class="text-red-400 text-2xl">❌</span>
                                <div>
                                    <h4 class="font-bold text-lg mb-1">ระบบ MLM แพง ไม่คุ้มค่า</h4>
                                    <p class="text-white/70">ราคาหลักแสน-หลักล้าน แต่คุณภาพไม่ตรงราคา</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="text-red-400 text-2xl">❌</span>
                                <div>
                                    <h4 class="font-bold text-lg mb-1">ระบบไม่ครบ ต้องซื้อเพิ่ม</h4>
                                    <p class="text-white/70">E-Commerce แยก, Payment Gateway แยก, AI แยก</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="text-red-400 text-2xl">❌</span>
                                <div>
                                    <h4 class="font-bold text-lg mb-1">Support ช้า แก้ปัญหาไม่ได้</h4>
                                    <p class="text-white/70">รอนาน ตอบช้า ไม่มีมาตรฐาน</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-4 text-white">
                            <div class="flex items-start gap-3">
                                <span class="text-red-400 text-2xl">❌</span>
                                <div>
                                    <h4 class="font-bold text-lg mb-1">เทคโนโลยีล้าสมัย</h4>
                                    <p class="text-white/70">UI เก่า ใช้งานยาก ไม่ responsive</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="text-red-400 text-2xl">❌</span>
                                <div>
                                    <h4 class="font-bold text-lg mb-1">ไม่มี Source Code</h4>
                                    <p class="text-white/70">ถูกผูกมัดตลอดชีพ แก้ไขเองไม่ได้</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="text-red-400 text-2xl">❌</span>
                                <div>
                                    <h4 class="font-bold text-lg mb-1">ค่าบำรุงรักษาแพง</h4>
                                    <p class="text-white/70">จ่ายเดือนละหลักหมื่น ไม่มีที่สิ้นสุด</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-3xl p-8">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <h3 class="text-3xl font-bold text-white mb-4">💡 เราจึงสร้าง ไทยพร๊อม</h3>
                        <p class="text-white/90 text-xl leading-relaxed">
                            แพลตฟอร์มที่รวมทุกอย่างไว้ในที่เดียว <strong>ราคาเป็นกันเอง</strong> คุณภาพระดับ Enterprise
                            <br>
                            พร้อม <strong>Source Code แท้</strong> ปรับแต่งได้เต็มที่ แก้ปัญหาได้ทันที
                        </p>
                    </div>
                    <div class="text-8xl ml-8">🚀</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 2.6: ทำไมต้อง ไทยพร๊อม / What Makes Us Different (NEW) -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-purple-900 via-pink-900 to-red-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                ⚡ เรามีดี ที่คนอื่นไม่กล้าทำ
            </h2>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <!-- Left Column -->
                <div class="space-y-6">
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6">
                        <div class="flex items-start gap-4">
                            <div class="text-5xl">🎯</div>
                            <div class="flex-1 text-white">
                                <h3 class="text-2xl font-bold mb-3">Source Code แท้ 100%</h3>
                                <p class="text-white/80 leading-relaxed">
                                    เราให้ <strong>Source Code แท้</strong> ไม่เข้ารหัส ปรับแต่งได้เต็มที่
                                    ไม่ผูกมัด ไม่ถูกเอาเปรียบ คุณเป็นเจ้าของระบบจริงๆ
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6">
                        <div class="flex items-start gap-4">
                            <div class="text-5xl">💰</div>
                            <div class="flex-1 text-white">
                                <h3 class="text-2xl font-bold mb-3">ไม่มีค่าบำรุงรักษา</h3>
                                <p class="text-white/80 leading-relaxed">
                                    จ่ายครั้งเดียว ใช้ตลอดชีพ ไม่มีค่า Monthly Fee
                                    <br>
                                    แก้ไขเองได้ เพิ่มฟีเจอร์เองได้ <strong>ประหยัดหลักแสนต่อปี</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6">
                        <div class="flex items-start gap-4">
                            <div class="text-5xl">🔒</div>
                            <div class="flex-1 text-white">
                                <h3 class="text-2xl font-bold mb-3">ติดตั้งบน Server ของคุณ</h3>
                                <p class="text-white/80 leading-relaxed">
                                    ข้อมูลอยู่กับคุณ 100% ไม่ผ่านเซิร์ฟเวอร์คนอื่น
                                    <br>
                                    ปลอดภัย ไม่มีใครเข้าถึงข้อมูลของคุณได้
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6">
                        <div class="flex items-start gap-4">
                            <div class="text-5xl">🚀</div>
                            <div class="flex-1 text-white">
                                <h3 class="text-2xl font-bold mb-3">เทคโนโลยีล้ำสมัย</h3>
                                <p class="text-white/80 leading-relaxed">
                                    Laravel 11, Tailwind CSS, Alpine.js, AI Integration
                                    <br>
                                    <strong>เทคโนโลยีปี 2025</strong> ไม่ใช่ของเก่าๆ ที่ล้าสมัย
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6">
                        <div class="flex items-start gap-4">
                            <div class="text-5xl">📦</div>
                            <div class="flex-1 text-white">
                                <h3 class="text-2xl font-bold mb-3">ครบจริง ไม่ต้องซื้อเพิ่ม</h3>
                                <p class="text-white/80 leading-relaxed">
                                    MLM + E-Commerce + Payment + AI + KYC + Analytics
                                    <br>
                                    ครบในระบบเดียว <strong>ไม่ต้องซื้อ Add-on</strong> แพงๆ
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6">
                        <div class="flex items-start gap-4">
                            <div class="text-5xl">💪</div>
                            <div class="flex-1 text-white">
                                <h3 class="text-2xl font-bold mb-3">Support จริงจัง</h3>
                                <p class="text-white/80 leading-relaxed">
                                    ทีมพัฒนาที่แท้จริง ไม่ใช่แค่ขายแล้วทิ้ง
                                    <br>
                                    มี Documentation ครบ มี Community รอช่วย
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-yellow-600 to-orange-600 rounded-3xl p-8 text-center">
                <p class="text-3xl text-white font-bold">
                    🏆 เราทำในสิ่งที่คนอื่นไม่กล้าทำ เพราะเรามั่นใจในคุณภาพของเรา
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 2.7: ROI & Investment Value (NEW) -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-emerald-900 via-teal-900 to-cyan-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                📈 การลงทุนที่คุ้มค่า
            </h2>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <!-- Cost Comparison -->
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <h3 class="text-3xl font-bold text-white mb-6 text-center">💸 เปรียบเทียบต้นทุน</h3>

                    <div class="space-y-4">
                        <!-- Competitor -->
                        <div class="bg-red-900/30 border border-red-500/50 rounded-2xl p-6">
                            <h4 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                                <span class="text-red-400">❌</span>
                                <span>ระบบทั่วไป</span>
                            </h4>
                            <div class="space-y-2 text-white/90">
                                <div class="flex justify-between py-2 border-b border-white/10">
                                    <span>ค่าติดตั้งระบบ:</span>
                                    <strong class="text-red-400">฿300,000</strong>
                                </div>
                                <div class="flex justify-between py-2 border-b border-white/10">
                                    <span>ค่า Monthly Fee (x12):</span>
                                    <strong class="text-red-400">฿180,000</strong>
                                </div>
                                <div class="flex justify-between py-2 border-b border-white/10">
                                    <span>ค่า Customization:</span>
                                    <strong class="text-red-400">฿150,000</strong>
                                </div>
                                <div class="flex justify-between py-2 border-b border-white/10">
                                    <span>ค่า Add-ons:</span>
                                    <strong class="text-red-400">฿100,000</strong>
                                </div>
                                <div class="flex justify-between pt-4 text-xl">
                                    <span class="font-bold">รวม (ปีแรก):</span>
                                    <strong class="text-3xl text-red-400">฿730,000</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Our System -->
                        <div class="bg-emerald-900/30 border border-emerald-500/50 rounded-2xl p-6">
                            <h4 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                                <span class="text-emerald-400">✓</span>
                                <span>ไทยพร๊อม</span>
                            </h4>
                            <div class="space-y-2 text-white/90">
                                <div class="flex justify-between py-2 border-b border-white/10">
                                    <span>ค่าระบบครบ (ครั้งเดียว):</span>
                                    <strong class="text-emerald-400">฿49,900</strong>
                                </div>
                                <div class="flex justify-between py-2 border-b border-white/10">
                                    <span>ค่า Monthly Fee:</span>
                                    <strong class="text-emerald-400">฿0</strong>
                                </div>
                                <div class="flex justify-between py-2 border-b border-white/10">
                                    <span>Source Code แท้:</span>
                                    <strong class="text-emerald-400">ให้ฟรี</strong>
                                </div>
                                <div class="flex justify-between py-2 border-b border-white/10">
                                    <span>Updates & Support:</span>
                                    <strong class="text-emerald-400">1 ปีฟรี</strong>
                                </div>
                                <div class="flex justify-between pt-4 text-xl">
                                    <span class="font-bold">รวม (ปีแรก):</span>
                                    <strong class="text-3xl text-emerald-400">฿49,900</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ROI Graph -->
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <h3 class="text-3xl font-bold text-white mb-6 text-center">💎 ประหยัดได้จริง</h3>

                    <!-- SVG Graph -->
                    <div class="bg-white/5 rounded-2xl p-6 mb-6">
                        <svg viewBox="0 0 400 250" class="w-full h-auto">
                            <!-- Grid Lines -->
                            <line x1="50" y1="200" x2="380" y2="200" stroke="rgba(255,255,255,0.2)" stroke-width="1"/>
                            <line x1="50" y1="150" x2="380" y2="150" stroke="rgba(255,255,255,0.2)" stroke-width="1"/>
                            <line x1="50" y1="100" x2="380" y2="100" stroke="rgba(255,255,255,0.2)" stroke-width="1"/>
                            <line x1="50" y1="50" x2="380" y2="50" stroke="rgba(255,255,255,0.2)" stroke-width="1"/>

                            <!-- Axes -->
                            <line x1="50" y1="10" x2="50" y2="210" stroke="white" stroke-width="2"/>
                            <line x1="45" y1="200" x2="385" y2="200" stroke="white" stroke-width="2"/>

                            <!-- Labels Y-axis -->
                            <text x="35" y="205" fill="white" font-size="12" text-anchor="end">0</text>
                            <text x="35" y="155" fill="white" font-size="12" text-anchor="end">200K</text>
                            <text x="35" y="105" fill="white" font-size="12" text-anchor="end">400K</text>
                            <text x="35" y="55" fill="white" font-size="12" text-anchor="end">600K</text>

                            <!-- Labels X-axis -->
                            <text x="120" y="220" fill="white" font-size="12" text-anchor="middle">ปี 1</text>
                            <text x="230" y="220" fill="white" font-size="12" text-anchor="middle">ปี 2</text>
                            <text x="340" y="220" fill="white" font-size="12" text-anchor="middle">ปี 3</text>

                            <!-- Competitor Line (Red) -->
                            <path d="M 50 102 L 120 102 L 230 72 L 340 42"
                                  stroke="#EF4444"
                                  stroke-width="4"
                                  fill="none"
                                  stroke-linecap="round"/>

                            <!-- Our System Line (Green) -->
                            <path d="M 50 193 L 120 193 L 230 193 L 340 193"
                                  stroke="#10B981"
                                  stroke-width="4"
                                  fill="none"
                                  stroke-linecap="round"/>

                            <!-- Points -->
                            <circle cx="120" cy="102" r="6" fill="#EF4444"/>
                            <circle cx="230" cy="72" r="6" fill="#EF4444"/>
                            <circle cx="340" cy="42" r="6" fill="#EF4444"/>

                            <circle cx="120" cy="193" r="6" fill="#10B981"/>
                            <circle cx="230" cy="193" r="6" fill="#10B981"/>
                            <circle cx="340" cy="193" r="6" fill="#10B981"/>

                            <!-- Legend -->
                            <line x1="260" y1="25" x2="290" y2="25" stroke="#EF4444" stroke-width="3"/>
                            <text x="295" y="30" fill="white" font-size="12">ระบบทั่วไป</text>

                            <line x1="260" y1="40" x2="290" y2="40" stroke="#10B981" stroke-width="3"/>
                            <text x="295" y="45" fill="white" font-size="12">ไทยพร๊อม</text>
                        </svg>
                    </div>

                    <div class="space-y-4 text-white">
                        <div class="bg-emerald-900/30 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-lg">💰 ประหยัดได้ในปีแรก:</span>
                                <span class="text-3xl font-black text-emerald-400">฿680,100</span>
                            </div>
                        </div>
                        <div class="bg-emerald-900/30 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-lg">💎 ประหยัดได้ใน 3 ปี:</span>
                                <span class="text-3xl font-black text-emerald-400">฿1,400,100+</span>
                            </div>
                        </div>
                        <div class="text-center pt-4">
                            <p class="text-emerald-300 text-lg font-semibold">
                                🎯 คุ้มค่ากว่า <strong class="text-2xl">14 เท่า!</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-yellow-600 to-orange-600 rounded-3xl p-6 text-center">
                <p class="text-2xl text-white font-bold mb-2">
                    ⚡ ลงทุนน้อย ได้มาก เป็นเจ้าของเต็มรูปแบบ
                </p>
                <p class="text-white/90 text-lg">
                    ไม่มีค่าใช้จ่ายซ่อนเร้น ไม่ผูกมัด คุ้มค่าที่สุดในตลาด!
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 2.8: ทำไมต้องเป็นคนไทย (NEW) -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-red-900 via-orange-900 to-yellow-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🇹🇭 ทำไมต้องเป็นคนไทย?
            </h2>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <!-- Left: Problems with Foreign Platforms -->
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <h3 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
                        <span class="text-4xl">❌</span>
                        <span>ปัญหาแพลตฟอร์มต่างชาติ</span>
                    </h3>
                    <div class="space-y-4 text-white">
                        <div class="bg-red-900/30 rounded-2xl p-5 border border-red-500/50">
                            <div class="flex items-start gap-3">
                                <span class="text-3xl">💸</span>
                                <div>
                                    <h4 class="font-bold text-xl mb-2 text-red-300">เงินหลั่งไหลออกนอกประเทศ</h4>
                                    <p class="text-white/90">ค่าบริการ, ค่าธรรมเนียม, กำไร ทั้งหมดไปต่างประเทศ ไม่เหลืออะไรให้คนไทย</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-red-900/30 rounded-2xl p-5 border border-red-500/50">
                            <div class="flex items-start gap-3">
                                <span class="text-3xl">🗣️</span>
                                <div>
                                    <h4 class="font-bold text-xl mb-2 text-red-300">ไม่เข้าใจบริบทไทย</h4>
                                    <p class="text-white/90">ภาษา, วัฒนธรรม, พฤติกรรม ไม่ตรงกับคนไทย การใช้งานลำบาก</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-red-900/30 rounded-2xl p-5 border border-red-500/50">
                            <div class="flex items-start gap-3">
                                <span class="text-3xl">📞</span>
                                <div>
                                    <h4 class="font-bold text-xl mb-2 text-red-300">Support ไม่มีคุณภาพ</h4>
                                    <p class="text-white/90">ติดต่อยาก, ตอบช้า, ไม่เข้าใจปัญหา แก้ไขไม่ทันใจ</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-red-900/30 rounded-2xl p-5 border border-red-500/50">
                            <div class="flex items-start gap-3">
                                <span class="text-3xl">⚖️</span>
                                <div>
                                    <h4 class="font-bold text-xl mb-2 text-red-300">กฎหมายไม่คุ้มครอง</h4>
                                    <p class="text-white/90">เกิดปัญหาร้องเรียนยาก ฟ้องผิดประเทศ ได้รับความเป็นธรรมยาก</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Benefits of Thai Platform -->
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <h3 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
                        <span class="text-4xl">✅</span>
                        <span>ข้อดีแพลตฟอร์มคนไทย</span>
                    </h3>
                    <div class="space-y-4 text-white">
                        <div class="bg-emerald-900/30 rounded-2xl p-5 border border-emerald-500/50">
                            <div class="flex items-start gap-3">
                                <span class="text-3xl">💚</span>
                                <div>
                                    <h4 class="font-bold text-xl mb-2 text-emerald-300">เงินหมุนเวียนในประเทศ</h4>
                                    <p class="text-white/90">กำไรกลับมาคนไทย สร้างงานสร้างรายได้ให้คนไทย เศรษฐกิจในประเทศแข็งแรง</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-emerald-900/30 rounded-2xl p-5 border border-emerald-500/50">
                            <div class="flex items-start gap-3">
                                <span class="text-3xl">🎯</span>
                                <div>
                                    <h4 class="font-bold text-xl mb-2 text-emerald-300">เข้าใจตลาดไทยจริงๆ</h4>
                                    <p class="text-white/90">ภาษา 100% วัฒนธรรม, ระบบการเงิน, การตลาด ตรงใจคนไทยทุกอย่าง</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-emerald-900/30 rounded-2xl p-5 border border-emerald-500/50">
                            <div class="flex items-start gap-3">
                                <span class="text-3xl">🚀</span>
                                <div>
                                    <h4 class="font-bold text-xl mb-2 text-emerald-300">Support ตัวจริงจากคนไทย</h4>
                                    <p class="text-white/90">Dev จริง, ตอบไว, แก้ไขทันใจ พูดคุยรู้เรื่อง ช่วยเหลือจริงใจ</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-emerald-900/30 rounded-2xl p-5 border border-emerald-500/50">
                            <div class="flex items-start gap-3">
                                <span class="text-3xl">🛡️</span>
                                <div>
                                    <h4 class="font-bold text-xl mb-2 text-emerald-300">มั่นใจในกฎหมายไทย</h4>
                                    <p class="text-white/90">อยู่ในประเทศเดียวกัน ร้องเรียนง่าย คุ้มครองสิทธิ์ได้จริง</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-gradient-to-r from-red-600 to-orange-600 rounded-3xl p-6 text-center">
                    <p class="text-2xl text-white font-bold mb-2">
                        ⚠️ วันนี้คุณไม่ต้องกลัวคนไทยน้อยหน้า
                    </p>
                    <p class="text-white/90 text-lg">
                        เราพัฒนาระบบได้ดีไม่แพ้ชาติใดในโลก! มั่นใจในฝีมือนักพัฒนาไทย
                    </p>
                </div>
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-3xl p-6 text-center">
                    <p class="text-2xl text-white font-bold mb-2">
                        💪 ไทยพร๊อม = Made by Thai, For Thai
                    </p>
                    <p class="text-white/90 text-lg">
                        ออกแบบโดยคนไทย เพื่อคนไทย เข้าใจธุรกิจไทยมากที่สุด
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 3: MLM System Overview -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-purple-900 via-indigo-900 to-blue-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🔄 ระบบ MLM (Multi-Level Marketing)
            </h2>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <div class="text-5xl mb-4">🌳</div>
                    <h3 class="text-3xl font-bold text-white mb-4">Unilevel Plan</h3>
                    <p class="text-white/80 text-lg mb-4">แผนการตลาดแบบไม่จำกัดสายงาน</p>
                    <ul class="space-y-2 text-white">
                        <li>• ไม่จำกัดจำนวนคนในแนวหน้า</li>
                        <li>• รับคอมมิชชั่นหลายระดับ</li>
                        <li>• เหมาะกับการขยายเครือข่าย</li>
                        <li>• ระบบคำนวณอัตโนมัติ</li>
                    </ul>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <div class="text-5xl mb-4">⚖️</div>
                    <h3 class="text-3xl font-bold text-white mb-4">Binary Plan</h3>
                    <p class="text-white/80 text-lg mb-4">แผนการตลาดแบบขา 2 ขา</p>
                    <ul class="space-y-2 text-white">
                        <li>• จำกัด 2 คนในแนวหน้า</li>
                        <li>• โบนัสจากความสมดุลทั้ง 2 ขา</li>
                        <li>• เหมาะกับทีมเวิร์คแน่น</li>
                        <li>• รายได้เติบโตแบบทวีคูณ</li>
                    </ul>
                </div>
            </div>

            <div class="bg-gradient-to-r from-yellow-500 to-orange-500 rounded-3xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-2xl font-bold text-white mb-2">Commission Engine</h4>
                        <p class="text-white/90">คำนวณคอมมิชชั่นอัตโนมัติ แม่นยำ ถูกต้อง 100%</p>
                    </div>
                    <div class="text-6xl">💰</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 3.5: MLM Commission Structure (NEW) -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-blue-900 via-purple-900 to-indigo-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                💎 โครงสร้างค่าคอมมิชชั่น MLM
            </h2>

            <div class="mb-8">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <h3 class="text-3xl font-bold text-white mb-6 text-center">🌟 Unilevel Commission</h3>
                    <div class="grid grid-cols-5 gap-4">
                        <div class="text-center p-4 bg-gradient-to-br from-emerald-600 to-green-600 rounded-2xl">
                            <div class="text-4xl font-black text-white mb-2">15%</div>
                            <p class="text-white/90 font-semibold">ชั้นที่ 1</p>
                            <p class="text-white/70 text-sm">ทีมตรง</p>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-blue-600 to-cyan-600 rounded-2xl">
                            <div class="text-4xl font-black text-white mb-2">10%</div>
                            <p class="text-white/90 font-semibold">ชั้นที่ 2</p>
                            <p class="text-white/70 text-sm">ชั้น 2</p>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl">
                            <div class="text-4xl font-black text-white mb-2">7%</div>
                            <p class="text-white/90 font-semibold">ชั้นที่ 3</p>
                            <p class="text-white/70 text-sm">ชั้น 3</p>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl">
                            <div class="text-4xl font-black text-white mb-2">5%</div>
                            <p class="text-white/90 font-semibold">ชั้นที่ 4</p>
                            <p class="text-white/70 text-sm">ชั้น 4</p>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-pink-600 to-red-600 rounded-2xl">
                            <div class="text-4xl font-black text-white mb-2">3%</div>
                            <p class="text-white/90 font-semibold">ชั้นที่ 5</p>
                            <p class="text-white/70 text-sm">ชั้น 5</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6">
                    <h4 class="text-2xl font-bold text-white mb-4 flex items-center gap-2">
                        <span>⚖️</span>
                        <span>Binary Bonus</span>
                    </h4>
                    <ul class="space-y-3 text-white">
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400 text-2xl">✓</span>
                            <span><strong>10%</strong> จากขาอ่อน</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400 text-2xl">✓</span>
                            <span>คำนวณตามสัปดาห์</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400 text-2xl">✓</span>
                            <span>ต้องมียอดทั้ง 2 ขา</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6">
                    <h4 class="text-2xl font-bold text-white mb-4 flex items-center gap-2">
                        <span>🎁</span>
                        <span>Matching Bonus</span>
                    </h4>
                    <ul class="space-y-3 text-white">
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400 text-2xl">✓</span>
                            <span><strong>5%</strong> จากทีมงาน</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400 text-2xl">✓</span>
                            <span>Matching ถึง 3 ชั้น</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400 text-2xl">✓</span>
                            <span>โบนัสพิเศษสำหรับลีดเดอร์</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 3.6: MLM Income Examples (NEW) -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                💰 ตัวอย่างรายได้จริง MLM
            </h2>

            <div class="grid grid-cols-3 gap-6 mb-8">
                <!-- Example 1: Beginner -->
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-6">
                    <div class="text-center mb-4">
                        <div class="text-5xl mb-3">🌱</div>
                        <h3 class="text-2xl font-bold text-white mb-2">มือใหม่</h3>
                        <p class="text-emerald-400 text-sm">เดือนที่ 1-3</p>
                    </div>
                    <div class="space-y-3 text-white">
                        <div class="flex justify-between items-center py-2 border-b border-white/20">
                            <span>ทีมตรง:</span>
                            <strong>5 คน</strong>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-white/20">
                            <span>ยอดขายเฉลี่ย:</span>
                            <strong>฿2,000/คน</strong>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-white/20">
                            <span>คอมชั้น 1 (15%):</span>
                            <strong>฿1,500</strong>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-white/20">
                            <span>Binary Bonus:</span>
                            <strong>฿800</strong>
                        </div>
                        <div class="bg-gradient-to-r from-emerald-600 to-green-600 rounded-xl p-3 text-center mt-4">
                            <p class="text-white/80 text-sm">รวมต่อเดือน</p>
                            <p class="text-3xl font-black text-white">฿2,300</p>
                        </div>
                    </div>
                </div>

                <!-- Example 2: Intermediate -->
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-6 ring-2 ring-yellow-500">
                    <div class="text-center mb-4">
                        <div class="text-5xl mb-3">🚀</div>
                        <h3 class="text-2xl font-bold text-white mb-2">มืออาชีพ</h3>
                        <p class="text-yellow-400 text-sm">เดือนที่ 4-6</p>
                    </div>
                    <div class="space-y-3 text-white">
                        <div class="flex justify-between items-center py-2 border-b border-white/20">
                            <span>ทีมตรง:</span>
                            <strong>20 คน</strong>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-white/20">
                            <span>ยอดขายเฉลี่ย:</span>
                            <strong>฿3,000/คน</strong>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-white/20">
                            <span>คอม 5 ชั้น:</span>
                            <strong>฿12,500</strong>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-white/20">
                            <span>Binary + Matching:</span>
                            <strong>฿8,200</strong>
                        </div>
                        <div class="bg-gradient-to-r from-yellow-600 to-orange-600 rounded-xl p-3 text-center mt-4">
                            <p class="text-white/80 text-sm">รวมต่อเดือน</p>
                            <p class="text-3xl font-black text-white">฿20,700</p>
                        </div>
                    </div>
                </div>

                <!-- Example 3: Leader -->
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-6 ring-2 ring-purple-500">
                    <div class="text-center mb-4">
                        <div class="text-5xl mb-3">👑</div>
                        <h3 class="text-2xl font-bold text-white mb-2">ลีดเดอร์</h3>
                        <p class="text-purple-400 text-sm">เดือนที่ 7+</p>
                    </div>
                    <div class="space-y-3 text-white">
                        <div class="flex justify-between items-center py-2 border-b border-white/20">
                            <span>ทีมทั้งหมด:</span>
                            <strong>100+ คน</strong>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-white/20">
                            <span>ยอดขายเฉลี่ย:</span>
                            <strong>฿5,000/คน</strong>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-white/20">
                            <span>คอม 5 ชั้น:</span>
                            <strong>฿45,000</strong>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-white/20">
                            <span>Binary + Matching:</span>
                            <strong>฿28,000</strong>
                        </div>
                        <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl p-3 text-center mt-4">
                            <p class="text-white/80 text-sm">รวมต่อเดือน</p>
                            <p class="text-3xl font-black text-white">฿73,000+</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-yellow-600 to-orange-600 rounded-3xl p-6 text-center">
                <p class="text-2xl text-white font-bold mb-2">
                    💎 รายได้ไม่จำกัด! ยิ่งทีมใหญ่ รายได้ยิ่งมาก
                </p>
                <p class="text-white/90 text-lg">
                    ลีดเดอร์ระดับสูงสุดสามารถสร้างรายได้ได้ถึง <strong class="text-2xl">฿200,000 - ฿500,000+</strong> ต่อเดือน
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 3.7: ระบบจำลองรายได้ (NEW) -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-violet-900 via-purple-900 to-fuchsia-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🧮 ระบบจำลองรายได้ (Income Simulator)
            </h2>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <!-- Left: Features -->
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <h3 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
                        <span class="text-4xl">✨</span>
                        <span>ฟีเจอร์ระบบจำลอง</span>
                    </h3>
                    <div class="space-y-4 text-white">
                        <div class="bg-white/5 rounded-2xl p-5 border border-purple-500/30">
                            <div class="flex items-start gap-3">
                                <span class="text-3xl">📊</span>
                                <div>
                                    <h4 class="font-bold text-xl mb-2 text-purple-300">คำนวณรายได้ล่วงหน้า</h4>
                                    <p class="text-white/90">ใส่จำนวนทีม, ยอดขายเฉลี่ย ระบบคำนวณรายได้ทุกระดับให้ทันที</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white/5 rounded-2xl p-5 border border-pink-500/30">
                            <div class="flex items-start gap-3">
                                <span class="text-3xl">🎯</span>
                                <div>
                                    <h4 class="font-bold text-xl mb-2 text-pink-300">ทดสอบหลายสถานการณ์</h4>
                                    <p class="text-white/90">ลองปรับตัวเลขหลายๆ แบบ เพื่อหากลยุทธ์ที่เหมาะสมที่สุด</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white/5 rounded-2xl p-5 border border-blue-500/30">
                            <div class="flex items-start gap-3">
                                <span class="text-3xl">📈</span>
                                <div>
                                    <h4 class="font-bold text-xl mb-2 text-blue-300">วางแผนเป้าหมาย</h4>
                                    <p class="text-white/90">ตั้งเป้ารายได้ที่ต้องการ ระบบบอกว่าต้องมีทีมเท่าไหร่</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white/5 rounded-2xl p-5 border border-emerald-500/30">
                            <div class="flex items-start gap-3">
                                <span class="text-3xl">💡</span>
                                <div>
                                    <h4 class="font-bold text-xl mb-2 text-emerald-300">มองเห็นอนาคต</h4>
                                    <p class="text-white/90">รู้ว่าถ้าทีมเติบโตแบบนี้ต่อ จะได้รายได้เท่าไหร่ในอนาคต</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Example Calculation -->
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <h3 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
                        <span class="text-4xl">🔢</span>
                        <span>ตัวอย่างการคำนวณ</span>
                    </h3>

                    <!-- Input Section -->
                    <div class="bg-gradient-to-r from-indigo-900/50 to-purple-900/50 rounded-2xl p-6 mb-6 border border-indigo-500/50">
                        <h4 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                            <span>📥</span>
                            <span>ข้อมูลที่ใส่</span>
                        </h4>
                        <div class="space-y-3 text-white">
                            <div class="flex justify-between items-center py-2 border-b border-white/20">
                                <span className="text-lg">จำนวนทีมชั้น 1:</span>
                                <strong className="text-2xl text-emerald-400">10 คน</strong>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-white/20">
                                <span className="text-lg">จำนวนทีมชั้น 2:</span>
                                <strong className="text-2xl text-emerald-400">50 คน</strong>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-white/20">
                                <span className="text-lg">ยอดขายเฉลี่ย/คน:</span>
                                <strong className="text-2xl text-yellow-400">฿3,000</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Output Section -->
                    <div class="bg-gradient-to-r from-emerald-900/50 to-teal-900/50 rounded-2xl p-6 border border-emerald-500/50">
                        <h4 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                            <span>📤</span>
                            <span>รายได้ที่คาดว่าจะได้รับ</span>
                        </h4>
                        <div class="space-y-3 text-white">
                            <div class="flex justify-between items-center py-2 border-b border-white/20">
                                <span className="text-lg">Unilevel (ชั้น 1):</span>
                                <strong className="text-xl text-emerald-300">฿4,500</strong>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-white/20">
                                <span className="text-lg">Unilevel (ชั้น 2):</span>
                                <strong className="text-xl text-emerald-300">฿15,000</strong>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-white/20">
                                <span className="text-lg">Binary Bonus:</span>
                                <strong className="text-xl text-yellow-300">฿18,000</strong>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-white/20">
                                <span className="text-lg">Matching Bonus:</span>
                                <strong className="text-xl text-pink-300">฿7,500</strong>
                            </div>
                            <div class="bg-gradient-to-r from-yellow-600 to-orange-600 rounded-xl p-4 mt-4">
                                <div class="flex justify-between items-center">
                                    <span className="text-xl font-bold">💰 รวมทั้งหมด/เดือน:</span>
                                    <span className="text-4xl font-black">฿45,000</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6">
                <div class="bg-gradient-to-r from-blue-600 to-cyan-600 rounded-2xl p-6 text-center">
                    <div class="text-4xl mb-3">🎲</div>
                    <h4 class="text-xl font-bold text-white mb-2">ลองได้ไม่จำกัด</h4>
                    <p class="text-white/90">ทดสอบหลายสถานการณ์จนกว่าจะเจอแผนที่ดีที่สุด</p>
                </div>
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-2xl p-6 text-center">
                    <div class="text-4xl mb-3">📱</div>
                    <h4 class="text-xl font-bold text-white mb-2">ใช้งานง่าย</h4>
                    <p class="text-white/90">อินเทอร์เฟซเรียบง่าย ใครๆ ก็ใช้ได้</p>
                </div>
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-2xl p-6 text-center">
                    <div class="text-4xl mb-3">⚡</div>
                    <h4 class="text-xl font-bold text-white mb-2">ผลลัพธ์ทันที</h4>
                    <p class="text-white/90">คำนวณเสร็จในไม่กี่วินาที ได้คำตอบทันที</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 4: E-Commerce System -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-pink-900 via-purple-900 to-indigo-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🛒 ระบบ E-Commerce แบบ Multi-Vendor
            </h2>

            <div class="grid grid-cols-3 gap-6 mb-8">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 text-center">
                    <div class="text-5xl mb-3">🏪</div>
                    <h3 class="text-2xl font-bold text-white mb-2">Multi-Vendor</h3>
                    <p class="text-white/80">ร้านค้าหลายผู้ขาย</p>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 text-center">
                    <div class="text-5xl mb-3">📦</div>
                    <h3 class="text-2xl font-bold text-white mb-2">Product Management</h3>
                    <p class="text-white/80">จัดการสินค้าครบครัน</p>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 text-center">
                    <div class="text-5xl mb-3">💳</div>
                    <h3 class="text-2xl font-bold text-white mb-2">Payment Gateway</h3>
                    <p class="text-white/80">ระบบชำระเงินหลากหลาย</p>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                <h3 class="text-3xl font-bold text-white mb-6 text-center">✨ ฟีเจอร์เด่น</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex items-start gap-3">
                        <span class="text-emerald-400 text-2xl">✓</span>
                        <div>
                            <h4 class="text-white font-bold text-lg">Product Variants</h4>
                            <p class="text-white/70">สินค้าหลายตัวเลือก (สี, ขนาด)</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-emerald-400 text-2xl">✓</span>
                        <div>
                            <h4 class="text-white font-bold text-lg">Inventory System</h4>
                            <p class="text-white/70">จัดการสต็อกสินค้าแบบเรียลไทม์</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-emerald-400 text-2xl">✓</span>
                        <div>
                            <h4 class="text-white font-bold text-lg">Order Management</h4>
                            <p class="text-white/70">จัดการออเดอร์อัตโนมัติ</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-emerald-400 text-2xl">✓</span>
                        <div>
                            <h4 class="text-white font-bold text-lg">Shipping Integration</h4>
                            <p class="text-white/70">เชื่อมต่อขนส่งทุกบริษัท</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 5: AI Integration -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-cyan-900 via-blue-900 to-indigo-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🤖 ระบบ AI Chatbot ที่ฉลาดที่สุด
            </h2>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <div class="text-6xl mb-4">💬</div>
                    <h3 class="text-3xl font-bold text-white mb-4">LINE OA Integration</h3>
                    <ul class="space-y-3 text-white text-lg">
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>เชื่อมต่อ LINE Official Account</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>ตอบคำถามอัตโนมัติ 24/7</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>Rich Menu แบบ Interactive</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>Push Message Marketing</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <div class="text-6xl mb-4">🧠</div>
                    <h3 class="text-3xl font-bold text-white mb-4">RAG Technology</h3>
                    <ul class="space-y-3 text-white text-lg">
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>Retrieval-Augmented Generation</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>ค้นหาข้อมูลจากฐานความรู้</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>ตอบคำถามแม่นยำ ถูกต้อง</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>เรียนรู้และพัฒนาต่อเนื่อง</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-3xl p-8 text-center">
                <h4 class="text-3xl font-bold text-white mb-3">
                    ⚡ AI ที่ทรงพลัง ลดต้นทุนบริการลูกค้าได้ถึง 80%
                </h4>
                <p class="text-white/90 text-xl">
                    ระบบ AI ของเราสามารถตอบคำถามได้ 95% โดยไม่ต้องใช้คน
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 5.5: AI กับธุรกิจยุคใหม่ (NEW) -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🚀 AI ตอบโจทย์ธุรกิจยุคใหม่
            </h2>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <!-- Left: Why AI is Critical -->
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <h3 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
                        <span class="text-4xl">🎯</span>
                        <span>ทำไม AI จึงสำคัญ?</span>
                    </h3>
                    <div class="space-y-4 text-white">
                        <div class="bg-white/5 rounded-2xl p-4 border border-emerald-500/30">
                            <div class="flex items-start gap-3">
                                <span class="text-2xl">⚡</span>
                                <div>
                                    <h4 class="font-bold text-lg mb-1">ลดต้นทุนการดำเนินงาน</h4>
                                    <p class="text-white/80">ประหยัดค่าบริการลูกค้าได้ถึง 80% ด้วย AI Chatbot</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white/5 rounded-2xl p-4 border border-blue-500/30">
                            <div class="flex items-start gap-3">
                                <span class="text-2xl">🌍</span>
                                <div>
                                    <h4 class="font-bold text-lg mb-1">แข่งขันในตลาดโลก</h4>
                                    <p class="text-white/80">ธุรกิจที่ไม่มี AI จะล้าหลังและถูกแซงหน้า</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white/5 rounded-2xl p-4 border border-purple-500/30">
                            <div class="flex items-start gap-3">
                                <span class="text-2xl">💰</span>
                                <div>
                                    <h4 class="font-bold text-lg mb-1">เพิ่มยอดขายได้จริง</h4>
                                    <p class="text-white/80">AI ช่วยปิดการขาย 24/7 ไม่มีวันหยุด ไม่เหนื่อย</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white/5 rounded-2xl p-4 border border-pink-500/30">
                            <div class="flex items-start gap-3">
                                <span class="text-2xl">⏰</span>
                                <div>
                                    <h4 class="font-bold text-lg mb-1">ประหยัดเวลาและแรงงาน</h4>
                                    <p class="text-white/80">ทำงานซ้ำๆ ให้ AI ทำ มนุษย์ทำงานสำคัญกว่า</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Why Rush to Join -->
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <h3 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
                        <span class="text-4xl">⏳</span>
                        <span>ทำไมต้องรีบเข้ามา?</span>
                    </h3>
                    <div class="space-y-4 text-white">
                        <div class="bg-gradient-to-r from-red-900/50 to-orange-900/50 rounded-2xl p-5 border border-red-500/50">
                            <div class="flex items-start gap-3 mb-3">
                                <span class="text-3xl">⚠️</span>
                                <div>
                                    <h4 class="font-bold text-xl mb-2 text-yellow-300">อันตราย!</h4>
                                    <p class="text-white/90 text-lg leading-relaxed">
                                        แพลตฟอร์มต่างชาติกำลังเข้ามาครองตลาดไทย หากเราไม่รีบสนับสนุนคนไทย
                                        <strong class="text-red-300">เงินในประเทศจะหลั่งไหลออกไปต่างประเทศ!</strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-emerald-900/50 to-teal-900/50 rounded-2xl p-5 border border-emerald-500/50">
                            <div class="flex items-start gap-3">
                                <span class="text-3xl">🏆</span>
                                <div>
                                    <h4 class="font-bold text-xl mb-2 text-emerald-300">โอกาสทอง!</h4>
                                    <p class="text-white/90 text-lg leading-relaxed">
                                        ผู้เข้ามาก่อนจะได้เปรียบ! ตลาด MLM + AI ในไทยยังไม่อิ่มตัว
                                        <strong class="text-emerald-300">ใครเริ่มก่อนชนะก่อน</strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-blue-900/50 to-indigo-900/50 rounded-2xl p-5 border border-blue-500/50">
                            <div class="flex items-start gap-3">
                                <span class="text-3xl">📈</span>
                                <div>
                                    <h4 class="font-bold text-xl mb-2 text-blue-300">เติบโตเร็ว!</h4>
                                    <p class="text-white/90 text-lg leading-relaxed">
                                        ตลาด AI ในไทยเติบโต <strong class="text-blue-300">300% ต่อปี</strong>
                                        อย่ารอจนสาย คู่แข่งกำลังเคลื่อนไหว!
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-3xl p-8 text-center">
                <p class="text-3xl text-white font-black mb-3 flex items-center justify-center gap-3">
                    <span>🔥</span>
                    <span>ธุรกิจที่ไม่ใช้ AI = ธุรกิจที่กำลังจะตาย</span>
                    <span>🔥</span>
                </p>
                <p class="text-white/90 text-xl">
                    ไทยพร๊อม คือคำตอบสำหรับธุรกิจยุคใหม่ที่พร้อมเติบโตไปกับ AI
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 6: User Management & Permissions -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-emerald-900 via-teal-900 to-cyan-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                👥 ระบบจัดการผู้ใช้งาน
            </h2>

            <div class="grid grid-cols-3 gap-6 mb-8">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6">
                    <div class="text-5xl mb-3 text-center">👨‍💼</div>
                    <h3 class="text-2xl font-bold text-white mb-3 text-center">Admin</h3>
                    <ul class="space-y-2 text-white/80">
                        <li>• จัดการระบบทั้งหมด</li>
                        <li>• ตั้งค่าคอนฟิก</li>
                        <li>• ดูรายงานทั้งหมด</li>
                        <li>• จัดการผู้ใช้</li>
                    </ul>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6">
                    <div class="text-5xl mb-3 text-center">🏪</div>
                    <h3 class="text-2xl font-bold text-white mb-3 text-center">Vendor</h3>
                    <ul class="space-y-2 text-white/80">
                        <li>• จัดการร้านค้า</li>
                        <li>• เพิ่ม-ลบสินค้า</li>
                        <li>• ดูรายงานขาย</li>
                        <li>• จัดการออเดอร์</li>
                    </ul>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6">
                    <div class="text-5xl mb-3 text-center">🎯</div>
                    <h3 class="text-2xl font-bold text-white mb-3 text-center">Affiliate</h3>
                    <ul class="space-y-2 text-white/80">
                        <li>• แชร์ลิงก์</li>
                        <li>• ดูคอมมิชชั่น</li>
                        <li>• จัดการทีม</li>
                        <li>• ถอนเงิน</li>
                    </ul>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                <h3 class="text-3xl font-bold text-white mb-6 text-center">🔐 ระบบสิทธิ์ที่ทรงพลัง (RBAC)</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-white font-bold text-xl mb-3">Role-Based Access Control</h4>
                        <ul class="space-y-2 text-white/80">
                            <li>• กำหนดสิทธิ์ตาม Role</li>
                            <li>• สร้าง Role ใหม่ได้ไม่จำกัด</li>
                            <li>• กำหนด Permission แบบละเอียด</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-bold text-xl mb-3">Security Features</h4>
                        <ul class="space-y-2 text-white/80">
                            <li>• Two-Factor Authentication (2FA)</li>
                            <li>• Activity Logging</li>
                            <li>• IP Whitelist/Blacklist</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 7: KYC & Document Verification -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-orange-900 via-red-900 to-pink-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🔍 ระบบ KYC และยืนยันตัวตน
            </h2>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <div class="text-6xl mb-4 text-center">📸</div>
                    <h3 class="text-3xl font-bold text-white mb-4 text-center">Document Verification</h3>
                    <ul class="space-y-3 text-white text-lg">
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>ถ่ายรูปบัตรประชาชน</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>ถ่ายรูปคู่กับบัตร (Selfie)</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>OCR อ่านข้อมูลอัตโนมัติ</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>ตรวจสอบความถูกต้อง</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <div class="text-6xl mb-4 text-center">✅</div>
                    <h3 class="text-3xl font-bold text-white mb-4 text-center">Bank Account Verification</h3>
                    <ul class="space-y-3 text-white text-lg">
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>ยืนยันบัญชีธนาคาร</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>ตรวจสอบชื่อบัญชี</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>ป้องกันการโกง</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>ถอนเงินปลอดภัย</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="bg-gradient-to-r from-red-600 to-orange-600 rounded-3xl p-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-3xl font-bold text-white mb-3">
                            🛡️ ความปลอดภัยระดับธนาคาร
                        </h4>
                        <p class="text-white/90 text-xl">
                            ป้องกันการฉ้อโกง และสร้างความเชื่อมั่นให้กับลูกค้า
                        </p>
                    </div>
                    <div class="text-7xl">🔒</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 8: Payment & Wallet System -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-green-900 via-emerald-900 to-teal-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                💰 ระบบกระเป๋าเงินและการเงิน
            </h2>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <div class="text-6xl mb-4">👛</div>
                    <h3 class="text-3xl font-bold text-white mb-4">Digital Wallet</h3>
                    <ul class="space-y-3 text-white text-lg">
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>กระเป๋าเงินสำหรับแต่ละคน</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>ติดตามยอดเงินแบบเรียลไทม์</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>ประวัติธุรกรรมละเอียด</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>แยกประเภทเงิน (คอม, โบนัส)</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <div class="text-6xl mb-4">💳</div>
                    <h3 class="text-3xl font-bold text-white mb-4">Payment Gateway</h3>
                    <ul class="space-y-3 text-white text-lg">
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>QR Code Payment</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>Bank Transfer</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>Credit/Debit Card</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>E-Wallet Integration</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6">
                <div class="bg-gradient-to-br from-yellow-600 to-orange-600 rounded-2xl p-6 text-center">
                    <div class="text-5xl mb-2">🏦</div>
                    <h4 class="text-white font-bold text-xl mb-2">ถอนเงินอัตโนมัติ</h4>
                    <p class="text-white/90">โอนเข้าบัญชีทันที</p>
                </div>

                <div class="bg-gradient-to-br from-green-600 to-emerald-600 rounded-2xl p-6 text-center">
                    <div class="text-5xl mb-2">📊</div>
                    <h4 class="text-white font-bold text-xl mb-2">รายงานการเงิน</h4>
                    <p class="text-white/90">ครบถ้วนละเอียด</p>
                </div>

                <div class="bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl p-6 text-center">
                    <div class="text-5xl mb-2">🔐</div>
                    <h4 class="text-white font-bold text-xl mb-2">ปลอดภัย 100%</h4>
                    <p class="text-white/90">เข้ารหัสทุกธุรกรรม</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 9: Dashboard & Analytics -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-indigo-900 via-blue-900 to-cyan-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                📊 Dashboard & Analytics
            </h2>

            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8 mb-8">
                <h3 class="text-3xl font-bold text-white mb-6 text-center">Real-time Analytics</h3>
                <div class="grid grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="text-5xl mb-3">📈</div>
                        <h4 class="text-white font-bold text-lg mb-2">Sales Analytics</h4>
                        <p class="text-white/70">วิเคราะห์ยอดขาย</p>
                    </div>
                    <div class="text-center">
                        <div class="text-5xl mb-3">👥</div>
                        <h4 class="text-white font-bold text-lg mb-2">User Insights</h4>
                        <p class="text-white/70">พฤติกรรมผู้ใช้</p>
                    </div>
                    <div class="text-center">
                        <div class="text-5xl mb-3">💹</div>
                        <h4 class="text-white font-bold text-lg mb-2">Revenue Reports</h4>
                        <p class="text-white/70">รายงานรายได้</p>
                    </div>
                    <div class="text-center">
                        <div class="text-5xl mb-3">🎯</div>
                        <h4 class="text-white font-bold text-lg mb-2">Performance</h4>
                        <p class="text-white/70">วัดผลประสิทธิภาพ</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <h3 class="text-2xl font-bold text-white mb-4">📉 Customizable Charts</h3>
                    <ul class="space-y-2 text-white/80">
                        <li>• กราฟแท่ง, กราฟเส้น, กราฟวงกลม</li>
                        <li>• เลือกช่วงเวลาได้ตามต้องการ</li>
                        <li>• Export เป็น PDF/Excel</li>
                        <li>• แสดงผลแบบ Interactive</li>
                    </ul>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <h3 class="text-2xl font-bold text-white mb-4">🎨 Beautiful UI/UX</h3>
                    <ul class="space-y-2 text-white/80">
                        <li>• ออกแบบด้วย Tailwind CSS</li>
                        <li>• Responsive ทุกอุปกรณ์</li>
                        <li>• Dark/Light Mode</li>
                        <li>• ใช้งานง่าย เข้าใจง่าย</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 10: Multi-Language & Localization -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-purple-900 via-pink-900 to-red-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🌍 ระบบหลายภาษา
            </h2>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <div class="text-6xl mb-4 text-center">🇹🇭</div>
                    <h3 class="text-3xl font-bold text-white mb-4 text-center">ภาษาไทย (Default)</h3>
                    <p class="text-white/80 text-lg text-center mb-4">
                        ภาษาหลักของระบบ เหมาะกับตลาดไทย
                    </p>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <div class="text-6xl mb-4 text-center">🇬🇧</div>
                    <h3 class="text-3xl font-bold text-white mb-4 text-center">English</h3>
                    <p class="text-white/80 text-lg text-center mb-4">
                        รองรับนักท่องเที่ยวและชาวต่างชาติ
                    </p>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                <h3 class="text-3xl font-bold text-white mb-6 text-center">✨ คุณสมบัติ</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-white font-bold text-xl mb-3 flex items-center gap-2">
                            <span class="text-emerald-400">✓</span>
                            <span>เพิ่มภาษาใหม่ได้ง่าย</span>
                        </h4>
                        <p class="text-white/70 ml-8">
                            เพียงแค่เพิ่มไฟล์ภาษา ไม่ต้องแก้โค้ด
                        </p>
                    </div>
                    <div>
                        <h4 class="text-white font-bold text-xl mb-3 flex items-center gap-2">
                            <span class="text-emerald-400">✓</span>
                            <span>สลับภาษาได้ทันที</span>
                        </h4>
                        <p class="text-white/70 ml-8">
                            ผู้ใช้เลือกภาษาได้เองตามต้องการ
                        </p>
                    </div>
                    <div>
                        <h4 class="text-white font-bold text-xl mb-3 flex items-center gap-2">
                            <span class="text-emerald-400">✓</span>
                            <span>แปลอัตโนมัติ</span>
                        </h4>
                        <p class="text-white/70 ml-8">
                            ใช้ Google Translate API ช่วยแปล
                        </p>
                    </div>
                    <div>
                        <h4 class="text-white font-bold text-xl mb-3 flex items-center gap-2">
                            <span class="text-emerald-400">✓</span>
                            <span>จัดการแบบ Admin</span>
                        </h4>
                        <p class="text-white/70 ml-8">
                            แก้ไขคำแปลได้จาก Admin Panel
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-8 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-3xl p-6 text-center">
                <p class="text-2xl text-white font-bold">
                    🌐 พร้อมขยายสู่ตลาดต่างประเทศได้ทันที
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 11: Security & Performance -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-red-900 via-orange-900 to-yellow-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🔐 ความปลอดภัยและประสิทธิภาพ
            </h2>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <div class="text-6xl mb-4">🛡️</div>
                    <h3 class="text-3xl font-bold text-white mb-4">Security</h3>
                    <ul class="space-y-3 text-white text-lg">
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>SSL/TLS Encryption</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>CSRF Protection</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>SQL Injection Prevention</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>XSS Protection</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>Rate Limiting</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <div class="text-6xl mb-4">⚡</div>
                    <h3 class="text-3xl font-bold text-white mb-4">Performance</h3>
                    <ul class="space-y-3 text-white text-lg">
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>Redis Caching</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>Database Query Optimization</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>Lazy Loading Images</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>CDN Integration</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span>
                            <span>Asset Minification</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6">
                <div class="bg-gradient-to-br from-green-600 to-emerald-600 rounded-2xl p-6 text-center">
                    <div class="text-5xl mb-2">🚀</div>
                    <h4 class="text-white font-bold text-xl mb-2">Page Load</h4>
                    <p class="text-3xl font-black text-white mb-1">&lt; 2s</p>
                    <p class="text-white/80 text-sm">เร็วเหมือนสายฟ้าแลบ</p>
                </div>

                <div class="bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl p-6 text-center">
                    <div class="text-5xl mb-2">📈</div>
                    <h4 class="text-white font-bold text-xl mb-2">Uptime</h4>
                    <p class="text-3xl font-black text-white mb-1">99.9%</p>
                    <p class="text-white/80 text-sm">เสถียรตลอดเวลา</p>
                </div>

                <div class="bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl p-6 text-center">
                    <div class="text-5xl mb-2">👥</div>
                    <h4 class="text-white font-bold text-xl mb-2">Concurrent Users</h4>
                    <p class="text-3xl font-black text-white mb-1">10,000+</p>
                    <p class="text-white/80 text-sm">รองรับผู้ใช้พร้อมกัน</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 12: Mobile Responsive -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-blue-900 via-indigo-900 to-purple-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                📱 Mobile First Design
            </h2>

            <div class="grid grid-cols-3 gap-8 mb-8">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 text-center">
                    <div class="text-6xl mb-4">📱</div>
                    <h3 class="text-2xl font-bold text-white mb-3">Smartphone</h3>
                    <p class="text-white/80">iPhone, Android</p>
                    <p class="text-emerald-400 font-bold mt-2">100% Compatible</p>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 text-center">
                    <div class="text-6xl mb-4">💻</div>
                    <h3 class="text-2xl font-bold text-white mb-3">Tablet</h3>
                    <p class="text-white/80">iPad, Galaxy Tab</p>
                    <p class="text-emerald-400 font-bold mt-2">100% Compatible</p>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 text-center">
                    <div class="text-6xl mb-4">🖥️</div>
                    <h3 class="text-2xl font-bold text-white mb-3">Desktop</h3>
                    <p class="text-white/80">Windows, Mac, Linux</p>
                    <p class="text-emerald-400 font-bold mt-2">100% Compatible</p>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                <h3 class="text-3xl font-bold text-white mb-6 text-center">✨ Responsive Features</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div class="flex items-start gap-4">
                        <div class="text-4xl">📐</div>
                        <div>
                            <h4 class="text-white font-bold text-xl mb-2">Flexible Layout</h4>
                            <p class="text-white/70">ปรับขนาดอัตโนมัติตามหน้าจอ</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="text-4xl">👆</div>
                        <div>
                            <h4 class="text-white font-bold text-xl mb-2">Touch Optimized</h4>
                            <p class="text-white/70">ปุ่มใหญ่ใช้งานง่ายบนมือถือ</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="text-4xl">🎨</div>
                        <div>
                            <h4 class="text-white font-bold text-xl mb-2">Adaptive Images</h4>
                            <p class="text-white/70">รูปภาพปรับตามขนาดหน้าจอ</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="text-4xl">⚡</div>
                        <div>
                            <h4 class="text-white font-bold text-xl mb-2">Fast Loading</h4>
                            <p class="text-white/70">โหลดเร็วแม้สัญญาณอินเทอร์เน็ตช้า</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 bg-gradient-to-r from-pink-600 to-purple-600 rounded-3xl p-6 text-center">
                <p class="text-2xl text-white font-bold">
                    📊 มากกว่า 70% ของผู้ใช้เข้าผ่านมือถือ - เราพร้อมแล้ว!
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 13: Technology Stack -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-slate-900 via-gray-900 to-slate-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                ⚙️ Technology Stack
            </h2>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <h3 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
                        <span>🔧</span>
                        <span>Backend</span>
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                            <span class="text-white font-semibold">PHP</span>
                            <span class="text-emerald-400">8.1+</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                            <span class="text-white font-semibold">Laravel</span>
                            <span class="text-emerald-400">11.x</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                            <span class="text-white font-semibold">MySQL</span>
                            <span class="text-emerald-400">8.0+</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                            <span class="text-white font-semibold">Redis</span>
                            <span class="text-emerald-400">7.0+</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                    <h3 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
                        <span>🎨</span>
                        <span>Frontend</span>
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                            <span class="text-white font-semibold">Tailwind CSS</span>
                            <span class="text-emerald-400">3.x</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                            <span class="text-white font-semibold">Alpine.js</span>
                            <span class="text-emerald-400">3.x</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                            <span class="text-white font-semibold">Livewire</span>
                            <span class="text-emerald-400">3.x</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                            <span class="text-white font-semibold">Chart.js</span>
                            <span class="text-emerald-400">4.x</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8">
                <h3 class="text-3xl font-bold text-white mb-6 text-center">🔌 Integrations</h3>
                <div class="grid grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-white/5 rounded-lg">
                        <div class="text-4xl mb-2">💬</div>
                        <p class="text-white font-semibold">LINE API</p>
                    </div>
                    <div class="text-center p-4 bg-white/5 rounded-lg">
                        <div class="text-4xl mb-2">💳</div>
                        <p class="text-white font-semibold">Payment APIs</p>
                    </div>
                    <div class="text-center p-4 bg-white/5 rounded-lg">
                        <div class="text-4xl mb-2">📧</div>
                        <p class="text-white font-semibold">Email SMTP</p>
                    </div>
                    <div class="text-center p-4 bg-white/5 rounded-lg">
                        <div class="text-4xl mb-2">📨</div>
                        <p class="text-white font-semibold">SMS Gateway</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 14: Business Model & ROI -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-yellow-900 via-orange-900 to-red-900 p-12">
        <div class="max-w-6xl w-full">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                💼 โมเดลธุรกิจและผลตอบแทน
            </h2>

            <div class="grid grid-cols-3 gap-6 mb-8">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 text-center">
                    <div class="text-5xl mb-3">🎯</div>
                    <h3 class="text-2xl font-bold text-white mb-2">Direct Sales</h3>
                    <p class="text-4xl font-black text-emerald-400 mb-2">10-30%</p>
                    <p class="text-white/80">คอมมิชชั่นจากการขายตรง</p>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 text-center">
                    <div class="text-5xl mb-3">🔄</div>
                    <h3 class="text-2xl font-bold text-white mb-2">Team Commission</h3>
                    <p class="text-4xl font-black text-emerald-400 mb-2">5-15%</p>
                    <p class="text-white/80">คอมมิชชั่นจากทีม</p>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 text-center">
                    <div class="text-5xl mb-3">🎁</div>
                    <h3 class="text-2xl font-bold text-white mb-2">Bonus & Incentive</h3>
                    <p class="text-4xl font-black text-emerald-400 mb-2">Extra</p>
                    <p class="text-white/80">โบนัสพิเศษ</p>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8 mb-8">
                <h3 class="text-3xl font-bold text-white mb-6 text-center">💰 ตัวอย่างรายได้ (ต่อเดือน)</h3>
                <div class="grid grid-cols-3 gap-6">
                    <div class="text-center p-6 bg-gradient-to-br from-green-600 to-emerald-600 rounded-2xl">
                        <h4 class="text-white font-bold text-xl mb-2">Beginner</h4>
                        <p class="text-4xl font-black text-white mb-2">฿5,000 - ฿15,000</p>
                        <p class="text-white/90 text-sm">ทำ Part-time</p>
                    </div>

                    <div class="text-center p-6 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl">
                        <h4 class="text-white font-bold text-xl mb-2">Intermediate</h4>
                        <p class="text-4xl font-black text-white mb-2">฿15,000 - ฿50,000</p>
                        <p class="text-white/90 text-sm">มีทีมเล็กๆ</p>
                    </div>

                    <div class="text-center p-6 bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl">
                        <h4 class="text-white font-bold text-xl mb-2">Professional</h4>
                        <p class="text-4xl font-black text-white mb-2">฿50,000+</p>
                        <p class="text-white/90 text-sm">ทำเต็มเวลา มีทีมใหญ่</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-yellow-600 to-orange-600 rounded-3xl p-6 text-center">
                <p class="text-2xl text-white font-bold">
                    🚀 ลงทุนน้อย ผลตอบแทนสูง - เริ่มต้นได้ทันทีวันนี้!
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 15: Call to Action / Contact -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 p-12">
        <div class="max-w-6xl w-full text-center">
            <div class="mb-8">
                <img src="{{ asset('images/logo.svg') }}" alt="ไทยพร๊อม" width="128" height="128" class="w-32 h-32 mx-auto filter drop-shadow-2xl mb-8">
            </div>

            <h2 class="text-6xl md:text-7xl font-black text-white mb-6">
                พร้อมเริ่มต้นแล้วหรือยัง?
            </h2>

            <p class="text-3xl text-indigo-200 mb-12 max-w-4xl mx-auto leading-relaxed">
                เข้าร่วมกับแพลตฟอร์มที่สมบูรณ์แบบที่สุด
                <br>
                <span class="text-white font-bold">ไทยพร๊อม - ThaiPrompt Affiliate</span>
            </p>

            <div class="grid grid-cols-2 gap-8 mb-12 max-w-4xl mx-auto">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-8">
                    <div class="text-5xl mb-4">📧</div>
                    <h3 class="text-2xl font-bold text-white mb-3">ติดต่อเรา</h3>
                    <p class="text-white/80 text-lg">support@thaiprompt.com</p>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-8">
                    <div class="text-5xl mb-4">💬</div>
                    <h3 class="text-2xl font-bold text-white mb-3">LINE Official</h3>
                    <p class="text-white/80 text-lg">@thaiprompt</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-6 justify-center items-center mb-8">
                <a href="{{ route('register') }}" class="group relative inline-flex items-center gap-3 px-12 py-6 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-2xl font-bold rounded-2xl shadow-2xl hover:shadow-emerald-500/50 transition-all duration-300 transform hover:scale-110">
                    <span>เริ่มต้นฟรีวันนี้</span>
                    <svg class="w-7 h-7 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>

                <a href="{{ route('platform.wiki') }}" class="inline-flex items-center gap-3 px-12 py-6 bg-white/10 backdrop-blur-sm border-2 border-white/30 text-white text-2xl font-bold rounded-2xl hover:bg-white/20 transition-all duration-300 transform hover:scale-110">
                    <span>อ่านข้อมูลเพิ่มเติม</span>
                </a>
            </div>

            <div class="text-white/60 text-lg">
                <p>ไม่เสียค่าใช้จ่าย • ไม่ต้องใช้บัตรเครดิต • เริ่มได้ทันที</p>
            </div>

            <div class="mt-12 pt-8 border-t border-white/20">
                <p class="text-white/40">
                    © 2025 ไทยพร๊อม (ThaiPrompt). All rights reserved.
                </p>
            </div>
        </div>
    </div>
</div>
