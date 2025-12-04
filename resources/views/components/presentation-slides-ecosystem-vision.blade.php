{{--
    Ecosystem & Vision Slides - ระบบนิเวศและวิสัยทัศน์
    เนื้อหา: Ecosystem, Integration, Partnership, Vision
--}}

{{-- Slide 1: Ecosystem Introduction --}}
<div class="slide" data-topic="ecosystem-vision">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="text-center max-w-5xl">
            {{-- Icon --}}
            <div class="mb-8">
                <div class="w-32 h-32 mx-auto bg-gradient-to-br from-teal-500 via-cyan-500 to-blue-500 rounded-3xl flex items-center justify-center shadow-2xl">
                    <i class="fas fa-globe text-5xl text-white"></i>
                </div>
            </div>

            <h1 class="text-5xl md:text-6xl font-black text-white mb-6">
                TP Ecosystem
                <span class="block text-3xl font-light text-white/80 mt-4">ระบบนิเวศที่เชื่อมโยงทุกสิ่ง</span>
            </h1>

            <p class="text-xl md:text-2xl text-white/70 mb-8 max-w-3xl mx-auto">
                ระบบนิเวศที่ครบวงจร เชื่อมต่อ
                <span class="text-cyan-400 font-bold">ทุกแพลตฟอร์ม</span>
                และ<span class="text-green-400 font-bold">ทุกบริการ</span>
                เข้าด้วยกันอย่างลงตัว
            </p>

            {{-- Ecosystem Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-10">
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                    <div class="text-3xl font-black text-teal-400">20+</div>
                    <div class="text-white/60 text-sm">Systems</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                    <div class="text-3xl font-black text-cyan-400">100+</div>
                    <div class="text-white/60 text-sm">APIs</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                    <div class="text-3xl font-black text-blue-400">50+</div>
                    <div class="text-white/60 text-sm">Partners</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                    <div class="text-3xl font-black text-purple-400">1</div>
                    <div class="text-white/60 text-sm">Platform</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 2: Ecosystem Map --}}
<div class="slide" data-topic="ecosystem-vision">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="w-full max-w-6xl">
            <div class="text-center mb-8">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    <i class="fas fa-project-diagram text-cyan-400 mr-4"></i>
                    แผนผัง Ecosystem
                </h2>
                <p class="text-xl text-white/70">ทุกระบบเชื่อมต่อกันเป็นหนึ่งเดียว</p>
            </div>

            {{-- Ecosystem Visual --}}
            <div class="relative">
                {{-- Center Hub --}}
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-10">
                    <div class="w-32 h-32 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center shadow-2xl animate-pulse">
                        <span class="text-2xl font-black text-white">TP</span>
                    </div>
                </div>

                {{-- Orbiting Systems --}}
                <div class="grid grid-cols-3 md:grid-cols-4 gap-4 min-h-[400px] items-center">
                    {{-- Row 1 --}}
                    <div class="bg-gradient-to-br from-orange-500/20 to-red-500/20 backdrop-blur-sm rounded-xl p-4 border border-orange-500/30 text-center">
                        <i class="fas fa-network-wired text-orange-400 text-2xl mb-2"></i>
                        <div class="text-white font-bold text-sm">MLM</div>
                        <div class="text-white/60 text-xs">Network</div>
                    </div>

                    <div class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 backdrop-blur-sm rounded-xl p-4 border border-green-500/30 text-center">
                        <i class="fas fa-shopping-cart text-green-400 text-2xl mb-2"></i>
                        <div class="text-white font-bold text-sm">E-Commerce</div>
                        <div class="text-white/60 text-xs">Marketplace</div>
                    </div>

                    <div class="bg-gradient-to-br from-yellow-500/20 to-orange-500/20 backdrop-blur-sm rounded-xl p-4 border border-yellow-500/30 text-center">
                        <i class="fas fa-coins text-yellow-400 text-2xl mb-2"></i>
                        <div class="text-white font-bold text-sm">TPIX</div>
                        <div class="text-white/60 text-xs">Blockchain</div>
                    </div>

                    <div class="bg-gradient-to-br from-cyan-500/20 to-blue-500/20 backdrop-blur-sm rounded-xl p-4 border border-cyan-500/30 text-center">
                        <i class="fas fa-robot text-cyan-400 text-2xl mb-2"></i>
                        <div class="text-white font-bold text-sm">AI Bot</div>
                        <div class="text-white/60 text-xs">Automation</div>
                    </div>

                    {{-- Row 2 --}}
                    <div class="bg-gradient-to-br from-purple-500/20 to-pink-500/20 backdrop-blur-sm rounded-xl p-4 border border-purple-500/30 text-center">
                        <i class="fas fa-graduation-cap text-purple-400 text-2xl mb-2"></i>
                        <div class="text-white font-bold text-sm">Academy</div>
                        <div class="text-white/60 text-xs">Learning</div>
                    </div>

                    <div class="md:col-span-2 bg-gradient-to-br from-indigo-500/10 to-purple-500/10 backdrop-blur-sm rounded-xl p-8 border border-indigo-500/20 flex items-center justify-center">
                        <div class="text-center">
                            <div class="text-4xl font-black bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">TP Core</div>
                            <div class="text-white/60 text-sm mt-2">Central Hub</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-teal-500/20 to-green-500/20 backdrop-blur-sm rounded-xl p-4 border border-teal-500/30 text-center">
                        <i class="fas fa-hotel text-teal-400 text-2xl mb-2"></i>
                        <div class="text-white font-bold text-sm">Hotel</div>
                        <div class="text-white/60 text-xs">Booking</div>
                    </div>

                    {{-- Row 3 --}}
                    <div class="bg-gradient-to-br from-blue-500/20 to-indigo-500/20 backdrop-blur-sm rounded-xl p-4 border border-blue-500/30 text-center">
                        <i class="fas fa-wallet text-blue-400 text-2xl mb-2"></i>
                        <div class="text-white font-bold text-sm">E-Wallet</div>
                        <div class="text-white/60 text-xs">Payment</div>
                    </div>

                    <div class="bg-gradient-to-br from-rose-500/20 to-red-500/20 backdrop-blur-sm rounded-xl p-4 border border-rose-500/30 text-center">
                        <i class="fas fa-utensils text-rose-400 text-2xl mb-2"></i>
                        <div class="text-white font-bold text-sm">Food</div>
                        <div class="text-white/60 text-xs">Passport</div>
                    </div>

                    <div class="bg-gradient-to-br from-fuchsia-500/20 to-purple-500/20 backdrop-blur-sm rounded-xl p-4 border border-fuchsia-500/30 text-center">
                        <i class="fas fa-code text-fuchsia-400 text-2xl mb-2"></i>
                        <div class="text-white font-bold text-sm">Software</div>
                        <div class="text-white/60 text-xs">Sales</div>
                    </div>

                    <div class="bg-gradient-to-br from-lime-500/20 to-green-500/20 backdrop-blur-sm rounded-xl p-4 border border-lime-500/30 text-center">
                        <i class="fab fa-line text-lime-400 text-2xl mb-2"></i>
                        <div class="text-white font-bold text-sm">LINE OA</div>
                        <div class="text-white/60 text-xs">Integration</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 3: Integration & APIs --}}
