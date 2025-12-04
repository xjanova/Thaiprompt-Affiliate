{{--
    System Overview Slides - ภาพรวมระบบ TP-Affiliate
    เนื้อหา: 20+ โมเดลธุรกิจ, แนวทางอนาคต
--}}

{{-- Slide 1: Title & Introduction --}}
<div class="slide" data-topic="system-overview">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="text-center max-w-5xl">
            {{-- Logo --}}
            <div class="mb-8">
                <div class="w-32 h-32 mx-auto bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 rounded-3xl flex items-center justify-center shadow-2xl transform hover:scale-105 transition-transform">
                    <span class="text-5xl font-black text-white">TP</span>
                </div>
            </div>

            <h1 class="text-5xl md:text-7xl font-black text-white mb-6 leading-tight">
                TP-Affiliate
                <span class="block text-3xl md:text-4xl font-light text-white/80 mt-4">All-in-One Business Platform</span>
            </h1>

            <p class="text-xl md:text-2xl text-white/70 mb-8 max-w-3xl mx-auto">
                แพลตฟอร์มธุรกิจครบวงจรที่รวม <span class="text-yellow-400 font-bold">20+ โมเดลธุรกิจ</span>
                ไว้ในที่เดียว พร้อมเทคโนโลยีล้ำสมัย AI, Blockchain และ Automation
            </p>

            {{-- Key Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-12">
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                    <div class="text-3xl md:text-4xl font-black text-yellow-400">20+</div>
                    <div class="text-white/70 text-sm">โมเดลธุรกิจ</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                    <div class="text-3xl md:text-4xl font-black text-green-400">339+</div>
                    <div class="text-white/70 text-sm">Models</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                    <div class="text-3xl md:text-4xl font-black text-blue-400">100+</div>
                    <div class="text-white/70 text-sm">API Endpoints</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                    <div class="text-3xl md:text-4xl font-black text-pink-400">∞</div>
                    <div class="text-white/70 text-sm">ความเป็นไปได้</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 2: 20+ Business Models Overview --}}
