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

<!-- Slide 3: ระบบ Affiliate & MLM -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-purple-900/90 via-pink-900/80 to-red-900/90 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-10 left-10 w-96 h-96 bg-purple-400 rounded-full mix-blend-screen filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-pink-400 rounded-full mix-blend-screen filter blur-3xl animate-pulse animation-delay-2000"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-8 md:mb-12 text-center">
                🎯 ระบบ Affiliate & MLM
            </h2>

            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <!-- Affiliate Features -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 hover:bg-white/15 transition-all shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">👥</div>
                        <div class="flex-1 text-white">
                            <h3 class="text-2xl font-bold mb-3">ระบบเครือข่าย Affiliate</h3>
                            <ul class="space-y-2 text-white/90">
                                <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> ไม่จำกัดจำนวนชั้น (Unlimited Levels)</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> คำนวณคอมมิชชั่นอัตโนมัติ</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> แผนการตลาด Unilevel & Binary</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> ระบบ Rank และ Bonus</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Commission System -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 hover:bg-white/15 transition-all shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">💰</div>
                        <div class="flex-1 text-white">
                            <h3 class="text-2xl font-bold mb-3">ระบบคอมมิชชั่นอัจฉริยะ</h3>
                            <ul class="space-y-2 text-white/90">
                                <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Direct Commission</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Override Commission</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Pool Bonus & Matching Bonus</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> รายงานรายได้แบบ Real-time</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Referral Links -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 hover:bg-white/15 transition-all shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">🔗</div>
                        <div class="flex-1 text-white">
                            <h3 class="text-2xl font-bold mb-3">ระบบลิงก์แนะนำ</h3>
                            <ul class="space-y-2 text-white/90">
                                <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> สร้างลิงก์อัตโนมัติ</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> QR Code Generator</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> ติดตามการคลิกและ Conversion</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Short URL & Custom Link</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Team Management -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 hover:bg-white/15 transition-all shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">📊</div>
                        <div class="flex-1 text-white">
                            <h3 class="text-2xl font-bold mb-3">ระบบจัดการทีม</h3>
                            <ul class="space-y-2 text-white/90">
                                <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Genealogy Tree แบบ Interactive</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> รายงานยอดทีมแบบละเอียด</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> ระบบแจ้งเตือนสมาชิกใหม่</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Leader Dashboard</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-yellow-500/20 to-orange-500/20 backdrop-blur-md border border-yellow-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-xl md:text-2xl text-white font-bold">
                    💡 ระบบ MLM ที่ยืดหยุ่นที่สุด ปรับแต่งได้ตามต้องการ
                </p>
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