<div class="slide" data-topic="ecosystem-vision">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="w-full max-w-6xl">
            <div class="text-center mb-10">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    <i class="fas fa-plug text-blue-400 mr-4"></i>
                    การเชื่อมต่อ & APIs
                </h2>
                <p class="text-xl text-white/70">เชื่อมต่อกับทุกแพลตฟอร์มที่คุณใช้งาน</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                {{-- Payment Gateways --}}
                <div class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 backdrop-blur-sm rounded-2xl p-6 border border-green-500/30">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-credit-card text-green-400"></i>
                        Payment Gateways
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white/10 rounded-lg p-3 flex items-center gap-2">
                            <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center text-white text-xs font-bold">2C2P</div>
                            <span class="text-white/80 text-sm">2C2P</span>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 flex items-center gap-2">
                            <div class="w-8 h-8 bg-purple-600 rounded flex items-center justify-center text-white text-xs font-bold">OM</div>
                            <span class="text-white/80 text-sm">Omise</span>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 flex items-center gap-2">
                            <div class="w-8 h-8 bg-blue-500 rounded flex items-center justify-center text-white text-xs font-bold">PP</div>
                            <span class="text-white/80 text-sm">PromptPay</span>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 flex items-center gap-2">
                            <div class="w-8 h-8 bg-orange-500 rounded flex items-center justify-center text-white text-xs font-bold">TB</div>
                            <span class="text-white/80 text-sm">TrueMoney</span>
                        </div>
                    </div>
                </div>

                {{-- Social & Messaging --}}
                <div class="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 backdrop-blur-sm rounded-2xl p-6 border border-blue-500/30">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-comments text-blue-400"></i>
                        Social & Messaging
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white/10 rounded-lg p-3 flex items-center gap-2">
                            <div class="w-8 h-8 bg-[#06C755] rounded flex items-center justify-center text-white text-xs font-bold">LINE</div>
                            <span class="text-white/80 text-sm">LINE OA</span>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 flex items-center gap-2">
                            <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center text-white text-xs font-bold">FB</div>
                            <span class="text-white/80 text-sm">Facebook</span>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 flex items-center gap-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded flex items-center justify-center text-white text-xs font-bold">IG</div>
                            <span class="text-white/80 text-sm">Instagram</span>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 flex items-center gap-2">
                            <div class="w-8 h-8 bg-black rounded flex items-center justify-center text-white text-xs font-bold">TT</div>
                            <span class="text-white/80 text-sm">TikTok</span>
                        </div>
                    </div>
                </div>

                {{-- Cloud Services --}}
                <div class="bg-gradient-to-br from-orange-500/20 to-red-500/20 backdrop-blur-sm rounded-2xl p-6 border border-orange-500/30">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-cloud text-orange-400"></i>
                        Cloud Services
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white/10 rounded-lg p-3 flex items-center gap-2">
                            <div class="w-8 h-8 bg-[#4285F4] rounded flex items-center justify-center text-white text-xs font-bold">G</div>
                            <span class="text-white/80 text-sm">Google Cloud</span>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 flex items-center gap-2">
                            <div class="w-8 h-8 bg-[#FF9900] rounded flex items-center justify-center text-white text-xs font-bold">AWS</div>
                            <span class="text-white/80 text-sm">AWS</span>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 flex items-center gap-2">
                            <div class="w-8 h-8 bg-[#00A4EF] rounded flex items-center justify-center text-white text-xs font-bold">Az</div>
                            <span class="text-white/80 text-sm">Azure</span>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 flex items-center gap-2">
                            <div class="w-8 h-8 bg-[#E4405F] rounded flex items-center justify-center text-white text-xs font-bold">CF</div>
                            <span class="text-white/80 text-sm">Cloudflare</span>
                        </div>
                    </div>
                </div>

                {{-- Blockchain & Crypto --}}
                <div class="bg-gradient-to-br from-purple-500/20 to-pink-500/20 backdrop-blur-sm rounded-2xl p-6 border border-purple-500/30">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-link text-purple-400"></i>
                        Blockchain & Crypto
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white/10 rounded-lg p-3 flex items-center gap-2">
                            <div class="w-8 h-8 bg-[#627EEA] rounded flex items-center justify-center text-white text-xs font-bold">ETH</div>
                            <span class="text-white/80 text-sm">Ethereum</span>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 flex items-center gap-2">
                            <div class="w-8 h-8 bg-[#F3BA2F] rounded flex items-center justify-center text-white text-xs font-bold">BNB</div>
                            <span class="text-white/80 text-sm">BSC</span>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 flex items-center gap-2">
                            <div class="w-8 h-8 bg-[#8247E5] rounded flex items-center justify-center text-white text-xs font-bold">POL</div>
                            <span class="text-white/80 text-sm">Polygon</span>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 flex items-center gap-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-orange-500 rounded flex items-center justify-center text-white text-xs font-bold">TP</div>
                            <span class="text-white/80 text-sm">TPIX Chain</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 4: Vision 2025 --}}