<div class="slide" data-topic="system-overview">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="w-full max-w-6xl">
            <div class="text-center mb-8">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    <i class="fas fa-cubes text-purple-400 mr-4"></i>
                    20+ โมเดลธุรกิจในระบบเดียว
                </h2>
                <p class="text-xl text-white/70">ครอบคลุมทุกรูปแบบธุรกิจที่คุณต้องการ</p>
            </div>

            {{-- Business Models Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3">
                {{-- MLM & Affiliate --}}
                <div class="bg-gradient-to-br from-orange-500/20 to-red-500/20 backdrop-blur-sm rounded-xl p-4 border border-orange-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-network-wired text-orange-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">MLM System</div>
                    <div class="text-white/60 text-xs">Unilevel & Binary</div>
                </div>

                <div class="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 backdrop-blur-sm rounded-xl p-4 border border-blue-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-handshake text-blue-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">Affiliate</div>
                    <div class="text-white/60 text-xs">Commission System</div>
                </div>

                {{-- E-Commerce --}}
                <div class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 backdrop-blur-sm rounded-xl p-4 border border-green-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-shopping-cart text-green-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">E-Commerce</div>
                    <div class="text-white/60 text-xs">Marketplace</div>
                </div>

                <div class="bg-gradient-to-br from-purple-500/20 to-pink-500/20 backdrop-blur-sm rounded-xl p-4 border border-purple-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-store text-purple-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">Multi-Vendor</div>
                    <div class="text-white/60 text-xs">Seller Platform</div>
                </div>

                {{-- AI & Bot --}}
                <div class="bg-gradient-to-br from-cyan-500/20 to-blue-500/20 backdrop-blur-sm rounded-xl p-4 border border-cyan-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-robot text-cyan-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">AI Chatbot</div>
                    <div class="text-white/60 text-xs">LINE & Web</div>
                </div>

                <div class="bg-gradient-to-br from-indigo-500/20 to-purple-500/20 backdrop-blur-sm rounded-xl p-4 border border-indigo-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-brain text-indigo-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">AI Bot Market</div>
                    <div class="text-white/60 text-xs">Bot Marketplace</div>
                </div>

                {{-- Blockchain --}}
                <div class="bg-gradient-to-br from-yellow-500/20 to-orange-500/20 backdrop-blur-sm rounded-xl p-4 border border-yellow-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-coins text-yellow-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">TPIX Token</div>
                    <div class="text-white/60 text-xs">Cryptocurrency</div>
                </div>

                <div class="bg-gradient-to-br from-amber-500/20 to-yellow-500/20 backdrop-blur-sm rounded-xl p-4 border border-amber-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-wallet text-amber-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">E-Wallet</div>
                    <div class="text-white/60 text-xs">Multi-Currency</div>
                </div>

                {{-- Hotel & Travel --}}
                <div class="bg-gradient-to-br from-teal-500/20 to-green-500/20 backdrop-blur-sm rounded-xl p-4 border border-teal-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-hotel text-teal-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">Hotel Booking</div>
                    <div class="text-white/60 text-xs">จองห้องพัก</div>
                </div>

                <div class="bg-gradient-to-br from-sky-500/20 to-blue-500/20 backdrop-blur-sm rounded-xl p-4 border border-sky-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-plane text-sky-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">Travel</div>
                    <div class="text-white/60 text-xs">ท่องเที่ยว</div>
                </div>

                {{-- Food & Supply Chain --}}
                <div class="bg-gradient-to-br from-lime-500/20 to-green-500/20 backdrop-blur-sm rounded-xl p-4 border border-lime-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-utensils text-lime-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">Food Passport</div>
                    <div class="text-white/60 text-xs">Traceability</div>
                </div>

                <div class="bg-gradient-to-br from-emerald-500/20 to-teal-500/20 backdrop-blur-sm rounded-xl p-4 border border-emerald-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-truck text-emerald-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">Supply Chain</div>
                    <div class="text-white/60 text-xs">Logistics</div>
                </div>

                {{-- Software & POS --}}
                <div class="bg-gradient-to-br from-violet-500/20 to-purple-500/20 backdrop-blur-sm rounded-xl p-4 border border-violet-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-code text-violet-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">Software Sales</div>
                    <div class="text-white/60 text-xs">License System</div>
                </div>

                <div class="bg-gradient-to-br from-fuchsia-500/20 to-pink-500/20 backdrop-blur-sm rounded-xl p-4 border border-fuchsia-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-cash-register text-fuchsia-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">POS System</div>
                    <div class="text-white/60 text-xs">Point of Sale</div>
                </div>

                {{-- Academy & CMS --}}
                <div class="bg-gradient-to-br from-rose-500/20 to-red-500/20 backdrop-blur-sm rounded-xl p-4 border border-rose-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-graduation-cap text-rose-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">Academy</div>
                    <div class="text-white/60 text-xs">Learning Platform</div>
                </div>

                <div class="bg-gradient-to-br from-slate-500/20 to-gray-500/20 backdrop-blur-sm rounded-xl p-4 border border-slate-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-newspaper text-slate-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">CMS</div>
                    <div class="text-white/60 text-xs">Content Management</div>
                </div>

                {{-- Analytics & Automation --}}
                <div class="bg-gradient-to-br from-red-500/20 to-orange-500/20 backdrop-blur-sm rounded-xl p-4 border border-red-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-chart-line text-red-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">Analytics</div>
                    <div class="text-white/60 text-xs">Dashboard</div>
                </div>

                <div class="bg-gradient-to-br from-pink-500/20 to-rose-500/20 backdrop-blur-sm rounded-xl p-4 border border-pink-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-cogs text-pink-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">Automation</div>
                    <div class="text-white/60 text-xs">Marketing Auto</div>
                </div>

                {{-- Community & More --}}
                <div class="bg-gradient-to-br from-blue-500/20 to-indigo-500/20 backdrop-blur-sm rounded-xl p-4 border border-blue-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-users text-blue-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">Community</div>
                    <div class="text-white/60 text-xs">Network</div>
                </div>

                <div class="bg-gradient-to-br from-gray-500/20 to-slate-500/20 backdrop-blur-sm rounded-xl p-4 border border-gray-500/30 hover:scale-105 transition-transform">
                    <i class="fas fa-ellipsis-h text-gray-400 text-2xl mb-2"></i>
                    <div class="text-white font-bold text-sm">และอีกมากมาย</div>
                    <div class="text-white/60 text-xs">Expandable</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 3: Core Technology Stack --}}