<!-- Slide 5: ระบบ AI & Automation -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-cyan-900/90 via-blue-900/80 to-indigo-900/90 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-20 right-20 w-80 h-80 bg-cyan-400 rounded-full mix-blend-screen filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 left-20 w-80 h-80 bg-blue-400 rounded-full mix-blend-screen filter blur-3xl animate-pulse animation-delay-2000"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-8 md:mb-12 text-center">
                🤖 ระบบ AI & Automation
            </h2>

            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <!-- AI Chatbot -->
                <div class="bg-gradient-to-br from-cyan-500/20 to-blue-500/10 backdrop-blur-lg border border-cyan-300/30 rounded-2xl p-6 hover:scale-105 transition-all shadow-xl">
                    <div class="text-5xl mb-4 text-center">💬</div>
                    <h3 class="text-2xl font-bold text-white mb-4 text-center">AI Chatbot</h3>
                    <ul class="space-y-2 text-white/90">
                        <li class="flex items-center gap-2"><span class="text-cyan-400">✓</span> ตอบคำถามอัตโนมัติ 24/7</li>
                        <li class="flex items-center gap-2"><span class="text-cyan-400">✓</span> รองรับหลายภาษา</li>
                        <li class="flex items-center gap-2"><span class="text-cyan-400">✓</span> เชื่อมต่อกับ ChatGPT/Claude</li>
                        <li class="flex items-center gap-2"><span class="text-cyan-400">✓</span> ปรับแต่ง Personality ได้</li>
                    </ul>
                </div>

                <!-- LINE Bot -->
                <div class="bg-gradient-to-br from-green-500/20 to-emerald-500/10 backdrop-blur-lg border border-green-300/30 rounded-2xl p-6 hover:scale-105 transition-all shadow-xl">
                    <div class="text-5xl mb-4 text-center">💚</div>
                    <h3 class="text-2xl font-bold text-white mb-4 text-center">LINE Bot อัจฉริยะ</h3>
                    <ul class="space-y-2 text-white/90">
                        <li class="flex items-center gap-2"><span class="text-green-400">✓</span> LINE Official Account Integration</li>
                        <li class="flex items-center gap-2"><span class="text-green-400">✓</span> Rich Menu & Flex Message</li>
                        <li class="flex items-center gap-2"><span class="text-green-400">✓</span> LINE LIFF Mini App</li>
                        <li class="flex items-center gap-2"><span class="text-green-400">✓</span> Broadcast & Narrowcast</li>
                    </ul>
                </div>

                <!-- Video Automation -->
                <div class="bg-gradient-to-br from-purple-500/20 to-pink-500/10 backdrop-blur-lg border border-purple-300/30 rounded-2xl p-6 hover:scale-105 transition-all shadow-xl">
                    <div class="text-5xl mb-4 text-center">🎬</div>
                    <h3 class="text-2xl font-bold text-white mb-4 text-center">Video Automation</h3>
                    <ul class="space-y-2 text-white/90">
                        <li class="flex items-center gap-2"><span class="text-purple-400">✓</span> สร้างวิดีโอ AI อัตโนมัติ</li>
                        <li class="flex items-center gap-2"><span class="text-purple-400">✓</span> Text-to-Video</li>
                        <li class="flex items-center gap-2"><span class="text-purple-400">✓</span> Video Missions & Rewards</li>
                        <li class="flex items-center gap-2"><span class="text-purple-400">✓</span> Auto-post Social Media</li>
                    </ul>
                </div>

                <!-- Bot Trading -->
                <div class="bg-gradient-to-br from-amber-500/20 to-orange-500/10 backdrop-blur-lg border border-amber-300/30 rounded-2xl p-6 hover:scale-105 transition-all shadow-xl">
                    <div class="text-5xl mb-4 text-center">📈</div>
                    <h3 class="text-2xl font-bold text-white mb-4 text-center">Trading Bot</h3>
                    <ul class="space-y-2 text-white/90">
                        <li class="flex items-center gap-2"><span class="text-amber-400">✓</span> Copy Trading อัตโนมัติ</li>
                        <li class="flex items-center gap-2"><span class="text-amber-400">✓</span> Signal Trading</li>
                        <li class="flex items-center gap-2"><span class="text-amber-400">✓</span> Portfolio Management</li>
                        <li class="flex items-center gap-2"><span class="text-amber-400">✓</span> Risk Management Tools</li>
                    </ul>
                </div>
            </div>

            <div class="bg-gradient-to-r from-cyan-500/20 to-blue-500/20 backdrop-blur-md border border-cyan-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-xl md:text-2xl text-white font-bold">
                    🧠 AI ที่ทำงานแทนคุณ ประหยัดเวลา เพิ่มประสิทธิภาพ
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 6: ระบบ Blockchain & TPIX Token -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-amber-900/90 via-orange-900/80 to-red-900/90 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-1/4 left-1/4 w-80 h-80 bg-amber-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-orange-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-8 md:mb-12 text-center">
                ⛓️ Blockchain & TPIX Token
            </h2>

            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-3xl p-6 md:p-8 mb-8 shadow-2xl">
                <div class="flex flex-col md:flex-row items-center justify-center gap-6 md:gap-12">
                    <div class="text-center">
                        <div class="text-6xl md:text-8xl mb-2">🪙</div>
                        <h3 class="text-3xl md:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-300 to-orange-300">TPIX</h3>
                        <p class="text-white/70">Native Token</p>
                    </div>
                    <div class="text-white text-center md:text-left">
                        <h3 class="text-2xl md:text-3xl font-bold mb-4">TPIX Token Ecosystem</h3>
                        <p class="text-lg md:text-xl text-white/80">
                            Token ของแพลตฟอร์มที่ใช้งานได้จริง<br>
                            ทั้งการชำระเงิน, Staking และ Rewards
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <!-- Staking -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 text-center hover:bg-white/15 transition-all shadow-xl">
                    <div class="text-5xl mb-4">🏦</div>
                    <h3 class="text-xl font-bold text-white mb-3">Staking & Farming</h3>
                    <ul class="space-y-2 text-white/80 text-sm">
                        <li>• Flexible & Locked Staking</li>
                        <li>• APY สูงสุด 30%+</li>
                        <li>• Auto-compound</li>
                        <li>• Multiple Pools</li>
                    </ul>
                </div>

                <!-- Payment -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 text-center hover:bg-white/15 transition-all shadow-xl">
                    <div class="text-5xl mb-4">💸</div>
                    <h3 class="text-xl font-bold text-white mb-3">Crypto Payment</h3>
                    <ul class="space-y-2 text-white/80 text-sm">
                        <li>• รับชำระด้วย Crypto</li>
                        <li>• TPIX, USDT, BNB</li>
                        <li>• Auto Convert</li>
                        <li>• Low Gas Fee</li>
                    </ul>
                </div>

                <!-- NFT -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 text-center hover:bg-white/15 transition-all shadow-xl">
                    <div class="text-5xl mb-4">🎨</div>
                    <h3 class="text-xl font-bold text-white mb-3">NFT & Collectibles</h3>
                    <ul class="space-y-2 text-white/80 text-sm">
                        <li>• NFT Marketplace</li>
                        <li>• Membership NFT</li>
                        <li>• Exclusive Benefits</li>
                        <li>• Royalty System</li>
                    </ul>
                </div>
            </div>

            <div class="bg-gradient-to-r from-amber-500/20 to-orange-500/20 backdrop-blur-md border border-amber-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-xl md:text-2xl text-white font-bold">
                    🔥 เข้าสู่โลก Web3 กับ TPIX Token
                </p>
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