<div class="slide" data-topic="ecosystem-vision">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="w-full max-w-5xl">
            <div class="text-center mb-10">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    <i class="fas fa-eye text-purple-400 mr-4"></i>
                    Vision 2025
                </h2>
                <p class="text-xl text-white/70">วิสัยทัศน์สู่อนาคต</p>
            </div>

            {{-- Vision Statement --}}
            <div class="bg-gradient-to-r from-purple-500/20 via-pink-500/20 to-orange-500/20 backdrop-blur-sm rounded-3xl p-8 border border-purple-500/30 mb-8">
                <div class="text-center">
                    <i class="fas fa-quote-left text-4xl text-purple-400/50 mb-4"></i>
                    <p class="text-2xl md:text-3xl font-light text-white leading-relaxed">
                        "เราจะเป็น <span class="font-bold text-yellow-400">All-in-One Platform</span> ที่ใหญ่ที่สุดในเอเชียตะวันออกเฉียงใต้
                        ช่วยให้ <span class="font-bold text-green-400">ผู้ประกอบการ 1 ล้านคน</span> สร้างธุรกิจและรายได้อย่างยั่งยืน"
                    </p>
                    <i class="fas fa-quote-right text-4xl text-purple-400/50 mt-4"></i>
                </div>
            </div>

            {{-- Goals --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 backdrop-blur-sm rounded-2xl p-6 border border-blue-500/30 text-center">
                    <div class="w-16 h-16 mx-auto bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center mb-4">
                        <i class="fas fa-users text-2xl text-white"></i>
                    </div>
                    <div class="text-3xl font-black text-blue-400">1M+</div>
                    <div class="text-white font-bold">สมาชิก</div>
                    <div class="text-white/60 text-sm mt-2">ผู้ใช้งานทั่วภูมิภาค</div>
                </div>

                <div class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 backdrop-blur-sm rounded-2xl p-6 border border-green-500/30 text-center">
                    <div class="w-16 h-16 mx-auto bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center mb-4">
                        <i class="fas fa-globe-asia text-2xl text-white"></i>
                    </div>
                    <div class="text-3xl font-black text-green-400">10+</div>
                    <div class="text-white font-bold">ประเทศ</div>
                    <div class="text-white/60 text-sm mt-2">ขยายตลาดระหว่างประเทศ</div>
                </div>

                <div class="bg-gradient-to-br from-yellow-500/20 to-orange-500/20 backdrop-blur-sm rounded-2xl p-6 border border-yellow-500/30 text-center">
                    <div class="w-16 h-16 mx-auto bg-gradient-to-br from-yellow-500 to-orange-500 rounded-2xl flex items-center justify-center mb-4">
                        <i class="fas fa-chart-line text-2xl text-white"></i>
                    </div>
                    <div class="text-3xl font-black text-yellow-400">$100M</div>
                    <div class="text-white font-bold">GMV</div>
                    <div class="text-white/60 text-sm mt-2">มูลค่าการซื้อขายรวม</div>
                </div>
            </div>
        </div>
    </div>
</div>
