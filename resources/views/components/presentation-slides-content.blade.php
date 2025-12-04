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
                        <div class="text-lg font-bold text-blue-300">1%</div>
                    </div>
                    <div class="bg-blue-500/10 rounded-lg p-2">
                        <div class="text-xs text-white/60">ชั้น 8</div>
                        <div class="text-lg font-bold text-blue-300">1%</div>
                    </div>
                    <div class="bg-blue-500/8 rounded-lg p-2">
                        <div class="text-xs text-white/60">ชั้น 9</div>
                        <div class="text-lg font-bold text-blue-300">1%</div>
                    </div>
                    <div class="bg-blue-500/5 rounded-lg p-2">
                        <div class="text-xs text-white/60">ชั้น 10</div>
                        <div class="text-lg font-bold text-blue-300">1%</div>
                    </div>
                </div>
                <p class="text-white/60 text-sm mt-3 text-center">
                    💡 รวมค่าคอมมิชชั่น <strong class="text-white">26%</strong> ต่อ PV • รองรับ Roll-up และ Compression
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

<!-- Slide 7: ระบบ Multi-Currency Wallet -->
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

<!-- Slide 8: ระบบเสริมอื่นๆ -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-gray-900/90 via-slate-900/80 to-zinc-900/90 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-8 md:mb-12 text-center">
                🎁 ระบบเสริมอีกมากมาย
            </h2>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                <!-- Hotel Booking -->
                <div class="bg-gradient-to-br from-red-500/20 to-pink-500/10 backdrop-blur-lg border border-red-300/30 rounded-2xl p-4 md:p-6 text-center hover:scale-105 transition-all">
                    <div class="text-4xl mb-3">🏨</div>
                    <h4 class="text-lg font-bold text-white">Hotel Booking</h4>
                    <p class="text-white/60 text-xs mt-1">ระบบจองโรงแรม</p>
                </div>

                <!-- POS System -->
                <div class="bg-gradient-to-br from-indigo-500/20 to-purple-500/10 backdrop-blur-lg border border-indigo-300/30 rounded-2xl p-4 md:p-6 text-center hover:scale-105 transition-all">
                    <div class="text-4xl mb-3">🖥️</div>
                    <h4 class="text-lg font-bold text-white">POS System</h4>
                    <p class="text-white/60 text-xs mt-1">ระบบขายหน้าร้าน</p>
                </div>

                <!-- Academy LMS -->
                <div class="bg-gradient-to-br from-teal-500/20 to-cyan-500/10 backdrop-blur-lg border border-teal-300/30 rounded-2xl p-4 md:p-6 text-center hover:scale-105 transition-all">
                    <div class="text-4xl mb-3">🎓</div>
                    <h4 class="text-lg font-bold text-white">Academy LMS</h4>
                    <p class="text-white/60 text-xs mt-1">ระบบคอร์สออนไลน์</p>
                </div>

                <!-- HRM System -->
                <div class="bg-gradient-to-br from-slate-500/20 to-gray-500/10 backdrop-blur-lg border border-slate-300/30 rounded-2xl p-4 md:p-6 text-center hover:scale-105 transition-all">
                    <div class="text-4xl mb-3">👔</div>
                    <h4 class="text-lg font-bold text-white">HRM System</h4>
                    <p class="text-white/60 text-xs mt-1">จัดการพนักงาน</p>
                </div>

                <!-- Accounting -->
                <div class="bg-gradient-to-br from-emerald-500/20 to-green-500/10 backdrop-blur-lg border border-emerald-300/30 rounded-2xl p-4 md:p-6 text-center hover:scale-105 transition-all">
                    <div class="text-4xl mb-3">📊</div>
                    <h4 class="text-lg font-bold text-white">Accounting</h4>
                    <p class="text-white/60 text-xs mt-1">ระบบบัญชี</p>
                </div>

                <!-- Food Passport -->
                <div class="bg-gradient-to-br from-lime-500/20 to-green-500/10 backdrop-blur-lg border border-lime-300/30 rounded-2xl p-4 md:p-6 text-center hover:scale-105 transition-all">
                    <div class="text-4xl mb-3">🌱</div>
                    <h4 class="text-lg font-bold text-white">Food Passport</h4>
                    <p class="text-white/60 text-xs mt-1">ย้อนรอยอาหาร</p>
                </div>

                <!-- Games -->
                <div class="bg-gradient-to-br from-fuchsia-500/20 to-pink-500/10 backdrop-blur-lg border border-fuchsia-300/30 rounded-2xl p-4 md:p-6 text-center hover:scale-105 transition-all">
                    <div class="text-4xl mb-3">🎮</div>
                    <h4 class="text-lg font-bold text-white">Mini Games</h4>
                    <p class="text-white/60 text-xs mt-1">เกมสะสมแต้ม</p>
                </div>

                <!-- Tarot -->
                <div class="bg-gradient-to-br from-violet-500/20 to-purple-500/10 backdrop-blur-lg border border-violet-300/30 rounded-2xl p-4 md:p-6 text-center hover:scale-105 transition-all">
                    <div class="text-4xl mb-3">🔮</div>
                    <h4 class="text-lg font-bold text-white">AI Tarot</h4>
                    <p class="text-white/60 text-xs mt-1">ดูดวง AI</p>
                </div>

                <!-- QR/Barcode -->
                <div class="bg-gradient-to-br from-gray-500/20 to-slate-500/10 backdrop-blur-lg border border-gray-300/30 rounded-2xl p-4 md:p-6 text-center hover:scale-105 transition-all">
                    <div class="text-4xl mb-3">📱</div>
                    <h4 class="text-lg font-bold text-white">QR & Barcode</h4>
                    <p class="text-white/60 text-xs mt-1">สแกนและสร้าง</p>
                </div>

                <!-- Software Sales -->
                <div class="bg-gradient-to-br from-cyan-500/20 to-blue-500/10 backdrop-blur-lg border border-cyan-300/30 rounded-2xl p-4 md:p-6 text-center hover:scale-105 transition-all">
                    <div class="text-4xl mb-3">💻</div>
                    <h4 class="text-lg font-bold text-white">Software Sales</h4>
                    <p class="text-white/60 text-xs mt-1">ขายซอฟต์แวร์</p>
                </div>

                <!-- Forum -->
                <div class="bg-gradient-to-br from-orange-500/20 to-amber-500/10 backdrop-blur-lg border border-orange-300/30 rounded-2xl p-4 md:p-6 text-center hover:scale-105 transition-all">
                    <div class="text-4xl mb-3">💬</div>
                    <h4 class="text-lg font-bold text-white">Forum</h4>
                    <p class="text-white/60 text-xs mt-1">ชุมชนออนไลน์</p>
                </div>

                <!-- NFC Cards -->
                <div class="bg-gradient-to-br from-blue-500/20 to-indigo-500/10 backdrop-blur-lg border border-blue-300/30 rounded-2xl p-4 md:p-6 text-center hover:scale-105 transition-all">
                    <div class="text-4xl mb-3">💳</div>
                    <h4 class="text-lg font-bold text-white">NFC Cards</h4>
                    <p class="text-white/60 text-xs mt-1">บัตรสมาชิก</p>
                </div>
            </div>

            <div class="mt-8 bg-gradient-to-r from-purple-500/20 to-pink-500/20 backdrop-blur-md border border-purple-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-xl md:text-2xl text-white font-bold">
                    ✨ ทุกระบบทำงานร่วมกันอย่างลงตัว ไม่ต้องซื้อแยก
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 9: ความปลอดภัย & ความน่าเชื่อถือ -->
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