<div class="slide" data-topic="system-overview">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="w-full max-w-6xl">
            <div class="text-center mb-10">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    <i class="fas fa-microchip text-cyan-400 mr-4"></i>
                    เทคโนโลยีหลัก
                </h2>
                <p class="text-xl text-white/70">สร้างด้วยเทคโนโลยีระดับ Enterprise</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Backend --}}
                <div class="bg-gradient-to-br from-red-500/20 to-orange-500/20 backdrop-blur-sm rounded-2xl p-6 border border-red-500/30">
                    <div class="text-center mb-4">
                        <div class="w-16 h-16 mx-auto bg-gradient-to-br from-red-500 to-orange-500 rounded-2xl flex items-center justify-center mb-4">
                            <i class="fab fa-laravel text-3xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Backend</h3>
                    </div>
                    <ul class="space-y-3 text-white/80">
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-400"></i>
                            Laravel 11 Framework
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-400"></i>
                            PHP 8.1+ Modern
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-400"></i>
                            MySQL 8.0 Database
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-400"></i>
                            Redis Cache & Queue
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-400"></i>
                            RESTful API
                        </li>
                    </ul>
                </div>

                {{-- Frontend --}}
                <div class="bg-gradient-to-br from-cyan-500/20 to-blue-500/20 backdrop-blur-sm rounded-2xl p-6 border border-cyan-500/30">
                    <div class="text-center mb-4">
                        <div class="w-16 h-16 mx-auto bg-gradient-to-br from-cyan-500 to-blue-500 rounded-2xl flex items-center justify-center mb-4">
                            <i class="fab fa-js text-3xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Frontend</h3>
                    </div>
                    <ul class="space-y-3 text-white/80">
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-400"></i>
                            Tailwind CSS 3.4
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-400"></i>
                            Alpine.js Reactive
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-400"></i>
                            Vite Build Tool
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-400"></i>
                            Chart.js & D3.js
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-400"></i>
                            Three.js 3D
                        </li>
                    </ul>
                </div>

                {{-- Advanced --}}
                <div class="bg-gradient-to-br from-purple-500/20 to-pink-500/20 backdrop-blur-sm rounded-2xl p-6 border border-purple-500/30">
                    <div class="text-center mb-4">
                        <div class="w-16 h-16 mx-auto bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center mb-4">
                            <i class="fas fa-rocket text-3xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Advanced</h3>
                    </div>
                    <ul class="space-y-3 text-white/80">
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-400"></i>
                            AI Integration (GPT)
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-400"></i>
                            Blockchain (TPIX)
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-400"></i>
                            LINE API (LIFF)
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-400"></i>
                            Google Cloud Vision
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-400"></i>
                            Web3 & Smart Contract
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Slide 4: Future Roadmap --}}
<div class="slide" data-topic="system-overview">
    <div class="absolute inset-0 flex items-center justify-center p-8">
        <div class="w-full max-w-6xl">
            <div class="text-center mb-10">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    <i class="fas fa-road text-green-400 mr-4"></i>
                    แนวทางอนาคต
                </h2>
                <p class="text-xl text-white/70">Roadmap สู่ความสำเร็จ</p>
            </div>

            {{-- Timeline --}}
            <div class="relative">
                {{-- Timeline Line --}}
                <div class="absolute left-1/2 transform -translate-x-1/2 h-full w-1 bg-gradient-to-b from-blue-500 via-purple-500 to-pink-500 hidden md:block"></div>

                <div class="space-y-8">
                    {{-- Phase 1 --}}
                    <div class="flex flex-col md:flex-row items-center gap-4">
                        <div class="md:w-1/2 md:text-right">
                            <div class="bg-gradient-to-r from-blue-500/20 to-cyan-500/20 backdrop-blur-sm rounded-2xl p-6 border border-blue-500/30 inline-block">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="px-3 py-1 bg-blue-500 text-white text-sm font-bold rounded-full">Phase 1</span>
                                    <span class="text-white/60">Q1 2025</span>
                                </div>
                                <h3 class="text-xl font-bold text-white mb-2">Foundation & AI</h3>
                                <ul class="text-white/70 text-sm space-y-1">
                                    <li>• AI Chatbot Advanced</li>
                                    <li>• LINE Bot Automation</li>
                                    <li>• Voice AI Integration</li>
                                </ul>
                            </div>
                        </div>
                        <div class="w-4 h-4 bg-blue-500 rounded-full border-4 border-white shadow-lg z-10 hidden md:block"></div>
                        <div class="md:w-1/2"></div>
                    </div>

                    {{-- Phase 2 --}}
                    <div class="flex flex-col md:flex-row items-center gap-4">
                        <div class="md:w-1/2"></div>
                        <div class="w-4 h-4 bg-purple-500 rounded-full border-4 border-white shadow-lg z-10 hidden md:block"></div>
                        <div class="md:w-1/2">
                            <div class="bg-gradient-to-r from-purple-500/20 to-pink-500/20 backdrop-blur-sm rounded-2xl p-6 border border-purple-500/30 inline-block">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="px-3 py-1 bg-purple-500 text-white text-sm font-bold rounded-full">Phase 2</span>
                                    <span class="text-white/60">Q2 2025</span>
                                </div>
                                <h3 class="text-xl font-bold text-white mb-2">Blockchain & DeFi</h3>
                                <ul class="text-white/70 text-sm space-y-1">
                                    <li>• TPIX DEX Launch</li>
                                    <li>• NFT Marketplace</li>
                                    <li>• Staking & Farming</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Phase 3 --}}
                    <div class="flex flex-col md:flex-row items-center gap-4">
                        <div class="md:w-1/2 md:text-right">
                            <div class="bg-gradient-to-r from-green-500/20 to-emerald-500/20 backdrop-blur-sm rounded-2xl p-6 border border-green-500/30 inline-block">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="px-3 py-1 bg-green-500 text-white text-sm font-bold rounded-full">Phase 3</span>
                                    <span class="text-white/60">Q3 2025</span>
                                </div>
                                <h3 class="text-xl font-bold text-white mb-2">Global Expansion</h3>
                                <ul class="text-white/70 text-sm space-y-1">
                                    <li>• Multi-language Support</li>
                                    <li>• International Partners</li>
                                    <li>• Cross-border Payment</li>
                                </ul>
                            </div>
                        </div>
                        <div class="w-4 h-4 bg-green-500 rounded-full border-4 border-white shadow-lg z-10 hidden md:block"></div>
                        <div class="md:w-1/2"></div>
                    </div>

                    {{-- Phase 4 --}}
                    <div class="flex flex-col md:flex-row items-center gap-4">
                        <div class="md:w-1/2"></div>
                        <div class="w-4 h-4 bg-pink-500 rounded-full border-4 border-white shadow-lg z-10 hidden md:block"></div>
                        <div class="md:w-1/2">
                            <div class="bg-gradient-to-r from-pink-500/20 to-rose-500/20 backdrop-blur-sm rounded-2xl p-6 border border-pink-500/30 inline-block">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="px-3 py-1 bg-pink-500 text-white text-sm font-bold rounded-full">Phase 4</span>
                                    <span class="text-white/60">Q4 2025</span>
                                </div>
                                <h3 class="text-xl font-bold text-white mb-2">Metaverse & Beyond</h3>
                                <ul class="text-white/70 text-sm space-y-1">
                                    <li>• Virtual Showroom</li>
                                    <li>• AR/VR Shopping</li>
                                    <li>• AI Agent Autonomous</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
