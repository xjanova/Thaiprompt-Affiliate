@extends('layouts.user-arrow-x')

@section('title', 'คู่มือเสริมทางเศรษฐี Pro - ฉบับสมบูรณ์พร้อม 3D Visualization')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">

    <!-- Hero Section with 3D Background -->
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-transparent to-slate-900/50 z-10"></div>
        <div class="container mx-auto px-4 py-16 relative z-20">
            <div class="text-center mb-8">
                <div class="inline-block mb-6">
                    <div class="text-7xl mb-4 animate-bounce">💎</div>
                    <h1 class="text-6xl font-bold bg-gradient-to-r from-yellow-400 via-amber-500 to-orange-500 bg-clip-text text-transparent drop-shadow-2xl mb-4">
                        คู่มือเสริมทางเศรษฐี Pro
                    </h1>
                    <p class="text-2xl text-yellow-100 font-semibold mb-4">ฉบับสมบูรณ์ พร้อม 3D Visualization</p>
                    <p class="text-lg text-gray-300 max-w-3xl mx-auto">
                        เรียนรู้ทุกวิธีการสร้างรายได้กับเรา พร้อมเครื่องมือ 3D ที่ช่วยให้คุณเข้าใจแผนธุรกิจได้ง่ายขึ้น
                    </p>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto mb-8">
                    <div class="bg-gradient-to-br from-blue-500/20 to-blue-600/20 backdrop-blur-sm rounded-xl p-4 border border-blue-400/30">
                        <div class="text-3xl mb-2">📊</div>
                        <div class="text-2xl font-bold text-blue-400">6+</div>
                        <div class="text-sm text-gray-300">ช่องทางรายได้</div>
                    </div>
                    <div class="bg-gradient-to-br from-green-500/20 to-green-600/20 backdrop-blur-sm rounded-xl p-4 border border-green-400/30">
                        <div class="text-3xl mb-2">💰</div>
                        <div class="text-2xl font-bold text-green-400">∞</div>
                        <div class="text-sm text-gray-300">ไม่จำกัดรายได้</div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-500/20 to-purple-600/20 backdrop-blur-sm rounded-xl p-4 border border-purple-400/30">
                        <div class="text-3xl mb-2">🎯</div>
                        <div class="text-2xl font-bold text-purple-400">5</div>
                        <div class="text-sm text-gray-300">ระดับยศ</div>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500/20 to-amber-600/20 backdrop-blur-sm rounded-xl p-4 border border-amber-400/30">
                        <div class="text-3xl mb-2">🚀</div>
                        <div class="text-2xl font-bold text-amber-400">3D</div>
                        <div class="text-sm text-gray-300">Interactive Tools</div>
                    </div>
                </div>

                <!-- CTA Buttons -->
                <div class="flex flex-wrap justify-center gap-4">
                    <button onclick="scrollToSection('income-overview')"
                            class="px-8 py-4 bg-gradient-to-r from-yellow-400 to-orange-500 text-white font-bold rounded-xl hover:from-yellow-500 hover:to-orange-600 transition-transform hover:scale-[1.02] transition-all shadow-xl">
                        🎯 เริ่มต้นสร้างรายได้
                    </button>
                    <button onclick="scrollToSection('3d-mindmap')"
                            class="px-8 py-4 bg-gradient-to-r from-purple-500 to-pink-500 text-white font-bold rounded-xl hover:from-purple-600 hover:to-pink-600 transition-transform hover:scale-[1.02] transition-all shadow-xl">
                        🧠 ดู 3D Mind Map
                    </button>
                    <button onclick="scrollToSection('income-calculator')"
                            class="px-8 py-4 bg-gradient-to-r from-blue-500 to-cyan-500 text-white font-bold rounded-xl hover:from-blue-600 hover:to-cyan-600 transition-transform hover:scale-[1.02] transition-all shadow-xl">
                        💎 คำนวณรายได้
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-12 max-w-7xl">

        <!-- 3D Mind Map Section -->
        <section id="3d-mindmap" class="mb-16 scroll-mt-20">
            <div class="bg-gradient-to-br from-slate-800/90 to-slate-900/90 backdrop-blur-sm rounded-3xl p-8 shadow-2xl border border-slate-700/50">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center gap-3 mb-4">
                        <span class="text-5xl">🧠</span>
                        <h2 class="text-4xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                            3D Mind Map แผนธุรกิจ
                        </h2>
                    </div>
                    <p class="text-gray-300 text-lg">ภาพรวมระบบรายได้ทั้งหมดในมุมมอง 3 มิติ - หมุน ซูม และสำรวจได้อย่างอิสระ</p>
                </div>

                <!-- 3D Mind Map Container -->
                <div class="relative bg-black/50 rounded-2xl overflow-hidden" style="height: 600px;">
                    <div id="wealth-3d-mindmap" class="w-full h-full"></div>

                    <!-- Controls Overlay -->
                    <div class="absolute top-4 left-4 z-20 space-y-2">
                        <button id="toggle-rotate"
                                class="px-4 py-2 bg-white/10 backdrop-blur-md text-white rounded-lg hover:bg-white/20 transition-all border border-white/20 text-sm font-semibold">
                            🔄 Auto Rotate
                        </button>
                        <button id="reset-camera"
                                class="px-4 py-2 bg-white/10 backdrop-blur-md text-white rounded-lg hover:bg-white/20 transition-all border border-white/20 text-sm font-semibold">
                            📷 Reset View
                        </button>
                    </div>

                    <!-- Info Panel -->
                    <div class="absolute bottom-4 left-4 right-4 z-20">
                        <div class="bg-gradient-to-r from-slate-800/95 to-slate-900/95 backdrop-blur-md rounded-xl p-4 border border-slate-600/50">
                            <div class="text-sm text-gray-300">
                                <strong class="text-yellow-400">💡 วิธีใช้:</strong> ลาก = หมุนดู | ล้อเมาส์ = ซูม | คลิกจุด = ดูรายละเอียด
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Navigation -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-6">
                    <button onclick="focusMindMapNode('รายได้ตรง')"
                            class="p-3 bg-gradient-to-br from-cyan-500/20 to-cyan-600/20 rounded-xl hover:from-cyan-500/30 hover:to-cyan-600/30 transition-all border border-cyan-400/30">
                        <div class="text-2xl mb-1">💵</div>
                        <div class="text-sm font-semibold text-cyan-300">รายได้ตรง</div>
                    </button>
                    <button onclick="focusMindMapNode('Binary Matching')"
                            class="p-3 bg-gradient-to-br from-red-500/20 to-red-600/20 rounded-xl hover:from-red-500/30 hover:to-red-600/30 transition-all border border-red-400/30">
                        <div class="text-2xl mb-1">⚖️</div>
                        <div class="text-sm font-semibold text-red-300">Binary Matching</div>
                    </button>
                    <button onclick="focusMindMapNode('Rank Bonus')"
                            class="p-3 bg-gradient-to-br from-green-500/20 to-green-600/20 rounded-xl hover:from-green-500/30 hover:to-green-600/30 transition-all border border-green-400/30">
                        <div class="text-2xl mb-1">👑</div>
                        <div class="text-sm font-semibold text-green-300">Rank Bonus</div>
                    </button>
                    <button onclick="focusMindMapNode('Sponsor Bonus')"
                            class="p-3 bg-gradient-to-br from-pink-500/20 to-pink-600/20 rounded-xl hover:from-pink-500/30 hover:to-pink-600/30 transition-all border border-pink-400/30">
                        <div class="text-2xl mb-1">🤝</div>
                        <div class="text-sm font-semibold text-pink-300">Sponsor Bonus</div>
                    </button>
                </div>
            </div>
        </section>

        <!-- 3D Income Flow Visualization -->
        <section id="income-flow" class="mb-16 scroll-mt-20">
            <div class="bg-gradient-to-br from-slate-800/90 to-slate-900/90 backdrop-blur-sm rounded-3xl p-8 shadow-2xl border border-slate-700/50">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center gap-3 mb-4">
                        <span class="text-5xl">💸</span>
                        <h2 class="text-4xl font-bold bg-gradient-to-r from-green-400 to-emerald-500 bg-clip-text text-transparent">
                            กระแสเงินสด 3D
                        </h2>
                    </div>
                    <p class="text-gray-300 text-lg">ดูการไหลของเงินจากทุกช่องทางรายได้แบบเรียลไทม์</p>
                </div>

                <!-- 3D Income Flow Container -->
                <div class="relative bg-black/50 rounded-2xl overflow-hidden" style="height: 600px;">
                    <div id="wealth-3d-income-flow" class="w-full h-full"></div>
                </div>

                <!-- Income Summary -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mt-6">
                    <div class="p-4 bg-gradient-to-br from-cyan-500/20 to-cyan-600/20 rounded-xl border border-cyan-400/30">
                        <div class="text-xs text-cyan-300 mb-1">Direct</div>
                        <div class="text-xl font-bold text-cyan-200">฿15,000</div>
                    </div>
                    <div class="p-4 bg-gradient-to-br from-red-500/20 to-red-600/20 rounded-xl border border-red-400/30">
                        <div class="text-xs text-red-300 mb-1">Binary</div>
                        <div class="text-xl font-bold text-red-200">฿8,000</div>
                    </div>
                    <div class="p-4 bg-gradient-to-br from-green-500/20 to-green-600/20 rounded-xl border border-green-400/30">
                        <div class="text-xs text-green-300 mb-1">Rank</div>
                        <div class="text-xl font-bold text-green-200">฿25,000</div>
                    </div>
                    <div class="p-4 bg-gradient-to-br from-pink-500/20 to-pink-600/20 rounded-xl border border-pink-400/30">
                        <div class="text-xs text-pink-300 mb-1">Sponsor</div>
                        <div class="text-xl font-bold text-pink-200">฿5,000</div>
                    </div>
                    <div class="p-4 bg-gradient-to-br from-yellow-500/20 to-yellow-600/20 rounded-xl border border-yellow-400/30">
                        <div class="text-xs text-yellow-300 mb-1">Marketplace</div>
                        <div class="text-xl font-bold text-yellow-200">฿12,000</div>
                    </div>
                    <div class="p-4 bg-gradient-to-br from-purple-500/20 to-purple-600/20 rounded-xl border border-purple-400/30">
                        <div class="text-xs text-purple-300 mb-1">Team</div>
                        <div class="text-xl font-bold text-purple-200">฿18,000</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Comprehensive Income Streams Guide -->
        <section id="income-overview" class="mb-16 scroll-mt-20">
            <div class="text-center mb-12">
                <div class="inline-flex items-center gap-3 mb-4">
                    <span class="text-5xl">💰</span>
                    <h2 class="text-4xl font-bold bg-gradient-to-r from-yellow-400 to-orange-500 bg-clip-text text-transparent">
                        6 ช่องทางรายได้ครบวงจร
                    </h2>
                </div>
                <p class="text-gray-300 text-lg max-w-3xl mx-auto">
                    สร้างรายได้จากหลากหลายช่องทาง ไม่พึ่งพาช่องทางเดียว มั่นคงและยั่งยืน
                </p>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Income Stream 1: Direct Commission -->
                <div class="group bg-gradient-to-br from-slate-800/90 to-slate-900/90 backdrop-blur-sm rounded-2xl p-6 shadow-xl border border-slate-700/50 hover:border-cyan-500/50 transition-all hover:scale-[1.02]">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="text-5xl">💵</div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-cyan-400 mb-2">1. Direct Commission (รายได้ตรง)</h3>
                            <div class="inline-block px-3 py-1 bg-cyan-500/20 text-cyan-300 rounded-full text-sm font-semibold">
                                30-15% ต่อระดับ
                            </div>
                        </div>
                    </div>

                    <p class="text-gray-300 mb-4">
                        รายได้จากการขายตรงและทีมของคุณ แบบ Unilevel ลึกถึง 5 ชั้น
                    </p>

                    <div class="space-y-3 mb-4">
                        <div class="flex items-center justify-between p-3 bg-slate-700/30 rounded-lg">
                            <span class="text-gray-300">ชั้นที่ 1 (Direct)</span>
                            <span class="font-bold text-cyan-400">30%</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-700/30 rounded-lg">
                            <span class="text-gray-300">ชั้นที่ 2</span>
                            <span class="font-bold text-cyan-400">20%</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-700/30 rounded-lg">
                            <span class="text-gray-300">ชั้นที่ 3-5</span>
                            <span class="font-bold text-cyan-400">10-15%</span>
                        </div>
                    </div>

                    <div class="p-4 bg-cyan-500/10 border border-cyan-500/30 rounded-xl">
                        <div class="text-sm text-cyan-300 mb-2">💡 ตัวอย่างรายได้:</div>
                        <div class="text-gray-300 text-sm">
                            สมาชิกชั้น 1 ซื้อ 1,000฿ = คุณได้ <strong class="text-cyan-400">300฿</strong><br>
                            มี 10 คน = <strong class="text-cyan-400">3,000฿/เดือน</strong>
                        </div>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('user.mlm.genealogy') }}"
                           class="flex-1 px-4 py-2 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 transition-all text-center text-sm font-semibold">
                            ดูโครงสร้างทีม
                        </a>
                        <a href="{{ route('user.mlm.income-simulator') }}"
                           class="flex-1 px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition-all text-center text-sm font-semibold">
                            คำนวณรายได้
                        </a>
                    </div>
                </div>

                <!-- Income Stream 2: Binary Matching -->
                <div class="group bg-gradient-to-br from-slate-800/90 to-slate-900/90 backdrop-blur-sm rounded-2xl p-6 shadow-xl border border-slate-700/50 hover:border-red-500/50 transition-all hover:scale-[1.02]">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="text-5xl">⚖️</div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-red-400 mb-2">2. Binary Matching Bonus</h3>
                            <div class="inline-block px-3 py-1 bg-red-500/20 text-red-300 rounded-full text-sm font-semibold">
                                1,000฿ ต่อคู่
                            </div>
                        </div>
                    </div>

                    <p class="text-gray-300 mb-4">
                        รายได้จากคู่ขาซ้าย-ขวา ยิ่งสมดุลยิ่งได้มาก ระบบ Binary Tree
                    </p>

                    <div class="space-y-3 mb-4">
                        <div class="p-3 bg-slate-700/30 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-300">ขาซ้าย: 5 คน</span>
                                <span class="text-red-400">5 PV</span>
                            </div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-300">ขาขวา: 5 คน</span>
                                <span class="text-red-400">5 PV</span>
                            </div>
                            <div class="border-t border-slate-600 pt-2 mt-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-300 font-semibold">ได้ 5 คู่</span>
                                    <span class="font-bold text-red-400">= 5,000฿</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-red-500/10 border border-red-500/30 rounded-xl">
                        <div class="text-sm text-red-300 mb-2">💡 กลยุทธ์:</div>
                        <div class="text-gray-300 text-sm">
                            สร้างทีมให้สมดุลทั้ง 2 ขา จะได้รายได้สูงสุด<br>
                            <strong class="text-red-400">50 คู่/สัปดาห์ = 200,000฿/เดือน</strong>
                        </div>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('user.mlm.genealogy') }}?view=binary"
                           class="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all text-center text-sm font-semibold">
                            ดู Binary Tree
                        </a>
                        <button onclick="calculateBinaryBonus()"
                                class="flex-1 px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition-all text-center text-sm font-semibold">
                            คำนวณโบนัส
                        </button>
                    </div>
                </div>

                <!-- Income Stream 3: Rank Achievement Bonus -->
                <div class="group bg-gradient-to-br from-slate-800/90 to-slate-900/90 backdrop-blur-sm rounded-2xl p-6 shadow-xl border border-slate-700/50 hover:border-green-500/50 transition-all hover:scale-[1.02]">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="text-5xl">👑</div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-green-400 mb-2">3. Rank Achievement Bonus</h3>
                            <div class="inline-block px-3 py-1 bg-green-500/20 text-green-300 rounded-full text-sm font-semibold">
                                5,000฿ - 500,000฿
                            </div>
                        </div>
                    </div>

                    <p class="text-gray-300 mb-4">
                        โบนัสพิเศษเมื่อคุณขึ้นยศ ทั้งโบนัสครั้งเดียวและรายเดือน
                    </p>

                    <div class="space-y-2 mb-4">
                        <div class="flex items-center justify-between p-2 bg-gradient-to-r from-orange-900/30 to-orange-800/30 rounded-lg border border-orange-600/30">
                            <span class="text-orange-300">🥉 Bronze</span>
                            <span class="font-bold text-orange-400">5,000฿ + 1,000฿/ด.</span>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-gradient-to-r from-gray-400/30 to-gray-300/30 rounded-lg border border-gray-400/30">
                            <span class="text-gray-200">🥈 Silver</span>
                            <span class="font-bold text-gray-300">20,000฿ + 5,000฿/ด.</span>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-gradient-to-r from-yellow-600/30 to-yellow-500/30 rounded-lg border border-yellow-500/30">
                            <span class="text-yellow-300">🥇 Gold</span>
                            <span class="font-bold text-yellow-400">50,000฿ + 15,000฿/ด.</span>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-gradient-to-r from-purple-600/30 to-purple-500/30 rounded-lg border border-purple-500/30">
                            <span class="text-purple-300">💎 Platinum</span>
                            <span class="font-bold text-purple-400">200,000฿ + 50,000฿/ด.</span>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-gradient-to-r from-blue-400/30 to-cyan-400/30 rounded-lg border border-cyan-400/30">
                            <span class="text-cyan-300">💠 Diamond</span>
                            <span class="font-bold text-cyan-400">500,000฿ + 150,000฿/ด.</span>
                        </div>
                    </div>

                    <div class="p-4 bg-green-500/10 border border-green-500/30 rounded-xl">
                        <div class="text-sm text-green-300 mb-2">💡 เป้าหมาย:</div>
                        <div class="text-gray-300 text-sm">
                            Diamond = <strong class="text-green-400">650,000฿ ในเดือนแรก</strong><br>
                            + <strong class="text-green-400">150,000฿ ทุกเดือน</strong> ตลอดชีวิต
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="#rank-roadmap"
                           class="block w-full px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-all text-center text-sm font-semibold">
                            ดู Roadmap สู่ Diamond
                        </a>
                    </div>
                </div>

                <!-- Income Stream 4: Sponsorship Bonus -->
                <div class="group bg-gradient-to-br from-slate-800/90 to-slate-900/90 backdrop-blur-sm rounded-2xl p-6 shadow-xl border border-slate-700/50 hover:border-pink-500/50 transition-all hover:scale-[1.02]">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="text-5xl">🤝</div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-pink-400 mb-2">4. Sponsorship Bonus</h3>
                            <div class="inline-block px-3 py-1 bg-pink-500/20 text-pink-300 rounded-full text-sm font-semibold">
                                10-20% ของยอดแรก
                            </div>
                        </div>
                    </div>

                    <p class="text-gray-300 mb-4">
                        โบนัสพิเศษเมื่อคุณแนะนำสมาชิกใหม่ รับทันทีจากยอดซื้อแรก
                    </p>

                    <div class="space-y-3 mb-4">
                        <div class="p-3 bg-slate-700/30 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-300">สมาชิกธรรมดา</span>
                                <span class="font-bold text-pink-400">10%</span>
                            </div>
                            <div class="text-sm text-gray-400">
                                แนะนำ 1 คน ซื้อ 5,000฿ = คุณได้ 500฿
                            </div>
                        </div>
                        <div class="p-3 bg-slate-700/30 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-300">สมาชิกระดับสูง</span>
                                <span class="font-bold text-pink-400">20%</span>
                            </div>
                            <div class="text-sm text-gray-400">
                                แนะนำ 1 คน ซื้อ 20,000฿ = คุณได้ 4,000฿
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-pink-500/10 border border-pink-500/30 rounded-xl">
                        <div class="text-sm text-pink-300 mb-2">💡 เคล็ดลับ:</div>
                        <div class="text-gray-300 text-sm">
                            แนะนำ <strong class="text-pink-400">10 คน/เดือน</strong> ๆ ละ 5,000฿<br>
                            = <strong class="text-pink-400">5,000฿ โบนัสทันที</strong>
                        </div>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <button onclick="copyReferralLink()"
                                class="flex-1 px-4 py-2 bg-pink-500 text-white rounded-lg hover:bg-pink-600 transition-all text-center text-sm font-semibold">
                            📋 Copy ลิงก์แนะนำ
                        </button>
                        <button onclick="generateQRCode()"
                                class="flex-1 px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition-all text-center text-sm font-semibold">
                            📱 QR Code
                        </button>
                    </div>
                </div>

                <!-- Income Stream 5: Marketplace Affiliate -->
                <div class="group bg-gradient-to-br from-slate-800/90 to-slate-900/90 backdrop-blur-sm rounded-2xl p-6 shadow-xl border border-slate-700/50 hover:border-yellow-500/50 transition-all hover:scale-[1.02]">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="text-5xl">🛒</div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-yellow-400 mb-2">5. Marketplace Affiliate</h3>
                            <div class="flex gap-2 flex-wrap">
                                <span class="px-2 py-1 bg-purple-500/20 text-purple-300 rounded text-xs font-semibold">Lazada 2-10%</span>
                                <span class="px-2 py-1 bg-orange-500/20 text-orange-300 rounded text-xs font-semibold">Shopee 5-15%</span>
                                <span class="px-2 py-1 bg-cyan-500/20 text-cyan-300 rounded text-xs font-semibold">TikTok 8-20%</span>
                            </div>
                        </div>
                    </div>

                    <p class="text-gray-300 mb-4">
                        รายได้จากการเป็น Affiliate ของ Lazada, Shopee, TikTok Shop
                    </p>

                    <div class="space-y-2 mb-4">
                        <div class="flex items-center gap-3 p-3 bg-gradient-to-r from-purple-900/30 to-purple-800/30 rounded-lg border border-purple-600/30">
                            <span class="text-2xl">🛒</span>
                            <div class="flex-1">
                                <div class="text-sm text-purple-300">Lazada</div>
                                <div class="text-xs text-gray-400">Commission: 2-10%</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 bg-gradient-to-r from-orange-900/30 to-orange-800/30 rounded-lg border border-orange-600/30">
                            <span class="text-2xl">🛍️</span>
                            <div class="flex-1">
                                <div class="text-sm text-orange-300">Shopee</div>
                                <div class="text-xs text-gray-400">Commission: 5-15%</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 bg-gradient-to-r from-cyan-900/30 to-cyan-800/30 rounded-lg border border-cyan-600/30">
                            <span class="text-2xl">🎵</span>
                            <div class="flex-1">
                                <div class="text-sm text-cyan-300">TikTok Shop</div>
                                <div class="text-xs text-gray-400">Commission: 8-20%</div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-xl">
                        <div class="text-sm text-yellow-300 mb-2">💡 โอกาส:</div>
                        <div class="text-gray-300 text-sm">
                            แชร์สินค้า <strong class="text-yellow-400">ราคา 1,000฿</strong> มี 100 คนซื้อ<br>
                            Commission 10% = <strong class="text-yellow-400">10,000฿</strong>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('user.marketplace.products') }}"
                           class="block w-full px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-all text-center text-sm font-semibold">
                            🚀 เริ่มต้น Marketplace Affiliate
                        </a>
                    </div>
                </div>

                <!-- Income Stream 6: Team Override -->
                <div class="group bg-gradient-to-br from-slate-800/90 to-slate-900/90 backdrop-blur-sm rounded-2xl p-6 shadow-xl border border-slate-700/50 hover:border-purple-500/50 transition-all hover:scale-[1.02]">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="text-5xl">👥</div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-purple-400 mb-2">6. Team Override Bonus</h3>
                            <div class="inline-block px-3 py-1 bg-purple-500/20 text-purple-300 rounded-full text-sm font-semibold">
                                5-10% จากทีม
                            </div>
                        </div>
                    </div>

                    <p class="text-gray-300 mb-4">
                        รายได้จากยอดขายรวมของทีม - ยิ่งทีมใหญ่ยิ่งได้มาก
                    </p>

                    <div class="space-y-3 mb-4">
                        <div class="p-3 bg-slate-700/30 rounded-lg">
                            <div class="text-sm text-gray-300 mb-2">ตัวอย่างการคำนวณ:</div>
                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-400">ทีมรวม 100 คน</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">ยอดขายเฉลี่ย 2,000฿/คน/เดือน</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">ยอดรวม = 200,000฿</span>
                                </div>
                                <div class="border-t border-slate-600 pt-2 mt-2 flex justify-between">
                                    <span class="text-gray-300 font-semibold">Override 5%</span>
                                    <span class="font-bold text-purple-400">= 10,000฿</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-purple-500/10 border border-purple-500/30 rounded-xl">
                        <div class="text-sm text-purple-300 mb-2">💡 กลยุทธ์:</div>
                        <div class="text-gray-300 text-sm">
                            สร้างทีม <strong class="text-purple-400">500 คน</strong><br>
                            = <strong class="text-purple-400">50,000฿/เดือน</strong> จาก Override
                        </div>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('user.mlm.genealogy') }}"
                           class="flex-1 px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-all text-center text-sm font-semibold">
                            📊 ดูทีม
                        </a>
                        <button onclick="calculateTeamBonus()"
                                class="flex-1 px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition-all text-center text-sm font-semibold">
                            💰 คำนวณ
                        </button>
                    </div>
                </div>
            </div>

            <!-- Total Potential -->
            <div class="mt-8 p-8 bg-gradient-to-r from-yellow-500/20 via-orange-500/20 to-red-500/20 rounded-2xl border-2 border-yellow-500/50">
                <div class="text-center">
                    <div class="text-4xl mb-4">🎯</div>
                    <h3 class="text-3xl font-bold text-yellow-400 mb-4">รายได้รวมที่เป็นไปได้</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                        <div class="p-4 bg-slate-800/50 rounded-xl">
                            <div class="text-sm text-gray-300 mb-1">ระดับเริ่มต้น</div>
                            <div class="text-2xl font-bold text-green-400">฿10,000+</div>
                            <div class="text-xs text-gray-400">ต่อเดือน</div>
                        </div>
                        <div class="p-4 bg-slate-800/50 rounded-xl">
                            <div class="text-sm text-gray-300 mb-1">ระดับกลาง</div>
                            <div class="text-2xl font-bold text-yellow-400">฿50,000+</div>
                            <div class="text-xs text-gray-400">ต่อเดือน</div>
                        </div>
                        <div class="p-4 bg-slate-800/50 rounded-xl col-span-2 md:col-span-1">
                            <div class="text-sm text-gray-300 mb-1">ระดับสูง (Diamond)</div>
                            <div class="text-2xl font-bold text-orange-400">฿200,000+</div>
                            <div class="text-xs text-gray-400">ต่อเดือน</div>
                        </div>
                    </div>
                    <p class="text-gray-300 text-sm">
                        * รายได้ขึ้นอยู่กับความพยายามและการทำงานของคุณและทีม
                    </p>
                </div>
            </div>
        </section>

        <!-- Income Calculator -->
        <section id="income-calculator" class="mb-16 scroll-mt-20">
            <div class="bg-gradient-to-br from-slate-800/90 to-slate-900/90 backdrop-blur-sm rounded-3xl p-8 shadow-2xl border border-slate-700/50">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center gap-3 mb-4">
                        <span class="text-5xl">🧮</span>
                        <h2 class="text-4xl font-bold bg-gradient-to-r from-blue-400 to-cyan-500 bg-clip-text text-transparent">
                            เครื่องคำนวณรายได้
                        </h2>
                    </div>
                    <p class="text-gray-300 text-lg">คำนวณรายได้ที่คุณสามารถทำได้ตามเป้าหมายของคุณ</p>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Calculator Input -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">จำนวนสมาชิกที่แนะนำต่อเดือน</label>
                            <input type="number" id="calc-direct-sales" value="5" min="0"
                                   class="w-full px-4 py-3 bg-slate-700 text-white rounded-lg border border-slate-600 focus:border-cyan-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">ยอดซื้อเฉลี่ยต่อคน (฿)</label>
                            <input type="number" id="calc-avg-purchase" value="3000" min="0" step="100"
                                   class="w-full px-4 py-3 bg-slate-700 text-white rounded-lg border border-slate-600 focus:border-cyan-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">ขนาดทีมปัจจุบัน (คน)</label>
                            <input type="number" id="calc-team-size" value="20" min="0"
                                   class="w-full px-4 py-3 bg-slate-700 text-white rounded-lg border border-slate-600 focus:border-cyan-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Binary Pairs ต่อสัปดาห์</label>
                            <input type="number" id="calc-binary-pairs" value="10" min="0"
                                   class="w-full px-4 py-3 bg-slate-700 text-white rounded-lg border border-slate-600 focus:border-cyan-500 focus:outline-none">
                        </div>

                        <button onclick="calculateIncome()"
                                class="w-full px-6 py-4 bg-gradient-to-r from-cyan-500 to-blue-500 text-white font-bold rounded-xl hover:from-cyan-600 hover:to-blue-600 transition-transform hover:scale-[1.02] transition-all shadow-lg">
                            💎 คำนวณรายได้
                        </button>
                    </div>

                    <!-- Calculator Results -->
                    <div class="bg-slate-900/50 rounded-2xl p-6 border border-slate-700">
                        <h3 class="text-xl font-bold text-cyan-400 mb-4">รายได้ประมาณการต่อเดือน</h3>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-cyan-500/10 rounded-lg border border-cyan-500/30">
                                <span class="text-gray-300 text-sm">Direct Commission</span>
                                <span class="font-bold text-cyan-400" id="result-direct">฿0</span>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-red-500/10 rounded-lg border border-red-500/30">
                                <span class="text-gray-300 text-sm">Binary Matching</span>
                                <span class="font-bold text-red-400" id="result-binary">฿0</span>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-pink-500/10 rounded-lg border border-pink-500/30">
                                <span class="text-gray-300 text-sm">Sponsor Bonus</span>
                                <span class="font-bold text-pink-400" id="result-sponsor">฿0</span>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-purple-500/10 rounded-lg border border-purple-500/30">
                                <span class="text-gray-300 text-sm">Team Override</span>
                                <span class="font-bold text-purple-400" id="result-team">฿0</span>
                            </div>

                            <div class="border-t-2 border-yellow-500 pt-4 mt-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-xl font-bold text-gray-200">รายได้รวม</span>
                                    <span class="text-3xl font-bold text-yellow-400" id="result-total">฿0</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 p-4 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                            <div class="text-sm text-blue-300 mb-2">📈 รายได้ต่อปี (โดยประมาณ)</div>
                            <div class="text-2xl font-bold text-blue-400" id="result-yearly">฿0</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Rank Roadmap -->
        <section id="rank-roadmap" class="mb-16 scroll-mt-20">
            <div class="text-center mb-12">
                <div class="inline-flex items-center gap-3 mb-4">
                    <span class="text-5xl">🗺️</span>
                    <h2 class="text-4xl font-bold bg-gradient-to-r from-orange-400 to-red-500 bg-clip-text text-transparent">
                        Roadmap สู่ Diamond
                    </h2>
                </div>
                <p class="text-gray-300 text-lg max-w-3xl mx-auto">
                    เส้นทางชัดเจนจากมือใหม่สู่ระดับ Diamond พร้อมเป้าหมายที่วัดผลได้
                </p>
            </div>

            <div class="relative">
                <!-- Progress Line -->
                <div class="absolute left-1/2 top-0 bottom-0 w-1 bg-gradient-to-b from-orange-500 via-gray-500 to-cyan-500 hidden md:block"></div>

                <!-- Rank Steps -->
                <div class="space-y-8">
                    <!-- Bronze -->
                    <div class="relative">
                        <div class="md:grid md:grid-cols-2 md:gap-8 items-center">
                            <div class="md:text-right mb-4 md:mb-0">
                                <div class="inline-block md:block bg-gradient-to-br from-orange-500/20 to-orange-600/20 backdrop-blur-sm rounded-2xl p-6 border border-orange-500/50">
                                    <div class="flex items-center gap-4 md:flex-row-reverse md:justify-end">
                                        <span class="text-5xl">🥉</span>
                                        <div>
                                            <h3 class="text-2xl font-bold text-orange-400 mb-2">Bronze</h3>
                                            <div class="text-sm text-gray-300">ระดับเริ่มต้น</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="relative md:pl-8">
                                <div class="absolute left-1/2 md:left-0 top-1/2 -translate-x-1/2 -translate-y-1/2 w-8 h-8 bg-orange-500 rounded-full border-4 border-slate-900 hidden md:block"></div>
                                <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700">
                                    <div class="text-sm text-gray-300 mb-2">เงื่อนไข:</div>
                                    <ul class="text-sm text-gray-400 space-y-1 mb-3">
                                        <li>✓ สมัครสมาชิก</li>
                                        <li>✓ มียอดซื้อขั้นต่ำ 5,000฿</li>
                                        <li>✓ แนะนำ 3 คน</li>
                                    </ul>
                                    <div class="text-orange-400 font-bold">รางวัล: 5,000฿ + 1,000฿/เดือน</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Silver -->
                    <div class="relative">
                        <div class="md:grid md:grid-cols-2 md:gap-8 items-center">
                            <div class="md:col-start-2 mb-4 md:mb-0">
                                <div class="inline-block md:block bg-gradient-to-br from-gray-400/20 to-gray-500/20 backdrop-blur-sm rounded-2xl p-6 border border-gray-400/50">
                                    <div class="flex items-center gap-4">
                                        <span class="text-5xl">🥈</span>
                                        <div>
                                            <h3 class="text-2xl font-bold text-gray-300 mb-2">Silver</h3>
                                            <div class="text-sm text-gray-400">ระดับกลาง</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="relative md:pr-8 md:text-right md:row-start-1 md:col-start-1">
                                <div class="absolute left-1/2 md:right-0 md:left-auto top-1/2 -translate-x-1/2 md:translate-x-1/2 -translate-y-1/2 w-8 h-8 bg-gray-400 rounded-full border-4 border-slate-900 hidden md:block"></div>
                                <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700">
                                    <div class="text-sm text-gray-300 mb-2">เงื่อนไข:</div>
                                    <ul class="text-sm text-gray-400 space-y-1 mb-3">
                                        <li>✓ ทีม 15 คน</li>
                                        <li>✓ ยอดขายรวม 50,000฿/เดือน</li>
                                        <li>✓ มี Bronze 3 คน</li>
                                    </ul>
                                    <div class="text-gray-300 font-bold">รางวัล: 20,000฿ + 5,000฿/เดือน</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gold -->
                    <div class="relative">
                        <div class="md:grid md:grid-cols-2 md:gap-8 items-center">
                            <div class="md:text-right mb-4 md:mb-0">
                                <div class="inline-block md:block bg-gradient-to-br from-yellow-500/20 to-yellow-600/20 backdrop-blur-sm rounded-2xl p-6 border border-yellow-500/50">
                                    <div class="flex items-center gap-4 md:flex-row-reverse md:justify-end">
                                        <span class="text-5xl">🥇</span>
                                        <div>
                                            <h3 class="text-2xl font-bold text-yellow-400 mb-2">Gold</h3>
                                            <div class="text-sm text-gray-300">ระดับสูง</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="relative md:pl-8">
                                <div class="absolute left-1/2 md:left-0 top-1/2 -translate-x-1/2 -translate-y-1/2 w-8 h-8 bg-yellow-500 rounded-full border-4 border-slate-900 hidden md:block"></div>
                                <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700">
                                    <div class="text-sm text-gray-300 mb-2">เงื่อนไข:</div>
                                    <ul class="text-sm text-gray-400 space-y-1 mb-3">
                                        <li>✓ ทีม 50 คน</li>
                                        <li>✓ ยอดขายรวม 150,000฿/เดือน</li>
                                        <li>✓ มี Silver 3 คน</li>
                                    </ul>
                                    <div class="text-yellow-400 font-bold">รางวัล: 50,000฿ + 15,000฿/เดือน</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Platinum -->
                    <div class="relative">
                        <div class="md:grid md:grid-cols-2 md:gap-8 items-center">
                            <div class="md:col-start-2 mb-4 md:mb-0">
                                <div class="inline-block md:block bg-gradient-to-br from-purple-500/20 to-purple-600/20 backdrop-blur-sm rounded-2xl p-6 border border-purple-500/50">
                                    <div class="flex items-center gap-4">
                                        <span class="text-5xl">💎</span>
                                        <div>
                                            <h3 class="text-2xl font-bold text-purple-400 mb-2">Platinum</h3>
                                            <div class="text-sm text-gray-300">ระดับมืออาชีพ</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="relative md:pr-8 md:text-right md:row-start-1 md:col-start-1">
                                <div class="absolute left-1/2 md:right-0 md:left-auto top-1/2 -translate-x-1/2 md:translate-x-1/2 -translate-y-1/2 w-8 h-8 bg-purple-500 rounded-full border-4 border-slate-900 hidden md:block"></div>
                                <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700">
                                    <div class="text-sm text-gray-300 mb-2">เงื่อนไข:</div>
                                    <ul class="text-sm text-gray-400 space-y-1 mb-3">
                                        <li>✓ ทีม 150 คน</li>
                                        <li>✓ ยอดขายรวม 500,000฿/เดือน</li>
                                        <li>✓ มี Gold 3 คน</li>
                                    </ul>
                                    <div class="text-purple-400 font-bold">รางวัล: 200,000฿ + 50,000฿/เดือน</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Diamond -->
                    <div class="relative">
                        <div class="md:grid md:grid-cols-2 md:gap-8 items-center">
                            <div class="md:text-right mb-4 md:mb-0">
                                <div class="inline-block md:block bg-gradient-to-br from-cyan-400/20 to-blue-500/20 backdrop-blur-sm rounded-2xl p-6 border-2 border-cyan-400/70 shadow-2xl">
                                    <div class="flex items-center gap-4 md:flex-row-reverse md:justify-end">
                                        <span class="text-5xl">💠</span>
                                        <div>
                                            <h3 class="text-2xl font-bold text-cyan-400 mb-2">Diamond</h3>
                                            <div class="text-sm text-gray-300">ระดับสูงสุด 🏆</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="relative md:pl-8">
                                <div class="absolute left-1/2 md:left-0 top-1/2 -translate-x-1/2 -translate-y-1/2 w-10 h-10 bg-gradient-to-r from-cyan-400 to-blue-500 rounded-full border-4 border-slate-900 hidden md:block animate-pulse"></div>
                                <div class="bg-slate-800/50 rounded-xl p-4 border-2 border-cyan-400/50">
                                    <div class="text-sm text-gray-300 mb-2">เงื่อนไข:</div>
                                    <ul class="text-sm text-gray-400 space-y-1 mb-3">
                                        <li>✓ ทีม 500 คน</li>
                                        <li>✓ ยอดขายรวม 2,000,000฿/เดือน</li>
                                        <li>✓ มี Platinum 3 คน</li>
                                    </ul>
                                    <div class="text-cyan-400 font-bold text-lg">รางวัล: 500,000฿ + 150,000฿/เดือน</div>
                                    <div class="text-xs text-gray-400 mt-2">+ สิทธิพิเศษอื่นๆ มากมาย</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline Summary -->
            <div class="mt-12 p-6 bg-gradient-to-r from-cyan-500/10 to-blue-500/10 rounded-2xl border border-cyan-500/30">
                <div class="text-center mb-4">
                    <h3 class="text-2xl font-bold text-cyan-400 mb-2">⏱️ กี่นานถึงขึ้น Diamond?</h3>
                    <p class="text-gray-300">ขึ้นอยู่กับความพยายามและกลยุทธ์ของคุณ</p>
                </div>
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="p-4 bg-slate-800/50 rounded-xl text-center">
                        <div class="text-3xl mb-2">🚀</div>
                        <div class="text-lg font-bold text-green-400 mb-1">Fast Track</div>
                        <div class="text-2xl font-bold text-white mb-2">6-12 เดือน</div>
                        <div class="text-sm text-gray-400">ทำงานเต็มเวลา + สร้างทีมเร็ว</div>
                    </div>
                    <div class="p-4 bg-slate-800/50 rounded-xl text-center">
                        <div class="text-3xl mb-2">📈</div>
                        <div class="text-lg font-bold text-yellow-400 mb-1">Standard</div>
                        <div class="text-2xl font-bold text-white mb-2">1-2 ปี</div>
                        <div class="text-sm text-gray-400">ทำงานสม่ำเสมอ + สร้างทีมแข็งแกร่ง</div>
                    </div>
                    <div class="p-4 bg-slate-800/50 rounded-xl text-center">
                        <div class="text-3xl mb-2">🎯</div>
                        <div class="text-lg font-bold text-blue-400 mb-1">Steady</div>
                        <div class="text-2xl font-bold text-white mb-2">2-3 ปี</div>
                        <div class="text-sm text-gray-400">ทำงานเป็นรายได้เสริม</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Action Steps -->
        <section class="mb-16">
            <div class="bg-gradient-to-br from-orange-500/20 to-red-500/20 backdrop-blur-sm rounded-3xl p-8 border-2 border-orange-500/50 shadow-2xl">
                <div class="text-center mb-8">
                    <div class="text-6xl mb-4">🚀</div>
                    <h2 class="text-4xl font-bold text-orange-400 mb-4">เริ่มต้นวันนี้!</h2>
                    <p class="text-xl text-gray-300 max-w-2xl mx-auto">
                        ทุกการเดินทางเริ่มต้นด้วยก้าวแรก - ก้าวของคุณคืออะไร?
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                    <div class="text-center p-6 bg-slate-800/50 rounded-xl hover:bg-slate-800/70 transition-all">
                        <div class="text-4xl mb-4">1️⃣</div>
                        <h3 class="text-xl font-bold text-white mb-2">ศึกษาระบบ</h3>
                        <p class="text-gray-400 text-sm mb-4">เรียนรู้ทุกช่องทางรายได้</p>
                        <a href="{{ route('user.wealth-guide') }}"
                           class="inline-block px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-all text-sm font-semibold">
                            อ่านคู่มือฉบับเต็ม
                        </a>
                    </div>

                    <div class="text-center p-6 bg-slate-800/50 rounded-xl hover:bg-slate-800/70 transition-all">
                        <div class="text-4xl mb-4">2️⃣</div>
                        <h3 class="text-xl font-bold text-white mb-2">วางแผน</h3>
                        <p class="text-gray-400 text-sm mb-4">คำนวณเป้าหมายของคุณ</p>
                        <a href="{{ route('user.mlm.income-simulator') }}"
                           class="inline-block px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-all text-sm font-semibold">
                            ใช้เครื่องคำนวณ
                        </a>
                    </div>

                    <div class="text-center p-6 bg-slate-800/50 rounded-xl hover:bg-slate-800/70 transition-all">
                        <div class="text-4xl mb-4">3️⃣</div>
                        <h3 class="text-xl font-bold text-white mb-2">ลงมือทำ</h3>
                        <p class="text-gray-400 text-sm mb-4">เริ่มแชร์และสร้างทีม</p>
                        <button onclick="copyReferralLink()"
                                class="inline-block px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-all text-sm font-semibold">
                            Copy ลิงก์แนะนำ
                        </button>
                    </div>
                </div>

                <div class="mt-8 text-center">
                    <a href="{{ route('user.dashboard') }}"
                       class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-orange-500 to-red-500 text-white font-bold rounded-xl hover:from-orange-600 hover:to-red-600 transition-transform hover:scale-[1.02] transition-all shadow-xl text-lg">
                        <span>🏠</span>
                        <span>ไปที่แดชบอร์ด</span>
                    </a>
                </div>
            </div>
        </section>

    </div>