<!-- Slide 10: ทำไมต้องเลือก TP-Affiliate Pro -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-blue-900/90 via-purple-900/80 to-pink-900/90 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-8 md:mb-12 text-center">
                🚀 ทำไมต้องเลือกเรา?
            </h2>

            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <!-- All-in-One -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 text-center hover:bg-white/15 transition-all shadow-xl">
                    <div class="text-5xl mb-4">🎯</div>
                    <h3 class="text-2xl font-bold text-white mb-3">All-in-One</h3>
                    <p class="text-white/80">
                        รวมทุกระบบไว้ในที่เดียว<br>
                        ไม่ต้องซื้อหลายระบบ<br>
                        <strong class="text-emerald-300">ประหยัดกว่า 70%</strong>
                    </p>
                </div>

                <!-- Ready to Use -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 text-center hover:bg-white/15 transition-all shadow-xl">
                    <div class="text-5xl mb-4">⚡</div>
                    <h3 class="text-2xl font-bold text-white mb-3">พร้อมใช้งาน</h3>
                    <p class="text-white/80">
                        ติดตั้งเสร็จ ใช้ได้เลย<br>
                        ไม่ต้องพัฒนาเพิ่ม<br>
                        <strong class="text-amber-300">ประหยัดเวลาหลายเดือน</strong>
                    </p>
                </div>

                <!-- Support -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 text-center hover:bg-white/15 transition-all shadow-xl">
                    <div class="text-5xl mb-4">🤝</div>
                    <h3 class="text-2xl font-bold text-white mb-3">Support ดีเยี่ยม</h3>
                    <p class="text-white/80">
                        ทีมงานคนไทย<br>
                        พร้อมช่วยเหลือ 24/7<br>
                        <strong class="text-blue-300">ไม่ทิ้งให้เผชิญปัญหาคนเดียว</strong>
                    </p>
                </div>
            </div>

            <!-- Comparison -->
            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 md:p-8 shadow-xl">
                <h3 class="text-2xl font-bold text-white mb-6 text-center">เปรียบเทียบกับการพัฒนาเอง</h3>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-red-500/10 border border-red-300/30 rounded-xl p-4">
                        <h4 class="text-lg font-bold text-red-300 mb-3">❌ พัฒนาเอง</h4>
                        <ul class="space-y-2 text-white/80 text-sm">
                            <li>• ใช้เวลา 6-12 เดือน</li>
                            <li>• ค่าใช้จ่าย 500,000+ บาท</li>
                            <li>• ต้องจ้างทีมพัฒนา</li>
                            <li>• ความเสี่ยงสูง</li>
                        </ul>
                    </div>
                    <div class="bg-emerald-500/10 border border-emerald-300/30 rounded-xl p-4">
                        <h4 class="text-lg font-bold text-emerald-300 mb-3">✅ {{ $appName }}</h4>
                        <ul class="space-y-2 text-white/80 text-sm">
                            <li>• ใช้งานได้ทันที</li>
                            <li>• ราคาย่อมเยา</li>
                            <li>• ทีม Support พร้อม</li>
                            <li>• อัพเดทต่อเนื่อง</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 11: แพ็คเกจและราคา -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-yellow-900/90 via-amber-900/80 to-orange-900/90 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-8 md:mb-12 text-center">
                💎 แพ็คเกจและบริการ
            </h2>

            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <!-- Starter -->
                <div class="bg-gradient-to-br from-gray-500/20 to-gray-600/20 backdrop-blur-sm border border-gray-300/30 rounded-2xl p-6 hover:scale-105 transition-all">
                    <div class="text-center">
                        <div class="text-4xl mb-3">🥉</div>
                        <h4 class="text-2xl font-bold text-white mb-2">Starter</h4>
                        <p class="text-white/70 text-sm mb-4">เหมาะสำหรับเริ่มต้น</p>
                        <ul class="space-y-2 text-white/90 text-sm text-left mb-6">
                            <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> 1 โดเมน</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> ระบบพื้นฐานครบ</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Support 6 เดือน</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Updates 1 ปี</li>
                        </ul>
                        <div class="bg-white/10 rounded-xl p-3">
                            <p class="text-white/60 text-xs">ติดต่อสอบถามราคา</p>
                        </div>
                    </div>
                </div>

                <!-- Professional -->
                <div class="bg-gradient-to-br from-blue-500/20 to-indigo-600/20 backdrop-blur-sm border-2 border-blue-400/50 rounded-2xl p-6 hover:scale-105 transition-all shadow-xl relative">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-gradient-to-r from-blue-500 to-indigo-500 px-4 py-1 rounded-full text-white text-xs font-bold">
                        แนะนำ
                    </div>
                    <div class="text-center">
                        <div class="text-4xl mb-3">🥈</div>
                        <h4 class="text-2xl font-bold text-white mb-2">Professional</h4>
                        <p class="text-white/70 text-sm mb-4">เหมาะสำหรับธุรกิจ</p>
                        <ul class="space-y-2 text-white/90 text-sm text-left mb-6">
                            <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> 5 โดเมน</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> ระบบครบทุกฟีเจอร์</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Support 1 ปี</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Updates ตลอดชีพ</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Priority Support</li>
                        </ul>
                        <div class="bg-white/10 rounded-xl p-3">
                            <p class="text-white/60 text-xs">ติดต่อสอบถามราคา</p>
                        </div>
                    </div>
                </div>

                <!-- Enterprise -->
                <div class="bg-gradient-to-br from-yellow-500/20 to-orange-600/20 backdrop-blur-sm border border-yellow-300/30 rounded-2xl p-6 hover:scale-105 transition-all shadow-xl">
                    <div class="text-center">
                        <div class="text-4xl mb-3">🥇</div>
                        <h4 class="text-2xl font-bold text-white mb-2">Enterprise</h4>
                        <p class="text-white/70 text-sm mb-4">เหมาะสำหรับองค์กร</p>
                        <ul class="space-y-2 text-white/90 text-sm text-left mb-6">
                            <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> ไม่จำกัดโดเมน</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Custom Features</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Support ตลอดชีพ</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Dedicated Support</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> White Label</li>
                        </ul>
                        <div class="bg-white/10 rounded-xl p-3">
                            <p class="text-white/60 text-xs">ติดต่อสอบถามราคา</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-amber-500/20 to-orange-500/20 backdrop-blur-md border border-amber-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-xl md:text-2xl text-white font-bold">
                    📞 ติดต่อทีมขายเพื่อรับข้อเสนอพิเศษ
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 12: Thank You -->
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
