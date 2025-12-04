@php
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate Pro');
    $siteSettings = \App\Models\SiteSetting::getSetting();
    $systemLogo = $siteSettings->logo ?? null;
@endphp

<!-- Slide 1: Title / Cover -->
<div class="slide active">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-indigo-900/90 via-purple-900/80 to-pink-900/90 p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Animated background particles -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-20 left-20 w-72 h-72 bg-blue-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute top-40 right-20 w-72 h-72 bg-purple-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
            <div class="absolute bottom-20 left-1/2 w-72 h-72 bg-pink-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-4000"></div>
        </div>

        <div class="text-center text-white max-w-5xl relative z-10">
            <!-- Logo -->
            <div class="mb-8 animate-float">
                @if($systemLogo)
                    <img src="{{ asset('storage/' . $systemLogo) }}" alt="{{ $appName }}" class="w-48 h-48 mx-auto filter drop-shadow-2xl object-contain">
                @else
                    <img src="{{ asset('images/logo.svg') }}" alt="{{ $appName }}" width="200" height="200" class="w-48 h-48 mx-auto filter drop-shadow-2xl object-contain">
                @endif
            </div>

            <h1 class="text-6xl md:text-7xl lg:text-8xl font-black mb-6 leading-tight animate-fade-in-up">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-pink-300 to-purple-300">
                    {{ $appName }}
                </span>
            </h1>

            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-3xl p-8 mb-8 shadow-2xl">
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold mb-4">
                    แพลตฟอร์ม All-in-One ระดับ Enterprise
                </h2>
                <p class="text-xl md:text-2xl text-indigo-200 leading-relaxed">
                    รวม 20+ ระบบครบวงจร • Affiliate • MLM • E-Commerce • AI Bot • Blockchain
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-4 md:gap-8 text-base md:text-lg">
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full border border-white/20">
                    <div class="w-3 h-3 bg-emerald-400 rounded-full animate-pulse"></div>
                    <span>Enterprise Ready</span>
                </div>
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full border border-white/20">
                    <div class="w-3 h-3 bg-emerald-400 rounded-full animate-pulse"></div>
                    <span>20+ ระบบครบวงจร</span>
                </div>
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full border border-white/20">
                    <div class="w-3 h-3 bg-emerald-400 rounded-full animate-pulse"></div>
                    <span>24/7 Support</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 2: ภาพรวมแพลตฟอร์ม -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-slate-900/90 via-indigo-900/80 to-slate-900/90 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background decoration -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image:
                linear-gradient(to right, rgba(99, 102, 241, 0.15) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(99, 102, 241, 0.15) 1px, transparent 1px);
                background-size: 40px 40px;"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-8 md:mb-12 text-center animate-fade-in">
                🌟 แพลตฟอร์มที่ครบครันที่สุด
            </h2>

            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-3xl p-6 md:p-8 mb-8 shadow-2xl">
                <p class="text-xl md:text-2xl text-white/90 leading-relaxed text-center">
                    <strong class="text-transparent bg-clip-text bg-gradient-to-r from-blue-300 to-purple-300">{{ $appName }}</strong>
                    คือแพลตฟอร์ม All-in-One ที่รวมทุกระบบไว้ในที่เดียว
                    <br class="hidden md:block">
                    ตอบโจทย์ทุกความต้องการของธุรกิจยุคใหม่ ไม่ว่าจะเป็น Affiliate, E-Commerce, AI หรือ Blockchain
                </p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                <!-- Card 1 -->
                <div class="bg-gradient-to-br from-blue-500/20 to-blue-600/10 backdrop-blur-lg border border-blue-300/30 rounded-2xl p-4 md:p-6 text-center transform hover:scale-105 transition-all">
                    <div class="text-4xl md:text-5xl mb-3">🎯</div>
                    <h3 class="text-lg md:text-xl font-bold text-white">Affiliate & MLM</h3>
                    <p class="text-white/70 text-sm mt-2">ระบบเครือข่ายไม่จำกัดชั้น</p>
                </div>

                <!-- Card 2 -->
                <div class="bg-gradient-to-br from-purple-500/20 to-purple-600/10 backdrop-blur-lg border border-purple-300/30 rounded-2xl p-4 md:p-6 text-center transform hover:scale-105 transition-all">
                    <div class="text-4xl md:text-5xl mb-3">🛒</div>
                    <h3 class="text-lg md:text-xl font-bold text-white">E-Commerce</h3>
                    <p class="text-white/70 text-sm mt-2">ร้านค้าออนไลน์ครบวงจร</p>
                </div>

                <!-- Card 3 -->
                <div class="bg-gradient-to-br from-pink-500/20 to-pink-600/10 backdrop-blur-lg border border-pink-300/30 rounded-2xl p-4 md:p-6 text-center transform hover:scale-105 transition-all">
                    <div class="text-4xl md:text-5xl mb-3">🤖</div>
                    <h3 class="text-lg md:text-xl font-bold text-white">AI & Automation</h3>
                    <p class="text-white/70 text-sm mt-2">ระบบอัจฉริยะอัตโนมัติ</p>
                </div>

                <!-- Card 4 -->
                <div class="bg-gradient-to-br from-amber-500/20 to-amber-600/10 backdrop-blur-lg border border-amber-300/30 rounded-2xl p-4 md:p-6 text-center transform hover:scale-105 transition-all">
                    <div class="text-4xl md:text-5xl mb-3">⛓️</div>
                    <h3 class="text-lg md:text-xl font-bold text-white">Blockchain</h3>
                    <p class="text-white/70 text-sm mt-2">TPIX Token & Web3</p>
                </div>
            </div>

            <div class="mt-8 text-center">
                <div class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-500/20 to-teal-500/20 backdrop-blur-md border border-emerald-300/30 rounded-full px-6 py-3 text-white font-semibold">
                    <span class="text-2xl">✨</span>
                    <span>และอีก 20+ ระบบ พร้อมใช้งานทันที</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 3: ระบบ MLM - ภาพรวมแผนการจ่าย -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-purple-900/90 via-pink-900/80 to-red-900/90 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-10 left-10 w-96 h-96 bg-purple-400 rounded-full mix-blend-screen filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-pink-400 rounded-full mix-blend-screen filter blur-3xl animate-pulse animation-delay-2000"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6 md:mb-8 text-center">
                🎯 ระบบ MLM & แผนการจ่าย
            </h2>

            <p class="text-xl text-white/80 text-center mb-8">
                ระบบ MLM ที่ยืดหยุ่นที่สุด รองรับทั้ง <strong class="text-pink-300">Unilevel</strong> และ <strong class="text-purple-300">Binary</strong>
            </p>

            <!-- Commission Types Overview -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <!-- Unilevel -->
                <div class="bg-gradient-to-br from-blue-500/30 to-blue-600/10 backdrop-blur-lg border border-blue-300/40 rounded-2xl p-4 text-center hover:scale-105 transition-all">
                    <div class="text-4xl mb-2">📊</div>
                    <h3 class="text-lg font-bold text-white">Unilevel</h3>
                    <p class="text-2xl font-black text-blue-300">10 ชั้น</p>
                    <p class="text-white/60 text-xs mt-1">10% → 1%</p>
                </div>

                <!-- Binary -->
                <div class="bg-gradient-to-br from-purple-500/30 to-purple-600/10 backdrop-blur-lg border border-purple-300/40 rounded-2xl p-4 text-center hover:scale-105 transition-all">
                    <div class="text-4xl mb-2">⚖️</div>
                    <h3 class="text-lg font-bold text-white">Binary</h3>
                    <p class="text-2xl font-black text-purple-300">฿100/คู่</p>
                    <p class="text-white/60 text-xs mt-1">50% matching</p>
                </div>

                <!-- Direct Referral -->
                <div class="bg-gradient-to-br from-emerald-500/30 to-emerald-600/10 backdrop-blur-lg border border-emerald-300/40 rounded-2xl p-4 text-center hover:scale-105 transition-all">
                    <div class="text-4xl mb-2">🤝</div>
                    <h3 class="text-lg font-bold text-white">แนะนำตรง</h3>
                    <p class="text-2xl font-black text-emerald-300">฿100</p>
                    <p class="text-white/60 text-xs mt-1">หรือ 5% ต่อออเดอร์</p>
                </div>

                <!-- Pool Bonus -->
                <div class="bg-gradient-to-br from-amber-500/30 to-amber-600/10 backdrop-blur-lg border border-amber-300/40 rounded-2xl p-4 text-center hover:scale-105 transition-all">
                    <div class="text-4xl mb-2">🏆</div>
                    <h3 class="text-lg font-bold text-white">Pool Bonus</h3>
                    <p class="text-2xl font-black text-amber-300">5%</p>
                    <p class="text-white/60 text-xs mt-1">แบ่งตาม Rank</p>
                </div>
            </div>

            <!-- Unilevel Commission Detail -->
            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 mb-6 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <span class="text-2xl">📊</span>
                    <span>Unilevel Commission - 10 ชั้น</span>
                </h3>
                <div class="grid grid-cols-5 md:grid-cols-10 gap-2 text-center">
                    <div class="bg-blue-500/30 rounded-lg p-2">
                        <div class="text-xs text-white/60">ชั้น 1</div>
                        <div class="text-lg font-bold text-blue-300">10%</div>
                    </div>
                    <div class="bg-blue-500/25 rounded-lg p-2">
                        <div class="text-xs text-white/60">ชั้น 2</div>
                        <div class="text-lg font-bold text-blue-300">5%</div>
                    </div>
                    <div class="bg-blue-500/20 rounded-lg p-2">
                        <div class="text-xs text-white/60">ชั้น 3</div>
                        <div class="text-lg font-bold text-blue-300">3%</div>
                    </div>
                    <div class="bg-blue-500/18 rounded-lg p-2">
                        <div class="text-xs text-white/60">ชั้น 4</div>
                        <div class="text-lg font-bold text-blue-300">2%</div>
                    </div>
                    <div class="bg-blue-500/15 rounded-lg p-2">
                        <div class="text-xs text-white/60">ชั้น 5</div>
                        <div class="text-lg font-bold text-blue-300">1%</div>
                    </div>
                    <div class="bg-blue-500/13 rounded-lg p-2">
                        <div class="text-xs text-white/60">ชั้น 6</div>
                        <div class="text-lg font-bold text-blue-300">1%</div>
                    </div>
                    <div class="bg-blue-500/11 rounded-lg p-2">
                        <div class="text-xs text-white/60">ชั้น 7</div>
                        <div class="text-lg font-bold text-blue-300">0.5%</div>
                    </div>
                    <div class="bg-blue-500/10 rounded-lg p-2">
                        <div class="text-xs text-white/60">ชั้น 8</div>
                        <div class="text-lg font-bold text-blue-300">0.5%</div>
                    </div>
                    <div class="bg-blue-500/8 rounded-lg p-2">
                        <div class="text-xs text-white/60">ชั้น 9</div>
                        <div class="text-lg font-bold text-blue-300">0.25%</div>
                    </div>
                    <div class="bg-blue-500/5 rounded-lg p-2">
                        <div class="text-xs text-white/60">ชั้น 10</div>
                        <div class="text-lg font-bold text-blue-300">0.25%</div>
                    </div>
                </div>
                <p class="text-white/60 text-sm mt-3 text-center">
                    💡 รวมค่าคอมมิชชั่น <strong class="text-white">23.5%</strong> ต่อ PV • รองรับ Roll-up และ Compression
                </p>
            </div>

            <div class="bg-gradient-to-r from-yellow-500/20 to-orange-500/20 backdrop-blur-md border border-yellow-300/30 rounded-2xl p-4 text-center shadow-xl">
                <p class="text-lg md:text-xl text-white font-bold">
                    ⚙️ ปรับแต่งทุกค่าได้จาก Admin Panel • คำนวณอัตโนมัติ Real-time
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 3.1: ระบบ Rank 8 ระดับ -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-indigo-900/90 via-violet-900/80 to-purple-900/90 p-6 md:p-10 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-20 right-20 w-80 h-80 bg-violet-400 rounded-full mix-blend-screen filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 left-20 w-80 h-80 bg-indigo-400 rounded-full mix-blend-screen filter blur-3xl animate-pulse animation-delay-2000"></div>
        </div>

        <div class="max-w-7xl w-full relative z-10">
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-black text-white mb-4 md:mb-6 text-center">
                👑 ระบบ Rank 8 ระดับ
            </h2>
            <p class="text-lg text-white/70 text-center mb-6">
                ไต่ระดับจาก <span class="text-amber-400">สำริด</span> สู่ <span class="text-pink-400">ตำนาน</span> พร้อมโบนัสสูงสุด <strong class="text-yellow-300">1,000,000 บาท!</strong>
            </p>

            <!-- Rank Cards - 8 ระดับ -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-6">
                <!-- Bronze -->
                <div class="bg-gradient-to-br from-amber-700/40 to-amber-800/20 backdrop-blur-lg border border-amber-500/40 rounded-xl p-3 md:p-4 text-center hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-1">🥉</div>
                    <h4 class="text-base md:text-lg font-bold text-white">Bronze</h4>
                    <p class="text-amber-300 text-xs">สำริด</p>
                    <div class="mt-2 space-y-1 text-xs">
                        <div class="text-white/80">Commission <span class="text-amber-300 font-bold">5%</span></div>
                        <div class="text-white/60">3 ชั้น</div>
                        <div class="text-emerald-300">ระดับเริ่มต้น</div>
                    </div>
                </div>

                <!-- Silver -->
                <div class="bg-gradient-to-br from-gray-400/40 to-gray-500/20 backdrop-blur-lg border border-gray-300/40 rounded-xl p-3 md:p-4 text-center hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-1">🥈</div>
                    <h4 class="text-base md:text-lg font-bold text-white">Silver</h4>
                    <p class="text-gray-300 text-xs">เงิน</p>
                    <div class="mt-2 space-y-1 text-xs">
                        <div class="text-white/80">Commission <span class="text-gray-200 font-bold">7.5%</span></div>
                        <div class="text-white/60">4 ชั้น</div>
                        <div class="text-yellow-300">โบนัส ฿500</div>
                    </div>
                </div>

                <!-- Gold -->
                <div class="bg-gradient-to-br from-yellow-500/40 to-yellow-600/20 backdrop-blur-lg border border-yellow-400/40 rounded-xl p-3 md:p-4 text-center hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-1">🥇</div>
                    <h4 class="text-base md:text-lg font-bold text-white">Gold</h4>
                    <p class="text-yellow-300 text-xs">ทอง</p>
                    <div class="mt-2 space-y-1 text-xs">
                        <div class="text-white/80">Commission <span class="text-yellow-300 font-bold">10%</span></div>
                        <div class="text-white/60">5 ชั้น</div>
                        <div class="text-yellow-300">โบนัส ฿2,000</div>
                    </div>
                </div>

                <!-- Platinum -->
                <div class="bg-gradient-to-br from-slate-400/40 to-slate-500/20 backdrop-blur-lg border border-slate-300/40 rounded-xl p-3 md:p-4 text-center hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-1">💎</div>
                    <h4 class="text-base md:text-lg font-bold text-white">Platinum</h4>
                    <p class="text-slate-200 text-xs">แพลตินัม</p>
                    <div class="mt-2 space-y-1 text-xs">
                        <div class="text-white/80">Commission <span class="text-slate-200 font-bold">15%</span></div>
                        <div class="text-white/60">6 ชั้น</div>
                        <div class="text-yellow-300">โบนัส ฿10,000</div>
                    </div>
                </div>

                <!-- Diamond -->
                <div class="bg-gradient-to-br from-cyan-400/40 to-cyan-500/20 backdrop-blur-lg border border-cyan-300/40 rounded-xl p-3 md:p-4 text-center hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-1">💠</div>
                    <h4 class="text-base md:text-lg font-bold text-white">Diamond</h4>
                    <p class="text-cyan-300 text-xs">เพชร</p>
                    <div class="mt-2 space-y-1 text-xs">
                        <div class="text-white/80">Commission <span class="text-cyan-300 font-bold">20%</span></div>
                        <div class="text-white/60">7 ชั้น</div>
                        <div class="text-yellow-300">โบนัส ฿50,000</div>
                    </div>
                </div>

                <!-- Crown -->
                <div class="bg-gradient-to-br from-amber-500/40 to-orange-500/20 backdrop-blur-lg border border-amber-400/40 rounded-xl p-3 md:p-4 text-center hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-1">👑</div>
                    <h4 class="text-base md:text-lg font-bold text-white">Crown</h4>
                    <p class="text-amber-300 text-xs">มงกุฎ</p>
                    <div class="mt-2 space-y-1 text-xs">
                        <div class="text-white/80">Commission <span class="text-amber-300 font-bold">25%</span></div>
                        <div class="text-white/60">8 ชั้น</div>
                        <div class="text-yellow-300">โบนัส ฿100,000</div>
                    </div>
                </div>

                <!-- Royal -->
                <div class="bg-gradient-to-br from-violet-500/40 to-purple-500/20 backdrop-blur-lg border border-violet-400/40 rounded-xl p-3 md:p-4 text-center hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-1">🏆</div>
                    <h4 class="text-base md:text-lg font-bold text-white">Royal</h4>
                    <p class="text-violet-300 text-xs">รอยัล</p>
                    <div class="mt-2 space-y-1 text-xs">
                        <div class="text-white/80">Commission <span class="text-violet-300 font-bold">30%</span></div>
                        <div class="text-white/60">9 ชั้น</div>
                        <div class="text-yellow-300">โบนัส ฿300,000</div>
                    </div>
                </div>

                <!-- Legend -->
                <div class="bg-gradient-to-br from-pink-500/50 to-rose-500/30 backdrop-blur-lg border-2 border-pink-400/60 rounded-xl p-3 md:p-4 text-center hover:scale-105 transition-all relative overflow-hidden">
                    <div class="absolute top-0 right-0 bg-yellow-400 text-black text-[10px] font-bold px-2 py-0.5 rounded-bl-lg">TOP</div>
                    <div class="text-3xl md:text-4xl mb-1">🌟</div>
                    <h4 class="text-base md:text-lg font-bold text-white">Legend</h4>
                    <p class="text-pink-300 text-xs">ตำนาน</p>
                    <div class="mt-2 space-y-1 text-xs">
                        <div class="text-white/80">Commission <span class="text-pink-300 font-bold">35%</span></div>
                        <div class="text-white/60">10 ชั้น</div>
                        <div class="text-yellow-300 font-bold animate-pulse">โบนัส ฿1,000,000!</div>
                    </div>
                </div>
            </div>

            <!-- Rank Benefits Summary -->
            <div class="grid md:grid-cols-3 gap-4">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-4">
                    <h4 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                        <span>💵</span> โบนัสเลื่อนระดับ
                    </h4>
                    <p class="text-white/70 text-sm">รับโบนัสอัตโนมัติทันทีเมื่อเลื่อนระดับ ตั้งแต่ ฿500 ถึง ฿1,000,000</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-4">
                    <h4 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                        <span>📈</span> โบนัสรายเดือน
                    </h4>
                    <p class="text-white/70 text-sm">รับโบนัสรายเดือนตาม Rank สูงสุด ฿100,000/เดือน (Legend)</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-4">
                    <h4 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                        <span>🎁</span> สิทธิพิเศษ
                    </h4>
                    <p class="text-white/70 text-sm">รถยนต์, บ้าน, ท่องเที่ยว, ผู้ช่วยส่วนตัว และอื่นๆ</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 3.2: แพ็คเกจสมาชิก MLM -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-emerald-900/90 via-teal-900/80 to-cyan-900/90 p-6 md:p-10 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-20 left-20 w-80 h-80 bg-emerald-400 rounded-full mix-blend-screen filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-20 w-80 h-80 bg-teal-400 rounded-full mix-blend-screen filter blur-3xl animate-pulse animation-delay-2000"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-black text-white mb-4 md:mb-6 text-center">
                📦 แพ็คเกจสมาชิก MLM
            </h2>
            <p class="text-lg text-white/70 text-center mb-6">
                เลือกแพ็คเกจที่เหมาะกับคุณ • ยิ่งแพ็คเกจสูง ยิ่งได้สิทธิประโยชน์มาก
            </p>

            <!-- Package Cards - 5 แพ็คเกจ -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-4 mb-6">
                <!-- Bronze Package -->
                <div class="bg-gradient-to-br from-amber-700/30 to-amber-800/10 backdrop-blur-lg border border-amber-500/30 rounded-xl p-4 text-center hover:scale-105 transition-all">
                    <div class="text-3xl mb-2">🛡️</div>
                    <h4 class="text-lg font-bold text-white">Bronze</h4>
                    <p class="text-3xl font-black text-amber-300 my-2">฿990</p>
                    <p class="text-amber-200/70 text-xs mb-3">990 PV</p>
                    <ul class="text-left text-xs text-white/80 space-y-1">
                        <li>✓ เริ่มต้นธุรกิจ MLM</li>
                        <li>✓ ระบบ Back Office</li>
                        <li>✓ ถอนขั้นต่ำ ฿500</li>
                        <li>✓ ถอน 2 ครั้ง/เดือน</li>
                    </ul>
                </div>

                <!-- Silver Package -->
                <div class="bg-gradient-to-br from-gray-400/30 to-gray-500/10 backdrop-blur-lg border-2 border-blue-400/50 rounded-xl p-4 text-center hover:scale-105 transition-all relative">
                    <div class="absolute -top-2 left-1/2 -translate-x-1/2 bg-blue-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">Popular</div>
                    <div class="text-3xl mb-2">🏅</div>
                    <h4 class="text-lg font-bold text-white">Silver</h4>
                    <p class="text-3xl font-black text-gray-200 my-2">฿2,990</p>
                    <p class="text-gray-300/70 text-xs mb-3">2,990 PV</p>
                    <ul class="text-left text-xs text-white/80 space-y-1">
                        <li>✓ ทุกสิทธิ์ Bronze</li>
                        <li>✓ สินค้าพรีเมี่ยม</li>
                        <li>✓ โบนัสพิเศษ 5%</li>
                        <li>✓ ถอน 4 ครั้ง/เดือน</li>
                    </ul>
                </div>

                <!-- Gold Package -->
                <div class="bg-gradient-to-br from-yellow-500/30 to-yellow-600/10 backdrop-blur-lg border border-yellow-400/30 rounded-xl p-4 text-center hover:scale-105 transition-all relative">
                    <div class="absolute -top-2 left-1/2 -translate-x-1/2 bg-yellow-500 text-black text-[10px] font-bold px-2 py-0.5 rounded-full">Best Value</div>
                    <div class="text-3xl mb-2">⭐</div>
                    <h4 class="text-lg font-bold text-white">Gold</h4>
                    <p class="text-3xl font-black text-yellow-300 my-2">฿5,990</p>
                    <p class="text-yellow-200/70 text-xs mb-3">5,990 PV</p>
                    <ul class="text-left text-xs text-white/80 space-y-1">
                        <li>✓ ทุกสิทธิ์ Silver</li>
                        <li>✓ เข้าร่วมสัมมนา</li>
                        <li>✓ โบนัสพิเศษ 10%</li>
                        <li>✓ ถอน 8 ครั้ง/เดือน</li>
                    </ul>
                </div>

                <!-- Diamond Package -->
                <div class="bg-gradient-to-br from-cyan-400/30 to-cyan-500/10 backdrop-blur-lg border border-cyan-300/30 rounded-xl p-4 text-center hover:scale-105 transition-all relative">
                    <div class="absolute -top-2 left-1/2 -translate-x-1/2 bg-cyan-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">Elite</div>
                    <div class="text-3xl mb-2">💎</div>
                    <h4 class="text-lg font-bold text-white">Diamond</h4>
                    <p class="text-3xl font-black text-cyan-300 my-2">฿9,990</p>
                    <p class="text-cyan-200/70 text-xs mb-3">9,990 PV</p>
                    <ul class="text-left text-xs text-white/80 space-y-1">
                        <li>✓ ทุกสิทธิ์ Gold</li>
                        <li>✓ ทีมซัพพอร์ต VIP</li>
                        <li>✓ โบนัสพิเศษ 15%</li>
                        <li>✓ ถอนไม่จำกัด</li>
                    </ul>
                </div>

                <!-- Premier Package -->
                <div class="bg-gradient-to-br from-purple-500/40 to-violet-600/20 backdrop-blur-lg border-2 border-purple-400/50 rounded-xl p-4 text-center hover:scale-105 transition-all relative col-span-2 md:col-span-1">
                    <div class="absolute -top-2 left-1/2 -translate-x-1/2 bg-gradient-to-r from-purple-500 to-pink-500 text-white text-[10px] font-bold px-3 py-0.5 rounded-full">VIP</div>
                    <div class="text-3xl mb-2">👑</div>
                    <h4 class="text-lg font-bold text-white">Premier</h4>
                    <p class="text-3xl font-black text-purple-300 my-2">฿19,990</p>
                    <p class="text-purple-200/70 text-xs mb-3">19,990 PV</p>
                    <ul class="text-left text-xs text-white/80 space-y-1">
                        <li>✓ ทุกสิทธิ์ Diamond</li>
                        <li>✓ ระบบ AI อัตโนมัติ</li>
                        <li>✓ โบนัสพิเศษ 20%</li>
                        <li>✓ ไม่มีขั้นต่ำถอน</li>
                    </ul>
                </div>
            </div>

            <!-- Feature Comparison -->
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-4 md:p-6">
                <h4 class="text-xl font-bold text-white mb-4 text-center">🔄 เปรียบเทียบสิทธิประโยชน์</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs md:text-sm text-white/90">
                        <thead>
                            <tr class="border-b border-white/20">
                                <th class="text-left py-2 px-2">สิทธิประโยชน์</th>
                                <th class="text-center py-2 px-1">Bronze</th>
                                <th class="text-center py-2 px-1">Silver</th>
                                <th class="text-center py-2 px-1">Gold</th>
                                <th class="text-center py-2 px-1">Diamond</th>
                                <th class="text-center py-2 px-1">Premier</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-white/10">
                                <td class="py-2 px-2">โบนัสพิเศษ</td>
                                <td class="text-center">-</td>
                                <td class="text-center text-emerald-300">5%</td>
                                <td class="text-center text-emerald-300">10%</td>
                                <td class="text-center text-emerald-300">15%</td>
                                <td class="text-center text-emerald-300">20%</td>
                            </tr>
                            <tr class="border-b border-white/10">
                                <td class="py-2 px-2">ถอนต่อเดือน</td>
                                <td class="text-center">2 ครั้ง</td>
                                <td class="text-center">4 ครั้ง</td>
                                <td class="text-center">8 ครั้ง</td>
                                <td class="text-center text-emerald-300">ไม่จำกัด</td>
                                <td class="text-center text-emerald-300">ไม่จำกัด</td>
                            </tr>
                            <tr class="border-b border-white/10">
                                <td class="py-2 px-2">ขั้นต่ำถอน</td>
                                <td class="text-center">฿500</td>
                                <td class="text-center">฿300</td>
                                <td class="text-center">฿200</td>
                                <td class="text-center">฿100</td>
                                <td class="text-center text-emerald-300">ไม่มี</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-2">VIP Support</td>
                                <td class="text-center">-</td>
                                <td class="text-center">-</td>
                                <td class="text-center text-emerald-300">✓</td>
                                <td class="text-center text-emerald-300">✓</td>
                                <td class="text-center text-emerald-300">✓</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 3.3: เงื่อนไขการเลื่อนระดับ -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-rose-900/90 via-pink-900/80 to-fuchsia-900/90 p-6 md:p-10 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-20 right-20 w-80 h-80 bg-rose-400 rounded-full mix-blend-screen filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 left-20 w-80 h-80 bg-pink-400 rounded-full mix-blend-screen filter blur-3xl animate-pulse animation-delay-2000"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-black text-white mb-4 md:mb-6 text-center">
                📈 เงื่อนไขการเลื่อนระดับ
            </h2>
            <p class="text-lg text-white/70 text-center mb-6">
                เงื่อนไขชัดเจน • ตรวจสอบได้ Real-time • เลื่อนระดับอัตโนมัติ
            </p>

            <!-- Rank Requirements Table -->
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden mb-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs md:text-sm text-white">
                        <thead class="bg-white/10">
                            <tr>
                                <th class="text-left py-3 px-3 font-bold">Rank</th>
                                <th class="text-center py-3 px-2 font-bold">คะแนน (PV)</th>
                                <th class="text-center py-3 px-2 font-bold">แนะนำ</th>
                                <th class="text-center py-3 px-2 font-bold">ยอดขายส่วนตัว</th>
                                <th class="text-center py-3 px-2 font-bold">ยอดขายทีม</th>
                                <th class="text-center py-3 px-2 font-bold hidden md:table-cell">เงื่อนไขพิเศษ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-white/10 bg-amber-700/10">
                                <td class="py-2 px-3"><span class="text-lg mr-1">🥉</span> Bronze</td>
                                <td class="text-center py-2 px-2 text-emerald-300">ไม่มี</td>
                                <td class="text-center py-2 px-2 text-emerald-300">ไม่มี</td>
                                <td class="text-center py-2 px-2 text-emerald-300">ไม่มี</td>
                                <td class="text-center py-2 px-2 text-emerald-300">ไม่มี</td>
                                <td class="text-center py-2 px-2 hidden md:table-cell text-white/60">ระดับเริ่มต้น</td>
                            </tr>
                            <tr class="border-b border-white/10 bg-gray-500/10">
                                <td class="py-2 px-3"><span class="text-lg mr-1">🥈</span> Silver</td>
                                <td class="text-center py-2 px-2">100</td>
                                <td class="text-center py-2 px-2">5 คน</td>
                                <td class="text-center py-2 px-2">฿10,000</td>
                                <td class="text-center py-2 px-2">-</td>
                                <td class="text-center py-2 px-2 hidden md:table-cell text-white/60">-</td>
                            </tr>
                            <tr class="border-b border-white/10 bg-yellow-500/10">
                                <td class="py-2 px-3"><span class="text-lg mr-1">🥇</span> Gold</td>
                                <td class="text-center py-2 px-2">500</td>
                                <td class="text-center py-2 px-2">20 คน</td>
                                <td class="text-center py-2 px-2">฿50,000</td>
                                <td class="text-center py-2 px-2">-</td>
                                <td class="text-center py-2 px-2 hidden md:table-cell text-white/60">Active 10 คน</td>
                            </tr>
                            <tr class="border-b border-white/10 bg-slate-500/10">
                                <td class="py-2 px-3"><span class="text-lg mr-1">💎</span> Platinum</td>
                                <td class="text-center py-2 px-2">2,000</td>
                                <td class="text-center py-2 px-2">50 คน</td>
                                <td class="text-center py-2 px-2">฿200,000</td>
                                <td class="text-center py-2 px-2">฿500,000</td>
                                <td class="text-center py-2 px-2 hidden md:table-cell text-white/60">Active 25 คน</td>
                            </tr>
                            <tr class="border-b border-white/10 bg-cyan-500/10">
                                <td class="py-2 px-3"><span class="text-lg mr-1">💠</span> Diamond</td>
                                <td class="text-center py-2 px-2">10,000</td>
                                <td class="text-center py-2 px-2">100 คน</td>
                                <td class="text-center py-2 px-2">฿1,000,000</td>
                                <td class="text-center py-2 px-2">฿2,000,000</td>
                                <td class="text-center py-2 px-2 hidden md:table-cell text-white/60">Active 50 คน</td>
                            </tr>
                            <tr class="border-b border-white/10 bg-amber-500/10">
                                <td class="py-2 px-3"><span class="text-lg mr-1">👑</span> Crown</td>
                                <td class="text-center py-2 px-2">25,000</td>
                                <td class="text-center py-2 px-2">200 คน</td>
                                <td class="text-center py-2 px-2">฿2,500,000</td>
                                <td class="text-center py-2 px-2">฿5,000,000</td>
                                <td class="text-center py-2 px-2 hidden md:table-cell text-yellow-300">Diamond 2 คน</td>
                            </tr>
                            <tr class="border-b border-white/10 bg-violet-500/10">
                                <td class="py-2 px-3"><span class="text-lg mr-1">🏆</span> Royal</td>
                                <td class="text-center py-2 px-2">50,000</td>
                                <td class="text-center py-2 px-2">350 คน</td>
                                <td class="text-center py-2 px-2">฿5,000,000</td>
                                <td class="text-center py-2 px-2">฿10,000,000</td>
                                <td class="text-center py-2 px-2 hidden md:table-cell text-yellow-300">Crown 3 คน</td>
                            </tr>
                            <tr class="bg-pink-500/20">
                                <td class="py-2 px-3"><span class="text-lg mr-1">🌟</span> <strong>Legend</strong></td>
                                <td class="text-center py-2 px-2 font-bold">100,000</td>
                                <td class="text-center py-2 px-2 font-bold">500 คน</td>
                                <td class="text-center py-2 px-2 font-bold">฿10,000,000</td>
                                <td class="text-center py-2 px-2 font-bold">฿25,000,000</td>
                                <td class="text-center py-2 px-2 hidden md:table-cell text-yellow-300 font-bold">Royal 3 คน</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Key Features -->
            <div class="grid md:grid-cols-3 gap-4">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-4 text-center">
                    <div class="text-3xl mb-2">⚡</div>
                    <h4 class="text-lg font-bold text-white mb-2">เลื่อนระดับอัตโนมัติ</h4>
                    <p class="text-white/70 text-sm">ระบบตรวจสอบและเลื่อนระดับอัตโนมัติเมื่อครบเงื่อนไข</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-4 text-center">
                    <div class="text-3xl mb-2">📊</div>
                    <h4 class="text-lg font-bold text-white mb-2">Dashboard Real-time</h4>
                    <p class="text-white/70 text-sm">ติดตามความคืบหน้าการเลื่อนระดับได้ตลอด 24 ชม.</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-4 text-center">
                    <div class="text-3xl mb-2">💰</div>
                    <h4 class="text-lg font-bold text-white mb-2">โบนัสทันที</h4>
                    <p class="text-white/70 text-sm">รับโบนัสเลื่อนระดับเข้า Wallet ทันทีเมื่อเลื่อนสำเร็จ</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 4: ระบบ E-Commerce & Marketplace -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-blue-900/90 via-indigo-900/80 to-purple-900/90 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-8 md:mb-12 text-center">
                🛒 ระบบ E-Commerce & Marketplace
            </h2>

            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <!-- E-Commerce -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 hover:bg-white/15 transition-all shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">🏪</div>
                        <div class="flex-1 text-white">
                            <h3 class="text-2xl font-bold mb-3">ร้านค้าออนไลน์</h3>
                            <ul class="space-y-2 text-white/90">
                                <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> จัดการสินค้าไม่จำกัด</li>
                                <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> หมวดหมู่และแท็กสินค้า</li>
                                <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> ระบบ Variants & SKU</li>
                                <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> Stock Management</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Marketplace -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 hover:bg-white/15 transition-all shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">🏬</div>
                        <div class="flex-1 text-white">
                            <h3 class="text-2xl font-bold mb-3">Marketplace Multi-Vendor</h3>
                            <ul class="space-y-2 text-white/90">
                                <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> หลายร้านค้าในระบบเดียว</li>
                                <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> Seller Dashboard แยกต่างหาก</li>
                                <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> ระบบค่าคอมมิชชั่นร้านค้า</li>
                                <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> อนุมัติร้านค้าอัตโนมัติ/Manual</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Order Management -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 hover:bg-white/15 transition-all shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">📦</div>
                        <div class="flex-1 text-white">
                            <h3 class="text-2xl font-bold mb-3">จัดการคำสั่งซื้อ</h3>
                            <ul class="space-y-2 text-white/90">
                                <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> ติดตามสถานะ Order แบบ Real-time</li>
                                <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> ระบบจัดส่งหลากหลายช่องทาง</li>
                                <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> Tracking Number อัตโนมัติ</li>
                                <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> Invoice & Receipt</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Payment -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 hover:bg-white/15 transition-all shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">💳</div>
                        <div class="flex-1 text-white">
                            <h3 class="text-2xl font-bold mb-3">ช่องทางชำระเงิน</h3>
                            <ul class="space-y-2 text-white/90">
                                <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> บัตรเครดิต/เดบิต</li>
                                <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> PromptPay & QR Payment</li>
                                <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> True Money Wallet</li>
                                <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> Cryptocurrency & TPIX</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-blue-500/20 to-purple-500/20 backdrop-blur-md border border-blue-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-xl md:text-2xl text-white font-bold">
                    🚀 ระบบ E-Commerce ที่ครบครัน พร้อมขายได้ทันที
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 5: AI & Automation - ภาพรวม -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-cyan-900/95 via-blue-900/90 to-indigo-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-cyan-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-blue-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
            <div class="absolute top-1/2 right-1/3 w-72 h-72 bg-indigo-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-4000"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6 text-center">
                🤖 AI & Automation
            </h2>
            <p class="text-center text-xl md:text-2xl text-cyan-200 mb-10">
                เปลี่ยนธุรกิจของคุณให้ทำงานอัตโนมัติ 24/7
            </p>

            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-3xl p-6 md:p-8 mb-8 shadow-2xl">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center gap-4 px-6 py-3 bg-gradient-to-r from-cyan-500/30 to-blue-500/30 rounded-full border border-cyan-300/30">
                        <span class="text-4xl">🚀</span>
                        <span class="text-xl md:text-2xl font-bold text-white">ทำไม AI ถึงเปลี่ยนเกมธุรกิจ?</span>
                    </div>
                </div>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="text-center p-4">
                        <div class="text-5xl mb-3">⏰</div>
                        <h4 class="text-xl font-bold text-white mb-2">ทำงาน 24/7</h4>
                        <p class="text-white/70">ไม่มีวันหยุด ไม่มีเวลาพัก ตอบลูกค้าได้ตลอด</p>
                    </div>
                    <div class="text-center p-4">
                        <div class="text-5xl mb-3">⚡</div>
                        <h4 class="text-xl font-bold text-white mb-2">เร็วกว่ามนุษย์ 100x</h4>
                        <p class="text-white/70">ตอบได้หลายพันคนพร้อมกัน ไม่ต้องรอคิว</p>
                    </div>
                    <div class="text-center p-4">
                        <div class="text-5xl mb-3">💰</div>
                        <h4 class="text-xl font-bold text-white mb-2">ประหยัด 80%</h4>
                        <p class="text-white/70">ลดต้นทุนพนักงาน แต่เพิ่มยอดขาย</p>
                    </div>
                </div>
            </div>

            <!-- AI Features Overview -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-gradient-to-br from-cyan-500/30 to-cyan-500/10 backdrop-blur-lg border border-cyan-300/30 rounded-2xl p-4 text-center transform hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-2">💬</div>
                    <h4 class="text-base font-bold text-white">AI Chatbot</h4>
                    <p class="text-white/60 text-xs mt-1">ตอบทุกคำถาม</p>
                </div>
                <div class="bg-gradient-to-br from-green-500/30 to-green-500/10 backdrop-blur-lg border border-green-300/30 rounded-2xl p-4 text-center transform hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-2">💚</div>
                    <h4 class="text-base font-bold text-white">LINE Bot</h4>
                    <p class="text-white/60 text-xs mt-1">แชทอัตโนมัติ</p>
                </div>
                <div class="bg-gradient-to-br from-purple-500/30 to-purple-500/10 backdrop-blur-lg border border-purple-300/30 rounded-2xl p-4 text-center transform hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-2">🎯</div>
                    <h4 class="text-base font-bold text-white">Auto Sales</h4>
                    <p class="text-white/60 text-xs mt-1">ปิดการขาย</p>
                </div>
                <div class="bg-gradient-to-br from-amber-500/30 to-amber-500/10 backdrop-blur-lg border border-amber-300/30 rounded-2xl p-4 text-center transform hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-2">📊</div>
                    <h4 class="text-base font-bold text-white">Analytics</h4>
                    <p class="text-white/60 text-xs mt-1">วิเคราะห์ข้อมูล</p>
                </div>
            </div>

            <div class="bg-gradient-to-r from-cyan-500/20 to-blue-500/20 backdrop-blur-md border border-cyan-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-xl md:text-2xl text-white font-bold">
                    🧠 <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-300 to-blue-300">AI ทำงานแทน = คุณมีเวลามากขึ้น + ยอดขายเพิ่มขึ้น</span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 5.1: AI Chatbot & LINE Bot -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-emerald-900/95 via-green-900/90 to-teal-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-25">
            <div class="absolute top-1/3 left-1/4 w-80 h-80 bg-emerald-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute bottom-1/3 right-1/4 w-80 h-80 bg-green-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6 text-center">
                💬 AI Chatbot & LINE Bot
            </h2>
            <p class="text-center text-xl text-emerald-200 mb-10">
                ตอบลูกค้าอัตโนมัติ ปิดการขาย 24 ชั่วโมง
            </p>

            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <!-- AI Chatbot Features -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 shadow-xl">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl flex items-center justify-center text-3xl">
                            🤖
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-white">AI Chatbot</h3>
                            <p class="text-white/70">เชื่อมต่อ ChatGPT & Claude</p>
                        </div>
                    </div>
                    <ul class="space-y-3 text-white/90">
                        <li class="flex items-start gap-3">
                            <span class="text-cyan-400 text-xl">✓</span>
                            <div>
                                <strong class="text-white">ตอบคำถามอัจฉริยะ</strong>
                                <p class="text-sm text-white/60">เข้าใจบริบท ตอบได้ถูกต้องแม่นยำ</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-cyan-400 text-xl">✓</span>
                            <div>
                                <strong class="text-white">หลายภาษา</strong>
                                <p class="text-sm text-white/60">ไทย อังกฤษ จีน และอีก 50+ ภาษา</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-cyan-400 text-xl">✓</span>
                            <div>
                                <strong class="text-white">Personality สั่งได้</strong>
                                <p class="text-sm text-white/60">กำหนดบุคลิกบอทตามแบรนด์</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-cyan-400 text-xl">✓</span>
                            <div>
                                <strong class="text-white">เรียนรู้จากข้อมูลคุณ</strong>
                                <p class="text-sm text-white/60">Train ด้วย FAQ, สินค้า, บริการ</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- LINE Bot Features -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 shadow-xl">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center text-3xl">
                            💚
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-white">LINE Bot</h3>
                            <p class="text-white/70">Official Account Integration</p>
                        </div>
                    </div>
                    <ul class="space-y-3 text-white/90">
                        <li class="flex items-start gap-3">
                            <span class="text-green-400 text-xl">✓</span>
                            <div>
                                <strong class="text-white">Rich Menu สวยงาม</strong>
                                <p class="text-sm text-white/60">เมนูภาพสวย ใช้งานง่าย</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-400 text-xl">✓</span>
                            <div>
                                <strong class="text-white">Flex Message</strong>
                                <p class="text-sm text-white/60">ข้อความสวยๆ มีปุ่มกดได้</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-400 text-xl">✓</span>
                            <div>
                                <strong class="text-white">LIFF Mini App</strong>
                                <p class="text-sm text-white/60">แอปในไลน์ สมัครสมาชิก ซื้อของ</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-400 text-xl">✓</span>
                            <div>
                                <strong class="text-white">Broadcast & Segment</strong>
                                <p class="text-sm text-white/60">ส่งข้อความหาลูกค้ากลุ่มเป้าหมาย</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Comparison -->
            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 overflow-x-auto">
                <table class="w-full text-white text-sm md:text-base">
                    <thead>
                        <tr class="border-b border-white/20">
                            <th class="text-left py-3 px-4">เปรียบเทียบ</th>
                            <th class="text-center py-3 px-4 text-red-300">❌ ไม่มี AI</th>
                            <th class="text-center py-3 px-4 text-emerald-300">✅ มี AI ของเรา</th>
                        </tr>
                    </thead>
                    <tbody class="text-white/80">
                        <tr class="border-b border-white/10">
                            <td class="py-3 px-4">เวลาตอบลูกค้า</td>
                            <td class="text-center py-3 px-4 text-red-400">5-30 นาที</td>
                            <td class="text-center py-3 px-4 text-emerald-400 font-bold">&lt;3 วินาที ✅</td>
                        </tr>
                        <tr class="border-b border-white/10">
                            <td class="py-3 px-4">รองรับลูกค้าพร้อมกัน</td>
                            <td class="text-center py-3 px-4 text-red-400">3-5 คน</td>
                            <td class="text-center py-3 px-4 text-emerald-400 font-bold">ไม่จำกัด ✅</td>
                        </tr>
                        <tr class="border-b border-white/10">
                            <td class="py-3 px-4">เวลาทำงาน</td>
                            <td class="text-center py-3 px-4 text-red-400">8-10 ชม./วัน</td>
                            <td class="text-center py-3 px-4 text-emerald-400 font-bold">24/7 ✅</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4">ค่าใช้จ่าย/เดือน</td>
                            <td class="text-center py-3 px-4 text-red-400">฿15,000+/คน</td>
                            <td class="text-center py-3 px-4 text-emerald-400 font-bold">รวมในแพ็คเกจ ✅</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Slide 5.2: Marketing Automation -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-purple-900/95 via-pink-900/90 to-rose-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-25">
            <div class="absolute top-1/4 left-1/3 w-80 h-80 bg-purple-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute bottom-1/4 right-1/3 w-80 h-80 bg-pink-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6 text-center">
                🎯 Marketing Automation
            </h2>
            <p class="text-center text-xl text-pink-200 mb-10">
                ระบบการตลาดอัตโนมัติที่ช่วยขายแทนคุณ
            </p>

            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <!-- Lead Generation -->
                <div class="bg-gradient-to-br from-blue-500/25 to-indigo-500/15 backdrop-blur-lg border border-blue-300/30 rounded-2xl p-5 shadow-xl">
                    <div class="text-center mb-4">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl mb-3">
                            <span class="text-3xl">🧲</span>
                        </div>
                        <h3 class="text-xl font-bold text-white">Lead Generation</h3>
                    </div>
                    <ul class="space-y-2 text-white/80 text-sm">
                        <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> ดึงดูดลูกค้าใหม่อัตโนมัติ</li>
                        <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> Landing Page + Form</li>
                        <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> Social Media Integration</li>
                        <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> Lead Scoring อัจฉริยะ</li>
                    </ul>
                </div>

                <!-- Auto Follow-up -->
                <div class="bg-gradient-to-br from-purple-500/25 to-pink-500/15 backdrop-blur-lg border border-purple-300/30 rounded-2xl p-5 shadow-xl">
                    <div class="text-center mb-4">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl mb-3">
                            <span class="text-3xl">🔄</span>
                        </div>
                        <h3 class="text-xl font-bold text-white">Auto Follow-up</h3>
                    </div>
                    <ul class="space-y-2 text-white/80 text-sm">
                        <li class="flex items-center gap-2"><span class="text-purple-400">✓</span> Email Sequence อัตโนมัติ</li>
                        <li class="flex items-center gap-2"><span class="text-purple-400">✓</span> LINE/SMS Reminder</li>
                        <li class="flex items-center gap-2"><span class="text-purple-400">✓</span> Drip Campaign</li>
                        <li class="flex items-center gap-2"><span class="text-purple-400">✓</span> Retargeting Smart</li>
                    </ul>
                </div>

                <!-- Auto Closing -->
                <div class="bg-gradient-to-br from-emerald-500/25 to-green-500/15 backdrop-blur-lg border border-emerald-300/30 rounded-2xl p-5 shadow-xl">
                    <div class="text-center mb-4">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl mb-3">
                            <span class="text-3xl">💰</span>
                        </div>
                        <h3 class="text-xl font-bold text-white">Auto Closing</h3>
                    </div>
                    <ul class="space-y-2 text-white/80 text-sm">
                        <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> AI ปิดการขายอัตโนมัติ</li>
                        <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Upsell & Cross-sell</li>
                        <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Abandoned Cart Recovery</li>
                        <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Limited Offer Trigger</li>
                    </ul>
                </div>
            </div>

            <!-- Sales Funnel -->
            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 mb-6">
                <h3 class="text-xl font-bold text-white text-center mb-6">🔥 Marketing Funnel อัตโนมัติ</h3>
                <div class="flex flex-col md:flex-row items-center justify-center gap-4">
                    <div class="flex items-center gap-2 px-4 py-2 bg-blue-500/30 rounded-full">
                        <span class="text-2xl">👀</span>
                        <span class="text-white font-bold">รับรู้</span>
                    </div>
                    <div class="text-white/50 text-2xl hidden md:block">→</div>
                    <div class="flex items-center gap-2 px-4 py-2 bg-purple-500/30 rounded-full">
                        <span class="text-2xl">🤔</span>
                        <span class="text-white font-bold">สนใจ</span>
                    </div>
                    <div class="text-white/50 text-2xl hidden md:block">→</div>
                    <div class="flex items-center gap-2 px-4 py-2 bg-pink-500/30 rounded-full">
                        <span class="text-2xl">💭</span>
                        <span class="text-white font-bold">พิจารณา</span>
                    </div>
                    <div class="text-white/50 text-2xl hidden md:block">→</div>
                    <div class="flex items-center gap-2 px-4 py-2 bg-amber-500/30 rounded-full">
                        <span class="text-2xl">🛒</span>
                        <span class="text-white font-bold">ซื้อ</span>
                    </div>
                    <div class="text-white/50 text-2xl hidden md:block">→</div>
                    <div class="flex items-center gap-2 px-4 py-2 bg-emerald-500/30 rounded-full">
                        <span class="text-2xl">❤️</span>
                        <span class="text-white font-bold">ซื้อซ้ำ</span>
                    </div>
                </div>
                <p class="text-center text-white/60 text-sm mt-4">
                    ทุกขั้นตอนทำงานอัตโนมัติ ไม่ต้องจ้างคน ไม่ต้องทำเอง
                </p>
            </div>

            <div class="bg-gradient-to-r from-purple-500/20 to-pink-500/20 backdrop-blur-md border border-purple-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-xl md:text-2xl text-white font-bold">
                    📈 <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-300 to-pink-300">เพิ่มยอดขาย 3-5 เท่า โดยไม่ต้องเพิ่มพนักงาน</span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 5.3: AI Benefits & ROI -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-amber-900/95 via-orange-900/90 to-red-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-25">
            <div class="absolute top-1/4 left-1/4 w-80 h-80 bg-amber-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-orange-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6 text-center">
                🏆 ทำไม AI ของเราเหนือกว่า?
            </h2>
            <p class="text-center text-xl text-amber-200 mb-10">
                เปรียบเทียบกับแพลตฟอร์มอื่น
            </p>

            <div class="grid md:grid-cols-2 gap-8 mb-8">
                <!-- Our Advantages -->
                <div class="bg-gradient-to-br from-emerald-500/25 to-green-500/15 backdrop-blur-lg border border-emerald-300/30 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-2xl font-bold text-emerald-300 mb-6 flex items-center gap-3">
                        <span class="text-3xl">✅</span> ข้อดีของระบบเรา
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-start gap-4 p-3 bg-white/5 rounded-xl">
                            <div class="text-2xl">🔗</div>
                            <div>
                                <h4 class="font-bold text-white">All-in-One</h4>
                                <p class="text-white/70 text-sm">รวมทุกระบบไว้ที่เดียว ไม่ต้องซื้อแยก</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 p-3 bg-white/5 rounded-xl">
                            <div class="text-2xl">🇹🇭</div>
                            <div>
                                <h4 class="font-bold text-white">ภาษาไทยเนทีฟ</h4>
                                <p class="text-white/70 text-sm">เข้าใจภาษาไทย ไม่เหมือน AI ต่างประเทศ</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 p-3 bg-white/5 rounded-xl">
                            <div class="text-2xl">💎</div>
                            <div>
                                <h4 class="font-bold text-white">รวมในราคาเดียว</h4>
                                <p class="text-white/70 text-sm">ไม่มีค่า API, ไม่มีค่าใช้งานเพิ่ม</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 p-3 bg-white/5 rounded-xl">
                            <div class="text-2xl">🛠️</div>
                            <div>
                                <h4 class="font-bold text-white">ปรับแต่งได้</h4>
                                <p class="text-white/70 text-sm">Train AI ด้วยข้อมูลธุรกิจคุณเอง</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Competitors Issues -->
                <div class="bg-gradient-to-br from-red-500/25 to-rose-500/15 backdrop-blur-lg border border-red-300/30 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-2xl font-bold text-red-300 mb-6 flex items-center gap-3">
                        <span class="text-3xl">❌</span> ปัญหาที่อื่น
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-start gap-4 p-3 bg-white/5 rounded-xl">
                            <div class="text-2xl">💸</div>
                            <div>
                                <h4 class="font-bold text-white">ค่าใช้จ่ายแยกหลายตัว</h4>
                                <p class="text-white/70 text-sm">Chatbot + CRM + Email = ฿10,000+/เดือน</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 p-3 bg-white/5 rounded-xl">
                            <div class="text-2xl">🌐</div>
                            <div>
                                <h4 class="font-bold text-white">ภาษาไทยไม่เก่ง</h4>
                                <p class="text-white/70 text-sm">ตอบผิด เข้าใจบริบทไม่ได้</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 p-3 bg-white/5 rounded-xl">
                            <div class="text-2xl">🔧</div>
                            <div>
                                <h4 class="font-bold text-white">ต้องตั้งค่าเอง</h4>
                                <p class="text-white/70 text-sm">ซับซ้อน ต้องจ้างผู้เชี่ยวชาญ</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 p-3 bg-white/5 rounded-xl">
                            <div class="text-2xl">📊</div>
                            <div>
                                <h4 class="font-bold text-white">ข้อมูลกระจาย</h4>
                                <p class="text-white/70 text-sm">ใช้หลายระบบ ดูรายงานยาก</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ROI Calculator -->
            <div class="bg-gradient-to-r from-amber-600/30 via-orange-600/30 to-red-600/30 backdrop-blur-md border border-amber-300/40 rounded-2xl p-6 md:p-8 shadow-xl">
                <div class="text-center">
                    <h3 class="text-2xl md:text-3xl font-black text-white mb-4">
                        💰 ประหยัดได้เท่าไหร่?
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white/10 rounded-xl px-4 py-3 border border-white/20">
                            <div class="text-2xl md:text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-red-300 to-rose-300">-80%</div>
                            <div class="text-white/60 text-xs">ลดต้นทุน</div>
                        </div>
                        <div class="bg-white/10 rounded-xl px-4 py-3 border border-white/20">
                            <div class="text-2xl md:text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-300 to-orange-300">+300%</div>
                            <div class="text-white/60 text-xs">เพิ่มยอดขาย</div>
                        </div>
                        <div class="bg-white/10 rounded-xl px-4 py-3 border border-white/20">
                            <div class="text-2xl md:text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 to-green-300">24/7</div>
                            <div class="text-white/60 text-xs">ทำงานตลอด</div>
                        </div>
                        <div class="bg-white/10 rounded-xl px-4 py-3 border border-white/20">
                            <div class="text-2xl md:text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-300 to-blue-300">∞</div>
                            <div class="text-white/60 text-xs">รองรับลูกค้า</div>
                        </div>
                    </div>
                    <p class="text-white/80 mt-4 text-lg">
                        🚀 <strong>เริ่มต้นวันนี้ เห็นผลลัพธ์ทันที</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 6: รู้จักกับ TPIX Token -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-violet-900/95 via-purple-900/90 to-fuchsia-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Animated background effects -->
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-violet-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-fuchsia-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
            <div class="absolute top-1/2 left-1/2 w-72 h-72 bg-pink-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-4000"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6 md:mb-8 text-center">
                🪙 รู้จักกับ TPIX Token
            </h2>
            <p class="text-center text-xl md:text-2xl text-purple-200 mb-8 md:mb-10">
                Native Cryptocurrency ของ Thaiprompt Ecosystem
            </p>

            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-3xl p-6 md:p-8 mb-8 shadow-2xl">
                <div class="flex flex-col md:flex-row items-center justify-center gap-8 md:gap-12">
                    <!-- TPIX Logo -->
                    <div class="text-center flex-shrink-0">
                        <div class="w-32 h-32 md:w-40 md:h-40 mx-auto mb-4 animate-float">
                            <img src="{{ asset('images/tpix-logo.svg') }}" alt="TPIX Token" class="w-full h-full object-contain filter drop-shadow-2xl">
                        </div>
                        <h3 class="text-3xl md:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-violet-300 via-purple-300 to-fuchsia-300">TPIX</h3>
                        <p class="text-white/70 text-lg">Thaiprompt Index</p>
                    </div>

                    <!-- Description -->
                    <div class="text-white text-center md:text-left max-w-xl">
                        <h3 class="text-2xl md:text-3xl font-bold mb-4">เหรียญที่สร้างเพื่อคนไทย</h3>
                        <p class="text-lg md:text-xl text-white/80 leading-relaxed mb-4">
                            <strong class="text-transparent bg-clip-text bg-gradient-to-r from-violet-300 to-fuchsia-300">TPIX (Thaiprompt Index)</strong>
                            เป็น Native Cryptocurrency ที่มี <strong>Blockchain ของตัวเอง</strong>
                            ไม่ใช่แค่ Token บน Ethereum หรือ BSC
                        </p>
                        <p class="text-md text-white/70 leading-relaxed">
                            พัฒนาโดยทีมนักพัฒนาไทย เพื่อตอบโจทย์การใช้งานจริงในประเทศไทย
                            และรองรับการขยายตลาดสู่เอเชียตะวันออกเฉียงใต้
                        </p>
                    </div>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-gradient-to-br from-violet-500/30 to-purple-500/20 backdrop-blur-lg border border-violet-300/30 rounded-2xl p-4 md:p-6 text-center transform hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-violet-300 to-purple-300 mb-2">7B</div>
                    <div class="text-white/70 text-sm md:text-base">Total Supply</div>
                    <div class="text-white/50 text-xs mt-1">Fixed (ไม่เพิ่มอีก)</div>
                </div>
                <div class="bg-gradient-to-br from-fuchsia-500/30 to-pink-500/20 backdrop-blur-lg border border-fuchsia-300/30 rounded-2xl p-4 md:p-6 text-center transform hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-fuchsia-300 to-pink-300 mb-2">2 วิ</div>
                    <div class="text-white/70 text-sm md:text-base">Block Time</div>
                    <div class="text-white/50 text-xs mt-1">เร็วกว่า Bitcoin 300x</div>
                </div>
                <div class="bg-gradient-to-br from-purple-500/30 to-violet-500/20 backdrop-blur-lg border border-purple-300/30 rounded-2xl p-4 md:p-6 text-center transform hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-300 to-violet-300 mb-2">&lt;฿0.3</div>
                    <div class="text-white/70 text-sm md:text-base">ค่าธรรมเนียม/รายการ</div>
                    <div class="text-white/50 text-xs mt-1">vs Ethereum ฿150+</div>
                </div>
                <div class="bg-gradient-to-br from-pink-500/30 to-fuchsia-500/20 backdrop-blur-lg border border-pink-300/30 rounded-2xl p-4 md:p-6 text-center transform hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-pink-300 to-fuchsia-300 mb-2">1,500</div>
                    <div class="text-white/70 text-sm md:text-base">TPS</div>
                    <div class="text-white/50 text-xs mt-1">ธุรกรรม/วินาที</div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-violet-500/20 to-fuchsia-500/20 backdrop-blur-md border border-purple-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-xl md:text-2xl text-white font-bold">
                    🏗️ <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-300 to-fuchsia-300">Blockchain ของเราเอง</span> • ไม่พึ่งพาใคร • ควบคุมได้เต็มที่
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 6.1: ทำไมต้อง TPIX -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-emerald-900/95 via-teal-900/90 to-cyan-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-25">
            <div class="absolute top-1/3 left-1/4 w-80 h-80 bg-emerald-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute bottom-1/3 right-1/4 w-80 h-80 bg-cyan-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6 text-center">
                💡 ทำไมต้อง TPIX?
            </h2>
            <p class="text-center text-xl text-emerald-200 mb-10">
                เหตุผลที่เราสร้าง Blockchain ของตัวเอง แทนที่จะใช้ Ethereum หรือ BSC
            </p>

            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <!-- Problem vs Solution Cards -->
                <div class="bg-gradient-to-br from-red-500/20 to-rose-500/10 backdrop-blur-lg border border-red-300/30 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-xl font-bold text-red-300 mb-4 flex items-center gap-2">
                        <span class="text-2xl">❌</span> ปัญหาของ Blockchain อื่น
                    </h3>
                    <ul class="space-y-3 text-white/80">
                        <li class="flex items-start gap-3">
                            <span class="text-red-400 text-lg">•</span>
                            <div>
                                <strong class="text-white">ค่าแก๊สสูงลิบ</strong>
                                <p class="text-sm text-white/60">Ethereum: ฿150-1,500 ต่อรายการ</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-red-400 text-lg">•</span>
                            <div>
                                <strong class="text-white">ช้า & แออัด</strong>
                                <p class="text-sm text-white/60">Bitcoin: 10 นาที, Ethereum: 15 วินาที</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-red-400 text-lg">•</span>
                            <div>
                                <strong class="text-white">ไม่มี Use Case จริง</strong>
                                <p class="text-sm text-white/60">ส่วนใหญ่แค่เก็งกำไร ไม่มีการใช้งานจริง</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-red-400 text-lg">•</span>
                            <div>
                                <strong class="text-white">ต้องพึ่งพาคนอื่น</strong>
                                <p class="text-sm text-white/60">Network congestion กระทบธุรกิจ</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="bg-gradient-to-br from-emerald-500/20 to-green-500/10 backdrop-blur-lg border border-emerald-300/30 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-xl font-bold text-emerald-300 mb-4 flex items-center gap-2">
                        <span class="text-2xl">✅</span> ทางออกของ TPIX
                    </h3>
                    <ul class="space-y-3 text-white/80">
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-lg">•</span>
                            <div>
                                <strong class="text-white">ค่าธรรมเนียมต่ำมาก</strong>
                                <p class="text-sm text-white/60">TPIX: &lt;฿0.30 ต่อรายการ (ถูกกว่า 500x)</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-lg">•</span>
                            <div>
                                <strong class="text-white">เร็วสุดยอด</strong>
                                <p class="text-sm text-white/60">Block time 2 วินาที, 1,500 TPS</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-lg">•</span>
                            <div>
                                <strong class="text-white">11+ Use Cases จริง</strong>
                                <p class="text-sm text-white/60">E-Commerce, MLM, FoodPassport, DEX...</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-lg">•</span>
                            <div>
                                <strong class="text-white">ควบคุมได้ 100%</strong>
                                <p class="text-sm text-white/60">Own Blockchain = อิสระเต็มที่</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Comparison Chart -->
            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 mb-6 overflow-x-auto">
                <table class="w-full text-white text-sm md:text-base">
                    <thead>
                        <tr class="border-b border-white/20">
                            <th class="text-left py-3 px-2 md:px-4">เปรียบเทียบ</th>
                            <th class="text-center py-3 px-2 md:px-4 text-amber-300">Bitcoin</th>
                            <th class="text-center py-3 px-2 md:px-4 text-indigo-300">Ethereum</th>
                            <th class="text-center py-3 px-2 md:px-4 text-emerald-300">🪙 TPIX</th>
                        </tr>
                    </thead>
                    <tbody class="text-white/80">
                        <tr class="border-b border-white/10">
                            <td class="py-3 px-2 md:px-4">ความเร็ว</td>
                            <td class="text-center py-3 px-2 md:px-4 text-red-400">10 นาที</td>
                            <td class="text-center py-3 px-2 md:px-4 text-yellow-400">15 วินาที</td>
                            <td class="text-center py-3 px-2 md:px-4 text-emerald-400 font-bold">2 วินาที ✅</td>
                        </tr>
                        <tr class="border-b border-white/10">
                            <td class="py-3 px-2 md:px-4">ค่าธรรมเนียม</td>
                            <td class="text-center py-3 px-2 md:px-4 text-red-400">฿50+</td>
                            <td class="text-center py-3 px-2 md:px-4 text-red-400">฿150-1,500</td>
                            <td class="text-center py-3 px-2 md:px-4 text-emerald-400 font-bold">&lt;฿0.30 ✅</td>
                        </tr>
                        <tr class="border-b border-white/10">
                            <td class="py-3 px-2 md:px-4">TPS</td>
                            <td class="text-center py-3 px-2 md:px-4 text-red-400">7</td>
                            <td class="text-center py-3 px-2 md:px-4 text-yellow-400">30</td>
                            <td class="text-center py-3 px-2 md:px-4 text-emerald-400 font-bold">1,500 ✅</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-2 md:px-4">Smart Contract</td>
                            <td class="text-center py-3 px-2 md:px-4 text-red-400">❌</td>
                            <td class="text-center py-3 px-2 md:px-4 text-emerald-400">✅</td>
                            <td class="text-center py-3 px-2 md:px-4 text-emerald-400 font-bold">✅ EVM</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-gradient-to-r from-emerald-500/20 to-cyan-500/20 backdrop-blur-md border border-emerald-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-xl md:text-2xl text-white font-bold">
                    ⚡ <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 to-cyan-300">เร็วกว่า ถูกกว่า ใช้งานได้จริง</span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 6.2: ประโยชน์และการใช้งาน TPIX -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-amber-900/95 via-orange-900/90 to-yellow-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-25">
            <div class="absolute top-1/4 left-1/3 w-80 h-80 bg-amber-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute bottom-1/4 right-1/3 w-80 h-80 bg-yellow-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6 text-center">
                🎯 ใช้ TPIX ทำอะไรได้บ้าง?
            </h2>
            <p class="text-center text-xl text-amber-200 mb-10">
                11+ Use Cases ที่ใช้งานได้จริงในแพลตฟอร์ม
            </p>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
                <!-- Use Case Cards -->
                <div class="bg-gradient-to-br from-blue-500/25 to-indigo-500/15 backdrop-blur-lg border border-blue-300/30 rounded-2xl p-4 md:p-5 text-center transform hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-3">💰</div>
                    <h4 class="text-base md:text-lg font-bold text-white">ชำระค่าสินค้า</h4>
                    <p class="text-white/60 text-xs mt-1">E-Commerce & ร้านค้า</p>
                </div>

                <div class="bg-gradient-to-br from-purple-500/25 to-violet-500/15 backdrop-blur-lg border border-purple-300/30 rounded-2xl p-4 md:p-5 text-center transform hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-3">🎯</div>
                    <h4 class="text-base md:text-lg font-bold text-white">รับคอมมิชชัน</h4>
                    <p class="text-white/60 text-xs mt-1">Affiliate & MLM</p>
                </div>

                <div class="bg-gradient-to-br from-emerald-500/25 to-green-500/15 backdrop-blur-lg border border-emerald-300/30 rounded-2xl p-4 md:p-5 text-center transform hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-3">🏦</div>
                    <h4 class="text-base md:text-lg font-bold text-white">Staking</h4>
                    <p class="text-white/60 text-xs mt-1">รับ APY สูงสุด 200%</p>
                </div>

                <div class="bg-gradient-to-br from-cyan-500/25 to-teal-500/15 backdrop-blur-lg border border-cyan-300/30 rounded-2xl p-4 md:p-5 text-center transform hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-3">🔄</div>
                    <h4 class="text-base md:text-lg font-bold text-white">DEX Trading</h4>
                    <p class="text-white/60 text-xs mt-1">แลกเปลี่ยน Token</p>
                </div>

                <div class="bg-gradient-to-br from-rose-500/25 to-pink-500/15 backdrop-blur-lg border border-rose-300/30 rounded-2xl p-4 md:p-5 text-center transform hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-3">🪙</div>
                    <h4 class="text-base md:text-lg font-bold text-white">สร้าง Token</h4>
                    <p class="text-white/60 text-xs mt-1">Token Factory</p>
                </div>

                <div class="bg-gradient-to-br from-orange-500/25 to-amber-500/15 backdrop-blur-lg border border-orange-300/30 rounded-2xl p-4 md:p-5 text-center transform hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-3">🍴</div>
                    <h4 class="text-base md:text-lg font-bold text-white">FoodPassport</h4>
                    <p class="text-white/60 text-xs mt-1">ตรวจสอบอาหารปลอดภัย</p>
                </div>

                <div class="bg-gradient-to-br from-lime-500/25 to-green-500/15 backdrop-blur-lg border border-lime-300/30 rounded-2xl p-4 md:p-5 text-center transform hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-3">🌱</div>
                    <h4 class="text-base md:text-lg font-bold text-white">Carbon Credit</h4>
                    <p class="text-white/60 text-xs mt-1">ซื้อขายคาร์บอนเครดิต</p>
                </div>

                <div class="bg-gradient-to-br from-indigo-500/25 to-blue-500/15 backdrop-blur-lg border border-indigo-300/30 rounded-2xl p-4 md:p-5 text-center transform hover:scale-105 transition-all">
                    <div class="text-3xl md:text-4xl mb-3">🎁</div>
                    <h4 class="text-base md:text-lg font-bold text-white">Rewards</h4>
                    <p class="text-white/60 text-xs mt-1">รางวัล & Bonus</p>
                </div>
            </div>

            <!-- Featured Use Case -->
            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 mb-6 shadow-xl">
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="text-center p-4 bg-gradient-to-br from-amber-500/20 to-yellow-500/10 rounded-xl">
                        <div class="text-5xl mb-3">📈</div>
                        <h4 class="text-lg font-bold text-white mb-2">Staking Rewards</h4>
                        <p class="text-white/70 text-sm">ล็อค TPIX รับผลตอบแทนสูงสุด <strong class="text-amber-300">200% APY</strong></p>
                        <p class="text-white/50 text-xs mt-2">ระยะเวลา 7 วัน - 365 วัน</p>
                    </div>
                    <div class="text-center p-4 bg-gradient-to-br from-purple-500/20 to-violet-500/10 rounded-xl">
                        <div class="text-5xl mb-3">💎</div>
                        <h4 class="text-lg font-bold text-white mb-2">Liquidity Provider</h4>
                        <p class="text-white/70 text-sm">เพิ่มสภาพคล่อง DEX รับค่าธรรมเนียม <strong class="text-purple-300">0.25%</strong></p>
                        <p class="text-white/50 text-xs mt-2">ทุกการ Swap</p>
                    </div>
                    <div class="text-center p-4 bg-gradient-to-br from-emerald-500/20 to-green-500/10 rounded-xl">
                        <div class="text-5xl mb-3">🎯</div>
                        <h4 class="text-lg font-bold text-white mb-2">Referral Bonus</h4>
                        <p class="text-white/70 text-sm">แนะนำเพื่อนรับ <strong class="text-emerald-300">5%</strong> + ผู้ถูกแนะนำรับ <strong class="text-emerald-300">2%</strong></p>
                        <p class="text-white/50 text-xs mt-2">ทุกธุรกรรม</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-amber-500/20 to-orange-500/20 backdrop-blur-md border border-amber-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-xl md:text-2xl text-white font-bold">
                    🔥 <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 to-orange-300">ใช้งานได้จริง ทุกวัน ทุกธุรกรรม</span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 6.3: TPIX Tokenomics -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-slate-900/95 via-gray-900/90 to-zinc-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-1/4 left-1/4 w-80 h-80 bg-blue-500 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-purple-500 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6 text-center">
                📊 TPIX Tokenomics
            </h2>
            <p class="text-center text-xl text-gray-300 mb-10">
                การกระจาย Token อย่างยุติธรรม • ระบบ Deflationary
            </p>

            <div class="grid md:grid-cols-2 gap-8 mb-8">
                <!-- Token Distribution -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-2xl font-bold text-white mb-6 text-center">🥧 การกระจาย 7 พันล้าน TPIX</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-4 h-4 rounded-full bg-blue-500"></div>
                                <span class="text-white">💧 Liquidity</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="h-3 rounded-full bg-blue-500" style="width: 120px"></div>
                                <span class="text-blue-300 font-bold">30%</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-4 h-4 rounded-full bg-purple-500"></div>
                                <span class="text-white">👥 Team</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="h-3 rounded-full bg-purple-500" style="width: 80px"></div>
                                <span class="text-purple-300 font-bold">20%</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-4 h-4 rounded-full bg-amber-500"></div>
                                <span class="text-white">🏦 Staking Rewards</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="h-3 rounded-full bg-amber-500" style="width: 80px"></div>
                                <span class="text-amber-300 font-bold">20%</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-4 h-4 rounded-full bg-emerald-500"></div>
                                <span class="text-white">🛠️ Development</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="h-3 rounded-full bg-emerald-500" style="width: 40px"></div>
                                <span class="text-emerald-300 font-bold">10%</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-4 h-4 rounded-full bg-cyan-500"></div>
                                <span class="text-white">🌍 Ecosystem</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="h-3 rounded-full bg-cyan-500" style="width: 40px"></div>
                                <span class="text-cyan-300 font-bold">10%</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-4 h-4 rounded-full bg-rose-500"></div>
                                <span class="text-white">🚀 Public Sale</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="h-3 rounded-full bg-rose-500" style="width: 40px"></div>
                                <span class="text-rose-300 font-bold">10%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Benefits -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-2xl font-bold text-white mb-6 text-center">🎁 ทำไมต้องถือ TPIX?</h3>
                    <div class="space-y-4">
                        <div class="flex items-start gap-4 p-3 bg-gradient-to-r from-emerald-500/20 to-green-500/10 rounded-xl">
                            <div class="text-3xl">🔥</div>
                            <div>
                                <h4 class="font-bold text-emerald-300">Deflationary</h4>
                                <p class="text-white/70 text-sm">เผาทุกธุรกรรม = Supply ลดลงเรื่อยๆ</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 p-3 bg-gradient-to-r from-amber-500/20 to-orange-500/10 rounded-xl">
                            <div class="text-3xl">📈</div>
                            <div>
                                <h4 class="font-bold text-amber-300">Staking APY สูง</h4>
                                <p class="text-white/70 text-sm">5% - 200% ขึ้นอยู่กับระยะเวลา</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 p-3 bg-gradient-to-r from-purple-500/20 to-violet-500/10 rounded-xl">
                            <div class="text-3xl">💎</div>
                            <div>
                                <h4 class="font-bold text-purple-300">Utility Token</h4>
                                <p class="text-white/70 text-sm">ใช้งานได้จริงใน 11+ ระบบ</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 p-3 bg-gradient-to-r from-cyan-500/20 to-teal-500/10 rounded-xl">
                            <div class="text-3xl">🗳️</div>
                            <div>
                                <h4 class="font-bold text-cyan-300">Governance Rights</h4>
                                <p class="text-white/70 text-sm">มีสิทธิ์โหวตการพัฒนาแพลตฟอร์ม</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="bg-gradient-to-r from-violet-600/30 via-purple-600/30 to-fuchsia-600/30 backdrop-blur-md border border-purple-300/40 rounded-2xl p-6 md:p-8 shadow-xl">
                <div class="text-center">
                    <div class="flex justify-center mb-4">
                        <img src="{{ asset('images/tpix-logo.svg') }}" alt="TPIX" class="w-16 h-16 md:w-20 md:h-20 animate-float">
                    </div>
                    <h3 class="text-2xl md:text-3xl font-black text-white mb-3">
                        🚀 เริ่มต้นกับ TPIX วันนี้
                    </h3>
                    <p class="text-lg text-white/80 mb-4">
                        เป็นส่วนหนึ่งของ Ecosystem ที่เติบโตเร็วที่สุด
                    </p>
                    <div class="flex flex-wrap justify-center gap-4">
                        <div class="bg-white/10 rounded-xl px-6 py-3 border border-white/20">
                            <div class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 to-green-300">7B</div>
                            <div class="text-white/60 text-xs">Total Supply</div>
                        </div>
                        <div class="bg-white/10 rounded-xl px-6 py-3 border border-white/20">
                            <div class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-300 to-orange-300">200%</div>
                            <div class="text-white/60 text-xs">Max APY</div>
                        </div>
                        <div class="bg-white/10 rounded-xl px-6 py-3 border border-white/20">
                            <div class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-300 to-fuchsia-300">11+</div>
                            <div class="text-white/60 text-xs">Use Cases</div>
                        </div>
                        <div class="bg-white/10 rounded-xl px-6 py-3 border border-white/20">
                            <div class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-300 to-blue-300">&lt;฿0.30</div>
                            <div class="text-white/60 text-xs">ค่าธรรมเนียม</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 7: Academy & ปลดแอกธุรกิจ - ภาพรวม -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-rose-900/95 via-orange-900/90 to-amber-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 left-10 w-96 h-96 bg-rose-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-10 w-80 h-80 bg-amber-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-orange-500/10 rounded-full blur-3xl"></div>
            <!-- Freedom Pattern -->
            <div class="absolute inset-0 opacity-5">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <pattern id="freedom-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                        <path d="M10 0L20 10L10 20L0 10Z" fill="currentColor" class="text-white"/>
                    </pattern>
                    <rect width="100" height="100" fill="url(#freedom-pattern)"/>
                </svg>
            </div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-8 md:mb-12">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-rose-500/30 to-amber-500/30 backdrop-blur-md rounded-full border border-white/30 mb-6">
                    <span class="text-3xl">🦅</span>
                    <span class="text-white font-bold text-lg">Freedom Business Platform</span>
                </div>
                <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6 text-center">
                    🎓 Academy & <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 to-rose-300">ปลดแอกธุรกิจ</span>
                </h2>
                <p class="text-xl md:text-2xl text-white/80 max-w-4xl mx-auto">
                    สร้างอิสรภาพทางธุรกิจ ไม่พึ่งพาแพลตฟอร์มต่างชาติอีกต่อไป
                </p>
            </div>

            <!-- Main Content -->
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Left: Problems with Foreign Platforms -->
                <div class="bg-red-900/30 backdrop-blur-md border border-red-400/30 rounded-3xl p-6 shadow-2xl">
                    <h3 class="text-2xl font-bold text-red-300 mb-4 flex items-center gap-3">
                        <span class="text-3xl">⛓️</span> ปัญหาจากแพลตฟอร์มต่างชาติ
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 bg-red-500/20 rounded-xl p-3">
                            <span class="text-2xl">📱</span>
                            <div>
                                <p class="text-white font-semibold">Facebook/Meta</p>
                                <p class="text-red-200/70 text-sm">แบนบัญชีได้ตลอด ค่าโฆษณาแพง</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 bg-red-500/20 rounded-xl p-3">
                            <span class="text-2xl">🛒</span>
                            <div>
                                <p class="text-white font-semibold">Shopee/Lazada</p>
                                <p class="text-red-200/70 text-sm">ค่าคอมมิชชั่นสูง แข่งราคากันเอง</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 bg-red-500/20 rounded-xl p-3">
                            <span class="text-2xl">💬</span>
                            <div>
                                <p class="text-white font-semibold">LINE Official</p>
                                <p class="text-red-200/70 text-sm">ค่าบริการรายเดือน ข้อจำกัดมาก</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 bg-red-500/20 rounded-xl p-3">
                            <span class="text-2xl">🔍</span>
                            <div>
                                <p class="text-white font-semibold">Google Ads</p>
                                <p class="text-red-200/70 text-sm">ค่าคลิกแพง ต้องจ่ายตลอด</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Our Solution -->
                <div class="bg-emerald-900/30 backdrop-blur-md border border-emerald-400/30 rounded-3xl p-6 shadow-2xl">
                    <h3 class="text-2xl font-bold text-emerald-300 mb-4 flex items-center gap-3">
                        <span class="text-3xl">🔓</span> ทางออกของเรา
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 bg-emerald-500/20 rounded-xl p-3">
                            <span class="text-2xl">🌐</span>
                            <div>
                                <p class="text-white font-semibold">เว็บไซต์ของตัวเอง</p>
                                <p class="text-emerald-200/70 text-sm">ไม่ถูกแบน เป็นเจ้าของ 100%</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 bg-emerald-500/20 rounded-xl p-3">
                            <span class="text-2xl">🤖</span>
                            <div>
                                <p class="text-white font-semibold">AI Bot ของตัวเอง</p>
                                <p class="text-emerald-200/70 text-sm">LINE Bot, Web Chat ไม่มีค่ารายเดือน</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 bg-emerald-500/20 rounded-xl p-3">
                            <span class="text-2xl">🛍️</span>
                            <div>
                                <p class="text-white font-semibold">Marketplace ของตัวเอง</p>
                                <p class="text-emerald-200/70 text-sm">ไม่ต้องแข่งราคา กำหนดค่าคอมเอง</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 bg-emerald-500/20 rounded-xl p-3">
                            <span class="text-2xl">📊</span>
                            <div>
                                <p class="text-white font-semibold">Data เป็นของเรา</p>
                                <p class="text-emerald-200/70 text-sm">ลูกค้าเป็นของเรา ไม่ใช่แพลตฟอร์ม</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Stats -->
            <div class="mt-8 grid grid-cols-3 gap-4">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <div class="text-3xl font-black text-amber-300">0%</div>
                    <div class="text-white/70 text-sm">ค่าคอมให้แพลตฟอร์ม</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <div class="text-3xl font-black text-emerald-300">100%</div>
                    <div class="text-white/70 text-sm">เป็นเจ้าของข้อมูล</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <div class="text-3xl font-black text-rose-300">∞</div>
                    <div class="text-white/70 text-sm">อิสรภาพทางธุรกิจ</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 7.1: ระบบ Academy - การเรียนรู้ออนไลน์ -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-violet-900/95 via-purple-900/90 to-indigo-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-10 right-20 w-80 h-80 bg-violet-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 left-20 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s;"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-violet-500/30 to-indigo-500/30 backdrop-blur-md rounded-full border border-white/30 mb-6">
                    <span class="text-3xl">📚</span>
                    <span class="text-white font-bold text-lg">Online Academy System</span>
                </div>
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    🎓 ระบบ Academy <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-300 to-indigo-300">ครบวงจร</span>
                </h2>
                <p class="text-xl text-white/80">สร้างคอร์สออนไลน์ ฝึกอบรมทีม สร้างรายได้จากความรู้</p>
            </div>

            <!-- Academy Features Grid -->
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <!-- Feature 1: Course Management -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 hover:bg-white/15 transition-all group">
                    <div class="w-16 h-16 bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <span class="text-3xl">📖</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">สร้างคอร์สเอง</h3>
                    <ul class="text-white/70 space-y-2 text-sm">
                        <li class="flex items-center gap-2"><span class="text-violet-400">✓</span> วิดีโอ HD ไม่จำกัด</li>
                        <li class="flex items-center gap-2"><span class="text-violet-400">✓</span> เอกสาร PDF/Slides</li>
                        <li class="flex items-center gap-2"><span class="text-violet-400">✓</span> Quiz & แบบทดสอบ</li>
                        <li class="flex items-center gap-2"><span class="text-violet-400">✓</span> Certificate อัตโนมัติ</li>
                    </ul>
                </div>

                <!-- Feature 2: Team Training -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 hover:bg-white/15 transition-all group">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <span class="text-3xl">👥</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">ฝึกอบรมทีม</h3>
                    <ul class="text-white/70 space-y-2 text-sm">
                        <li class="flex items-center gap-2"><span class="text-indigo-400">✓</span> Onboarding อัตโนมัติ</li>
                        <li class="flex items-center gap-2"><span class="text-indigo-400">✓</span> Track Progress ทีม</li>
                        <li class="flex items-center gap-2"><span class="text-indigo-400">✓</span> Leaderboard แข่งขัน</li>
                        <li class="flex items-center gap-2"><span class="text-indigo-400">✓</span> รายงานการเรียน</li>
                    </ul>
                </div>

                <!-- Feature 3: Monetization -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 hover:bg-white/15 transition-all group">
                    <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <span class="text-3xl">💰</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">สร้างรายได้</h3>
                    <ul class="text-white/70 space-y-2 text-sm">
                        <li class="flex items-center gap-2"><span class="text-amber-400">✓</span> ขายคอร์สออนไลน์</li>
                        <li class="flex items-center gap-2"><span class="text-amber-400">✓</span> Membership รายเดือน</li>
                        <li class="flex items-center gap-2"><span class="text-amber-400">✓</span> Affiliate Program</li>
                        <li class="flex items-center gap-2"><span class="text-amber-400">✓</span> Bundle Packages</li>
                    </ul>
                </div>
            </div>

            <!-- Comparison with Other Platforms -->
            <div class="bg-gradient-to-r from-violet-500/20 to-indigo-500/20 backdrop-blur-md border border-white/20 rounded-2xl p-6">
                <h3 class="text-xl font-bold text-white mb-4 text-center">เปรียบเทียบกับแพลตฟอร์มอื่น</h3>
                <div class="grid grid-cols-4 gap-4 text-center text-sm">
                    <div class="text-white/60 font-semibold"></div>
                    <div class="text-white font-bold">TP Academy</div>
                    <div class="text-red-300">Udemy</div>
                    <div class="text-red-300">Teachable</div>

                    <div class="text-white/80 text-left">ค่าคอมมิชชั่น</div>
                    <div class="text-emerald-400 font-bold">0%</div>
                    <div class="text-red-300">37-63%</div>
                    <div class="text-red-300">5-10%</div>

                    <div class="text-white/80 text-left">ค่ารายเดือน</div>
                    <div class="text-emerald-400 font-bold">รวมในแพ็คเกจ</div>
                    <div class="text-red-300">-</div>
                    <div class="text-red-300">$39-249/เดือน</div>

                    <div class="text-white/80 text-left">เจ้าของข้อมูลลูกค้า</div>
                    <div class="text-emerald-400 font-bold">คุณ 100%</div>
                    <div class="text-red-300">Udemy</div>
                    <div class="text-amber-300">แชร์กัน</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 7.2: บริการครบวงจร - ไม่ต้องพึ่งใคร -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-emerald-900/95 via-teal-900/90 to-cyan-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 left-20 w-72 h-72 bg-emerald-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-20 w-96 h-96 bg-cyan-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-8">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    🛡️ บริการครบวงจร <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 to-cyan-300">ในที่เดียว</span>
                </h2>
                <p class="text-xl text-white/80">ไม่ต้องพึ่งพาบริการต่างชาติ ทุกอย่างอยู่ในระบบเดียว</p>
            </div>

            <!-- Services Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <!-- Service 1 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 text-center">
                    <div class="text-4xl mb-3">🌐</div>
                    <h3 class="text-lg font-bold text-white mb-1">Website Builder</h3>
                    <p class="text-white/60 text-sm">สร้างเว็บไซต์สวยงาม ไม่ต้องเขียนโค้ด</p>
                    <div class="mt-2 text-xs text-emerald-400">แทน: Wix, WordPress</div>
                </div>

                <!-- Service 2 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 text-center">
                    <div class="text-4xl mb-3">🛒</div>
                    <h3 class="text-lg font-bold text-white mb-1">E-Commerce</h3>
                    <p class="text-white/60 text-sm">ขายของออนไลน์ครบวงจร</p>
                    <div class="mt-2 text-xs text-emerald-400">แทน: Shopify, WooCommerce</div>
                </div>

                <!-- Service 3 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 text-center">
                    <div class="text-4xl mb-3">🤖</div>
                    <h3 class="text-lg font-bold text-white mb-1">AI Chatbot</h3>
                    <p class="text-white/60 text-sm">บอทตอบลูกค้าอัตโนมัติ</p>
                    <div class="mt-2 text-xs text-emerald-400">แทน: ManyChat, Chatfuel</div>
                </div>

                <!-- Service 4 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 text-center">
                    <div class="text-4xl mb-3">📧</div>
                    <h3 class="text-lg font-bold text-white mb-1">Email Marketing</h3>
                    <p class="text-white/60 text-sm">ส่งอีเมลหาลูกค้าอัตโนมัติ</p>
                    <div class="mt-2 text-xs text-emerald-400">แทน: Mailchimp, SendGrid</div>
                </div>

                <!-- Service 5 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 text-center">
                    <div class="text-4xl mb-3">📊</div>
                    <h3 class="text-lg font-bold text-white mb-1">CRM System</h3>
                    <p class="text-white/60 text-sm">จัดการลูกค้าอย่างเป็นระบบ</p>
                    <div class="mt-2 text-xs text-emerald-400">แทน: HubSpot, Salesforce</div>
                </div>

                <!-- Service 6 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 text-center">
                    <div class="text-4xl mb-3">💳</div>
                    <h3 class="text-lg font-bold text-white mb-1">Payment Gateway</h3>
                    <p class="text-white/60 text-sm">รับชำระเงินทุกช่องทาง</p>
                    <div class="mt-2 text-xs text-emerald-400">แทน: Stripe, PayPal</div>
                </div>

                <!-- Service 7 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 text-center">
                    <div class="text-4xl mb-3">👥</div>
                    <h3 class="text-lg font-bold text-white mb-1">MLM/Affiliate</h3>
                    <p class="text-white/60 text-sm">ระบบสร้างทีมขาย</p>
                    <div class="mt-2 text-xs text-emerald-400">แทน: Post Affiliate, Tapfiliate</div>
                </div>

                <!-- Service 8 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 text-center">
                    <div class="text-4xl mb-3">📈</div>
                    <h3 class="text-lg font-bold text-white mb-1">Analytics</h3>
                    <p class="text-white/60 text-sm">วิเคราะห์ข้อมูลธุรกิจ</p>
                    <div class="mt-2 text-xs text-emerald-400">แทน: Google Analytics</div>
                </div>
            </div>

            <!-- Cost Comparison -->
            <div class="bg-gradient-to-r from-emerald-500/20 to-cyan-500/20 backdrop-blur-md border border-white/20 rounded-2xl p-6">
                <div class="grid md:grid-cols-2 gap-6 items-center">
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-2">ประหยัดค่าใช้จ่ายมหาศาล</h3>
                        <p class="text-white/70">ถ้าใช้บริการต่างชาติแยกกัน คุณต้องจ่ายรายเดือนให้แต่ละบริการ</p>
                    </div>
                    <div class="flex items-center justify-center gap-6">
                        <div class="text-center">
                            <div class="text-red-400 line-through text-xl">฿50,000+</div>
                            <div class="text-white/60 text-sm">ถ้าซื้อแยก/เดือน</div>
                        </div>
                        <div class="text-4xl text-white">→</div>
                        <div class="text-center">
                            <div class="text-emerald-400 text-3xl font-black">รวมในแพ็คเกจ</div>
                            <div class="text-white/60 text-sm">ครบทุกบริการ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 7.3: สรุปประโยชน์ - ทำไมต้องปลดแอก -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-amber-900/95 via-orange-900/90 to-rose-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-10 left-10 w-96 h-96 bg-amber-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-10 w-80 h-80 bg-rose-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.7s;"></div>
            <!-- Celebration Effects -->
            <div class="absolute top-1/4 left-1/4 text-6xl animate-bounce" style="animation-duration: 2s;">🎉</div>
            <div class="absolute top-1/3 right-1/4 text-5xl animate-bounce" style="animation-duration: 2.5s; animation-delay: 0.5s;">🚀</div>
            <div class="absolute bottom-1/4 left-1/3 text-5xl animate-bounce" style="animation-duration: 1.8s; animation-delay: 0.3s;">💎</div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-8">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    🎯 ทำไมต้อง<span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 to-rose-300">ปลดแอก</span>ธุรกิจ?
                </h2>
                <p class="text-xl text-white/80">ความแตกต่างที่จะเปลี่ยนธุรกิจคุณตลอดไป</p>
            </div>

            <!-- Benefits Grid -->
            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <!-- Benefit 1 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 flex gap-4">
                    <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center text-3xl">
                        🔒
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white mb-2">ความมั่นคงถาวร</h3>
                        <p class="text-white/70">ไม่ถูกแบน ไม่ถูกลบ ไม่ถูกเปลี่ยนกฎกลางทาง ธุรกิจคุณปลอดภัยตลอดไป</p>
                    </div>
                </div>

                <!-- Benefit 2 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 flex gap-4">
                    <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center text-3xl">
                        💵
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white mb-2">ประหยัดค่าใช้จ่าย</h3>
                        <p class="text-white/70">ไม่ต้องจ่ายค่าคอมให้แพลตฟอร์ม ไม่ต้องจ่ายค่าโฆษณาแพง กำไรเพิ่มขึ้น</p>
                    </div>
                </div>

                <!-- Benefit 3 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 flex gap-4">
                    <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl flex items-center justify-center text-3xl">
                        👥
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white mb-2">ลูกค้าเป็นของคุณ</h3>
                        <p class="text-white/70">Data ลูกค้าอยู่กับคุณ ติดต่อได้ตลอด ไม่ต้องจ่ายเงินเพื่อเข้าถึงลูกค้าตัวเอง</p>
                    </div>
                </div>

                <!-- Benefit 4 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 flex gap-4">
                    <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl flex items-center justify-center text-3xl">
                        🎨
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white mb-2">ควบคุมได้เต็มที่</h3>
                        <p class="text-white/70">ปรับแต่ง Branding ได้ตามต้องการ ไม่มีโลโก้แพลตฟอร์มอื่นบนหน้าเว็บคุณ</p>
                    </div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="bg-gradient-to-r from-amber-500/30 to-rose-500/30 backdrop-blur-md border border-white/30 rounded-3xl p-8 text-center">
                <h3 class="text-3xl font-black text-white mb-4">🦅 เริ่มต้นอิสรภาพทางธุรกิจวันนี้</h3>
                <p class="text-xl text-white/80 mb-6">
                    อย่าปล่อยให้แพลตฟอร์มต่างชาติกำหนดชะตาธุรกิจของคุณ
                    <br>สร้างอาณาจักรของคุณเอง ที่ไม่มีใครมาแย่งไปได้
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <div class="flex items-center gap-2 bg-white/20 px-4 py-2 rounded-full">
                        <span class="text-emerald-400">✓</span>
                        <span class="text-white">เริ่มต้นง่าย</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/20 px-4 py-2 rounded-full">
                        <span class="text-emerald-400">✓</span>
                        <span class="text-white">มีทีมช่วย Setup</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/20 px-4 py-2 rounded-full">
                        <span class="text-emerald-400">✓</span>
                        <span class="text-white">Academy ฝึกใช้งาน</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/20 px-4 py-2 rounded-full">
                        <span class="text-emerald-400">✓</span>
                        <span class="text-white">Support 24/7</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 8: Ecosystem & Vision - ภาพรวมระบบนิเวศ -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-slate-900/95 via-indigo-900/90 to-violet-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 left-10 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-10 w-80 h-80 bg-violet-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-indigo-500/30 to-violet-500/30 backdrop-blur-md rounded-full border border-white/30 mb-6">
                    <span class="text-3xl">🌍</span>
                    <span class="text-white font-bold text-lg">Complete Ecosystem</span>
                </div>
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    ระบบนิเวศ <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 to-violet-300">ครบวงจร</span>
                </h2>
                <p class="text-xl text-white/80">มากกว่าแพลตฟอร์ม คือ Ecosystem ที่ช่วยให้คุณเติบโต</p>
            </div>

            <!-- Why Ecosystem Matters -->
            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6">
                    <h3 class="text-2xl font-bold text-white mb-4 flex items-center gap-3">
                        <span class="text-3xl">❌</span>
                        <span>แพลตฟอร์มแยกส่วน</span>
                    </h3>
                    <div class="space-y-3 text-red-200/80">
                        <div class="flex items-center gap-3">
                            <span>💸</span> จ่ายค่าบริการหลายที่ แพงมาก
                        </div>
                        <div class="flex items-center gap-3">
                            <span>🔄</span> ข้อมูลไม่เชื่อมกัน ต้อง sync เอง
                        </div>
                        <div class="flex items-center gap-3">
                            <span>⏰</span> เสียเวลาเรียนรู้หลายระบบ
                        </div>
                        <div class="flex items-center gap-3">
                            <span>🤯</span> จัดการยุ่งยาก วุ่นวาย
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-emerald-500/20 to-green-500/20 backdrop-blur-md border border-emerald-400/30 rounded-2xl p-6">
                    <h3 class="text-2xl font-bold text-white mb-4 flex items-center gap-3">
                        <span class="text-3xl">✅</span>
                        <span>TP-Affiliate Ecosystem</span>
                    </h3>
                    <div class="space-y-3 text-emerald-200/80">
                        <div class="flex items-center gap-3">
                            <span>💰</span> จ่ายที่เดียว ครบทุกระบบ
                        </div>
                        <div class="flex items-center gap-3">
                            <span>🔗</span> ข้อมูลเชื่อมกัน Real-time
                        </div>
                        <div class="flex items-center gap-3">
                            <span>🎯</span> UI เดียว เรียนรู้ง่าย
                        </div>
                        <div class="flex items-center gap-3">
                            <span>🚀</span> Synergy ช่วยให้เติบโตเร็ว
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Stats -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <div class="text-4xl font-black text-indigo-300">20+</div>
                    <div class="text-white/70 text-sm">ระบบในหนึ่งเดียว</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <div class="text-4xl font-black text-violet-300">100%</div>
                    <div class="text-white/70 text-sm">Data Integration</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <div class="text-4xl font-black text-purple-300">1</div>
                    <div class="text-white/70 text-sm">Dashboard เดียว</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 8.1: Ecosystem - ระบบที่เชื่อมต่อ -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-slate-900/95 via-indigo-900/90 to-violet-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-10 right-10 w-80 h-80 bg-indigo-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 left-10 w-96 h-96 bg-violet-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s;"></div>
            <!-- Network Lines -->
            <svg class="absolute inset-0 w-full h-full opacity-10" viewBox="0 0 100 100" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="line-gradient-8-1" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#818cf8;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#c084fc;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <line x1="10" y1="30" x2="50" y2="50" stroke="url(#line-gradient-8-1)" stroke-width="0.3"/>
                <line x1="90" y1="20" x2="50" y2="50" stroke="url(#line-gradient-8-1)" stroke-width="0.3"/>
                <line x1="20" y1="80" x2="50" y2="50" stroke="url(#line-gradient-8-1)" stroke-width="0.3"/>
                <line x1="80" y1="70" x2="50" y2="50" stroke="url(#line-gradient-8-1)" stroke-width="0.3"/>
                <line x1="50" y1="10" x2="50" y2="50" stroke="url(#line-gradient-8-1)" stroke-width="0.3"/>
                <line x1="50" y1="90" x2="50" y2="50" stroke="url(#line-gradient-8-1)" stroke-width="0.3"/>
            </svg>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-indigo-500/30 to-violet-500/30 backdrop-blur-md rounded-full border border-white/30 mb-4">
                    <span class="text-3xl">🔗</span>
                    <span class="text-white font-bold text-lg">Connected Systems</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white mb-2">
                    6 ระบบหลัก <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 to-violet-300">เชื่อมต่อกัน</span>
                </h2>
            </div>

            <!-- Center Hub Diagram -->
            <div class="relative mb-6">
                <div class="flex justify-center mb-6">
                    <div class="w-24 h-24 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-full flex items-center justify-center shadow-2xl shadow-indigo-500/50 border-4 border-white/30">
                        <div class="text-center">
                            <div class="text-2xl">🏢</div>
                            <div class="text-white font-bold text-xs">TP-Affiliate</div>
                        </div>
                    </div>
                </div>

                <!-- Connected Systems Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="bg-gradient-to-br from-blue-500/20 to-blue-600/10 backdrop-blur-md border border-blue-400/30 rounded-2xl p-5 hover:scale-105 transition-transform">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="text-3xl">👥</div>
                            <div class="text-lg font-bold text-white">MLM System</div>
                        </div>
                        <div class="text-white/70 text-sm space-y-1">
                            <div>• Unilevel 10 ชั้น (23.5%)</div>
                            <div>• 8 Rank + โบนัส</div>
                            <div>• คอมมิชชั่นอัตโนมัติ</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-emerald-500/20 to-green-600/10 backdrop-blur-md border border-emerald-400/30 rounded-2xl p-5 hover:scale-105 transition-transform">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="text-3xl">🛒</div>
                            <div class="text-lg font-bold text-white">E-Commerce</div>
                        </div>
                        <div class="text-white/70 text-sm space-y-1">
                            <div>• Multi-Vendor Marketplace</div>
                            <div>• Dropshipping</div>
                            <div>• POS & Hotel Booking</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-purple-500/20 to-purple-600/10 backdrop-blur-md border border-purple-400/30 rounded-2xl p-5 hover:scale-105 transition-transform">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="text-3xl">🤖</div>
                            <div class="text-lg font-bold text-white">AI & Automation</div>
                        </div>
                        <div class="text-white/70 text-sm space-y-1">
                            <div>• LINE AI Bot</div>
                            <div>• ChatGPT/Claude/Gemini</div>
                            <div>• ตอบลูกค้า 24/7</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-amber-500/20 to-orange-600/10 backdrop-blur-md border border-amber-400/30 rounded-2xl p-5 hover:scale-105 transition-transform">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="text-3xl">🪙</div>
                            <div class="text-lg font-bold text-white">TPIX Token</div>
                        </div>
                        <div class="text-white/70 text-sm space-y-1">
                            <div>• Blockchain Polygon</div>
                            <div>• Staking 24% APY</div>
                            <div>• Governance Voting</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-rose-500/20 to-pink-600/10 backdrop-blur-md border border-rose-400/30 rounded-2xl p-5 hover:scale-105 transition-transform">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="text-3xl">🎓</div>
                            <div class="text-lg font-bold text-white">Academy</div>
                        </div>
                        <div class="text-white/70 text-sm space-y-1">
                            <div>• LMS ครบวงจร</div>
                            <div>• ขายคอร์สออนไลน์</div>
                            <div>• ใบ Certificate</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-cyan-500/20 to-teal-600/10 backdrop-blur-md border border-cyan-400/30 rounded-2xl p-5 hover:scale-105 transition-transform">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="text-3xl">💰</div>
                            <div class="text-lg font-bold text-white">Multi-Wallet</div>
                        </div>
                        <div class="text-white/70 text-sm space-y-1">
                            <div>• THB, USD, TPIX, Points</div>
                            <div>• ฝาก/ถอนอัตโนมัติ</div>
                            <div>• 10+ ช่องทางชำระ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 8.2: Ecosystem - Synergy Effects -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-slate-900/95 via-indigo-900/90 to-violet-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-1/4 left-1/4 w-80 h-80 bg-emerald-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.7s;"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-emerald-500/30 to-indigo-500/30 backdrop-blur-md rounded-full border border-white/30 mb-4">
                    <span class="text-3xl">⚡</span>
                    <span class="text-white font-bold text-lg">Synergy Power</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white mb-2">
                    พลังทวีคูณ <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 to-indigo-300">1 + 1 = 10</span>
                </h2>
            </div>

            <!-- Synergy Examples -->
            <div class="space-y-4">
                <!-- Synergy 1 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5">
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <div class="bg-blue-500/30 px-4 py-2 rounded-full text-white font-bold">👥 MLM</div>
                        <span class="text-2xl text-white">+</span>
                        <div class="bg-emerald-500/30 px-4 py-2 rounded-full text-white font-bold">🛒 E-Commerce</div>
                        <span class="text-2xl text-white">=</span>
                        <div class="bg-gradient-to-r from-blue-500 to-emerald-500 px-4 py-2 rounded-full text-white font-bold">💎 ขายสินค้าได้ค่าแนะนำด้วย!</div>
                    </div>
                    <p class="text-white/70 text-sm text-center mt-3">ทีมซื้อสินค้า → ได้ PV → ได้คอมมิชชั่น Unilevel 10 ชั้น</p>
                </div>

                <!-- Synergy 2 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5">
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <div class="bg-purple-500/30 px-4 py-2 rounded-full text-white font-bold">🤖 AI Bot</div>
                        <span class="text-2xl text-white">+</span>
                        <div class="bg-blue-500/30 px-4 py-2 rounded-full text-white font-bold">👥 MLM</div>
                        <span class="text-2xl text-white">=</span>
                        <div class="bg-gradient-to-r from-purple-500 to-blue-500 px-4 py-2 rounded-full text-white font-bold">🚀 รับสมัครอัตโนมัติ 24/7!</div>
                    </div>
                    <p class="text-white/70 text-sm text-center mt-3">LINE Bot ตอบคำถาม → ปิดการขาย → สมัครสมาชิกเอง</p>
                </div>

                <!-- Synergy 3 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5">
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <div class="bg-amber-500/30 px-4 py-2 rounded-full text-white font-bold">🪙 TPIX</div>
                        <span class="text-2xl text-white">+</span>
                        <div class="bg-cyan-500/30 px-4 py-2 rounded-full text-white font-bold">💰 Wallet</div>
                        <span class="text-2xl text-white">=</span>
                        <div class="bg-gradient-to-r from-amber-500 to-cyan-500 px-4 py-2 rounded-full text-white font-bold">📈 รายได้ Passive จาก Staking!</div>
                    </div>
                    <p class="text-white/70 text-sm text-center mt-3">คอมมิชชั่นเข้า Wallet → แปลงเป็น TPIX → Stake 24% APY</p>
                </div>

                <!-- Synergy 4 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5">
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <div class="bg-rose-500/30 px-4 py-2 rounded-full text-white font-bold">🎓 Academy</div>
                        <span class="text-2xl text-white">+</span>
                        <div class="bg-blue-500/30 px-4 py-2 rounded-full text-white font-bold">👥 MLM</div>
                        <span class="text-2xl text-white">=</span>
                        <div class="bg-gradient-to-r from-rose-500 to-blue-500 px-4 py-2 rounded-full text-white font-bold">🏆 ทีมเก่งขึ้น ขายได้มากขึ้น!</div>
                    </div>
                    <p class="text-white/70 text-sm text-center mt-3">สร้างคอร์สฝึกทีม → ทีมเก่งขึ้น → ยอดขายพุ่ง</p>
                </div>
            </div>

            <!-- Bottom CTA -->
            <div class="mt-6 text-center">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-indigo-500/30 to-emerald-500/30 backdrop-blur-md rounded-full border border-white/30">
                    <span class="text-2xl">💡</span>
                    <span class="text-white font-bold">ใช้ได้ครบ ยิ่งได้เปรียบ!</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 8.3: Ecosystem - Roadmap 2025-2027 -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-slate-900/95 via-indigo-900/90 to-violet-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-10 left-20 w-80 h-80 bg-indigo-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-violet-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s;"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-indigo-500/30 to-violet-500/30 backdrop-blur-md rounded-full border border-white/30 mb-4">
                    <span class="text-3xl">🗺️</span>
                    <span class="text-white font-bold text-lg">Vision & Roadmap</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white mb-2">
                    เส้นทางสู่ <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 to-violet-300">10 ล้านผู้ใช้</span>
                </h2>
            </div>

            <!-- Timeline -->
            <div class="relative">
                <!-- Timeline Line -->
                <div class="absolute top-1/2 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 to-violet-500 transform -translate-y-1/2 hidden md:block"></div>

                <div class="grid md:grid-cols-3 gap-6">
                    <!-- 2025 -->
                    <div class="bg-gradient-to-br from-indigo-500/20 to-indigo-600/10 backdrop-blur-md border border-indigo-400/30 rounded-2xl p-6 relative">
                        <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-indigo-500 text-white font-black px-4 py-1 rounded-full text-sm">2025</div>
                        <div class="pt-4">
                            <div class="text-center mb-4">
                                <div class="text-5xl font-black text-indigo-300">Q1-Q4</div>
                                <div class="text-white/70 text-sm">Foundation Year</div>
                            </div>
                            <div class="space-y-3 text-sm">
                                <div class="flex items-center gap-3 text-white/80">
                                    <span class="text-emerald-400">✓</span>
                                    <span>📱 Mobile App (iOS & Android)</span>
                                </div>
                                <div class="flex items-center gap-3 text-white/80">
                                    <span class="text-emerald-400">✓</span>
                                    <span>🪙 TPIX DEX Launch</span>
                                </div>
                                <div class="flex items-center gap-3 text-white/80">
                                    <span class="text-emerald-400">✓</span>
                                    <span>🤖 AI Agent V2</span>
                                </div>
                                <div class="flex items-center gap-3 text-white/80">
                                    <span class="text-emerald-400">✓</span>
                                    <span>👥 100,000 Users</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2026 -->
                    <div class="bg-gradient-to-br from-violet-500/20 to-violet-600/10 backdrop-blur-md border border-violet-400/30 rounded-2xl p-6 relative">
                        <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-violet-500 text-white font-black px-4 py-1 rounded-full text-sm">2026</div>
                        <div class="pt-4">
                            <div class="text-center mb-4">
                                <div class="text-5xl font-black text-violet-300">Q1-Q4</div>
                                <div class="text-white/70 text-sm">Expansion Year</div>
                            </div>
                            <div class="space-y-3 text-sm">
                                <div class="flex items-center gap-3 text-white/80">
                                    <span class="text-amber-400">🔜</span>
                                    <span>🌏 Global Expansion (SEA)</span>
                                </div>
                                <div class="flex items-center gap-3 text-white/80">
                                    <span class="text-amber-400">🔜</span>
                                    <span>🤖 AI Agent Pro (Voice AI)</span>
                                </div>
                                <div class="flex items-center gap-3 text-white/80">
                                    <span class="text-amber-400">🔜</span>
                                    <span>🏦 Crypto Exchange</span>
                                </div>
                                <div class="flex items-center gap-3 text-white/80">
                                    <span class="text-amber-400">🔜</span>
                                    <span>👥 1,000,000 Users</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2027 -->
                    <div class="bg-gradient-to-br from-purple-500/20 to-purple-600/10 backdrop-blur-md border border-purple-400/30 rounded-2xl p-6 relative">
                        <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-purple-500 text-white font-black px-4 py-1 rounded-full text-sm">2027</div>
                        <div class="pt-4">
                            <div class="text-center mb-4">
                                <div class="text-5xl font-black text-purple-300">Q1-Q4</div>
                                <div class="text-white/70 text-sm">Decentralization Year</div>
                            </div>
                            <div class="space-y-3 text-sm">
                                <div class="flex items-center gap-3 text-white/80">
                                    <span class="text-purple-400">🔮</span>
                                    <span>🏪 Metaverse Store</span>
                                </div>
                                <div class="flex items-center gap-3 text-white/80">
                                    <span class="text-purple-400">🔮</span>
                                    <span>🗳️ DAO Governance</span>
                                </div>
                                <div class="flex items-center gap-3 text-white/80">
                                    <span class="text-purple-400">🔮</span>
                                    <span>🌐 Global 50+ Countries</span>
                                </div>
                                <div class="flex items-center gap-3 text-white/80">
                                    <span class="text-purple-400">🔮</span>
                                    <span>👥 10,000,000 Users</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vision Statement -->
            <div class="mt-8 bg-gradient-to-r from-indigo-500/20 to-violet-500/20 backdrop-blur-md border border-white/20 rounded-2xl p-6 text-center">
                <p class="text-xl md:text-2xl text-white font-bold">
                    🚀 "สร้างโอกาสให้ทุกคนเป็นเจ้าของธุรกิจดิจิทัลได้ง่ายขึ้น"
                </p>
                <p class="text-white/70 mt-2">เข้าร่วมตั้งแต่วันนี้ เติบโตไปด้วยกัน</p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 9: พลิกเกมส์ธุรกิจ - ภาพรวม -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-amber-900/95 via-yellow-900/90 to-orange-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-10 left-10 w-96 h-96 bg-amber-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-10 w-80 h-80 bg-orange-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s;"></div>
            <!-- Sparkle Effects -->
            <div class="absolute top-1/4 right-1/4 text-4xl animate-bounce" style="animation-duration: 2s;">💎</div>
            <div class="absolute bottom-1/3 left-1/4 text-3xl animate-bounce" style="animation-duration: 2.5s; animation-delay: 0.3s;">✨</div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-amber-500/30 to-orange-500/30 backdrop-blur-md rounded-full border border-white/30 mb-6">
                    <span class="text-3xl">🎯</span>
                    <span class="text-white font-bold text-lg">Game Changer</span>
                </div>
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    💎 <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 to-orange-300">พลิกเกมส์</span>ธุรกิจ
                </h2>
                <p class="text-xl text-white/80">ทำไมถึงต้องเปลี่ยนวิธีทำธุรกิจแบบเดิม?</p>
            </div>

            <!-- The Problem -->
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 mb-6">
                <h3 class="text-2xl font-bold text-white mb-4 text-center">🤔 ธุรกิจแบบเดิมมันยากขึ้นทุกวัน</h3>
                <div class="grid md:grid-cols-3 gap-4 text-center">
                    <div class="bg-red-500/20 border border-red-400/30 rounded-xl p-4">
                        <div class="text-4xl mb-2">📈</div>
                        <div class="text-white font-bold">ค่าเช่าพุ่ง</div>
                        <div class="text-red-200/80 text-sm">30-50% ของรายได้</div>
                    </div>
                    <div class="bg-red-500/20 border border-red-400/30 rounded-xl p-4">
                        <div class="text-4xl mb-2">👷</div>
                        <div class="text-white font-bold">หาคนยาก</div>
                        <div class="text-red-200/80 text-sm">ลาออกบ่อย เทรนใหม่ตลอด</div>
                    </div>
                    <div class="bg-red-500/20 border border-red-400/30 rounded-xl p-4">
                        <div class="text-4xl mb-2">🏪</div>
                        <div class="text-white font-bold">แข่งขันสูง</div>
                        <div class="text-red-200/80 text-sm">คู่แข่งทุกมุมเมือง</div>
                    </div>
                </div>
            </div>

            <!-- The Solution -->
            <div class="bg-gradient-to-r from-emerald-500/20 to-green-500/20 backdrop-blur-md border border-emerald-400/30 rounded-2xl p-6">
                <h3 class="text-2xl font-bold text-white mb-4 text-center">💡 ทางออกคือ... ธุรกิจดิจิทัล!</h3>
                <div class="grid md:grid-cols-4 gap-4 text-center">
                    <div class="bg-white/10 rounded-xl p-4">
                        <div class="text-3xl mb-2">🏠</div>
                        <div class="text-white font-bold text-sm">ทำที่บ้านได้</div>
                    </div>
                    <div class="bg-white/10 rounded-xl p-4">
                        <div class="text-3xl mb-2">🌍</div>
                        <div class="text-white font-bold text-sm">ขายทั่วโลก</div>
                    </div>
                    <div class="bg-white/10 rounded-xl p-4">
                        <div class="text-3xl mb-2">🤖</div>
                        <div class="text-white font-bold text-sm">AI ช่วยงาน</div>
                    </div>
                    <div class="bg-white/10 rounded-xl p-4">
                        <div class="text-3xl mb-2">📊</div>
                        <div class="text-white font-bold text-sm">ขยายได้ไม่จำกัด</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 9.1: พลิกเกมส์ - ปัญหาธุรกิจแบบเดิม -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-amber-900/95 via-yellow-900/90 to-orange-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 right-20 w-80 h-80 bg-red-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 left-20 w-96 h-96 bg-amber-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s;"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-red-500/30 to-amber-500/30 backdrop-blur-md rounded-full border border-white/30 mb-4">
                    <span class="text-3xl">❌</span>
                    <span class="text-white font-bold text-lg">The Old Way</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white mb-2">
                    ปัญหาของ <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-300 to-amber-300">ธุรกิจแบบเดิม</span>
                </h2>
            </div>

            <!-- Problems Grid -->
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-red-900/30 backdrop-blur-md border border-red-400/30 rounded-2xl p-5">
                    <div class="flex items-start gap-4">
                        <div class="text-4xl">💸</div>
                        <div>
                            <h4 class="text-lg font-bold text-red-300">ลงทุนสูงมาก</h4>
                            <div class="text-red-200/80 text-sm mt-2 space-y-1">
                                <div>• ค่าเช่าร้าน: 30,000-200,000 บาท/เดือน</div>
                                <div>• ค่าตกแต่ง: 500,000-2,000,000 บาท</div>
                                <div>• สต็อกสินค้า: 200,000-1,000,000 บาท</div>
                                <div>• เครื่องมืออุปกรณ์: 100,000-500,000 บาท</div>
                            </div>
                            <div class="mt-3 text-white font-bold bg-red-500/30 px-3 py-1 rounded-full text-sm inline-block">
                                รวม: 1-4 ล้านบาท ขั้นต่ำ!
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-red-900/30 backdrop-blur-md border border-red-400/30 rounded-2xl p-5">
                    <div class="flex items-start gap-4">
                        <div class="text-4xl">⏰</div>
                        <div>
                            <h4 class="text-lg font-bold text-red-300">คืนทุนช้า</h4>
                            <div class="text-red-200/80 text-sm mt-2 space-y-1">
                                <div>• กำไรเดือนละ 30,000-100,000 บาท</div>
                                <div>• จ่ายค่าเช่า + เงินเดือน + ค่าใช้จ่าย</div>
                                <div>• เหลือกำไรสุทธิ 10-20%</div>
                                <div>• คืนทุน 3-5 ปี (ถ้าไม่เจ๊ง)</div>
                            </div>
                            <div class="mt-3 text-white font-bold bg-red-500/30 px-3 py-1 rounded-full text-sm inline-block">
                                ROI: 20-30% ต่อปี (ดีที่สุด)
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-red-900/30 backdrop-blur-md border border-red-400/30 rounded-2xl p-5">
                    <div class="flex items-start gap-4">
                        <div class="text-4xl">👷</div>
                        <div>
                            <h4 class="text-lg font-bold text-red-300">ปัญหาคน</h4>
                            <div class="text-red-200/80 text-sm mt-2 space-y-1">
                                <div>• หาคนทำงานยาก Gen Z ไม่อยากทำ</div>
                                <div>• เงินเดือนพนักงาน 15,000-30,000 บาท/คน</div>
                                <div>• ลาออกบ่อย ต้องเทรนใหม่ตลอด</div>
                                <div>• ขโมย/ทุจริต เกิดขึ้นบ่อย</div>
                            </div>
                            <div class="mt-3 text-white font-bold bg-red-500/30 px-3 py-1 rounded-full text-sm inline-block">
                                ปวดหัว + เสียเงิน + เสียเวลา
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-red-900/30 backdrop-blur-md border border-red-400/30 rounded-2xl p-5">
                    <div class="flex items-start gap-4">
                        <div class="text-4xl">📍</div>
                        <div>
                            <h4 class="text-lg font-bold text-red-300">ข้อจำกัดพื้นที่</h4>
                            <div class="text-red-200/80 text-sm mt-2 space-y-1">
                                <div>• ลูกค้าแค่รัศมี 5-10 กม.</div>
                                <div>• ขึ้นกับทำเลที่ตั้ง</div>
                                <div>• คู่แข่งเปิดใกล้ๆ ก็จบ</div>
                                <div>• ขยายสาขา = ลงทุนเพิ่มอีกหลายล้าน</div>
                            </div>
                            <div class="mt-3 text-white font-bold bg-red-500/30 px-3 py-1 rounded-full text-sm inline-block">
                                เพดานการเติบโตจำกัด!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 9.2: พลิกเกมส์ - ทางออกกับเรา -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-amber-900/95 via-yellow-900/90 to-orange-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-10 left-10 w-96 h-96 bg-emerald-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-10 w-80 h-80 bg-amber-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s;"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-emerald-500/30 to-amber-500/30 backdrop-blur-md rounded-full border border-white/30 mb-4">
                    <span class="text-3xl">✅</span>
                    <span class="text-white font-bold text-lg">The New Way</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white mb-2">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 to-amber-300">ลงทุนกับเรา</span> ต่างกันอย่างไร?
                </h2>
            </div>

            <!-- Benefits Grid -->
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-emerald-900/30 backdrop-blur-md border border-emerald-400/30 rounded-2xl p-5">
                    <div class="flex items-start gap-4">
                        <div class="text-4xl">💰</div>
                        <div>
                            <h4 class="text-lg font-bold text-emerald-300">ลงทุนน้อย เริ่มได้ทันที</h4>
                            <div class="text-emerald-200/80 text-sm mt-2 space-y-1">
                                <div>• แพ็คเกจเริ่มต้น: 5,000-30,000 บาท</div>
                                <div>• ไม่ต้องเช่าร้าน/ตกแต่ง</div>
                                <div>• ไม่ต้องสต็อกสินค้า (Dropshipping)</div>
                                <div>• ระบบพร้อมใช้งานทันที</div>
                            </div>
                            <div class="mt-3 text-white font-bold bg-emerald-500/30 px-3 py-1 rounded-full text-sm inline-block">
                                ลงทุนแค่ 1-5% ของธุรกิจเดิม!
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-emerald-900/30 backdrop-blur-md border border-emerald-400/30 rounded-2xl p-5">
                    <div class="flex items-start gap-4">
                        <div class="text-4xl">🚀</div>
                        <div>
                            <h4 class="text-lg font-bold text-emerald-300">เห็นผลเร็ว ROI สูง</h4>
                            <div class="text-emerald-200/80 text-sm mt-2 space-y-1">
                                <div>• เริ่มขายได้ภายใน 1 วัน</div>
                                <div>• คอมมิชชั่นเข้าทุกการขาย</div>
                                <div>• Passive Income จากทีม</div>
                                <div>• Staking TPIX 24% APY</div>
                            </div>
                            <div class="mt-3 text-white font-bold bg-emerald-500/30 px-3 py-1 rounded-full text-sm inline-block">
                                ROI: 100-500%+ ต่อปี!
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-emerald-900/30 backdrop-blur-md border border-emerald-400/30 rounded-2xl p-5">
                    <div class="flex items-start gap-4">
                        <div class="text-4xl">🤖</div>
                        <div>
                            <h4 class="text-lg font-bold text-emerald-300">AI ทำงานแทน</h4>
                            <div class="text-emerald-200/80 text-sm mt-2 space-y-1">
                                <div>• LINE Bot ตอบลูกค้า 24/7</div>
                                <div>• รับออเดอร์อัตโนมัติ</div>
                                <div>• ปิดการขายได้เอง</div>
                                <div>• ไม่ต้องจ้างพนักงาน</div>
                            </div>
                            <div class="mt-3 text-white font-bold bg-emerald-500/30 px-3 py-1 rounded-full text-sm inline-block">
                                ทำงานนอนก็ได้เงิน!
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-emerald-900/30 backdrop-blur-md border border-emerald-400/30 rounded-2xl p-5">
                    <div class="flex items-start gap-4">
                        <div class="text-4xl">🌍</div>
                        <div>
                            <h4 class="text-lg font-bold text-emerald-300">ตลาดไร้พรมแดน</h4>
                            <div class="text-emerald-200/80 text-sm mt-2 space-y-1">
                                <div>• ขายได้ทั่วประเทศ ทั่วโลก</div>
                                <div>• ไม่ขึ้นกับทำเล</div>
                                <div>• ขยายทีมได้ไม่จำกัด</div>
                                <div>• รายได้โตแบบ Exponential</div>
                            </div>
                            <div class="mt-3 text-white font-bold bg-emerald-500/30 px-3 py-1 rounded-full text-sm inline-block">
                                ไม่มีเพดาน ไม่มีขีดจำกัด!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 9.3: พลิกเกมส์ - หลายช่องทางรายได้ -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-amber-900/95 via-yellow-900/90 to-orange-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 left-20 w-80 h-80 bg-amber-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-orange-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s;"></div>
            <div class="absolute top-1/4 right-1/4 text-4xl animate-bounce" style="animation-duration: 2s;">💰</div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-amber-500/30 to-orange-500/30 backdrop-blur-md rounded-full border border-white/30 mb-4">
                    <span class="text-3xl">💎</span>
                    <span class="text-white font-bold text-lg">Multiple Income Streams</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white mb-2">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 to-orange-300">7+ ช่องทาง</span>รายได้ในที่เดียว
                </h2>
            </div>

            <!-- Income Streams -->
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center hover:scale-105 transition-transform">
                    <div class="text-4xl mb-2">🛒</div>
                    <h4 class="text-white font-bold">ขายสินค้า</h4>
                    <p class="text-amber-300 text-sm">E-Commerce</p>
                    <div class="text-white/70 text-xs mt-2">กำไร 20-50% ต่อชิ้น</div>
                </div>

                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center hover:scale-105 transition-transform">
                    <div class="text-4xl mb-2">👥</div>
                    <h4 class="text-white font-bold">Unilevel</h4>
                    <p class="text-amber-300 text-sm">10 ชั้น 23.5%</p>
                    <div class="text-white/70 text-xs mt-2">รายได้จากทีม</div>
                </div>

                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center hover:scale-105 transition-transform">
                    <div class="text-4xl mb-2">🏆</div>
                    <h4 class="text-white font-bold">Rank Bonus</h4>
                    <p class="text-amber-300 text-sm">5-35%</p>
                    <div class="text-white/70 text-xs mt-2">โบนัสตำแหน่ง</div>
                </div>

                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center hover:scale-105 transition-transform">
                    <div class="text-4xl mb-2">🎓</div>
                    <h4 class="text-white font-bold">ขายคอร์ส</h4>
                    <p class="text-amber-300 text-sm">Academy</p>
                    <div class="text-white/70 text-xs mt-2">กำไร 50-80%</div>
                </div>

                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center hover:scale-105 transition-transform">
                    <div class="text-4xl mb-2">🪙</div>
                    <h4 class="text-white font-bold">Staking</h4>
                    <p class="text-amber-300 text-sm">TPIX Token</p>
                    <div class="text-white/70 text-xs mt-2">24% APY</div>
                </div>

                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center hover:scale-105 transition-transform">
                    <div class="text-4xl mb-2">🤖</div>
                    <h4 class="text-white font-bold">ขาย AI Bot</h4>
                    <p class="text-amber-300 text-sm">Marketplace</p>
                    <div class="text-white/70 text-xs mt-2">กำไร 30-70%</div>
                </div>

                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center hover:scale-105 transition-transform">
                    <div class="text-4xl mb-2">🏨</div>
                    <h4 class="text-white font-bold">จองโรงแรม</h4>
                    <p class="text-amber-300 text-sm">Hotel Affiliate</p>
                    <div class="text-white/70 text-xs mt-2">5-15% Commission</div>
                </div>

                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center hover:scale-105 transition-transform">
                    <div class="text-4xl mb-2">📦</div>
                    <h4 class="text-white font-bold">Dropship</h4>
                    <p class="text-amber-300 text-sm">ไม่ต้องสต็อก</p>
                    <div class="text-white/70 text-xs mt-2">กำไร 15-40%</div>
                </div>
            </div>

            <!-- CTA -->
            <div class="text-center">
                <div class="inline-flex items-center gap-4 px-8 py-4 bg-gradient-to-r from-amber-500 to-orange-500 rounded-2xl shadow-2xl hover:scale-105 transition-transform">
                    <span class="text-3xl">🚀</span>
                    <div class="text-left">
                        <div class="text-white font-black text-xl">พร้อมพลิกเกมส์?</div>
                        <div class="text-white/80 text-sm">เริ่มต้นวันนี้ รายได้หลายทางทันที!</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 10: E-Commerce Empire - ภาพรวม -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-blue-900/95 via-cyan-900/90 to-teal-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 right-20 w-80 h-80 bg-blue-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 left-20 w-96 h-96 bg-teal-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s;"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-blue-500/30 to-teal-500/30 backdrop-blur-md rounded-full border border-white/30 mb-6">
                    <span class="text-3xl">🛒</span>
                    <span class="text-white font-bold text-lg">E-Commerce Empire</span>
                </div>
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    🏪 อาณาจักร <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-300 to-teal-300">E-Commerce</span>
                </h2>
                <p class="text-xl text-white/80">ทุกรูปแบบการค้าขายออนไลน์ในระบบเดียว</p>
            </div>

            <!-- What is E-Commerce Empire -->
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">🎯</span> สิ่งที่คุณจะได้
                    </h3>
                    <div class="space-y-3 text-white/80">
                        <div class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span> ร้านค้าออนไลน์พร้อมใช้งาน
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span> รองรับทุกรูปแบบสินค้า
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span> ระบบจัดการสต็อกอัตโนมัติ
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span> เชื่อมต่อ Payment Gateways
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-emerald-400">✓</span> รายงานยอดขาย Real-time
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-blue-500/20 to-teal-500/20 backdrop-blur-md border border-blue-400/30 rounded-2xl p-6">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">💼</span> 6 โมเดลธุรกิจ
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white/10 rounded-lg p-3 text-center">
                            <div class="text-2xl">🏬</div>
                            <div class="text-white text-sm font-bold">Multi-Vendor</div>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 text-center">
                            <div class="text-2xl">📦</div>
                            <div class="text-white text-sm font-bold">Dropshipping</div>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 text-center">
                            <div class="text-2xl">🖥️</div>
                            <div class="text-white text-sm font-bold">POS System</div>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 text-center">
                            <div class="text-2xl">🏨</div>
                            <div class="text-white text-sm font-bold">Hotel Booking</div>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 text-center">
                            <div class="text-2xl">🥗</div>
                            <div class="text-white text-sm font-bold">Food Passport</div>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 text-center">
                            <div class="text-2xl">🎫</div>
                            <div class="text-white text-sm font-bold">Digital Products</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <div class="text-3xl font-black text-blue-300">∞</div>
                    <div class="text-white/70 text-sm">สินค้าไม่จำกัด</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <div class="text-3xl font-black text-teal-300">10+</div>
                    <div class="text-white/70 text-sm">ช่องทางชำระ</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <div class="text-3xl font-black text-cyan-300">24/7</div>
                    <div class="text-white/70 text-sm">ขายได้ตลอด</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <div class="text-3xl font-black text-emerald-300">0%</div>
                    <div class="text-white/70 text-sm">ค่าธรรมเนียม</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 10.1: E-Commerce - Multi-Vendor & Dropshipping -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-blue-900/95 via-cyan-900/90 to-teal-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-10 left-10 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-10 w-80 h-80 bg-teal-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s;"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-blue-500/30 to-emerald-500/30 backdrop-blur-md rounded-full border border-white/30 mb-4">
                    <span class="text-3xl">🏬</span>
                    <span class="text-white font-bold text-lg">Marketplace Models</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white mb-2">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-300 to-emerald-300">Multi-Vendor & Dropshipping</span>
                </h2>
            </div>

            <!-- Two Models -->
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Multi-Vendor -->
                <div class="bg-gradient-to-br from-blue-500/20 to-blue-600/10 backdrop-blur-md border border-blue-400/30 rounded-2xl p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center text-3xl">🏬</div>
                        <div>
                            <h3 class="text-2xl font-bold text-white">Multi-Vendor Marketplace</h3>
                            <p class="text-blue-300 text-sm">ตลาดหลายร้านค้า</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-white/80 mb-4">
                        <div class="flex items-start gap-3">
                            <span class="text-blue-400 mt-1">•</span>
                            <span>เปิดให้ Sellers หลายคนมาขายในแพลตฟอร์ม</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-blue-400 mt-1">•</span>
                            <span>เก็บค่าคอมมิชชั่นจากทุกการขาย 5-15%</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-blue-400 mt-1">•</span>
                            <span>ระบบจัดการ Seller, สินค้า, ออเดอร์ครบวงจร</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-blue-400 mt-1">•</span>
                            <span>แดชบอร์ดสำหรับ Admin และ Seller แยกกัน</span>
                        </div>
                    </div>
                    <div class="bg-blue-500/20 rounded-xl p-3 text-center">
                        <div class="text-white/70 text-xs">โมเดลธุรกิจ</div>
                        <div class="text-white font-bold">เป็น "เจ้าของตลาด" ไม่ต้องสต็อกเอง!</div>
                    </div>
                </div>

                <!-- Dropshipping -->
                <div class="bg-gradient-to-br from-emerald-500/20 to-green-600/10 backdrop-blur-md border border-emerald-400/30 rounded-2xl p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl flex items-center justify-center text-3xl">📦</div>
                        <div>
                            <h3 class="text-2xl font-bold text-white">Dropshipping</h3>
                            <p class="text-emerald-300 text-sm">ขายไม่ต้องสต็อก</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-white/80 mb-4">
                        <div class="flex items-start gap-3">
                            <span class="text-emerald-400 mt-1">•</span>
                            <span>เชื่อมต่อกับ Supplier โดยตรง</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-emerald-400 mt-1">•</span>
                            <span>ลูกค้าสั่ง → Supplier ส่งให้เลย</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-emerald-400 mt-1">•</span>
                            <span>ไม่ต้องสต็อก ไม่ต้องแพ็คของ</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-emerald-400 mt-1">•</span>
                            <span>กำไรคือส่วนต่างราคา 15-40%</span>
                        </div>
                    </div>
                    <div class="bg-emerald-500/20 rounded-xl p-3 text-center">
                        <div class="text-white/70 text-xs">ความเสี่ยง</div>
                        <div class="text-white font-bold">ใกล้ 0! ไม่มีทุนจม ไม่มีสินค้าค้าง</div>
                    </div>
                </div>
            </div>

            <!-- Flow Diagram -->
            <div class="mt-6 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4">
                <div class="flex items-center justify-center gap-2 md:gap-4 text-sm md:text-base">
                    <div class="bg-blue-500/30 px-3 py-2 rounded-lg text-white">👤 ลูกค้าสั่งซื้อ</div>
                    <span class="text-white">→</span>
                    <div class="bg-teal-500/30 px-3 py-2 rounded-lg text-white">📱 คุณรับออเดอร์</div>
                    <span class="text-white">→</span>
                    <div class="bg-emerald-500/30 px-3 py-2 rounded-lg text-white">📦 Supplier ส่งสินค้า</div>
                    <span class="text-white">→</span>
                    <div class="bg-green-500/30 px-3 py-2 rounded-lg text-white">💰 คุณได้กำไร!</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 10.2: E-Commerce - POS & Hotel Booking -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-blue-900/95 via-cyan-900/90 to-teal-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 right-20 w-80 h-80 bg-purple-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 left-10 w-96 h-96 bg-amber-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s;"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-purple-500/30 to-amber-500/30 backdrop-blur-md rounded-full border border-white/30 mb-4">
                    <span class="text-3xl">🖥️</span>
                    <span class="text-white font-bold text-lg">Offline + Online</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white mb-2">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-300 to-amber-300">POS & Hotel Booking</span>
                </h2>
            </div>

            <!-- Two Systems -->
            <div class="grid md:grid-cols-2 gap-6">
                <!-- POS System -->
                <div class="bg-gradient-to-br from-purple-500/20 to-pink-600/10 backdrop-blur-md border border-purple-400/30 rounded-2xl p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center text-3xl">🖥️</div>
                        <div>
                            <h3 class="text-2xl font-bold text-white">POS System</h3>
                            <p class="text-purple-300 text-sm">ระบบหน้าร้าน</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-white/80 mb-4">
                        <div class="flex items-start gap-3">
                            <span class="text-purple-400 mt-1">•</span>
                            <span>ใช้กับหน้าร้านจริง + ออนไลน์</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-purple-400 mt-1">•</span>
                            <span>Sync สต็อก Real-time ทุกช่องทาง</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-purple-400 mt-1">•</span>
                            <span>รองรับ Barcode Scanner</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-purple-400 mt-1">•</span>
                            <span>พิมพ์ใบเสร็จ ออกบิล VAT</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-purple-400 mt-1">•</span>
                            <span>รายงานยอดขายรายวัน/เดือน</span>
                        </div>
                    </div>
                    <div class="bg-purple-500/20 rounded-xl p-3 text-center">
                        <div class="text-white font-bold">Omnichannel ครบจบในที่เดียว!</div>
                    </div>
                </div>

                <!-- Hotel Booking -->
                <div class="bg-gradient-to-br from-amber-500/20 to-orange-600/10 backdrop-blur-md border border-amber-400/30 rounded-2xl p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center text-3xl">🏨</div>
                        <div>
                            <h3 class="text-2xl font-bold text-white">Hotel Booking</h3>
                            <p class="text-amber-300 text-sm">ระบบจองโรงแรม</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-white/80 mb-4">
                        <div class="flex items-start gap-3">
                            <span class="text-amber-400 mt-1">•</span>
                            <span>จัดการห้องพัก ราคา ช่วงเวลา</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-400 mt-1">•</span>
                            <span>ปฏิทินจอง Availability Calendar</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-400 mt-1">•</span>
                            <span>Check-in / Check-out อัตโนมัติ</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-400 mt-1">•</span>
                            <span>ส่วนลด Promo Code, ราคาตามฤดูกาล</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-400 mt-1">•</span>
                            <span>รีวิวและ Rating จากลูกค้า</span>
                        </div>
                    </div>
                    <div class="bg-amber-500/20 rounded-xl p-3 text-center">
                        <div class="text-white font-bold">เปิดโรงแรม รีสอร์ท ได้ทันที!</div>
                    </div>
                </div>
            </div>

            <!-- Benefits -->
            <div class="mt-6 grid grid-cols-3 gap-4">
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 text-center">
                    <div class="text-3xl mb-2">📊</div>
                    <div class="text-white font-bold text-sm">รายงานครบ</div>
                    <div class="text-white/60 text-xs">Real-time Analytics</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 text-center">
                    <div class="text-3xl mb-2">📱</div>
                    <div class="text-white font-bold text-sm">ใช้มือถือได้</div>
                    <div class="text-white/60 text-xs">Mobile Friendly</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 text-center">
                    <div class="text-3xl mb-2">🔗</div>
                    <div class="text-white font-bold text-sm">เชื่อมต่อ MLM</div>
                    <div class="text-white/60 text-xs">ได้ PV ทุกการขาย</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 10.3: E-Commerce - Digital Products & Food Passport -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-blue-900/95 via-cyan-900/90 to-teal-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-10 left-20 w-96 h-96 bg-rose-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-10 w-80 h-80 bg-green-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s;"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-rose-500/30 to-green-500/30 backdrop-blur-md rounded-full border border-white/30 mb-4">
                    <span class="text-3xl">🎫</span>
                    <span class="text-white font-bold text-lg">Special Products</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white mb-2">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-rose-300 to-green-300">Digital Products & Food Passport</span>
                </h2>
            </div>

            <!-- Two Systems -->
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Digital Products -->
                <div class="bg-gradient-to-br from-rose-500/20 to-red-600/10 backdrop-blur-md border border-rose-400/30 rounded-2xl p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-rose-500 to-red-600 rounded-xl flex items-center justify-center text-3xl">🎫</div>
                        <div>
                            <h3 class="text-2xl font-bold text-white">Digital Products</h3>
                            <p class="text-rose-300 text-sm">สินค้าดิจิทัล</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-white/80 mb-4">
                        <div class="flex items-start gap-3">
                            <span class="text-rose-400 mt-1">•</span>
                            <span>ขาย Software & License Keys</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-rose-400 mt-1">•</span>
                            <span>E-book & PDF Downloads</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-rose-400 mt-1">•</span>
                            <span>เชื่อมกับ Academy ขายคอร์ส</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-rose-400 mt-1">•</span>
                            <span>ส่งมอบอัตโนมัติทันที</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-rose-400 mt-1">•</span>
                            <span>ไม่มีค่าขนส่ง กำไร 80-100%</span>
                        </div>
                    </div>
                    <div class="bg-rose-500/20 rounded-xl p-3 text-center">
                        <div class="text-white font-bold">ขายได้ไม่จำกัด ไม่ต้องส่งของ!</div>
                    </div>
                </div>

                <!-- Food Passport -->
                <div class="bg-gradient-to-br from-green-500/20 to-emerald-600/10 backdrop-blur-md border border-green-400/30 rounded-2xl p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-3xl">🥗</div>
                        <div>
                            <h3 class="text-2xl font-bold text-white">Food Passport</h3>
                            <p class="text-green-300 text-sm">Supply Chain Traceability</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-white/80 mb-4">
                        <div class="flex items-start gap-3">
                            <span class="text-green-400 mt-1">•</span>
                            <span>ติดตามแหล่งที่มาอาหาร</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-green-400 mt-1">•</span>
                            <span>QR Code สแกนดูข้อมูลทั้งหมด</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-green-400 mt-1">•</span>
                            <span>จากฟาร์ม → โรงงาน → ร้านค้า → โต๊ะ</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-green-400 mt-1">•</span>
                            <span>Blockchain-based สำหรับความโปร่งใส</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-green-400 mt-1">•</span>
                            <span>Premium Price เพราะความน่าเชื่อถือ</span>
                        </div>
                    </div>
                    <div class="bg-green-500/20 rounded-xl p-3 text-center">
                        <div class="text-white font-bold">สร้างความเชื่อมั่นให้ผู้บริโภค!</div>
                    </div>
                </div>
            </div>

            <!-- CTA -->
            <div class="mt-6 text-center">
                <div class="inline-flex items-center gap-4 px-8 py-4 bg-gradient-to-r from-blue-500 to-teal-500 rounded-2xl shadow-2xl">
                    <span class="text-3xl">🛒</span>
                    <div class="text-left">
                        <div class="text-white font-black text-xl">พร้อมสร้างอาณาจักร?</div>
                        <div class="text-white/80 text-sm">6 โมเดลธุรกิจ ในแพลตฟอร์มเดียว!</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 11: Community & Partnership - ภาพรวม -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-pink-900/95 via-rose-900/90 to-red-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-10 left-20 w-80 h-80 bg-pink-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-20 w-96 h-96 bg-rose-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.7s;"></div>
            <div class="absolute top-1/4 left-1/4 text-4xl animate-pulse" style="animation-duration: 2s;">❤️</div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-pink-500/30 to-rose-500/30 backdrop-blur-md rounded-full border border-white/30 mb-6">
                    <span class="text-3xl">🤝</span>
                    <span class="text-white font-bold text-lg">Community & Partnership</span>
                </div>
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    👨‍👩‍👧‍👦 ชุมชน <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-300 to-rose-300">แห่งความสำเร็จ</span>
                </h2>
                <p class="text-xl text-white/80">คุณไม่ได้เดินคนเดียว เรามีทีมพร้อมช่วยเหลือ</p>
            </div>

            <!-- Why Community Matters -->
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">❌</span> ทำธุรกิจคนเดียว
                    </h3>
                    <div class="space-y-3 text-red-200/80">
                        <div class="flex items-center gap-3">
                            <span>😔</span> เหนื่อย ท้อ หมดแรง
                        </div>
                        <div class="flex items-center gap-3">
                            <span>❓</span> ติดปัญหา ไม่รู้จะถามใคร
                        </div>
                        <div class="flex items-center gap-3">
                            <span>🐌</span> เติบโตช้า ไม่มีไอเดีย
                        </div>
                        <div class="flex items-center gap-3">
                            <span>😰</span> ล้มแล้วลุกเองยาก
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-emerald-500/20 to-green-500/20 backdrop-blur-md border border-emerald-400/30 rounded-2xl p-6">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">✅</span> มีชุมชนช่วยเหลือ
                    </h3>
                    <div class="space-y-3 text-emerald-200/80">
                        <div class="flex items-center gap-3">
                            <span>💪</span> มีกำลังใจ มีแรงบันดาลใจ
                        </div>
                        <div class="flex items-center gap-3">
                            <span>💡</span> ถามได้ตลอด มีคนตอบ 24/7
                        </div>
                        <div class="flex items-center gap-3">
                            <span>🚀</span> เรียนรู้จากคนสำเร็จ
                        </div>
                        <div class="flex items-center gap-3">
                            <span>🤝</span> ล้มแล้วมีคนช่วยพยุง
                        </div>
                    </div>
                </div>
            </div>

            <!-- Community Stats -->
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <div class="text-3xl font-black text-pink-300">1,000+</div>
                    <div class="text-white/70 text-sm">สมาชิก Active</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <div class="text-3xl font-black text-rose-300">50+</div>
                    <div class="text-white/70 text-sm">Partners</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <div class="text-3xl font-black text-red-300">24/7</div>
                    <div class="text-white/70 text-sm">Support</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <div class="text-3xl font-black text-orange-300">∞</div>
                    <div class="text-white/70 text-sm">ความช่วยเหลือ</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 11.1: Community - ชุมชนที่แข็งแกร่ง -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-pink-900/95 via-rose-900/90 to-red-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 right-10 w-96 h-96 bg-pink-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 left-10 w-80 h-80 bg-rose-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s;"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-pink-500/30 to-rose-500/30 backdrop-blur-md rounded-full border border-white/30 mb-4">
                    <span class="text-3xl">👥</span>
                    <span class="text-white font-bold text-lg">Strong Community</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white mb-2">
                    ชุมชน <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-300 to-rose-300">ที่แข็งแกร่ง</span>
                </h2>
            </div>

            <!-- Community Features -->
            <div class="grid md:grid-cols-3 gap-6">
                <!-- LINE Group -->
                <div class="bg-gradient-to-br from-green-500/20 to-emerald-600/10 backdrop-blur-md border border-green-400/30 rounded-2xl p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-3xl">💬</div>
                        <div>
                            <h3 class="text-xl font-bold text-white">LINE Group</h3>
                            <p class="text-green-300 text-sm">สนับสนุน 24/7</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-white/80 text-sm">
                        <div class="flex items-start gap-3">
                            <span class="text-green-400">•</span>
                            <span>ถามตอบทุกปัญหา</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-green-400">•</span>
                            <span>แชร์เทคนิค ประสบการณ์</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-green-400">•</span>
                            <span>Admin ตอบเร็วทันใจ</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-green-400">•</span>
                            <span>Notification ข่าวสารสำคัญ</span>
                        </div>
                    </div>
                </div>

                <!-- Live Training -->
                <div class="bg-gradient-to-br from-purple-500/20 to-violet-600/10 backdrop-blur-md border border-purple-400/30 rounded-2xl p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl flex items-center justify-center text-3xl">📺</div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Live Training</h3>
                            <p class="text-purple-300 text-sm">รายสัปดาห์</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-white/80 text-sm">
                        <div class="flex items-start gap-3">
                            <span class="text-purple-400">•</span>
                            <span>สอนใช้งานระบบ</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-purple-400">•</span>
                            <span>เทคนิคการขาย</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-purple-400">•</span>
                            <span>Q&A Session</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-purple-400">•</span>
                            <span>Replay ดูย้อนหลังได้</span>
                        </div>
                    </div>
                </div>

                <!-- Event & Meetup -->
                <div class="bg-gradient-to-br from-amber-500/20 to-orange-600/10 backdrop-blur-md border border-amber-400/30 rounded-2xl p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center text-3xl">🎉</div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Event & Meetup</h3>
                            <p class="text-amber-300 text-sm">พบปะสมาชิก</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-white/80 text-sm">
                        <div class="flex items-start gap-3">
                            <span class="text-amber-400">•</span>
                            <span>Seminar ทุกเดือน</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-400">•</span>
                            <span>Networking Party</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-400">•</span>
                            <span>สร้างเครือข่ายธุรกิจ</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-400">•</span>
                            <span>รับรางวัลความสำเร็จ</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 11.2: Community - พันธมิตรทางธุรกิจ -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-pink-900/95 via-rose-900/90 to-red-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-10 left-10 w-80 h-80 bg-blue-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-pink-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s;"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-blue-500/30 to-pink-500/30 backdrop-blur-md rounded-full border border-white/30 mb-4">
                    <span class="text-3xl">🤝</span>
                    <span class="text-white font-bold text-lg">Business Partners</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white mb-2">
                    พันธมิตร <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-300 to-pink-300">ทางธุรกิจ</span>
                </h2>
            </div>

            <!-- Partners Grid -->
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <!-- Corporate Partners -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">🏢</span> พาร์ทเนอร์องค์กร
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center gap-4 bg-white/10 rounded-xl p-3">
                            <div class="text-3xl">🏭</div>
                            <div>
                                <div class="text-white font-semibold">Suppliers & Manufacturers</div>
                                <div class="text-white/60 text-sm">ผู้ผลิตสินค้าคุณภาพ พร้อม Dropship</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 bg-white/10 rounded-xl p-3">
                            <div class="text-3xl">🏨</div>
                            <div>
                                <div class="text-white font-semibold">Hotel & Resort Partners</div>
                                <div class="text-white/60 text-sm">โรงแรม รีสอร์ท ทั่วประเทศ</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 bg-white/10 rounded-xl p-3">
                            <div class="text-3xl">🏫</div>
                            <div>
                                <div class="text-white font-semibold">Training Institutes</div>
                                <div class="text-white/60 text-sm">สถาบันอบรม คอร์สพัฒนาตนเอง</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technology Partners -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">📱</span> Technology Partners
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center gap-4 bg-white/10 rounded-xl p-3">
                            <div class="text-3xl">💚</div>
                            <div>
                                <div class="text-white font-semibold">LINE Official Account</div>
                                <div class="text-white/60 text-sm">เชื่อมต่อ Messaging API</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 bg-white/10 rounded-xl p-3">
                            <div class="text-3xl">💳</div>
                            <div>
                                <div class="text-white font-semibold">Payment Gateways</div>
                                <div class="text-white/60 text-sm">2C2P, Omise, PromptPay</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 bg-white/10 rounded-xl p-3">
                            <div class="text-3xl">☁️</div>
                            <div>
                                <div class="text-white font-semibold">Cloud & AI Services</div>
                                <div class="text-white/60 text-sm">AWS, Google Cloud, OpenAI</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Global Network -->
            <div class="bg-gradient-to-r from-blue-500/20 to-purple-500/20 backdrop-blur-md border border-white/20 rounded-2xl p-6 text-center">
                <div class="flex items-center justify-center gap-4 mb-4">
                    <span class="text-4xl">🌏</span>
                    <h3 class="text-2xl font-bold text-white">Global Network</h3>
                </div>
                <p class="text-white/70 mb-4">เครือข่ายพันธมิตรทั่วโลก พร้อมขยายตลาดไปด้วยกัน</p>
                <div class="flex justify-center gap-4 text-3xl">
                    <span>🇹🇭</span>
                    <span>🇸🇬</span>
                    <span>🇲🇾</span>
                    <span>🇮🇩</span>
                    <span>🇵🇭</span>
                    <span>🇻🇳</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 11.3: Community - บริการสนับสนุน -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-pink-900/95 via-rose-900/90 to-red-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 left-20 w-96 h-96 bg-emerald-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-10 w-80 h-80 bg-rose-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s;"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-emerald-500/30 to-rose-500/30 backdrop-blur-md rounded-full border border-white/30 mb-4">
                    <span class="text-3xl">🛡️</span>
                    <span class="text-white font-bold text-lg">Full Support</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white mb-2">
                    บริการสนับสนุน <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 to-rose-300">ครบวงจร</span>
                </h2>
            </div>

            <!-- Support Services Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-gradient-to-br from-blue-500/20 to-blue-600/10 backdrop-blur-md border border-blue-400/30 rounded-2xl p-5 text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-3xl mx-auto mb-4">🎓</div>
                    <h4 class="text-lg font-bold text-white mb-2">Training</h4>
                    <p class="text-blue-200/80 text-sm">อบรมใช้งานฟรี</p>
                    <div class="mt-3 text-white/70 text-xs space-y-1">
                        <div>• สอนตั้งค่าระบบ</div>
                        <div>• สอนใช้งานทุกฟีเจอร์</div>
                        <div>• มีคู่มือให้ดู</div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-emerald-500/20 to-green-600/10 backdrop-blur-md border border-emerald-400/30 rounded-2xl p-5 text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl flex items-center justify-center text-3xl mx-auto mb-4">🔧</div>
                    <h4 class="text-lg font-bold text-white mb-2">Setup</h4>
                    <p class="text-emerald-200/80 text-sm">ติดตั้งให้ฟรี</p>
                    <div class="mt-3 text-white/70 text-xs space-y-1">
                        <div>• Deploy ระบบให้</div>
                        <div>• ตั้งค่า Domain</div>
                        <div>• เชื่อมต่อ Payment</div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-500/20 to-violet-600/10 backdrop-blur-md border border-purple-400/30 rounded-2xl p-5 text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl flex items-center justify-center text-3xl mx-auto mb-4">📞</div>
                    <h4 class="text-lg font-bold text-white mb-2">Support</h4>
                    <p class="text-purple-200/80 text-sm">ช่วยเหลือ 24/7</p>
                    <div class="mt-3 text-white/70 text-xs space-y-1">
                        <div>• ตอบ LINE ทันที</div>
                        <div>• แก้ปัญหาให้</div>
                        <div>• Priority Support</div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-amber-500/20 to-orange-600/10 backdrop-blur-md border border-amber-400/30 rounded-2xl p-5 text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center text-3xl mx-auto mb-4">🔄</div>
                    <h4 class="text-lg font-bold text-white mb-2">Updates</h4>
                    <p class="text-amber-200/80 text-sm">อัพเดทตลอดชีพ</p>
                    <div class="mt-3 text-white/70 text-xs space-y-1">
                        <div>• ฟีเจอร์ใหม่ตลอด</div>
                        <div>• Security patches</div>
                        <div>• Bug fixes</div>
                    </div>
                </div>
            </div>

            <!-- CTA -->
            <div class="text-center">
                <div class="inline-flex items-center gap-4 px-8 py-4 bg-gradient-to-r from-pink-500 to-rose-500 rounded-2xl shadow-2xl hover:scale-105 transition-transform">
                    <span class="text-3xl">🤝</span>
                    <div class="text-left">
                        <div class="text-white font-black text-xl">พร้อมเข้าร่วมครอบครัว?</div>
                        <div class="text-white/80 text-sm">มีทีมพร้อมช่วยเหลือคุณตลอดทาง!</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 12: ระบบ Multi-Currency Wallet -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-emerald-900/90 via-teal-900/80 to-cyan-900/90 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-8 md:mb-12 text-center">
                💰 ระบบ Multi-Currency Wallet
            </h2>

            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-3xl p-6 md:p-8 mb-8 shadow-2xl">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div class="bg-white/10 rounded-2xl p-4">
                        <div class="text-4xl mb-2">🇹🇭</div>
                        <h4 class="text-xl font-bold text-white">THB</h4>
                        <p class="text-white/70 text-sm">บาทไทย</p>
                    </div>
                    <div class="bg-white/10 rounded-2xl p-4">
                        <div class="text-4xl mb-2">🇺🇸</div>
                        <h4 class="text-xl font-bold text-white">USD</h4>
                        <p class="text-white/70 text-sm">ดอลลาร์</p>
                    </div>
                    <div class="bg-white/10 rounded-2xl p-4">
                        <div class="text-4xl mb-2">🪙</div>
                        <h4 class="text-xl font-bold text-white">TPIX</h4>
                        <p class="text-white/70 text-sm">Token</p>
                    </div>
                    <div class="bg-white/10 rounded-2xl p-4">
                        <div class="text-4xl mb-2">💎</div>
                        <h4 class="text-xl font-bold text-white">Points</h4>
                        <p class="text-white/70 text-sm">คะแนนสะสม</p>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Deposit -->
                <div class="bg-gradient-to-br from-emerald-500/20 to-green-500/10 backdrop-blur-lg border border-emerald-300/30 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-2xl font-bold text-white mb-4 flex items-center gap-3">
                        <span class="text-4xl">📥</span>
                        <span>ช่องทางฝากเงิน</span>
                    </h3>
                    <ul class="space-y-3 text-white/90">
                        <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> โอนธนาคาร (Auto Confirm)</li>
                        <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> PromptPay QR</li>
                        <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> บัตรเครดิต/เดบิต</li>
                        <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> True Money Wallet</li>
                        <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Cryptocurrency</li>
                    </ul>
                </div>

                <!-- Withdraw -->
                <div class="bg-gradient-to-br from-blue-500/20 to-cyan-500/10 backdrop-blur-lg border border-blue-300/30 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-2xl font-bold text-white mb-4 flex items-center gap-3">
                        <span class="text-4xl">📤</span>
                        <span>ช่องทางถอนเงิน</span>
                    </h3>
                    <ul class="space-y-3 text-white/90">
                        <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> โอนเข้าบัญชีธนาคาร</li>
                        <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> PromptPay</li>
                        <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> Crypto Wallet</li>
                        <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> True Money</li>
                        <li class="flex items-center gap-2"><span class="text-blue-400">✓</span> อนุมัติอัตโนมัติ/Manual</li>
                    </ul>
                </div>
            </div>

            <div class="mt-8 bg-gradient-to-r from-emerald-500/20 to-teal-500/20 backdrop-blur-md border border-emerald-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-xl md:text-2xl text-white font-bold">
                    🔐 ปลอดภัย โปร่งใส ตรวจสอบได้ทุกธุรกรรม
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 13: ความปลอดภัย & ความน่าเชื่อถือ -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-emerald-900/90 via-teal-900/80 to-cyan-900/90 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-8 md:mb-12 text-center">
                🛡️ ความปลอดภัยระดับ Enterprise
            </h2>

            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <!-- Security Features -->
                <div class="bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-lg border border-white/30 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                        <span class="text-3xl">🔐</span>
                        <span>มาตรการรักษาความปลอดภัย</span>
                    </h3>
                    <ul class="space-y-3 text-white text-lg">
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-xl">✅</span>
                            <div>
                                <strong>Two-Factor Authentication (2FA)</strong>
                                <p class="text-white/70 text-sm">ยืนยันตัวตนสองขั้นตอน</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-xl">✅</span>
                            <div>
                                <strong>SSL/TLS Encryption</strong>
                                <p class="text-white/70 text-sm">เข้ารหัสข้อมูลทุกการเชื่อมต่อ</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-xl">✅</span>
                            <div>
                                <strong>CSRF & XSS Protection</strong>
                                <p class="text-white/70 text-sm">ป้องกันการโจมตีทั่วไป</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-xl">✅</span>
                            <div>
                                <strong>IP Whitelist & Rate Limiting</strong>
                                <p class="text-white/70 text-sm">ควบคุมการเข้าถึง</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Trust Indicators -->
                <div class="bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-lg border border-white/30 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                        <span class="text-3xl">⭐</span>
                        <span>ความน่าเชื่อถือ</span>
                    </h3>
                    <ul class="space-y-3 text-white text-lg">
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-400 text-xl">🏆</span>
                            <div>
                                <strong>99.9% Uptime SLA</strong>
                                <p class="text-white/70 text-sm">พร้อมใช้งานตลอดเวลา</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-400 text-xl">📊</span>
                            <div>
                                <strong>Activity Logging</strong>
                                <p class="text-white/70 text-sm">บันทึกทุกการกระทำ</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-400 text-xl">💾</span>
                            <div>
                                <strong>Auto Backup Daily</strong>
                                <p class="text-white/70 text-sm">สำรองข้อมูลอัตโนมัติ</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-400 text-xl">🎧</span>
                            <div>
                                <strong>24/7 Technical Support</strong>
                                <p class="text-white/70 text-sm">ทีมซัพพอร์ตพร้อมช่วยเหลือ</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="bg-gradient-to-r from-emerald-500/20 to-teal-500/20 backdrop-blur-md border border-emerald-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-xl md:text-2xl text-white font-bold">
                    🔒 ข้อมูลของคุณปลอดภัยในมือเรา
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 15: Thank You -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-indigo-900/90 via-purple-900/80 to-pink-900/90 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute top-1/3 right-1/4 w-96 h-96 bg-purple-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
            <div class="absolute bottom-1/4 left-1/2 w-96 h-96 bg-pink-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-4000"></div>
        </div>

        <div class="text-center text-white max-w-5xl relative z-10">
            <div class="mb-8 animate-float">
                @if($systemLogo)
                    <img src="{{ asset('storage/' . $systemLogo) }}" alt="{{ $appName }}" class="w-40 h-40 mx-auto filter drop-shadow-2xl object-contain">
                @else
                    <img src="{{ asset('images/logo.svg') }}" alt="{{ $appName }}" width="160" height="160" class="w-40 h-40 mx-auto filter drop-shadow-2xl object-contain">
                @endif
            </div>

            <h1 class="text-5xl md:text-6xl lg:text-7xl font-black mb-8 leading-tight">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-pink-300 to-purple-300">
                    ขอบคุณที่รับชม
                </span>
            </h1>

            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-3xl p-8 md:p-10 mb-8 shadow-2xl">
                <h2 class="text-3xl md:text-4xl font-bold mb-6">
                    พร้อมเริ่มต้นธุรกิจใหม่แล้วหรือยัง?
                </h2>
                <p class="text-xl md:text-2xl text-white/90 mb-8 leading-relaxed">
                    เริ่มต้นสร้างธุรกิจออนไลน์ของคุณวันนี้
                    <br>
                    ด้วย <strong class="text-pink-300">{{ $appName }}</strong> แพลตฟอร์ม All-in-One ที่ครบครันที่สุด
                </p>

                <div class="flex flex-wrap items-center justify-center gap-4 md:gap-6">
                    <a href="{{ route('register') }}" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-lg md:text-xl px-8 md:px-10 py-4 md:py-5 rounded-2xl shadow-2xl transform hover:scale-105 transition-all">
                        🚀 เริ่มต้นใช้งานฟรี
                    </a>
                    <a href="{{ route('contact') }}" class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold text-lg md:text-xl px-8 md:px-10 py-4 md:py-5 rounded-2xl shadow-2xl transform hover:scale-105 transition-all">
                        📞 ติดต่อทีมขาย
                    </a>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-4 md:gap-8 text-base md:text-lg">
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 md:px-6 py-2 md:py-3 rounded-full border border-white/20">
                    <span class="text-xl md:text-2xl">❤️</span>
                    <span>Made in Thailand</span>
                </div>
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 md:px-6 py-2 md:py-3 rounded-full border border-white/20">
                    <span class="text-xl md:text-2xl">🚀</span>
                    <span>20+ ระบบครบวงจร</span>
                </div>
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 md:px-6 py-2 md:py-3 rounded-full border border-white/20">
                    <span class="text-xl md:text-2xl">🎧</span>
                    <span>Support 24/7</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes blob {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(20px, -50px) scale(1.1); }
    50% { transform: translate(-20px, 20px) scale(0.9); }
    75% { transform: translate(20px, 20px) scale(1.05); }
}

.animate-blob {
    animation: blob 15s ease-in-out infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

@keyframes fade-in-up {
    0% { opacity: 0; transform: translateY(30px); }
    100% { opacity: 1; transform: translateY(0); }
}

.animate-fade-in-up {
    animation: fade-in-up 1s ease-out;
}

.animate-fade-in {
    animation: fade-in-up 0.8s ease-out;
}
</style>