</div>

@vite(['resources/js/wealth-guide-pro.js'])

<script>
// Smooth scroll to section
function scrollToSection(sectionId) {
    document.getElementById(sectionId)?.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
}

// Income Calculator
function calculateIncome() {
    const directSales = parseInt(document.getElementById('calc-direct-sales').value) || 0;
    const avgPurchase = parseInt(document.getElementById('calc-avg-purchase').value) || 0;
    const teamSize = parseInt(document.getElementById('calc-team-size').value) || 0;
    const binaryPairs = parseInt(document.getElementById('calc-binary-pairs').value) || 0;

    // Calculations
    const directCommission = directSales * avgPurchase * 0.3;
    const binaryBonus = binaryPairs * 1000 * 4; // per week * 4 weeks
    const sponsorBonus = directSales * avgPurchase * 0.15;
    const teamOverride = teamSize * avgPurchase * 0.05;

    const total = directCommission + binaryBonus + sponsorBonus + teamOverride;
    const yearly = total * 12;

    // Update results
    document.getElementById('result-direct').textContent = `฿${Math.floor(directCommission).toLocaleString()}`;
    document.getElementById('result-binary').textContent = `฿${Math.floor(binaryBonus).toLocaleString()}`;
    document.getElementById('result-sponsor').textContent = `฿${Math.floor(sponsorBonus).toLocaleString()}`;
    document.getElementById('result-team').textContent = `฿${Math.floor(teamOverride).toLocaleString()}`;
    document.getElementById('result-total').textContent = `฿${Math.floor(total).toLocaleString()}`;
    document.getElementById('result-yearly').textContent = `฿${Math.floor(yearly).toLocaleString()}`;

    // Animate numbers
    animateValue('result-total', 0, total, 1000);
}

function animateValue(id, start, end, duration) {
    const element = document.getElementById(id);
    const startTime = Date.now();
    const animate = () => {
        const elapsed = Date.now() - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const current = Math.floor(start + (end - start) * progress);
        element.textContent = `฿${current.toLocaleString()}`;
        if (progress < 1) {
            requestAnimationFrame(animate);
        }
    };
    animate();
}

// Copy referral link
function copyReferralLink() {
    const referralCode = '{{ auth()->user()->affiliate_code ?? "YOUR_CODE" }}';
    const link = `{{ url('/register') }}?ref=${referralCode}`;
    navigator.clipboard.writeText(link).then(() => {
        alert('✅ คัดลอกลิงก์แนะนำเรียบร้อย!\n\n' + link);
    });
}

// Generate QR Code
function generateQRCode() {
    alert('🚧 ฟีเจอร์ QR Code กำลังพัฒนา...');
}

// Calculate initial income on load
document.addEventListener('DOMContentLoaded', function() {
    calculateIncome();
});
</script>

@endsection
